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

class AuthCookie
{
    /** @var int */
    private $usr_id;

    /** @var string */
    private $email;

    /**
     * @param int $usr_id optional user id
     * @param string $email optional user email
     */
    public function __construct($usr_id = null, $email = null)
    {
        $this->usr_id = $usr_id;
        $this->email = $email;
    }

    /**
     * Get email. Retrieve by user id if neccessary.
     *
     * @return string
     */
    private function getEmail()
    {
        if (!$this->email) {
            if (!$this->usr_id) {
                throw new LogicException("Need usr_id or email");
            }

            $user_details = User::getDetails($this->usr_id);
            $this->email = $user_details['usr_email'];
        }

        return $this->email;

    }

    /**
     * Get cookie used for user authentication
     *
     * @return string
     */
    public function generateCookie()
    {
        $time = time();
        $email = $this->getEmail();
        $cookie = array(
            'email' => $email,
            'login_time' => $time,
            'hash' => $this->generateHash($time, $email),
        );

        return base64_encode(serialize($cookie));
    }

    /**
     * Get cookie used to save project id
     *
     * @param int $prj_id
     * @param bool $remember
     * @return string
     */
    public static function generateProjectCookie($prj_id, $remember = false)
    {
        $cookie = array(
            'prj_id' => $prj_id,
            'remember' => $remember,
        );

        return base64_encode(serialize($cookie));
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
     * Method used to remove auth cookie from the user's browser.
     */
    public static function removeAuthCookie()
    {
        Auth::removeCookie(APP_COOKIE);
    }

    /**
     * Method used to remove project cookie from the user's browser.
     */
    public static function removeProjectCookie()
    {
        Auth::removeCookie(APP_PROJECT_COOKIE);
    }

    /**
     * Method used to check whether a cookie is valid or not.
     *
     * @param array $cookie The unserialized contents of the cookie
     * @return boolean
     */
    private static function isValidCookie($cookie)
    {
        if (empty($cookie['email']) || empty($cookie['hash'])) {
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
     * Method to check if the user has a valid auth cookie.
     *
     * @return boolean
     */
    public static function hasAuthCookie()
    {
        if (empty($_COOKIE[APP_COOKIE])) {
            return false;
        }
        $cookie = unserialize(base64_decode($_COOKIE[APP_COOKIE]));

        return self::isValidCookie($cookie);
    }

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
     * @param   string $email The email address to be stored in the cookie
     * @param   boolean $permanent Set to false to make session cookie (Expires when browser is closed)
     * @return  void
     */
    public static function setAuthCookie($email, $permanent = true)
    {
        $ac = new AuthCookie(null, $email);
        $cookie = $ac->generateCookie();
        Auth::setCookie(APP_COOKIE, $cookie, $permanent ? APP_COOKIE_EXPIRE : null);
    }

    /**
     * Sets the current selected project for the user session.
     *
     * @param int $prj_id The project ID
     * @param bool $remember Whether to automatically remember the setting or not
     */
    public static function setProjectCookie($prj_id, $remember = false)
    {
        $cookie = self::generateProjectCookie($prj_id, $remember);

        Auth::setCookie(APP_PROJECT_COOKIE, $cookie, APP_PROJECT_COOKIE_EXPIRE);
        $_COOKIE[APP_PROJECT_COOKIE] = $cookie;
    }

    /**
     * Creates a fake $_COOKIE entries so processes not run from a browser can access current user and project
     *
     * @param int|string $user User Id or User email.
     * @param int $prj_id The ID of the project.
     */
    public static function setDelegateCookies($user = null, $prj_id = null)
    {
        if ($user) {
            if (is_numeric($user)) {
                $user_id = $user;
                $email = null;
            } else {
                $user_id = null;
                $email = $user;
            }
            $ac = new AuthCookie($user_id, $email);
            $_COOKIE[APP_COOKIE] = $ac->generateCookie();
        }

        if ($prj_id) {
            $_COOKIE[APP_PROJECT_COOKIE] = self::generateProjectCookie($prj_id);
        }
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

    /**
     * Method used to get the unserialized contents of the specified cookie
     * name.
     *
     * @param   string $cookie_name The name of the cookie to check for
     * @return  array The unserialized contents of the cookie
     */
    private static function getDecodedCookie($cookie_name)
    {
        if (!isset($_COOKIE[$cookie_name])) {
            return null;
        }
        $data = base64_decode($_COOKIE[$cookie_name], true);
        if ($data === false) {
            return null;
        }

        return unserialize($data);
    }
}
