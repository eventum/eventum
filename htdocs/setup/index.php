<?php

/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

// XXX: try reading $_ENV['HOSTNAME'] and then ask the user if nothing could be found
// XXX: dynamically check the email blob and skips the email if it is bigger than 16MB on PHP4 versions

use Eventum\Db\Adapter\AdapterInterface;
use Eventum\Db\DatabaseException;
use Eventum\Db\Migrate;

ini_set('memory_limit', '64M');

ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_STRICT);
set_time_limit(0);
define('APP_NAME', 'Eventum');
define('APP_CHARSET', 'UTF-8');
define('APP_DEFAULT_LOCALE', 'en_US');
define('APP_PATH', realpath(__DIR__ . '/../..'));
define('APP_VAR_PATH', APP_PATH . '/var');
define('APP_INC_PATH', APP_PATH . '/lib/eventum');
define('APP_CONFIG_PATH', APP_PATH . '/config');
define('APP_SETUP_FILE', APP_CONFIG_PATH . '/setup.php');
define('APP_TPL_PATH', APP_PATH . '/templates');
define('APP_TPL_COMPILE_PATH', APP_VAR_PATH . '/cache');
define('APP_LOG_PATH', APP_VAR_PATH . '/log');
define('APP_ERROR_LOG', APP_LOG_PATH . '/errors.log');
define('APP_LOCKS_PATH', APP_VAR_PATH . '/lock');
define('APP_LOCAL_PATH', APP_CONFIG_PATH);
define('APP_RELATIVE_URL', '../');

header('Content-Type: text/html; charset=' . APP_CHARSET);

$have_config = file_exists(APP_CONFIG_PATH . '/config.php') && filesize(APP_CONFIG_PATH . '/config.php');
// get out if already configured
if ($have_config) {
    header('Location: ../');
    exit(0);
}

require_once APP_PATH . '/autoload.php';

// set default timezone to utc to avoid default timezone not set warnings
date_default_timezone_set(@date_default_timezone_get());

list($warnings, $errors) = checkRequirements();
if ($warnings || $errors) {
    Misc::displayRequirementErrors(array_merge($errors, $warnings), 'Eventum Setup');
    if ($errors) {
        exit(1);
    }
}

$tpl = new Template_Helper();

if (@$_POST['cat'] == 'install') {
    $res = install();
    $tpl->assign('result', $res);
    // check for the optional IMAP extension
    $tpl->assign('is_imap_enabled', function_exists('imap_open'));
}

$full_url = dirname($_SERVER['PHP_SELF']);
$pieces = explode('/', $full_url);
$relative_url = array();
$relative_url[] = '';
foreach ($pieces as $piece) {
    if ((!empty($piece)) && ($piece != 'setup')) {
        $relative_url[] = $piece;
    }
}
$relative_url[] = '';
$relative_url = implode('/', $relative_url);
define('APP_REL_URL', $relative_url);
$tpl->assign('phpversion', phpversion());
$tpl->assign('core', array(
    'rel_url'   =>  $relative_url,
    'app_title' =>  APP_NAME,
));
if (@$_SERVER['HTTPS'] == 'on') {
    $ssl_mode = 'enabled';
} else {
    $ssl_mode = 'disabled';
}
$tpl->assign('ssl_mode', $ssl_mode);

$tpl->assign('zones', Date_Helper::getTimezoneList());
$tpl->assign('default_timezone', getTimezone());
$tpl->assign('default_weekday', getFirstWeekday());

$tpl->setTemplate('setup.tpl.html');
$tpl->displayTemplate(false);

/**
 * Checks for $file for write permission.
 *
 * IMPORTANT: if the file does not exist, an empty file is created.
 */
