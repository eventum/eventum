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
use Phinx\Db\Table;
use Phinx\Db\Table\Column;

class EventumUtf8Mb4Convert extends AbstractMigration
{
    public function up(): void
    {
        $this->upgradeTables([
            ['support_email', ['sup_from', 'sup_to', 'sup_cc', 'sup_subject']],
        ]);
    }

    private function upgradeTables(array $definitions): void
    {
        $progressBar = $this->createProgressBar($this->countColumns($definitions));
        $progressBar->start();

        foreach ($definitions as [$tableName, $columnNames]) {
            /**
             * @var Table $table
             * @var Column $column
             */
            foreach ($this->upgradeTable($tableName, $columnNames) as [$table, $column]) {
                $table->changeColumn($column->getName(), $column);
                $progressBar->advance();
            }
        }
        $progressBar->setMessage('');
        $progressBar->finish();
    }

    /**
     * @param string $tableName
     * @param string[] $columnNames
     * @return Generator|Column[]
     */
    private function upgradeTable($tableName, $columnNames): Generator
    {
        $table = $this->table($tableName);
        $columns = $this->getColumns($table, $columnNames);

        foreach ($columns as $column) {
            $column->setEncoding($this->charset);
            $column->setCollation($this->collation);
            yield [$table, $column];
        }

        $table->update();
    }

    private function countColumns(array $definitions): int
    {
        $total = 0;
        foreach ($definitions as [$tableName, $columnNames]) {
            $total += count($columnNames);
        }

        return $total;
    }
}
