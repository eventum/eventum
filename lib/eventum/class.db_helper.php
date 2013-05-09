<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2013 Eventum Team.                              |
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
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// +----------------------------------------------------------------------+

require_once 'DB.php';

/**
 * Class to manage all tasks related to the DB abstraction module. This is only
 * useful to mantain a data dictionary of the current database schema tables.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

class DB_Helper
{
    /**
     * @var DB_Helper $instance
     */
    private static $instance;

	/**
	 * @static
	 * @return DB_common
	 */
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new DB_Helper();

            if (PEAR::isError($e = self::$instance->dbh)) {
	            /** @var $e PEAR_Error */
                Error_Handler::logError(array($e->getMessage(), $e->getDebugInfo()), __FILE__, __LINE__);
	            /** @global $error_type  */
                $error_type = "db";
                require_once APP_PATH . "/htdocs/offline.php";
                exit(2);
            }
        }
        return self::$instance->dbh;
    }

	/** @var DB_common */
    private $dbh;
    /**
     * Connects to the database and creates a data dictionary array to be used
     * on database related schema dynamic lookups.
     *
     * @access public
     */
    private function __construct() {
        $dsn = array(
            'phptype'  => APP_SQL_DBTYPE,
            'hostspec' => APP_SQL_DBHOST,
            'database' => APP_SQL_DBNAME,
            'username' => APP_SQL_DBUSER,
            'password' => APP_SQL_DBPASS
        );
        // if we are using some non-standard mysql port, pass that value in the dsn
        if ((defined('APP_SQL_DBPORT')) && (APP_SQL_DBPORT != 3306)) {
            $dsn['port'] = APP_SQL_DBPORT;
        }

        $this->dbh = DB::connect($dsn);
        if (PEAR::isError($this->dbh)) {
            return;
        }
        $this->dbh->query("SET SQL_MODE = ''");
        if (strtolower(APP_CHARSET) == 'utf-8' || strtolower(APP_CHARSET) == 'utf8') {
            $this->dbh->query("SET NAMES utf8");
        }
    }


    /**
     * Method used to get the last inserted ID. This is a simple
     * wrapper to the mysql_insert_id function, as a work around to
     * the somewhat annoying implementation of PEAR::DB to create
     * separate tables to host the ID sequences.
     *
     * @access  public
     * @return  integer The last inserted ID
     */
    static function get_last_insert_id() {
        return mysql_insert_id(self::getInstance()->connection);
    }


    /**
     * Returns the escaped version of the given string.
     *
     * @access  public
     * @param   string $str The string that needs to be escaped
     * @param   bool $add_quotes Whether to add quotes around result as well
     * @return  string The escaped string
     */
    static function escapeString($str, $add_quotes = false)
    {
        if ($add_quotes) {
            return "'". mysql_real_escape_string($str, self::getInstance()->connection) . "'";
        }
        return mysql_real_escape_string($str, self::getInstance()->connection);
    }


    /**
     * Returns the SQL used to calculate the difference of 2 dates, not counting weekends.
     * This thing is truly a work of art, the type of art that throws lemon juice in your eye and then laughs.
     * If $end_date_field is null, the current date is used instead.
     *
     * @access  public
     * @param   string $start_date_field The name of the field the first date is.
     * @param   string $end_date_field The name of the field where the second date is.
     * @return  string The SQL used to compare the 2 dates.
     */
    function getNoWeekendDateDiffSQL($start_date_field, $end_date_field = false)
    {
        if ($end_date_field == false) {
            $end_date_field = "'" . Date_Helper::getCurrentDateGMT() . "'";
        }

        // this is crazy, but it does work. Anyone with a better solution email bryan@mysql.com
        $sql = "((UNIX_TIMESTAMP($end_date_field) - UNIX_TIMESTAMP($start_date_field)) - (CASE
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
        return str_replace("\n", " ", $sql);
    }
}
