<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2014-2015 Eventum Team.                                |
// |                                                                      |
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License as published by |
// | the Free Software Foundation; either version 2 of the License, or    |
// | (at your option) any later version.                                  |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to:                           |
// |                                                                      |
// | Free Software Foundation, Inc.                                       |
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+

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
