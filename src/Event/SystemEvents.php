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
     * @see ImapMessage::createFromImap
     */
    public const MAIL_LOADED_IMAP = 'mail.loaded.imap';

    /**
     * @since 3.4.0
     * @see Mail_Queue::send()
     * @see MailQueueListener
     */
    public const MAIL_QUEUE_SEND = 'mail.queue.send';
    public const MAIL_QUEUE_SENT = 'mail.queue.sent';
    public const MAIL_QUEUE_ERROR = 'mail.queue.error';

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
     * @since 3.6.0
     * @see Workflow::shouldEmailAddress()
     */
    public const NOTIFICATION_NOTIFY_ADDRESS = 'notification.notify.address';

    /**
     * @since 3.6.3
     * @see Workflow::handleSubscription()
     */
    public const NOTIFICATION_HANDLE_SUBSCRIPTION = 'notification.handle.subscription';

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
     * @since 3.5.0
     * @see Workflow::handleNewNote()
     */
    public const NOTE_CREATED = 'note.created';

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
     * @see \Eventum\Markdown::applyExtensions
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
