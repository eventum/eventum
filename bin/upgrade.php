#!/usr/bin/php
<?php
/**
 * Tool for helping Eventum upgrades.
 *
 * See our Wiki for documentation:
 * https://github.com/eventum/eventum/wiki/Upgrading
 */

define('INSTALL_PATH', __DIR__ . '/..');
define('CONFIG_PATH', INSTALL_PATH . '/config');

// avoid init.php redirecting us to setup if not configured yet
$setup_path = CONFIG_PATH . '/setup.php';
if (!file_exists($setup_path) || !filesize($setup_path) || !is_readable($setup_path)) {
    error_log("ERROR: Can't get setup.php in '" . CONFIG_PATH . "'");
    error_log('Did you forgot to copy config from old install? Is file readable?');
    exit(1);
}

require_once INSTALL_PATH . '/init.php';

try {
    $dbmigrate = new DbMigrate(INSTALL_PATH . '/upgrade');
    $dbmigrate->patch_database();
} catch (Exception $e) {
    echo $e->getMessage(), "\n";
    exit(1);
}
