<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright 2011, Elan Ruusamäe <glen@delfi.ee>                        |
// | Copyright (c) 2011 - 2014 Eventum Team.                              |
// +----------------------------------------------------------------------+
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

// we init paths ourselves like init.php does, to be independent and not
// needing actual config being present.
define('APP_PATH', realpath(dirname(__FILE__) . '/..'));
define('APP_CONFIG_PATH', dirname(__FILE__));
define('APP_SETUP_FILE', APP_CONFIG_PATH . '/_setup.php');
define('APP_ERROR_LOG', STDERR);
define('APP_INC_PATH', APP_PATH . '/lib/eventum');
define('APP_PEAR_PATH', APP_PATH . '/lib/pear');
define('APP_SYSTEM_USER_ID', 1);
define('APP_CHARSET', 'UTF-8');
define('APP_DEFAULT_LOCALE', 'en_US');
define('APP_HOSTNAME', 'eventum.example.org');
define('APP_LOCKS_PATH', sys_get_temp_dir());
define('APP_COOKIE', 'eventum');
define('APP_DEFAULT_TIMEZONE', 'UTC');
//define('APP_DEFAULT_TIMEZONE', 'Europe/Tallinn');
define('APP_DEFAULT_WEEKDAY', 1);
define('APP_DEFAULT_REFRESH_RATE', 0);

// add pear to the include path
if (defined('APP_PEAR_PATH') && APP_PEAR_PATH) {
    set_include_path(APP_PEAR_PATH . PATH_SEPARATOR . get_include_path());
}

// emulate gettext
if (!extension_loaded('gettext')) {
    define('APP_PHP_GETTEXT_PATH', APP_PATH . '/lib/php-gettext');
    require_once APP_INC_PATH . '/gettext.php';
}

if (file_exists($autoload = APP_PATH . '/vendor/autoload.php')) {
    // composer paths
    require_once $autoload;
    define('APP_SMARTY_PATH', APP_PATH . '/vendor/smarty/smarty/distribution/libs');
    define('APP_SPHINXAPI_PATH', APP_PATH . '/vendor/sphinx/php-sphinxapi');
} else {
    require_once APP_PATH . '/vendor/autoload-dist.php';
}

require_once APP_INC_PATH . '/gettext.php';

// create dummy file
if (!file_exists(APP_SETUP_FILE)) {
    // try grab params from existing config. hide constant redefined warnings
    @include APP_PATH . '/config/config.php';
    Setup::save(array(
        'db' => array(
            'table_prefix' => APP_TABLE_PREFIX,
            'dbtype' => APP_SQL_DBTYPE,
            'host' => APP_SQL_DBHOST,
            'database' => APP_SQL_DBNAME,
            'user' => APP_SQL_DBUSER,
            'password' => APP_SQL_DBPASS,
        ),

        // used for tests
        'admin_user' => 2,
    ));
}

if (!getenv('TRAVIS')) {
    // init these from setup file
    $setup = &Setup::load(true);

    define('APP_DEFAULT_DB', $setup['db']['database']);
    define('APP_TABLE_PREFIX', $setup['db']['table_prefix']);
    define('APP_SQL_DBTYPE', $setup['db']['dbtype']);
    define('APP_SQL_DBHOST', $setup['db']['host']);
    define('APP_SQL_DBNAME', $setup['db']['database']);
    define('APP_SQL_DBUSER', $setup['db']['user']);
    define('APP_SQL_DBPASS', $setup['db']['password']);

    // used for tests
    define('APP_ADMIN_USER_ID', $setup['admin_user']);
}

// this setups ev_gettext wrappers
Language::setup();
