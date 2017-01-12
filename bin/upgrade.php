#!/usr/bin/php
<?php
/**
 * Tool for helping Eventum upgrades.
 *
 * See our Wiki for documentation:
 * https://github.com/eventum/eventum/wiki/Upgrading
 */

use Symfony\Component\Console\Input\ArgvInput;

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

// run legacy eventum db updater
$app = new Eventum\Command\UpgradeCommand();
$app->run();

// run phinx based updater
chdir(__DIR__ . '/../res/config');

// emulate running "migrate" command
$input = new ArgvInput([$argv[0], 'migrate']);

$app = new Phinx\Console\PhinxApplication();
$app->run($input);
