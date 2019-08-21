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

/*
 * Constants here are internal and can not be overriden by installation
 */

use Eventum\Config\Paths;

$define = static function ($name, $value): void {
    if (defined($name)) {
        return;
    }
    define($name, $value);
};

/**
 * @deprecated this file won't be loaded in 3.9.0
 */
if (file_exists($configFile = Setup::getConfigPath() . '/config.php')) {
    require_once $configFile;
}

/**
 * @deprecated constants to be dropped in 3.9.0
 *
 * These may be present in workflow methods or in config/logger.php
 */
$define('APP_CACHE_PATH', Paths::APP_CACHE_PATH);
$define('APP_LOG_PATH', Paths::APP_LOG_PATH);
$define('APP_SYSTEM_USER_ID', Setup::getSystemUserId());
$define('APP_TPL_COMPILE_PATH', Paths::APP_TPL_COMPILE_PATH);
