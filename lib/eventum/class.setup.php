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

use Eventum\Config\ConfigPersistence;
use Eventum\Monolog\Logger;
use Symfony\Component\Filesystem\Exception\IOException;
use Zend\Config\Config;

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
        $existing = $config[$section]->toArray();

        // add defaults
        $config->merge(new Config([$section => $defaults]));
        // and then whatever was already there
        $config->merge(new Config([$section => $existing]));

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
        try {
            $clone = clone $config;
            // save ldap config to separate file
            $ldap = $clone->ldap;
            unset($clone->ldap);

            self::saveConfig(self::getSetupFile(), $clone);
            if ($ldap) {
                self::saveConfig(self::getConfigPath() . '/ldap.php', $ldap);
            }
        } catch (Exception $e) {
            $code = $e->getCode();
            Logger::app()->error($e);

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
     * Initialize config object, load it from setup files, merge defaults.
     */
    private static function initialize(): Config
    {
        $loader = new ConfigPersistence();

        $config = new Config(self::getDefaults(), true);
        $config->merge(new Config($loader->load(self::getSetupFile())));

        // some subtrees are saved to different files
        $extra_configs = [
            'ldap' => self::getConfigPath() . '/ldap.php',
        ];

        foreach ($extra_configs as $section => $filename) {
            if (!file_exists($filename)) {
                continue;
            }

            $subConfig = $loader->load($filename);
            if ($subConfig) {
                $config->merge(new Config([$section => $subConfig]));
            }
        }

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

            'email_error' => [],

            'email_routing' => [
                'warning' => [],
            ],
            'note_routing' => [],
            'draft_routing' => [],

            'subject_based_routing' => [],

            'email_reminder' => [],

            'extensions' => [],

            'handle_clock_in' => 'enabled',

            // default expiry: 5 minutes
            'issue_lock' => 300,

            'relative_date' => 'enabled',
            'markdown' => 'disabled',
            'audit_trail' => 'disabled',

            'attachments' => [
                'default_adapter' => 'pdo',
                'adapters' => [],
            ],
        ];

        return $defaults;
    }
}
