<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004 MySQL AB                                    |
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
//
// @(#) $Id: s.class.db_api.php 1.19 04/01/19 15:19:26-00:00 jpradomaia $
//


/**
 * Class to manage all tasks related to the DB abstraction module. This is only
 * useful to mantain a data dictionary of the current database schema tables.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

$TOTAL_QUERIES = 0;

include_once("DB.php");
include_once(APP_INC_PATH . "class.error_handler.php");
include_once(APP_INC_PATH . "class.misc.php");

class DB_API
{
    var $dbh;

    var $required_tables = array(
        "custom_field",
        "custom_field_option",
        "custom_filter",
        "email_account",
        "email_response",
        "issue",
        "issue_association",
        "issue_attachment",
        "issue_attachment_file",
        "issue_checkin",
        "issue_custom_field",
        "issue_history",
        "issue_requirement",
        "issue_user",
        "note",
        "phone_support",
        "priority",
        "project",
        "project_category",
        "project_custom_field",
        "project_release",
        "project_status",
        "project_user",
        "resolution",
        "status",
        "subscription",
        "subscription_type",
        "support_email",
        "time_tracking",
        "time_tracking_category",
        "user"
    );


    /**
     * Connects to the database and creates a data dictionary array to be used
     * on database related schema dynamic lookups.
     *
     * @access public
     */
    function DB_API()
    {
        $dsn = array(
            'phptype'  => APP_SQL_DBTYPE,
            'hostspec' => APP_SQL_DBHOST,
            'database' => APP_SQL_DBNAME,
            'username' => APP_SQL_DBUSER,
            'password' => APP_SQL_DBPASS
        );
        $this->dbh = DB::connect($dsn);
        if (PEAR::isError($this->dbh)) {
            Error_Handler::logError(array($this->dbh->getMessage(), $this->dbh->getDebugInfo()), __FILE__, __LINE__);
            $error_type = "db";
            include_once(APP_PATH . "offline.php");
            exit;
        }
        // add the table prefix to all of the required tables
        $this->required_tables = Misc::array_map_deep($this->required_tables, array('DB_API', 'add_table_prefix'));
        // check if all of the required tables are really there
        $stmt = "SHOW TABLES";
        $table_list = $this->dbh->getCol($stmt);
        for ($i = 0; $i < count($this->required_tables); $i++) {
            if (!in_array($this->required_tables[$i], $table_list)) {
                $error_type = "table";
                include_once(APP_PATH . "offline.php");
                exit;
            }
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
    function get_last_insert_id()
    {
        return @mysql_insert_id($this->dbh->connection);
    }


    /**
     * Method used by the code that checks if the required tables
     * do exist in the appropriate database. It returns the given
     * table name prepended with the appropriate table prefix.
     *
     * @access  private
     * @param   string $table_name The table name
     * @return  string The table name with the prefix added to it
     */
    function add_table_prefix($table_name)
    {
        return APP_TABLE_PREFIX . $table_name;
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included DB_API Class');
}
?>