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
use Date_Helper;
use DB_Helper;
use Eventum\Attachment\Exceptions\AttachmentException;
use Eventum\Db\DatabaseException;
use Exception;
use glen\FilenameNormalizer\Normalizer;
use History;
use Issue;
use Misc;
use User;

/**
 * Represents an individual attachment to an issue. Attachments should be associated with an AttachmentGroup but are
 * not when they are first uploaded. Attachment meta data is stored in the `issue_attachment_file` table.
 */
class Attachment
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $filename;

    /**
     * @var string
     */
    public $filetype;

    /**
     * @var int
     */
    public $filesize;

    /**
     * The path where the attachment is stored.
     *
     * @var string
     */
    public $flysystem_path;

    /**
     * The ID of the AttachmentGroup
     *
     * @var int|null
     */
    public $group_id;

    /**
     * The actual content of the file. This is only set when an attachment is created
     * or if someone requests the contents be read.
     *
     * @var string|null
     */
    private $blob;

    /**
     * The attachment group this Attachment belongs too. Loaded on demand.
     *
     * @var AttachmentGroup
     */
    private $group;

    /**
     * Attachment constructor.
     *
     * @param string $filename
     * @param string $filetype MIME Type
     */
    public function __construct($filename, $filetype)
    {
        $this->filename = $filename;
        $this->filetype = $filetype;
    }

    /**
     * Creates an attachment object and saves it to the issue_attachment_file table.
     *
     * @param string $filename
     * @param string $filetype MIME Type
     * @param string $blob
     * @throws AttachmentException
     * @return \Eventum\Attachment\Attachment
     */
    public static function create($filename, $filetype, $blob)
    {
        try {
            $filename = Normalizer::normalize($filename);
            $attachment = new self($filename, $filetype);
            $attachment->blob = $blob;
            $attachment->save();

            return $attachment;
        } catch (DatabaseException $e) {
            throw new AttachmentException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Saves new attachment info in database.
     *
     * @throws AttachmentException
     * @throws DatabaseException
     */
    public function save()
    {
        if ($this->id) {
            if ($this->group) {
                $sql = 'UPDATE
                            `issue_attachment_file`
                        SET
                            iaf_iat_id = ?
                        WHERE
                            iaf_id = ?';
                DB_Helper::getInstance()->query($sql, [
                    $this->group->id,
                    $this->id,
                ]);
            }
            $this->savePath();
        } else {
            $sql = 'INSERT INTO
                        `issue_attachment_file`
                    (
                        iaf_filename,
                        iaf_filesize,
                        iaf_filetype,
                        iaf_created_date
                    ) VALUES (
                        ?, ?, ?, ?
                    )';
            DB_Helper::getInstance()->query($sql, [
                $this->filename,
                Misc::countBytes($this->blob),
                $this->filetype,
                Date_Helper::getCurrentDateGMT(),
            ]);
            $this->id = DB_Helper::get_last_insert_id();

            $this->flysystem_path = $this->calculateNewPath();

            $storage = StorageManager::get();
            $storage->addFile($this->flysystem_path, $this->blob);

            $this->savePath(true);
        }
    }

    /**
     * Saves the path of a file
     * @param bool $insert
     */
    private function savePath($insert = false)
    {
        if ($insert) {
            $sql = 'INSERT INTO
                        `issue_attachment_file_path`
                    SET
                        iap_iaf_id = ?,
                        iap_flysystem_path = ?';
            DB_Helper::getInstance()->query($sql, [
                $this->id,
                $this->flysystem_path,
            ]);
        } else {
            $sql = 'UPDATE
                        `issue_attachment_file_path`
                    SET
                        iap_flysystem_path = ?
                    WHERE
                        iap_iaf_id = ?';
            DB_Helper::getInstance()->query($sql, [
                $this->flysystem_path,
                $this->id,
            ]);
        }
    }

    /**
     * Calculates the new path for this attachment based on all information
     * associated with this object. This is used after the attachment has been
     * associated with a group so the issue ID can be included in the path.
     *
     * @return string
     */
    public function calculateNewPath()
    {
        $params = [$this->id, $this->filename];
        if ($this->getGroup()) {
            $params[] = $this->group->issue_id;
        }

        return AttachmentManager::generatePath(...$params);
    }

    /**
     * Returns the AttachmentGroup for this Attachment or null if there is no group. If the group has not been loaded
     * it will be loaded and cached.
     *
     * @return AttachmentGroup
     */
    public function getGroup()
    {
        if (!$this->group && $this->group_id) {
            // try to load group based on iat_id
            $this->group = AttachmentManager::getGroup($this->group_id);
        }

        return $this->group;
    }

    /**
     * @param \Eventum\Attachment\AttachmentGroup $group
     */
    public function setGroup(AttachmentGroup $group)
    {
        $this->group = $group;
    }

    /**
     * @see AttachmentGroup::canAccess()
     * @param int $usr_id
     * @return bool
     */
    public function canAccess($usr_id)
    {
        return $this->getGroup()->canAccess($usr_id);
    }

    /**
     * Method used to output the headers and the binary data for
     * an attachment file.
     *
     * This method never returns to caller.
     *
     * @param   bool $force_inline If the file should be forced to render in the browser
     */
    public function outputDownload($force_inline = false)
    {
        try {
            Misc::outputDownload($this->getFile()->read(), $this->filename, $this->filesize, $this->filetype, $force_inline);
        } catch (Exception $e) {
            // this is very broad, but it is better to display something besides a blank screen
            header('HTTP/1.1 500 Internal Server Error');
            echo 'Error serving download, please contact your system administrator.';
            exit;
        }
    }

    /**
     * Returns the Flysystem file object
     *
     * @return \League\Flysystem\File
     */
    public function getFile()
    {
        return StorageManager::get()->getFile($this->flysystem_path);
    }

    /**
     * Deletes the attachment from the database and storage backend. Also marks the issue as updated and saves a
     * history entry.
     *
     * @return int 1 for success, -1 otherwise
     */
    public function delete()
    {
        $usr_id = Auth::getUserID();
        $group = $this->getGroup();
        try {
            $sm = StorageManager::get();
            $sm->deleteFile($this->flysystem_path);

            $sql = 'DELETE FROM
                        `issue_attachment_file`
                    WHERE
                        iaf_id=?';
            DB_Helper::getInstance()->query($sql, [$this->id]);

            $sql = 'DELETE FROM
                        `issue_attachment_file_path`
                    WHERE
                        iap_iaf_id=?';
            DB_Helper::getInstance()->query($sql, [$this->id]);
        } catch (DatabaseException $e) {
            return -1;
        }
        Issue::markAsUpdated($usr_id);
        History::add($group->issue_id, $usr_id, 'attachment_removed', 'Attachment "{filename}" removed by {user}', [
            'user' => User::getFullName($usr_id),
            'filename' => $this->filename,
        ]);

        return 1;
    }

    /**
     * @return array Returns an array of this attachments details
     */
    public function getDetails()
    {
        return [
            'name' => $this->filename,
            'type' => $this->filetype,
            'size' => $this->filesize,
            'contents' => $this->getFileContents(),
        ];
    }

    /**
     * Returns the actual file contents
     *
     * @return string
     */
    public function getFileContents()
    {
        if ($this->blob === null) {
            $this->blob = StorageManager::get()->getFile($this->flysystem_path)->read();
        }

        return $this->blob;
    }
}
