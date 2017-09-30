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

/**
 * Class to handle form validation in the server-side, duplicating the
 * javascript based validation available in most forms, to make sure
 * the data integrity is the best possible.
 */
class Validation
{
    /**
     * Method used to check whether a string is totally compromised of
     * whitespace characters, such as spaces, tabs or newlines.
     *
     * @param   string $str The string to check against
     * @return  bool
     */
    public static function isWhitespace($str)
    {
        $str = trim($str);
        if (strlen($str) == 0) {
            return true;
        }

        return false;
    }

    /**
     * Method used to check whether an email address is a valid one.
     *
     * @param   string $str The email address to check against
     * @return  bool
     */
    public static function isEmail($str)
    {
        $valid_chars = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i',
                                'j', 'l', 'k', 'm', 'n', 'o', 'p', 'q', 'r',
                                's', 't', 'u', 'w', 'v', 'x', 'y', 'z',
                                '0', '1', '2', '3', '4', '5', '6', '7',
                                '8', '9', ];
        $extended_chars = ['.', '+', '_', '-', '@'];
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
        if ((substr($str, strlen($str) - 1) == '.') &&
                (substr($str, strrpos($str, '@')) != '@localhost.')) {
            return false;
        }
        // the last character cannot be one of the extended ones
        if (in_array(substr($str, strlen($str) - 1), $extended_chars)) {
            return false;
        }

        return true;
    }
}
