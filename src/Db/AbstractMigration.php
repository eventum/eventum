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

use LogicException;
use Phinx;
use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration as PhinxAbstractMigration;

abstract class AbstractMigration extends PhinxAbstractMigration
{
    // According to https://dev.mysql.com/doc/refman/5.0/en/blob.html BLOB sizes are the same as TEXT
    const BLOB_TINY = MysqlAdapter::BLOB_TINY;
    const BLOB_REGULAR = MysqlAdapter::BLOB_REGULAR;
    const BLOB_MEDIUM = MysqlAdapter::BLOB_MEDIUM;
    const BLOB_LONG = MysqlAdapter::BLOB_LONG;

    const INT_TINY = MysqlAdapter::INT_TINY;
    const INT_SMALL = MysqlAdapter::INT_SMALL;
    const INT_MEDIUM = MysqlAdapter::INT_MEDIUM;
    const INT_REGULAR = MysqlAdapter::INT_REGULAR;
    const INT_BIG = MysqlAdapter::INT_BIG;

    const TEXT_TINY = MysqlAdapter::TEXT_TINY;
    const TEXT_SMALL = MysqlAdapter::TEXT_SMALL;
    const TEXT_REGULAR = MysqlAdapter::TEXT_REGULAR;
    const TEXT_MEDIUM = MysqlAdapter::TEXT_MEDIUM;
    const TEXT_LONG = MysqlAdapter::TEXT_LONG;

    const PHINX_TYPE_BLOB = MysqlAdapter::PHINX_TYPE_BLOB;

    /**
     * MySQL Engine
     *
     * @var $engine
     */
    private $engine;

    /**
     * MySQL Charset
     *
     * @var $string
     */
    private $charset;

    /**
     * MySQL Collation
     *
     * @var $string
     */
    private $collation;

    /** @var bool */
    private $initialized;

    private function initOptions()
    {
        // extract options from phinx.php config
        $options = $this->getAdapter()->getOptions();
        $this->charset = $options['charset'];
        $this->collation = $options['collation'];
        $this->engine = $options['engine'];
        $this->initialized = true;
    }

    /**
     * Override until upstream adds support
     *
     * @see https://github.com/robmorgan/phinx/pull/810
     * {@inheritdoc}
     */
    public function table($tableName, $options = [])
    {
        if (!$this->initialized) {
            $this->initOptions();
        }

        $options['engine'] = $this->engine;
        $options['charset'] = $this->charset;
        $options['collation'] = $this->collation;

        return parent::table($tableName, $options);
    }

    /**
     * Get Primary Key column from Pending Table Operations.
     * Hack for AUTO_INCREMENT being lost when defining custom Primary Key column
     *
     * @see https://github.com/robmorgan/phinx/issues/28#issuecomment-298693426
     * @param Phinx\Db\Table $table
     * @return Phinx\Db\Table\Column
     */
    protected function getPrimaryKey(Phinx\Db\Table $table)
    {
        $options = $table->getOptions();
        if (!isset($options['primary_key'])) {
            throw new LogicException('primary_key column required');
        }

        $name = $options['primary_key'];
        $columns = $table->getPendingColumns();
        foreach ($columns as $column) {
            if ($column->getName() == $name) {
                return $column;
            }
        }
        throw new LogicException('primary_key column not found');
    }
}