function checkPermissions($file, $desc, $is_directory = false)
{
    clearstatcache();
    if (!file_exists($file)) {
        if (!$is_directory) {
            // try to create the file ourselves then
            $fp = @fopen($file, 'w');
            if (!$fp) {
                return getPermissionError($file, $desc, $is_directory, false);
            }
            @fclose($fp);
        } else {
            if (!@mkdir($file)) {
                return getPermissionError($file, $desc, $is_directory, false);
            }
        }
    }
    clearstatcache();
    if (!is_writable($file)) {
        if (!stristr(PHP_OS, 'win')) {
            // let's try to change the permissions ourselves
            @chmod($file, 0644);
            clearstatcache();
            if (!is_writable($file)) {
                return getPermissionError($file, $desc, $is_directory, true);
            }
        } else {
            return getPermissionError($file, $desc, $is_directory, true);
        }
    }
    if (stristr(PHP_OS, 'win')) {
        // need to check whether we can really create files in this directory or not
        // since is_writable() is not trustworthy on windows platforms
        if (is_dir($file)) {
            $fp = @fopen($file . '/dummy.txt', 'w');
            if (!$fp) {
                return "$desc is not writable";
            }
            @fwrite($fp, 'test');
            @fclose($fp);
            // clean up after ourselves
            @unlink($file . '/dummy.txt');
        }
    }

    return '';
}

function getPermissionError($file, $desc, $is_directory, $exists)
{
    $error = '';
    if ($is_directory) {
        $title = 'Directory';
    } else {
        $title = 'File';
    }
    $error = "$title <b>'" . $file . ($is_directory ? '/' : '') . "'</b> ";

    if (!$exists) {
        $error .= "does not exist. Please create the $title and reload this page.";
    } else {
        $error .= "is not writeable. Please change this $title to be writeable by the web server.";
    }

    return $error;
}

function checkRequirements()
{
    $errors = array();
    $warnings = array();

    $extensions = array(
        // extension => array(IS_REQUIRED, MESSAGE_TO_DISPLAY)
        'gd' => array(true, 'The GD extension needs to be enabled in your PHP.INI file in order for Eventum to work properly.'),
        'session' => array(true, 'The Session extension needs to be enabled in your PHP.INI file in order for Eventum to work properly.'),
        'mysqli' => array(true, 'The MySQLi extension needs to be enabled in your PHP.INI file in order for Eventum to work properly.'),
        'json' => array(true, 'The json extension needs to be enabled in your PHP.INI file in order for Eventum to work properly.'),
        'mbstring' =>  array(false, 'The Multibyte String Functions extension is not enabled in your PHP installation. For localization to work properly ' .
            'You need to install this extension. If you do not install this extension localization will be disabled.', ),
        'iconv' => array(false, 'The ICONV extension is not enabled in your PHP installation. '.
            'You need to install this extension for optimal operation. If you do not install this extension some unicode data will be corrupted.', ),
    );

    foreach ($extensions as $extension => $value) {
        list($required, $message) = $value;
        if (!extension_loaded($extension)) {
            if ($required) {
                $errors[] = $message;
            } else {
                $warnings[] = $message;
            }
        }
    }

    // check for the file_uploads php.ini directive
    if (ini_get('file_uploads') != '1') {
        $errors[] = "The 'file_uploads' directive needs to be enabled in your PHP.INI file in order for Eventum to work properly.";
    }

    $error = checkPermissions(APP_CONFIG_PATH, "Directory '" . APP_CONFIG_PATH . "'", true);
    if (!empty($error)) {
        $errors[] = $error;
    }
    $error = checkPermissions(APP_SETUP_FILE, "File '" . APP_SETUP_FILE. "'");
    if (!empty($error)) {
        $errors[] = $error;
    }
    $error = checkPermissions(APP_CONFIG_PATH . '/private_key.php', "File '" . APP_CONFIG_PATH . '/private_key.php'. "'");
    if (!empty($error)) {
        $errors[] = $error;
    }
    $error = checkPermissions(APP_CONFIG_PATH . '/config.php', "File '" . APP_CONFIG_PATH . '/config.php'. "'");
    if (!empty($error)) {
        $errors[] = $error;
    }

    $error = checkPermissions(APP_LOCKS_PATH, "Directory '" . APP_LOCKS_PATH . "'", true);
    if (!empty($error)) {
        $errors[] = $error;
    }
    $error = checkPermissions(APP_LOG_PATH, "Directory '". APP_LOG_PATH . "'", true);
    if (!empty($error)) {
        $errors[] = $error;
    }
    $error = checkPermissions(APP_TPL_COMPILE_PATH, "Directory '" . APP_TPL_COMPILE_PATH . "'", true);
    if (!empty($error)) {
        $errors[] = $error;
    }
    $error = checkPermissions(APP_ERROR_LOG, "File '" . APP_ERROR_LOG . "'");
    if (!empty($error)) {
        $errors[] = $error;
    }

    return array($warnings, $errors);
}

