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
// @(#) $Id: s.class.misc.php 1.3 04/01/19 15:19:26-00:00 jpradomaia $
//


/**
 * Class to hold methods and algorythms that woudln't fit in other classes, such
 * as functions to work around PHP bugs or incompatibilities between separate 
 * PHP configurations.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

class Misc
{
    /**
     * Method used to print a prompt asking the user for information.
     *
     * @access  public
     * @param   string $message The message to print
     * @param   string $default_value The default value to be used if the user just press <enter>
     * @return  string The user response
     */
    function prompt($message, $default_value)
    {
        echo $message;
        if ($default_value !== FALSE) {
            echo " [default: $default_value] -> ";
        } else {
            echo " [required] -> ";
        }
        flush();
        $input = trim(Misc::getInput(true));
        if (empty($input)) {
            if ($default_value === FALSE) {
                die("ERROR: Required parameter was not provided!\n");
            } else {
                return $default_value;
            }
        } else {
            return $input;
        }
    }


    /**
     * Method used to get the standard input.
     *
     * @access  public
     * @return  string The standard input value
     */
    function getInput($is_one_liner = FALSE)
    {
        $terminator = "\n";

        $stdin = fopen("php://stdin", "r");
        $input = '';
        while (!feof($stdin)) {
            $buffer = fgets($stdin, 256);
            $input .= $buffer;
            if (strstr($input, $terminator)) {
                break;
            }
        }
        return $input;
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Misc Class');
}
?>