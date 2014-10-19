<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2014 Eventum Team.                                     |
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
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

/**
 * Interface DbInterface
 *
 * Database interface designed against PEAR::DB
 */
interface DbInterface
{
    /**
     * Indicates the current default fetch mode should be used
     * @see DB_common::$fetchmode
     */
    const DB_FETCHMODE_DEFAULT = 0;

    /**
     * Column data indexed by numbers, ordered from 0 and up
     */
    const DB_FETCHMODE_ORDERED = 1;

    /**
     * Column data indexed by column names
     */
    const DB_FETCHMODE_ASSOC = 2;

    public function affectedRows();
    public function escapeSimple($str);
    public function getAll($query, $params = array(), $fetchmode = DbInterface::DB_FETCHMODE_DEFAULT);
    public function getAssoc($query, $force_array = false, $params = array(), $fetchmode = DbInterface::DB_FETCHMODE_DEFAULT, $group = false);
    public function getCol($query, $col = 0, $params = array());
    public function getOne($query, $params = array());
    public function getPair($query, $params = array());
    public function getRow($query, $params = array(), $fetchmode = DbInterface::DB_FETCHMODE_DEFAULT);

    public function query($query, $params = array());
    public function quoteIdentifier($str);
}
