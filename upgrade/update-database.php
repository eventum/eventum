#!/usr/bin/php
<?php
// common init for upgrade scripts
define('INSTALL_PATH', __DIR__ . '/..');
define('CONFIG_PATH', INSTALL_PATH . '/config');

// avoid setup redirecting us
if (!file_exists(CONFIG_PATH . '/setup.php') || !filesize(CONFIG_PATH . '/setup.php') || !is_readable(CONFIG_PATH . '/setup.php')) {
    error_log("ERROR: Can't get setup.php in '" . CONFIG_PATH . "'");
    error_log('Did you forgot to copy config from old install? Is file readable?');
    exit(1);
}

// load init only if no autoloader present
if (!class_exists('DB_Helper')) {
    require_once INSTALL_PATH . '/init.php';
}

$in_setup = defined('IN_SETUP');

if (!$in_setup && php_sapi_name() != 'cli') {
    echo "<pre>\n";
}

try {
    $dbmigrate = new DbMigrate(__DIR__);
    $dbmigrate->patch_database();
} catch (Exception $e) {
    if ($in_setup) {
        throw $e;
    }
    echo $e->getMessage(), "\n";
    exit(1);
}

if (!$in_setup && php_sapi_name() != 'cli') {
    echo "</pre>\n";
}
