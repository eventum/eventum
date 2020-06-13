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
use Eventum\Extension\Legacy;
use Eventum\Extension\RegisterExtension;

class EventumRegisterPartnerLegacyExtension extends AbstractMigration
{
    private const EXTENSIONS = [
        Legacy\PartnerLegacyExtension::class,
    ];

    public function up(): void
    {
        $register = new RegisterExtension();
        $register->register(...self::EXTENSIONS);
    }

    public function down(): void
    {
        $register = new RegisterExtension();
        $register->unregister(...self::EXTENSIONS);
    }
}
