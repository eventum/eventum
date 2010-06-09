<?php
// common init for upgrade scripst
define('INSTALL_PATH', realpath(dirname(__FILE__) . '/..'));
define('CONFIG_PATH', INSTALL_PATH.'/config');

// avoid setup redirecting us
if (!file_exists(CONFIG_PATH. '/config.php') || !filesize(CONFIG_PATH. '/config.php') || !is_readable(CONFIG_PATH. '/config.php')) {
	error_log("ERROR: Can't get config.php in '". CONFIG_PATH. "'");
   	error_log("Did you forgot to copy config from old install? Is file readable?");
	exit(1);
}

require_once INSTALL_PATH . '/init.php';
