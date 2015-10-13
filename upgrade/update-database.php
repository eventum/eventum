#!/usr/bin/php
<?php
// common init for upgrade scripts
define('INSTALL_PATH', __DIR__ . '/..');
define('CONFIG_PATH', INSTALL_PATH . '/config');

// avoid setup redirecting us
if (!file_exists(CONFIG_PATH . '/setup.php') || !filesize(CONFIG_PATH . '/setup.php') || !is_readable(CONFIG_PATH . '/setup.php')) {
    error_log("ERROR: Can't get setup.php in '" . CONFIG_PATH . "'");
    error_log('Did you forgot to copy config from old install? Is file readable?');
    exit(1);
}

// load init only if no autoloader present
if (!class_exists('DB_Helper')) {
    require_once INSTALL_PATH . '/init.php';
}

$in_setup = defined('IN_SETUP');
global $dbconfig, $db;
$dbconfig = DB_Helper::getConfig();
$db = DB_Helper::getInstance();

function exec_sql_file($input_file)
{
    if (!file_exists($input_file) && !is_readable($input_file)) {
        throw new RuntimeException("Can't read file: $input_file");
    }

    global $dbconfig, $db;

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
            $db->query($query);
        }
    }
}

function read_patches($update_path)
{
    $handle = opendir($update_path);
    if (!$handle) {
        throw new RuntimeException("Could not read: $update_path");
    }
    while (false !== ($file = readdir($handle))) {
        $number = substr($file, 0, strpos($file, '_'));
        if (in_array(substr($file, -4), array('.sql', '.php')) && is_numeric($number)) {
            $files[(int) $number] = trim($update_path) . (substr(trim($update_path), -1) == '/' ? '' : '/') . $file;
        }
    }
    closedir($handle);
    ksort($files);

    return $files;
}

function init_database()
{
    $file = __DIR__ . '/schema.sql';
    echo '* Creating database: ', basename($file), "\n";
    exec_sql_file($file);
}

function patch_database()
{
    // sanity check. check that the version table exists.
    global $dbconfig, $db;
    $has_table = $db->getOne("SHOW TABLES LIKE '{$dbconfig['table_prefix']}version'");
    if (!$has_table) {
        init_database();
    }

    $last_patch = $db->getOne('SELECT ver_version FROM {{%version}}');
    if (!isset($last_patch)) {
        // insert initial value
        $db->query('INSERT INTO {{%version}} SET ver_version=0');
        $last_patch = 0;
    }

    $files = read_patches(__DIR__ . '/patches');

    $addCount = 0;
    foreach ($files as $number => $file) {
        if ($number > $last_patch) {
            echo '* Applying patch: ', $number, ' (', basename($file), ")\n";
            exec_sql_file($file);
            $db->query("UPDATE {{%version}} SET ver_version=$number");
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

if (!$in_setup && php_sapi_name() != 'cli') {
    echo "<pre>\n";
}

try {
    patch_database();
} catch (Exception $e) {
    if ($in_setup) {
        throw $e;
    }
    echo $e->getMessage(), "\n";
    exit(1);
}

if (!$in_setup && php_sapi_name() != 'cli') {
    echo "</pre>\n";
}
