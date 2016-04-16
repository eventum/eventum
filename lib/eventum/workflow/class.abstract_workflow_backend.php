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
 * Abstract Class that all workflow backends should extend. This is so any new
 * workflow methods added in future releases will not break current backends.
 */
class Abstract_Workflow_Backend
{
    /**
     * Interface for using config values within Workflow class.
     *
     * To read an option:
     * <code>
     * $option = $this->getConfig('myoption');
     * </code>
     *
     * to make change to option value::
     * <code>
     * $this->config['myoption'] = $new_value;
     * $this->saveConfig();
     * </code>
     */

    /**
     * hold workflow settings, best accessed via ->getConfig()
     */
    protected $config = null;

    /**
     * use this function to access workflow configuration variables
     *
     * @param string $option
     * @return mixed
     */
    public function getConfig($option = null)
    {
        if (!isset($this->config)) {
            $this->loadConfig();
        }

        return $option === null ? $this->config : $this->config[$option];
    }

    /**
     * loadConfig()
     * merges the workflow's default settings with any local settings
     * this function is automatically called through getConfig()
     */
    private function loadConfig()
    {
        $defaults = $this->getConfigDefaults();
        $name = $this->getWorkflowName();
        $setup = Setup::get();

        if (!isset($setup['workflow'])) {
            $setup['workflow'] = array();
        }

        // create copy, this avoids the "indirect" error
        $config = $setup['workflow'][$name];
        // merge defaults
        foreach ($defaults as $key => $value) {
            if (isset($config[$key])) {
                continue;
            }
            $config[$key] = $value;
        }

        // save back to config tree
        $setup['workflow'][$name] = $config;

        // assign again to get Zend\Config instance
        $this->config = $setup['workflow'][$name];
    }

    /**
     * If you made changes to config, you may call this to persist the changes
     * back to disk
     */
    protected function saveConfig()
    {
        if (!isset($this->config)) {
            return;
        }

        Setup::save();
    }

    /**
     * You should override this in your workflow class
     */
    protected function getConfigDefaults()
    {
        return array();
    }

    /**
     * Returns name of active workflow class
     */
    protected function getWorkflowName()
    {
        return strtolower(current(explode('_', get_class($this), 2)));
    }

