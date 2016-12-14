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
 * Example class for partner business logic.
 */
class Example_Partner_Backend extends Abstract_Partner_Backend
{
    public function __construct()
    {
        // setup the backend
    }

    public function getName()
    {
        return 'Example';
    }

    public function issueAdded($iss_id)
    {
        echo "partner: issue $iss_id added for " . $this->getName();
    }

    public function issueRemoved($iss_id)
    {
        echo "partner: issue $iss_id removed for " . $this->getName();
    }

    public function handleNewEmail($iss_id, $sup_id)
    {
        echo "partner: new email $sup_id on issue $iss_id";
    }

    public function handleNewNote($iss_id, $not_id)
    {
        echo "partner: new note $not_id on $iss_id";
    }

    public function handleIssueChange($iss_id, $usr_id, $old_details, $changes)
    {
        echo "partner: issue $iss_id changed";
    }

    public function getIssueMessage($iss_id)
    {
        return 'foo blah blah';
    }

    public static function canUserAccessFeature($usr_id, $feature)
    {
        switch ($feature) {
            case 'create_issue':
                return false;
            case 'associate_emails':
                return false;
            case 'reports':
                return false;
        }
    }

    public static function canUserAccessIssueSection($usr_id, $section)
    {
        switch ($section) {
            case 'partners':
                return false;
            case 'drafts':
                return false;
            case 'time':
                return false;
            case 'notes':
                return false;
            case 'phone':
                return false;
            case 'files':
                return false;
            case 'history':
                return false;
            case 'notification_list':
                return false;
            case 'authorized_repliers':
                return false;
            case 'change_reporter':
                return false;
            case 'change_status':
                return false;
            case 'convert_note':
                return false;
        }
    }

    public static function canUpdateIssue($issue_id, $usr_id)
    {
        return false;
    }
}
