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
use Eventum\Config\Paths;
use Eventum\Db\Doctrine;
use Eventum\Event\EventContext;
use Eventum\Event\ResultableEvent;
use Eventum\Event\SystemEvents;
use Eventum\EventDispatcher\EventManager;
use Eventum\Extension\ExtensionLoader;
use Eventum\LinkFilter\LinkFilter;
use Eventum\Mail\Helper\AddressHeader;
use Eventum\Mail\ImapMessage;
use Eventum\Mail\MailMessage;
use Eventum\ServiceContainer;
use Laminas\Mail\Address;

/**
 * @deprecated workflow backend concept is deprecated, use event subscribers
 */
class Workflow
{
    /**
     * Is called when an issue is updated.
     *
     * @param   int $prj_id the project ID
     * @param   int $issue_id the ID of the issue
     * @param   int $usr_id the ID of the user
     * @param   array $old_details the old details of the issues
     * @param   array $raw_post The changes that were applied to this issue (the $_POST)
     * @param array $updated_fields
     * @param array $updated_custom_fields
     * @since 3.5.0 emits ISSUE_UPDATED event
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     * @since 3.8.13 emits EventContext event
     * @since 3.8.17 Partner integration is done by PartnerLegacyExtension
     */
    public static function handleIssueUpdated(int $prj_id, int $issue_id, int $usr_id, $old_details, $raw_post, $updated_fields, $updated_custom_fields): void
    {
        $arguments = [
            'issue_details' => Issue::getDetails($issue_id, true),
            'updated_fields' => $updated_fields,
            'updated_custom_fields' => $updated_custom_fields,
            'old_details' => $old_details,
            'raw_post' => $raw_post,
        ];
        $event = new EventContext($prj_id, $issue_id, $usr_id, $arguments);
        EventManager::dispatch(SystemEvents::ISSUE_UPDATED, $event);
    }

    /**
     * Called before an issue is updated.
     *
     * @param   int $prj_id The project ID
     * @param   int $issue_id The ID of the issue
     * @param   int $usr_id the ID of the user changing the issue
     * @param   array $changes
     * @return  mixed. True to continue, anything else to cancel the change and return the value
     * @since 3.5.0 emits ISSUE_CREATED_BEFORE event
     * @since 3.8.13 can use stopPropagation() to cancel event
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     * @since 3.8.13 emits EventContext event
     */
    public static function preIssueUpdated(int $prj_id, int $issue_id, int $usr_id, &$changes, $issue_details)
    {
        $arguments = [
            'issue_details' => $issue_details,
            'changes' => $changes,
            // 'true' to continue, anything else to cancel the change and return the value
            // @deprecated since 3.8.13, use stopPropagation() to cancel
            'bubble' => true,
        ];

        $event = new EventContext($prj_id, $issue_id, $usr_id, $arguments);
        EventManager::dispatch(SystemEvents::ISSUE_UPDATED_BEFORE, $event);

        if ($event['bubble'] !== true) {
            return $event['bubble'];
        }

        if ($event->isPropagationStopped()) {
            return false;
        }

        return true;
    }

    /**
     * Called when a file is attached to an issue..
     *
     * @param   int $prj_id The project ID
     * @param   int $issue_id the ID of the issue
     * @param   int $usr_id the id of the user who attached this file
     * @param   AttachmentGroup $attachment_group The attachment object
     * @since 3.8.13 Emits ATTACHMENT_ATTACHMENT_GROUP event
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     */
    public static function handleAttachment(int $prj_id, int $issue_id, int $usr_id, AttachmentGroup $attachment_group): void
    {
        $event = new EventContext($prj_id, $issue_id, $usr_id, [], $attachment_group);
        EventManager::dispatch(SystemEvents::ATTACHMENT_ATTACHMENT_GROUP, $event);
    }

    /**
     * Determines if the attachment should be added
     *
     * @param   int $prj_id the project ID
     * @param   int $issue_id the ID of the issue
     * @param   int $usr_id The id of the user who attached the file
     * @param   array $attachment attachment object
     * @return  bool
     * @since 3.6.3 emits ATTACHMENT_ATTACH_FILE event
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     */
    public static function shouldAttachFile(int $prj_id, int $issue_id, $usr_id, array $attachment): bool
    {
        $event = new ResultableEvent($prj_id, $issue_id, $usr_id, [], $attachment);
        $event->setResult(true);
        EventManager::dispatch(SystemEvents::ATTACHMENT_ATTACH_FILE, $event);

        return $event->getResult();
    }

