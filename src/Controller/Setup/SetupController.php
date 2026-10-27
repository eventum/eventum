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
use Eventum\AppInfo;
use Eventum\Controller\Traits\RedirectResponseTrait;
use Eventum\Controller\Traits\RequestTrait;
use Eventum\Controller\Traits\SmartyResponseTrait;
use Eventum\Db\Doctrine;
use Eventum\ServiceContainer;
use Eventum\Setup\DatabaseSetup;
use Eventum\Setup\RequirementNotSatisfiedException;
use Eventum\Setup\Requirements;
use Eventum\Setup\SetupException;
use IntlCalendar;
use Misc;
use Setup;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SetupController
{
    use SmartyResponseTrait;
    use RequestTrait;
    use RedirectResponseTrait;

    /** @var string */
    protected $tpl_name = 'setup.tpl.html';

    /** @var string */
    private $cat;

    /** @var string[] */
    private $params = [];

    public function defaultAction(Request $request): Response
    {
        $this->boot($request);

        // get out if already configured
        if (!$this->needsSetup()) {
            return $this->redirect(Setup::getRelativeUrl());
        }

        $this->cat = $request->request->get('cat');
        $this->request = $request;
        $params = [
            'is_imap_enabled' => function_exists('imap_open'),
            'header_templates' => [],
            '_process' => false,
        ];

        try {
            Requirements::check();
        } catch (RequirementNotSatisfiedException $e) {
            Misc::displayRequirementErrors($e->getErrors(), 'Eventum Setup');
            exit(1);
        }

        if ($this->cat === 'install') {
            try {
                $this->installAction();
                $params['result'] = 'success';
            } catch (SetupException $e) {
                $params['db_result'] = $e->getMessage();
                $params['result'] = $e->getType();
            }
        }

        return $this->renderTemplate($params);
    }

    protected function renderTemplate(array $params = []): Response
    {
        $appInfo = new AppInfo();
        $params += $this->params;
        $params += [
            'core' => [
                'rel_url' => Setup::getRelativeUrl(),
                'base_url' => Setup::getBaseUrl(),
                'app_title' => 'Eventum',
                'app_version' => $appInfo->getVersion(),
                'php_version' => PHP_VERSION,
                'template_id' => 'setup',
            ],
            'userstyle' => '',
            'userscript' => '',
            'is_secure' => $this->request->isSecure(),
            'zones' => Date_Helper::getTimezoneList(),
            'default_timezone' => $this->getTimezone(),
            'default_weekday' => $this->getFirstWeekday(),
        ];

        return $this->render($this->tpl_name, $params);
    }

    private function getTimezone(): string
    {
        $ini = ini_get('date.timezone');
        if ($ini) {
            return $ini;
        }

        // if php.ini is not configured, this function is noisy
        return @date_default_timezone_get();
    }

    private function getFirstWeekday(): int
    {
        $cal = IntlCalendar::createInstance();

        return $cal->getFirstDayOfWeek() === IntlCalendar::DOW_MONDAY ? 1 : 0;
    }

    /**
     * return error message as string, or true indicating success
     * requires setup to be written first.
     */
    private function setupDatabase(): void
    {
        $post = $this->getPost();

        $db_hostname = $post->get('db_hostname');
        $parts = explode(':', $db_hostname, 2);
        if (count($parts) > 1) {
            [$hostname, $socket] = $parts;
        } else {
            [$hostname] = $parts;
            $socket = null;
        }

        $dsn = [
            // connection info
            'hostname' => $hostname,
            'database' => '', // NOTE: db name has to be written after the table has been created
            'username' => $post->get('db_username'),
            'password' => $post->get('db_password'),
            'port' => 3306,
            'charset' => 'utf8',
            'socket' => $socket,
        ];

        $config = [
            'db_name' => $post->get('db_name'),
            'username' => $post->get('eventum_user'),
            'password' => $post->get('eventum_password'),

            'drop_tables' => $post->get('drop_tables') === 'yes',
            'create_db' => $post->get('create_db') === 'yes',
            'alternate_user' => $post->get('alternate_user') === 'yes',
            'create_user' => $post->get('create_user') === 'yes',
        ];

        Setup::save(['database' => $dsn]);
        $dsn = Doctrine::getUrl();
        putenv('DATABASE_URL=' . $dsn);

        $dbs = new DatabaseSetup();
        $db_result = $dbs->run($config);
        $this->params['db_result'] = $db_result;
    }

    /**
     * write initial values for setup file
     */
    private function writeSetup(): void
    {
        $post = $this->getPost();
        $setup = $post->get('setup');
        $setup['update'] = 1;
        $setup['closed'] = 1;
        $setup['emails'] = 1;
        $setup['files'] = 1;
        $setup['support_email'] = 'enabled';

        $setup['default_timezone'] = $post->get('default_timezone') ?: 'UTC';
        $setup['default_weekday'] = (int)$post->getInt('default_weekday');

        $protocol_type = $post->get('is_ssl') === 'yes' ? 'https://' : 'http://';
        $relativeUrl = $post->get('relative_url');

        $setup['base_url'] = "{$protocol_type}{$post->get('hostname')}{$relativeUrl}";
        $setup['cookie_path'] = $setup['cookie_url'] = $relativeUrl;
        $setup['relative_url'] = $relativeUrl;
        $setup['hostname'] = $post->get('hostname');

        // Special key indicating setup is not complete
        $setup['__installing'] = true;

        Setup::save($setup);
    }

    private function installAction(): void
    {
        Auth::generatePrivateKey();
        $this->writeSetup();
        $this->setupDatabase();
        $this->clearCache();
        $this->finishSetup();
    }

    private function clearCache(): void
    {
        $app = ServiceContainer::getApplication();
        $app->run(new StringInput('cache:clear --no-warmup'));
    }

    private function boot(Request $request): void
    {
        $setup = ServiceContainer::getConfig();

        $baseUrl = $request->getBaseUrl();
        $relative_url = rtrim(dirname($baseUrl, 2), '/') . '/';

        $setup['relative_url'] = $relative_url;
    }

    private function needsSetup(): bool
    {
        if (Setup::needsSetup()) {
            return true;
        }

        $setup = ServiceContainer::getConfig();

        return $setup['__installing'] ? true : false;
    }

    private function finishSetup(): void
    {
        $setup = ServiceContainer::getConfig();
        unset($setup['__installing']);

        Setup::save();
    }
}
