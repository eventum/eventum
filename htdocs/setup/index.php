<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2013 Eventum Team.                              |
// |                                                                      |
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License as published by |
// | the Free Software Foundation; either version 2 of the License, or    |
// | (at your option) any later version.                                  |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to:                           |
// |                                                                      |
// | Free Software Foundation, Inc.                                       |
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: Bryan Alsdorf <bryan@mysql.com>                             |
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+
//

// XXX: try reading $_ENV['HOSTNAME'] and then ask the user if nothing could be found
// XXX: dynamically check the email blob and skips the email if it is bigger than 16MB on PHP4 versions

ini_set('memory_limit', '64M');

ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);
define('APP_CHARSET', 'UTF-8');
define('APP_DEFAULT_LOCALE', 'en_US');
define('APP_PATH', realpath(dirname(__FILE__) . '/../..'));
define('APP_INC_PATH', APP_PATH . '/lib/eventum');
define('APP_PEAR_PATH', APP_PATH . '/lib/pear');
define('APP_SMARTY_PATH', APP_PATH . '/lib/Smarty');
define('APP_CONFIG_PATH', APP_PATH . '/config');
define('APP_SETUP_FILE', APP_CONFIG_PATH . '/setup.php');
define('APP_TPL_PATH', APP_PATH . '/templates');
define('APP_TPL_COMPILE_PATH', APP_PATH . '/templates_c');
define('APP_LOG_PATH', APP_PATH . '/logs');
define('APP_ERROR_LOG', APP_LOG_PATH . '/errors.log');
define('APP_LOCKS_PATH', APP_PATH . '/locks');

header('Content-Type: text/html; charset=' . APP_CHARSET);

if (defined('APP_PEAR_PATH')) {
    set_include_path(
        APP_PEAR_PATH . PATH_SEPARATOR .
        APP_INC_PATH . PATH_SEPARATOR .
        get_include_path()
    );
}
require_once 'File/Util.php';
require_once 'class.date_helper.php';

list($warnings, $errors) = checkRequirements();
if ((count($warnings) > 0) || (count($errors) > 0)) {
    echo '<html>
<head>
<style type="text/css">
<!--
.default {
  font-family: Verdana, Arial, Helvetica, sans-serif;
  font-style: normal;
  font-weight: normal;
  font-size: 70%;
}
-->
</style>
<title>Eventum Setup</title>
</head>
<body>

<br /><br />

<table width="600" bgcolor="#003366" border="0" cellspacing="0" cellpadding="1" align="center">
  <tr>
    <td>
      <table bgcolor="#FFFFFF" width="100%" cellspacing="1" cellpadding="2" border="0">
        <tr>
          <td><img src="../images/icons/error.gif" hspace="2" vspace="2" border="0" align="left"></td>
          <td width="100%" class="default"><span style="font-weight: bold; font-size: 160%; color: red;">Configuration Error:</span></td>
        </tr>
        <tr>
          <td colspan="2" class="default">
            <br />
            <b>The following problems were found:</b>
            <br /><br />
            ', implode("\n<hr>\n", array_merge($errors, $warnings)), '
            <br /><br />
            <b>Please resolve the issues described above. For file permission errors, please provide the appropriate permissions to the user that the web server run as to write in the directories and files specified above.</b>
            <br /><br />
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>

</body>
</html>';
    if (count($errors) > 0) {
        exit(1);
    }
}

require_once APP_SMARTY_PATH . '/Smarty.class.php';

$tpl = new Smarty();
$tpl->plugins_dir  = array(APP_INC_PATH . '/smarty', 'plugins');
$tpl->template_dir = APP_TPL_PATH;
$tpl->compile_dir = APP_TPL_COMPILE_PATH;
$tpl->config_dir = '';

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

$tpl->assign('phpversion', phpversion());
$tpl->assign('rel_url', $relative_url);
if (@$_SERVER['HTTPS'] == 'on') {
    $ssl_mode = 'enabled';
} else {
    $ssl_mode = 'disabled';
}
$tpl->assign('ssl_mode', $ssl_mode);

$tpl->assign("zones", Date_Helper::getTimezoneList());

$tpl->display('setup.tpl.html');


