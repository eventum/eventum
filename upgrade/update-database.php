#!/usr/bin/php
<?php
require_once dirname(__FILE__) . '/init.php';

$dbconfig = DB_Helper::getConfig();

define('EXIT_OK', 0);
define('EXIT_ERROR', 1);

function db_getAll($query)
{
    try {
        $res = DB_Helper::getInstance()->getAll($query);
    } catch (DbException $e) {
        echo $e->getMessage(), "\n";
        exit(1);
    }

    return $res;
}

function db_getOne($query)
{
    try {
        $res = DB_Helper::getInstance()->getOne($query);
    } catch (DbException $e) {
        echo $e->getMessage(), "\n";
        exit(1);
    }

    return $res;
}

function db_getCol($query)
{
    try {
        $res = DB_Helper::getInstance()->getColumn($query);
    } catch (DbException $e) {
        echo $e->getMessage(), "\n";
        exit(1);
    }

    return $res;
}

function db_query($query)
{
    try {
        $res = DB_Helper::getInstance()->query($query);
    } catch (DbException $e) {
        echo $e->getMessage(), "\n";
        exit(1);
    }

    return $res;
}

function exec_sql_file($input_file)
{
    if (!file_exists($input_file) && !is_readable($input_file)) {
        echo "ERROR: Can't read file: $input_file\n";
        exit(EXIT_ERROR);
    }

    // use *.php for complex updates
    if (substr($input_file, -4) == '.php') {
        $queries = array();
        require $input_file;
    } else {
        $queries = explode(';', file_get_contents($input_file));
    }

    foreach ($queries as $query) {
        $query = trim($query);
        if ($query) {
           db_query($query);
       }
    }
}

function read_patches($update_path)
{
    $handle = opendir($update_path);
    if (!$handle) {
        echo "ERROR: Could not read: $update_path\n";
        exit(EXIT_ERROR);
    }
    while (false !== ($file = readdir($handle))) {
        $number =  substr($file, 0, strpos($file, '_'));
        if (in_array(substr($file, -4), array('.sql', '.php')) && is_numeric($number)) {
            $files[(int) $number] = trim($update_path) . (substr(trim($update_path), -1) == '/' ? '' : '/') . $file;
        }
    }
    closedir($handle);
    ksort($files);

    return $files;
}

function patch_database()
{
    // sanity check. check that the version table exists.
    $last_patch = db_getOne("SELECT ver_version FROM {{%version}}");
    if (!isset($last_patch)) {
        // insert initial value
        db_query("INSERT INTO {{%version}} SET ver_version=0");
        $last_patch = 0;
    }

    $files = read_patches(APP_SQL_PATCHES_PATH);

    $addCount = 0;
    foreach ($files as $number => $file) {
        if ($number > $last_patch) {
            echo "* Applying patch: ", $number, " (", basename($file), ")\n";
            exec_sql_file($file);
            db_query("UPDATE {{%version}} SET ver_version=$number");
            $addCount++;
        }
    }

    $version = max(array_keys($files));
    if ($addCount == 0) {
        echo "* Your database is already up-to-date. Version $version\n";
    } else {
        echo "* Your database is now up-to-date. Updated from $last_patch to $version\n";
    }
}

if (php_sapi_name() != 'cli') {
    echo "<pre>\n";
}

patch_database();

if (php_sapi_name() != 'cli') {
    echo "</pre>\n";
}

exit(EXIT_OK);
