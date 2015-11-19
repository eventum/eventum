<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2015 Eventum Team.                              |
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
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+

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
     * @return Config
     * @deprecated wrapper for Setup::get() for legacy compatibility
     */
    public static function load()
    {
        return self::get();
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
        $config->merge(new Config(array($section => $defaults)));
        // and then whatever was already there
        $config->merge(new Config(array($section => $existing)));

        return $config[$section];
    }

    /**
     * Method used to save the setup options for the application.
     * The $options are merged with existing config and then saved.
     *
     * @param array $options Options to modify (does not need to be full setup)
     * @return integer 1 if the update worked, -1 or -2 otherwise
     */
    public static function save($options = array())
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
        $config->merge(new Config(self::loadConfigFile(APP_SETUP_FILE, $migrate)));

        if ($migrate) {
            // save config in new format
            self::saveConfig(APP_SETUP_FILE, $config);
        }

        // some subtrees are saved to different files
        $extra_configs = array(
            'ldap' => APP_CONFIG_PATH . '/ldap.php',
        );

        foreach ($extra_configs as $section => $filename) {
            if (!file_exists($filename)) {
                continue;
            }

            $subconfig = self::loadConfigFile($filename, $migrate);
            if ($subconfig) {
                if ($migrate) {
                    // save config in new format
                    self::saveConfig($filename, new Config($subconfig));
                }
                $config->merge(new Config(array($section => $subconfig)));
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
    private static function loadConfigFile($path, &$migrate)
    {
        $eventum_setup_string = $eventum_setup = null;
        $ldap_setup = null;

        // return empty array if the file is empty
        // this is to help eventum installation wizard to proceed
        if (!file_exists($path) || !filesize($path)) {
            return array();
        }

        // config array is supposed to be returned from that path
        /** @noinspection PhpIncludeInspection */
        $config = require $path;
        // fall back to old modes:
        // 1. $eventum_setup string
        // 2. base64 encoded $eventum_setup_string
        // 3. $ldap_setup
        if (isset($eventum_setup)) {
            $config = $eventum_setup;
            $migrate = true;
        } elseif (isset($eventum_setup_string)) {
            $config = unserialize(base64_decode($eventum_setup_string));
            $migrate = true;
        } elseif (isset($ldap_setup)) {
            $config = $ldap_setup;
            $migrate = true;
        } elseif ($config == 1) {
            // something went wrong, do not return "1", but empty array
            $config = array();
        }

        return $config;
    }

    /**
     * Save config to filesystem
     *
     * @param string $path
     * @param Config $config
     */
    private static function saveConfig($path, Config $config)
    {
        // if file exists, the file must be writable
        if (file_exists($path)) {
            if (!is_writable($path)) {
                throw new RuntimeException("File '$path' is not writable'", -2);
            }
        } else {
            // if file does not exist, it's parent dir must be writable
            $dir = dirname($path);
            if (!is_writable($dir)) {
                throw new RuntimeException("Directory '$dir' is not writable'", -1);
            }
        }

        $contents = self::dumpConfig($config);
        $res = file_put_contents($path, $contents);
        if ($res === false) {
            throw new RuntimeException("Can't write {$path}", -2);
        }
        clearstatcache(true, $path);
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
     * @return  string array of the default preferences
     */
    private static function getDefaults()
    {
        // at minimum should define top level array elements
        // so that fluent access works without errors and notices
        $defaults = array(
            'monitor' => array(
                'diskcheck' => array(
                    'status' => 'enabled',
                    'partition' => APP_PATH,
                ),
                'paths' => array(
                    'status' => 'enabled',
                ),
                'ircbot' => array(
                    'status' => 'enabled',
                ),
            ),

            'smtp' => array(),
            'ldap' => array(),

            'email_routing' => array(
                'warning' => array(),
            ),
            'note_routing' => array(),
            'draft_routing' => array(),

            'subject_based_routing' => array(),

            'email_reminder' => array(),

            'handle_clock_in' => 'enabled',

            // default expiry: 5 minutes
            'issue_lock' => 300,
        );

        return $defaults;
    }
}
