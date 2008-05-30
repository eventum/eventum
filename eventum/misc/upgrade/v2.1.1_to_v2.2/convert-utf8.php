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

function apply_db_changes($stmts) {
	foreach ($stmts as $stmt) {
		$stmt = str_replace('%TABLE_PREFIX%', APP_TABLE_PREFIX, $stmt);
		$res = $GLOBALS['db_api']->dbh->query($stmt);
		if (PEAR::isError($res)) {
			echo $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
			exit(1);
		}
	}
}

$changes = array();
if (strtolower(APP_CHARSET) == 'utf-8' || strtolower(APP_CHARSET) == 'utf8') {
	$collation = 'latin1_swedish_ci'; // XXX autodetect

	// This command can generate the alter statement for all tables:
	$query = 
		"SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES ".
		"WHERE TABLE_SCHEMA = '".APP_SQL_DBNAME."' AND TABLE_COLLATION = '$collation'";
	$res = $GLOBALS['db_api']->dbh->getAll($query, DB_FETCHMODE_ASSOC);
	if (PEAR::isError($res)) {
		echo $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
		exit(1);
	}
	foreach ($res as $col => $val) {
		$changes[] = "ALTER TABLE {$val['TABLE_NAME']} CONVERT TO CHARACTER SET utf8";
	}
	// Alter database:
	$changes[] = 'ALTER DATABASE `'.APP_SQL_DBNAME.'` DEFAULT CHARACTER SET utf8';
}

echo "Performing database changes (", count($changes), ") queries\n";
apply_db_changes($changes);
echo "Done!\n";
