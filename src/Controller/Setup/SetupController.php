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

namespace Eventum\Controller\Setup;

use Auth;
use Date_Helper;
use DB_Helper;
use Eventum\Controller\BaseController;
use Eventum\Db\Adapter\AdapterInterface;
use Eventum\Db\DatabaseException;
use Eventum\Db\Migrate;
use Eventum\Monolog\Logger;
use Exception;
use IntlCalendar;
use Misc;
use RuntimeException;
use Setup;
use Symfony\Component\Filesystem\Filesystem;

class SetupController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'setup.tpl.html';

    /** @var string */
    private $cat;

    protected function configure()
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat');
    }

    protected function canAccess()
    {
        return true;
    }

    protected function defaultAction()
    {
        list($warnings, $errors) = $this->checkRequirements();
        if ($warnings || $errors) {
            Misc::displayRequirementErrors(array_merge($errors, $warnings), 'Eventum Setup');
            if ($errors) {
                exit(1);
            }
        }

        if ($this->cat == 'install') {
            $res = $this->install();
            $this->tpl->assign('result', $res);
            // check for the optional IMAP extension
            $this->tpl->assign('is_imap_enabled', function_exists('imap_open'));
        }
    }

    protected function prepareTemplate()
    {
        $request = $this->getRequest();
        $relative_url = dirname($request->getBaseUrl()) . '/';
        $this->tpl->assign(
            [
                'phpversion' => phpversion(),
                'core' => [
                    'rel_url' => $relative_url,
                    'app_title' => APP_NAME,
                    'template_id' => 'setup',
                ],
                'is_secure' => $request->isSecure(),
                'zones' => Date_Helper::getTimezoneList(),
                'default_timezone' => $this->getTimezone(),
                'default_weekday' => $this->getFirstWeekday(),
            ]
        );
    }

    protected function displayTemplate($tpl_name = null)
    {
        $this->tpl->displayTemplate(false);
    }

    /**
     * Checks for $file for write permission.
     *
     * IMPORTANT: if the file does not exist, an empty file is created.
     * @param string $file
     * @param string $desc
     */
    private function checkPermissions($file, $desc, $is_directory = false)
    {
        clearstatcache();
        if (!file_exists($file)) {
            if (!$is_directory) {
                // try to create the file ourselves then
                $fp = @fopen($file, 'w');
                if (!$fp) {
                    return $this->getPermissionError($file, $desc, $is_directory, false);
                }
                @fclose($fp);
            } else {
                if (!@mkdir($file)) {
                    return $this->getPermissionError($file, $desc, $is_directory, false);
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
                    return $this->getPermissionError($file, $desc, $is_directory, true);
                }
            } else {
                return $this->getPermissionError($file, $desc, $is_directory, true);
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

    /**
     * @param bool $is_directory
     * @param bool $exists
     */
    private function getPermissionError($file, $desc, $is_directory, $exists)
    {
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

    private function checkRequirements()
    {
        $errors = [];
        $warnings = [];

        $extensions = [
            // extension => array(IS_REQUIRED, MESSAGE_TO_DISPLAY)
            'gd' => [true,
                     'The GD extension needs to be enabled in your PHP.INI file in order for Eventum to work properly.', ],
            'session' => [true,
                          'The Session extension needs to be enabled in your PHP.INI file in order for Eventum to work properly.', ],
            'pdo_mysql' => [true,
                            'The PDO MySQL extension needs to be enabled in your PHP.INI file in order for Eventum to work properly.', ],
            'json' => [true,
                       'The json extension needs to be enabled in your PHP.INI file in order for Eventum to work properly.', ],
            'mbstring' => [false,
                           'The Multibyte String Functions extension is not enabled in your PHP installation. For localization to work properly '
                           .
                           'You need to install this extension. If you do not install this extension localization will be disabled.', ],
            'iconv' => [false, 'The ICONV extension is not enabled in your PHP installation. ' .
                        'You need to install this extension for optimal operation. If you do not install this extension some unicode data will be corrupted.', ],
        ];

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
            $errors[]
                = "The 'file_uploads' directive needs to be enabled in your PHP.INI file in order for Eventum to work properly.";
        }

        $error = $this->checkPermissions(APP_CONFIG_PATH, "Directory '" . APP_CONFIG_PATH . "'", true);
        if (!empty($error)) {
            $errors[] = $error;
        }
        $error = $this->checkPermissions(APP_SETUP_FILE, "File '" . APP_SETUP_FILE . "'");
        if (!empty($error)) {
            $errors[] = $error;
        }
        $error = $this->checkPermissions(
            APP_CONFIG_PATH . '/private_key.php', "File '" . APP_CONFIG_PATH . '/private_key.php' . "'"
        );
        if (!empty($error)) {
            $errors[] = $error;
        }
        $error = $this->checkPermissions(
            APP_CONFIG_PATH . '/config.php', "File '" . APP_CONFIG_PATH . '/config.php' . "'"
        );
        if (!empty($error)) {
            $errors[] = $error;
        }

        $error = $this->checkPermissions(APP_LOCKS_PATH, "Directory '" . APP_LOCKS_PATH . "'", true);
        if (!empty($error)) {
            $errors[] = $error;
        }
        $error = $this->checkPermissions(APP_LOG_PATH, "Directory '" . APP_LOG_PATH . "'", true);
        if (!empty($error)) {
            $errors[] = $error;
        }
        $error = $this->checkPermissions(APP_TPL_COMPILE_PATH, "Directory '" . APP_TPL_COMPILE_PATH . "'", true);
        if (!empty($error)) {
            $errors[] = $error;
        }
        $error = $this->checkPermissions(APP_ERROR_LOG, "File '" . APP_ERROR_LOG . "'");
        if (!empty($error)) {
            $errors[] = $error;
        }

        return [$warnings, $errors];
    }

    /**
     * @param string $type
     * @param string $message
     *
     * @return string|null
     */
    private function getErrorMessage($type, $message)
    {
        if (empty($message)) {
            return '';
        }
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

    private function getTimezone()
    {
        $ini = ini_get('date.timezone');
        if ($ini) {
            return $ini;
        }

        // if php.ini is not configured, this function is noisy
        return @date_default_timezone_get();
    }

    private function getFirstWeekday()
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
    private function checkDatabaseExists($conn, $database)
    {
        $exists = $conn->getOne('SHOW DATABASES LIKE ?', [$database]);

        return $exists;
    }

    /**
     * @param AdapterInterface $conn
     * @return array
     */
    private function getUserList($conn)
    {
        // avoid "1046 ** No database selected" error
        $conn->query('USE mysql');
        try {
            $users = $conn->getColumn('SELECT DISTINCT User FROM user');
        } catch (DatabaseException $e) {
            // if the user cannot select from the mysql.user table, then return an empty list
            return [];
        }

        // FIXME: why lowercase is necessary?
        $users = Misc::lowercase($users);

        return $users;
    }

    /**
     * @param AdapterInterface $conn
     * @return array
     */
    private function getTableList($conn)
    {
        $tables = $conn->getColumn('SHOW TABLES');

        // FIXME: why is lowercase necessary?
        $tables = Misc::lowercase($tables);

        return $tables;
    }

    private function e($s)
    {
        return var_export($s, 1);
    }

    /**
     * @param string $file
     */
    private function get_queries($file)
    {
        $contents = file_get_contents($file);
        $queries = explode(';', $contents);
        $queries = Misc::trim($queries);
        $queries = array_filter($queries);

        return $queries;
    }

    private function initlogger()
    {
        // init timezone, logger needs it
        if (!defined('APP_DEFAULT_TIMEZONE')) {
            $tz = $this->getRequest()->request->get('default_timezone');
            define('APP_DEFAULT_TIMEZONE', $tz ?: 'UTC');
        }

        // and APP_VERSION
        if (!defined('APP_VERSION')) {
            define('APP_VERSION', '3.x');
        }
        Logger::initialize();
    }

    private function getDb()
    {
        $this->initlogger();
        try {
            return DB_Helper::getInstance(false);
        } catch (DatabaseException $e) {
        }

        $err = $e->getMessage();

        // Given such PDO Exception:
        // "SQLSTATE[HY000] [2002] No such file or directory"
        // indicate that mysql default socket may be wrong
        if (strpos($err, 'No such file or directory') !== false) {
            $ini = 'pdo_mysql.default_socket';
            $err .= sprintf(". Please check that PHP ini parameter $ini='%s' is correct", ini_get($ini));
        }

        throw new RuntimeException($err, $e->getCode());
    }

    /**
     * return error message as string, or true indicating success
     * requires setup to be written first.
     */
    private function setup_database()
    {
        $conn = $this->getDb();
        $post = $this->getRequest()->request;

        $db_name = $post->get('db_name');
        $eventum_user = $post->get('eventum_user');
        $eventum_password = $post->get('eventum_password');

        $db_exists = $this->checkDatabaseExists($conn, $db_name);
        if (!$db_exists) {
            if ($post->get('create_db') == 'yes') {
                try {
                    $conn->query("CREATE DATABASE {{{$db_name}}}");
                } catch (DatabaseException $e) {
                    throw new RuntimeException($this->getErrorMessage('create_db', $e->getMessage()));
                }
            } else {
                throw new RuntimeException(
                    'The provided database name could not be found. Review your information or specify that the database should be created in the form below.'
                );
            }
        }

        // create the new user, if needed
        if ($post->get('alternate_user') == 'yes') {
            $user_list = $this->getUserList($conn);
            if ($user_list) {
                $user_exists = in_array(strtolower($eventum_user), $user_list);

                if ($post->get('create_user') == 'yes') {
                    if (!$user_exists) {
                        $stmt
                            = "GRANT SELECT, UPDATE, DELETE, INSERT, ALTER, DROP, CREATE, INDEX ON {{{$db_name}}}.* TO ?@'%' IDENTIFIED BY ?";
                        try {
                            $conn->query($stmt, [$eventum_user, $eventum_password]);
                        } catch (DatabaseException $e) {
                            throw new RuntimeException($this->getErrorMessage('create_user', $e->getMessage()));
                        }
                    }
                } else {
                    if (!$user_exists) {
                        throw new RuntimeException(
                            'The provided MySQL username could not be found. Review your information or specify that the username should be created in the form below.'
                        );
                    }
                }
            }
        }

        // check if we can use the database
        try {
            $conn->query("USE {{{$db_name}}}");
        } catch (DatabaseException $e) {
            throw new RuntimeException($this->getErrorMessage('select_db', $e->getMessage()));
        }

        // set sql mode (sad that we rely on old bad mysql defaults)
        $conn->query("SET SQL_MODE = ''");

        // check the CREATE and DROP privileges by trying to create and drop a test table
        $table_list = $this->getTableList($conn);
        if (!in_array('eventum_test', $table_list)) {
            try {
                $conn->query('CREATE TABLE eventum_test (test CHAR(1))');
            } catch (DatabaseException $e) {
                throw new RuntimeException($this->getErrorMessage('create_test', $e->getMessage()));
            }
        }
        try {
            $conn->query('DROP TABLE eventum_test');
        } catch (DatabaseException $e) {
            throw new RuntimeException($this->getErrorMessage('drop_test', $e->getMessage()));
        }

        // if requested. drop tables first
        if ($post->get('drop_tables') == 'yes') {
            $queries = $this->get_queries(APP_PATH . '/upgrade/drop.sql');
            foreach ($queries as $stmt) {
                try {
                    $conn->query($stmt);
                } catch (DatabaseException $e) {
                    throw new RuntimeException($this->getErrorMessage('drop_table', $e->getMessage()));
                }
            }
        }

        // setup database with upgrade script
        $buffer = [];
        try {
            $dbmigrate = new Migrate(APP_PATH . '/upgrade');
            $dbmigrate->setLogger(
                function ($e) use (&$buffer) {
                    $buffer[] = $e;
                }
            );
            $dbmigrate->patch_database();
            $e = false;
        } catch (Exception $e) {
        }

        $this->tpl->assign('db_result', implode("\n", $buffer));

        if ($e) {
            $upgrade_script = APP_PATH . '/bin/upgrade.php';
            $error = [
                'Database setup failed on upgrade:',
                "<tt>{$e->getMessage()}</tt>",
                '',
                "You may want run update script <tt>$upgrade_script</tt> manually",
            ];
            throw new RuntimeException(implode('<br/>', $error));
        }

        // write db name now that it has been created
        $setup = [];
        $setup['database'] = $db_name;

        // substitute the appropriate values in config.php!!!
        if ($post->get('alternate_user') == 'yes') {
            $setup['username'] = $eventum_user;
            $setup['password'] = $eventum_password;
        }

        Setup::save(['database' => $setup]);
    }

    /**
     * write initial values for setup file
     */
    private function write_setup()
    {
        $post = $this->getRequest()->request;
        $setup = $post->get('setup');
        $setup['update'] = 1;
        $setup['closed'] = 1;
        $setup['emails'] = 1;
        $setup['files'] = 1;
        $setup['support_email'] = 'enabled';

        $db_hostname = $post->get('db_hostname');
        $parts = explode(':', $db_hostname, 2);
        if (count($parts) > 1) {
            list($hostname, $socket) = $parts;
        } else {
            list($hostname) = $parts;
            $socket = null;
        }

        $setup['database'] = [
            // connection info
            'hostname' => $hostname,
            'database' => '', // NOTE: db name has to be written after the table has been created
            'username' => $post->get('db_username'),
            'password' => $post->get('db_password'),
            'port' => 3306,
            'socket' => $socket,
        ];

        Setup::save($setup);
    }

    private function write_config()
    {
        $post = $this->getRequest()->request;
        $config_file_path = APP_CONFIG_PATH . '/config.php';

        // disable the full-text search feature for certain mysql server users
        $mysql_version = DB_Helper::getInstance(false)->getOne('SELECT VERSION()');
        preg_match('/(\d{1,2}\.\d{1,2}\.\d{1,2})/', $mysql_version, $matches);
        $enable_fulltext = $matches[1] > '4.0.23';

        $protocol_type = $post->get('is_ssl') == 'yes' ? 'https://' : 'http://';

        $replace = [
            "'%{APP_HOSTNAME}%'" => $this->e($post->get('hostname')),
            "'%{CHARSET}%'" => $this->e(APP_CHARSET),
            "'%{APP_RELATIVE_URL}%'" => $this->e($post->get('relative_url')),
            "'%{APP_DEFAULT_TIMEZONE}%'" => $this->e($post->get('default_timezone')),
            "'%{APP_DEFAULT_WEEKDAY}%'" => (int)$post->getInt('default_weekday'),
            "'%{PROTOCOL_TYPE}%'" => $this->e($protocol_type),
            "'%{APP_ENABLE_FULLTEXT}%'" => $this->e($enable_fulltext),
        ];

        $config_contents = file_get_contents(APP_CONFIG_PATH . '/config.dist.php');
        $config_contents = str_replace(array_keys($replace), array_values($replace), $config_contents);

        $fs = new Filesystem();
        $fs->dumpFile($config_file_path, $config_contents, null);
    }

    private function install()
    {
        try {
            Auth::generatePrivateKey();
            $this->write_setup();
            $this->setup_database();
            $this->write_config();
        } catch (RuntimeException $e) {
            return $e->getMessage();
        }

        return 'success';
    }
}
