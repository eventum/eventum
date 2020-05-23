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

class EventumEmailAccountTypesUnify extends AbstractMigration
{
    public function up(): void
    {
        $this->table('email_account')
            ->changeColumn('ema_leave_copy', 'boolean', ['default' => false])
            ->changeColumn('ema_get_only_new', 'boolean', ['default' => false])
            ->changeColumn('ema_use_routing', 'boolean', ['default' => false])
            ->update();
    }

    public function down(): void
    {
    }
}
