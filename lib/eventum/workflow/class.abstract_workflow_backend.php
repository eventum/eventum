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

use Eventum\Attachment\AttachmentGroup;
use Eventum\Mail\ImapMessage;
use Eventum\Mail\MailMessage;
use Eventum\Model\Entity;

/**
 * Abstract Class that all workflow backends should extend. This is so any new
 * workflow methods added in future releases will not break current backends.
 */
abstract class Abstract_Workflow_Backend
{
    /**
     * Project Id this Workflow was created for.
     * The value is set by Eventum Core.
     *
     * @var int
     */
    public $prj_id;

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
            $setup['workflow'] = [];
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
        return [];
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
     * @param int $prj_id the project ID
     * @param int $issue_id the ID of the issue
     * @param int $usr_id the ID of the user
     * @param array $old_details the old details of the issues
     * @param array $changes The changes that were applied to this issue (the $_POST)
     */
    public function handleIssueUpdated($prj_id, $issue_id, $usr_id, $old_details, $changes)
    {
    }

    /**
     * Called before an issue is updated.
     *
     * @param   int $prj_id The project ID
     * @param   int $issue_id The ID of the issue
     * @param   int $usr_id the ID of the user changing the issue
     * @param   array $changes
     * @return  mixed. True to continue, anything else to cancel the change and return the value
     */
    public function preIssueUpdated($prj_id, $issue_id, $usr_id, &$changes)
    {
        return true;
    }

    /**
     * Called when a file is attached to an issue.
     *
     * @param   int $prj_id The projectID
     * @param   int $issue_id the ID of the issue
     * @param   int $usr_id the id of the user who attached this file
     * @param   AttachmentGroup $attachment_group The attachment group object
     */
    public function handleAttachment($prj_id, $issue_id, $usr_id, AttachmentGroup $attachment_group)
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
     * @param   int $prj_id the project ID
     * @param   int $issue_id the ID of the issue
     * @param   int $usr_id The id of the user who attached the file
     * @param   array $attachment attachment object
     * @return  bool
     */
    public function shouldAttachFile($prj_id, $issue_id, $usr_id, $attachment)
    {
        return true;
    }

    /**
     * Called when the priority of an issue changes.
     *
     * @param   int $prj_id The projectID
     * @param   int $issue_id the ID of the issue
     * @param   int $usr_id the id of the user who changed the issue
     * @param   array $old_details the old details of the issue
     * @param   array $changes The changes that were applied to this issue (the $_POST)
     */
    public function handlePriorityChange($prj_id, $issue_id, $usr_id, $old_details, $changes)
    {
    }

    /**
     * Called when the severity of an issue changes.
     *
     * @param   int $prj_id The projectID
     * @param   int $issue_id the ID of the issue
     * @param   int $usr_id the id of the user who changed the issue
     * @param   array $old_details the old details of the issue
     * @param   array $changes The changes that were applied to this issue (the $_POST)
     */
    public function handleSeverityChange($prj_id, $issue_id, $usr_id, $old_details, $changes)
    {
    }

    /**
     * Called when an email is blocked.
     *
     * @param   int $prj_id The projectID
     * @param   int $issue_id the ID of the issue
     * @param   array $email_details Details of the issue
     * @param   string $type what type of blocked email this is
     */
    public function handleBlockedEmail($prj_id, $issue_id, $email_details, $type)
    {
    }

    /**
     * Called when a note is routed.
     *
     * @param   int $prj_id The projectID
     * @param   int $issue_id the ID of the issue
     * @param   int $usr_id The user ID of the person posting this new note
     * @param   bool $closing If the issue is being closed
     * @param   int $note_id The ID of the new note
     */
    public function handleNewNote($prj_id, $issue_id, $usr_id, $closing, $note_id)
    {
    }

