#!/usr/bin/php
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

define('INSTALL_PATH', __DIR__ . '/..');
define('CONFIG_PATH', INSTALL_PATH . '/config');

// avoid init.php redirecting us to setup if not configured yet
$setup_path = CONFIG_PATH . '/setup.php';
if (!file_exists($setup_path) || !filesize($setup_path) || !is_readable($setup_path)) {
    // make path absolute first for readable error messages
    $setup_path = realpath($setup_path);
    error_log("ERROR: $setup_path does not exist, is not readable, or is an empty file.");
    error_log('Did you forgot to copy config from old install?');
    exit(1);
}

require_once INSTALL_PATH . '/init.php';

// run phinx based updater
chdir(__DIR__ . '/..');

$app = new Phinx\Console\PhinxApplication();
$app->setDefaultCommand('migrate');
$app->run();
