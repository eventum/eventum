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

/**
 * Contains all events known in workflow.
 */
final class WorkflowEvents
{
    /**
     * Upgrade config so that values contain EncryptedValue where some secrecy is wanted
     *
     * @since 3.2.1
     */
    const CRYPTO_UPGRADE = 'crypto.upgrade_config';

    /**
     * Downgrade config: remove all EncryptedValue elements.
     *
     * @since 3.2.1
     */
    const CRYPTO_DOWNGRADE = 'crypto.downgrade_config';
}