    /**
     * Called when an issue is updated.
     *
     * @param integer $prj_id The project ID.
     * @param integer $issue_id The ID of the issue.
     * @param integer $usr_id The ID of the user.
     * @param array $old_details The old details of the issues.
     * @param array $changes The changes that were applied to this issue (the $_POST)
     */
    public function handleIssueUpdated($prj_id, $issue_id, $usr_id, $old_details, $changes)
    {
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
    public function preIssueUpdated($prj_id, $issue_id, $usr_id, &$changes)
    {
        return true;
    }

    /**
     * Called when a file is attached to an issue.
     *
     * @param   integer $prj_id The projectID
     * @param   integer $issue_id The ID of the issue.
     * @param   integer $usr_id The id of the user who locked the issue.
     */
    public function handleAttachment($prj_id, $issue_id, $usr_id)
    {
    }

    /**
     * Determines if the attachment should be added
     *
     * Attachment array contains:
     * - $attachment['filename']
     * - $attachment['filetype']
     * - $attachment['blob']
     *
     * @param   integer $prj_id The project ID.
     * @param   integer $issue_id The ID of the issue.
     * @param   integer $usr_id The id of the user who attached the file
     * @param   array $attachment attachment object
     * @return  boolean
     */
    public function shouldAttachFile($prj_id, $issue_id, $usr_id, $attachment)
    {
        return true;
    }

    /**
     * Called when the priority of an issue changes.
     *
     * @param   integer $prj_id The projectID
     * @param   integer $issue_id The ID of the issue.
     * @param   integer $usr_id The id of the user who changed the issue.
     * @param   array $old_details The old details of the issue.
     * @param   array $changes The changes that were applied to this issue (the $_POST)
     */
    public function handlePriorityChange($prj_id, $issue_id, $usr_id, $old_details, $changes)
    {
    }

    /**
     * Called when the severity of an issue changes.
     *
     * @param   integer $prj_id The projectID
     * @param   integer $issue_id The ID of the issue.
     * @param   integer $usr_id The id of the user who changed the issue.
     * @param   array $old_details The old details of the issue.
     * @param   array $changes The changes that were applied to this issue (the $_POST)
     */
    public function handleSeverityChange($prj_id, $issue_id, $usr_id, $old_details, $changes)
    {
    }

    /**
     * Called when an email is blocked.
     *
     * @param   integer $prj_id The projectID
     * @param   integer $issue_id The ID of the issue.
     * @param   array $email_details Details of the issue
     * @param   string $type What type of blocked email this is.
     */
    public function handleBlockedEmail($prj_id, $issue_id, $email_details, $type)
    {
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
    public function handleNewNote($prj_id, $issue_id, $usr_id, $closing, $note_id)
    {
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
    public function handleAssignmentChange($prj_id, $issue_id, $usr_id, $issue_details, $new_assignees, $remote_assignment)
    {
    }

    /**
     * Called when a new issue is created.
     *
     * @param   integer $prj_id The projectID
     * @param   integer $issue_id The ID of the issue.
     * @param   boolean $has_TAM If this issue has a technical account manager.
     * @param   boolean $has_RR If Round Robin was used to assign this issue.
     */
    public function handleNewIssue($prj_id, $issue_id, $has_TAM, $has_RR)
    {
    }

    /**
     * Called when an email is associated with an issue.
     *
     * @param   integer $prj_id The projectID
     * @param   integer $issue_id The issue ID
     */
    public function handleManualEmailAssociation($prj_id, $issue_id)
    {
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
    public function handleNewEmail($prj_id, $issue_id, $message, $row = null, $closing = false)
    {
    }

    /**
     * Method is called to return the list of statuses valid for a specific issue.
     *
     * @param   integer $prj_id The projectID
     * @param   integer $issue_id The ID of the issue.
     * @return  array An associative array of statuses valid for this issue.
     */
    public function getAllowedStatuses($prj_id, $issue_id)
    {
        return Status::getAssocStatusList($prj_id, false);
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
     * @param   integer $usr_id The ID of the user closing this issue
     * @return  void
     */
    public function handleIssueClosed($prj_id, $issue_id, $send_notification, $resolution_id, $status_id, $reason, $usr_id)
    {
    }

    /**
     * Called when custom fields are updated
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue
     * @param   array $old The custom fields before the update.
     * @param   array $new The custom fields after the update.
     * @param   array $changed An array containing what was changed.
     */
    public function handleCustomFieldsUpdated($prj_id, $issue_id, $old, $new, $changed)
    {
    }

    /**
     * Called when an attempt is made to add a user or email address to the
     * notification list.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue.
     * @param   integer $subscriber_usr_id The ID of the user to subscribe if this is a real user (false otherwise).
     * @param   string $email The email address to subscribe to subscribe (if this is not a real user).
     * @param   array $actions The action types.
     * @return  mixed An array of information or true to continue unchanged or false to prevent the user from being added.
     */
    public function handleSubscription($prj_id, $issue_id, &$subscriber_usr_id, &$email, &$actions)
    {
        return true;
    }

    /**
     * Called when SCM checkin is associated.
     *
     * NOTE: The $module parameter is deprecated. always NULL, use 'module' from $file object
     *
     * @param   integer $prj_id The project ID.
     * @param   integer $issue_id The ID of the issue.
     * @param   string $module The SCM module commit was made.
     * @param   array $files File list with their version numbers changes made on.
     * @param   string $username SCM user doing the checkin.
     * @param   string $commit_msg Message associated with the SCM commit.
     * @param   ScmCheckin $scm SCM config associated with the commit
     */
    public function handleSCMCheckins($prj_id, $issue_id, $module, $files, $username, $commit_msg, $scm)
    {
    }

    /**
     * Determines if the address should be emailed.
     *
     * @param   integer $prj_id The project ID
     * @param   string $address The email address to check
     * @param   integer $issue_id The ID of the issue.
     * @param   string $type The type of notification to send.
     * @return  boolean
     */
    public function shouldEmailAddress($prj_id, $address, $issue_id = null, $type = null)
    {
        return true;
    }

    /**
     * Returns additional email addresses that should be notified for a specific event..
     *
     * @param    integer $prj_id The project ID.
     * @param    integer $issue_id The ID of the issue.
     * @param    string  $event The event to return additional email addresses for. Currently only "new_issue" is supported.
     * @return   array   An array of email addresses to be notified.
     */
    public function getAdditionalEmailAddresses($prj_id, $issue_id, $event)
    {
        return array();
    }

    /**
     * Indicates if the the specified email address can email the issue. Can be
     * used to disable email blocking by always returning true.
     *
     * @param   integer $prj_id The project ID.
     * @param   integer $issue_id The ID of the issue
     * @param   string $email The email address that is trying to send an email
     * @return  boolean true if the sender can email the issue, false if the sender
     *          should not email the issue and null if the default rules should be used.
     */
    public function canEmailIssue($prj_id, $issue_id, $email)
    {
        return null;
    }

    /**
     * Called to check if an email address that does not have an eventum account can send notes to an issue.
     *
     * @param integer $prj_id The project ID
     * @param integer $issue_id The issue ID
     * @param string $email The email address to check
     * @param object $structure Parsed email structure
     * @return  boolean True if the note should be added, false otherwise
     */
    public function canSendNote($prj_id, $issue_id, $email, $structure)
    {
        return null;
    }

    /**
     * Handles when an authorized replier is added
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue
     * @param   string  $email The email address added
     * @return  boolean
     */
    public function handleAuthorizedReplierAdded($prj_id, $issue_id, $email)
    {
    }

    /**
     * Called at the beginning of the email download process. If it returns true, the
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
    public function preEmailDownload($prj_id, $info, $mbox, $num, &$message, $email, $structure = null)
    {
        return null;
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
    public function preNoteInsert($prj_id, $issue_id, &$data)
    {
        return null;
    }

    /**
     * Indicates if the email addresses should automatically be added to the NL from notes and emails.
     *
     * @param   integer $prj_id The project ID.
     * @return  boolean
     */
    public function shouldAutoAddToNotificationList($prj_id)
    {
        return true;
    }

    /**
     * Returns the issue ID to associate a new email with, null to use the default logic and "new" to create
     * a new issue.
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
     * @return int
     */
    public function getIssueIDforNewEmail($prj_id, $info, $headers, $message_body, $date, $from, $subject, $to, $cc)
    {
        return null;
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
    public function modifyMailQueue($prj_id, &$recipient, &$headers, &$body, $issue_id, $type, $sender_usr_id, $type_id)
    {
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
    public function preStatusChange($prj_id, &$issue_id, &$status_id, &$notify)
    {
        return true;
    }

    /**
     * Called at the start of many pages. After the includes and maybe some other code this
     * method is called to do whatever you want. Eventually this will be called on many pages.
     *
     * @param   integer $prj_id The project ID
     * @param   string $page_name The name of the page
     * @return  null
     */
    public function prePage($prj_id, $page_name)
    {
        return null;
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
    public function getNotificationActions($prj_id, $issue_id, $email, $source)
    {
        return null;
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
    public function getIssueFieldsToDisplay($prj_id, $issue_id, $location)
    {
        return array();
    }

    /**
     * Returns an array of patterns and replacements.
     *
     * @param   integer $prj_id The ID of the project
     * @return  array An array of patterns and replacements
     */
    public function getLinkFilters($prj_id)
    {
        return array();
    }

    /**
     * Returns if a user can update an issue. Return null to use default rules.
     */
    public function canUpdateIssue($prj_id, $issue_id, $usr_id)
    {
        return null;
    }

    /**
     * Returns if a user can change the assignee of an issue. Return null to use default rules.
     */
    public static function canChangeAssignee($prj_id, $issue_id, $usr_id)
    {
        return null;
    }

    /**
     * Returns if a user can clone an issue. Return null to use default rules.
     */
    public function canCloneIssue($prj_id, $issue_id, $usr_id)
    {
        return null;
    }

    /**
     * Returns if a user can change the security settings of an issue. Return null to use default rules.
     */
    public function canChangeAccessLevel($prj_id, $issue_id, $usr_id)
    {
        return null;
    }

    /**
     * Returns the ID of the group that is "active" right now.
     */
    public function getActiveGroup($prj_id)
    {
        return null;
    }

    public static function formatIRCMessage($prj_id, $notice, $issue_id = false, $usr_id = false, $category = false,
                                            $type = false)
    {
        return $notice;
    }

    /**
     * Returns an array of additional access levels an issue can be set to
     *
     * @param $prj_id
     * @return array
     */
    public static function getAccessLevels($prj_id)
    {
        return array();
    }

    /**
     * Performs additional checks on if a user can access an issue.
     *
     * @param $prj_id
     * @param $issue_id
     * @param $usr_id
     * @return mixed null to use default rules, true or false otherwise
     */
    public static function canAccessIssue($prj_id, $issue_id, $usr_id)
    {
        return null;
    }

    /**
     * Returns custom SQL to limit what results a user can see on the list issues page
     *
     * @param $prj_id
     * @param $usr_id
     * @return mixed null to use default rules or an sql string otherwise
     */
    public static function getAdditionalAccessSQL($prj_id, $usr_id)
    {
        return null;
    }
}