function getErrorMessage($type, $message)
{
    if (empty($message)) {
        return '';
    } else {
        if (stristr($message, 'Unknown MySQL Server Host')) {
            return 'Could not connect to the MySQL database server with the provided information.';
        } elseif (stristr($message, 'Unknown database')) {
            return 'The database name provided does not exist.';
        } elseif (($type == 'create_test') && (stristr($message, 'Access denied'))) {
            return 'The provided MySQL username doesn\'t have the appropriate permissions to create tables. Please contact your local system administrator for further assistance.';
        } elseif (($type == 'drop_test') && (stristr($message, 'Access denied'))) {
            return 'The provided MySQL username doesn\'t have the appropriate permissions to drop tables. Please contact your local system administrator for further assistance.';
        }

        return $message;
    }
}

function getTimezone()
{
    $ini = ini_get('date.timezone');
    if ($ini) {
        return $ini;
    }

    // if php.ini is unconfigured, this function is noisy
    return @date_default_timezone_get();
}

function getFirstWeekday()
{
    // this works on Linux
    // http://stackoverflow.com/questions/727471/how-do-i-get-the-first-day-of-the-week-for-the-current-locale-php-l8n
    $weekday = exec('locale first_weekday');
    if ($weekday) {
        // Returns Monday=2, but we need 1 for Monday
        // see http://man7.org/linux/man-pages/man5/locale.5.html
        return --$weekday;
    }

    // This would work in PHP 5.5
    if (class_exists('IntlCalendar')) {
        $cal = IntlCalendar::createInstance();

        return $cal->getFirstDayOfWeek() == IntlCalendar::DOW_MONDAY ? 1 : 0;
    }

    // default to Monday as it's default for "World" in CLDR's supplemental data
    return 1;
}

/***
 * @param AdapterInterface $conn
 * @param string $database
 * @return array|null
 */
function checkDatabaseExists($conn, $database)
{
    $exists = $conn->getOne('SHOW DATABASES LIKE ?', $database);

    return $exists;
}

/**
 * @param AdapterInterface $conn
 * @return array
 */
function getUserList($conn)
{
    // avoid "1046 ** No database selected" error
    $conn->query('USE mysql');
    try {
        $users = $conn->getColumn('SELECT DISTINCT User from user');
    } catch (DatabaseException $e) {
        // if the user cannot select from the mysql.user table, then return an empty list
        return array();
    }

    // FIXME: why lowercase neccessary?
    $users = Misc::lowercase($users);

    return $users;
}

/**
 * @param AdapterInterface $conn
 * @return array
 */
function getTableList($conn)
{
    $tables = $conn->getColumn('SHOW TABLES');

    // FIXME: why lowercase neccessary?
    $tables = Misc::lowercase($tables);

    return $tables;
}

function e($s)
{
    return var_export($s, 1);
}

function get_queries($file)
{
    $contents = file_get_contents($file);
    $queries = explode(';', $contents);
    $queries = Misc::trim($queries);
    $queries = array_filter($queries);

    return $queries;
}

function initlogger()
{
    // init timezone, logger needs it
  if (!defined('APP_DEFAULT_TIMEZONE')) {
      $tz = !empty($_POST['default_timezone']) ? $_POST['default_timezone'] : @date_default_timezone_get();
      define('APP_DEFAULT_TIMEZONE', $tz ?: 'UTC');
  }

  // and APP_VERSION
  if (!defined('APP_VERSION')) {
      define('APP_VERSION', '3.x');
  }
    Logger::initialize();
}

