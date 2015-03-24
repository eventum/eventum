<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2014 Eventum Team.                              |
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
// | 51 Franklin Street, Suite 330                                          |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: Bryan Alsdorf <bryan@mysql.com>                             |
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+
//

class Workflow
{
    /**
     * Returns a list of backends available
     *
     * @return  array An array of workflow backends
     */
    public static function getBackendList()
    {
        $files = Misc::getFileList(APP_INC_PATH . '/workflow');
        $files = array_merge($files, Misc::getFileList(APP_LOCAL_PATH. '/workflow'));
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
     * @param   integer $prj_id The id of the project to lookup.
     * @return  string The name of the customer backend.
     */
    private static function _getBackendNameByProject($prj_id)
    {
        static $backends;

        if (isset($backends[$prj_id])) {
            return $backends[$prj_id];
        }

        $stmt = "SELECT
                    prj_id,
                    prj_workflow_backend
                 FROM
                    {{%project}}
                 ORDER BY
                    prj_id";
        try {
            $res = DB_Helper::getInstance()->fetchAssoc($stmt);
        } catch (DbException $e) {
            return '';
        }

        $backends = $res;

        return @$backends[$prj_id];
    }

    /**
     * Includes the appropriate workflow backend class associated with the
     * given project ID, instantiates it and returns the class.
     *
     * @param   integer $prj_id The project ID
     * @return  Abstract_Workflow_Backend
     */
    public static function &_getBackend($prj_id)
    {
        static $setup_backends;

        if (empty($setup_backends[$prj_id])) {
            $backend_class = self::_getBackendNameByProject($prj_id);
            if (empty($backend_class)) {
                return false;
            }
            $file_name_chunks = explode(".", $backend_class);
            $class_name = $file_name_chunks[1] . "_Workflow_Backend";

            if (file_exists(APP_LOCAL_PATH . "/workflow/$backend_class")) {
                require_once APP_LOCAL_PATH . "/workflow/$backend_class";
            } else {
                require_once APP_INC_PATH . "/workflow/$backend_class";
            }

            $setup_backends[$prj_id] = new $class_name();
        }

        return $setup_backends[$prj_id];
    }

    /**
     * Checks whether the given project ID is setup to use workflow integration
     * or not.
     *
     * @param   integer integer $prj_id The project ID
     * @return  boolean
     */
    public static function hasWorkflowIntegration($prj_id)
    {
        $backend = self::_getBackendNameByProject($prj_id);
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
     * @param   array $changes The changes that were applied to this issue (the $_POST)
     */
    public static function handleIssueUpdated($prj_id, $issue_id, $usr_id, $old_details, $changes)
    {
        Partner::handleIssueChange($issue_id, $usr_id, $old_details, $changes);
        if (!self::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& self::_getBackend($prj_id);

        return $backend->handleIssueUpdated($prj_id, $issue_id, $usr_id, $old_details, $changes);
    }

    /**
     * Called before an issue is updated.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue
     * @param   integer $usr_id The ID of the user changing the issue.
     * @param   array   $changes
     * @return  mixed. True to continue, anything else to cancel the change and return the value
     */
    public static function preIssueUpdated($prj_id, $issue_id, $usr_id, &$changes)
    {
        if (!self::hasWorkflowIntegration($prj_id)) {
            return true;
        }
        $backend =& self::_getBackend($prj_id);

        return $backend->preIssueUpdated($prj_id, $issue_id, $usr_id, $changes);
    }

    /**
     * Called when an issue is assigned.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue.
     * @param   integer $usr_id The id of the user who assigned the issue.
     */
    public function handleAssignment($prj_id, $issue_id, $usr_id)
    {
        if (!self::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& self::_getBackend($prj_id);

        return $backend->handleAssignment($prj_id, $issue_id, $usr_id);
    }

    /**
     * Called when a file is attached to an issue..
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue.
     * @param   integer $usr_id The id of the user who locked the issue.
     */
    public static function handleAttachment($prj_id, $issue_id, $usr_id)
    {
        if (!self::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& self::_getBackend($prj_id);

        return $backend->handleAttachment($prj_id, $issue_id, $usr_id);
    }

    /**
     * Determines if the attachment should be added
     *
     * @param   integer $prj_id The project ID.
     * @param   integer $issue_id The ID of the issue.
     * @param   integer $usr_id The id of the user who attached the file
     * @param   array $attachment attachment object
     * @return  boolean
     */
    public static function shouldAttachFile($prj_id, $issue_id, $usr_id, $attachment)
    {
        if (!self::hasWorkflowIntegration($prj_id)) {
            return true;
        }
        $backend =& self::_getBackend($prj_id);

        return $backend->shouldAttachFile($prj_id, $issue_id, $usr_id, $attachment);
    }

    /**
     * Called when the priority of an issue changes.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue.
     * @param   integer $usr_id The id of the user who changed the issue.
     * @param   array $old_details The old details of the issue.
     * @param   array $changes The changes that were applied to this issue (the $_POST)
     */
    public static function handlePriorityChange($prj_id, $issue_id, $usr_id, $old_details, $changes)
    {
        if (!self::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& self::_getBackend($prj_id);

        return $backend->handlePriorityChange($prj_id, $issue_id, $usr_id, $old_details, $changes);
    }

    /**
     * Called when the severity of an issue changes.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue.
     * @param   integer $usr_id The id of the user who changed the issue.
     * @param   array $old_details The old details of the issue.
     * @param   array $changes The changes that were applied to this issue (the $_POST)
     */
    public static function handleSeverityChange($prj_id, $issue_id, $usr_id, $old_details, $changes)
    {
        if (!self::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& self::_getBackend($prj_id);

        return $backend->handleSeverityChange($prj_id, $issue_id, $usr_id, $old_details, $changes);
    }

    /**
     * Called when an email is blocked.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue.
     * @param   array $email_details Details of the issue
     * @param   string $type What type of blocked email this is.
     */
    public static function handleBlockedEmail($prj_id, $issue_id, $email_details, $type)
    {
        if (!self::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& self::_getBackend($prj_id);

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
    public static function handleAssignmentChange($prj_id, $issue_id, $usr_id, $issue_details, $new_assignees, $remote_assignment = false)
    {
        if (!self::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& self::_getBackend($prj_id);

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
    public static function handleNewIssue($prj_id, $issue_id, $has_TAM, $has_RR)
    {
        if (!self::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& self::_getBackend($prj_id);

        return $backend->handleNewIssue($prj_id, $issue_id, $has_TAM, $has_RR);
    }

    /**
     * Called when an email is received.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue.
     * @param   object $message An object containing the new email
     * @param   array $row The array of data that was inserted into the database.
     * @param   boolean $closing If we are closing the issue.
     */
    public static function handleNewEmail($prj_id, $issue_id, $message, $row, $closing = false)
    {
        Partner::handleNewEmail($issue_id, $row['sup_id']);

        if (!self::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& self::_getBackend($prj_id);

        return $backend->handleNewEmail($prj_id, $issue_id, $message, $row, $closing);
    }

    /**
     * Called when an email is manually associated with an existing issue.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue.
     */
    public static function handleManualEmailAssociation($prj_id, $issue_id)
    {
        if (!self::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& self::_getBackend($prj_id);

        return $backend->handleManualEmailAssociation($prj_id, $issue_id);
    }

    /**
     * Called when a note is routed.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue.
     * @param   integer $usr_id The user ID of the person posting this new note
     * @param   boolean $closing If the issue is being closed
     * @param   integer $note_id The ID of the new note
     */
    public static function handleNewNote($prj_id, $issue_id, $usr_id, $closing = false, $note_id = false)
    {
        Partner::handleNewNote($issue_id, $note_id);

        if (!self::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& self::_getBackend($prj_id);

        return $backend->handleNewNote($prj_id, $issue_id, $usr_id, $closing, $note_id);
    }

    /**
     * Method is called to return the list of statuses valid for a specific issue.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue.
     * @return  array An associative array of statuses valid for this issue.
     */
    public static function getAllowedStatuses($prj_id, $issue_id = null)
    {
        if (!self::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& self::_getBackend($prj_id);

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
     * @param   string  $reason The reason for closing this issue
     * @param   integer $usr_id The ID of the user closing this issue
     * @return  void
     */
    public static function handleIssueClosed($prj_id, $issue_id, $send_notification, $resolution_id, $status_id, $reason, $usr_id)
    {
        if (!self::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& self::_getBackend($prj_id);
        $backend->handleIssueClosed($prj_id, $issue_id, $send_notification, $resolution_id, $status_id, $reason, $usr_id);
    }

    /**
     * Called when custom fields are updated
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue
     * @param   array $old The custom fields before the update.
     * @param   array $new The custom fields after the update.
     */
    public static function handleCustomFieldsUpdated($prj_id, $issue_id, $old, $new)
    {
        if (!self::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& self::_getBackend($prj_id);

        return $backend->handleCustomFieldsUpdated($prj_id, $issue_id, $old, $new);
    }

    /**
     * Called when an attempt is made to add a user or email address to the
     * notification list.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue.
     * @param   integer $subscriber_usr_id The ID of the user to subscribe if this is a real user (false otherwise).
     * @param   string $email The email address  to subscribe (if this is not a real user).
     * @param   array $types The action types.
     * @return  mixed An array of information or true to continue unchanged or false to prevent the user from being added.
     */
    public static function handleSubscription($prj_id, $issue_id, &$subscriber_usr_id, &$email, &$types)
    {
        if (!self::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& self::_getBackend($prj_id);

        return $backend->handleSubscription($prj_id, $issue_id, $subscriber_usr_id, $email, $types);
    }

    /**
     * Called when SCM checkins are associated.
     *
     * @param   integer $prj_id The project ID.
     * @param   integer $issue_id The ID of the issue.
     * @param   array $files File list with their version numbers changes made on.
     * @param   string $username SCM user doing the checkin.
     * @param   string $commit_msg Message associated with the SCM commit.
     */
    public static function handleSCMCheckins($prj_id, $issue_id, $files, $username, $commit_msg)
    {
        if (!self::hasWorkflowIntegration($prj_id)) {
            return;
        }

        $backend = self::_getBackend($prj_id);

        /**
         * @deprecated. The $module parameter is deprecated. always NULL, use 'module' from $file object
         */
        $backend->handleSCMCheckins($prj_id, $issue_id, null, $files, $username, $commit_msg);
    }

    /**
     * Determines if the address should should be emailed.
     *
     * @param integer $prj_id The project ID.
     * @param string $address The email address to check
     * @param bool $issue_id
     * @param bool $type
     * @return boolean
     */
    public static function shouldEmailAddress($prj_id, $address, $issue_id = false, $type = false)
    {
        if (!self::hasWorkflowIntegration($prj_id)) {
            return true;
        }
        $backend =& self::_getBackend($prj_id);

        return $backend->shouldEmailAddress($prj_id, $address, $issue_id, $type);
    }

    /**
     * Returns additional email addresses that should be notified for a specific event..
     *
     * @param   integer $prj_id The project ID.
     * @param   integer $issue_id The ID of the issue.
     * @param   string  $event The event to return additional email addresses for. Currently only "new_issue" is supported.
     * @param   array   $extra Extra information, contains different info depending on where it is called from
     * @return  array   An array of email addresses to be notified.
     */
    public static function getAdditionalEmailAddresses($prj_id, $issue_id, $event, $extra = false)
    {
        if (!self::hasWorkflowIntegration($prj_id)) {
            return array();
        }
        $backend =& self::_getBackend($prj_id);

        return $backend->getAdditionalEmailAddresses($prj_id, $issue_id, $event, $extra);
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
    public static function canEmailIssue($prj_id, $issue_id, $email)
    {
        if (!self::hasWorkflowIntegration($prj_id)) {
            return null;
        }
        $backend =& self::_getBackend($prj_id);

        return $backend->canEmailIssue($prj_id, $issue_id, $email);
    }

    /**
     * Called to check if an email address that does not have an eventum account can send notes to an issue.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The issue ID
     * @param   string $email The email address to check
     * @param $structure
     * @return  boolean True if the note should be added, false otherwise
     */
    public static function canSendNote($prj_id, $issue_id, $email, $structure)
    {
        if (!Workflow::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& Workflow::_getBackend($prj_id);

        return $backend->canSendNote($prj_id, $issue_id, $email, $structure);
    }

    /**
     * Called to check if an email address that does not have an eventum account can send notes to an issue.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The issue ID
     * @param   string $usr_id The ID of the user
     * @return  boolean True if the issue can be cloned, false otherwise
     */
    public static function canCloneIssue($prj_id, $issue_id, $usr_id)
    {
        if (!Workflow::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& Workflow::_getBackend($prj_id);

        return $backend->canCloneIssue($prj_id, $issue_id, $usr_id);
    }

    /**
     * Handles when an authorized replier is added
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue
     * @param   string  $email The email address added
     * @return  boolean
     */
    public static function handleAuthorizedReplierAdded($prj_id, $issue_id, &$email)
    {
        if (!self::hasWorkflowIntegration($prj_id)) {
            return null;
        }
        $backend =& self::_getBackend($prj_id);

        return $backend->handleAuthorizedReplierAdded($prj_id, $issue_id, $email);
    }

    /**
     * Called at the begining of the email download process. If it returns -1, the
     * rest of the email code will not be executed.
     *
     * @param   integer $prj_id The project ID
     * @param   array $info An array containing the information on the email account.
     * @param   resource $mbox The imap connection resource
     * @param   integer $num The sequential email number
     * @param   string $message The complete email message
     * @param   object $email An object containing the decoded email
     * @param   object $structure An object containing the decoded email
     * @return  mixed null by default, -1 if the rest of the email script should not be processed.
     */
    public static function preEmailDownload($prj_id, $info, $mbox, $num, &$message, &$email, &$structure)
    {
        if (!self::hasWorkflowIntegration($prj_id)) {
            return null;
        }
        $backend =& self::_getBackend($prj_id);

        return $backend->preEmailDownload($prj_id, $info, $mbox, $num, $message, $email, $structure);
    }

    /**
     * Called before inserting a note. If it returns false the rest of the note code
     * will not be executed. Return null to continue as normal (possibly with changed $data)
     *
     * @param   integer $prj_id
     * @param   integer $issue_id
     * @param   array   $data
     * @return  mixed   Null by default, false if the note should not be inserted
     */
    public static function preNoteInsert($prj_id, $issue_id, $unknown_user, &$data)
    {
        if (!self::hasWorkflowIntegration($prj_id)) {
            return null;
        }
        $backend =& self::_getBackend($prj_id);

        return $backend->preNoteInsert($prj_id, $issue_id, $unknown_user, $data);
    }

    /**
     * Indicates if the email addresses should automatically be added to the NL from notes and emails.
     *
     * @param   integer $prj_id The project ID.
     * @return  boolean
     */
    public static function shouldAutoAddToNotificationList($prj_id)
    {
        if (!self::hasWorkflowIntegration($prj_id)) {
            return true;
        }
        $backend =& self::_getBackend($prj_id);

        return $backend->shouldAutoAddToNotificationList($prj_id);
    }

    /**
     * Returns the issue ID to associate a new email with, null to use the default logic and "new" to create
     * a new issue.
     * Can also return an array containing 'customer_id', 'contact_id' and 'contract_id', 'sev_id'
     *
     * @param   integer $prj_id The ID of the project
     * @param   array   $info An array of info about the email account.
     * @param   string  $headers The headers of the email.
     * @param   string  $message_body The body of the message.
     * @param   string  $date The date this message was sent
     * @param   string  $from The name and email address of the sender.
     * @param   string  $subject The subject of this message.
     * @param   array   $to An array of to addresses
     * @param   array   $cc An array of cc addresses
     * @return  string|array
     */
    public static function getIssueIDForNewEmail($prj_id, $info, $headers, $message_body, $date, $from, $subject, $to, $cc)
    {
        if (!self::hasWorkflowIntegration($prj_id)) {
            return null;
        }
        $backend =& self::_getBackend($prj_id);

        return $backend->getIssueIDforNewEmail($prj_id, $info, $headers, $message_body, $date, $from, $subject, $to, $cc);
    }

    /**
     * Modifies the content of the message being added to the mail queue.
     *
     * @param   integer $prj_id
     * @param   string $recipient
     * @param   array $headers
     * @param   string $body
     * @param   integer $issue_id
     * @param   string $type The type of message this is.
     * @param   integer $sender_usr_id The id of the user sending this email.
     * @param   integer $type_id The ID of the event that triggered this notification (issue_id, sup_id, not_id, etc)
     */
    public static function modifyMailQueue($prj_id, &$recipient, &$headers, &$body, $issue_id, $type, $sender_usr_id, $type_id)
    {
        if (!self::hasWorkflowIntegration($prj_id)) {
            return true;
        }
        $backend =& self::_getBackend($prj_id);

        return $backend->modifyMailQueue($prj_id, $recipient, $headers, $body, $issue_id, $type, $sender_usr_id, $type_id);
    }

    /**
     * Called before the status changes. Parameters are passed by reference so the values can be changed.
     *
     * @param   integer $prj_id
     * @param   integer $issue_id
     * @param   integer $status_id
     * @param   boolean $notify
     * @return  boolean true to continue normal processing, anything else to cancel and return value.
     */
    public static function preStatusChange($prj_id, &$issue_id, &$status_id, &$notify)
    {
        if (!self::hasWorkflowIntegration($prj_id)) {
            return true;
        }
        $backend =& self::_getBackend($prj_id);

        return $backend->preStatusChange($prj_id, $issue_id, $status_id, $notify);
    }

    /**
     * Called at the start of many pages. After the includes and maybe some other code this
     * method is called to do whatever you want. Eventually this will be called on many pages.
     *
     * @param   integer $prj_id The project ID
     * @param   string $page_name The name of the page
     * @return  null
     */
    public static function prePage($prj_id, $page_name)
    {
        if (!self::hasWorkflowIntegration($prj_id)) {
            return true;
        }
        $backend =& self::_getBackend($prj_id);

        return $backend->prePage($prj_id, $page_name);
    }

    /**
     * Called to determine which actions to subscribe a new user too.
     *
     * @see     Notification::getDefaultActions()
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue
     * @param   string  $email The email address of the user being added
     * @param   string  $source The source of this call
     * @return  array   an array of actions
     */
    public static function getNotificationActions($prj_id, $issue_id, $email, $source)
    {
        if (!self::hasWorkflowIntegration($prj_id)) {
            return null;
        }
        $backend =& self::_getBackend($prj_id);

        return $backend->getNotificationActions($prj_id, $issue_id, $email, $source);
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
    public static function getIssueFieldsToDisplay($prj_id, $issue_id, $location)
    {
        if (!self::hasWorkflowIntegration($prj_id)) {
            return array();
        }
        $backend =& self::_getBackend($prj_id);

        return $backend->getIssueFieldsToDisplay($prj_id, $issue_id, $location);
    }

    /**
     * Returns an array of patterns and replacements.
     *
     * @param   integer $prj_id The ID of the project
     * @return  array An array of patterns and replacements
     */
    public static function getLinkFilters($prj_id)
    {
        if (!self::hasWorkflowIntegration($prj_id)) {
            return array();
        }
        $backend =& self::_getBackend($prj_id);

        return $backend->getLinkFilters($prj_id);
    }

    /**
     * Returns if a user can update an issue. Return null to use default rules.
     */
    public static function canUpdateIssue($prj_id, $issue_id, $usr_id)
    {
        if (!self::hasWorkflowIntegration($prj_id)) {
            return null;
        }
        $backend =& self::_getBackend($prj_id);

        return $backend->canUpdateIssue($prj_id, $issue_id, $usr_id);
    }

    /**
     * Returns the ID of the group that is "active" right now.
     */
    public static function getActiveGroup($prj_id)
    {
        if (!self::hasWorkflowIntegration($prj_id)) {
            return null;
        }
        $backend =& self::_getBackend($prj_id);

        return $backend->getActiveGroup($prj_id);
    }

    public static function formatIRCMessage($prj_id, $notice, $issue_id = false, $usr_id = false, &$category = false,
                                            $type = false)
    {
        if (!self::hasWorkflowIntegration($prj_id)) {
            return $notice;
        }
        $backend =& self::_getBackend($prj_id);

        return $backend->formatIRCMessage($prj_id, $notice, $issue_id, $usr_id, $category, $type);
    }
}
