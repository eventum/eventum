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

require_once 'DB.php';

class DbPear implements DbInterface
{
    /** @var DB_common */
    private $db;

    /**
     * @var string
     */
    private $tablePrefix;

    /**
     * Connects to the database and creates a data dictionary array to be used
     * on database related schema dynamic lookups.
     */
    public function __construct($config)
    {
        $dsn = array(
            'phptype'  => $config['driver'],
            'hostspec' => $config['hostname'],
            'database' => $config['database'],
            'username' => $config['username'],
            'password' => $config['password'],
        );

        // DBTYPE specific dsn settings
        switch ($dsn['phptype']) {
            case 'mysql':
            case 'mysqli':
                // if we are using some non-standard mysql port, pass that value in the dsn
                if ($config['port'] != 3306) {
                    $dsn['port'] = $config['port'];
                }
                break;
            default:
                if ($config['port']) {
                    $dsn['port'] = $config['port'];
                }
                break;
        }

        $db = DB::connect($dsn);
        $this->assertError($db);

        // DBTYPE specific session setup commands
        switch ($dsn['phptype']) {
            case 'mysql':
            case 'mysqli':
                $db->query("SET SQL_MODE = ''");
                if (Language::isUTF8()) {
                    $db->query("SET NAMES utf8");
                }
                break;
            default:
                break;
        }

        $this->db = $db;
        $this->tablePrefix = $config['table_prefix'];
    }

    /**
     * Fetches the first column of the first row from a query result
     *
     * Takes care of doing the query and freeing the results when finished.
     *
     * @see DB_common::getOne
     * @param string $query the SQL query
     * @param mixed $params array, string or numeric data
     * @return mixed the returned value of the query.
     * @throws DbException on failure.
     */
    public function getOne($query, $params = array())
    {
        $query = $this->quoteSql($query);
        $res = $this->db->getOne($query, $params);
        $this->assertError($res);
        return $res;
    }


    /**
     * Fetches an entire query result and returns it as an
     * associative array using the first column as the key
     *
     * Keep in mind that database functions in PHP usually return string
     * values for results regardless of the database's internal type.
     *
     * @see DB_common::getAssoc
     * @param string $query
     * @param bool $force_array
     * @param mixed $params
     * @param int $fetchmode
     * @param bool $group
     * @return array  the associative array containing the query results.
     * @throws DbException on failure.
     */
    public function getAssoc(
        $query, $force_array = false, $params = array(),
        $fetchmode = DB_FETCHMODE_DEFAULT, $group = false
    ) {
        $query = $this->quoteSql($query);
        $res = $this->db->getAssoc($query, $force_array, $params, $fetchmode, $group);
        $this->assertError($res);
        return $res;
    }


    /**
     * Sends a query to the database server
     *
     * @see DB_common::query
     * @param string $query the SQL query or the statement to prepare
     * @param mixed $params array, string or numeric data
     * @return mixed  a new DB_result object for successful SELECT queries
     *                 or DB_OK for successull data manipulation queries.
     * @throws DbException on failure.
     */
    public function query($query, $params = array())
    {
        $query = $this->quoteSql($query);
        $res = $this->db->query($query, $params);
        $this->assertError($res);
        return $res;
    }

    /**
     * Fetches all of the rows from a query result
     *
     * @see DB_common::getAll
     * @param string $query the SQL query
     * @param mixed $params array, string or numeric data
     * @param int $fetchmode the fetch mode to use
     * @return array the nested array.
     * @throws DbException on failure.
     */
    public function getAll(
        $query, $params = array(),
        $fetchmode = DB_FETCHMODE_DEFAULT
    ) {
        $query = $this->quoteSql($query);
        $res = $this->db->getAll($query, $params, $fetchmode);
        $this->assertError($res);
        return $res;
    }


    /**
     * Fetches the first row of data returned from a query result
     *
     * @see DB_common::getRow
     * @param string $query the SQL query
     * @param mixed $params array, string or numeric data
     * @param int $fetchmode the fetch mode to use
     * @return array  the first row of results as an array.
     * @throws DbException on failure.
     */
    public function getRow(
        $query, $params = array(),
        $fetchmode = DB_FETCHMODE_DEFAULT
    ) {
        $query = $this->quoteSql($query);
        $res = $this->db->getRow($query, $params, $fetchmode);
        $this->assertError($res);
        return $res;
    }

    /**
     * Fetches a single column from a query result and returns it as an
     * indexed array
     *
     * @see DB_common::getCol
     * @param string $query the SQL query
     * @param mixed $col which column to return
     * @param mixed $params array, string or numeric data
     * @return array  the results as an array.
     * @throws DbException on failure.
     */
    public function getCol($query, $col = 0, $params = array())
    {
        $query = $this->quoteSql($query);
        $res = $this->db->getCol($query, $col, $params);
        $this->assertError($res);
        return $res;
    }

    /**
     * Quotes a string so it can be safely used as a table or column name
     *
     * Delimiting style depends on which database driver is being used.
     *
     * @see DB_common::quoteIdentifier
     * @param string $str the identifier name to be quoted
     * @return string  the quoted identifier
     * @throws DbException on failure.
     */
    public function quoteIdentifier($str)
    {
        $res = $this->db->quoteIdentifier($str);
        $this->assertError($res);
        return $res;
    }

    /**
     * Escapes a string according to the current DBMS's standards
     *
     * @see DB_common::escapeSimple
     * @param string $str the string to be escaped
     * @return string  the escaped string
     * @throws DbException on failure.
     */
    public function escapeSimple($str)
    {
        $res = $this->db->escapeSimple($str);
        $this->assertError($res);
        return $res;
    }

    /**
     * Check if $e is PEAR error, if so, throw as DbException
     *
     * @param $e PEAR_Error
     */
    private function assertError($e)
    {
        if (!PEAR::isError($e)) {
            return;
        }

        list($file, $line) = self::getTrace(2);
        Error_Handler::logError(array($e->getMessage(), $e->getDebugInfo()), $file, $line);

        throw new DbException($e->getMessage(), $e->getCode());
    }

    /**
     * Get array of FILE and LOCATION from backtrace
     *
     * @param int $depth
     * @return array
     */
    private static function getTrace($depth = 1)
    {
        $trace = debug_backtrace();
        if (!isset($trace[$depth])) {
            return null;
        }
        $caller = (object )$trace[$depth];
        if (!isset($caller->file)) {
            return null;
        }

        return array($caller->file, $caller->line);
    }

    /**
     * Processes a SQL statement by quoting table and column names that are enclosed within double brackets.
     * Tokens enclosed within double curly brackets are treated as table names, while
     * tokens enclosed within double square brackets are column names. They will be quoted accordingly.
     * Also, the percentage character "%" at the beginning or ending of a table name will be replaced
     * with [[tablePrefix]].
     *
     * @param string $sql the SQL to be quoted
     * @return string the quoted SQL
     * @see https://github.com/yiisoft/yii2/blob/2.0.0/framework/db/Connection.php#L761-L783
     */
    private function quoteSql($sql)
    {
        $that = $this;
        return preg_replace_callback(
            '/(\\{\\{(%?[\w\-\. ]+%?)\\}\\}|\\[\\[([\w\-\. ]+)\\]\\])/',
            function ($matches) use ($that) {
                if (isset($matches[3])) {
                    return $that->quoteIdentifier($matches[3]);
//                    return $matches[3];
                } else {
                    return str_replace('%', $that->tablePrefix, $that->quoteIdentifier($matches[2]));
//                    return str_replace('%', $that->tablePrefix, $matches[2]);
                }
            },
            $sql
        );
    }
}
