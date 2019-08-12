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

require_once __DIR__ . '/globals.php';

if (!file_exists(APP_CONFIG_PATH . '/setup.php') || !filesize(APP_CONFIG_PATH . '/setup.php')) {
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

require_once APP_CONFIG_PATH . '/config.php';
require_once APP_PATH . '/autoload.php';

Misc::stripInput($_POST);

// set default timezone
date_default_timezone_set(Date_Helper::getDefaultTimezone());

Eventum\Monolog\Logger::initialize();
Language::setup();

// set charset
header('Content-Type: text/html; charset=UTF-8');

// display maintenance message if requested.
if (Setup::get()['maintenance']) {
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
Eventum\EventDispatcher\EventManager::dispatch(SystemEvents::BOOT);
