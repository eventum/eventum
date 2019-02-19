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

class EventumExtensionClass extends AbstractMigration
{
    /**
     * changeColumn() doesn't support down(),
     * and we don't implement it ourselves,
     * because it's just width increase change.
     */
    public function up(): void
    {
        $this->modifyColumn('partner_project', 'pap_par_code');
        $this->modifyColumn('custom_field', 'fld_backend', ['null' => true]);
        $this->modifyColumn('project', 'prj_customer_backend', ['null' => true]);
        $this->modifyColumn('project', 'prj_workflow_backend', ['null' => true]);
        $this->modifyColumn('user', 'usr_par_code', ['null' => true]);
        $this->modifyColumn('issue_partner', 'ipa_par_code');
    }

    private function modifyColumn($table, $column, $options = []): void
    {
        $options += ['limit' => self::TEXT_TINY, 'encoding' => 'ascii'];
        $this->table($table)
            ->changeColumn($column, 'string', $options)
            ->save();
    }
}
