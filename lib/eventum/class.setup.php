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

use Eventum\Monolog\Logger;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
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
    public static function get()
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
    public static function set($options)
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
    public static function setDefaults($section, array $defaults)
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

            self::saveConfig(APP_SETUP_FILE, $clone);
            if ($ldap) {
                self::saveConfig(APP_CONFIG_PATH . '/ldap.php', $ldap);
            }
        } catch (Exception $e) {
            $code = $e->getCode();
            Logger::app()->error($e);

            return $code ?: -1;
        }

        return 1;
    }

    /**
     * Initialize config object, load it from setup files, merge defaults.
     *
     * @return Config
     */
    private static function initialize()
    {
        $config = new Config(self::getDefaults(), true);
        $config->merge(new Config(self::loadConfigFile(APP_SETUP_FILE)));

        // some subtrees are saved to different files
        $extra_configs = [
            'ldap' => APP_CONFIG_PATH . '/ldap.php',
        ];

        foreach ($extra_configs as $section => $filename) {
            if (!file_exists($filename)) {
                continue;
            }

            $subconfig = self::loadConfigFile($filename);
            if ($subconfig) {
                $config->merge(new Config([$section => $subconfig]));
            }
        }

        return $config;
    }

    /**
     * Load config from $path.
     * Config file should return configuration array.
     *
     * @param string $path
     * @return array
     */
    private static function loadConfigFile($path)
    {
        // return empty array if the file is empty
        // this is to help eventum installation wizard to proceed
        if (!file_exists($path) || !filesize($path)) {
            return [];
        }

        // config array is supposed to be returned from that path
        /** @noinspection PhpIncludeInspection */
        return require $path;
    }

    /**
     * Save config to filesystem
     *
     * @param string $path
     * @param Config $config
     */
    private static function saveConfig($path, Config $config)
    {
        $contents = self::dumpConfig($config);

        try {
            $fs = new Filesystem();
            $fs->dumpFile($path, $contents);
        } catch (IOException $e) {
            throw new RuntimeException($e->getMessage(), -2);
        }
    }

    /**
     * Export config in a format to be stored to config file
     *
     * @param Config $config
     * @return string
     */
    private static function dumpConfig(Config $config)
    {
        return '<' . "?php\nreturn " . var_export($config->toArray(), 1) . ";\n";
    }

    /**
     * Method used to get the system-wide defaults.
     *
     * @return array of the default preferences
     */
    private static function getDefaults()
    {
        // at minimum should define top level array elements
        // so that fluent access works without errors and notices
        $defaults = [
            'monitor' => [
                'diskcheck' => [
                    'status' => 'enabled',
                    'partition' => APP_PATH,
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
