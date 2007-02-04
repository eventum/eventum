<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005, 2006, 2007 MySQL AB                  |
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

if (!file_exists(dirname(__FILE__) . '/config/config.php')) {
    Header('Location: setup/');
    exit;
}

// setup change some PHP settings
ini_set('allow_url_fopen', 0);
set_time_limit(0);
set_magic_quotes_runtime(0);

// prevent session from messing up the browser cache
ini_set('session.cache_limiter', 'nocache');

define('APP_URL', 'http://www.mysql.com/products/eventum/');
define('APP_VERSION', '2.0-alpha');

// define base path
define('APP_PATH', realpath(dirname(__FILE__)) . '/');
define('APP_CONFIG_PATH', APP_PATH . 'config/');

// include local site config
require_once APP_CONFIG_PATH . 'config.php';

// define other paths
if (!defined('APP_INC_PATH')) {
    define('APP_INC_PATH', APP_PATH . 'include/');
}

if (!defined('APP_PEAR_PATH')) {
    define('APP_PEAR_PATH', APP_INC_PATH . 'pear/');
}

if (!defined('APP_TPL_PATH')) {
    define('APP_TPL_PATH', APP_PATH . 'templates/');
}

if (!defined('APP_TPL_COMPILE_PATH')) {
    define('APP_TPL_COMPILE_PATH', APP_PATH . 'templates_c');
}

if (!defined('APP_SMARTY_PATH')) {
    define('APP_SMARTY_PATH', APP_INC_PATH . 'Smarty/');
}

if (!defined('APP_JPGRAPH_PATH')) {
    define('APP_JPGRAPH_PATH', APP_INC_PATH . 'jpgraph/');
}

if (!defined('APP_LOCKS_PATH')) {
    define('APP_LOCKS_PATH', APP_PATH . 'locks/');
}

if (!defined('APP_SETUP_FILE')) {
    define('APP_SETUP_FILE', APP_CONFIG_PATH . 'setup.php');
}

if (!defined('APP_LOG_PATH')) {
    define('APP_LOG_PATH', APP_PATH . 'logs/');
}

if (!defined('APP_ROUTED_MAILS_SAVEDIR')) {
    define('APP_ROUTED_MAILS_SAVEDIR', APP_PATH . 'misc/');
}

if (!defined('APP_ERROR_LOG')) {
    define('APP_ERROR_LOG', APP_LOG_PATH . 'errors.log');
}

if (!defined('APP_CLI_LOG')) {
    define('APP_CLI_LOG', APP_LOG_PATH . 'cli.log');
}

if (!defined('APP_IRC_LOG')) {
    define('APP_IRC_LOG', APP_LOG_PATH . 'irc_bot.log');
}

if (!defined('APP_LOGIN_LOG')) {
    define('APP_LOGIN_LOG', APP_LOG_PATH . 'login_attempts.log');
}

// add pear to the include path
set_include_path(get_include_path() . PATH_SEPARATOR . APP_PEAR_PATH);

// define the user_id of system user
if (!defined('APP_SYSTEM_USER_ID')) {
    define('APP_SYSTEM_USER_ID', 1);
}

// if full text searching is enabled
if (!defined('APP_ENABLE_FULLTEXT')) {
    define('APP_ENABLE_FULLTEXT', false);
}

if (!defined('APP_BENCHMARK')) {
    define('APP_BENCHMARK', false);
}

if (!defined('APP_DEFAULT_ASSIGNED_EMAILS')) {
    define('APP_DEFAULT_ASSIGNED_EMAILS', 1);
}
if (!defined('APP_DEFAULT_NEW_EMAILS')) {
    define('APP_DEFAULT_NEW_EMAILS', 0);
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
if (!defined('APP_EMAIL_ENCODING')) {
    if (APP_CHARSET == 'UTF-8') {
        define('APP_EMAIL_ENCODING', '8bit');
    } else {
        define('APP_EMAIL_ENCODING', '7bit');
    }
}

if (APP_BENCHMARK) {
    // always benchmark the scripts
    require_once 'Benchmark/Timer.php';
    $bench = new Benchmark_Timer;
    $bench->start();
}

// handle the language preferences now
$avail_langs = array(
    'en_US' =>  'English',
#    'ru_RU' =>  'Russian',
#    'de_DE' =>  'German',
#    'fr_FR' =>  'French',
    'it_IT' =>  'Italian',
#    'fi_FI' =>  'Finish',
#    'es_ES' =>  'Spanish',
#    'nl_NL' =>  'Dutch',
    'sv_SE' =>  'Swedish',
);

include_once(APP_INC_PATH . 'class.language.php');
include_once(APP_INC_PATH . 'db_access.php');
include_once(APP_INC_PATH . 'class.auth.php');
include_once(APP_INC_PATH . 'class.misc.php');

// fix magic_quote_gpc'ed values
if (get_magic_quotes_gpc()) {
    $_GET = Misc::dispelMagicQuotes($_GET);
    $_POST = Misc::dispelMagicQuotes($_POST);
    $_REQUEST = Misc::dispelMagicQuotes($_REQUEST);
}

Language::setup();

// set charset
Header('Content-Type: text/html; charset=' . APP_CHARSET);