function getDb()
{
    initlogger();
    try {
        return DB_Helper::getInstance(false);
    } catch (DatabaseException $e) {
    }

    $err = $e->getMessage();
    // PEAR driver has 'debuginfo' property
    if (isset($e->context['debuginfo'])) {
        $err .= ' ' . $e->context['debuginfo'];
    }

    // indicate that mysql default socket may be wrong
    if (strpos($err, 'No such file or directory') !== 0) {
        $ini = 'mysqli.default_socket';
        $err .= sprintf(" Please check that PHP ini parameter $ini='%s' is correct", ini_get($ini));
    }

    throw new RuntimeException($err, $e->getCode());
}

/**
 * return error message as string, or true indicating success
 * requires setup to be written first.
 */
function setup_database()
{
    $conn = getDb();

    $db_exists = checkDatabaseExists($conn, $_POST['db_name']);
    if (!$db_exists) {
        if (@$_POST['create_db'] == 'yes') {
            try {
                $conn->query("CREATE DATABASE {{{$_POST['db_name']}}}");
            } catch (DatabaseException $e) {
                throw new RuntimeException(getErrorMessage('create_db', $e->getMessage()));
            }
        } else {
            throw new RuntimeException('The provided database name could not be found. Review your information or specify that the database should be created in the form below.');
        }
    }

    // create the new user, if needed
    if (@$_POST['alternate_user'] == 'yes') {
        $user_list = getUserList($conn);
        if ($user_list) {
            $user_exists = in_array(strtolower(@$_POST['eventum_user']), $user_list);

            if (@$_POST['create_user'] == 'yes') {
                if (!$user_exists) {
                    $stmt = "GRANT SELECT, UPDATE, DELETE, INSERT, ALTER, DROP, CREATE, INDEX ON {{{$_POST['db_name']}}}.* TO ?@'%' IDENTIFIED BY ?";
                    try {
                        $conn->query($stmt, array($_POST['eventum_user'], $_POST['eventum_password']));
                    } catch (DatabaseException $e) {
                        throw new RuntimeException(getErrorMessage('create_user', $e->getMessage()));
                    }
                }
            } else {
                if (!$user_exists) {
                    throw new RuntimeException('The provided MySQL username could not be found. Review your information or specify that the username should be created in the form below.');
                }
            }
        }
    }

    // check if we can use the database
    try {
        $conn->query("USE {{{$_POST['db_name']}}}");
    } catch (DatabaseException $e) {
        throw new RuntimeException(getErrorMessage('select_db', $e->getMessage()));
    }

    // set sql mode (sad that we rely on old bad mysql defaults)
    $conn->query("SET SQL_MODE = ''");

    // check the CREATE and DROP privileges by trying to create and drop a test table
    $table_list = getTableList($conn);
    if (!in_array('eventum_test', $table_list)) {
        try {
            $conn->query('CREATE TABLE eventum_test (test char(1))');
        } catch (DatabaseException $e) {
            throw new RuntimeException(getErrorMessage('create_test', $e->getMessage()));
        }
    }
    try {
        $conn->query('DROP TABLE eventum_test');
    } catch (DatabaseException $e) {
        throw new RuntimeException(getErrorMessage('drop_test', $e->getMessage()));
    }

    // if requested. drop tables first
    if (@$_POST['drop_tables'] == 'yes') {
        $queries = get_queries(APP_PATH . '/upgrade/drop.sql');
        foreach ($queries as $stmt) {
            try {
                $conn->query($stmt);
            } catch (DatabaseException $e) {
                throw new RuntimeException(getErrorMessage('drop_table', $e->getMessage()));
            }
        }
    }

    // setup database with upgrade script
    $buffer = array();
    try {
        $dbmigrate = new Migrate(APP_PATH . '/upgrade');
        $dbmigrate->setLogger(function ($e) use (&$buffer) {
            $buffer[] = $e;
        });
        $dbmigrate->patch_database();
        $e = false;
    } catch (Exception $e) {
    }

    global $tpl;
    $tpl->assign('db_result', implode("\n", $buffer));

    if ($e) {
        $upgrade_script = APP_PATH . '/bin/upgrade.php';
        $error = array(
            'Database setup failed on upgrade:',
            "<tt>{$e->getMessage()}</tt>",
            '',
            "You may want run update script <tt>$upgrade_script</tt> manually"
        );
        throw new RuntimeException(implode('<br/>', $error));
    }

    // write db name now that it has been created
    $setup = array();
    $setup['database'] = $_POST['db_name'];

    // substitute the appropriate values in config.php!!!
    if (@$_POST['alternate_user'] == 'yes') {
        $setup['username'] = $_POST['eventum_user'];
        $setup['password'] = $_POST['eventum_password'];
    }

    Setup::save(array('database' => $setup));
}

