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
use Eventum\Extension\BuiltinLegacyLoaderExtension;
use Eventum\Extension\ExtensionLoader;

class EventumExtensionMigrateDb extends AbstractMigration
{
    public function up()
    {
        $this->migratePartners();
        $this->migrateCustomFields();
        $this->migrateWorkflows();
        $this->migrateCustomers();
        $this->setupLegacyLoader();
    }

    /**
     * Setup BuiltinLegacyLoaderExtension being loaded by default
     */
    private function setupLegacyLoader()
    {
        $setup = Setup::get();
        $rf = new ReflectionClass(BuiltinLegacyLoaderExtension::class);
        $setup['extensions'][$rf->getName()] = $rf->getFileName();
        Setup::save();
    }

    private function migratePartners()
    {
        $el = Partner::getExtensionLoader();
        $this->migrate('partner_project', 'pap_par_code', $el);
        $this->migrate('user', 'usr_par_code', $el);
        $this->migrate('issue_partner', 'ipa_par_code', $el);
    }

    private function migrateCustomFields()
    {
        $el = Custom_Field::getExtensionLoader();
        $this->migrate('custom_field', 'fld_backend', $el);
    }

    private function migrateWorkflows()
    {
        $el = Workflow::getExtensionLoader();
        $this->migrate('project', 'prj_workflow_backend', $el);
    }

    private function migrateCustomers()
    {
        $el = CRM::getExtensionLoader();
        $this->migrate('project', 'prj_customer_backend', $el);
    }

    /**
     * @param string $table
     * @param string $field
     * @param ExtensionLoader $el
     */
    private function migrate($table, $field, $el)
    {
        $table = $this->quoteColumnName($table);
        $column = $this->quoteTableName($field);

        $st = $this->query("SELECT DISTINCT {$column} FROM {$table}");
        foreach ($st as $row) {
            $value = $row[$field];
            // nothing to convert for empty values
            if (!$value) {
                continue;
            }

            $classname = $el->getClassName($value);

            // use deterministic class name
            $classname = ucwords(str_replace('_', ' ', $classname));
            $classname = str_replace(' ', '_', $classname);

            // there's no placeholders support,
            // method to escape values not exported
            // but the class names should not need sql escaping
            // so just go with unescaped variant
            //
            // last known PR implementing params for execute:
            // https://github.com/robmorgan/phinx/pull/850
            $stmt = "UPDATE {$table} SET {$column} = '{$classname}' WHERE {$column} = '{$value}'";
            $this->execute($stmt);
        }
    }
}
