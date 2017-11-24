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

class EventumCustomFilterMultiselect extends AbstractMigration
{
    const COLUMNS = [
        'cst_iss_pri_id' => 'cst_priorities',
        'cst_iss_sev_id' => 'cst_severities',
        'cst_reporter' => 'cst_reporters',
        'cst_iss_prc_id' => 'cst_categories',
        'cst_iss_sta_id' => 'cst_statuses',
        'cst_iss_pre_id' => 'cst_releases',
        'cst_pro_id' => 'cst_products',
    ];

    public function up()
    {
        $table = $this->table('custom_filter');
        foreach (self::COLUMNS as $old_name => $new_name) {
            $table->changeColumn($old_name, 'string', ['length' => 255, 'null' => true]);
            $table->renameColumn($old_name, $new_name);
        }
        $table->changeColumn('cst_users', 'string', ['length' => 255, 'null' => true]);
        $table->save();
    }

    public function down()
    {
        $table = $this->table('custom_filter');
        foreach (self::COLUMNS as $old_name => $new_name) {
            $table->changeColumn($new_name, 'integer', ['length' => 10, 'null' => true, 'signed' => false]);
            $table->renameColumn($new_name, $old_name);
        }
        $table->changeColumn('cst_users', 'string', ['length' => 64, 'null' => true]);
        $table->save();
    }
}
