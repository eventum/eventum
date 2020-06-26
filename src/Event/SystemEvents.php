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

namespace Eventum\Event;

use Eventum\Event\Subscriber\MailQueueListener;
use Eventum\Model\Repository\CommitRepository;

final class SystemEvents
{
    /**
     * @since 3.6.3
     * @see Workflow::shouldAttachFile()
     */
    public const ATTACHMENT_ATTACH_FILE = 'attachment.attach.file';

    /**
     * @since 3.8.13
     * @see Workflow::handleAttachment
     */
    public const ATTACHMENT_ATTACHMENT_GROUP = 'attachment.attach.group';

    /**
     * Event fired when history entry is added
     *
     * @since 3.3.0
     * @since 3.4.0 uses GenericEvent
     */
    public const HISTORY_ADD = 'history.add';

    /**
     * @since 3.3.0
     * @since 3.4.0 uses GenericEvent
     */
    public const USER_CREATE = 'user.create';

    /**
     * @since 3.3.0
     * @since 3.4.0 uses GenericEvent
     */
    public const USER_UPDATE = 'user.update';

    /**
     * Upgrade config so that values contain EncryptedValue where some secrecy is wanted
     *
     * @since 3.5.0
     */
    public const CONFIG_CRYPTO_UPGRADE = 'config.crypto.upgrade';

    /**
     * Downgrade config: remove all EncryptedValue elements.
     *
     * @since 3.5.0
     */
    public const CONFIG_CRYPTO_DOWNGRADE = 'config.crypto.downgrade';

    /**
     * Fired prior saving config to disk.
     *
     * @since 3.9.0
     */
    public const CONFIG_SAVE = 'config.save';

    /**
     * @since 3.4.0
     * @see CommitRepository::preCommit
     */
    public const SCM_COMMIT_BEFORE = 'scm.commit.before';

    /**
     * Commit is associated to an issue
     *
     * @since 3.4.0
     * @see CommitRepository::addIssues
     */
    public const SCM_COMMIT_ASSOCIATED = 'scm.commit.associated';

    /**
     * Event Fired when MailMessage was created from IMAP Connection.
     *
     * @since 3.4.0
     * @see ImapMessage::createFromImapResource
     */
    public const MAIL_LOADED_IMAP = 'mail.loaded.imap';

    /**
     * @since 3.8.11
     * @see Workflow::preEmailDownload
     */
    public const MAIL_PROCESS_BEFORE = 'mail.process.before';

    /**
     * @since 3.4.0
     * @see Mail_Queue::send()
     * @see MailQueueListener
     */
    public const MAIL_QUEUE_SEND = 'mail.queue.send';
    public const MAIL_QUEUE_SENT = 'mail.queue.sent';
    public const MAIL_QUEUE_ERROR = 'mail.queue.error';

    /**
     * @since 3.8.14
     * @see Routing::route_emails
     */
    public const MAIL_ROUTE_EMAIL = 'mail.route.email';

    /**
     * @since 3.8.14
     * @see Routing::route_notes
     */
    public const MAIL_ROUTE_NOTE = 'mail.route.note';
    /**
     * @since 3.8.14
     * @see Routing::route_drafts
     */
    public const MAIL_ROUTE_DRAFT = 'mail.route.draft';

    /**
     * @since 3.8.13
     * @see Workflow::modifyMailQueue
     */
    public const MAIL_QUEUE_MODIFY = 'mail.queue.modify';

    /**
     * @since 3.4.2
     * @see Workflow::handleNewEmail()
     */
    public const MAIL_PENDING = 'mail.pending';

    /**
     * @since 3.4.2
     * @see Workflow::handleNewEmail()
     */
    public const MAIL_CREATED = 'mail.created';

    /**
     * @since 3.8.13
     * @see Workflow::handleManualEmailAssociation
     */
    public const MAIL_ASSOCIATED_MANUAL = 'mail.associated.manual';

    /**
     * @since 3.8.13
     * @see Workflow::handleCustomFieldsUpdated()
     */
    public const CUSTOM_FIELDS_UPDATED = 'custom_field.updated';

