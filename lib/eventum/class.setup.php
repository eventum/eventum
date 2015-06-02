<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2014 Eventum Team.                              |
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
// | 51 Franklin Street, Suite 330                                          |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

/**
 * Class to handle the business logic related to setting and updating
 * the setup information of the system.
 */
class Setup
{
    /**
     * Method used to load the setup options for the application.
     *
     * @param   boolean $force If the data should be forced to be loaded again.
     * @return  array The system-wide preferences
     */
    public static function &load($force = false)
    {
        static $setup;
        if (!$setup || $force == true) {
            $setup = self::loadConfig(APP_SETUP_FILE, self::getDefaults());
        }

        return $setup;
    }

    /**
     * Method used to save the setup options for the application.
     *
     * @param   array $options The system-wide preferences
     * @return  integer 1 if the update worked, -1 or -2 otherwise
     */
    public static function save($options)
    {
        try {
            self::saveConfig(APP_SETUP_FILE, $options);
        } catch (Exception $e) {
            $code = $e->getCode();
            error_log($e->getMessage());
            error_log($e->getTraceAsString());

            return $code ?: -1;
        }

        return 1;
    }

    /**
     * Load config from $path and merge with $defaults.
     * Config file should return configuration array.
     *
     * @param string $path
     * @param array $defaults
     * @return array
     */
    private static function loadConfig($path, $defaults)
    {
        $eventum_setup_string = $eventum_setup = null;

        // config array is supposed to be returned from that path
        /** @noinspection PhpIncludeInspection */
        $config = require $path;

        // fall back to old modes:
        // 1. $eventum_setup string
        // 2. base64 encoded $eventum_setup_string
        // TODO: save it over so the support could be removed soon
        if (isset($eventum_setup)) {
            $config = $eventum_setup;
        } elseif (isset($eventum_setup_string)) {
            $config = unserialize(base64_decode($eventum_setup_string));
        }

        // merge with defaults
        if ($defaults) {
            $config = Misc::array_extend($defaults, $config);
        }

        return $config;
    }

    /**
     * Save config to filesystem
     *
     * @param string $path
     * @param array $config
     */
    private static function saveConfig($path, $config)
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
     * @param array $config
     * @return string
     */
    private static function dumpConfig($config)
    {
        return '<' . "?php\nreturn \$eventum_setup = " . var_export($config, 1) . ";\n";
    }

    /**
     * Method used to get the system-wide defaults.
     *
     * @return  string array of the default preferences
     */
    public static function getDefaults()
    {
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
            'handle_clock_in' => 'enabled',

            // default expiry: 5 minutes
            'issue_lock' => 300,
        );

        return $defaults;
    }
}
