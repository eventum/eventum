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

use Eventum\Config\Config;
use Eventum\Config\ConfigPersistence;
use Eventum\Monolog\Logger;
use Eventum\ServiceContainer;

$_ENV['APP_ENV'] = 'test';

require_once __DIR__ . '/../autoload.php';

// set default timezone
$config = ServiceContainer::getConfig();
$config['default_timezone'] = 'Europe/Tallinn';
date_default_timezone_set(Setup::getDefaultTimezone());

if (!getenv('TRAVIS')) {
    // override with test setup, if present
    $testSetupConfig = __DIR__ . '/_setup.php';
    if (file_exists($testSetupConfig)) {
        $loader = new ConfigPersistence();
        $config->merge(new Config($loader->load($testSetupConfig)));
    }
}

if (!file_exists($privateKeyFile = Setup::getPrivateKeyPath()) || !filesize($privateKeyFile)) {
    Auth::generatePrivateKey();
}

// this setups ev_gettext wrappers
Language::setup();
Logger::initialize();
