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
use Eventum\Extension\ExtensionManager;
use Eventum\ServiceContainer;

require_once __DIR__ . '/autoload.php';

if (Setup::needsSetup()) {
    // redirect to setup
    if (PHP_SAPI === 'cli') {
        throw new RuntimeException('Eventum is not configured, setup file is missing');
    }
    header('Location: setup/');
    exit(0);
}

// setup change some PHP settings
ini_set('memory_limit', '512M');

// prevent session from messing up the browser cache
ini_set('session.cache_limiter', 'nocache');

Misc::stripInput($_POST);

// set default timezone
date_default_timezone_set(Setup::getDefaultTimezone());

Eventum\Monolog\Logger::initialize();
Language::setup();

// set charset
header('Content-Type: text/html; charset=UTF-8');

// display maintenance message if requested.
if (Setup::isMaintenance()) {
    $is_manage = (strpos($_SERVER['PHP_SELF'], '/manage/') !== false);
    if (!$is_manage) {
        $tpl = new Template_Helper();
        $tpl->setTemplate('maintenance.tpl.html');
        $tpl->displayTemplate();
        exit(0);
    }
}

Eventum\DebugBarManager::getDebugBarManager();
ServiceContainer::get(ExtensionManager::class);
Eventum\EventDispatcher\EventManager::dispatch(SystemEvents::BOOT);
