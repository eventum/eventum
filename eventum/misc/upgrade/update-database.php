#!/usr/bin/php
<?php
// avoid setup redirecting us
define('INSTALL_PATH', realpath(dirname(__FILE__) . '/../..'));
define('CONFIG_PATH', INSTALL_PATH.'/config');

if (!file_exists(CONFIG_PATH. '/config.php')) {
	die("Can't find config.php from ". CONFIG_PATH . ". Did you forgot to copy config from old install?");
}

require_once INSTALL_PATH . '/init.php';
require_once APP_INC_PATH . 'db_access.php';

define('EXIT_OK', 0);
define('EXIT_ERROR', 1);

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

function db_getOne($query) {
	$query = str_replace('%TABLE_PREFIX%', APP_TABLE_PREFIX, $query);
	$query = str_replace('%DBNAME%', APP_SQL_DBNAME, $query);

	$res = $GLOBALS['db_api']->dbh->getOne($query);
	if (PEAR::isError($res)) {
		echo $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
		exit(1);
	}
	return $res;
}

function db_getCol($query) {
	$query = str_replace('%TABLE_PREFIX%', APP_TABLE_PREFIX, $query);
	$query = str_replace('%DBNAME%', APP_SQL_DBNAME, $query);

	$res = $GLOBALS['db_api']->dbh->getCol($query);
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

function patch_database() {
	/*
	 * database versions. each version script can create it's dynamic queries
	 */
	$versions = array(
		1 => '01_notes.php',
		2 => '02_usr_alias.php',
		3 => '03_prj_mail_aliases.php',
	);

	// sanity check. check that the version table exists.
	$version = db_getOne("SELECT ver_version FROM %TABLE_PREFIX%version");
	if (!isset($version)) {
		# insert initial value
		db_query("INSERT INTO %TABLE_PREFIX%version SET ver_version=0");
		$version = 0;
	}
	$target = max(array_keys($versions));
	echo "Current database version: $version; Versions available: $target\n";
	if ($target < $version) {
		echo "ERROR: Your database version is greater ($version) than this upgrade supports ($target)!\n";
		return EXIT_ERROR;
	}
	if ($target == $version) {
		echo "Database already at version $version. Nothing to upgrade.\n";
		return EXIT_OK;
	}

	echo "Upgrading database to version $target\n";
	for ($i = $version + 1; $i <= $target; $i++) {
		if (empty($versions[$i])) {
			echo "ERROR: patch $i is not recorded in upgrade script.\n";
			return EXIT_ERROR;
		}
		$patch = APP_SQL_PATCHES_PATH . '/' . $versions[$i];
		echo "Checking patch $patch\n";
		if (!file_exists($patch)) {
			echo "ERROR: Patch file doesn't exist\n";
			return EXIT_ERROR;
		}
		require $patch;
		$func = "db_patch_$i";
		if (!function_exists($func)) {
			echo "ERROR: Patch did not define '$func' function\n";
			return EXIT_ERROR;
		}
		$patchset = $func();
		echo "Applying patch ", $i, ": ", count($patchset), " queries\n";
		apply_db_changes($patchset);
		db_query("UPDATE %TABLE_PREFIX%version SET ver_version=$i");
	}

	return EXIT_OK;
}

if (php_sapi_name() != 'cli') {
	echo "<pre>\n";
}

$ret = patch_database();

if (php_sapi_name() != 'cli') {
	echo "</pre>\n";
}

exit($ret);
