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

use Eventum\Db\AbstractMigration;
use Eventum\Extension\AuditTrailExtension;
use Eventum\Extension\RegisterExtension;
use Eventum\ServiceContainer;

class EventumRegisterAuditTrailExtension extends AbstractMigration
{
    private const EXTENSION = AuditTrailExtension::class;

    public function up(): void
    {
        $enabled = ServiceContainer::getConfig()['audit_trail'] === 'enabled';
        if (!$enabled) {
            return;
        }

        $register = new RegisterExtension();
        $register->register(self::EXTENSION);
    }

    public function down(): void
    {
        $register = new RegisterExtension();
        $register->unregister(self::EXTENSION);
    }
}
