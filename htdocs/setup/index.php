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

// XXX: try reading $_ENV['HOSTNAME'] and then ask the user if nothing could be found
// XXX: dynamically check the email blob and skips the email if it is bigger than 16MB on PHP4 versions

ini_set('memory_limit', '64M');
ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_STRICT);
set_time_limit(0);

require_once __DIR__ . '/../../globals.php';

define('APP_CHARSET', 'UTF-8');

header('Content-Type: text/html; charset=' . APP_CHARSET);

$have_config = file_exists(APP_CONFIG_PATH . '/config.php') && filesize(APP_CONFIG_PATH . '/config.php');
// get out if already configured
if ($have_config) {
    header('Location: ../');
    exit(0);
}

require_once APP_PATH . '/autoload.php';

// set default timezone to utc to avoid default timezone not set warnings
date_default_timezone_set(@date_default_timezone_get());

define('APP_NAME', 'Eventum');
define('APP_DEFAULT_LOCALE', 'en_US');
define('APP_VAR_PATH', APP_PATH . '/var');
define('APP_INC_PATH', APP_PATH . '/lib/eventum');
define('APP_SETUP_FILE', APP_CONFIG_PATH . '/setup.php');
define('APP_TPL_PATH', APP_PATH . '/templates');
define('APP_TPL_COMPILE_PATH', APP_VAR_PATH . '/cache');
define('APP_LOG_PATH', APP_VAR_PATH . '/log');
define('APP_ERROR_LOG', APP_LOG_PATH . '/errors.log');
define('APP_LOCKS_PATH', APP_VAR_PATH . '/lock');
define('APP_LOCAL_PATH', APP_CONFIG_PATH);
define('APP_RELATIVE_URL', '../');
define('APP_SITE_NAME', 'Eventum');
define('APP_COOKIE', 'eventum');

$controller = new Eventum\Controller\Setup\SetupController();
$controller->run();
