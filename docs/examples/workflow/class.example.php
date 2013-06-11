<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2013 Eventum Team.                              |
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


/**
 * Example workflow backend class. For example purposes it will print what
 * method is called.
 *
 * @author  Bryan Alsdorf <bryan@mysql.com>
 */
class Example_Workflow_Backend extends Abstract_Workflow_Backend
{
    /**
     * Called when an issue is updated.
     *
     * @param integer $prj_id The project ID.
     * @param integer $issue_id The ID of the issue.
     * @param integer $usr_id The ID of the user.
     * @param array $old_details The old details of the issues.
     * @param array $changes The changes that were applied to this issue (the $_POST)
     */
    function handleIssueUpdated($prj_id, $issue_id, $usr_id, $old_details, $changes)
    {
        echo "Workflow: Issue Updated<br />\n";
    }


    /**
     * Called when an issue is assigned.
     *
     * @param   integer $prj_id The projectID
     * @param   integer $issue_id The ID of the issue.
     * @param   integer $usr_id The id of the user who assigned the issue.
     */
    function handleAssignment($prj_id, $issue_id, $usr_id)
    {
        echo "Workflow: Issue Assigned<br />\n";
    }


    /**
     * Called when a file is attached to an issue.
     *
     * @param   integer $prj_id The projectID
     * @param   integer $issue_id The ID of the issue.
     * @param   integer $usr_id The id of the user who locked the issue.
     */
    function handleAttachment($prj_id, $issue_id, $usr_id)
    {
        echo "Workflow: File attached<br />\n";
    }


    /**
     * Called when the priority of an issue changes.
     *
     * @param   integer $prj_id The projectID
     * @param   integer $issue_id The ID of the issue.
     * @param   integer $usr_id The id of the user who locked the issue.
     * @param   array $old_details The old details of the issue.
     * @param   array $changes The changes that were applied to this issue (the $_POST)
     */
    function handlePriorityChange($prj_id, $issue_id, $usr_id, $old_details, $changes)
    {
        echo "Workflow: Priority Changed<br />\n";
    }


    /**
     * Called when an email is blocked.
     *
     * @param   integer $prj_id The projectID
     * @param   integer $issue_id The ID of the issue.
     * @param   array $email_details Details of the issue
     * @param   string $type What type of blocked email this is.
     */
    function handleBlockedEmail($prj_id, $issue_id, $email_details, $type)
    {
        echo "Workflow: Email Blocked<br />\n";
    }


    /**
     * Called when a note is routed.
     *
     * @param   integer $prj_id The projectID
     * @param   integer $issue_id The ID of the issue.
     * @param   integer $usr_id The user ID of the person posting this new note
     * @param   boolean $closing If the issue is being closed
     * @param   integer $note_id The ID of the new note
     */
    function handleNewNote($prj_id, $issue_id, $usr_id, $closing, $note_id)
    {
        echo "Workflow: New Note<br />\n";
    }


    /**
     * Called when the assignment on an issue changes.
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
        echo "Workflow: Assignment changed<br />\n";
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
        echo "Workflow: New Issue<br />\n";
    }




    /**
     * Updates the existing issue to a different status when an email is
     * manually associated to an existing issue.
     *
     * @access  public
     * @param   integer $prj_id The projectID
     * @param   integer $issue_id The issue ID
     */
    function handleManualEmailAssociation($prj_id, $issue_id)
    {
        echo "Workflow: Manually associating email to issue<br />\n";
    }


    /**
     * Called when a new message is received.
     *
     * @param   integer $prj_id The projectID
     * @param   integer $issue_id The ID of the issue.
     * @param   object $message An object containing the new email
     * @param   array $row The array of data that was inserted into the database.
     * @param   boolean $closing If we are closing the issue.
     */
    function handleNewEmail($prj_id, $issue_id, $message, $row = false, $closing = false)
    {
        echo "Workflow: New";
        if ($closing) {
            echo " closing";
        }
        echo " Email<br />\n";
    }


    /**
     * Method is called to return the list of statuses valid for a specific issue.
     *
     * @param   integer $prj_id The projectID
     * @param   integer $issue_id The ID of the issue.
     * @return  array An associative array of statuses valid for this issue.
     */
    function getAllowedStatuses($prj_id, $issue_id)
    {
        echo "Workflow: Returning allowed statuses<br />\n";
       $statuses = Status::getAssocStatusList($prj_id, false);
       unset($statuses[4], $statuses[3]);
       // you should perform any logic and remove any statuses you need to here.
       return $statuses;
    }


    /**
     * Called when an attempt is made to add a user or email address to the
     * notification list.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue.
     * @param   integer $subscriber_usr_id The ID of the user to subscribe if this is a real user (false otherwise).
     * @param   string $email The email address to subscribe to subscribe (if this is not a real user).
     * @param   array $types The action types.
     * @return  mixed An array of information or true to continue unchanged or false to prevent the user from being added.
     */
    function handleSubscription($prj_id, $issue_id, &$subscriber_usr_id, &$email, &$actions)
    {
        // prevent a certain email address from being added to the notification list.
        if ($email == "invalidemail@example.com") {
            return false;
        }
        // just for this example, if the usr_id is 99, change the usr_id to 100
        if ($subscriber_usr_id == 99) {
            $subscriber_usr_id = 100;
        }
        // another thing this workflow can do is change the actions a user is subscribed too.
        // we will make sure all users are subscribed to the "email" action.
        if (!in_array("emails", $actions)) {
            $actions[] = "emails";
        }
        // you can also change the email address being subscribed
        if ($email == "changethis@example.com") {
            $email = "changed@example.com";
        }
        // if you want the subscription to be added with no changes, simply return true;
        return true;
    }


    /**
     * Called when issue is closed.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue.
     * @param   boolean $send_notification Whether to send a notification about this action or not
     * @param   integer $resolution_id The resolution ID
     * @param   integer $status_id The status ID
     * @param   string $reason The reason for closing this issue
     * @return  void
     */
    function handleIssueClosed($prj_id, $issue_id, $send_notification, $resolution_id, $status_id, $reason)
    {
        $sql = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                SET
                    iss_percent_complete = '100%'
                WHERE
                    iss_id = $issue_id";
        $res = DB_Helper::getInstance()->query($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        }

        echo "Workflow: handleIssueClosed<br />\n";
    }


    /**
     * Called when custom fields are updated
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue
     * @param   array $old The custom fields before the update.
     * @param   array $new The custom fields after the update.
     */
    function handleCustomFieldsUpdated($prj_id, $issue_id, $old, $new)
    {
        echo "Workflow: handleCustomFieldsUpdated<br />\n";
    }


    /**
     * Determines if the address should should be emailed.
     *
     * @param   integer $prj_id The project ID
     * @param   string $address The email address to check
     * @return  boolean
     */
    function shouldEmailAddress($prj_id, $address)
    {
        if ($address == "bad_email@example.com") {
            return false;
        } else {
            return true;
        }
    }


    /**
     * Returns which "issue fields" should be displayed in a given location.
     *
     * @see     class.issue_field.php
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue
     * @param   string  $location The location to display these fields at
     * @return  array   an array of fields to display and their associated options
     */
    function getIssueFieldsToDisplay($prj_id, $issue_id, $location)
    {
        if ($location == 'post_note') {
            return array(
                        'assignee'  =>  array(),
                        'custom'    =>  array(1),
            );
        } else {
            return array();
        }
    }
}
