<?php

/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

namespace Eventum\Attachment;

use Auth;
use CRM;
use Date_Helper;
use DB_Helper;
use Eventum\Attachment\Exceptions\AttachmentException;
use Eventum\Attachment\Exceptions\AttachmentGroupNotFoundException;
use Eventum\Attachment\Exceptions\AttachmentNotFoundException;
use Eventum\Db\DatabaseException;
use History;
use Issue;
use League\Flysystem\FileNotFoundException;
use Link_Filter;
use LogicException;
use Misc;
use Notification;
use RuntimeException;
use SplFileInfo;
use User;
use Workflow;

class AttachmentManager
{
    /**
     * Files uploaded, but not linked to attachment are expired after this time passes
     * use 24h, this is very safe value
     *
     * @see cleanupAbandonedFiles()
     */
    const ATTACHMENT_EXPIRE_TIME = 86400;

    /**
     * Adds multiple files to attachment_file table
     *
     * The files has multidimensional structure (here are 3 files uploaded):
     * $files => [
     *   'name' => [
     *     0 => 'file1.png',
     *     1 => 'file2.png',
     *     2 => null,
     *   ],
     *   'type' => [
     *     0 => 'image/png',
     *     1 => 'image/png',
     *     2 => null,
     *   ],
     *   'tmp_name' => [
     *     0 => '/tmp/phpjESjor',
     *     1 => '/tmp/phpX2s2nb',
     *     2 => null,
     *   ],
     *   'error' => [
     *     0 => 0,
     *     1 => 0,
     *     2 => 4,
     *   ],
     *   'size' => [
     *     0 => 25935,
     *     1 => 890,
     *     2 => 0,
     *   ],
     * ],
     *
     * @param array $files Array from $_FILES
     * @return int[] return id-s of attachment files inserted to database
     */
    public static function addFiles($files)
    {
        if (!is_array($files['name'])) {
            throw new RuntimeException('Wrong structure, did you forgot dropfile[]?');
        }

        $iaf_ids = [];
        $nfiles = count($files['name']);
        for ($i = 0; $i < $nfiles; $i++) {
            $filename = $files['name'][$i];
            if (!$filename) {
                continue;
            }
            $blob = file_get_contents($files['tmp_name'][$i]);
            if ($blob === false) {
                throw new RuntimeException("Can't read tmp file");
            }
            try {
                $iaf_ids[] = Attachment::create($filename, $files['type'][$i], $blob)->id;
            } catch (AttachmentException $e) {
                throw new RuntimeException("Unable to create attachment: {$e->getMessage()}", $e->getCode(), $e);
            }
        }

        return $iaf_ids;
    }

    /**
     * Attach uploaded files to an issue
     * It also notifies any subscribers of this new attachment.
     *
     * @param int $issue_id The issue ID
     * @param int $usr_id The user ID
     * @param int[] $iaf_ids attachment file id-s to attach
     * @param int $minimum_role The minimum role that can see this attachment group
     * @param string $file_description File description text
     * @param array $extra An array containing one or more of the following options:
     *          'unknown_user': the email of the user who originally sent this email, who doesn't have an account
     *          'associated_note_id' The note ID that these attachments should be associated with
     */
    public static function attachFiles($issue_id, $usr_id, $iaf_ids, $minimum_role, $file_description, $extra = [])
    {
        if (!$iaf_ids) {
            throw new LogicException('No attachment ids');
        }

        $attachment_group = new AttachmentGroup($issue_id, $usr_id, $file_description, $minimum_role);
        if (isset($extra['unknown_user'])) {
            $attachment_group->unknown_user = $extra['unknown_user'];
        }
        if (isset($extra['associated_note_id'])) {
            $attachment_group->associated_note_id = $extra['associated_note_id'];
        }
        $attachment_group->save();
        $sm = StorageManager::get();
        foreach ($iaf_ids as $iaf_id) {
            $attachment = self::getAttachment($iaf_id);
            if ($attachment->group_id) {
                // stop the attachment from being re-associated with an issue if the user hits reload and delete the empty attachmentgroup
                $attachment_group->delete(false);

                return;
            }

            $attachment->setGroup($attachment_group);
            $new_path = $attachment->calculateNewPath();
            $sm->renameFile($attachment->flysystem_path, $new_path);
            $attachment->flysystem_path = $new_path;
            $attachment->save();

            History::add($issue_id, $usr_id, 'attachment_added', 'Attachment "{filename}" uploaded by {user}', [
                'user' => User::getFullName($usr_id),
                'filename' => $attachment->filename,
            ]);
        }

        Issue::markAsUpdated($issue_id, 'file uploaded');

        // if there is customer integration, mark last customer action
        $prj_id = Issue::getProjectID($issue_id);
        $has_crm = CRM::hasCustomerIntegration($prj_id);
        $is_customer = User::getRoleByUser($usr_id, $prj_id) == User::ROLE_CUSTOMER;
        if ($has_crm && $is_customer) {
            Issue::recordLastCustomerAction($issue_id);
        }

        Workflow::handleAttachment($prj_id, $issue_id, $usr_id, $attachment_group);
        $internal_only = ($minimum_role >= User::ROLE_USER); // TODO: Make Notification::notify() handle minimum roles
        Notification::notify($issue_id, 'files', $attachment_group->id, $internal_only);

        self::cleanupAbandonedFiles();
    }

