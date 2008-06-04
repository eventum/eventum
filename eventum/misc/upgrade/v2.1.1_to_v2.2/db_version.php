#!/usr/bin/php
<?php
// avoid setup redirecting us
define('INSTALL_PATH', realpath(dirname(__FILE__) . '/../../..'));
define('CONFIG_PATH', INSTALL_PATH.'/config');

if (!file_exists(CONFIG_PATH. '/config.php')) {
	die("Can't find config.php from ". CONFIG_PATH . ". Did you forgot to copy config from old install?");
}

require_once INSTALL_PATH . '/init.php';
require_once APP_INC_PATH . 'db_access.php';

function db_query($query) {
	$query = str_replace('%TABLE_PREFIX%', APP_TABLE_PREFIX, $query);
	$query = str_replace('%DBNAME%', APP_SQL_DBNAME, $query);
	$res = $GLOBALS['db_api']->dbh->query($query);
	if (PEAR::isError($res)) {
		echo $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
		exit(1);
	}
	return $res;
}

$stmts = array();

$stmts[] = "CREATE TABLE %TABLE_PREFIX%version (
    ver_version int(11) unsigned NOT NULL DEFAULT 0
) ENGINE = MYISAM DEFAULT CHARSET=utf8";
$stmts[] = "INSERT INTO %TABLE_PREFIX%version SET ver_version=0";

foreach ($stmts as $stmt) {
	db_query($stmt);
}

echo "done\n";
