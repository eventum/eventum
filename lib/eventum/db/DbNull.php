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

/**
 * Class DbNull
 *
 * Database which all methods do nothing, to be used for offline.php
 */
class DbNull implements DbInterface
{
    public function __construct(array $config)
    {
    }

    public function getAll($query, $params = array(), $fetchmode = DbInterface::DB_FETCHMODE_ASSOC)
    {
    }

    public function fetchAssoc($query, $params = array(), $fetchmode = DbInterface::DB_FETCHMODE_DEFAULT)
    {
    }

    public function getColumn($query, $params = array())
    {
    }

    public function getOne($query, $params = array())
    {
    }

    public function getPair($query, $params = array())
    {
    }

    public function getRow($query, $params = array(), $fetchmode = DbInterface::DB_FETCHMODE_ASSOC)
    {
    }

    public function escapeSimple($str)
    {
    }

    public function query($query, $params = array())
    {
    }

    public function quoteIdentifier($str)
    {
    }
}
