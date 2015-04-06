<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2015 Eventum Team.                              |
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
// | Authors: Bryan Alsdorf <bryan@mysql.com>                             |
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

if (!file_exists(dirname(__FILE__) . '/config/config.php') || !filesize(dirname(__FILE__) . '/config/config.php')) {
    Header('Location: setup/');
    exit(0);
}

// setup change some PHP settings
ini_set('memory_limit', '512M');

// prevent session from messing up the browser cache
ini_set('session.cache_limiter', 'nocache');

define('APP_URL', 'https://launchpad.net/eventum/');
define('APP_VERSION', '3.0.0-dev');

// define base path
define('APP_PATH', realpath(dirname(__FILE__)));
if (!defined('APP_CONFIG_PATH')) {
    define('APP_CONFIG_PATH', APP_PATH . '/config');
}

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
if (!defined('APP_LOCAL_PATH')) {
    define('APP_LOCAL_PATH', APP_CONFIG_PATH);
}

if (!defined('APP_COOKIE')) {
    define('APP_COOKIE', 'eventum');
}

// define other paths
if (!defined('APP_SETUP_FILE')) {
    define('APP_SETUP_FILE', APP_CONFIG_PATH . '/setup.php');
}

if (!defined('APP_TPL_PATH')) {
    define('APP_TPL_PATH', APP_PATH . '/templates');
}

if (!defined('APP_TPL_COMPILE_PATH')) {
    define('APP_TPL_COMPILE_PATH', APP_PATH . '/templates_c');
}

if (!defined('APP_INC_PATH')) {
    define('APP_INC_PATH', APP_PATH . '/lib/eventum');
}

if (!defined('APP_LOCKS_PATH')) {
    define('APP_LOCKS_PATH', APP_PATH . '/locks');
}

if (!defined('APP_LOG_PATH')) {
    define('APP_LOG_PATH', APP_PATH . '/logs');
}

if (!defined('APP_ERROR_LOG')) {
    define('APP_ERROR_LOG', APP_LOG_PATH . '/errors.log');
}

if (!defined('APP_CLI_LOG')) {
    define('APP_CLI_LOG', APP_LOG_PATH . '/cli.log');
}

if (!defined('APP_IRC_LOG')) {
    define('APP_IRC_LOG', APP_LOG_PATH . '/irc_bot.log');
}

if (!defined('APP_LOGIN_LOG')) {
    define('APP_LOGIN_LOG', APP_LOG_PATH . '/login_attempts.log');
}

// define the user_id of system user
if (!defined('APP_SYSTEM_USER_ID')) {
    define('APP_SYSTEM_USER_ID', 1);
}

// email address of anonymous user.
// if you want anonymous users getting access to your eventum.
if (!defined('APP_ANON_USER')) {
    define('APP_ANON_USER', '');
}

// if full text searching is enabled
if (!defined('APP_ENABLE_FULLTEXT')) {
    define('APP_ENABLE_FULLTEXT', false);
}

if (!defined('APP_FULLTEXT_SEARCH_CLASS')) {
    define('APP_FULLTEXT_SEARCH_CLASS', 'MySQL_Fulltext_Search');
}

if (!defined('APP_AUTH_BACKEND')) {
    define('APP_AUTH_BACKEND', 'Mysql_Auth_Backend');
}

if (!defined('APP_AUTH_BACKEND_ALLOW_FALLBACK')) {
    define('APP_AUTH_BACKEND_ALLOW_FALLBACK', false);
}

if (!defined('APP_DEFAULT_ASSIGNED_EMAILS')) {
    define('APP_DEFAULT_ASSIGNED_EMAILS', 1);
}
if (!defined('APP_DEFAULT_NEW_EMAILS')) {
    define('APP_DEFAULT_NEW_EMAILS', 0);
}
if (!defined('APP_DEFAULT_COPY_OF_OWN_ACTION')) {
    define('APP_DEFAULT_COPY_OF_OWN_ACTION', 0);
}
if (!defined('APP_RELATIVE_URL')) {
    define('APP_RELATIVE_URL', '/');
}
if (!defined('APP_COOKIE_URL')) {
    define('APP_COOKIE_URL', APP_RELATIVE_URL);
}
if (!defined('APP_COOKIE_DOMAIN')) {
    define('APP_COOKIE_DOMAIN', null);
}
if (!defined('APP_HASH_TYPE')) {
    define('APP_HASH_TYPE', 'MD5');
}
if (!defined('APP_DEFAULT_LOCALE')) {
    define('APP_DEFAULT_LOCALE', 'en_US');
}
if (!defined('APP_CHARSET')) {
    define('APP_CHARSET', 'UTF-8');
}
if (!defined('APP_EMAIL_ENCODING')) {
    if (APP_CHARSET == 'UTF-8') {
        define('APP_EMAIL_ENCODING', '8bit');
    } else {
        define('APP_EMAIL_ENCODING', '7bit');
    }
}
if (!defined('APP_DEFAULT_TIMEZONE')) {
    define('APP_DEFAULT_TIMEZONE', 'UTC');
}
if (!defined('APP_DEFAULT_WEEKDAY')) {
    define('APP_DEFAULT_WEEKDAY', 0);
}

// Number of failed attempts before Back-Off locking kicks in.
// If set to false do not use Back-Off locking.
if (!defined('APP_FAILED_LOGIN_BACKOFF_COUNT')) {
	define('APP_FAILED_LOGIN_BACKOFF_COUNT', false);
}
// How many minutes to lock account for during Back-Off
if (!defined('APP_FAILED_LOGIN_BACKOFF_MINUTES')) {
	define('APP_FAILED_LOGIN_BACKOFF_MINUTES', 15);
}

define('APP_HIDE_CLOSED_STATS_COOKIE', 'eventum_hide_closed_stats');

// if set, normal calls to eventum are redirected to a maintenance page while
// requests to /manage/ still work
if (!defined('APP_MAINTENANCE')) {
    define('APP_MAINTENANCE', false);
}

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

require_once APP_INC_PATH . '/gettext.php';
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
define("APP_EVENTUM_IRC_CATEGORY_DEFAULT", "default");

if (!defined('APP_EVENTUM_IRC_CATEGORY_REMINDER')) {
    define("APP_EVENTUM_IRC_CATEGORY_REMINDER", APP_EVENTUM_IRC_CATEGORY_DEFAULT);
}

// legacy constants, enable this block if you need time to migrate custom workflow, custom_field, customer, etc classes
/*
if (!defined('APP_DEFAULT_DB') || !defined('APP_TABLE_PREFIX')) {
    $dbconfig = DB_Helper::getConfig();
    if (!defined('APP_DEFAULT_DB')) {
        define('APP_DEFAULT_DB', $dbconfig['database']);
    }

    if (!defined('APP_TABLE_PREFIX')) {
        define('APP_TABLE_PREFIX', $dbconfig['table_prefix']);
    }
    unset($dbconfig);
}
*/
