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
     * Event fired when history entry is added
     *
     * @since 3.3.0
     * @since 3.4.0 uses GenericEvent
     */
    const HISTORY_ADD = 'history.add';

    /**
     * @since 3.3.0
     * @since 3.4.0 uses GenericEvent
     */
    const USER_CREATE = 'user.create';

    /**
     * @since 3.3.0
     * @since 3.4.0 uses GenericEvent
     */
    const USER_UPDATE = 'user.update';

    /**
     * Upgrade config so that values contain EncryptedValue where some secrecy is wanted
     *
     * @since 3.5.0
     */
    const CONFIG_CRYPTO_UPGRADE = 'config.crypto_upgrade';

    /**
     * Downgrade config: remove all EncryptedValue elements.
     *
     * @since 3.5.0
     */
    const CONFIG_CRYPTO_DOWNGRADE = 'config.crypto_downgrade';

    /**
     * @since 3.4.0
     * @see CommitRepository::preCommit
     */
    const SCM_COMMIT_BEFORE = 'scm.commit.before';

    /**
     * Commit is associated to an issue
     *
     * @since 3.4.0
     * @see CommitRepository::addIssues
     */
    const SCM_COMMIT_ASSOCIATED = 'scm.commit.associated';

    /**
     * Event Fired when MailMessage was created from IMAP Connection.
     *
     * @since 3.4.0
     * @see ImapMessage::createFromImap
     */
    const MAIL_LOADED_IMAP = 'mail.loaded.imap';

    /**
     * @since 3.4.0
     * @see Mail_Queue::send()
     * @see MailQueueListener
     */
    const MAIL_QUEUE_SEND = 'mail.queue.send';
    const MAIL_QUEUE_SENT = 'mail.queue.sent';
    const MAIL_QUEUE_ERROR = 'mail.queue.error';

    /**
     * @since 3.4.2
     * @see Workflow::handleNewEmail()
     */
    const MAIL_PENDING = 'mail.pending';

    /**
     * @since 3.4.2
     * @see Workflow::handleNewEmail()
     */
    const MAIL_CREATED = 'mail.created';

    /**
     * @since 3.6.0
     * @see Workflow::shouldEmailAddress()
     */
    const NOTIFICATION_NOTIFY_ADDRESS = 'notification.notify.address';

    /**
     * @since 3.4.2
     */
    const IRC_NOTIFY = 'irc.notify';

    /**
     * @since 3.4.2
     * @see Workflow::handleBlockedEmail()
     */
    const EMAIL_BLOCKED = 'email.blocked';

    /**
     * @since 3.4.2
     * @see Workflow::handleAssignmentChange()
     */
    const ISSUE_ASSIGNMENT_CHANGE = 'issue.assignment_change';

    /**
     * @since 3.5.0
     * @see Workflow::preIssueUpdated
     */
    const ISSUE_UPDATED_BEFORE = 'issue.updated.before';

    /**
     * @since 3.6.0
     * @see Issue::markAsDuplicate
     */
    const ISSUE_MARK_DUPLICATE = 'issue.mark_duplicate';

    /**
     * @since 3.5.0
     * @see Workflow::handleNewIssue
     */
    const ISSUE_CREATED = 'issue.created';

    /**
     * @since 3.4.2
     * @see Workflow::handleIssueClosed()
     */
    const ISSUE_CLOSED = 'issue.closed';

    /**
     * @since 3.5.0
     * @see Workflow::handleIssueUpdated()
     */
    const ISSUE_UPDATED = 'issue.updated';

    /**
     * @since 3.5.0
     * @see Workflow::handleNewNote()
     */
    const NOTE_CREATED = 'note.created';

    /**
     * @since 3.4.2
     * @see Notification::notifyNewIssue()
     */
    const NOTIFY_ISSUE_CREATED = 'notify.issue.created';

    /**
     * @since 3.4.2
     * @see Reminder_Action::perform()
     */
    const REMINDER_ACTION_PERFORM = 'reminder.action.perform';

    /**
     * @since 3.4.2
     */
    const IRC_FORMAT_MESSAGE = 'irc.format.message';

    /**
     * @since 3.6.0
     * @see \Eventum\Scm\Adapter\Gitlab::processIssueHook()
     */
    public const RPC_GITLAB_MATCH_ISSUE = 'rpc.gitlab.match.issue';

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
