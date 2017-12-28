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

// we init paths ourselves like init.php does, to be independent and not
// needing actual config being present.
use Eventum\Monolog\Logger;

define('APP_PATH', dirname(__DIR__));
define('APP_CONFIG_PATH', __DIR__);
define('APP_VAR_PATH', APP_PATH . '/var');
define('APP_SETUP_FILE', APP_CONFIG_PATH . '/_setup.php');
// FIXME: HHVM: Warning: Constants may only evaluate to scalar values
define('APP_ERROR_LOG', STDERR);
define('APP_INC_PATH', APP_PATH . '/lib/eventum');
define('APP_SYSTEM_USER_ID', 1);
define('APP_CHARSET', 'UTF-8');
define('APP_EMAIL_ENCODING', APP_CHARSET);
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
define('APP_RELATIVE_URL', '/eventum/');
define('APP_COOKIE_DOMAIN', null);
define('APP_COOKIE_EXPIRE', time() + (60 * 60 * 8));
define('APP_COOKIE_URL', APP_RELATIVE_URL);
define('APP_PROJECT_COOKIE', 'eventum_project');
define('APP_PROJECT_COOKIE_EXPIRE', time() + (60 * 60 * 24));
define('APP_BASE_URL', 'http://localhost');
define('APP_LOG_PATH', APP_CONFIG_PATH);
define('APP_LOCAL_PATH', APP_CONFIG_PATH);
define('APP_TPL_COMPILE_PATH', APP_CONFIG_PATH . '/tpl_c');
define('APP_TPL_PATH', APP_PATH . '/templates');
define('APP_NAME', 'Eventum Tests');
define('APP_AUTH_BACKEND', 'mysql_auth_backend');
define('APP_SITE_NAME', 'Eventum');

require_once APP_PATH . '/autoload.php';

// set default timezone
date_default_timezone_set(APP_DEFAULT_TIMEZONE);

// create dummy file
if (!file_exists(APP_SETUP_FILE)) {
    // create new config
    Setup::save([
        'database' => [
            'hostname' => 'localhost',
            'database' => 'eventum',
            'username' => 'mysql',
            'password' => '',
            'port' => 3306,
        ],

        // used for tests
        'admin_user' => 2,
    ]);
}

if (!getenv('TRAVIS')) {
    // init these from setup file
    $setup = Setup::get();

    // used for tests
    define('APP_ADMIN_USER_ID', $setup['admin_user']);
}

// this setups ev_gettext wrappers
Language::setup();
Logger::initialize();
