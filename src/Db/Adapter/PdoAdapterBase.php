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
use PDO;
use UnexpectedValueException;

/**
 * Class for common methods for PDO adapters
 */
abstract class PdoAdapterBase
{
    const DEFAULT_DRIVER = 'mysql';

    const PDO_EXT_MISSING_ERROR = 'The PDO extension is required for this adapter but the extension is not loaded';

    const PDO_DRIVER_MISSING_ERROR = 'The %s driver is not currently installed';

    /**
     * Get connect DSN for PDO
     *
     * @param array $config
     * @throws BadMethodCallException
     * @return string
     */
    protected function getDsn($config)
    {
        $driver = $this->getDriverName($config);
        $charset = $this->getCharset();

        $dsn = "{$driver}:host={$config['hostname']};dbname={$config['database']};charset={$charset}";

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
     * Get charset suitable for PDO mysql driver
     *
     * @return string
     */
    protected function getCharset()
    {
        // no dash variant listed, blindly reap "UTF-8" to "UTF8"
        // http://dev.mysql.com/doc/refman/5.0/en/charset-charsets.html
        $charset = strtolower(str_replace('-', '', APP_CHARSET));

        return $charset;
    }

    /**
     * Get PDO driver name.
     *
     * @param array $config
     * @throws BadMethodCallException
     * @return string
     */
    protected function getDriverName($config)
    {
        // check for PDO extension
        if (!extension_loaded('pdo')) {
            throw new BadMethodCallException(self::PDO_EXT_MISSING_ERROR);
        }

        $driver = isset($config['driver']) ? $config['driver'] : self::DEFAULT_DRIVER;
        if ($driver === 'mysqli') {
            $driver = 'mysql';
        }

        // check the PDO driver is available
        if (!in_array($driver, PDO::getAvailableDrivers())) {
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
    protected function convertFetchMode(&$fetchMode)
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