    /**
     * Called when the assignment on an issue changes.
     *
     * @param   int $prj_id The projectID
     * @param   int $issue_id the ID of the issue
     * @param   int $usr_id the id of the user who locked the issue
     * @param   array $issue_details the old details of the issue
     * @param   array $new_assignees the new assignees of this issue
     * @param   bool $remote_assignment if this issue was remotely assigned
     */
    public function handleAssignmentChange($prj_id, $issue_id, $usr_id, $issue_details, $new_assignees, $remote_assignment)
    {
    }

    /**
     * Called when a new issue is created.
     *
     * @param   int $prj_id The projectID
     * @param   int $issue_id the ID of the issue
     * @param   bool $has_TAM if this issue has a technical account manager
     * @param   bool $has_RR if Round Robin was used to assign this issue
     */
    public function handleNewIssue($prj_id, $issue_id, $has_TAM, $has_RR)
    {
    }

    /**
     * Called when an email is associated with an issue.
     *
     * @param   int $prj_id The projectID
     * @param   int $issue_id The issue ID
     */
    public function handleManualEmailAssociation($prj_id, $issue_id)
    {
    }

    /**
     * Called when an email is received.
     *
     * @param   int $prj_id The project ID
     * @param   int $issue_id the ID of the issue
     * @param   MailMessage $mail The Mail object
     * @param   array $row the array of data that was inserted into the database
     * @param   bool $closing if we are closing the issue
     * @since 3.4.0 uses new signature, see #263
     */
    public function handleNewEmail($prj_id, $issue_id, MailMessage $mail, $row, $closing = false)
    {
    }

    /**
     * Method is called to return the list of statuses valid for a specific issue.
     *
     * @param   int $prj_id The projectID
     * @param   int $issue_id the ID of the issue
     * @return  array an associative array of statuses valid for this issue
     */
    public function getAllowedStatuses($prj_id, $issue_id)
    {
        return Status::getAssocStatusList($prj_id, false);
    }

    /**
     * Called when issue is closed.
     *
     * @param   int $prj_id The project ID
     * @param   int $issue_id the ID of the issue
     * @param   bool $send_notification Whether to send a notification about this action or not
     * @param   int $resolution_id The resolution ID
     * @param   int $status_id The status ID
     * @param   string $reason The reason for closing this issue
     * @param   int $usr_id The ID of the user closing this issue
     */
    public function handleIssueClosed($prj_id, $issue_id, $send_notification, $resolution_id, $status_id, $reason, $usr_id)
    {
    }

    /**
     * Called when custom fields are updated
     *
     * @param   int $prj_id The project ID
     * @param   int $issue_id The ID of the issue
     * @param   array $old the custom fields before the update
     * @param   array $new the custom fields after the update
     * @param   array $changed an array containing what was changed
     */
    public function handleCustomFieldsUpdated($prj_id, $issue_id, $old, $new, $changed)
    {
    }

    /**
     * Called when an attempt is made to add a user or email address to the
     * notification list.
     *
     * @param   int $prj_id The project ID
     * @param   int $issue_id the ID of the issue
     * @param   int $subscriber_usr_id the ID of the user to subscribe if this is a real user (false otherwise)
     * @param   string $email the email address to subscribe to subscribe (if this is not a real user)
     * @param   array $actions the action types
     * @return  array|bool an array of information or true to continue unchanged or false to prevent the user from being added
     */
    public function handleSubscription($prj_id, $issue_id, &$subscriber_usr_id, &$email, &$actions)
    {
        return true;
    }

    /**
     * Method called on Commit to allow workflow update project name/commit author or user id
     *
     * @param int $prj_id the project ID
     * @param Entity\Commit $commit
     * @param mixed $payload
     * @since 3.1.0
     */
    public function preScmCommit($prj_id, Entity\Commit $commit, $payload)
    {
    }

    /**
     * Handle commit associated to issue
     *
     * @param int $prj_id the project ID
     * @param int $issue_id the ID of the issue
     * @param Entity\Commit $commit
     * @since 3.0.12
     */
    public function handleScmCommit($prj_id, $issue_id, Entity\Commit $commit)
    {
    }

