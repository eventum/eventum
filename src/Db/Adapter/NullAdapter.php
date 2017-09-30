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

namespace Eventum\Db\Adapter;

/**
 * Class NullAdapter
 *
 * Database which all methods do nothing, to be used for offline.php
 */
class NullAdapter implements AdapterInterface
{
    public function __construct(array $config)
    {
    }

    public function getAll($query, $params = [], $fetchMode = AdapterInterface::DB_FETCHMODE_ASSOC)
    {
    }

    public function fetchAssoc($query, $params = [], $fetchMode = AdapterInterface::DB_FETCHMODE_DEFAULT)
    {
    }

    public function getColumn($query, $params = [])
    {
    }

    public function getOne($query, $params = [])
    {
    }

    public function getPair($query, $params = [])
    {
    }

    public function getRow($query, $params = [], $fetchmode = AdapterInterface::DB_FETCHMODE_ASSOC)
    {
    }

    public function escapeSimple($str)
    {
    }

    public function query($query, $params = [])
    {
    }

    public function quoteIdentifier($str)
    {
    }
}
