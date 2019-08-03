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
use Eventum\Config\Config;
use Eventum\Config\ConfigPersistence;
use Eventum\Monolog\Logger;

define('APP_PATH', dirname(__DIR__));
define('APP_CONFIG_PATH', __DIR__);
define('APP_VAR_PATH', APP_PATH . '/var');
// FIXME: HHVM: Warning: Constants may only evaluate to scalar values
define('APP_ERROR_LOG', STDERR);
define('APP_INC_PATH', APP_PATH . '/lib/eventum');
define('APP_HOSTNAME', 'eventum.example.org');
define('APP_LOCKS_PATH', sys_get_temp_dir());
define('APP_RELATIVE_URL', '/eventum/');
define('APP_COOKIE_DOMAIN', null);
define('APP_COOKIE_URL', APP_RELATIVE_URL);
define('APP_BASE_URL', 'http://localhost/');
define('APP_LOG_PATH', __DIR__);
define('APP_LOCAL_PATH', __DIR__);
define('APP_CACHE_PATH', APP_VAR_PATH . '/test');
define('APP_TPL_COMPILE_PATH', APP_CACHE_PATH . '/tpl_c');
define('APP_TPL_PATH', APP_PATH . '/templates');
define('APP_NAME', 'Eventum Tests');

require_once APP_PATH . '/autoload.php';

// set default timezone
date_default_timezone_set(Date_Helper::getDefaultTimezone());

if (!getenv('TRAVIS')) {
    $config = Setup::get();

    // override with test setup, if present
    $testSetupConfig = __DIR__ . '/_setup.php';
    if (file_exists($testSetupConfig)) {
        $loader = new ConfigPersistence();
        $config->merge(new Config($loader->load($testSetupConfig)));
    }

    // used for tests
    define('APP_ADMIN_USER_ID', $config['admin_user']);
}

// this setups ev_gettext wrappers
Language::setup();
Logger::initialize();