    /**
     * Determines if the address should be emailed.
     *
     * @param   int $prj_id The project ID
     * @param   string $address The email address to check
     * @param   int $issue_id the ID of the issue
     * @param   string $type the type of notification to send
     * @return  bool
     */
    public function shouldEmailAddress($prj_id, $address, $issue_id = null, $type = null)
    {
        return true;
    }

    /**
     * Returns additional email addresses that should be notified for a specific event..
     *
     * @param    int $prj_id the project ID
     * @param    int $issue_id the ID of the issue
     * @param    string $event The event to return additional email addresses for. Currently only "new_issue" is supported.
     * @param   array $extra Extra information, contains different info depending on where it is called from
     * @return   array   an array of email addresses to be notified
     */
    public function getAdditionalEmailAddresses($prj_id, $issue_id, $event, $extra)
    {
        return [];
    }

    /**
     * Indicates if the the specified email address can email the issue. Can be
     * used to disable email blocking by always returning true.
     *
     * @param   int $prj_id the project ID
     * @param   int $issue_id The ID of the issue
     * @param   string $email The email address that is trying to send an email
     * @return  bool true if the sender can email the issue, false if the sender
     *          should not email the issue and null if the default rules should be used
     */
    public function canEmailIssue($prj_id, $issue_id, $email)
    {
        return null;
    }

    /**
     * Called to check if an email address that does not have an eventum account can send notes to an issue.
     *
     * @param int $prj_id The project ID
     * @param int $issue_id The issue ID
     * @param string $sender_email The email address to check
     * @param MailMessage $mail
     * @return bool True if the note should be added, false otherwise
     * @since 3.4.0 uses new signature, see #263
     */
    public function canSendNote($prj_id, $issue_id, $sender_email, MailMessage $mail)
    {
        return null;
    }

    /**
     * Handles when an authorized replier is added
     *
     * @param   int $prj_id The project ID
     * @param   int $issue_id The ID of the issue
     * @param   string $email The email address added
     * @return  bool
     */
    public function handleAuthorizedReplierAdded($prj_id, $issue_id, $email)
    {
    }

    /**
     * Called at the beginning of the email download process. If it returns true, the
     * rest of the email code will not be executed.
     *
     * @param   int $prj_id The project ID
     * @param   ImapMessage $mail The Imap Mail Message object
     * @return  mixed null by default, -1 if the rest of the email script should not be processed
     * @since 3.4.0 uses new signature, see #263
     */
    public function preEmailDownload($prj_id, ImapMessage $mail)
    {
        return null;
    }

    /**
     * Called before inserting a note. If it returns false the rest of the note code
     * will not be executed. Return null to continue as normal (possibly with changed $data)
     *
     * @param   int $prj_id
     * @param   int $issue_id
     * @param   array $data
     * @return  mixed   Null by default, false if the note should not be inserted
     */
    public function preNoteInsert($prj_id, $issue_id, &$data)
    {
        return null;
    }

    /**
     * Indicates if the email addresses should automatically be added to the NL from notes and emails.
     *
     * @param   int $prj_id the project ID
     * @return  bool
     */
    public function shouldAutoAddToNotificationList($prj_id)
    {
        return true;
    }

    /**
     * Returns the issue ID to associate a new email with, null to use the default logic and "new" to create
     * a new issue.
     *
     * @param   int $prj_id The ID of the project
     * @param   array $info an array of info about the email account
     * @param   MailMessage $mail The Mail object
     * @return int
     * @since 3.4.0 uses new signature, see #263
     */
    public function getIssueIDforNewEmail($prj_id, $info, MailMessage $mail)
    {
        return null;
    }

    /**
     * Modifies the content of the message being added to the mail queue.
     *
     * @param   int $prj_id
     * @param   string $recipient
     * @param MailMessage $mail The Mail object
     * @param array $options Optional options, see Mail_Queue::queue
     * @since 3.4.0 uses new signature, see #263
     */
    public function modifyMailQueue($prj_id, $recipient, MailMessage $mail, $options)
    {
    }

