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

use Eventum\Db\DatabaseException;

/**
 * Class designed to handle all business logic related to attachments being
 * uploaded to issues in the application.
 */
class Attachment
{
    /**
     * files uploaded, but not linked to attachment are expired after this time passes
     * use 24h, this is very safe value
     * @see associateFiles
     */
    const ATTACHMENT_EXPIRE_TIME = 86400;

    /**
     * Returns true if specified mime type should be displayed
     * directly in the browser window.
     */
    private static function displayInline($mimetype)
    {
        $parts = explode('/', $mimetype, 2);
        if (count($parts) < 2) {
            return false;
        }

        list($type) = $parts;

        // display inline images and text documents
        return in_array($type, array('image', 'text'));
    }

    /**
     * Method used to output the headers and the binary data for
     * an attachment file.
     *
     * This method never returns to caller.
     *
     * @param   string $data The binary data of this file download
     * @param   string $filename The filename
     * @param   integer $filesize The size of this file
     * @param   string $mimetype The mimetype of this file
     * @param   boolean $force_inline If the file should be forced to render in the browser
     * @return  void
     */
    public static function outputDownload(&$data, $filename, $filesize, $mimetype, $force_inline = false)
    {
        if ($force_inline == true) {
            header('Content-Type: text/plain');

            if (stristr($mimetype, 'gzip')) {
                header('Content-Encoding: gzip');
            }
            header('Content-Disposition: inline; filename="' . urlencode($filename) . '"');
            header('Content-Length: ' . $filesize);
            echo $data;
            exit;
        }

        if (empty($mimetype)) {
            $mimetype = 'application/octet-stream';
        }
        if (empty($filename)) {
            $filename = ev_gettext('Untitled');
        }
        $disposition = self::displayInline($mimetype) ? 'inline' : 'attachment';
        $filename = rawurlencode($filename);
        header('Content-Type: ' . $mimetype);
        header("Content-Disposition: {$disposition}; filename=\"{$filename}\"; filename*=".APP_CHARSET."''{$filename}");
        header("Content-Length: {$filesize}");
        echo $data;
        exit;
    }

