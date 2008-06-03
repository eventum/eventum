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


/*
 * Convert database to real charset with wrong charset defined in schema.
 * I.e.: you data in database is cp1257, but column type is latin1.
 */

// the charset your data is
$in_charset = 'cp1257';
// the charset your tables are
$db_charset = 'latin1';

$changes = array();

$res = db_getAll(
	"SELECT TABLE_NAME,COLUMN_NAME,COLUMN_TYPE,COLUMN_KEY FROM INFORMATION_SCHEMA.COLUMNS ".
	"WHERE TABLE_SCHEMA='%DBNAME%' AND CHARACTER_SET_NAME='$db_charset'"
);

$tables = array();
foreach ($res as $idx => $row) {
	$col = $row['COLUMN_NAME'];
	$convert = "CONVERT(CONVERT(CONVERT(CONVERT($col USING utf8) using $db_charset) using binary) using $in_charset)";
	$tables[$row['TABLE_NAME']][] = "$col=$convert";
}

foreach ($tables as $table => $column) {
	$changes[] = "ALTER TABLE $table CONVERT TO CHARACTER SET utf8";
	$changes[] = "UPDATE $table SET ". join(", ", $column);
}

$changes = array();
// Alter database:
$changes[] = 'ALTER DATABASE `%DBNAME%` DEFAULT CHARACTER SET utf8';

echo "Performing database changes (", count($changes), ") queries\n";
apply_db_changes($changes);
echo "Done!\n";