    /**
     * @since 3.6.0
     * @see Workflow::shouldEmailAddress()
     */
    public const NOTIFICATION_NOTIFY_ADDRESS = 'notification.notify.address';

    /**
     * @since 3.8.13
     * @see Workflow::getAdditionalEmailAddresses()
     */
    public const NOTIFICATION_NOTIFY_ADDRESSES_EXTRA = 'notification.notify.addresses.extra';

    /**
     * @since 3.6.3
     * @see Workflow::handleSubscription()
     */
    public const NOTIFICATION_HANDLE_SUBSCRIPTION = 'notification.handle.subscription';

    /**
     * @since 3.8.13
     * @see Workflow::shouldAutoAddToNotificationList
     */
    public const PROJECT_NOTIFICATION_AUTO_ADD = 'project.notification.auto_add';

    /**
     * @since 3.8.13
     * @see Workflow::getNotificationActions
     */
    public const NOTIFICATION_ACTIONS = 'notitication.actions';

    /**
     * @since 3.4.2
     */
    public const IRC_NOTIFY = 'irc.notify';

    /**
     * @since 3.4.2
     * @see Workflow::handleBlockedEmail()
     */
    public const EMAIL_BLOCKED = 'email.blocked';

    /**
     * @since 3.4.2
     * @see Workflow::handleAssignmentChange()
     */
    public const ISSUE_ASSIGNMENT_CHANGE = 'issue.assignment_change';

    /**
     * @since 3.5.0
     * @see Workflow::preIssueUpdated
     */
    public const ISSUE_UPDATED_BEFORE = 'issue.updated.before';

    /**
     * @since 3.8.13
     * @see Workflow::handlePriorityChange
     */
    public const ISSUE_UPDATED_PRIORITY = 'issue.updated.priority';

    /**
     * @since 3.8.13
     * @see Workflow::handlePriorityChange
     */
    public const ISSUE_UPDATED_SEVERITY = 'issue.updated.severity';

    /**
     * @since 3.6.0
     * @see Issue::markAsDuplicate
     */
    public const ISSUE_MARK_DUPLICATE = 'issue.mark_duplicate';

    /**
     * @since 3.5.0
     * @see Workflow::handleNewIssue
     */
    public const ISSUE_CREATED = 'issue.created';

    /**
     * @since 3.4.2
     * @see Workflow::handleIssueClosed()
     */
    public const ISSUE_CLOSED = 'issue.closed';

    /**
     * @since 3.5.0
     * @see Workflow::handleIssueUpdated()
     */
    public const ISSUE_UPDATED = 'issue.updated';

    /**
     * @since 3.5.0
     * @see Workflow::getIssueIDForNewEmail()
     */
    public const ISSUE_EMAIL_CREATE_OPTIONS = 'issue.email.create.options';

    /**
     * @since 3.5.0
     * @see Workflow::preStatusChange
     */
    public const ISSUE_STATUS_BEFORE = 'issue.status.before';

    /**
     * @since 3.6.3
     * @see Workflow::getAllowedStatuses()
     */
    public const ISSUE_ALLOWED_STATUSES = 'issue.allowed.statuses';

    /**
     * @since 3.6.3
     * @see Workflow::addLinkFilters()
     */
    public const ISSUE_LINK_FILTERS = 'issue.link.filters';

    /**
     * @since 3.8.13
     * @see Workflow::getIssueFieldsToDisplay
     */
    public const ISSUE_FIELDS_DISPLAY = 'issue.fields.display';

    /**
     * @since 3.8.13
     * @see Workflow::handleIssueMovedFromProject
     */
    public const ISSUE_MOVE_FROM_PROJECT = 'issue.move.from_project';

    /**
     * @since 3.8.13
     * @see Workflow::handleIssueMovedToProject
     */
    public const ISSUE_MOVE_TO_PROJECT = 'issue.move.to_project';

    /**
     * @since 3.8.13
     * @see Workflow::getMovedIssueMapping
     */
    public const ISSUE_MOVE_MAPPING = 'issue.move.mapping';

    /**
     * @since 3.8.11
     * @see Workflow::canAccessIssue()
     */
    public const ACCESS_ISSUE = 'access.issue';

