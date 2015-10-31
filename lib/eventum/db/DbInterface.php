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
 * Interface DbInterface
 *
 * Database interface designed against PEAR::DB
 */
interface DbInterface
{
    /**
     * Indicates the current default fetch mode should be used
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

    /**
     * Connects to the database
     *
     * @param array $config
     * @throws DbException on connection failure
     */
    public function __construct(array $config);

    /**
     * Determines the number of rows affected by a data manipulation query
     *
     * 0 is returned for queries that don't manipulate data.
     *
     * @return int  the number of rows
     * @throws DbException on failure.
     */
    public function affectedRows();

    /**
     * Escapes a string according to the current DBMS's standards
     *
     * @param string $str the string to be escaped
     * @return string  the escaped string
     * @throws DbException on failure.
     */
    public function escapeSimple($str);

    /**
     * Sends a query to the database server
     *
     * @param string $query the SQL query or the statement to prepare
     * @param mixed $params array, string or numeric data
     * @return mixed  a new DB_result object for successful SELECT queries
     *                 or DB_OK for successful data manipulation queries.
     * @throws DbException on failure.
     */
    public function query($query, $params = array());

    /**
     * Quotes a string so it can be safely used as a table or column name
     *
     * Delimiting style depends on which database driver is being used.
     *
     * @param string $str the identifier name to be quoted
     * @return string  the quoted identifier
     * @throws DbException on failure.
     */
    public function quoteIdentifier($str);

    /**
     * Fetches all of the rows from a query result
     *
     * @param string $query the SQL query
     * @param mixed $params array, string or numeric data
     * @param int $fetchmode the fetch mode to use
     * @return array the nested array.
     * @throws DbException on failure.
     */
    public function getAll($query, $params = array(), $fetchmode = DbInterface::DB_FETCHMODE_ASSOC);

    /**
     * Fetches an entire query result and returns it as an
     * associative array using the first column as the key
     *
     * Keep in mind that database functions in PHP usually return string
     * values for results regardless of the database's internal type.
     *
     * @param string $query
     * @param mixed $params
     * @param int $fetchmode
     * @throws DbException on failure.
     */
    public function fetchAssoc($query, $params = array(), $fetchmode = DbInterface::DB_FETCHMODE_DEFAULT);

    /**
     * Fetches a single column from a query result and returns it as an
     * indexed array
     *
     * @param string $query the SQL query
     * @param mixed $col which column to return
     * @param mixed $params array, string or numeric data
     * @return array  the results as an array.
     * @throws DbException on failure.
     * @deprecated use getColumn() instead and reorder select fields if need some other index
     */
    public function getCol($query, $col = 0, $params = array());

    /**
     * Fetches a first column from a query result and returns it as an
     * indexed array
     *
     * @param string $query the SQL query
     * @param mixed $params array, string or numeric data
     * @return array  the results as an array.
     * @throws DbException on failure.
     */
    public function getColumn($query, $params = array());

    /**
     * Fetches the first column of the first row from a query result
     *
     * Takes care of doing the query and freeing the results when finished.
     *
     * @param string $query the SQL query
     * @param mixed $params array, string or numeric data
     * @return mixed the returned value of the query.
     * @throws DbException on failure.
     */
    public function getOne($query, $params = array());

    /**
     * Fetches an entire query result and returns it as an
     * associative array using the first column as the key
     *
     * This mode requires the result set to contain exactly 2 columns use getAssoc() if you need more.
     *
     * @see DbInterface::getAssoc
     * @param string $query
     * @param mixed $params
     * @return array  the associative array containing the query results.
     * @throws DbException on failure.
     */
    public function getPair($query, $params = array());

    /**
     * Fetches the first row of data returned from a query result
     *
     * @param string $query the SQL query
     * @param mixed $params array, string or numeric data
     * @param int $fetchmode the fetch mode to use
     * @return array  the first row of results as an array.
     * @throws DbException on failure.
     */
    public function getRow($query, $params = array(), $fetchmode = DbInterface::DB_FETCHMODE_ASSOC);
}
