#!/usr/bin/php
<?php
require_once dirname(__FILE__) . '/../init.php';

function db_getAll($query) {
	$query = str_replace('%TABLE_PREFIX%', APP_TABLE_PREFIX, $query);
	$query = str_replace('%DBNAME%', APP_SQL_DBNAME, $query);
	$res = DB_Helper::getInstance()->getAll($query, DB_FETCHMODE_ASSOC);
	if (PEAR::isError($res)) {
		echo $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
		exit(1);
	}
	return $res;
}

function db_query($query) {
	$query = str_replace('%TABLE_PREFIX%', APP_TABLE_PREFIX, $query);
	$query = str_replace('%DBNAME%', APP_SQL_DBNAME, $query);
	$res = DB_Helper::getInstance()->query($query);
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

// convert textual columns to proper encoding and store as utf8
$res = db_getAll(
	"SELECT COLUMNS.TABLE_NAME,COLUMN_NAME,COLUMN_TYPE,COLUMN_KEY FROM INFORMATION_SCHEMA.COLUMNS, INFORMATION_SCHEMA.TABLES ".
	"WHERE COLUMNS.TABLE_SCHEMA='%DBNAME%' AND CHARACTER_SET_NAME='$db_charset' AND COLUMNS.TABLE_SCHEMA = TABLES.TABLE_SCHEMA AND " .
	"COLUMNS.TABLE_NAME = TABLES.TABLE_NAME AND TABLE_TYPE = 'BASE TABLE'"
);

$tables = array();
foreach ($res as $idx => $row) {
	$col = $row['COLUMN_NAME'];
	$convert = "CONVERT(CONVERT(CONVERT(CONVERT($col USING utf8) using $db_charset) using binary) using $in_charset)";
	$tables[$row['TABLE_NAME']][] = "$col=$convert";
}

foreach ($tables as $table => $column) {
	$changes[] = "ALTER TABLE `$table` CONVERT TO CHARACTER SET utf8";
	$changes[] = "UPDATE $table SET ". join(", ", $column);
}

// convert tables to utf8 that didn't had any text columns
$res = db_getAll(
	"SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES ".
	"WHERE TABLE_SCHEMA='%DBNAME%' AND TABLE_COLLATION NOT LIKE 'utf8\_%' AND TABLE_TYPE = 'BASE TABLE'"
);
foreach ($res as $idx => $row) {
	$changes[] = "ALTER TABLE {$row['TABLE_NAME']} CONVERT TO CHARACTER SET utf8";
}

// Alter database:
$changes[] = 'ALTER DATABASE `%DBNAME%` DEFAULT CHARACTER SET utf8';

echo "Performing database changes (", count($changes), ") queries\n";
apply_db_changes($changes);
echo "Done!\n";
