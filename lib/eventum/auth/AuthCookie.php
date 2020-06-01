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

class AuthCookie
{
    /**
     * Method to check if the user has cookie support enabled in his browser or not.
     *
     * @return bool
     */
    public static function hasCookieSupport(): bool
    {
        // check for any cookie being present
        return !empty($_COOKIE);
    }

    /**
     * Method used to set auth cookie in user's browser.
     *
     * @param int|string $user user Id or User email
     * @param bool $permanent Set to false to make session cookie (Expires when browser is closed)
     */
    public static function setAuthCookie($user, $permanent = true): void
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
        $config = Setup::getAuthCookie();

        Auth::setCookie($config['name'], $cookie, $permanent ? $config['expire'] : null);
        $_COOKIE[$config['name']] = $cookie;
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

        $cookie = self::getDecodedCookie(Setup::getAuthCookie()['name']);
        if (!$cookie || empty($cookie['email'])) {
            return null;
        }

        return $cookie;
    }

    /**
     * Method to check if the user has a valid auth cookie.
     * The cookie contents is validated for hash matching and user id from database.
     *
     * @return bool
     */
    public static function hasAuthCookie(): bool
    {
        $cookie = self::getDecodedCookie(Setup::getAuthCookie()['name']);
        if (!$cookie || empty($cookie['email']) || empty($cookie['hash'])) {
            return false;
        }

        $hash = self::generateHash($cookie['login_time'], $cookie['email']);
        if ($cookie['hash'] !== $hash) {
            return false;
        }

        $usr_id = User::getUserIDByEmail($cookie['email']);

        return !!$usr_id;
    }

    /**
     * Method used to remove auth cookie from the user's browser.
     */
    public static function removeAuthCookie(): void
    {
        Auth::removeCookie(Setup::getAuthCookie()['name']);
    }

    /**
     * Sets the current selected project for the user session.
     * If rememner is NULL, then existing value is attempted to autodetect from existing cookie
     *
     * @param int $prj_id The project ID
     * @param bool $remember Whether to automatically remember the setting or not
     */
    public static function setProjectCookie($prj_id, $remember = null): void
    {
        // try to preserve "remember" from existing cookie
        if ($remember === null) {
            $cookie = self::getProjectCookie();
            $remember = $cookie ? (bool)$cookie['remember'] : false;
        }

        $cookie = self::generateProjectCookie($prj_id, $remember);
        $config = Setup::getProjectCookie();

        Auth::setCookie($config['name'], $cookie, $config['expire']);
        $_COOKIE[$config['name']] = $cookie;
    }

    /**
     * Get structure of project cookie.
     * Requires prj_id being present or returns null.
     *
     * @return array|null
     */
    public static function getProjectCookie(): ?array
    {
        $cookie = self::getDecodedCookie(Setup::getProjectCookie()['name']);
        if (!$cookie || empty($cookie['prj_id'])) {
            return null;
        }

        return $cookie;
    }

    /**
     * Method used to remove project cookie from the user's browser.
     */
    public static function removeProjectCookie(): void
    {
        Auth::removeCookie(Setup::getProjectCookie()['name']);
    }

    /**
     * Get cookie string used for user authentication
     *
     * @param string $email
     * @param bool $permanent
     * @return string
     */
    private static function generateAuthCookie($email, $permanent = true): string
    {
        $time = time();
        $cookie = [
            'email' => $email,
            'login_time' => $time,
            'permanent' => $permanent,
            'hash' => self::generateHash($time, $email),
        ];

        return base64_encode(serialize($cookie));
    }

    /**
     * Get cookie string used to save project id
     *
     * @param int $prj_id
     * @param bool $remember
     * @return string
     */
    private static function generateProjectCookie($prj_id, $remember = false): string
    {
        $cookie = [
            'prj_id' => $prj_id,
            // it's stored as number, probably to save bytes in cookie size
            'remember' => (int)$remember,
        ];

        return base64_encode(serialize($cookie));
    }

    /**
     * Method used to get the unserialized contents of the specified cookie
     * name.
     *
     * @param   string $cookie_name The name of the cookie to check for
     * @return  array The unserialized contents of the cookie
     */
    private static function getDecodedCookie(string $cookie_name): ?array
    {
        if (empty($_COOKIE[$cookie_name])) {
            return null;
        }
        $data = base64_decode($_COOKIE[$cookie_name], true);
        if ($data === false) {
            return null;
        }

        return Misc::unserialize($data);
    }

    /**
     * Generate hash based on time and email
     *
     * @param int $time
     * @param string $email
     * @return string
     */
    private static function generateHash($time, $email): string
    {
        return md5(Auth::privateKey() . $time . $email);
    }
}
