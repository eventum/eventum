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
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
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
        if (empty($setup) || $force == true) {
            $setup = array();
            $eventum_setup_string = $eventum_setup = null;

            require APP_SETUP_FILE;

            if (isset($eventum_setup)) {
                $setup = $eventum_setup;

            } elseif (isset($eventum_setup_string)) {
                // support reading legacy base64 encoded config
                $setup = unserialize(base64_decode($eventum_setup_string));
            }

            // merge with defaults
            $setup = Misc::array_extend(self::getDefaults(), $setup);
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
        // this is needed to check if the file can be created or not
        if (!file_exists(APP_SETUP_FILE)) {
            if (!is_writable(APP_CONFIG_PATH)) {
                clearstatcache();

                return -1;
            }
        } else {
            if (!is_writable(APP_SETUP_FILE)) {
                clearstatcache();

                return -2;
            }
        }

        $contents = "<"."?php\n\$eventum_setup = " . var_export($options, 1) . ";\n";
        $res = file_put_contents(APP_SETUP_FILE, $contents);
        if ($res === false) {
            return -2;
        }

        return 1;
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
