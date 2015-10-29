<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2011-2014 Eventum Team              .                  |
// |                                                                      |
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License as published by |
// | the Free Software Foundation; either version 2 of the License, or    |
// | (at your option) any later version.                                  |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to:                           |
// |                                                                      |
// | Free Software Foundation, Inc.                                       |
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+

/**
 * Abstract parent class for partner business logic.
 */
abstract class Abstract_Partner_Backend
{
    /**
     * method to set up the backend
     */
    public function __construct()
    {
    }

    /**
     * return the code grabbed from this class name
     * @return int
     */
    public function getCode()
    {
    }

    /**
     * @return string
     */
    abstract public function getName();

    /**
     * @param int $iss_id
     */
    public function issueAdded($iss_id)
    {
    }

    /**
     * @param int $iss_id
     */
    public function issueRemoved($iss_id)
    {
    }

    /**
     * @param int $iss_id
     * @param int $sup_id
     */
    public function handleNewEmail($iss_id, $sup_id)
    {
    }

    /**
     * @param int $iss_id
     * @param int $not_id
     */
    public function handleNewNote($iss_id, $not_id)
    {
    }

    /**
     * @param int $iss_id
     * @param int $usr_id
     * @param array $old_details
     * @param array $changes
     */
    public function handleIssueChange($iss_id, $usr_id, $old_details, $changes)
    {
    }

    /**
     * @param int $iss_id
     * @return string
     */
    public function getIssueMessage($iss_id)
    {
    }

    /**
     * @param int $usr_id
     * @param string $feature
     * @return bool
     */
    public static function canUserAccessFeature($usr_id, $feature)
    {
    }

    /**
     * @param int $issue_id
     * @param int $usr_id
     * @return bool
     */
    public static function canEditIssue($issue_id, $usr_id)
    {
    }

    /**
     * @param int $usr_id
     * @param string $section partners, drafts, files, time, notes, phone, history, notification_list, authorized_repliers
     * @return bool
     */
    public static function canUserAccessIssueSection($usr_id, $section)
    {
    }

    /**
     * If the partner can edit the issue.
     *
     * @param integer   $issue_id
     * @param integer   $usr_id
     * @return bool
     */
    public static function canUpdateIssue($issue_id, $usr_id)
    {
    }
}
