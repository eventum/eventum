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

if (!file_exists(__DIR__ . '/config/config.php') || !filesize(__DIR__ . '/config/config.php')) {
    header('Location: setup/');
    exit(0);
}

// setup change some PHP settings
ini_set('memory_limit', '512M');

// prevent session from messing up the browser cache
ini_set('session.cache_limiter', 'nocache');

define('APP_URL', 'https://github.com/eventum/eventum');
define('APP_VERSION', '3.0.10-dev');

// define base path
define('APP_PATH', __DIR__);

$define = function ($name, $value) {
    if (defined($name)) {
        return;
    }
    define($name, $value);
};

$define('APP_CONFIG_PATH', APP_PATH . '/config');

// include local site config. may override any default
require_once APP_CONFIG_PATH . '/config.php';

/**
 * Path for local overrides:
 * APP_LOCAL_PATH/crm
 * APP_LOCAL_PATH/custom_field
 * APP_LOCAL_PATH/include
 * APP_LOCAL_PATH/partner
 * APP_LOCAL_PATH/templates
 * APP_LOCAL_PATH/workflow
 */
$define('APP_LOCAL_PATH', APP_CONFIG_PATH);
$define('APP_COOKIE', 'eventum');

// /var path for writable data
$define('APP_VAR_PATH', APP_PATH . '/var');

// define other paths
$define('APP_SETUP_FILE', APP_CONFIG_PATH . '/setup.php');
$define('APP_INC_PATH', APP_PATH . '/lib/eventum');
$define('APP_TPL_PATH', APP_PATH . '/templates');
$define('APP_TPL_COMPILE_PATH', APP_VAR_PATH . '/cache');
$define('APP_LOCKS_PATH', APP_VAR_PATH . '/lock');
$define('APP_LOG_PATH', APP_VAR_PATH . '/log');
$define('APP_ERROR_LOG', APP_LOG_PATH . '/errors.log');

// define the user_id of system user
$define('APP_SYSTEM_USER_ID', 1);

// email address of anonymous user.
// if you want anonymous users getting access to your eventum.
$define('APP_ANON_USER', '');

// if full text searching is enabled
$define('APP_ENABLE_FULLTEXT', false);
$define('APP_FULLTEXT_SEARCH_CLASS', 'MySQL_Fulltext_Search');

$define('APP_AUTH_BACKEND', 'Mysql_Auth_Backend');

$define('APP_AUTH_BACKEND_ALLOW_FALLBACK', false);
$define('APP_DEFAULT_ASSIGNED_EMAILS', 1);
$define('APP_DEFAULT_NEW_EMAILS', 0);
$define('APP_DEFAULT_COPY_OF_OWN_ACTION', 0);
$define('APP_RELATIVE_URL', '/');
$define('APP_COOKIE_URL', APP_RELATIVE_URL);
$define('APP_COOKIE_DOMAIN', null);
$define('APP_DEFAULT_LOCALE', 'en_US');
$define('APP_CHARSET', 'UTF-8');
$define('APP_DEFAULT_TIMEZONE', 'UTC');
$define('APP_DEFAULT_WEEKDAY', 0);

if (!defined('APP_EMAIL_ENCODING')) {
    if (APP_CHARSET == 'UTF-8') {
        define('APP_EMAIL_ENCODING', '8bit');
    } else {
        define('APP_EMAIL_ENCODING', '7bit');
    }
}

// Number of failed attempts before Back-Off locking kicks in.
// If set to false do not use Back-Off locking.
$define('APP_FAILED_LOGIN_BACKOFF_COUNT', false);
// How many minutes to lock account for during Back-Off
$define('APP_FAILED_LOGIN_BACKOFF_MINUTES', 15);

$define('APP_HIDE_CLOSED_STATS_COOKIE', 'eventum_hide_closed_stats');

// if set, normal calls to eventum are redirected to a maintenance page while
// requests to /manage/ still work
$define('APP_MAINTENANCE', false);

require_once APP_PATH . '/autoload.php';

// fix magic_quote_gpc'ed values
if (get_magic_quotes_gpc()) {
    $_GET = Misc::dispelMagicQuotes($_GET);
    $_POST = Misc::dispelMagicQuotes($_POST);
    $_REQUEST = Misc::dispelMagicQuotes($_REQUEST);
}

Misc::stripInput($_POST);

// set default timezone
date_default_timezone_set(APP_DEFAULT_TIMEZONE);

Logger::initialize();
Language::setup();

// set charset
header('Content-Type: text/html; charset=' . APP_CHARSET);

// display maintenance message if requested.
if (APP_MAINTENANCE) {
    $is_manage = (strpos($_SERVER['PHP_SELF'], '/manage/') !== false);
    if (APP_MAINTENANCE && !$is_manage) {
        $tpl = new Template_Helper();
        $tpl->setTemplate("maintenance.tpl.html");
        $tpl->displayTemplate();
        exit(0);
    }
}

// Default IRC category
$define("APP_EVENTUM_IRC_CATEGORY_DEFAULT", "default");
$define("APP_EVENTUM_IRC_CATEGORY_REMINDER", APP_EVENTUM_IRC_CATEGORY_DEFAULT);

// legacy constants, enable this block if you need time to migrate custom workflow, custom_field, customer, etc classes
/*
if (!defined('APP_DEFAULT_DB') || !defined('APP_TABLE_PREFIX')) {
    $dbconfig = DB_Helper::getConfig();
    $define('APP_DEFAULT_DB', $dbconfig['database']);
    $define('APP_TABLE_PREFIX', $dbconfig['table_prefix']);
    unset($dbconfig);
}
*/

Eventum\DebugBar::initialize();
