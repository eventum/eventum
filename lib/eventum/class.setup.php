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

use Eventum\Config\Config;
use Eventum\Config\ConfigPersistence;
use Eventum\Config\Paths;
use Eventum\Event\ConfigUpdateEvent;
use Eventum\Event\SystemEvents;
use Eventum\EventDispatcher\EventManager;
use Eventum\ServiceContainer;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * Class to handle the business logic related to setting and updating
 * the setup information of the system.
 */
class Setup
{
    /**
     * Get system setup options for the application.
     * The configuration is loaded from config/setup.php file
     *
     * @return Config The system-wide preferences
     * @deprecated use ServiceContainer::getConfig()
     */
    public static function get(): Config
    {
        static $config;
        if (!$config) {
            $config = self::initialize();
        }

        return $config;
    }

    /**
     * @return array
     * @since 3.8.0
     */
    public static function getAuthCookie(): array
    {
        $config = self::get();

        return [
            'name' => $config['cookie'],
            'expire' => time() + $config['cookie_expire'],
        ];
    }

    /**
     * @return array
     * @since 3.8.0
     */
    public static function getProjectCookie(): array
    {
        $config = self::get();

        return [
            'name' => $config['project_cookie'],
            'expire' => time() + $config['project_cookie_expire'],
        ];
    }

    /**
     * @since 3.8.0
     */
    public static function getBaseUrl(): string
    {
        return self::get()['base_url'];
    }

    /**
     * @since 3.8.0
     */
    public static function getRelativeUrl(): string
    {
        return self::get()['relative_url'];
    }

    /**
     * @since 3.8.0
     */
    public static function getHostname(): string
    {
        return self::get()['hostname'];
    }

    /**
     * @since 3.8.0
     */
    public static function getAppName(): string
    {
        return self::get()['name'];
    }

    /**
     * @since 3.8.0
     */
    public static function getShortName(): string
    {
        return self::get()['short_name'];
    }

    /**
     * Method used to get the title given to the current installation of Eventum.
     *
     * @return string The installation title
     * @since 3.8.0
     */
    public static function getToolCaption(): string
    {
        return self::get()['tool_caption'];
    }

    /**
     * @since 3.8.0
     */
    public static function getAnonymousUser(): ?string
    {
        return self::get()['anonymous_user'];
    }

    /**
     * @since 3.8.0
     */
    public static function getSystemUserId(): int
    {
        return self::get()['system_user_id'] ?? (defined('APP_SYSTEM_USER_ID') ? APP_SYSTEM_USER_ID : 1);
    }

    /**
     * @since 3.8.17
     */
    public static function getSmtpFrom(): ?string
    {
        return self::get()['smtp']['from'];
    }

    /**
     * Get the application default timezone.
     *
     * @return string The default timezone
     * @since 3.8.0
     */
    public static function getDefaultTimezone(): string
    {
        return self::get()['default_timezone'] ?? 'UTC';
    }

    /**
     * @since 3.8.17
     */
    public static function getDefaultLocale(): string
    {
        return self::get()['default_locale'] ?? 'en_US';
    }

    /**
     * Method used to get the default start of week day.
     *
     * @return int 0 - Sunday, 1 - Monday
     * @since 3.8.0
     */
    public static function getDefaultWeekday(): int
    {
        return self::get()['default_weekday'];
    }

    /**
     * @since 3.8.17
     */
    public static function getDefaultPagerSize(): int
    {
        return self::get()['default_pager_size'];
    }

    /**
     * @return array
     * @since 3.8.0
     */
    public static function getTemplatePaths(): array
    {
        $localPath = self::get()['local_path'];

        return [
            $localPath . '/templates',
            Paths::APP_TPL_PATH,
        ];
    }
    /**
     * @since 3.8.17
     */
    public static function isMaintenance(): bool
    {
        return self::get()['maintenance'];
    }

    /**
     * Set options to system config.
     * The changes are not stored to disk.
     *
     * @param array $options
     * @return Config returns the root config object
     */
    public static function set($options): Config
    {
        $config = self::get();
        $config->merge(new Config($options));

        return $config;
    }

