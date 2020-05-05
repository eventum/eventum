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

namespace Eventum\Extension\Legacy;

use Eventum\Extension\Provider;

/**
 * Extension that adds integration of legacy Customer classes to Extension events
 */
class CustomerLegacyExtension implements Provider\SubscriberProvider
{
    public function getSubscribers(): array
    {
        return [
        ];
    }
}
