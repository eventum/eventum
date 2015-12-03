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

/**
 * Class to hold methods and algorithms that wouldn't fit in other classes, such
 * as functions to work around PHP bugs or incompatibilities between separate
 * PHP configurations.
 */
class CLI_Misc
{
    /**
     * Method used to print a prompt asking the user for information.
     *
     * @param   string $message The message to print
     * @param   string $default_value The default value to be used if the user just press <enter>
     * @return  string The user response
     */
    public static function prompt($message, $default_value)
    {
        echo $message;
        if ($default_value !== false) {
            echo " [default: $default_value] -> ";
        } else {
            echo ' [required] -> ';
        }
        flush();
        $input = trim(self::getInput());
        if (empty($input)) {
            if ($default_value === false) {
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
     * @return  string The standard input value
     */
    public function getInput()
    {
        return fgets(STDIN);
    }

    public static function base64_decode($data)
    {
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $data[$k] = self::base64_decode($v);
            }
        } else {
            $data = base64_decode($data);
        }

        return $data;
    }
}
