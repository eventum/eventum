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
use Eventum\AppInfo;
use Eventum\Controller\BaseController;
use Eventum\Monolog\Logger;
use Eventum\Setup\DatabaseSetup;
use Eventum\Setup\RequirementNotSatisfiedException;
use Eventum\Setup\Requirements;
use Eventum\Setup\SetupException;
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
        try {
            Requirements::check();
        } catch (RequirementNotSatisfiedException $e) {
            Misc::displayRequirementErrors($e->getErrors(), 'Eventum Setup');
            exit(1);
        }

        if ($this->cat === 'install') {
            $res = $this->installAction();
            $this->tpl->assign('result', $res);
            // check for the optional IMAP extension
            $this->tpl->assign('is_imap_enabled', function_exists('imap_open'));
        }
    }

    protected function prepareTemplate()
    {
        $appInfo = new AppInfo();
        $request = $this->getRequest();
        $relative_url = rtrim(dirname($request->getBaseUrl()), '/') . '/';
        $this->tpl->assign(
            [
                'core' => [
                    'rel_url' => $relative_url,
                    'app_title' => APP_NAME,
                    'app_version' => $appInfo->getVersion(),
                    'php_version' => PHP_VERSION,
                    'template_id' => 'setup',
                ],
                'userstyle' => '',
                'userscript' => '',
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

            return $cal->getFirstDayOfWeek() === IntlCalendar::DOW_MONDAY ? 1 : 0;
        }

        // default to Monday as it's default for "World" in CLDR's supplemental data
        return 1;
    }

    private function e($s)
    {
        return var_export($s, 1);
    }

    private function initLogger(): void
    {
        // init timezone, logger needs it
        if (!defined('APP_DEFAULT_TIMEZONE')) {
            $tz = $this->getRequest()->request->get('default_timezone');
            define('APP_DEFAULT_TIMEZONE', $tz ?: 'UTC');
        }

        Logger::initialize();
    }

    /**
     * return error message as string, or true indicating success
     * requires setup to be written first.
     */
    private function setupDatabase(): void
    {
        $this->initLogger();
        $post = $this->getRequest()->request;

        $db_config = [
            'db_name' => $post->get('db_name'),
            'user' => $post->get('eventum_user'),
            'password' => $post->get('eventum_password'),

            'drop_tables' => $post->get('drop_tables') === 'yes',
            'create_db' => $post->get('create_db') === 'yes',
            'alternate_user' => $post->get('alternate_user') === 'yes',
            'create_user' => $post->get('create_user') === 'yes',
        ];

        $dbs = new DatabaseSetup();
        try {
            $db_result = $dbs->run($db_config);
        } catch (SetupException $e) {
            $this->tpl->assign('db_result', $e->getMessage());
            throw new RuntimeException($e->getType());
        }
        $this->tpl->assign('db_result', $db_result);
    }

    /**
     * write initial values for setup file
     */
    private function writeSetup(): void
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
            'charset' => 'utf8',
            'socket' => $socket,
        ];

        Setup::save($setup);
    }

    private function writeConfig(): void
    {
        $post = $this->getRequest()->request;
        $configPath = Setup::getConfigPath();
        $configFilePath = $configPath . '/config.php';

        // disable the full-text search feature for certain mysql server users
        $mysql_version = DB_Helper::getInstance(false)->getOne('SELECT VERSION()');
        preg_match('/(\d{1,2}\.\d{1,2}\.\d{1,2})/', $mysql_version, $matches);
        $enable_fulltext = $matches[1] > '4.0.23';

        $protocol_type = $post->get('is_ssl') === 'yes' ? 'https://' : 'http://';

        $replace = [
            "'%{APP_HOSTNAME}%'" => $this->e($post->get('hostname')),
            "'%{CHARSET}%'" => $this->e(APP_CHARSET),
            "'%{APP_RELATIVE_URL}%'" => $this->e($post->get('relative_url')),
            "'%{APP_DEFAULT_TIMEZONE}%'" => $this->e($post->get('default_timezone')),
            "'%{APP_DEFAULT_WEEKDAY}%'" => (int)$post->getInt('default_weekday'),
            "'%{PROTOCOL_TYPE}%'" => $this->e($protocol_type),
            "'%{APP_ENABLE_FULLTEXT}%'" => $this->e($enable_fulltext),
        ];

        $config_contents = file_get_contents($configPath . '/config.dist.php');
        $config_contents = str_replace(array_keys($replace), array_values($replace), $config_contents);

        $fs = new Filesystem();
        $fs->dumpFile($configFilePath, $config_contents, null);
    }

    private function installAction(): string
    {
        try {
            Auth::generatePrivateKey();
            $this->writeSetup();
            $this->setupDatabase();
            $this->writeConfig();
        } catch (RuntimeException $e) {
            return $e->getMessage();
        }

        return 'success';
    }
}