    /**
     * Called when the priority of an issue changes.
     *
     * @param   int $prj_id The project ID
     * @param   int $issue_id the ID of the issue
     * @param   int $usr_id the id of the user who changed the issue
     * @param   array $old_details the old details of the issue
     * @param   array $changes The changes that were applied to this issue (the $_POST)
     * @since 3.8.13 emits ISSUE_UPDATED_PRIORITY event
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     */
    public static function handlePriorityChange(int $prj_id, int $issue_id, int $usr_id, array $old_details, array $changes): void
    {
        $arguments = [
            'old_details' => $old_details,
            'changes' => $changes,
        ];
        $event = new EventContext($prj_id, $issue_id, $usr_id, $arguments);
        EventManager::dispatch(SystemEvents::ISSUE_UPDATED_PRIORITY, $event);
    }

    /**
     * Called when the severity of an issue changes.
     *
     * @param   int $prj_id The project ID
     * @param   int $issue_id the ID of the issue
     * @param   int $usr_id the id of the user who changed the issue
     * @param   array $old_details the old details of the issue
     * @param   array $changes The changes that were applied to this issue (the $_POST)
     * @since 3.8.13 emits ISSUE_UPDATED_SEVERITY event
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     */
    public static function handleSeverityChange(int $prj_id, int $issue_id, int $usr_id, array $old_details, array $changes): void
    {
        $arguments = [
            'old_details' => $old_details,
            'changes' => $changes,
        ];
        $event = new EventContext($prj_id, $issue_id, $usr_id, $arguments);
        EventManager::dispatch(SystemEvents::ISSUE_UPDATED_SEVERITY, $event);
    }

    /**
     * Called when an email is blocked.
     *
     * @param   int $prj_id The project ID
     * @param   int $issue_id the ID of the issue
     * @param   array $email_details Details of the issue
     * @param   string $type what type of blocked email this is
     * @param MailMessage $mail
     * @since 3.4.2 emits BLOCKED_EMAIL event
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     * @since 3.8.13 $mail is passed as $subject to an event
     * @since 3.8.13 emits EventContext event
     */
    public static function handleBlockedEmail(int $prj_id, int $issue_id, array $email_details, string $type, MailMessage $mail): void
    {
        $arguments = [
            'email_details' => $email_details,
            'type' => $type,
            'mail' => $mail,
        ];
        $event = new EventContext($prj_id, $issue_id, null, $arguments, $mail);
        EventManager::dispatch(SystemEvents::EMAIL_BLOCKED, $event);
    }

    /**
     * Called when the assignment on an issue changes.
     *
     * @param   int $prj_id The project ID
     * @param   int $issue_id the ID of the issue
     * @param   int $usr_id the id of the user who assigned the issue
     * @param   array $issue_details the old details of the issue
     * @param   array $new_assignees the new assignees of this issue
     * @param   bool $remote_assignment if this issue was remotely assigned
     * @since 3.4.2 emits ISSUE_ASSIGNMENT_CHANGE event
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     * @since 3.8.13 emits EventContext event
     */
    public static function handleAssignmentChange(int $prj_id, int $issue_id, int $usr_id, array $issue_details, $new_assignees, bool $remote_assignment = false): void
    {
        $arguments = [
            'issue_details' => $issue_details,
            'new_assignees' => $new_assignees ?: [],
            'remote_assignment' => $remote_assignment,
        ];

        $event = new EventContext($prj_id, $issue_id, $usr_id, $arguments);
        EventManager::dispatch(SystemEvents::ISSUE_ASSIGNMENT_CHANGE, $event);
    }

    /**
     * Called when a new issue is created.
     *
     * @param   int $prj_id The project ID
     * @param   int $issue_id the ID of the issue
     * @param   bool $has_TAM if this issue has a technical account manager
     * @param   bool $has_RR if Round Robin was used to assign this issue
     * @since 3.5.0 emits ISSUE_CREATED event
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     * @since 3.8.13 emits EventContext event
     */
    public static function handleNewIssue(int $prj_id, int $issue_id, bool $has_TAM, bool $has_RR): void
    {
        $usr_id = Auth::getUserID() ?: Setup::getSystemUserId();
        $arguments = [
            'has_TAM' => $has_TAM,
            'has_RR' => $has_RR,
            'issue_details' => Issue::getDetails($issue_id),
        ];
        $event = new EventContext($prj_id, $issue_id, $usr_id, $arguments);

        EventManager::dispatch(SystemEvents::ISSUE_CREATED, $event);
    }

