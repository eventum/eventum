<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2012 - 2013 Eventum Team.                              |
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
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// | Authors: Bryan Alsdorf <balsdorf@gmail.com>                          |
// +----------------------------------------------------------------------+
//


/**
 * Abstract class for auth backend
 */
abstract class Abstract_Auth_Backend
{
    /**
     * Checks whether the provided password match against the email
     * address provided.
     *
     * @access  public
     * @param   string $login The login to check for
     * @param   string $password The password of the user to check for
     * @return  boolean
     */
    public function verifyPassword($login, $password)
    {
        return false;
    }

    /**
     * Method used to update the account password for a specific user.
     *
     * @access  public
     * @param   integer $usr_id The user ID
     * @param   string  $password The password.
     * @return  boolean true if update worked, false otherwise
     */
    function updatePassword($usr_id, $password)
    {
        return false;
    }

    /**
     * Hashes the password according to APP_HASH_TYPE constant
     *
     * @param   string $password The plain text password
     * @return  string The hashed password
     */
    public static function hashPassword($password)
    {
        if (APP_HASH_TYPE == 'MD5-64') {
            return base64_encode(pack('H*', md5($password)));
        } else {
            // default to md5
            return md5($password);
        }
    }


    /**
     * Creates or updates local user entry for the specified ID.
     *
     * @param $login The $username ID of the user to create or update
     * @return  bool True if the user was created or updated, false otherwise
     */
    public function updateLocalUserFromBackend($login)
    {
        return false;
    }

    /**
     * Returns the user ID for the specified login. This can be the email address, an alias,
     * the external login id or any other info the backend can handle.
     *
     * @abstract
     * @param $login
     * @return  int|null The user id or null
     */
    abstract public function getUserIDByLogin($login);

    /**
     * If this backend allows the user to update their name.
     *
     * @param $usr_id
     * @return bool
     */
    public function canUserUpdateName($usr_id)
    {
        return true;
    }

    /**
     * If this backend allows the user to update their email.
     *
     * @param $usr_id
     * @return bool
     */
    public function canUserUpdateEmail($usr_id)
    {
        return true;
    }

    /**
     * If this backend allows the user to update their password.
     *
     * @param $usr_id
     * @return bool
     */
    public function canUserUpdatePassword($usr_id)
    {
        return true;
    }

    /**
     * Returns true if the backend is ready to process users, false otherwise.
     *
     * @return bool
     */
    public function isSetup()
    {
        return true;
    }

    /**
     * Increment the failed logins attempts for this user
     *
     * @access  public
     * @param   integer $usr_id The ID of the user
     * @return  boolean
     */
    public function incrementFailedLogins($usr_id)
    {
        return true;
    }

    /**
     * Reset the failed logins attempts for this user
     *
     * @access  public
     * @param   integer $usr_id The ID of the user
     * @return  boolean
     */
    public function resetFailedLogins($usr_id)
    {
        return true;
    }

    /**
     * Returns the true if the account is currently locked becouse of Back-Off locking
     *
     * @access  public
     * @param   integer $usr_id The ID of the user
     * @return  boolean
     */
    function isUserBackOffLocked($usr_id)
    {
        return false;
    }
}
