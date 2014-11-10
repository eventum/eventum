<?php
// common init for upgrade scripts
define('INSTALL_PATH', realpath(dirname(__FILE__) . '/..'));
define('CONFIG_PATH', INSTALL_PATH . '/config');

// avoid setup redirecting us
if (!file_exists(CONFIG_PATH . '/setup.php') || !filesize(CONFIG_PATH . '/setup.php') || !is_readable(CONFIG_PATH . '/setup.php')) {
    error_log("ERROR: Can't get setup.php in '" . CONFIG_PATH . "'");
    error_log("Did you forgot to copy config from old install? Is file readable?");
    exit(1);
}

require_once INSTALL_PATH . '/init.php';