    /**
     * Called when an email is received.
     *
     * @param   int $prj_id The project ID
     * @param   int $issue_id the ID of the issue
     * @param   MailMessage $mail The Mail object
     * @param   array $row the array of data that was inserted into the database
     * @param   bool $closing if we are closing the issue
     * @see Support::moveEmail
     * @see Support::insertEmail
     * @since 3.4.2 emits MAIL_PENDING event
     * @since 3.7.0 adds 'issue' argument to event
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     * @since 3.8.13 emits EventContext event
     * @since 3.8.17 Partner integration is done by PartnerLegacyExtension
     */
    public static function handleNewEmail(int $prj_id, int $issue_id, MailMessage $mail, array $row, bool $closing = false): void
    {
        // there are more variable options in $row
        // add just useful ones for event handler
        $arguments = [
            'issue' => Doctrine::getIssueRepository()->findById($issue_id),
            'closing' => $closing,
            'customer_id' => $row['customer_id'] ?? null,
            'contact_id' => $row['contact_id'] ?? null,
            'ema_id' => $row['ema_id'] ?? null,
            'sup_id' => $row['sup_id'] ?? null,
            'should_create_issue' => $row['should_create_issue'] ?? null,
            'data' => $row,
        ];

        if (empty($row['issue_id'])) {
            $event = new EventContext($prj_id, $issue_id, null, $arguments, $mail);
            EventManager::dispatch(SystemEvents::MAIL_PENDING, $event);
        }

        $event = new EventContext($prj_id, $issue_id, null, $arguments, $mail);
        EventManager::dispatch(SystemEvents::MAIL_CREATED, $event);
    }

    /**
     * Called when an email is manually associated with an existing issue.
     *
     * @param   int $prj_id The project ID
     * @param   int $issue_id the ID of the issue
     * @since 3.8.13 emits MAIL_ASSOCIATED_MANUAL event
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     * @since 3.8.13 adds $target and $sup_ids to make method actually useful
     */
    public static function handleManualEmailAssociation(int $prj_id, int $issue_id, string $target, array $sup_ids): void
    {
        $arguments = [
            'target' => $target,
            'sup_ids' => $sup_ids,
        ];
        $event = new EventContext($prj_id, $issue_id, null, $arguments);
        EventManager::dispatch(SystemEvents::MAIL_ASSOCIATED_MANUAL, $event);
    }

    /**
     * Called when a note is routed.
     *
     * @param   int $prj_id The project ID
     * @param   int $issue_id the ID of the issue
     * @param   int $usr_id The user ID of the person posting this new note
     * @param   bool $closing If the issue is being closed
     * @param   int $note_id The ID of the new note
     * @since 3.5.0 emits NOTE_CREATED event
     * @since 3.7.0 adds 'issue' argument to event
     * @since 3.8.13 emits EventContext event
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     * @since 3.8.17 Partner integration is done by PartnerLegacyExtension
     */
    public static function handleNewNote(int $prj_id, int $issue_id, int $usr_id, bool $closing, int $note_id): void
    {
        $arguments = [
            'issue' => Doctrine::getIssueRepository()->findById($issue_id),
            'note_id' => $note_id,
            'note_details' => Note::getDetails($note_id),
            'closing' => $closing,
        ];
        $event = new EventContext($prj_id, $issue_id, $usr_id, $arguments);
        EventManager::dispatch(SystemEvents::NOTE_CREATED, $event);
    }

