<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005 MySQL AB                              |
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

class Workflow
{
    /**
     * Returns a list of backends available
     *
     * @access  public
     * @return  array An array of workflow backends
     */
    function getBackendList()
    {
        $files = Misc::getFileList(APP_INC_PATH . "workflow");
        $list = array();
        for ($i = 0; $i < count($files); $i++) {
            // display a prettyfied backend name in the admin section
            if (preg_match('/^class\.(.*)\.php$/', $files[$i], $matches)) {
                if ($matches[1] == 'abstract_workflow_backend') {
                    continue;
                }
                $name = ucwords(str_replace('_', ' ', $matches[1]));
                $list[$files[$i]] = $name;
            }
        }
        return $list;
    }


    /**
     * Returns the name of the workflow backend for the specified project.
     *
     * @access  public
     * @param   integer $prj_id The id of the project to lookup.
     * @return  string The name of the customer backend.
     */
    function _getBackendNameByProject($prj_id)
    {
        static $backends;

        if (isset($backends[$prj_id])) {
            return $backends[$prj_id];
        }

        $stmt = "SELECT
                    prj_id,
                    prj_workflow_backend
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project
                 ORDER BY
                    prj_id";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return '';
        } else {
            $backends = $res;
            return @$backends[$prj_id];
        }
    }


    /**
     * Includes the appropriate workflow backend class associated with the
     * given project ID, instantiates it and returns the class.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  boolean
     */
    function &_getBackend($prj_id)
    {
        static $setup_backends;

        if (empty($setup_backends[$prj_id])) {
            $backend_class = Workflow::_getBackendNameByProject($prj_id);
            if (empty($backend_class)) {
                return false;
            }
            $file_name_chunks = explode(".", $backend_class);
            $class_name = $file_name_chunks[1] . "_Workflow_Backend";

            include_once(APP_INC_PATH . "workflow/$backend_class");

            $setup_backends[$prj_id] = new $class_name;
        }
        return $setup_backends[$prj_id];
    }


    /**
     * Checks whether the given project ID is setup to use workflow integration
     * or not.
     *
     * @access  public
     * @param   integer integer $prj_id The project ID
     * @return  boolean
     */
    function hasWorkflowIntegration($prj_id)
    {
        $backend = Workflow::_getBackendNameByProject($prj_id);
        if (empty($backend)) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * Is called when an issue is updated.
     *
     * @param   integer $prj_id The project ID.
     * @param   integer $issue_id The ID of the issue.
     * @param   integer $usr_id The ID of the user.
     * @param   array $old_details The old details of the issues.
     * @param   array $changes The changes that were applied to this issue (the $HTTP_POST_VARS)
     */
    function handleIssueUpdated($prj_id, $issue_id, $usr_id, $old_details, $changes)
    {
        if (!Workflow::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& Workflow::_getBackend($prj_id);
        return $backend->handleIssueUpdated($prj_id, $issue_id, $usr_id, $old_details, $changes);
    }


    /**
     * Called when an issue is assigned.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue.
     * @param   integer $usr_id The id of the user who assigned the issue.
     */
    function handleAssignment($prj_id, $issue_id, $usr_id)
    {
        if (!Workflow::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& Workflow::_getBackend($prj_id);
        return $backend->handleAssignment($prj_id, $issue_id, $usr_id);
    }


    /**
     * Called when a file is attached to an issue..
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue.
     * @param   integer $usr_id The id of the user who locked the issue.
     */
    function handleAttachment($prj_id, $issue_id, $usr_id)
    {
        if (!Workflow::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& Workflow::_getBackend($prj_id);
        return $backend->handleAttachment($prj_id, $issue_id, $usr_id);
    }


    /**
     * Called when the priority of an issue changes.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue.
     * @param   integer $usr_id The id of the user who locked the issue.
     * @param   array $old_details The old details of the issue.
     * @param   array $changes The changes that were applied to this issue (the $HTTP_POST_VARS)
     */
    function handlePriorityChange($prj_id, $issue_id, $usr_id, $old_details, $changes)
    {
        if (!Workflow::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& Workflow::_getBackend($prj_id);
        return $backend->handlePriorityChange($prj_id, $issue_id, $usr_id, $old_details, $changes);
    }


    /**
     * Called when an email is blocked.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue.
     * @param   array $email_details Details of the issue
     * @param   string $type What type of blocked email this is.
     */
    function handleBlockedEmail($prj_id, $issue_id, $email_details, $type)
    {
        if (!Workflow::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& Workflow::_getBackend($prj_id);
        return $backend->handleBlockedEmail($prj_id, $issue_id, $email_details, $type);
    }


    /**
     * Called when the assignment on an issue changes.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue.
     * @param   integer $usr_id The id of the user who locked the issue.
     * @param   array $issue_details The old details of the issue.
     * @param   array $new_assignees The new assignees of this issue.
     * @param   boolean $remote_assignment If this issue was remotely assigned.
     */
    function handleAssignmentChange($prj_id, $issue_id, $usr_id, $issue_details, $new_assignees, $remote_assignment = false)
    {
        if (!Workflow::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& Workflow::_getBackend($prj_id);
        return $backend->handleAssignmentChange($prj_id, $issue_id, $usr_id, $issue_details, $new_assignees, $remote_assignment);
    }


    /**
     * Called when a new issue is created.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue.
     * @param   boolean $has_TAM If this issue has a technical account manager.
     * @param   boolean $has_RR If Round Robin was used to assign this issue.
     */
    function handleNewIssue($prj_id, $issue_id, $has_TAM, $has_RR)
    {
        if (!Workflow::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& Workflow::_getBackend($prj_id);
        return $backend->handleNewIssue($prj_id, $issue_id, $has_TAM, $has_RR);
    }


    /**
     * Called when an email is recieved.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue.
     * @param   object $message An object containing the new email
     * @param   array $row The array of data that was inserted into the database.
     * @param   boolean $closing If we are closing the issue.
     */
    function handleNewEmail($prj_id, $issue_id, $message, $row = FALSE, $closing = false)
    {
        if (!Workflow::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& Workflow::_getBackend($prj_id);
        return $backend->handleNewEmail($prj_id, $issue_id, $message, $row, $closing);
    }


    /**
     * Called when an email is manually associated with an existing issue.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue.
     */
    function handleManualEmailAssociation($prj_id, $issue_id)
    {
        if (!Workflow::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& Workflow::_getBackend($prj_id);
        return $backend->handleManualEmailAssociation($prj_id, $issue_id);
    }


    /**
     * Called when a note is routed.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue.
     * @param   integer $usr_id The user ID of the person posting this new note
     * @param   boolean $closing If the issue is being closed
     */
    function handleNewNote($prj_id, $issue_id, $usr_id, $closing = false)
    {
        if (!Workflow::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& Workflow::_getBackend($prj_id);
        return $backend->handleNewNote($prj_id, $issue_id, $usr_id, $closing);
    }


    /**
     * Method is called to return the list of statuses valid for a specific issue.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue.
     * @return  array An associative array of statuses valid for this issue.
     */
    function getAllowedStatuses($prj_id, $issue_id)
    {
        if (!Workflow::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& Workflow::_getBackend($prj_id);
        return $backend->getAllowedStatuses($prj_id, $issue_id);
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
        if (!Workflow::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& Workflow::_getBackend($prj_id);
        $backend->handleIssueClosed($prj_id, $issue_id, $send_notification, $resolution_id, $status_id, $reason);
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
        if (!Workflow::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& Workflow::_getBackend($prj_id);
        return $backend->handleCustomFieldsUpdated($prj_id, $issue_id, $old, $new);
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
    function handleSubscription($prj_id, $issue_id, &$subscriber_usr_id, &$email, &$types)
    {
        if (!Workflow::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& Workflow::_getBackend($prj_id);
        return $backend->handleSubscription($prj_id, $issue_id, $subscriber_usr_id, $email, $types);
    }


    /**
     * Called when SCM checkin is associated.
     *
     * @param   integer $prj_id The project ID.
     * @param   integer $issue_id The ID of the issue.
     * @param   string $module The SCM module commit was made.
     * @param   array $files File list with their version numbers changes made on.
     * @param   string $username SCM user doing the checkin.
     * @param   string $commit_msg Message associated with the SCM commit.
     * @return  void
     */
    function handleSCMCheckins($prj_id, $issue_id, $module, $files, $username, $commit_msg)
    {
        if (!Workflow::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& Workflow::_getBackend($prj_id);
        return $backend->handleSCMCheckins($prj_id, $issue_id, $module, $files, $username, $commit_msg);
    }


    /**
     * Determines if the address should should be emailed.
     *
     * @param   integer $prj_id The project ID.
     * @param   string $address The email address to check
     * @return  boolean
     */
   function shouldEmailAddress($prj_id, $address)
   {
        if (!Workflow::hasWorkflowIntegration($prj_id)) {
            return true;
        }
        $backend =& Workflow::_getBackend($prj_id);
        return $backend->shouldEmailAddress($prj_id, $address);
   }


    /**
     * Returns additional email addresses that should be notified for a specific event..
     *
     * @param    integer $prj_id The project ID.
     * @param    integer $issue_id The ID of the issue.
     * @param    string  $event The event to return additional email addresses for. Currently only "new_issue" is supported.
     * @return   array   An array of email addresses to be notified.
     */
    function getAdditionalEmailAddresses($prj_id, $issue_id, $event)
    {
        if (!Workflow::hasWorkflowIntegration($prj_id)) {
            return array();
        }
        $backend =& Workflow::_getBackend($prj_id);
        return $backend->getAdditionalEmailAddresses($prj_id, $issue_id, $event);
    }


    /**
     * Indicates if the the specified email address can email the issue. Can be
     * used to disable email blocking by always returning true.
     *
     * @param   integer $prj_id The project ID.
     * @param   integer $issue_id The ID of the issue
     * @param   string The email address that is trying to send an email
     * @return  boolean true if the sender can email the issue, false if the sender
     *          should not email the issue and null if the default rules should be used.
     */
    function canEmailIssue($prj_id, $issue_id, $email)
    {
        if (!Workflow::hasWorkflowIntegration($prj_id)) {
            return null;
        }
        $backend =& Workflow::_getBackend($prj_id);
        return $backend->canEmailIssue($prj_id, $issue_id, $email);
    }


    /**
     * Handles when an authorized replier is added
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue
     * @param   string  $email The email address added
     * @return  boolean
     */
    function handleAuthorizedReplierAdded($prj_id, $issue_id, &$email)
    {
        if (!Workflow::hasWorkflowIntegration($prj_id)) {
            return null;
        }
        $backend =& Workflow::_getBackend($prj_id);
        return $backend->handleAuthorizedReplierAdded($prj_id, $issue_id, $email);
    }
}


?>