    /**
     * Method used to remove a specific file out of an existing attachment.
     *
     * @param   integer $iaf_id The attachment file ID
     * @return int -1 or -2 if the removal was not successful, 1 otherwise
     */
    public static function removeIndividualFile($iaf_id)
    {
        $usr_id = Auth::getUserID();
        $stmt = 'SELECT
                    iat_iss_id
                 FROM
                    {{%issue_attachment}},
                    {{%issue_attachment_file}}
                 WHERE
                    iaf_id=? AND
                    iat_id=iaf_iat_id';

        $params = array($iaf_id);
        if (Auth::getCurrentRole() < User::ROLE_MANAGER) {
            $stmt .= ' AND
                    iat_usr_id=?';
            $params[] = $usr_id;
        }
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, $params);
        } catch (DatabaseException $e) {
            return -1;
        }

        if (empty($res)) {
            return -2;
        }

        // check if the file is the only one in the attachment
        $stmt = 'SELECT
                    iat_id
                 FROM
                    {{%issue_attachment}},
                    {{%issue_attachment_file}}
                 WHERE
                    iaf_id=? AND
                    iaf_iat_id=iat_id';
        $attachment_id = DB_Helper::getInstance()->getOne($stmt, array($iaf_id));

        $res = self::getFileList($attachment_id);
        if (count($res) > 1) {
            self::removeFile($iaf_id);
        } else {
            self::remove($attachment_id);
        }

        return 1;
    }

    /**
     * Method used to return the details for a given attachment.
     *
     * @param   integer $file_id The attachment ID
     * @return  array The details of the attachment
     */
    public static function getDetails($file_id)
    {
        $stmt = 'SELECT
                    *
                 FROM
                    {{%issue_attachment}},
                    {{%issue_attachment_file}}
                 WHERE
                    iat_id=iaf_iat_id AND
                    iaf_id=?';
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, array($file_id));
        } catch (DatabaseException $e) {
            return '';
        }

        // don't allow customers to reach internal only files
        $user_role_id = User::getRoleByUser(Auth::getUserID(), Issue::getProjectID($res['iat_iss_id']));
        if (($res['iat_status'] == 'internal') && $user_role_id <= User::ROLE_CUSTOMER) {
            return '';
        } else {
            return $res;
        }
    }

    /**
     * Removes all attachments (and associated files) related to a set
     * of specific issues.
     *
     * @param   array $ids The issue IDs that need to be removed
     * @return  boolean Whether the removal worked or not
     */
    public static function removeByIssues($ids)
    {
        $stmt = 'SELECT
                    iat_id
                 FROM
                    {{%issue_attachment}}
                 WHERE
                    iat_iss_id IN (' . DB_Helper::buildList($ids) . ')';

        try {
            $res = DB_Helper::getInstance()->getColumn($stmt, $ids);
        } catch (DatabaseException $e) {
            return false;
        }

        foreach ($res as $id) {
            self::remove($id);
        }

        return true;
    }

    /**
     * Method used to remove attachments from the database.
     *
     * @param   integer $iat_id attachment_id.
     * @param   boolean $add_history whether to add history entry.
     * @return  integer Numeric code used to check for any errors
     */
    public static function remove($iat_id, $add_history = true)
    {
        $usr_id = Auth::getUserID();
        $stmt = 'SELECT
                    iat_iss_id
                 FROM
                    {{%issue_attachment}}
                 WHERE
                    iat_id=?';
        $params = array($iat_id);
        if (Auth::getCurrentRole() < User::ROLE_MANAGER) {
            $stmt .= ' AND
                    iat_usr_id=?';
            $params[] = $usr_id;
        }

        try {
            $res = DB_Helper::getInstance()->getOne($stmt, $params);
        } catch (DatabaseException $e) {
            return -1;
        }

        if (empty($res)) {
            return -2;
        }

        $issue_id = $res;
        $files = self::getFileList($iat_id);
        $stmt = 'DELETE FROM
                    {{%issue_attachment}}
                 WHERE
                    iat_id=? AND
                    iat_iss_id=?';
        try {
            DB_Helper::getInstance()->query($stmt, array($iat_id, $issue_id));
        } catch (DatabaseException $e) {
            return -1;
        }

        foreach ($files as $file) {
            self::removeFile($file['iaf_id']);
        }

        if ($add_history) {
            Issue::markAsUpdated($usr_id);
            // need to save a history entry for this
            History::add($issue_id, $usr_id, 'attachment_removed', 'Attachment removed by {user}', array(
                'user' => User::getFullName($usr_id)
            ));
        }

        return 1;
    }

    /**
     * Method used to remove a specific file from an attachment, since every
     * attachment can have several files associated with it.
     *
     * @param   integer $iaf_id The attachment file ID
     * @return int
     */
    public function removeFile($iaf_id)
    {
        $stmt = 'DELETE FROM
                    {{%issue_attachment_file}}
                 WHERE
                    iaf_id=?';
        try {
            DB_Helper::getInstance()->query($stmt, array($iaf_id));
        } catch (DatabaseException $e) {
            return -1;
        }

        return 0;
    }

    /**
     * Method used to get the full listing of files for a specific attachment.
     *
     * @param   integer $attachment_id The attachment ID
     * @return  array The full list of files
     */
    public static function getFileList($attachment_id)
    {
        $stmt = 'SELECT
                    iaf_id,
                    iaf_filename,
                    iaf_filesize
                 FROM
                    {{%issue_attachment_file}}
                 WHERE
                    iaf_iat_id=?';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, array($attachment_id));
        } catch (DatabaseException $e) {
            return '';
        }

        foreach ($res as &$row) {
            $row['iaf_filesize'] = Misc::formatFileSize($row['iaf_filesize']);
        }

        return $res;
    }

    /**
     * Method used to return the full list of attachments related to a specific
     * issue in the database.
     *
     * @param   integer $issue_id The issue ID
     * @return  array The full list of attachments
     */
    public static function getList($issue_id)
    {
        $usr_id = Auth::getUserID();
        $prj_id = Issue::getProjectID($issue_id);

        $stmt = 'SELECT
                    iat_id,
                    iat_usr_id,
                    usr_full_name,
                    iat_created_date,
                    iat_description,
                    iat_unknown_user,
                    iat_status
                 FROM
                    {{%issue_attachment}},
                    {{%user}}
                 WHERE
                    iat_iss_id=? AND
                    iat_usr_id=usr_id';
        if (User::getRoleByUser($usr_id, $prj_id) <= User::ROLE_CUSTOMER) {
            $stmt .= " AND iat_status='public' ";
        }
        $stmt .= '
                 ORDER BY
                    iat_created_date ASC';
        $params = array($issue_id);
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, $params);
        } catch (DatabaseException $e) {
            return '';
        }

        foreach ($res as &$row) {
            $row['iat_description'] = Link_Filter::processText($prj_id, nl2br(htmlspecialchars($row['iat_description'])));
            $row['files'] = self::getFileList($row['iat_id']);

            // if there is an unknown user, user that instead of the user_full_name
            if (!empty($row['iat_unknown_user'])) {
                $row['usr_full_name'] = $row['iat_unknown_user'];
            }
        }

        return $res;
    }

    /**
     * Associate uploaded files to an "attachment".
     * Additionally cleanups stale uploads.
     *
     * @param int $attachment_id
     * @param int[] $iaf_ids
     */
    private static function associateFiles($attachment_id, $iaf_ids)
    {
        // TODO: verify that all $iaf_ids actually existed, not expired
        $list = DB_Helper::buildList($iaf_ids);
        $stmt = "UPDATE {{%issue_attachment_file}} SET iaf_iat_id=? WHERE iaf_id in ($list)";
        $params = $iaf_ids;
        array_unshift($params, $attachment_id);
        DB_Helper::getInstance()->query($stmt, $params);

        // run cleanup of stale uploads
        $stmt = "DELETE FROM {{%issue_attachment_file}} WHERE iaf_iat_id=0 AND iaf_created_date>'0000-00-00 00:00:00' AND iaf_created_date < ?";
        $expire_date = time() - self::ATTACHMENT_EXPIRE_TIME;
        $params = array(Date_Helper::convertDateGMT($expire_date));
        DB_Helper::getInstance()->query($stmt, $params);
    }

    /**
     * Attach uploaded files to an issue
     * It also notifies any subscribers of this new attachment.
     *
     * @param int $issue_id The issue ID
     * @param int $usr_id The user ID
     * @param int[] $iaf_ids attachment file id-s to attach
     * @param boolean $internal_only Whether this attachment is supposed to be internal only or not
     * @param string $file_description File description text
     * @param string $unknown_user The email of the user who originally sent this email, who doesn't have an account.
     * @param integer $associated_note_id The note ID that these attachments should be associated with
     */
    public static function attachFiles($issue_id, $usr_id, $iaf_ids, $internal_only, $file_description, $unknown_user = null, $associated_note_id = null)
    {
        if (!$iaf_ids) {
            throw new LogicException('No attachment ids');
        }
        $attachment_id = self::add($issue_id, $usr_id, $file_description, $internal_only, $unknown_user, $associated_note_id);
        self::associateFiles($attachment_id, $iaf_ids);

        Issue::markAsUpdated($issue_id, 'file uploaded');
        History::add($issue_id, $usr_id, 'attachment_added', 'Attachment uploaded by {user}', array(
            'user' => User::getFullName($usr_id),
        ));

        // if there is customer integration, mark last customer action
        $prj_id = Issue::getProjectID($issue_id);
        $has_crm = CRM::hasCustomerIntegration($prj_id);
        $is_customer = User::getRoleByUser($usr_id, $prj_id) == User::ROLE_CUSTOMER;
        if ($has_crm && $is_customer) {
            Issue::recordLastCustomerAction($issue_id);
        }

        Workflow::handleAttachment($prj_id, $issue_id, $usr_id);
        Notification::notify($issue_id, 'files', $attachment_id, $internal_only);
    }

    /**
     * Method used to associate an attachment to an issue, and all of its
     * related files. It also notifies any subscribers of this new attachment.
     *
     * Error codes:
     * -1 - An error occurred while trying to process the uploaded file.
     * -2 - The uploaded file is already attached to the current issue.
     *  1 - The uploaded file was associated with the issue.
     *
     * @param integer $usr_id The user ID
     * @param string $status The attachment status
     * @return integer Numeric code used to check for any errors
     * @deprecated this method uses super-globals, and doesn't emit exceptions
     */
    public static function attach($usr_id, $status = 'public')
    {
        try {
            $iaf_ids = self::addFiles($_FILES['attachment']);
        } catch (DatabaseException $e) {
            return -1;
        }

        if (empty($iaf_ids)) {
            return -1;
        }

        $internal_only = $status == 'internal';

        try {
            self::attachFiles($_POST['issue_id'], $usr_id, $iaf_ids, $internal_only, $_POST['file_description']);
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Method used to add files to a specific attachment in the database.
     *
     * @param   integer $attachment_id The attachment ID
     * @param   string $filename The filename to be added
     * @return  int|boolean iaf_id if insert was success
     */
    public static function addFile($attachment_id, $filename, $filetype, &$blob)
    {
        $stmt = 'INSERT INTO
                    {{%issue_attachment_file}}
                 (
                    iaf_iat_id,
                    iaf_filename,
                    iaf_filesize,
                    iaf_filetype,
                    iaf_created_date,
                    iaf_file
                 ) VALUES (
                    ?, ?, ?, ?, ?, ?
                 )';
        try {
            DB_Helper::getInstance()->query($stmt, array(
                $attachment_id,
                $filename,
                Misc::countBytes($blob),
                $filetype,
                Date_Helper::getCurrentDateGMT(),
                $blob,
            ));
        } catch (DatabaseException $e) {
            return false;
        }

        return DB_Helper::get_last_insert_id();
    }

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
            throw new RuntimeException('Wrong structure, dif you forgot dropfile[]?');
        }

        $iaf_ids = array();
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
            $iaf_id = self::addFile(0, $filename, $files['type'][$i], $blob);
            // FIXME: handle errors properly
            if ($iaf_id) {
                $iaf_ids[] = $iaf_id;
            }
        }

        return $iaf_ids;
    }

    /**
     * Method used to add an attachment to the database.
     *
     * @param   integer $issue_id The issue ID
     * @param   integer $usr_id The user ID
     * @param   string $description The description for this new attachment
     * @param   boolean $internal_only Whether this attachment is supposed to be internal only or not
     * @param   string $unknown_user The email of the user who originally sent this email, who doesn't have an account.
     * @param   integer $associated_note_id The note ID that these attachments should be associated with
     * @return  integer The new attachment ID
     */
    public static function add($issue_id, $usr_id, $description, $internal_only = false, $unknown_user = null, $associated_note_id = null)
    {
        if ($internal_only) {
            $attachment_status = 'internal';
        } else {
            $attachment_status = 'public';
        }

        $params = array(
            'iat_iss_id' => $issue_id,
            'iat_usr_id' => $usr_id,
            'iat_created_date' => Date_Helper::getCurrentDateGMT(),
            'iat_description' => $description,
            'iat_status' => $attachment_status,
        );

        if ($unknown_user) {
            $params['iat_unknown_user'] = $unknown_user;
        }

        if ($associated_note_id) {
            $params['iat_not_id'] = $associated_note_id;
        }

        $stmt = 'INSERT INTO {{%issue_attachment}} SET '. DB_Helper::buildSet($params);

        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return false;
        }

        return DB_Helper::get_last_insert_id();
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