function checkPermissions($file, $desc, $is_directory = false)
{
    clearstatcache();
    if (!file_exists($file)) {
        if (!$is_directory) {
            // try to create the file ourselves then
            $fp = @fopen($file, 'w');
            if (!$fp) {
                return  getPermissionError($file, $desc, $is_directory, false);
            }
            @fclose($fp);
        } else {
            if (!@mkdir($file)) {
                return  getPermissionError($file, $desc, $is_directory, false);
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
    $error = "$title <b>'" . File_Util::realPath($file) . ($is_directory ? '/' : '') . "'</b> ";

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
        'mysql' => array(true, 'The MySQL extension needs to be enabled in your PHP.INI file in order for Eventum to work properly.'),
        'json' => array(true, 'The json extension needs to be enabled in your PHP.INI file in order for Eventum to work properly.'),
        'mbstring' =>  array(false, "The Multibyte String Functions extension is not enabled in your PHP installation. For localization to work properly " .
            "You need to install this extension. If you do not install this extension localization will be disabled."),
        'iconv' => array(false, "The ICONV extension is not enabled in your PHP installation. ".
            "You need to install this extension for optimal operation. If you do not install this extension some unicode data will be corrupted."),
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
    if (ini_get('file_uploads') != "1") {
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

function replace_table_prefix($str)
{
    return str_replace('%TABLE_PREFIX%', $_POST['db_table_prefix'], $str);
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

function getDatabaseList($conn)
{
    $db_list = mysql_list_dbs($conn);
    $dbs = array();
    while ($row = mysql_fetch_array($db_list)) {
        $dbs[] = $row['Database'];
    }
    return $dbs;
}

function getUserList($conn)
{
    @mysql_select_db('mysql');
    $res = @mysql_query('SELECT DISTINCT User from user');
    $users = array();
    // if the user cannot select from the mysql.user table, then return an empty list
    if (!$res) {
        return $users;
    }
    while ($row = mysql_fetch_row($res)) {
        $users[] = $row[0];
    }
    return $users;
}

function getTableList($conn)
{
    $res = mysql_query('SHOW TABLES', $conn);
    $tables = array();
    while ($row = mysql_fetch_row($res)) {
        $tables[] = $row[0];
    }
    return $tables;
}

function install()
{
    $private_key_path = APP_CONFIG_PATH . '/private_key.php';
    $config_file_path = APP_CONFIG_PATH . '/config.php';
    $setup_file_path = APP_SETUP_FILE;

    clearstatcache();
    // check if config directory is writable
    if (!is_writable(APP_CONFIG_PATH)) {
        return "The file '" . APP_CONFIG_PATH . "' directory needs to be writable by the web server user. Please correct this problem and try again.";
    }
    // need to create a random private key variable
    $private_key = '<'.'?php
$private_key = "' . md5(microtime()) . '";
';
    $fp = @fopen($private_key_path, 'w');
    if ($fp === false) {
        return "Could not open the file '$private_key_path' for writing. The permissions on the file should be set as to allow the user that the web server runs as to open it. Please correct this problem and try again.";
    }
    $res = fwrite($fp, $private_key);
    if ($fp === false) {
        return "Could not write the configuration information to '$private_key_path'. The file should be writable by the user that the web server runs as. Please correct this problem and try again.";
    }
    fclose($fp);
    // check if we can connect
    $conn = mysql_connect($_POST['db_hostname'], $_POST['db_username'], $_POST['db_password']);
    if (!$conn) {
        return getErrorMessage('connect', mysql_error());
    }
    $db_list = getDatabaseList($conn);
    $db_list = array_map('strtolower', $db_list);
    if (@$_POST['create_db'] == 'yes') {
        if (!in_array(strtolower($_POST['db_name']), $db_list)) {
            if (!mysql_query('CREATE DATABASE ' . $_POST['db_name'], $conn)) {
                return getErrorMessage('create_db', mysql_error());
            }
        }
    } else {
        if ((count($db_list) > 0) && (!in_array(strtolower($_POST['db_name']), $db_list))) {
            return "The provided database name could not be found. Review your information or specify that the database should be created in the form below.";
        }
    }
    // create the new user, if needed
    if (@$_POST["alternate_user"] == 'yes') {
        $user_list = getUserList($conn);
        if (count($user_list) > 0) {
            $user_list = array_map('strtolower', $user_list);
            if (@$_POST["create_user"] == 'yes') {
                if (!in_array(strtolower(@$_POST['eventum_user']), $user_list)) {
                    $stmt = "GRANT SELECT, UPDATE, DELETE, INSERT, ALTER, DROP, CREATE, INDEX ON " . $_POST['db_name'] . ".* TO '" . $_POST["eventum_user"] . "'@'%' IDENTIFIED BY '" . $_POST["eventum_password"] . "'";
                    if (!mysql_query($stmt, $conn)) {
                        return getErrorMessage('create_user', mysql_error());
                    }
                }
            } else {
                if (!in_array(strtolower(@$_POST['eventum_user']), $user_list)) {
                    return "The provided MySQL username could not be found. Review your information or specify that the username should be created in the form below.";
                }
            }
        }
    }
    // check if we can use the database
    if (!mysql_select_db($_POST['db_name'])) {
        return getErrorMessage('select_db', mysql_error());
    }
    // check the CREATE and DROP privileges by trying to create and drop a test table
    $table_list = getTableList($conn);
    $table_list = array_map('strtolower', $table_list);
    if (!in_array('eventum_test', $table_list)) {
        if (!mysql_query('CREATE TABLE eventum_test (test char(1))', $conn)) {
            return getErrorMessage('create_test', mysql_error());
        }
    }
    if (!mysql_query('DROP TABLE eventum_test', $conn)) {
        return getErrorMessage('drop_test', mysql_error());
    }
    $contents = implode("", file("schema.sql"));
    $queries = explode(";", $contents);
    unset($queries[count($queries)-1]);
    // COMPAT: the next line requires PHP >= 4.0.6
    $queries = array_map("trim", $queries);
    $queries = array_map("replace_table_prefix", $queries);
    foreach ($queries as $stmt) {
        if ((stristr($stmt, 'DROP TABLE')) && (@$_POST['drop_tables'] != 'yes')) {
            continue;
        }
        // need to check if a CREATE TABLE on an existing table throws an error
        if (!mysql_query($stmt, $conn)) {
            if (stristr($stmt, 'DROP TABLE')) {
                $type = 'drop_table';
            } else {
                $type = 'create_table';
            }
            return getErrorMessage($type, mysql_error());
        }
    }

    // substitute the appropriate values in config.php!!!
    if (@$_POST['alternate_user'] == 'yes') {
        $_POST['db_username'] = $_POST['eventum_user'];
        $_POST['db_password'] = $_POST['eventum_password'];
    }

    $config_contents = file_get_contents('config.php');
    $config_contents = str_replace("%{APP_SQL_DBHOST}%", $_POST['db_hostname'], $config_contents);
    $config_contents = str_replace("%{APP_SQL_DBNAME}%", $_POST['db_name'], $config_contents);
    $config_contents = str_replace("%{APP_SQL_DBUSER}%", $_POST['db_username'], $config_contents);
    $config_contents = str_replace("%{APP_SQL_DBPASS}%", $_POST['db_password'], $config_contents);
    $config_contents = str_replace("%{APP_TABLE_PREFIX}%", $_POST['db_table_prefix'], $config_contents);
    $config_contents = str_replace("%{APP_HOSTNAME}%", $_POST['hostname'], $config_contents);
    $config_contents = str_replace("%{CHARSET}%", APP_CHARSET, $config_contents);
    $config_contents = str_replace("%{APP_RELATIVE_URL}%", $_POST['relative_url'], $config_contents);
    $config_contents = str_replace("'%{APP_DEFAULT_TIMEZONE}%'", var_export($_POST['default_timezone'], 1), $config_contents);
    $config_contents = str_replace("'%{APP_DEFAULT_WEEKDAY}%'", (int )$_POST['default_weekday'], $config_contents);

    if (@$_POST['is_ssl'] == 'yes') {
        $protocol_type = 'https://';
    } else {
        $protocol_type = 'http://';
    }
    $config_contents = str_replace("%{PROTOCOL_TYPE}%", $protocol_type, $config_contents);
    // disable the full-text search feature for certain mysql server users
    $stmt = "SELECT VERSION();";
    $res = mysql_query($stmt, $conn);
    $mysql_version = mysql_result($res, 0, 0);
    preg_match('/(\d{1,2}\.\d{1,2}\.\d{1,2})/', $mysql_version, $matches);
    if ($matches[1] > '4.0.23') {
        $config_contents = str_replace("'%{APP_ENABLE_FULLTEXT}%'", "true", $config_contents);
    } else {
        $config_contents = str_replace("'%{APP_ENABLE_FULLTEXT}%'", "false", $config_contents);
    }

    $fp = @fopen($config_file_path, 'w');
    if ($fp === false) {
        return "Could not open the file '$config_file_path' for writing. The permissions on the file should be set as to allow the user that the web server runs as to open it. Please correct this problem and try again.";
    }
    $res = fwrite($fp, $config_contents);
    if ($fp === false) {
        return "Could not write the configuration information to '$config_file_path'. The file should be writable by the user that the web server runs as. Please correct this problem and try again.";
    }
    fclose($fp);

    // write setup file
    require_once APP_INC_PATH . '/class.setup.php';
    $_REQUEST['setup']['update'] = 1;
    $_REQUEST['setup']['closed'] = 1;
    $_REQUEST['setup']['emails'] = 1;
    $_REQUEST['setup']['files'] = 1;
    $_REQUEST['setup']['allow_unassigned_issues'] = 'yes';
    $_REQUEST['setup']['support_email'] = 'enabled';
    Setup::save($_REQUEST['setup']);

    // after config has been written down, we can finish database setup by calling upgrade script
    $upgrade_script = APP_PATH . '/upgrade/update-database.php';
    exec("$upgrade_script 2>&1", $upgrade_log, $rc);
    if ($rc != 0) {
        $upgrade_log = htmlspecialchars(implode("\n", $upgrade_log));
        return "Database setup failed on upgrade. Upgrade log:<br/><pre>$upgrade_log</pre><br/>You may want run update script <tt>$upgrade_script</tt> manually.";
    }

    return 'success';
}


function ev_gettext($str)
{
    return $str;
}
