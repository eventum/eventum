<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
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


/**
 * Class to handle form validation in the server-side, duplicating the
 * javascript based validation available in most forms, to make sure
 * the data integrity is the best possible.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

class Validation
{
    /**
     * Method used to check whether a string is totally compromised of
     * whitespace characters, such as spaces, tabs or newlines.
     *
     * @access  public
     * @param   string $str The string to check against
     * @return  boolean
     */
    function isWhitespace($str)
    {
        $str = trim($str);
        if (strlen($str) == 0) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Method used to check whether an email address is a valid one.
     *
     * @access  public
     * @param   string $str The email address to check against
     * @return  boolean
     */
    function isEmail($str)
    {
        $valid_chars = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i',
                                'j', 'l', 'k', 'm', 'n', 'o', 'p', 'q', 'r',
                                's', 't', 'u', 'w', 'v', 'x', 'y', 'z',
                                '0', '1', '2', '3', '4', '5', '6', '7',
                                '8', '9');
        $extended_chars = array('.', '+', '_', '-', '@');
        $str = strtolower($str);

        // we need at least one @ symbol
        if (!strstr($str, '@')) {
            return false;
        }
        // and no more than one @ symbol
        if (strpos($str, '@') != strrpos($str, '@')) {
            return false;
        }
        // check for invalid characters in the email address
        for ($i = 0; $i < strlen($str); $i++) {
            if ((!in_array(substr($str, $i, 1), $valid_chars)) &&
                    (!in_array(substr($str, $i, 1), $extended_chars))) {
                return false;
            }
        }
        // email addresses need at least one dot (but also allow for user@localhost addresses)
        if ((!strstr($str, '.')) && (substr($str, strrpos($str, '@')) != '@localhost')) {
            return false;
        }
        // no two dots alongside each other
        if (strstr($str, '..')) {
            return false;
        }
        // do an extra check for a dot as the last character of an address
        array_shift($extended_chars);
        if ((substr($str, strlen($str)-1) == '.') &&
                (substr($str, strrpos($str, '@')) != '@localhost.')) {
            return false;
        }
        // the last character cannot be one of the extended ones
        if (in_array(substr($str, strlen($str)-1), $extended_chars)) {
            return false;
        }
        return true;
    }


    /**
     * Method used to check whether a string has only valid (ASCII)
     * characters.
     *
     * @access  public
     * @param   string $str The string to check against
     * @return  boolean
     */
    function hasValidChars($str)
    {
        $valid_chars = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i',
                                'j', 'l', 'k', 'm', 'n', 'o', 'p', 'q', 'r',
                                's', 't', 'u', 'w', 'v', 'x', 'y', 'z');

        for ($i = 0; $i < strlen($str); $i++) {
            if (!in_array(substr($str, $i, 1), $valid_chars)) {
                return false;
            }
        }
        return true;
    }
}
