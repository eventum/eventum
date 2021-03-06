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

use Doctrine\DBAL\Driver\Connection;
use Eventum;
use Eventum\Db\DatabaseException;
use PDO;
use PDOException;
use UnexpectedValueException;

class PdoAdapter implements AdapterInterface
{
    /** @var Connection */
    private $db;

    /**
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
    }

    /**
     * @return PDO
     */
    public function getPdo()
    {
        return $this->db;
    }

    public function getAll($query, $params = [], $fetchMode = AdapterInterface::DB_FETCHMODE_ASSOC)
    {
        $this->convertFetchMode($fetchMode);

        return $this->fetchAll($query, $params, $fetchMode);
    }

    public function fetchAssoc($query, $params = [], $fetchMode = AdapterInterface::DB_FETCHMODE_DEFAULT)
    {
        $flags = PDO::FETCH_GROUP | PDO::FETCH_UNIQUE;
        if ($fetchMode === AdapterInterface::DB_FETCHMODE_ASSOC) {
            $flags |= PDO::FETCH_ASSOC;
        } elseif ($fetchMode === AdapterInterface::DB_FETCHMODE_DEFAULT) {
            $flags |= PDO::FETCH_NUM;
        } else {
            throw new UnexpectedValueException(__FUNCTION__ . ' unsupported fetchmode: ' . $fetchMode);
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
        $stmt = $this->db->prepare($query);
        $this->convertParams($params);
        try {
            $stmt->execute($params);
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
        }

        $res = $stmt->fetchColumn();

        // emulate empty result
        if ($res === false) {
            return null;
        }

        return $res;
    }

    public function getRow($query, $params = [], $fetchMode = AdapterInterface::DB_FETCHMODE_ASSOC)
    {
        $stmt = $this->db->prepare($query);
        $this->convertParams($params);
        try {
            $stmt->execute($params);
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
        }

        $this->convertFetchMode($fetchMode);

        return $stmt->fetch($fetchMode);
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

        if ($str[0] === "'") {
            return substr($str, 1, -1);
        }

        return $str;
    }

    public function query($query, $params = [])
    {
        $stmt = $this->db->prepare($query);
        $this->convertParams($params);
        try {
            $stmt->execute($params);
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
        }

        return true;
    }

    public function quoteIdentifier($str)
    {
        return '`' . str_replace('`', '``', $str) . '`';
    }

    /**
     * Common method for API
     *
     * @param string $query
     * @param array $params
     * @param int $fetchmode
     * @return array
     */
    private function fetchAll($query, $params, $fetchmode)
    {
        $stmt = $this->db->prepare($query);
        $this->convertParams($params);
        try {
            $stmt->execute($params);
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), $e->getCode(), $e);
        }

        return $stmt->fetchAll($fetchmode);
    }

    /**
     * Convert params to be indexed array instead of hash:
     * To avoid PDO error "SQLSTATE[HY093]: Invalid parameter number: parameter was not defined"
     *
     * The error comes mostly with BuildSet
     */
    private function convertParams(&$params): void
    {
        $params = array_values($params);
    }

    /**
     * Convert Eventum\Db\DbInterface fetchmode to PDO Fetch mode
     *
     * @param int $fetchMode
     * @throws UnexpectedValueException
     */
    private function convertFetchMode(&$fetchMode): void
    {
        switch ($fetchMode) {
            case AdapterInterface::DB_FETCHMODE_ASSOC:
                $fetchMode = PDO::FETCH_ASSOC;
                break;

            case AdapterInterface::DB_FETCHMODE_DEFAULT:
                $fetchMode = PDO::FETCH_NUM;
                break;

            default:
                throw new UnexpectedValueException('Unsupported fetchmode');
        }
    }
}
