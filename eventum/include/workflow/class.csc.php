<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004 MySQL AB                                    |
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
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: Bryan Alsdorf <bryan@mysql.com>                             |
// +----------------------------------------------------------------------+
//

class CSC_Workflow_Backend
{
    /**
     * When an issue is updated, set the status to "Waiting on Developer" if the following
     * conditions are true:
     *  - New status is not pending.
     *  - New Status is not a closed status.
     * 
     * @param integer $prj_id The project ID.
     * @param integer $issue_id The ID of the issue.
     * @param integer $usr_id The ID of the user.
     * @param array $old_details The old details of the issues.
     * @param array $changes The changes that were applied to this issue (the $HTTP_POST_VARS)
     */
    function handleIssueUpdated($prj_id, $issue_id, $usr_id, $old_details, $changes)
    {
        $status_id = Status::getStatusID('Waiting on Developer');
        if ((!empty($status_id)) &&
                ($changes["status"] != Status::getStatusID('Pending')) &&
                (!Status::hasClosedContext($changes["status"])) &&
                (Customer::hasCustomerIntegration($prj_id)) &&
                (User::getRoleByUser($usr_id) == User::getRoleID('Customer'))
                ) {
            $this->markAsWaitingOnDeveloper($issue_id, $status_id, 'update');
        }
    }


    /**
     * Called when an issue is locked. If the current status is pending, change the
     * status to 'Assigned'.
     *
     * @param   integer $prj_id The projectID
     * @param   integer $issue_id The ID of the issue.
     * @param   integer $usr_id The id of the user who locked the issue.
     */
    function handleLock($prj_id, $issue_id, $usr_id)
    {
        $current_status_id = Issue::getStatusID($issue_id);
        if ($current_status_id == Status::getStatusID('Pending')) {
            Issue::setStatus($issue_id, Status::getStatusID('Assigned'));
            History::add($issue_id, $usr_id, History::getTypeID('status_changed'), "Status changed to 'Assigned' because " . User::getFullName($usr_id) . " locked the issue.");
        }
    }


    /**
     * Called when a file is attached to an issue. Updates The status to 'Waiting on Developer' if the
     * current status is not 'Pending'.
     *
     * @param   integer $prj_id The projectID
     * @param   integer $issue_id The ID of the issue.
     * @param   integer $usr_id The id of the user who locked the issue.
     */
    function handleAttachment($prj_id, $issue_id, $usr_id)
    {
        if ((Customer::hasCustomerIntegration($prj_id)) && (User::getRoleByUser($usr_id) == User::getRoleID('Customer'))) {
            $status_id = Status::getStatusID('Waiting on Developer');
            if ((!empty($status_id)) && (Issue::getStatusID($issue_id) != Status::getStatusID('Pending'))) {
                $this->markAsWaitingOnDeveloper($issue_id, $status_id, 'file');
            }
        }
    }


    /**
     * Called when the priority of an issue changes.
     *
     * @param   integer $prj_id The projectID
     * @param   integer $issue_id The ID of the issue.
     * @param   integer $usr_id The id of the user who locked the issue.
     * @param   array $old_details The old details of the issue.
     * @param   array $changes The changes that were applied to this issue (the $HTTP_POST_VARS)
     */
    function handlePriorityChange($prj_id, $issue_id, $usr_id, $old_details, $changes)
    {
        // only send an irc notice if this was done by a customer user
        if ((Customer::hasCustomerIntegration($prj_id)) && (User::getRoleByUser($usr_id) == User::getRoleID('Customer'))) {
            $irc_notice = "Issue #$issue_id updated (Old Priority: " . Misc::getPriorityTitle($old_details['iss_pri_id']) . "; New Priority: " . Misc::getPriorityTitle($changes["priority"]) . "), " . $old_details['customer_info']['customer_name'] . ", " . $changes["summary"];
            Notification::notifyIRC($issue_id, $irc_notice);
        }
    }


    /**
     * Called when an email is blocked. The status will be changed to "Waiting on Developer" if the current
     * status is not "Pending".
     *
     * @param   integer $prj_id The projectID
     * @param   integer $issue_id The ID of the issue.
     * @param   array $email_details Details of the issue
     * @param   string $type What type of blocked email this is.
     */
    function handleBlockedEmail($prj_id, $issue_id, $email_details, $type)
    {
        // notify the email being blocked to IRC
        Notification::notifyIRCBlockedMessage($issue_id, $email_details['from']);
        
        if ($type != 'new') {
            $status_id = Status::getStatusID('Waiting on Developer');
            if ((!empty($status_id)) && (Issue::getStatusID($issue_id) != Status::getStatusID('Pending'))) {
                $this->markAsWaitingOnDeveloper($issue_id, $status_id, 'blocked_email');
            }
        }
    }


