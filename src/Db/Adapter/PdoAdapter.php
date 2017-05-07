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

use DB_Helper;
use Eventum;
use Eventum\Db\DatabaseException;
use PDO;
use PDOException;
use UnexpectedValueException;

class PdoAdapter extends PdoAdapterBase implements AdapterInterface
{
    /** @var PDO */
    private $db;

    /** @var string */
    private $tablePrefix;

    /**
     * @param $config
     */
    public function __construct(array $config)
    {
        $dsn = $this->getDsn($config);

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

            // http://dev.mysql.com/doc/refman/5.7/en/sql-mode.html
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET SQL_MODE = ''",
        ];

        try {
            $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
        }

        if (Eventum\DebugBar::hasDebugBar()) {
            $pdo = Eventum\DebugBar::getTraceablePDO($pdo);
        }

        $this->db = $pdo;
        $this->tablePrefix = $config['table_prefix'];
    }

    public function getAll($query, $params = [], $fetchmode = AdapterInterface::DB_FETCHMODE_ASSOC)
    {
        $this->convertFetchMode($fetchmode);

        return $this->fetchAll($query, $params, $fetchmode);
    }

    public function fetchAssoc($query, $params = [], $fetchmode = AdapterInterface::DB_FETCHMODE_DEFAULT)
    {
        $flags = PDO::FETCH_GROUP | PDO::FETCH_UNIQUE;
        if ($fetchmode == AdapterInterface::DB_FETCHMODE_ASSOC) {
            $flags |= PDO::FETCH_ASSOC;
        } elseif ($fetchmode == AdapterInterface::DB_FETCHMODE_DEFAULT) {
            $flags |= PDO::FETCH_NUM;
        } else {
            throw new UnexpectedValueException(__FUNCTION__ . ' unsupported fetchmode: ' . $fetchmode);
        }

        return $this->fetchAll($query, $params, $flags);
    }

    public function getPair($query, $params = [])
    {
        return $this->fetchAll($query, $params, PDO::FETCH_KEY_PAIR);
    }

    public function getColumn($query, $params = [])
    {
        return $this->fetchAll($query, $params, PDO::FETCH_COLUMN);
    }

    public function getOne($query, $params = [])
    {
        $query = $this->quoteSql($query);
        $stmt = $this->db->prepare($query);
        $this->convertParams($params);
        $stmt->execute($params);

        $res = $stmt->fetchColumn();

        // emulate empty result
        if ($res === false) {
            return null;
        }

        return $res;
    }

    public function getRow($query, $params = [], $fetchmode = AdapterInterface::DB_FETCHMODE_ASSOC)
    {
        $query = $this->quoteSql($query);
        $stmt = $this->db->prepare($query);
        $this->convertParams($params);
        $stmt->execute($params);

        $this->convertFetchMode($fetchmode);

        return $stmt->fetch($fetchmode);
    }

    /**
     * @deprecated this is broken by design, should use parameters instead
     * @param string $str
     * @return string
     */
    public function escapeSimple($str)
    {
        // doesn't do arrays
        if (!is_scalar($str)) {
            return null;
        }

        $str = $this->db->quote($str);

        if ($str[0] == "'") {
            return substr($str, 1, -1);
        }

        return $str;
    }

    public function query($query, $params = [])
    {
        $query = $this->quoteSql($query);
        $stmt = $this->db->prepare($query);
        $this->convertParams($params);
        $stmt->execute($params);

        return true;
    }

    public function quoteIdentifier($str)
    {
        return '`' . str_replace('`', '``', $str) . '`';
    }

    /**
     * Common method for API
     * @param string $query
     */
    private function fetchAll($query, $params, $fetchmode)
    {
        $query = $this->quoteSql($query);
        $stmt = $this->db->prepare($query);
        $this->convertParams($params);
        $stmt->execute($params);

        return $stmt->fetchAll($fetchmode);
    }

    private function quoteSql($sql)
    {
        return DB_Helper::quoteTableName($this, $this->tablePrefix, $sql);
    }

    /**
     * Convert params to be indexed array instead of hash:
     * To avoid PDO error "SQLSTATE[HY093]: Invalid parameter number: parameter was not defined"
     *
     * The error comes mostly with BuildSet
     */
    private function convertParams(&$params)
    {
        $params = array_values($params);
    }
}