function write_file($file, $contents)
{
    clearstatcache();
    // check if directory is writable
    $dir = dirname($file);
    if (!is_writable($dir)) {
        throw new RuntimeException("The file '{$dir}' directory needs to be writable by the web server user. Please correct this problem and try again.");
    }
    $fp = @fopen($file, 'w');
    if ($fp === false) {
        throw new RuntimeException("Could not open the file '$file' for writing. The permissions on the file should be set as to allow the user that the web server runs as to open it. Please correct this problem and try again.");
    }
    $res = fwrite($fp, $contents);
    if ($res <= 0) {
        throw new RuntimeException("Could not write the configuration information to '$file'. The file should be writable by the user that the web server runs as. Please correct this problem and try again.");
    }
    fclose($fp);
}

/**
 * write initial values for setup file
 */
function write_setup()
{
    $setup = $_REQUEST['setup'];
    $setup['update'] = 1;
    $setup['closed'] = 1;
    $setup['emails'] = 1;
    $setup['files'] = 1;
    $setup['support_email'] = 'enabled';

    $parts = explode(':', $_POST['db_hostname'], 2);
    if (count($parts) > 1) {
        list($hostname, $socket) = $parts;
    } else {
        list($hostname) = $parts;
        $socket = null;
    }

    $setup['database'] = array(
        // database driver
        'driver' => 'mysqli',

        // connection info
        'hostname' => $hostname,
        'database' => '', // NOTE: db name has to be written after the table has been created
        'username' => $_POST['db_username'],
        'password' => $_POST['db_password'],
        'port' => 3306,
        'socket' => $socket,

        // table prefix
        'table_prefix' => $_POST['db_table_prefix'],
    );

    Setup::save($setup);
}

function write_config()
{
    $config_file_path = APP_CONFIG_PATH . '/config.php';

    // disable the full-text search feature for certain mysql server users
    /** @var AdapterInterface $conn */
    $mysql_version = DB_Helper::getInstance(false)->getOne('SELECT VERSION()');
    preg_match('/(\d{1,2}\.\d{1,2}\.\d{1,2})/', $mysql_version, $matches);
    $enable_fulltext = $matches[1] > '4.0.23';

    $replace = array(
        "'%{APP_HOSTNAME}%'" => e($_POST['hostname']),
        "'%{CHARSET}%'" => e(APP_CHARSET),
        "'%{APP_RELATIVE_URL}%'" => e($_POST['relative_url']),
        "'%{APP_DEFAULT_TIMEZONE}%'" => e($_POST['default_timezone']),
        "'%{APP_DEFAULT_WEEKDAY}%'" => (int) $_POST['default_weekday'],
        "'%{PROTOCOL_TYPE}%'" => e(@$_POST['is_ssl'] == 'yes' ? 'https://' : 'http://'),
        "'%{APP_ENABLE_FULLTEXT}%'" => e($enable_fulltext),
    );

    $config_contents = file_get_contents(APP_CONFIG_PATH . '/config.dist.php');
    $config_contents = str_replace(array_keys($replace), array_values($replace), $config_contents);

    write_file($config_file_path, $config_contents);
}

function install()
{
    try {
        Auth::generatePrivateKey();
        write_setup();
        setup_database();
        write_config();
    } catch (RuntimeException $e) {
        return $e->getMessage();
    }

    return 'success';
}
