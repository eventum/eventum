<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005 MySQL AB                              |
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
// @(#) $Id: s.config.inc.php 1.8 04/01/19 15:19:26-00:00 jpradomaia $
//
ini_set('allow_url_fopen', 0);
ini_set("display_errors", 0);
error_reporting(0);
@set_time_limit(0);
set_magic_quotes_runtime(0);
// prevent session from messing up the browser cache
ini_set('session.cache_limiter', 'nocache');

// only needed for older PHP versions
if (!function_exists('is_a')) {
    function is_a($object, $class_name)
    {
        $class_name = strtolower($class_name);
        if (get_class($object) == $class_name) {
            return TRUE;
        } else {
            return is_subclass_of($object, $class_name);
        }
    }
}

// definitions of path related variables
$app_path = '%{APP_PATH}%';
if ((substr($app_path, -1) != '/') && (substr($app_path, -2) != '\\')) {
    $app_path .= '/';
}
@define("APP_PATH", $app_path);
@define("APP_INC_PATH", APP_PATH . "include/");
@define("APP_PEAR_PATH", APP_INC_PATH . "pear/");
@define("APP_TPL_PATH", APP_PATH . "templates/");
@define("APP_SMARTY_PATH", APP_INC_PATH . "Smarty/");
@define("APP_JPGRAPH_PATH", APP_INC_PATH . "jpgraph/");
@define("APP_LOG_PATH", APP_PATH . "logs/");
@define("APP_LOCKS_PATH", APP_PATH . "locks/");
if (stristr(PHP_OS, 'darwin')) {
    ini_set("include_path", ".:" . APP_PEAR_PATH);
} elseif (stristr(PHP_OS, 'win')) {
    ini_set("include_path", ".;" . APP_PEAR_PATH);
} else {
    ini_set("include_path", ".:" . APP_PEAR_PATH);
}

@define("APP_SETUP_PATH", APP_PATH);
@define("APP_SETUP_FILE", APP_SETUP_PATH . "setup.conf.php");

// definitions of SQL variables
@define("APP_SQL_DBTYPE", "mysql");
@define("APP_SQL_DBHOST", "%{APP_SQL_DBHOST}%");
@define("APP_SQL_DBPORT", 3306);
@define("APP_SQL_DBNAME", "%{APP_SQL_DBNAME}%");
@define("APP_SQL_DBUSER", "%{APP_SQL_DBUSER}%");
@define("APP_SQL_DBPASS", "%{APP_SQL_DBPASS}%");

@define("APP_DEFAULT_DB", APP_SQL_DBNAME);
@define("APP_TABLE_PREFIX", "%{APP_TABLE_PREFIX}%");

@define("APP_ERROR_LOG", APP_LOG_PATH . "errors.log");
@define("APP_CLI_LOG", APP_LOG_PATH . "cli.log");
@define("APP_IRC_LOG", APP_LOG_PATH . "irc_bot.log");
@define("APP_LOGIN_LOG", APP_LOG_PATH . "login_attempts.log");

@define("APP_NAME", "Eventum");
@define("APP_SHORT_NAME", APP_NAME); // used in the subject of notification emails
@define("APP_URL", "http://www.mysql.com/products/eventum/");
@define("APP_HOSTNAME", "%{APP_HOSTNAME}%");
@define("APP_SITE_NAME", APP_NAME);
@define("APP_RELATIVE_URL", "%{APP_RELATIVE_URL}%");
@define("APP_BASE_URL", "%{PROTOCOL_TYPE}%" . APP_HOSTNAME . APP_RELATIVE_URL);
@define("APP_COOKIE_URL", APP_RELATIVE_URL);
@define("APP_COOKIE_DOMAIN", APP_HOSTNAME);
@define("APP_COOKIE", "eventum");
@define("APP_COOKIE_EXPIRE", time() + (60 * 60 * 8));
@define("APP_PROJECT_COOKIE", "eventum_project");
@define("APP_PROJECT_COOKIE_EXPIRE", time() + (60 * 60 * 24));

@define("APP_VERSION", "%{APP_VERSION}%");

@define("APP_DEFAULT_PAGER_SIZE", 5);
@define("APP_DEFAULT_REFRESH_RATE", 5); // in minutes

// new users will use these for default preferences
@define("APP_DEFAULT_ASSIGNED_EMAILS", 1);// if the user will recieve an email when an issue is assigned to him
@define("APP_DEFAULT_NEW_EMAILS", 0);// if the user will recieve an email when ANY issue is created

@define("APP_CHARSET", "ISO-8859-1");

// define colors used by eventum
@define("APP_CELL_COLOR", "#255282");
@define("APP_LIGHT_COLOR", "#DDDDDD");
@define("APP_MIDDLE_COLOR", "#CACACA");
@define("APP_DARK_COLOR", "#CACACA");
@define("APP_CYCLE_COLORS", "#DDDDDD,#CACACA");
@define("APP_INTERNAL_COLOR", "#9C494B");

// define the user_id of system user
@define("APP_SYSTEM_USER_ID", 1);

// define the type of password hashing to use (MD5, MD5-64)
@define('APP_HASH_TYPE', 'MD5');

// if full text searching is enabled
@define("APP_ENABLE_FULLTEXT", '%{APP_ENABLE_FULLTEXT}%');

@define("APP_BENCHMARK", false);
if (APP_BENCHMARK) {
    // always benchmark the scripts
    include_once("Benchmark/Timer.php");
    $bench = new Benchmark_Timer;
    $bench->start();
}

include_once(APP_INC_PATH . "class.misc.php");

if (isset($_GET)) {
    $HTTP_POST_VARS = $_POST;
    $HTTP_GET_VARS = $_GET;
    $HTTP_SERVER_VARS = $_SERVER;
    $HTTP_ENV_VARS = $_ENV;
    $HTTP_POST_FILES = $_FILES;
    // seems like PHP 4.1.0 didn't implement the $_SESSION auto-global...
    if (isset($_SESSION)) {
        $HTTP_SESSION_VARS = $_SESSION;
    }
    $HTTP_COOKIE_VARS = $_COOKIE;
}
// fix magic_quote_gpc'ed values (i wish i knew who is the person behind this)
$HTTP_GET_VARS = Misc::dispelMagicQuotes($HTTP_GET_VARS);
$HTTP_POST_VARS = Misc::dispelMagicQuotes($HTTP_POST_VARS);
$_REQUEST = Misc::dispelMagicQuotes($_REQUEST);

// handle the language preferences now
@include_once(APP_INC_PATH . "class.language.php");
Language::setPreference();

// set charset
header("content-type: text/html;charset=" . APP_CHARSET);
?>