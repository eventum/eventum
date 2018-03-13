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

// base path
define('APP_PATH', __DIR__);
define('APP_CONFIG_PATH', APP_PATH . '/config');
define('APP_INC_PATH', APP_PATH . '/lib/eventum');
define('APP_SETUP_FILE', APP_CONFIG_PATH . '/setup.php');

// /var path for writable data
define('APP_VAR_PATH', APP_PATH . '/var');

// define other paths
define('APP_TPL_PATH', APP_PATH . '/templates');
define('APP_TPL_COMPILE_PATH', APP_VAR_PATH . '/cache');
define('APP_LOG_PATH', APP_VAR_PATH . '/log');
define('APP_ERROR_LOG', APP_LOG_PATH . '/errors.log');
define('APP_LOCKS_PATH', APP_VAR_PATH . '/lock');
