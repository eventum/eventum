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

use Eventum\Db\Adapter\AdapterInterface;
use Eventum\Db\Adapter\NullAdapter;
use Eventum\Db\DatabaseException;
use Eventum\Monolog\Logger;

class DB_Helper
{
    const DEFAULT_ADAPTER = 'PdoAdapter';

    /**
     * @param bool $fallback
     * @throws DatabaseException
     * @return AdapterInterface
     */
    public static function getInstance($fallback = true)
    {
        static $instance;
        if ($instance !== null) {
            return $instance;
        }

        // initialize value to avoid recursion
        $instance = false;

        $config = self::getConfig();
        $className = self::getAdapterClass($config);

        try {
            $instance = new $className($config);

            return $instance;
        } catch (DatabaseException $e) {
            Logger::db()->error($e->getMessage(), ['exception' => $e]);
        }

        // set dummy provider in as offline.php uses db methods
        $instance = new NullAdapter($config);

        if (!$fallback) {
            throw $e;
        }

        // exit is evil, especially when unit testing
        if (defined('PHPUNIT_EVENTUM_TESTSUITE')) {
            throw $e;
        }

        /** @global $error_type */
        /** @noinspection PhpUnusedLocalVariableInspection */
        $error_type = 'db';
        require APP_PATH . '/htdocs/offline.php';
        exit(2);
    }

    private static function getAdapterClass($config)
    {
        $classname = isset($config['adapter']) ? $config['adapter'] : self::DEFAULT_ADAPTER;

        return 'Eventum\\Db\\Adapter\\' . $classname;
    }

    /**
     * Get database config.
     *
     * @return array
     */
    public static function getConfig()
    {
        return Setup::get()->database->toArray();
    }

    /**
     * Method used to get the last inserted ID.
     *
     * @return  int The last inserted ID
     */
    public static function get_last_insert_id()
    {
        $stmt = 'SELECT last_insert_id()';
        $res = (integer) self::getInstance()->getOne($stmt);

        return $res;
    }

    /**
     * Returns the escaped version of the given string.
     *
     * @param   string $str The string that needs to be escaped
     * @param   bool $add_quotes Whether to add quotes around result as well
     * @return  string The escaped string
     */
    public static function escapeString($str, $add_quotes = false)
    {
        $db = self::getInstance();
        if ($db) {
            $res = $db->escapeSimple($str);
        } else {
            // as this is so low level (handled by offline page)
            // supply some fallback
            $res = null;
        }

        if ($add_quotes) {
            $res = "'" . $res . "'";
        }

        return $res;
    }

    /**
     * Helper to build SQL queries with variable length parameters
     *
     * @param array $params
     * @return string A SQL statement partial with placeholders: field1=?, field2=?, field3=? ...
     */
    public static function buildSet($params)
    {
        $partial = [];
        foreach (array_keys($params) as $key) {
            $partial[] = "$key=?";
        }

        return implode(', ', $partial);
    }

    /**
     * Helper to build SQL queries with SET of parameters
     *
     * @param array $params
     * @return string A SQL statement partial with placeholders: ?, ?, ? ...
     */
    public static function buildList($params)
    {
        return implode(', ', array_fill(0, count($params), '?'));
    }

    /**
     * Give valid ORDER BY parameter
     *
     * @param string $order
     * @param string $default
     * @return string
     */
    public static function orderBy($order, $default = 'DESC')
    {
        if (!in_array(strtoupper($order), ['ASC', 'DESC'])) {
            return $default;
        }

        return $order;
    }

    /**
     * Returns the SQL used to calculate the difference of 2 dates, not counting weekends.
     * This thing is truly a work of art, the type of art that throws lemon juice in your eye and then laughs.
     * If $end_date_field is null, the current date is used instead.
     *
     * @param   string $start_date_field the name of the field the first date is
     * @param   string $end_date_field the name of the field where the second date is
     * @return  string the SQL used to compare the 2 dates
     */
    public static function getNoWeekendDateDiffSQL($start_date_field, $end_date_field = null)
    {
        if (!$end_date_field) {
            $end_date_field = "'" . Date_Helper::getCurrentDateGMT() . "'";
        }

        // this is crazy, but it does work. Anyone with a better solution email balsdorf@gmail.com
        $sql
            = "((UNIX_TIMESTAMP($end_date_field) - UNIX_TIMESTAMP($start_date_field)) - (CASE
            WHEN DAYOFWEEK($start_date_field) = 1 THEN (floor(((TO_DAYS($end_date_field) - TO_DAYS($start_date_field))-1)/7) * 86400 * 2)
            WHEN DAYOFWEEK($start_date_field) = 2 THEN (floor(((TO_DAYS($end_date_field) - TO_DAYS($start_date_field)))/7) * 86400 *2)
            WHEN DAYOFWEEK($start_date_field) = 3 THEN (floor(((TO_DAYS($end_date_field) - TO_DAYS($start_date_field))+1)/7) * 86400 *2)
            WHEN DAYOFWEEK($start_date_field) = 4 THEN (floor(((TO_DAYS($end_date_field) - TO_DAYS($start_date_field))+2)/7) * 86400 *2)
            WHEN DAYOFWEEK($start_date_field) = 5 THEN (floor(((TO_DAYS($end_date_field) - TO_DAYS($start_date_field))+3)/7) * 86400 *2)
            WHEN DAYOFWEEK($start_date_field) = 6 THEN (floor(((TO_DAYS($end_date_field) - TO_DAYS($start_date_field))+4)/7) * 86400 *2)
            WHEN DAYOFWEEK($start_date_field) = 7 THEN (floor(((TO_DAYS($end_date_field) - TO_DAYS($start_date_field))-2)/7) * 86400 *2)
        END) - (CASE
            WHEN DAYOFWEEK($start_date_field) = 7 THEN (86400 + (86400 - time_to_sec($start_date_field)))
            WHEN DAYOFWEEK($start_date_field) = 1 THEN (86400 - time_to_sec($start_date_field))
            ELSE 0
        END) - CASE
            WHEN DAYOFWEEK($end_date_field) = 7 THEN time_to_sec($end_date_field)
            WHEN DAYOFWEEK($end_date_field) = 1 THEN (86400 + time_to_sec($end_date_field))
            ELSE 0
        END)";

        return str_replace("\n", ' ', $sql);
    }
}
