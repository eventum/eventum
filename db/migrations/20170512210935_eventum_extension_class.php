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

use Phinx\Migration\AbstractMigration;

class EventumExtensionClass extends AbstractMigration
{
    /**
     * change() doesn't support MODIFY statement
     * there's no down() because we just increase width here
     */
    public function up()
    {
        $notNullable = 'VARCHAR(255) CHARACTER SET ASCII NOT NULL';
        $nullable = 'VARCHAR(255) CHARACTER SET ASCII DEFAULT NULL';

        $this->modifyColumn('partner_project', 'pap_par_code', $notNullable);
        $this->modifyColumn('custom_field', 'fld_backend', $nullable);
        $this->modifyColumn('project', 'prj_customer_backend', $nullable);
        $this->modifyColumn('project', 'prj_workflow_backend', $nullable);
    }

    private function modifyColumn($table, $column, $definition)
    {
        return $this->execute("ALTER TABLE `{$table}` MODIFY `{$column}` {$definition}");
    }
}
