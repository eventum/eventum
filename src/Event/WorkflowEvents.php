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
 * @deprecated class deprecated since 3.5.0, use SystemEvents constants.
 */
final class WorkflowEvents
{
    /**
     * Upgrade config so that values contain EncryptedValue where some secrecy is wanted
     *
     * @since 3.2.1
     * @deprecated since 3.5.0 use new constant
     */
    const CONFIG_CRYPTO_UPGRADE = SystemEvents::CONFIG_CRYPTO_UPGRADE;

    /**
     * Downgrade config: remove all EncryptedValue elements.
     *
     * @since 3.2.1
     * @deprecated since 3.5.0 use new constant
     */
    const CONFIG_CRYPTO_DOWNGRADE = SystemEvents::CONFIG_CRYPTO_DOWNGRADE;
}