    /**
     * Called before the status changes. Parameters are passed by reference so the values can be changed.
     *
     * @param   int $prj_id
     * @param   int $issue_id
     * @param   int $status_id
     * @param   bool $notify
     * @return  bool true to continue normal processing, anything else to cancel and return value
     */
    public function preStatusChange($prj_id, &$issue_id, &$status_id, &$notify)
    {
        return true;
    }

    /**
     * Called at the start of many pages. After the includes and maybe some other code this
     * method is called to do whatever you want. Eventually this will be called on many pages.
     *
     * @param   int $prj_id The project ID
     * @param   string $page_name The name of the page
     */
    public function prePage($prj_id, $page_name)
    {
        return null;
    }

    /**
     * Called to determine which actions to subscribe a new user too.
     *
     * @see     Notification::getDefaultActions()
     * @param   int $prj_id The project ID
     * @param   int $issue_id The ID of the issue
     * @param   string $email The email address of the user being added
     * @param   string $source The source of this call
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
     * @param   int $prj_id The project ID
     * @param   int $issue_id The ID of the issue
     * @param   string $location The location to display these fields at
     * @return  array   an array of fields to display and their associated options
     */
    public function getIssueFieldsToDisplay($prj_id, $issue_id, $location)
    {
        return [];
    }

    /**
     * Returns an array of patterns and replacements.
     *
     * @param   int $prj_id The ID of the project
     * @return  array An array of patterns and replacements
     */
    public function getLinkFilters($prj_id)
    {
        return [];
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
    public function canChangeAssignee($prj_id, $issue_id, $usr_id)
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

    public function formatIRCMessage($prj_id, $notice, $issue_id = false, $usr_id = false, $category = false,
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
    public function getAccessLevels($prj_id)
    {
        return [];
    }

    /**
     * Performs additional checks on if a user can access an issue.
     *
     * @param $prj_id
     * @param $issue_id
     * @param $usr_id
     * @return mixed null to use default rules, true or false otherwise
     */
    public function canAccessIssue($prj_id, $issue_id, $usr_id)
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
    public function getAdditionalAccessSQL($prj_id, $usr_id)
    {
        return null;
    }

    /**
     * Upgrade config so that values contain EncryptedValue where some secrecy is wanted
     *
     * @see \Eventum\Crypto\CryptoUpgradeManager::upgradeConfig
     * @since 3.1.0
     */
    public function cryptoUpgradeConfig()
    {
    }

    /**
     * Downgrade config: remove all EncryptedValue elements
     *
     * @see \Eventum\Crypto\CryptoUpgradeManager::downgradeConfig
     *
     * @since 3.1.0
     */
    public function cryptoDowngradeConfig()
    {
    }

    /**
     * Called when an issue is moved from this project to another.
     *
     * @param $prj_id integer
     * @param $issue_id integer
     * @param $new_prj_id integer
     * @since 3.1.7
     */
    public function handleIssueMovedFromProject($prj_id, $issue_id, $new_prj_id)
    {
    }

    /**
     * Called when an issue is moved to this project from another.
     *
     * @param $prj_id integer
     * @param $issue_id integer
     * @param $old_prj_id integer
     * @since 3.1.7
     */
    public function handleIssueMovedToProject($prj_id, $issue_id, $old_prj_id)
    {
    }

    /**
     * Returns fields to be updated when an issue is moved from one project to another.
     *
     * @param $prj_id integer The ID of the project the issue is being moved to
     * @param $issue_id integer
     * @param $mapping array a key/value array containing default mappings
     * @param $old_prj_id integer The ID of the project the issue is being moved from
     * @return array A key/value array with the keys being field names in the issue table
     * @since 3.1.7
     */
    public function getMovedIssueMapping($prj_id, $issue_id, $mapping, $old_prj_id)
    {
        return $mapping;
    }
}