    /**
     * @param int $iaf_id
     * @return \Eventum\Attachment\Attachment
     */
    public static function getAttachment($iaf_id)
    {
        $sql = 'SELECT
                    iaf_id,
                    iaf_filename,
                    iaf_filetype,
                    iaf_filesize,
                    iaf_created_date,
                    iap_flysystem_path,
                    iaf_iat_id
                 FROM
                    `issue_attachment_file`,
                    `issue_attachment_file_path`
                 WHERE
                    iap_iaf_id = iaf_id AND
                    iaf_id=?';
        $res = DB_Helper::getInstance()->getRow($sql, [$iaf_id]);
        if (empty($res)) {
            throw new AttachmentNotFoundException();
        }

        $attachment = new Attachment($res['iaf_filename'], $res['iaf_filetype']);
        $attachment->id = $iaf_id;
        $attachment->filesize = $res['iaf_filesize'];
        $attachment->flysystem_path = $res['iap_flysystem_path'];
        $attachment->group_id = $res['iaf_iat_id'];

        return $attachment;
    }

    /**
     * Removes attachments that were never associated with issues
     */
    public static function cleanupAbandonedFiles()
    {
        $sql = "SELECT
                    iaf_id,
                    iap_flysystem_path
                 FROM
                    `issue_attachment_file`,
                    `issue_attachment_file_path`
                 WHERE
                    iap_iaf_id = iaf_id AND
                    iaf_iat_id=0 AND
                    iaf_created_date > '0000-00-00 00:00:00' AND
                    iaf_created_date < ?";
        $expire_date = time() - self::ATTACHMENT_EXPIRE_TIME;
        $params = [Date_Helper::convertDateGMT($expire_date)];
        $res = DB_Helper::getInstance()->getAll($sql, $params);
        $iaf_ids = [];
        $sm = StorageManager::get();
        foreach ($res as $row) {
            $iaf_ids[] = $row['iaf_id'];
            if (!empty($row['iap_flysystem_path'])) {
                try {
                    $sm->deleteFile($row['iap_flysystem_path']);
                } catch (FileNotFoundException $e) {
                    // TODO: Should we log this?
                }
            }
        }

        if (count($iaf_ids)) {
            $sql = 'DELETE FROM `issue_attachment_file` WHERE iaf_id IN(?)';
            DB_Helper::getInstance()->query($sql, [DB_Helper::buildList($iaf_ids)]);
        }
    }

    /**
     * Method used to return the full list of attachments related to a specific
     * issue in the database.
     *
     * @param   int $issue_id The issue ID
     * @param   int|null $max_role Don't return attachments with a role greater then this
     * @param   int|null $not_id The ID of the related note
     * @return array The full list of attachments
     */
    public static function getList($issue_id, $max_role = null, $not_id = null)
    {
        $usr_id = Auth::getUserID();
        $prj_id = Issue::getProjectID($issue_id);

        if (!$max_role) {
            $max_role = User::getRoleByUser($usr_id, $prj_id);
        }
        $params = [$issue_id, $max_role];
        $stmt = 'SELECT
                    iat_id,
                    iat_usr_id,
                    usr_full_name,
                    iat_created_date,
                    iat_description,
                    iat_unknown_user,
                    iat_min_role
                 FROM
                    `issue_attachment`,
                    `user`
                 WHERE
                    iat_iss_id=? AND
                    iat_usr_id=usr_id AND
                    iat_min_role <= ?';
        if ($not_id) {
            $stmt .= ' AND
                    iat_not_id = ?';
            $params[] = $not_id;
        }
        $stmt .= '
                 ORDER BY
                    iat_created_date ASC';
        $res = DB_Helper::getInstance()->getAll($stmt, $params);

        foreach ($res as &$row) {
            $row['iat_description'] = Link_Filter::processText($prj_id, nl2br(htmlspecialchars($row['iat_description'])));
            $row['files'] = self::getAttachmentList($row['iat_id']);
            $row['min_role_name'] = User::getRole($row['iat_min_role']);

            // if there is an unknown user, user that instead of the user_full_name
            if (!empty($row['iat_unknown_user'])) {
                $row['usr_full_name'] = $row['iat_unknown_user'];
            }
        }

        return $res;
    }

