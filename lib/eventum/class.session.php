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
 * Wrapper class for sessions. This is an initial bare bones implementation.
 * Additional methods will be later as needed.
 */
class Session
{
    /**
     * Sets the passed variable in the session using the specified name.
     *
     * @param   string $name Name to store variable under.
     * @param   mixed $var Variable to store in session.
     */
    public static function set($name, $var)
    {
        $_SESSION[$name] = $var;
    }

    /**
     * Returns the session variable specified by $name
     *
     * @param   string $name The name of variable to be returned.
     * @param   mixed  $default What should be returned if the named variabe is not set
     * @return  mixed The session variable.
     */
    public static function get($name, $default = null)
    {
        if (self::is_set($name)) {
            return $_SESSION[$name];
        } else {
            return $default;
        }
    }

    /**
     * Returns true if the session variable $name is set, false otherwise.
     *
     * @param   string $name The name of the variable to check.
     * @return  boolean If the variable is set
     */
    public static function is_set($name)
    {
        return isset($_SESSION[$name]);
    }

    /**
     * Initialize the session
     *
     * @param   integer $usr_id The ID of the user
     */
    public static function init($usr_id)
    {
        if (session_id() == '') {
            session_start();
        }

        // clear all old session variables
        $_SESSION = array();

        // regenerate ID to prevent session fixation
        session_regenerate_id();

        // set the IP in the session so we can check it later
        $_SESSION['login_ip'] = $_SERVER['REMOTE_ADDR'];

        // store user ID in session
        $_SESSION['usr_id'] = $usr_id;// XXX: Should we perform checks on this usr ID before accepting it?
    }

    /**
     * Verify that the current request to use the session has the same IP address as the request that started it.
     *
     * @param   integer $usr_id The ID of the user
     */
    public static function verify($usr_id)
    {
        if (session_id() == '') {
            session_start();
        }

        // Don't check the IP of the session, since this caused problems for users that use a proxy farm that uses
        // a different IP address each page load.
        if (!self::is_set('usr_id')) {
            self::init($usr_id);
        }
    }

    /**
     * Destroys the current session
     */
    public function destroy()
    {
        session_destroy();
    }
}