    /**
     * Set default values for specific section of config
     *
     * @param string $section
     * @param array $defaults
     * @return Config returns section that was just configured
     */
    public static function setDefaults($section, array $defaults): Config
    {
        $config = self::get();
        $existing = isset($config[$section]) ? $config[$section]->toArray() : null;

        // add defaults
        $config->merge(new Config([$section => $defaults]));
        // and then whatever was already there
        if ($existing) {
            $config->merge(new Config([$section => $existing]));
        }

        return $config[$section];
    }

    /**
     * Method used to save the setup options for the application.
     * The $options are merged with existing config and then saved.
     *
     * @param array $options Options to modify (does not need to be full setup)
     * @return int 1 if the update worked, -1 or -2 otherwise
     */
    public static function save($options = [])
    {
        $config = self::set($options);

        $event = new ConfigUpdateEvent($config);
        EventManager::dispatch(SystemEvents::CONFIG_SAVE, $event);

        try {
            self::saveConfig(self::getSetupFile(), $config);
        } catch (Exception $e) {
            $code = $e->getCode();
            ServiceContainer::getLogger()->error($e);

            return $code ?: -1;
        }

        return 1;
    }

    /**
     * @since 3.5.0
     */
    public static function getConfigPath(): string
    {
        return dirname(__DIR__, 2) . '/config';
    }

    /**
     * @since 3.5.0
     */
    public static function getSetupFile(): string
    {
        return self::getConfigPath() . '/setup.php';
    }

    /**
     * @since 3.9.3
     */
    public static function getPrivateKeyPath(): string
    {
        return self::getConfigPath() . '/private_key.php';
    }

    /**
     * @since 3.8.0
     * @return bool
     */
    public static function needsSetup(): bool
    {
        $setupFile = self::getSetupFile();

        return !file_exists($setupFile) || !filesize($setupFile);
    }

    /**
     * Initialize config object, load it from setup files, merge defaults.
     */
    private static function initialize(): Config
    {
        $loader = new ConfigPersistence();

        $config = new Config(self::getDefaults(), true);
        $config->merge(new Config($loader->load(self::getSetupFile())));

        return $config;
    }

    /**
     * Save config to filesystem
     *
     * @param string $path
     * @param Config $config
     */
    private static function saveConfig($path, Config $config): void
    {
        try {
            $store = new ConfigPersistence();
            $store->store($path, $config->toArray());
        } catch (IOException $e) {
            throw new RuntimeException($e->getMessage(), -2);
        }
    }

    /**
     * Method used to get the system-wide defaults.
     *
     * @return array of the default preferences
     */
    private static function getDefaults(): array
    {
        $appPath = dirname(__DIR__, 2);

        // at minimum should define top level array elements
        // so that fluent access works without errors and notices
        $defaults = [
            'short_name' => 'Eventum',
            'tool_caption' => 'Eventum',
            'cookie' => 'eventum',
            'relative_url' => '/',
            'monitor' => [
                'diskcheck' => [
                    'status' => 'enabled',
                    'partition' => $appPath,
                ],
                'paths' => [
                    'status' => 'enabled',
                ],
                'ircbot' => [
                    'status' => 'enabled',
                ],
            ],

            'scm' => [],
            'smtp' => [],
            'ldap' => [],

            'email_error' => [
                'subject' => '%extra.short_name%: %message%',
            ],

            'email_routing' => [
                'warning' => [],
            ],
            'note_routing' => [],
            'draft_routing' => [],

            'subject_based_routing' => [],

            'email_reminder' => [],

            'extensions' => [],

            'xhgui' => [
                // https://github.com/eventum/eventum/pull/519
                'status' => 'disabled',
            ],

            'sentry' => [
                'status' => 'disabled',
                // dsn consists of: 'https://<key>@<domain>/<project>'
                'key' => '',
                'project' => '',
                'domain' => '',
            ],

            'handle_clock_in' => 'enabled',

            // default expiry: 5 minutes
            'issue_lock' => 300,

            'relative_date' => 'enabled',
            'audit_trail' => 'disabled',

            'attachments' => [
                'default_adapter' => 'pdo',
                'adapters' => [],
            ],
        ];

        return $defaults;
    }
}
