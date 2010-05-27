<?php
// common init for upgrade scripst
define('INSTALL_PATH', realpath(dirname(__FILE__) . '/..'));
define('CONFIG_PATH', INSTALL_PATH.'/config');

// avoid setup redirecting us
if (!file_exists(CONFIG_PATH. '/config.php') || !filesize(CONFIG_PATH. '/config.php')) {
	fwrite(STDERR, "Can't find config.php in '". CONFIG_PATH. "'. Did you forgot to copy config from old install?\n");
	exit(1);
}

require_once INSTALL_PATH . '/init.php';
