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
use Eventum\Db\DatabaseException;
use History;
use Issue;
use User;

/**
 * Represents a group of attachments. This group is then associated with an issue. Groups should not exist without
 * files. Group data is stored in the `issue_attachment` table.
 */
class AttachmentGroup
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $issue_id;

    /**
     * The user who uploaded these files
     *
     * @var int
     */
    public $user_id;

    /**
     * @var string
     */
    public $description;

    /**
     * The minimum role that can access this attachment group.
     *
     * @see \User
     * @var int
     */
    public $minimum_role;

    /**
     * @var string
     */
    public $created_date;

    /**
     * The email of the user who added this attachment if they do not have an account
     *
     * @var string
     */
    public $unknown_user;

    /**
     * The note ID that these attachments should be associated with
     *
     * @var int
     */
    public $associated_note_id;

    /**
     * AttachmentGroup constructor.
     *
     * @param int $issue_id
     * @param int $user_id
     * @param string $description
     * @param int $minimum_role
     */
    public function __construct($issue_id, $user_id, $description, $minimum_role)
    {
        $this->issue_id = $issue_id;
        $this->user_id = $user_id;
        $this->description = $description;
        $this->minimum_role = $minimum_role;
    }

    /**
     * Saves a new AttachmentGroup to the database. Does not handle updates.
     */
    public function save()
    {
        $params = [
            'iat_iss_id' => $this->issue_id,
            'iat_usr_id' => $this->user_id,
            'iat_created_date' => Date_Helper::getCurrentDateGMT(),
            'iat_description' => $this->description,
            'iat_min_role' => $this->minimum_role,
        ];

        if ($this->unknown_user) {
            $params['iat_unknown_user'] = $this->unknown_user;
        }

        if ($this->associated_note_id) {
            $params['iat_not_id'] = $this->associated_note_id;
        }

        $sql = 'INSERT INTO `issue_attachment` SET ' . DB_Helper::buildSet($params);

        DB_Helper::getInstance()->query($sql, $params);

        $this->id = DB_Helper::get_last_insert_id();
    }

    /**
     * Verifies that the user can access the related issue and has a role greater or equal to the minimum role of this
     * group.
     *
     * @param int $usr_id
     * @return bool
     */
    public function canAccess($usr_id)
    {
        if (Issue::canAccess($this->issue_id, $usr_id)
            && User::getRoleByUser($usr_id, Issue::getProjectID($this->issue_id) >= $this->minimum_role)
        ) {
            return true;
        }

        return false;
    }

    /**
     * @return Attachment[]
     */
    public function getAttachments()
    {
        $files = AttachmentManager::getAttachmentList($this->id);
        $attachments = [];

        foreach ($files as $file) {
            $attachment = new Attachment($file['iaf_filename'], $file['iaf_filetype']);
            $attachment->id = $file['iaf_id'];
            $attachment->filesize = $file['iaf_filesize'];
            $attachment->flysystem_path = $file['iap_flysystem_path'];
            $attachment->group_id = $file['iaf_iat_id'];
            $attachments[] = $attachment;
        }

        return $attachments;
    }

    /**
     * Deletes this group and all related attachments.
     *
     * @see Attachment::delete()
     * @param bool $add_history If a history entry should be saved
     * @return int
     */
    public function delete($add_history = false)
    {
        $usr_id = Auth::getUserID();
        try {
            $sql = 'DELETE FROM
                        `issue_attachment`
                    WHERE
                        iat_id=?';
            DB_Helper::getInstance()->query($sql, [$this->id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        if ($add_history) {
            Issue::markAsUpdated($usr_id);
            History::add($this->issue_id, $usr_id, 'attachment_removed', 'Attachment Group removed by {user}', [
                'user' => User::getFullName($usr_id),
            ]);
        }

        return 1;
    }
}
