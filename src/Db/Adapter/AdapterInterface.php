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

use Eventum\Db\DatabaseException;

/**
 * Interface AdapterInterface
 *
 * Database interface designed against PEAR::DB
 */
interface AdapterInterface
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
     * @throws DatabaseException on connection failure
     */
    public function __construct(array $config);

    /**
     * Escapes a string according to the current DBMS's standards
     *
     * @param string $str the string to be escaped
     * @return string  the escaped string
     * @throws DatabaseException on failure.
     */
    public function escapeSimple($str);

    /**
     * Executes the SQL statement.
     *
     * This method should only be used for executing non-query SQL statement, such as `INSERT`, `DELETE`, `UPDATE` SQLs.
     *
     * @param string $query the SQL query or the statement to prepare
     * @param mixed $params array, string or numeric data
     * @return bool|object A new DB_result object for successful SELECT queries
     * or true for successful data manipulation queries.
     * @throws DatabaseException on failure.
     */
    public function query($query, $params = []);

    /**
     * Quotes a string so it can be safely used as a table or column name
     *
     * Delimiting style depends on which database driver is being used.
     *
     * @param string $str the identifier name to be quoted
     * @return string  the quoted identifier
     * @throws DatabaseException on failure.
     */
    public function quoteIdentifier($str);

    /**
     * Fetches all of the rows from a query result
     *
     * @param string $query the SQL query
     * @param mixed $params array, string or numeric data
     * @param int $fetchmode the fetch mode to use
     * @return array the nested array.
     * @throws DatabaseException on failure.
     */
    public function getAll($query, $params = [], $fetchmode = self::DB_FETCHMODE_ASSOC);

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
     * @throws DatabaseException on failure.
     */
    public function fetchAssoc($query, $params = [], $fetchmode = self::DB_FETCHMODE_DEFAULT);

    /**
     * Fetches a first column from a query result and returns it as an
     * indexed array
     *
     * @param string $query the SQL query
     * @param mixed $params array, string or numeric data
     * @return array the results as an array.
     * @throws DatabaseException on failure.
     */
    public function getColumn($query, $params = []);

    /**
     * Fetches the first column of the first row from a query result
     *
     * Takes care of doing the query and freeing the results when finished.
     *
     * @param string $query the SQL query
     * @param mixed $params array, string or numeric data
     * @return mixed the returned value of the query.
     * @throws DatabaseException on failure.
     */
    public function getOne($query, $params = []);

    /**
     * Fetches an entire query result and returns it as an
     * associative array using the first column as the key
     *
     * This mode requires the result set to contain exactly 2 columns use fetchAssoc() if you need more.
     *
     * @see DbInterface::fetchAssoc
     * @param string $query
     * @param mixed $params
     * @return array  the associative array containing the query results.
     * @throws DatabaseException on failure.
     */
    public function getPair($query, $params = []);

    /**
     * Fetches the first row of data returned from a query result
     *
     * @param string $query the SQL query
     * @param mixed $params array, string or numeric data
     * @param int $fetchmode the fetch mode to use
     * @return array  the first row of results as an array.
     * @throws DatabaseException on failure.
     */
    public function getRow($query, $params = [], $fetchmode = self::DB_FETCHMODE_ASSOC);
}