    /**
     * @since 3.8.11
     * @see Workflow::getAccessLevels()
     */
    public const ACCESS_LEVELS = 'access.levels';

    /**
     * @since 3.8.13
     * @see Workflow::getAdditionalAccessSQL()
     */
    public const ACCESS_LISTING_SQL = 'access.listing.sql';

    /**
     * @since 3.8.13
     * @see Workflow::canEmailIssue()
     */
    public const ACCESS_ISSUE_EMAIL = 'access.issue.email';

    /**
     * @since 3.8.13
     * @see Workflow::canSendNote()
     */
    public const ACCESS_ISSUE_NOTE = 'access.issue.note';

    /**
     * @since 3.8.13
     * @see Workflow::canCloneIssue()
     */
    public const ACCESS_ISSUE_CLONE = 'access.issue.clone';

    /**
     * @since 3.8.13
     * @see Workflow::canUpdateIssue()
     */
    public const ACCESS_ISSUE_UPDATE = 'access.issue.update';

    /**
     * @since 3.8.13
     * @see Workflow::canChangeAccessLevel()
     */
    public const ACCESS_ISSUE_CHANGE_ACCESS = 'access.issue.change_access';

    /**
     * @since 3.8.13
     * @see Workflow::ACCESS_ISSUE_UPDATE()
     */
    public const ACCESS_ISSUE_CHANGE_ASSIGNEE = 'access.issue.change_assignee';

    /**
     * @since 3.8.13
     * @see Workflow::handleAuthorizedReplierAdded()
     */
    public const AUTHORIZED_REPLIER_ADD = 'authorized_replier.add';

    /**
     * @since 3.8.13
     * @see Workflow::getActiveGroup()
     */
    public const GROUP_ACTIVE = 'group.active';

    /**
     * @since 3.5.0
     * @see Workflow::handleNewNote()
     */
    public const NOTE_CREATED = 'note.created';

    /**
     * @since 3.8.13
     * @see Workflow::preNoteInsert
     */
    public const NOTE_INSERT_BEFORE = 'note.insert.before';

    /**
     * @since 3.4.2
     * @see Notification::notifyNewIssue()
     */
    public const NOTIFY_ISSUE_CREATED = 'notify.issue.created';

    /**
     * @since 3.4.2
     * @see Reminder_Action::perform()
     */
    public const REMINDER_ACTION_PERFORM = 'reminder.action.perform';

    /**
     * @since 3.8.13
     * @see Workflow::prePage()
     */
    public const PAGE_BEFORE = 'page.before';

    /**
     * @since 3.4.2
     */
    public const IRC_FORMAT_MESSAGE = 'irc.format.message';

    /**
     * @since 3.6.0
     * @see \Eventum\Scm\Adapter\Gitlab::processIssueHook()
     */
    public const RPC_GITLAB_MATCH_ISSUE = 'rpc.gitlab.match.issue';

    /**
     * Event to allow configuring markdown renderer.
     *
     * @since 3.6.3
     * @see \Eventum\ServiceProvider\MarkdownServiceProvider::applyExtensions
     */
    public const MARKDOWN_ENVIRONMENT_CONFIGURE = 'markdown.environment.configure';

    /**
     * Event emitted when eventum boots.
     * You can listen to this event to add some initialization.
     *
     * @since 3.6.4
     */
    public const BOOT = 'eventum.boot';

    /**
     * Allow to hook into phinx configuration, to be able to specify extra migrations dirs.
     *
     * @since 3.6.5
     */
    public const PHINX_CONFIG = 'phinx.config';

    /**
     * Allow to hook into Smarty template processing.
     *
     * @since 3.9.0
     */
    public const SMARTY_PROCESS = 'smarty.process';

    /**
     * Event emitted when eventum shuts down.
     * You can listen to this event to do cleanup on request end.
     *
     * @since 3.6.4
     */
    public const SHUTDOWN = 'eventum.shutdown';

    /**
     * Helper to feature test whether specific event is being emitted by Eventum.
     *
     * @param string $eventName
     * @return bool
     * @since 3.5.5
     */
    public static function hasEvent($eventName)
    {
        $const = sprintf('%s::%s', self::class, $eventName);

        return defined($const);
    }
}
