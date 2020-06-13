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
    public function canUserAccessFeature($usr_id, $feature)
    {
    }

    /**
     * @param int $issue_id
     * @param int $usr_id
     * @return bool
     */
    public function canEditIssue($issue_id, $usr_id)
    {
    }

    /**
     * @param int $usr_id
     * @param string $section partners, drafts, files, time, notes, phone, history, notification_list, authorized_repliers
     * @return bool
     */
    public function canUserAccessIssueSection($usr_id, $section)
    {
    }

    /**
     * If the partner can edit the issue.
     *
     * @param int   $issue_id
     * @param int   $usr_id
     * @return bool
     */
    public function canUpdateIssue($issue_id, $usr_id)
    {
    }
}
