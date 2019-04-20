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

namespace Eventum\Extension;

/**
 * @deprecated [since 3.6.6]: implement each interface by your own
 */
interface ExtensionInterface extends
    Provider\AutoloadProvider,
    Provider\CustomFieldProvider,
    Provider\PartnerProvider,
    Provider\SubscriberProvider,
    Provider\WorkflowProvider,
    Provider\CrmProvider
{
}
