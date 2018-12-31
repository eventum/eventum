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
            [
                'support_email',
                [
                    // lower length not to exceed row length
                    $this->getColumn('support_email', 'sup_from')
                        ->setLimit(2048),
                    'sup_to',
                    'sup_cc',
                    'sup_subject',
                ],
            ],
        ]);
    }

    private function upgradeTables(array $definitions): void
    {
        $columnsGenerator = $this->getUpgradeColumns($definitions);
        $columns = iterator_to_array($columnsGenerator);
        $progressBar = $this->createProgressBar(count($columns));
        $progressBar->start();

        /**
         * @var Table $table
         * @var Column $column
         */
        foreach ($columns as [$table, $column]) {
            $table->changeColumn($column->getName(), $column);
            $progressBar->advance();
        }

        $progressBar->setMessage('');
        $progressBar->finish();
    }

    /**
     * @param array $definitions
     * @return Generator
     */
    private function getUpgradeColumns(array $definitions): Generator
    {
        foreach ($definitions as [$tableName, $columnNames]) {
            $table = $this->table($tableName);
            $columns = $this->getColumns($table);

            foreach ($columnNames as $column) {
                if (!$column instanceof Column) {
                    $column = $columns[$column];
                }
                $column->setEncoding($this->charset);
                $column->setCollation($this->collation);
                yield [$table, $column];
            }
        }
    }
}
