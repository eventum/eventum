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

class EventumUtf8Mb4Convert extends AbstractMigration
{
    public function up(): void
    {
        $this->upgradeTable('support_email', ['sup_from', 'sup_to', 'sup_cc', 'sup_subject']);
    }

    private function upgradeTable($tableName, $columnNames): void
    {
        $table = $this->table($tableName);
        $columns = $this->getColumns($table, $columnNames);

        foreach ($columns as $column) {
            $column->setEncoding($this->charset);
            $column->setCollation($this->collation);
            $table->changeColumn($column->getName(), $column);
        }

        $table->update();
    }
}
