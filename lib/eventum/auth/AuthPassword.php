<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2015 Eventum Team.                                     |
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
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

/**
 * Class dealing with user passwords
 */
class AuthPassword
{
    /**
     * Hash the password
     *
     * @param string $password The password to hash
     * @return string|false The hashed password, or false on error.
     */
    public static function hash($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify a password against a hash using a timing attack resistant approach
     *
     * @param string $hash The hash to verify against
     * @param string $password The password to verify
     * @return boolean If the password matches the hash
     */
    public static function verify($password, $hash)
    {
        $res = password_verify($password, $hash);
        if ($res) {
            return $res;
        }

        // try legacy authentication methods
        // try to do in constant time, i.e always do both checks
        $md5_64 = base64_encode(pack('H*', md5($password)));
        $md5 = md5($password);

        $cmp = 0;
        $cmp |= (int)self::cmp($hash, $md5_64);
        $cmp |= (int)self::cmp($hash, $md5);
        return (bool)$cmp;
    }

    /**
     * Compare strings using a timing attack resistant approach
     *
     * @param string $str1
     * @param string $str2
     * @return bool
     */
    private static function cmp($str1, $str2)
    {
        if (!is_string($str1) || Misc::countBytes($str1) != Misc::countBytes($str2) || Misc::countBytes($str1) <= 13) {
            return false;
        }

        $status = 0;
        $length = Misc::countBytes($str1);
        for ($i = 0; $i < $length; $i++) {
            $status |= (ord($str1[$i]) ^ ord($str2[$i]));
        }

        return $status === 0;
    }
}
