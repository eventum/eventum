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
}
