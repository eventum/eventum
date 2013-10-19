<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2013 Eventum Team.                              |
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
// | Authors: Bryan Alsdorf <bryan@mysql.com>                             |
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

if (!file_exists(dirname(__FILE__) . '/config/config.php') || !filesize(dirname(__FILE__) . '/config/config.php')) {
    Header('Location: setup/');
    exit(0);
}

// setup change some PHP settings
ini_set('allow_url_fopen', 0);
set_time_limit(0);
ini_set('memory_limit', '128M');

// prevent session from messing up the browser cache
ini_set('session.cache_limiter', 'nocache');

define('APP_URL', 'https://launchpad.net/eventum/');
define('APP_VERSION', '2.3.3');

// define base path
define('APP_PATH', realpath(dirname(__FILE__)));
if (!defined('APP_CONFIG_PATH')) {
    define('APP_CONFIG_PATH', APP_PATH . '/config');
}

// include local site config. may override any default
require_once APP_CONFIG_PATH . '/config.php';

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

if (!defined('APP_PEAR_PATH')) {
    define('APP_PEAR_PATH', APP_PATH . '/lib/pear');
}

if (!defined('APP_SPHINXAPI_PATH')) {
    define('APP_SPHINXAPI_PATH', APP_PATH . '/lib/sphinxapi');
}

if (!defined('APP_SMARTY_PATH')) {
    define('APP_SMARTY_PATH', APP_PATH . '/lib/Smarty');
}

if (!defined('APP_JPGRAPH_PATH')) {
    define('APP_JPGRAPH_PATH', APP_PATH . '/lib/jpgraph');
}

if (!defined('APP_LOCKS_PATH')) {
    define('APP_LOCKS_PATH', APP_PATH . '/locks');
}

if (!defined('APP_SQL_PATCHES_PATH')) {
    define('APP_SQL_PATCHES_PATH', APP_PATH . '/upgrade/patches');
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
    define('APP_FULLTEXT_SEARCH_CLASS', 'mysql_fulltext_search');
}

if (!defined('APP_AUTH_BACKEND')) {
    define('APP_AUTH_BACKEND', 'mysql_auth_backend');
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

// add pear to the include path
if (defined('APP_PEAR_PATH') && APP_PEAR_PATH) {
    set_include_path(APP_PEAR_PATH . PATH_SEPARATOR . get_include_path());
}
// add sphinxapi to the include path
if (defined('APP_SPHINXAPI_PATH') && APP_SPHINXAPI_PATH) {
    set_include_path(APP_SPHINXAPI_PATH . PATH_SEPARATOR . get_include_path());
}

require_once APP_INC_PATH . '/autoload.php';

// fix magic_quote_gpc'ed values
if (get_magic_quotes_gpc()) {
    $_GET = Misc::dispelMagicQuotes($_GET);
    $_POST = Misc::dispelMagicQuotes($_POST);
    $_REQUEST = Misc::dispelMagicQuotes($_REQUEST);
}

Language::setup();

// set charset
Header('Content-Type: text/html; charset=' . APP_CHARSET);

// display maintenance message if requested.
if (APP_MAINTENANCE){
    $is_manage = (strpos($_SERVER['PHP_SELF'],'/manage/') !== false);
    if (APP_MAINTENANCE && !$is_manage) {
        $tpl = new Template_Helper();
        $tpl->setTemplate("maintenance.tpl.html");
        $tpl->displayTemplate();
        exit(0);
	}
}

// Default IRC category
define("APP_EVENTUM_IRC_CATEGORY_DEFAULT", "default");
