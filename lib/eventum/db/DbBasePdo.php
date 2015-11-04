<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2015 Eventum Team.                                     |
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

/**
 * Class for common methods for PDO adapters
 */
abstract class DbBasePdo
{
    const DEFAULT_DRIVER = 'mysql';

    const PDO_EXT_MISSING_ERROR = 'The PDO extension is required for this adapter but the extension is not loaded';

    const PDO_DRIVER_MISSING_ERROR = 'The %s driver is not currently installed';

    /**
     * Get connect DSN for PDO
     *
     * @param array $config
     * @return string
     */
    protected function getDsn($config)
    {
        $driver = $this->getDriverName($config);
        $charset = $this->getCharset();

        $dsn = "{$driver}:host={$config['hostname']};dbname={$config['database']};charset={$charset}";

        // if we are using some non-standard mysql port, pass that value in the dsn
        if ($driver == 'mysql' && $config['port'] != 3306) {
            $dsn .= ";port={$config['port']}";
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
     * @return string
     */
    protected function getDriverName($config)
    {
        // check for PDO extension
        if (!extension_loaded('pdo')) {
            throw new BadMethodCallException(self::PDO_EXT_MISSING_ERROR);
        }

        $driver = $config['driver'] ?: self::DEFAULT_DRIVER;
        if ($driver == 'mysqli') {
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
     * Convert DbInterface fetchmode to PDO Fetch mode
     *
     * @param int $fetchmode
     */
    protected function convertFetchMode(&$fetchmode)
    {
        switch ($fetchmode) {
            case DbInterface::DB_FETCHMODE_ASSOC:
                $fetchmode = PDO::FETCH_ASSOC;
                break;

            case DbInterface::DB_FETCHMODE_DEFAULT:
                $fetchmode = PDO::FETCH_NUM;
                break;

            default:
                throw new UnexpectedValueException('Unsupported fetchmode');
        }
    }
}
