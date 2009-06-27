#!/usr/bin/php
<?php
require_once dirname(__FILE__) . '/../init.php';

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

$stmts = array();

$stmts[] = "CREATE TABLE %TABLE_PREFIX%version (
    ver_version int(11) unsigned NOT NULL DEFAULT 0
) ENGINE = MYISAM DEFAULT CHARSET=utf8";
$stmts[] = "INSERT INTO %TABLE_PREFIX%version SET ver_version=0";

foreach ($stmts as $stmt) {
	db_query($stmt);
}

echo "done\n";
