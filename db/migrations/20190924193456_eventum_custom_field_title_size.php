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

class EventumCustomFieldTitleSize extends AbstractMigration
{
    public function up(): void
    {
        $this->table('custom_field', ['id' => false, 'primary_key' => 'fld_id'])
            ->changeColumn('fld_title', 'string', ['length' => self::TEXT_SMALL, 'default' => ''])
            ->changeColumn('fld_description', 'string', ['length' => self::TEXT_SMALL, 'null' => true])
            ->update();
    }

    public function down(): void
    {
        $this->table('custom_field', ['id' => false, 'primary_key' => 'fld_id'])
            ->changeColumn('fld_title', 'string', ['length' => 32, 'default' => ''])
            ->changeColumn('fld_description', 'string', ['length' => 64, 'null' => true])
            ->update();
    }
}
