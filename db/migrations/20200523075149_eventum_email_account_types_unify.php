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
        $builder = $this->getQueryBuilder();
        $builder
            ->update('email_account')
            ->set($builder->newExpr("ema_issue_auto_creation = ema_issue_auto_creation='enabled'"))
            ->execute();

        $this->table('email_account')
            ->changeColumn('ema_leave_copy', 'boolean', ['default' => false])
            ->changeColumn('ema_get_only_new', 'boolean', ['default' => false])
            ->changeColumn('ema_use_routing', 'boolean', ['default' => false])
            ->changeColumn('ema_issue_auto_creation', 'boolean', ['default' => false])
            ->changeColumn('ema_port', 'integer', ['length' => self::INT_SMALL, 'signed' => false])
            ->update();
    }

    public function down(): void
    {
        $this->table('email_account')
            ->changeColumn('ema_issue_auto_creation', 'string', ['length' => 8, 'default' => 'disabled'])
            ->update();

        $builder = $this->getQueryBuilder();
        $builder
            ->update('email_account')
            ->set('ema_issue_auto_creation', 'enabled')
            ->where("ema_issue_auto_creation='1'")
            ->execute();
    }
}