    /**
     * Method is called to return the list of statuses valid for a specific issue.
     *
     * @param   int $prj_id The project ID
     * @param   int $issue_id the ID of the issue
     * @return  array an associative array of statuses valid for this issue
     * @since 3.6.3 emits ISSUE_ALLOWED_STATUSES event
     * @since 3.6.4 adds Status::getAssocStatusList as Subject
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     */
    public static function getAllowedStatuses(int $prj_id, ?int $issue_id = null): array
    {
        $statusList = Status::getAssocStatusList($prj_id, false);
        $event = new ResultableEvent($prj_id, $issue_id, null, [], $statusList);
        EventManager::dispatch(SystemEvents::ISSUE_ALLOWED_STATUSES, $event);
        if ($event->hasResult()) {
            return $event->getResult();
        }

        return $statusList;
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
     * @since 3.4.2 emits ISSUE_CLOSED event
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     * @since 3.8.13 emits EventContext event
     */
    public static function handleIssueClosed(int $prj_id, int $issue_id, $send_notification, int $resolution_id, int $status_id, $reason, int $usr_id): void
    {
        $issue_details = Issue::getDetails($issue_id, true);

        $arguments = [
            'send_notification' => $send_notification,
            'resolution_id' => $resolution_id,
            'status_id' => $status_id,
            'reason' => $reason,
            'issue_details' => $issue_details,
        ];

        $event = new EventContext($prj_id, $issue_id, $usr_id, $arguments);
        EventManager::dispatch(SystemEvents::ISSUE_CLOSED, $event);
    }

    /**
     * Called when custom fields are updated
     *
     * @param   int $prj_id The project ID
     * @param   int $issue_id The ID of the issue
     * @param   array $old the custom fields before the update
     * @param   array $new the custom fields after the update
     * @param   array $changed an array containing what was changed
     * @since 3.8.13 emits CUSTOM_FIELDS_UPDATED event
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     */
    public static function handleCustomFieldsUpdated(int $prj_id, int $issue_id, array $old, array $new, array $changed): void
    {
        $arguments = [
            'old' => $old,
            'new' => $new,
            'changed' => $changed,
        ];
        $event = new EventContext($prj_id, $issue_id, null, $arguments);
        EventManager::dispatch(SystemEvents::CUSTOM_FIELDS_UPDATED, $event);
    }

    /**
     * Called when an attempt is made to add a user or email address to the
     * notification list.
     *
     * @param   int $prj_id The project ID
     * @param   int $issue_id the ID of the issue
     * @param   int|bool $subscriber_usr_id the ID of the user to subscribe if this is a real user (false otherwise)
     * @param   string $email the email address  to subscribe (if this is not a real user)
     * @param   array $actions the action types
     * @return  array|bool|null an array of information or true to continue unchanged or false to prevent the user from being added
     * @since 3.6.3 emits NOTIFICATION_HANDLE_SUBSCRIPTION event
     * @since 3.6.4 add 'address' property of type Address
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     */
    public static function handleSubscription(int $prj_id, int $issue_id, &$subscriber_usr_id, &$email, &$actions)
    {
        $arguments = [
            'subscriber_usr_id' => is_numeric($subscriber_usr_id) ? (int)$subscriber_usr_id : $subscriber_usr_id,
            'email' => $email, // @deprecated, use 'address' instead
            'address' => $email ? AddressHeader::fromString($email)->getAddress() : null,
            'actions' => $actions,
        ];

        $event = new ResultableEvent($prj_id, $issue_id, null, $arguments);
        EventManager::dispatch(SystemEvents::NOTIFICATION_HANDLE_SUBSCRIPTION, $event);

        // assign back, in case these were changed
        $subscriber_usr_id = $event['subscriber_usr_id'];
        $email = $event['email'];
        $actions = $event['actions'];

        if ($event->hasResult()) {
            return $event->getResult();
        }

        return null;
    }

    /**
     * Determines if the address should should be emailed.
     *
     * @param int $prj_id the project ID
     * @param string $email The email address to check
     * @param bool $issue_id
     * @param bool $type
     * @since 3.6.0 emits NOTIFICATION_NOTIFY_ADDRESS event
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     * @since 3.8.13 $subject is set to Address of $email
     * @return bool
     * @todo https://github.com/eventum/eventum/pull/438#issuecomment-452706697
     */
    public static function shouldEmailAddress(int $prj_id, string $email, ?int $issue_id = null, $type = false): bool
    {
        $address = AddressHeader::fromString($email)->getAddress();
        $arguments = [
            'address' => $address,
            'type' => $type ?: null,
        ];

        $event = new ResultableEvent($prj_id, $issue_id, null, $arguments, $address);
        EventManager::dispatch(SystemEvents::NOTIFICATION_NOTIFY_ADDRESS, $event);

        if ($event->hasResult()) {
            return $event->getResult();
        }

        return true;
    }

    /**
     * Returns additional email addresses that should be notified for a specific event..
     *
     * @param   int $prj_id the project ID
     * @param   int $issue_id the ID of the issue
     * @param   string $eventName The event to return additional email addresses for. Values "new_issue", "issue_updated" are supported.
     * @param   array $extra Extra information, contains different info depending on where it is called from
     * @return  array   an array of email addresses to be notified
     * @since 3.8.13 emits NOTIFICATION_NOTIFY_ADDRESS event
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     */
    public static function getAdditionalEmailAddresses(int $prj_id, int $issue_id, string $eventName, $extra = null): array
    {
        $arguments = [
            'address' => $eventName, // @deprecated. bogus key
            'eventName' => $eventName,
            'extra' => $extra ?: [],
        ];

        $event = new ResultableEvent($prj_id, $issue_id, null, $arguments);
        EventManager::dispatch(SystemEvents::NOTIFICATION_NOTIFY_ADDRESSES_EXTRA, $event);

        if ($event->hasResult()) {
            return $event->getResult();
        }

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
     * @since 3.8.13 emits ACCESS_ISSUE_EMAIL event
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     */
    public static function canEmailIssue(int $prj_id, int $issue_id, string $email): ?bool
    {
        $address = AddressHeader::fromString($email)->getAddress();
        $event = new ResultableEvent($prj_id, $issue_id, null, [], $address);
        EventManager::dispatch(SystemEvents::ACCESS_ISSUE_EMAIL, $event);
        if ($event->hasResult()) {
            return $event->getResult();
        }

        return null;
    }

    /**
     * Called to check if an email address that does not have an eventum account can send notes to an issue.
     *
     * @param   int $prj_id The project ID
     * @param   int $issue_id The issue ID
     * @param string $email The email address to check
     * @param MailMessage $mail
     * @return  bool True if the note should be added, false otherwise
     * @since 3.8.13 emits ACCESS_ISSUE_NOTE event
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     */
    public static function canSendNote(int $prj_id, int $issue_id, string $email, MailMessage $mail): bool
    {
        $address = AddressHeader::fromString($email)->getAddress();
        $arguments = [
            'mail' => $mail,
        ];
        $event = new ResultableEvent($prj_id, $issue_id, null, $arguments, $address);
        EventManager::dispatch(SystemEvents::ACCESS_ISSUE_NOTE, $event);
        if ($event->hasResult()) {
            return $event->getResult();
        }

        return false;
    }

    /**
     * Called to check if a user can clone an issue
     *
     * @param   int $prj_id The project ID
     * @param   int $issue_id The issue ID
     * @param   string $usr_id The ID of the user
     * @return  bool True if the issue can be cloned, false otherwise
     * @since 3.8.13 emits ACCESS_ISSUE_CLONE event
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     */
    public static function canCloneIssue(int $prj_id, int $issue_id, int $usr_id): ?bool
    {
        $event = new ResultableEvent($prj_id, $issue_id, $usr_id);
        EventManager::dispatch(SystemEvents::ACCESS_ISSUE_CLONE, $event);
        if ($event->hasResult()) {
            return $event->getResult();
        }

        return null;
    }

    /**
     * Called to check if a user is allowed to edit the security settings of an issue
     *
     * @param   int $prj_id The project ID
     * @param   int $issue_id The issue ID
     * @param   string $usr_id The ID of the user
     * @return  bool True if the issue can be cloned, false otherwise
     * @since 3.8.13 emits ACCESS_ISSUE_CHANGE_ACCESS event
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     */
    public static function canChangeAccessLevel(int $prj_id, int $issue_id, int $usr_id): ?bool
    {
        $event = new ResultableEvent($prj_id, $issue_id, $usr_id);
        EventManager::dispatch(SystemEvents::ACCESS_ISSUE_CHANGE_ACCESS, $event);
        if ($event->hasResult()) {
            return $event->getResult();
        }

        return null;
    }

    /**
     * Handles when an authorized replier is added
     *
     * @param   int $prj_id The project ID
     * @param   int $issue_id The ID of the issue
     * @param   string $email The email address added
     * @return  bool if returns false, cancel subscribing the user
     * @since 3.8.13 emits AUTHORIZED_REPLIER_ADD event
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     */
    public static function handleAuthorizedReplierAdded(int $prj_id, int $issue_id, &$email): ?bool
    {
        $address = AddressHeader::fromString($email)->getAddress();
        $event = new ResultableEvent($prj_id, $issue_id, null, [], $address);
        EventManager::dispatch(SystemEvents::AUTHORIZED_REPLIER_ADD, $event);

        // assign back, in case it was modified by event
        if (isset($event['email'])) {
            $email = $event['email'];
        }

        if ($event->hasResult()) {
            return $event->getResult();
        }

        return null;
    }

    /**
     * Called at the beginning of the email download process.
     * If false is returned, email should not processed further.
     *
     * @param   int $prj_id The project ID
     * @param   ImapMessage $mail The Imap Mail Message object
     * @return  mixed null by default, -1 if the rest of the email script should not be processed
     * @since 3.8.11 emits ISSUE_UPDATED_BEFORE event
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     */
    public static function preEmailDownload(int $prj_id, ImapMessage $mail): bool
    {
        $event = new EventContext($prj_id, null, null, [], $mail);
        EventManager::dispatch(SystemEvents::MAIL_PROCESS_BEFORE, $event);
        if ($event->isPropagationStopped()) {
            return false;
        }

        return true;
    }

    /**
     * Called before inserting a note. If it returns false the rest of the note code
     * will not be executed. Return null to continue as normal (possibly with changed $data)
     *
     * @param   int $prj_id
     * @param   int $issue_id
     * @param   array $data
     * @return  mixed   Null by default, false if the note should not be inserted
     * @since 3.8.13 emits NOTE_INSERT_BEFORE event
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     */
    public static function preNoteInsert(int $prj_id, int $issue_id, &$data): ?bool
    {
        $arguments = [
            'data' => $data,
        ];
        $event = new ResultableEvent($prj_id, $issue_id, null, $arguments);
        EventManager::dispatch(SystemEvents::NOTE_INSERT_BEFORE, $event);

        // assign back, in case it was changed
        $data = $event['data'];

        if ($event->hasResult()) {
            return $event->getResult();
        }

        return null;
    }

    /**
     * Indicates if the email addresses should automatically be added to the NL from notes and emails.
     *
     * TODO:
     * This should be probably moved to project settings as it has no context than project id.
     *
     * @param   int $prj_id the project ID
     * @return  bool
     * @since 3.8.13 emits PROJECT_NOTIFICATION_AUTO_ADD event
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     */
    public static function shouldAutoAddToNotificationList(int $prj_id): bool
    {
        $event = new ResultableEvent($prj_id, null, null);
        $event->setResult(true);
        EventManager::dispatch(SystemEvents::PROJECT_NOTIFICATION_AUTO_ADD, $event);

        return $event->getResult();
    }

    /**
     * Returns the issue ID to associate a new email with, null to use the default logic and "new" to create
     * a new issue.
     * Can also return an array containing 'customer_id', 'contact_id' and 'contract_id', 'sev_id'
     *
     * TODO:
     * - update caller so this method always returns array
     *
     * @param   int $prj_id The ID of the project
     * @param   array $account an array of info about the email account
     * @param   MailMessage $mail The Mail object
     * @return  string|array
     * @since 3.8.13 emits ISSUE_EMAIL_CREATE_OPTIONS event
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     */
    public static function getIssueIDForNewEmail(int $prj_id, array $account, MailMessage $mail)
    {
        $arguments = [
            'account' => $account,
        ];
        $event = new ResultableEvent($prj_id, null, null, $arguments, $mail);
        EventManager::dispatch(SystemEvents::ISSUE_EMAIL_CREATE_OPTIONS, $event);

        if ($event->hasResult()) {
            return $event->getResult();
        }

        return null;
    }

    /**
     * Modifies the content of the message being added to the mail queue.
     *
     * @param   int $prj_id
     * @param   string|Address $email
     * @param MailMessage $mail The Mail object
     * @param array $options Optional options, see Mail_Queue::queue
     * @since 3.3.0 the method signature changed
     * @since 3.8.13 emits MAIL_QUEUE_MODIFY event
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     */
    public static function modifyMailQueue(?int $prj_id, $email, MailMessage $mail, array $options): void
    {
        $address = $email instanceof Address ? $email : AddressHeader::fromString($email)->getAddress();
        $arguments = [
            'options' => $options,
            'address' => $address,
        ];
        $event = new EventContext($prj_id, null, null, $arguments, $mail);
        EventManager::dispatch(SystemEvents::MAIL_QUEUE_MODIFY, $event);
    }

    /**
     * Called before the issue status changes. Parameters are passed by reference so the values can be changed.
     *
     * @param   int $prj_id
     * @param   int $issue_id
     * @param   int $status_id
     * @param   bool $notify
     * @return  bool true to continue normal processing, anything else to cancel and return value
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     * @since 3.8.13 emits ISSUE_STATUS_BEFORE event
     */
    public static function preStatusChange(int $prj_id, int &$issue_id, int &$status_id, bool &$notify)
    {
        $arguments = [
            'status_id' => $status_id,
            'notify' => $notify,
        ];
        $event = new ResultableEvent($prj_id, $issue_id, null, $arguments);
        EventManager::dispatch(SystemEvents::ISSUE_STATUS_BEFORE, $event);

        // assign back, in case they were modified
        $issue_id = $event['issue_id'];
        $status_id = $event['status_id'];
        $notify = $event['notify'];

        if ($event->hasResult()) {
            return $event->getResult();
        }

        return true;
    }

    /**
     * Called at the start of many pages. After the includes and maybe some other code this
     * method is called to do whatever you want. Eventually this will be called on many pages.
     *
     * @param   int $prj_id The project ID
     * @param   string $page_name The name of the page
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     * @since 3.8.13 emits PAGE_BEFORE event
     */
    public static function prePage(int $prj_id, string $page_name): void
    {
        $event = new EventContext($prj_id, null, null, [], $page_name);
        EventManager::dispatch(SystemEvents::PAGE_BEFORE, $event);
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
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     * @since 3.8.13 emits NOTIFICATION_ACTIONS event
     */
    public static function getNotificationActions(?int $prj_id, ?int $issue_id, ?string $email, ?string $source): ?array
    {
        $arguments = [
            'email' => $email,
            'address' => $email ? AddressHeader::fromString($email)->getAddress() : null,
            'source' => $source,
        ];
        $event = new ResultableEvent($prj_id, $issue_id, null, $arguments);
        EventManager::dispatch(SystemEvents::NOTIFICATION_ACTIONS, $event);

        if ($event->hasResult()) {
            return $event->getResult();
        }

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
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     * @since 3.8.13 emits ISSUE_FIELDS_DISPLAY event
     */
    public static function getIssueFieldsToDisplay(int $prj_id, int $issue_id, string $location): array
    {
        $event = new ResultableEvent($prj_id, $issue_id, null, [], $location);
        EventManager::dispatch(SystemEvents::ISSUE_FIELDS_DISPLAY, $event);

        if ($event->hasResult()) {
            return $event->getResult();
        }

        return [];
    }

    /**
     * Updates filters in $linkFilter.
     *
     * @since 3.6.3 emits ISSUE_LINK_FILTERS event
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     * @since 3.8.13 emits EventContext event
     */
    public static function addLinkFilters(LinkFilter $linkFilter, int $prj_id): void
    {
        $event = new EventContext($prj_id, null, null, [], $linkFilter);
        EventManager::dispatch(SystemEvents::ISSUE_LINK_FILTERS, $event);
    }

    /**
     * Returns if a user can update an issue. Return null to use default rules.
     *
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     * @since 3.8.13 emits ACCESS_ISSUE_UPDATE event
     */
    public static function canUpdateIssue(int $prj_id, int $issue_id, int $usr_id): ?bool
    {
        $event = new ResultableEvent($prj_id, $issue_id, $usr_id);
        EventManager::dispatch(SystemEvents::ACCESS_ISSUE_UPDATE, $event);

        if ($event->hasResult()) {
            return $event->getResult();
        }

        return null;
    }

    /**
     * Returns if a user can change the assignee of an issue. Return null to use default rules.
     *
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     * @since 3.8.13 emits ACCESS_ISSUE_CHANGE_ASSIGNEE event
     */
    public static function canChangeAssignee(int $prj_id, int $issue_id, int $usr_id): ?bool
    {
        $event = new ResultableEvent($prj_id, $issue_id, $usr_id);
        EventManager::dispatch(SystemEvents::ACCESS_ISSUE_CHANGE_ASSIGNEE, $event);

        if ($event->hasResult()) {
            return $event->getResult();
        }

        return null;
    }

    /**
     * Returns the ID of the group that is "active" right now.
     *
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     * @since 3.8.13 emits GROUP_ACTIVE event
     */
    public static function getActiveGroup(int $prj_id): ?int
    {
        $event = new ResultableEvent($prj_id, null, null);
        EventManager::dispatch(SystemEvents::GROUP_ACTIVE, $event);

        if ($event->hasResult()) {
            return $event->getResult();
        }

        return null;
    }

    /**
     * Returns an array of additional access levels an issue can be set to
     *
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     * @since 3.8.13 emits ACCESS_LEVELS event
     */
    public static function getAccessLevels(?int $prj_id): ?array
    {
        $event = new ResultableEvent($prj_id, null, null);
        EventManager::dispatch(SystemEvents::ACCESS_LEVELS, $event);

        if ($event->hasResult()) {
            return $event->getResult();
        }

        return null;
    }

    /**
     * Returns true if a user can access an issue.
     *
     * @since 3.8.11 emits ACCESS_ISSUE event
     * @since 3.8.11 workflow integration is done by WorkflowLegacyExtension
     */
    public static function canAccessIssue(int $prj_id, int $issue_id, int $usr_id, bool $return, bool $internal): bool
    {
        $arguments = [
            // if it's internal call. i.e called from canViewInternalNotes
            'internal' => $internal,
        ];
        $event = new ResultableEvent($prj_id, $issue_id, $usr_id, $arguments);
        $event->setResult($return);
        EventManager::dispatch(SystemEvents::ACCESS_ISSUE, $event);

        return $event->getResult();
    }

    /**
     * Returns custom SQL to limit what results a user can see on the list issues page
     *
     * @return mixed null to use default rules or an sql string otherwise
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     * @since 3.8.13 emits ACCESS_SQL_STATEMENT event
     */
    public static function getAdditionalAccessSQL(int $prj_id, int $usr_id): ?string
    {
        $event = new ResultableEvent($prj_id, null, $usr_id);
        EventManager::dispatch(SystemEvents::ACCESS_LISTING_SQL, $event);

        if ($event->hasResult()) {
            return $event->getResult();
        }

        return null;
    }

    /**
     * Called when an issue is moved from this project to another.
     *
     * @since 3.1.7
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     * @since 3.8.13 emits ISSUE_MOVE_FROM_PROJECT event
     */
    public static function handleIssueMovedFromProject(int $prj_id, int $issue_id, int $new_prj_id): void
    {
        $arguments = [
            'new_prj_id' => $new_prj_id,
        ];
        $event = new ResultableEvent($prj_id, $issue_id, null, $arguments);
        EventManager::dispatch(SystemEvents::ISSUE_MOVE_FROM_PROJECT, $event);
    }

    /**
     * Called when an issue is moved to this project from another.
     *
     * @since 3.1.7
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     * @since 3.8.13 emits ISSUE_MOVE_TO_PROJECT event
     */
    public static function handleIssueMovedToProject(int $prj_id, int $issue_id, int $old_prj_id): void
    {
        $arguments = [
            'old_prj_id' => $old_prj_id,
        ];
        $event = new ResultableEvent($prj_id, $issue_id, null, $arguments);
        EventManager::dispatch(SystemEvents::ISSUE_MOVE_TO_PROJECT, $event);
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
     * @since 3.8.13 workflow integration is done by WorkflowLegacyExtension
     * @since 3.8.13 emits ISSUE_MOVE_MAPPING event
     */
    public static function getMovedIssueMapping(int $prj_id, int $issue_id, array $mapping, int $old_prj_id): array
    {
        $arguments = [
            'old_prj_id' => $old_prj_id,
        ];
        $event = new ResultableEvent($prj_id, $issue_id, null, $arguments);
        $event->setResult($mapping);
        EventManager::dispatch(SystemEvents::ISSUE_MOVE_MAPPING, $event);

        return $event->getResult();
    }

    /**
     * @internal
     */
    public static function getExtensionLoader(): ExtensionLoader
    {
        $localPath = ServiceContainer::getConfig()['local_path'];

        $dirs = [
            Paths::APP_INC_PATH . '/workflow',
            $localPath . '/workflow',
        ];

        return new ExtensionLoader($dirs, '%s_Workflow_Backend');
    }
}
