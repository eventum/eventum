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

/**
 * Setup tables for phlib/flysystem-pdo
 *
 * @see https://github.com/phlib/flysystem-pdo/blob/0.0.3/schema/mysql.sql
 */
class FlysystemPdo extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('flysystem_path', ['id' => false, 'primary_key' => 'path_id']);
        $table
            ->addColumn('path_id', 'integer', ['limit' => self::INT_MEDIUM, 'signed' => false])
            ->addColumn('type', 'enum', ['values' => ['dir', 'file']])
            ->addColumn('path', 'string', ['limit' => self::TEXT_TINY])
            ->addColumn('mimetype', 'string', ['null' => true, 'limit' => self::TEXT_TINY, 'encoding' => 'ascii'])
            ->addColumn('visibility', 'string', ['null' => true, 'default' => '', 'limit' => 25])
            ->addColumn('size', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('is_compressed', 'integer', ['default' => '1', 'limit' => self::INT_TINY])
            ->addColumn('update_ts', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP']);
        $this->getPrimaryKey($table)->setIdentity(true);
        $table->create();

        $table = $this->table('flysystem_chunk', ['id' => false, 'primary_key' => ['path_id', 'chunk_no']]);
        $table
            ->addColumn('path_id', 'integer', ['limit' => self::INT_MEDIUM, 'signed' => false])
            ->addColumn('chunk_no', 'integer', ['limit' => self::INT_SMALL, 'signed' => false])
            ->addColumn('content', self::PHINX_TYPE_BLOB, ['length' => self::BLOB_MEDIUM])
            ->create();
    }
}
