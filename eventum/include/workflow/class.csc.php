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

include_once(APP_INC_PATH . "class.priority.php");

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
        $issue_details = Issue::getDetails($issue_id, true);
        $this->changeGroup($issue_id, $issue_details["iss_grp_id"], array($usr_id));
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
            $irc_notice = "Issue #$issue_id updated (Old Priority: " . Priority::getTitle($old_details['iss_pri_id']) . "; New Priority: " . Priority::getTitle($changes["priority"]) . "), " . $old_details['customer_info']['customer_name'] . ", " . $changes["summary"];
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
        // only change the issue status if we are not handling a notification for a new issue, or
        // a vacation auto-responder message
        if (($type != 'new') && ($type != 'vacation-autoresponder')) {
            $status_id = Status::getStatusID('Waiting on Developer');
            if ((!empty($status_id)) && (Issue::getStatusID($issue_id) != Status::getStatusID('Pending'))) {
                $this->markAsWaitingOnDeveloper($issue_id, $status_id, 'blocked_email');
            }
        }
    }


    /**
     * Called when a note is routed.
     *
     * @param   integer $prj_id The projectID
     * @param   integer $issue_id The ID of the issue.
     * @param   boolean $closing If the issue is being closed
     */
    function handleNewNote($prj_id, $issue_id, $closing)
    {
        // if the issue is being closed, nothing should be done.
        if ($closing) {
            return;
        }
        // change the status of the issue automatically to 'Waiting on Developer'
        $current_status_id = Issue::getStatusID($issue_id);
        $status_id = Status::getStatusID('Waiting on Developer');
        if ((!empty($status_id)) && ($current_status_id != Status::getStatusID('Pending'))) {
            $this->markAsWaitingOnDeveloper($issue_id, $status_id, 'note');
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
        if (count($new_assignees) > 0 && !in_array($usr_id, $new_assignees)) {
            Notification::notifyIRCAssignmentChange($issue_id, $usr_id, $issue_details['assigned_users'], $new_assignees, $remote_assignment);
        }
        $this->changeGroup($issue_id, $issue_details["iss_grp_id"], $new_assignees);
        
    }
    
    
    /**
     * Handles the logic for changing the group if an assignment changes.
     * 
     * @access  private
     * @param   integer $issue_id ID of the issue.
     * @param   integer $current_group ID of the current group.
     * @param   array $new_assignees An array of the users who are now assigned to this issue.
     */
    function changeGroup($issue_id, $current_group, $new_assignees)
    {
        if (count($new_assignees) < 1) {
            return -1;
        }
        
        // handle changing the group if the new user is in in a different group
        $user_groups = User::getGroupID($new_assignees);
        if ((empty($current_group)) || ((!empty($current_group)) && (!in_array($current_group, $user_groups)))) {
            // if the issue already has a group, and none of the new assignees are in the current group.
            // see if all the new assignees are in the same group and if so, change the group.
            $new_grp_id = $user_groups[0];
            $change_group = true;
            foreach ($user_groups as $grp_id) {
            	if ($grp_id != $new_grp_id) {
        	       // not all new assignees arein the same group, its not ok to change
            	   $change_group = false;
            	   break;
            	}
            }
            if (($change_group == true) && (!empty($new_grp_id))) {
                Issue::setGroup($issue_id, $new_grp_id);
            }
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
        $details = Issue::getDetails($issue_id);
        // figure out which Group this issue should belong too if no group is set
        if ((empty($details["iss_grp_id"])) && (Group::getAssocList() > 0)) {
            if (count($details['assigned_users']) > 0) {
                $this->changeGroup($issue_id, 0, $details['assigned_users']);
            } else {
                $first_char = strtolower(substr($details["customer_info"]["customer_name"], 0, 1));
                if ($first_char >= "n") {
                    Issue::setGroup($issue_id, Group::getGroupByName("N-Z"));
                } else {
                    Issue::setGroup($issue_id, Group::getGroupByName("A-M"));
                }
            }
        }
        
        // XXX: also need to check if the new per-incident support contract is 30 days old or not. if it is older than 30 days, then quarantine
        $customer_id = Issue::getCustomerID($issue_id);
        if (!empty($customer_id)) {
            // check if we need to send a notification about per-incident support being over
            if ((Customer::hasPerIncidentContract($prj_id, $customer_id)) && 
                    (!Customer::hasIncidentsLeft($prj_id, $customer_id))) {
                Customer::sendIncidentLimitNotice($prj_id, Issue::getContactID($issue_id), $customer_id, true);
            }

            // check if customer is using a support level with a 
            // minimum response time restriction, and if so, quarantine!
            if (Customer::hasMinimumResponseTime($prj_id, $customer_id)) {
                $min_response_time = Customer::getMinimumResponseTime($prj_id, $customer_id);
                $now_ts = Date_API::getCurrentUnixTimestampGMT();
                $expiration_date = Date_API::getDateGMTByTS($now_ts + $min_response_time);
                Issue::setQuarantine($issue_id, 2, $expiration_date);
                return true;
            }
            // check if customer is out of incidents and if so, quarantine issue
            if ((Customer::hasPerIncidentContract($prj_id, $customer_id)) && 
                    (!Customer::hasIncidentsLeft($prj_id, $customer_id))) {
                Issue::setQuarantine($issue_id, 1);
                return true;
            }
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
        History::add($issue_id, APP_SYSTEM_USER_ID, History::getTypeID('status_auto_changed'), $desc);
    }
}


?>