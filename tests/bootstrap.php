<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright 2011, Elan Ruusamäe <glen@delfi.ee>                        |
// | Copyright (c) 2011 - 2015 Eventum Team.                              |
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
// | 51 Franklin Street, Suite 330                                          |
// | Boston, MA 02110-1301, USA.                                          |
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
define('APP_DEFAULT_WEEKDAY', 1);
define('APP_DEFAULT_REFRESH_RATE', 0);
define('APP_DEFAULT_ASSIGNED_EMAILS', true);
define('APP_DEFAULT_NEW_EMAILS', false);
define('APP_DEFAULT_COPY_OF_OWN_ACTION', 0);

require_once APP_PATH . '/autoload.php';

require_once APP_INC_PATH . '/gettext.php';

// set default timezone
date_default_timezone_set(APP_DEFAULT_TIMEZONE);

// create dummy file
if (!file_exists(APP_SETUP_FILE)) {
    // create new config
    Setup::save(array(
        'database' => array(
            'driver' => 'mysqli',

            'hostname' => 'localhost',
            'database' => 'eventum',
            'username' => 'mysql',
            'password' => '',
            'port'     => 3306,
            'table_prefix' => 'eventum_',
        ),

        // used for tests
        'admin_user' => 2,
    ));
}

if (!getenv('TRAVIS')) {
    // init these from setup file
    $setup = &Setup::load(true);

    // used for tests
    define('APP_ADMIN_USER_ID', $setup['admin_user']);
}

// this setups ev_gettext wrappers
Language::setup();
