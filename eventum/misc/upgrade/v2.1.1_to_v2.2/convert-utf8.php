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

function db_getAll($query) {
	$query = str_replace('%TABLE_PREFIX%', APP_TABLE_PREFIX, $query);
	$query = str_replace('%DBNAME%', APP_SQL_DBNAME, $query);
	$res = $GLOBALS['db_api']->dbh->getAll($query, DB_FETCHMODE_ASSOC);
	if (PEAR::isError($res)) {
		echo $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
		exit(1);
	}
	return $res;
}

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

function apply_db_changes($stmts) {
	foreach ($stmts as $stmt) {
		db_query($stmt);
	}
}


$changes = array();
if (strtolower(APP_CHARSET) == 'utf-8' || strtolower(APP_CHARSET) == 'utf8') {
	// convert tables
	$res = db_getAll(
		"SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES ".
		"WHERE TABLE_SCHEMA='%DBNAME%' AND TABLE_COLLATION NOT LIKE 'utf8\_%' AND TABLE_TYPE = 'BASE TABLE'"
	);
	foreach ($res as $idx => $row) {
		$changes[] = "ALTER TABLE {$row['TABLE_NAME']} CONVERT TO CHARACTER SET utf8";
	}

	// convert database:
	$changes[] = 'ALTER DATABASE `%DBNAME%` DEFAULT CHARACTER SET utf8';
}

echo "Performing database changes (", count($changes), ") queries\n";
apply_db_changes($changes);
echo "Done!\n";