    /**
     * Called when the assignment on an issue changes. Notifies IRC of the assignment change if the new assignment
     * is to a user other then the user making the change.
     *
     * @param   integer $prj_id The projectID
     * @param   integer $issue_id The ID of the issue.
     * @param   integer $usr_id The id of the user who locked the issue.
     * @param   array $issue_details The old details of the issue.
     * @param   array $new_assignees The new assignees of this issue.
     * @param   boolean $remote_assignment If this issue was remotely assigned.
     */
    function handleAssignmentChange($prj_id, $issue_id, $usr_id, $issue_details, $new_assignees, $remote_assignment)
    {
        if (!in_array($usr_id, $new_assignees)) {
            Notification::notifyIRCAssignmentChange($issue_id, $usr_id, $issue_details['assigned_users'], $new_assignees, $remote_assignment);
        }
    }


    /**
     * Called when a new issue is created.
     *
     * @param   integer $prj_id The projectID
     * @param   integer $issue_id The ID of the issue.
     * @param   boolean $has_TAM If this issue has a technical account manager.
     * @param   boolean $has_RR If Round Robin was used to assign this issue.
     */
    function handleNewIssue($prj_id, $issue_id, $has_TAM, $has_RR)
    {
        if ($has_TAM || $has_RR) {
            Issue::setStatus($issue_id, Status::getStatusID('Assigned'));
        }
    }


    /**
     * Called when a new message is recieved. 
     *
     * @param   integer $prj_id The projectID
     * @param   integer $issue_id The ID of the issue.
     * @param   array $message An array containing the new email
     */
    function handleNewEmail($prj_id, $issue_id, $message)
    {
        $sender_email = strtolower(Mail_API::getEmailAddress($message->headers['from']));
        $current_status_id = Issue::getStatusID($issue_id);
        if (Notification::isBounceMessage($sender_email)) {
            // only change the status of the associated issue if the current status is not
            // currently marked to a status with a closed context
            $status_id = Status::getStatusID('Waiting on Developer');
            if ((!empty($status_id)) && (!Status::hasClosedContext($current_status_id)) &&
                    ($current_status_id != Status::getStatusID('Pending'))) {
                $this->markAsWaitingOnDeveloper($issue_id, $status_id, 'email');
                Issue::recordLastCustomerAction($issue_id);
            }
        } else {
            $staff_emails = Project::getUserEmailAssocList($prj_id, 'active', User::getRoleID('Customer'));
            $staff_emails = array_map('strtolower', $staff_emails);
            // handle the first_response_date / last_response_date fields
            if (in_array($sender_email, array_values($staff_emails))) {
                $stmt = "UPDATE
                            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                         SET
                            iss_last_response_date='" . Date_API::getCurrentDateGMT() . "'
                         WHERE
                            iss_id=$issue_id";
                $GLOBALS["db_api"]->dbh->query($stmt);
        
                $stmt = "UPDATE
                            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                         SET
                            iss_first_response_date='" . Date_API::getCurrentDateGMT() . "'
                         WHERE
                            iss_first_response_date IS NULL AND
                            iss_id=$issue_id";
                $GLOBALS["db_api"]->dbh->query($stmt);
            }
        
            // change the status of the issue automatically to 'Waiting on Developer' if a non-staff person is sending this email
            $status_id = Status::getStatusID('Waiting on Developer');
            if ((!empty($status_id)) && ($current_status_id != Status::getStatusID('Pending'))) {
                if (!in_array($sender_email, $staff_emails)) {
                    $this->markAsWaitingOnDeveloper($issue_id, $status_id, 'email');
                    Issue::recordLastCustomerAction($issue_id);
                }
            }
        }
    }


    /**
     * Updates the status of a given issue ID to 'Waiting on Developer' and 
     * saves a history entry about it.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   integer $status_id The status ID
     * @param   string $type The reason for changing the status (because of an email, file, etc)
     */
    function markAsWaitingOnDeveloper($issue_id, $status_id, $type)
    {
        Issue::setStatus($issue_id, $status_id);
        // save a history entry about this...
        if ($type == 'email') {
            $desc = "Issue automatically set to status '" . Status::getStatusTitle($status_id) . "' because of a non-staff incoming email.";
        } elseif ($type == 'file') {
            $desc = "Issue automatically set to status '" . Status::getStatusTitle($status_id) . "' because a new file was uploaded by a customer.";
        } elseif ($type == 'update') {
            $desc = "Issue automatically set to status '" . Status::getStatusTitle($status_id) . "' because the issue was updated by a customer contact.";
        } elseif ($type == 'blocked_email') {
            $desc = "Issue automatically set to status '" . Status::getStatusTitle($status_id) . "' because of a blocked incoming email.";
        } elseif ($type == 'note') {
            $desc = "Issue automatically set to status '" . Status::getStatusTitle($status_id) . "' because of a new internal note was posted.";
        }
        History::add($issue_id, APP_SYSTEM_USER_ID, History::getTypeID('status_changed'), $desc);
    }
}


?>