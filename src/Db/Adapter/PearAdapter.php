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

use DB;
use DB_common;
use DB_Helper;
use DB_result;
use Eventum\Db\DatabaseException;
use Eventum\Monolog\Logger;
use Language;
use LogicException;
use Misc;
use PEAR_Error;

class PearAdapter implements AdapterInterface
{
    /** @var DB_common */
    private $db;

    /**
     * @var string
     */
    private $tablePrefix;

    public function __construct(array $config)
    {
        $dsn = array(
            'phptype' => $config['driver'],
            'hostspec' => $config['hostname'],
            'database' => $config['database'],
            'username' => $config['username'],
            'password' => $config['password'],
        );

        if (isset($config['socket'])) {
            $dsn['socket'] = $config['socket'];
        }

        // DBTYPE specific dsn settings
        switch ($dsn['phptype']) {
            case 'mysql':
            case 'mysqli':
                // if we are using some non-standard mysql port, pass that value in the dsn
                if ($config['port'] != 3306) {
                    $dsn['port'] = $config['port'];
                }

                // add default socket, makes error message different
                if ($dsn['hostspec'] == 'localhost' && !isset($dsn['socket'])) {
                    $dsn['socket'] = ini_get("{$dsn['phptype']}.default_socket");
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
                    $db->query('SET NAMES utf8');
                }
                break;
            default:
                break;
        }

        $this->db = $db;
        $this->tablePrefix = $config['table_prefix'];
    }

    /**
     * @see DB_common::getOne
     */
    public function getOne($query, $params = array())
    {
        $query = $this->quoteSql($query, $params);
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
     * @param string $query
     * @param bool $force_array
     * @param mixed $params
     * @param int $fetchmode
     * @param bool $group
     * @return array  the associative array containing the query results.
     * @throws DatabaseException on failure.
     * @deprecated use fetchAssoc() instead for cleaner interface
     */
    private function getAssoc(
        $query, $force_array = false, $params = array(),
        $fetchmode = AdapterInterface::DB_FETCHMODE_DEFAULT, $group = false
    ) {
        if (is_array($force_array)) {
            throw new LogicException('force_array passed as array, did you mean fetchPair or forgot extra arg?');
        }
        $query = $this->quoteSql($query, $params);
        $res = $this->db->getAssoc($query, $force_array, $params, $fetchmode, $group);
        $this->assertError($res);

        return $res;
    }

    /**
     * @see DB_common::getAssoc
     */
    public function fetchAssoc($query, $params = array(), $fetchmode = AdapterInterface::DB_FETCHMODE_DEFAULT)
    {
        $query = $this->quoteSql($query, $params);
        $res = $this->db->getAssoc($query, false, $params, $fetchmode, false);
        $this->assertError($res);

        return $res;
    }

    public function getPair($query, $params = array())
    {
        return $this->getAssoc($query, false, $params);
    }

    /**
     * @see DB_common::query
     */
    public function query($query, $params = array())
    {
        $query = $this->quoteSql($query, $params);
        $res = $this->db->query($query, $params);
        $this->assertError($res);

        if ($res instanceof DB_result) {
            return $res;
        }

        return $res == DB_OK;
    }

    /**
     * @see DB_common::getAll
     */
    public function getAll($query, $params = array(), $fetchmode = AdapterInterface::DB_FETCHMODE_ASSOC)
    {
        $query = $this->quoteSql($query, $params);
        $res = $this->db->getAll($query, $params, $fetchmode);
        $this->assertError($res);

        return $res;
    }

    /**
     * @see DB_common::getRow
     */
    public function getRow($query, $params = array(), $fetchmode = AdapterInterface::DB_FETCHMODE_ASSOC)
    {
        $query = $this->quoteSql($query, $params);
        $res = $this->db->getRow($query, $params, $fetchmode);
        $this->assertError($res);

        return $res;
    }

    /**
     * @see DB_common::getCol
     */
    public function getColumn($query, $params = array())
    {
        $query = $this->quoteSql($query, $params);
        $res = $this->db->getCol($query, 0, $params);
        $this->assertError($res);

        return $res;
    }

    /**
     * @see DB_common::quoteIdentifier
     */
    public function quoteIdentifier($str)
    {
        $res = $this->db->quoteIdentifier($str);
        $this->assertError($res);

        return $res;
    }

    /**
     * @see DB_common::escapeSimple
     */
    public function escapeSimple($str)
    {
        $res = $this->db->escapeSimple($str);
        $this->assertError($res);

        return $res;
    }

    /**
     * Check if $e is PEAR error, if so, throw as DatabaseException
     *
     * @param $e PEAR_Error|array|object|int
     */
    private function assertError($e)
    {
        if (!Misc::isError($e)) {
            return;
        }

        $context = array(
            'debuginfo' => $e->getDebugInfo(),
        );

        // walk up in $e->backtrace until we find ourself
        // and from it we can get method name and it's arguments
        foreach ($e->backtrace as $i => $stack) {
            if (!isset($stack['object'])) {
                continue;
            }
            if (!$stack['object'] instanceof self) {
                continue;
            }

            $context['method'] = $stack['function'];
            $context['arguments'] = $stack['args'];

            // add these last, they are least interesting ones
            $context['code'] = $e->getCode();
            $context['file'] = $stack['file'];
            $context['line'] = $stack['line'];
            break;
        }

        Logger::db()->error($e->getMessage(), $context);

        $de = new DatabaseException($e->getMessage(), $e->getCode());
        if (isset($context['file'])) {
            $de->setExceptionLocation($context['file'], $context['line']);
        }
        $de->setContext($context);

        throw $de;
    }

    /**
     * @param string $sql the SQL to be quoted
     * @param array $params
     * @return string the quoted SQL
     */
    private function quoteSql($sql, $params)
    {
        /**
         * NOTE: PEAR driver treats these three as placeholders: '?&!'
         * but we want to use only '?', so need to quote these others first
         *
         * @see DB_common::prepare()
         */
        if (count($params)) {
            $sql = preg_replace('/((?<!\\\)[&!])/', '\\\$1', $sql);
        }

        return DB_Helper::quoteTableName($this, $this->tablePrefix, $sql);
    }
}
