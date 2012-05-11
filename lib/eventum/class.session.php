<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2012 Eventum Team.                              |
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
// | Authors: Bryan Alsdorf <bryan@mysql.com>                             |
// +----------------------------------------------------------------------+


/**
 * Wrapper class for sessions. This is an initial bare bones implementation.
 * Additional methods will be later as needed.
 *
 * @version 1.0
 * @author Bryan Alsdorf <bryan@mysql.com>
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
    public static function get($name, $default=null)
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
        session_start();

        // Don't check the IP of the session, since this caused problems for users that use a proxy farm that uses
        // a different IP address each page load.
        if (!self::is_set('usr_id')) {
            self::init($usr_id);
        }
    }


    /**
     * Destroys the current session
     */
    function destroy()
    {
        session_destroy();
    }
}
