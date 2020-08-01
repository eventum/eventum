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

use BadMethodCallException;
use DebugBar\DebugBarException;
use Doctrine\DBAL\Driver\Connection;
use Eventum;
use Eventum\Db\DatabaseException;
use Eventum\ServiceContainer;
use PDO;
use PDOException;
use UnexpectedValueException;

class PdoAdapter implements AdapterInterface
{
    const DEFAULT_DRIVER = 'mysql';

    const PDO_EXT_MISSING_ERROR = 'The PDO extension is required for this adapter but the extension is not loaded';

    const PDO_DRIVER_MISSING_ERROR = 'The %s driver is not currently installed';

    /** @var Connection */
    private $db;

    /**
     * @param array $config
     * @throws DatabaseException
     * @throws DebugBarException
     */
    public function __construct(array $config)
    {
        /** @var Connection $conn */
        $conn = ServiceContainer::get(Connection::class);

        $pdo = $conn->getWrappedConnection();
        $pdo = Eventum\DebugBarManager::getDebugBarManager()->registerPdo($pdo);

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
     * Get connect DSN for PDO
     *
     * @param array $config
     * @throws BadMethodCallException
     * @return string
     */
    private function getDsn($config)
    {
        $driver = $this->getDriverName($config);

        $dsn = "{$driver}:host={$config['hostname']};dbname={$config['database']};charset={$config['charset']}";

        // if we are using some non-standard mysql port, pass that value in the dsn
        if ($driver === 'mysql' && $config['port'] != 3306) {
            $dsn .= ";port={$config['port']}";
        }

        if (isset($config['socket'])) {
            // use socket connection
            $dsn .= ';unix_socket=' . $config['socket'];
        }

        return $dsn;
    }

    /**
     * Get PDO driver name.
     *
     * @param array $config
     * @throws BadMethodCallException
     * @return string
     */
    private function getDriverName($config)
    {
        // check for PDO extension
        if (!extension_loaded('pdo')) {
            throw new BadMethodCallException(self::PDO_EXT_MISSING_ERROR);
        }

        $driver = $config['driver'] ?? self::DEFAULT_DRIVER;
        if ($driver === 'mysqli') {
            $driver = 'mysql';
        }

        // check the PDO driver is available
        if (!in_array($driver, PDO::getAvailableDrivers(), true)) {
            $msg = sprintf(self::PDO_DRIVER_MISSING_ERROR, $driver);
            throw new BadMethodCallException($msg);
        }

        return $driver;
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