    /**
     * Method used to get the full listing of files for a specific attachment.
     *
     * @param   int $group_id The attachment ID
     * @return  array The full list of files
     */
    public static function getAttachmentList($group_id)
    {
        $stmt = 'SELECT
                    iaf_id,
                    iaf_filename,
                    iaf_filetype,
                    iaf_filesize,
                    iaf_created_date,
                    iap_flysystem_path,
                    iaf_iat_id
                 FROM
                    `issue_attachment_file`,
                    `issue_attachment_file_path`
                 WHERE
                    iap_iaf_id = iaf_id AND
                    iaf_iat_id=?';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, [$group_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        foreach ($res as &$row) {
            $row['iaf_filesize'] = Misc::formatFileSize($row['iaf_filesize']);
        }

        return $res;
    }

    /**
     * @param int $iaf_id
     * @param string $filename
     * @param int $issue_id
     * @return string
     */
    public static function generatePath($iaf_id, $filename, $issue_id = null)
    {
        $sm = StorageManager::get();
        $prefix = "{$sm->getDefaultAdapter()}://";
        $prefix .= $issue_id ?: 'unassociated';
        $prefix .= "/{$iaf_id}-";

        // make whole path not exceed 255 bytes
        // https://github.com/eventum/eventum/pull/355#issuecomment-372784072
        $fi = new SplFileInfo($filename);
        $extension = $fi->getExtension() ? '.' . $fi->getExtension() : '';
        $maxLength = 255 - strlen($prefix) - strlen($extension);
        // cut with unicode, but test with byte length
        // this ensures we don't break unicode and not exceed given length
        do {
            $filename = $prefix . mb_strimwidth($fi->getBasename($extension), 0, $maxLength, '', 'UTF-8') . $extension;
            $maxLength--;
        } while (strlen($filename) > 255);

        return $filename;
    }

    /**
     * Removes an individual file from an issue.
     *
     * @param int $iaf_id
     * @return bool
     */
    public static function removeAttachment($iaf_id)
    {
        try {
            $usr_id = Auth::getUserID();
            $attachment = self::getAttachment($iaf_id);
            $group = $attachment->getGroup();
            if (!$attachment->canAccess($usr_id) ||
                ($usr_id != $group->user_id && User::getRoleByUser($usr_id, Issue::getProjectID($group->issue_id) < User::ROLE_MANAGER))
            ) {
                return -2;
            }

            // check if this is the only file in the group
            if (count(self::getAttachmentList($group->id)) == 1) {
                return self::removeAttachmentGroup($group->id);
            }

            return $attachment->delete();
        } catch (AttachmentNotFoundException $e) {
            return -1;
        }
    }

    /**
     * Removes an AttachmentGroup and all related files from an issue.
     *
     * @param int $iat_id
     * @return bool
     */
    public static function removeAttachmentGroup($iat_id)
    {
        $usr_id = Auth::getUserID();
        $group = self::getGroup($iat_id);
        if (!$group->canAccess($usr_id) ||
            ($usr_id != $group->user_id &&
                User::getRoleByUser($usr_id, Issue::getProjectID($group->issue_id)) < User::ROLE_MANAGER)
        ) {
            return -2;
        }

        $attachments = $group->getAttachments();
        foreach ($attachments as $attachment) {
            $attachment->delete();
        }

        // only save history if there were no attachments, otherwise we can
        // use the individual attachment history entries
        return $group->delete(count($attachments) == 0);
    }

    /**
     * Returns the AttachmentGroup for the given id
     *
     * @param int $iat_id
     * @return AttachmentGroup
     */
    public static function getGroup($iat_id)
    {
        $sql = 'SELECT
                    iat_iss_id,
                    iat_usr_id,
                    iat_min_role,
                    iat_description,
                    iat_unknown_user,
                    iat_not_id,
                    iat_created_date
                FROM
                    `issue_attachment`
                WHERE
                    iat_id=?';
        $res = DB_Helper::getInstance()->getRow($sql, [$iat_id]);
        if (empty($res)) {
            throw new AttachmentGroupNotFoundException();
        }

        $group = new AttachmentGroup(
            $res['iat_iss_id'], $res['iat_usr_id'],
            $res['iat_description'], $res['iat_min_role']
        );

        $group->id = $iat_id;
        $group->unknown_user = $res['iat_unknown_user'];
        $group->associated_note_id = $res['iat_not_id'];
        $group->created_date = $res['iat_created_date'];

        return $group;
    }

    /**
     * Returns the current maximum file upload size.
     *
     * @param bool $raw whether to return computer or human readable value
     * @return string|int A string containing the formatted max file size
     */
    public static function getMaxAttachmentSize($raw = false)
    {
        $size = Misc::return_bytes(ini_get('upload_max_filesize'));

        return $raw ? $size : Misc::formatFileSize($size);
    }
}
