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

namespace Eventum\Db;

use PDO;
use Phinx;
use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Db\Table;
use Phinx\Migration\AbstractMigration as PhinxAbstractMigration;
use RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractMigration extends PhinxAbstractMigration
{
    // According to https://dev.mysql.com/doc/refman/5.0/en/blob.html BLOB sizes are the same as TEXT
    protected const BLOB_TINY = MysqlAdapter::BLOB_TINY;
    protected const BLOB_REGULAR = MysqlAdapter::BLOB_REGULAR;
    protected const BLOB_MEDIUM = MysqlAdapter::BLOB_MEDIUM;
    protected const BLOB_LONG = MysqlAdapter::BLOB_LONG;

    protected const INT_TINY = MysqlAdapter::INT_TINY;
    protected const INT_SMALL = MysqlAdapter::INT_SMALL;
    protected const INT_MEDIUM = MysqlAdapter::INT_MEDIUM;
    protected const INT_REGULAR = MysqlAdapter::INT_REGULAR;
    protected const INT_BIG = MysqlAdapter::INT_BIG;

    protected const TEXT_TINY = MysqlAdapter::TEXT_TINY;
    protected const TEXT_SMALL = MysqlAdapter::TEXT_SMALL;
    protected const TEXT_REGULAR = MysqlAdapter::TEXT_REGULAR;
    protected const TEXT_MEDIUM = MysqlAdapter::TEXT_MEDIUM;
    protected const TEXT_LONG = MysqlAdapter::TEXT_LONG;

    protected const PHINX_TYPE_BLOB = MysqlAdapter::PHINX_TYPE_BLOB;
    protected const PHINX_TYPE_STRING = MysqlAdapter::PHINX_TYPE_STRING;

    protected const ENCODING_ASCII = 'ascii';
    protected const COLLATION_ASCII = 'ascii_general_ci';

    /**
     * MySQL Engine
     *
     * @var $engine
     */
    protected $engine;

    /**
     * MySQL Charset
     *
     * @var $string
     */
    protected $charset;

    /**
     * MySQL Collation
     *
     * @var $string
     */
    protected $collation;

    public function init(): void
    {
        // undefine to lazy init the values
        unset($this->engine, $this->charset, $this->collation);
    }

    public function __get($name)
    {
        $this->initOptions();

        if (!isset($this->$name)) {
            throw new RuntimeException("Unknown property: '$name'");
        }

        return $this->$name;
    }

    /**
     * This would be in init() but it's too early to use adapter.
     *
     * @see https://github.com/robmorgan/phinx/issues/1095
     */
    private function initOptions(): void
    {
        // extract options from phinx.php config
        $options = $this->getAdapter()->getOptions();
        $this->charset = $options['charset'];
        $this->collation = $options['collation'];
        $this->engine = $options['engine'];
    }

    /**
     * Override until upstream adds support
     *
     * @see https://github.com/robmorgan/phinx/pull/810
     * {@inheritdoc}
     */
    public function table($tableName, $options = [])
    {
        $options['engine'] = $options['engine'] ?? $this->engine;
        $options['charset'] = $options['charset'] ?? $this->charset;
        $options['collation'] = $options['collation'] ?? $this->collation;

        return parent::table($tableName, $options);
    }

    /**
     * @param string $columnName
     * @return string
     */
    protected function quoteColumnName($columnName)
    {
        return $this->getAdapter()->quoteColumnName($columnName);
    }

    /**
     * @param string $tableName
     * @return string
     */
    protected function quoteTableName($tableName)
    {
        return $this->getAdapter()->quoteTableName($tableName);
    }

    /**
     * Quote field value.
     * As long as execute() does not take params, we need to quote values.
     *
     * @see https://github.com/robmorgan/phinx/pull/850
     * @param string $value
     * @param int $parameter_type
     * @return string
     */
    protected function quote($value, $parameter_type = PDO::PARAM_STR)
    {
        /** @var MysqlAdapter $adapter */
        $adapter = $this->getAdapter();

        return $adapter->getConnection()->quote($value, $parameter_type);
    }

    /**
     * Run SQL Query, return single result.
     *
     * @param string $sql
     * @param string $column
     * @return string|null
     */
    protected function queryOne($sql, $column = '0'): ?string
    {
        $rows = $this->queryColumn($sql, $column);

        if (!$rows) {
            return null;
        }

        return $rows[0];
    }

    /**
     * Run SQL Query, return single column.
     *
     * @param string $sql
     * @param string $column
     * @return array
     */
    protected function queryColumn(string $sql, string $column): array
    {
        $st = $this->query($sql);
        $rows = [];
        foreach ($st as $row) {
            $rows[] = $row[$column];
        }

        return $rows;
    }

    /**
     * Run SQL Query, return key => value pairs
     *
     * @param string $sql
     * @param string $keyColumn
     * @param string $valueColumn
     * @return array
     */
    protected function queryPair($sql, $keyColumn, $valueColumn)
    {
        $rows = [];
        foreach ($this->query($sql) as $row) {
            $key = $row[$keyColumn];

            $rows[$key] = $row[$valueColumn];
        }

        return $rows;
    }

    /**
     * Return columns indexed by column names
     *
     * @param Table $table
     * @param array $columnNames
     * @return Table\Column[]
     */
    protected function getColumns(Table $table, $columnNames = [])
    {
        $columns = [];
        foreach ($table->getColumns() as $column) {
            $columns[$column->getName()] = $column;
        }

        if ($columnNames) {
            return array_intersect_key($columns, array_flip($columnNames));
        }

        return $columns;
    }

    /**
     * @param string $tableName
     * @param string $columnName
     * @return Table\Column
     */
    protected function getColumn($tableName, $columnName)
    {
        $table = $this->table($tableName);
        $columns = $this->getColumns($table, [$columnName]);

        return $columns[$columnName];
    }

    /**
     * Writes a message to the output and adds a newline at the end.
     *
     * @param string|array $messages The message as an array of lines of a single string
     * @param int $options A bitmask of options (one of the OUTPUT or VERBOSITY constants)
     */
    protected function writeln($messages, $options = OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_NORMAL): void
    {
        $this->output->writeln($messages, $options);
    }

    /**
     * @param int $total
     * @return ProgressBar
     */
    protected function createProgressBar($total)
    {
        $progressBar = new ProgressBar($this->output, $total);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s% | %message% ');
        $progressBar->setMessage('');

        return $progressBar;
    }
}
