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

use Eventum\Event\SystemEvents;

if (!file_exists(__DIR__ . '/config/config.php') || !filesize(__DIR__ . '/config/config.php')) {
    // redirect to setup
    if (PHP_SAPI === 'cli') {
        throw new RuntimeException('Eventum is not configured');
    }
    header('Location: setup/');
    exit(0);
}

// setup change some PHP settings
ini_set('memory_limit', '512M');

// prevent session from messing up the browser cache
ini_set('session.cache_limiter', 'nocache');

require_once __DIR__ . '/globals.php';

$define = function ($name, $value): void {
    if (defined($name)) {
        return;
    }
    define($name, $value);
};

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

// define the user_id of system user
$define('APP_SYSTEM_USER_ID', 1);

// email address of anonymous user.
// if you want anonymous users getting access to your eventum.
$define('APP_ANON_USER', '');

// if full text searching is enabled
$define('APP_ENABLE_FULLTEXT', false);
$define('APP_FULLTEXT_SEARCH_CLASS', 'MySQL_Fulltext_Search');

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
    if (APP_CHARSET === 'UTF-8') {
        define('APP_EMAIL_ENCODING', '8bit');
    } else {
        define('APP_EMAIL_ENCODING', '7bit');
    }
}

$define('APP_HIDE_CLOSED_STATS_COOKIE', 'eventum_hide_closed_stats');

// if set, normal calls to eventum are redirected to a maintenance page while
// requests to /manage/ still work
$define('APP_MAINTENANCE', false);

require_once APP_PATH . '/autoload.php';

Misc::stripInput($_POST);

// set default timezone
date_default_timezone_set(APP_DEFAULT_TIMEZONE);

Eventum\Monolog\Logger::initialize();
Language::setup();

// set charset
header('Content-Type: text/html; charset=' . APP_CHARSET);

// display maintenance message if requested.
if (APP_MAINTENANCE) {
    $is_manage = (strpos($_SERVER['PHP_SELF'], '/manage/') !== false);
    if (!$is_manage) {
        $tpl = new Template_Helper();
        $tpl->setTemplate('maintenance.tpl.html');
        $tpl->displayTemplate();
        exit(0);
    }
}

Eventum\DebugBarManager::getDebugBarManager();
Eventum\Extension\ExtensionManager::getManager();
Eventum\EventDispatcher\EventManager::getEventDispatcher()->dispatch(SystemEvents::BOOT);
