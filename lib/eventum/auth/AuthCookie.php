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

class AuthCookie
{
    /**
     * Method to check if the user has cookie support enabled in his browser or not.
     *
     * @return bool
     */
    public static function hasCookieSupport()
    {
        // check for any cookie being present
        return !empty($_COOKIE);
    }

    /**
     * Method used to set auth cookie in user's browser.
     *
     * @param int|string $user User Id or User email.
     * @param boolean $permanent Set to false to make session cookie (Expires when browser is closed)
     */
    public static function setAuthCookie($user, $permanent = true)
    {
        if (!$user) {
            throw new LogicException('Need usr_id or email');
        }

        if (is_numeric($user)) {
            $user_details = User::getDetails($user);
            $email = $user_details['usr_email'];
        } else {
            $email = $user;
        }

        $cookie = self::generateAuthCookie($email, $permanent);
        Auth::setCookie(APP_COOKIE, $cookie, $permanent ? APP_COOKIE_EXPIRE : null);
        $_COOKIE[APP_COOKIE] = $cookie;
    }

    /**
     * Get structure of authentication cookie.
     * Requires cookie being valid and 'email' key being present
     *
     * @return array|null
     */
    public static function getAuthCookie()
    {
        if (!self::hasAuthCookie()) {
            return null;
        }

        $cookie = self::getDecodedCookie(APP_COOKIE);
        if (!$cookie || empty($cookie['email'])) {
            return null;
        }

        return $cookie;
    }

    /**
     * Method to check if the user has a valid auth cookie.
     * The cookie contents is validated for hash matching and user id from database.
     *
     * @return boolean
     */
    public static function hasAuthCookie()
    {
        $cookie = self::getDecodedCookie(APP_COOKIE);
        if (!$cookie || empty($cookie['email']) || empty($cookie['hash'])) {
            return false;
        }

        $hash = self::generateHash($cookie['login_time'], $cookie['email']);
        if ($cookie['hash'] != $hash) {
            return false;
        }

        $usr_id = User::getUserIDByEmail($cookie['email']);

        return !!$usr_id;
    }

    /**
     * Method used to remove auth cookie from the user's browser.
     */
    public static function removeAuthCookie()
    {
        Auth::removeCookie(APP_COOKIE);
    }

    /**
     * Sets the current selected project for the user session.
     * If rememner is NULL, then existing value is attempted to autodetect from existing cookie
     *
     * @param int $prj_id The project ID
     * @param bool $remember Whether to automatically remember the setting or not
     */
    public static function setProjectCookie($prj_id, $remember = null)
    {
        // try to preserve "remember" from existing cookie
        if ($remember === null) {
            $cookie = self::getProjectCookie();
            $remember = $cookie ? (bool) $cookie['remember'] : false;
        }

        $cookie = self::generateProjectCookie($prj_id, $remember);

        Auth::setCookie(APP_PROJECT_COOKIE, $cookie, APP_PROJECT_COOKIE_EXPIRE);
        $_COOKIE[APP_PROJECT_COOKIE] = $cookie;
    }

    /**
     * Get structure of project cookie.
     * Requires prj_id being present or returns null.
     *
     * @return array|null
     */
    public static function getProjectCookie()
    {
        $cookie = self::getDecodedCookie(APP_PROJECT_COOKIE);
        if (!$cookie || empty($cookie['prj_id'])) {
            return null;
        }

        return $cookie;
    }

    /**
     * Method used to remove project cookie from the user's browser.
     */
    public static function removeProjectCookie()
    {
        Auth::removeCookie(APP_PROJECT_COOKIE);
    }

    /**
     * Get cookie string used for user authentication
     *
     * @param bool|true $permanent
     * @return string
     */
    private static function generateAuthCookie($email, $permanent = true)
    {
        $time = time();
        $cookie = array(
            'email' => $email,
            'login_time' => $time,
            'permanent' => $permanent,
            'hash' => self::generateHash($time, $email),
        );

        return base64_encode(serialize($cookie));
    }

    /**
     * Get cookie string used to save project id
     *
     * @param int $prj_id
     * @param bool $remember
     * @return string
     */
    private static function generateProjectCookie($prj_id, $remember = false)
    {
        $cookie = array(
            'prj_id' => $prj_id,
            // it's stored as number, probably to save bytes in cookie size
            'remember' => (int) $remember,
        );

        return base64_encode(serialize($cookie));
    }

    /**
     * Method used to get the unserialized contents of the specified cookie
     * name.
     *
     * @param   string $cookie_name The name of the cookie to check for
     * @return  array The unserialized contents of the cookie
     */
    private static function getDecodedCookie($cookie_name)
    {
        if (empty($_COOKIE[$cookie_name])) {
            return null;
        }
        $data = base64_decode($_COOKIE[$cookie_name], true);
        if ($data === false) {
            return null;
        }

        return unserialize($data);
    }

    /**
     * Generate hash based on time and email
     *
     * @param int $time
     * @param string $email
     * @return string
     */
    private static function generateHash($time, $email)
    {
        return md5(Auth::privateKey() . $time . $email);
    }
}
