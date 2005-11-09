<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005 MySQL AB                              |
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
// +----------------------------------------------------------------------+
//
//

include_once(APP_INC_PATH . "class.error_handler.php");
include_once(APP_INC_PATH . "class.misc.php");

/**
 * Class to handle the business logic related to setting and updating
 * the setup information of the system.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

class Setup
{
    /**
     * Method used to load the setup options for the application.
     *
     * @access  public
     * @param   boolean $force If the data should be forced to be loaded again.
     * @return  array The system-wide preferences
     */
    function load($force = false)
    {
        static $setup;
        if ((empty($setup)) || ($force == true)) {
            include(APP_SETUP_FILE);
            $setup = unserialize(base64_decode($eventum_setup_string));
        }
        return $setup;
    }


    /**
     * Method used to save the setup options for the application.
     *
     * @access  public
     * @param   array $options The system-wide preferences
     * @return  integer 1 if the update worked, -1 or -2 otherwise
     */
    function save($options)
    {
        // this is needed to check if the file can be created or not
        if (!file_exists(APP_SETUP_FILE)) {
            if (!@is_writable(APP_SETUP_PATH)) {
                clearstatcache();
                return -1;
            }
        } else {
            if (!@is_writable(APP_SETUP_FILE)) {
                clearstatcache();
                return -2;
            }
        }
        $fp = @fopen(APP_SETUP_FILE, "w");
        if (!$fp) {
            return -2;
        }
        @flock($fp, LOCK_EX);
        @fwrite($fp, "<?php\n\$eventum_setup_string='" . base64_encode(serialize($options)) . "';\n?>");
        @flock($fp, LOCK_UN);
        @fclose($fp);
        return 1;
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Setup Class');
}
?>