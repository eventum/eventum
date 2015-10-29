<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2012 - 2015 Eventum Team.                              |
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
 * Auth Backend Interface
 */
interface Auth_Backend_Interface
{
    /**
     * Checks whether the provided password match against the login or email
     * address provided.
     *
     * @param   string $login The login or email to check for
     * @param   string $password The password of the user to check for
     * @return  boolean
     */
    public function verifyPassword($login, $password);

    /**
     * Method used to update the account password for a specific user.
     *
     * @param   integer $usr_id The user ID
     * @param   string  $password The password.
     * @return  boolean true if update worked, false otherwise
     */
    public function updatePassword($usr_id, $password);

    /**
     * Returns the user ID for the specified login. This can be the email address, an alias,
     * the external login id or any other info the backend can handle.
     *
     * @param $login
     * @return  int|null The user id or null
     */
    public function getUserIDByLogin($login);

    /**
     * If this backend allows the user to update their name.
     *
     * @param int $usr_id
     * @return bool
     */
    public function canUserUpdateName($usr_id);

    /**
     * If this backend allows the user to update their email.
     *
     * @param int $usr_id
     * @return bool
     */
    public function canUserUpdateEmail($usr_id);

    /**
     * If this backend allows the user to update their password.
     *
     * @param int $usr_id
     * @return bool
     */
    public function canUserUpdatePassword($usr_id);

    /**
     * Increment the failed logins attempts for this user
     *
     * @param   integer $usr_id The ID of the user
     * @return  boolean
     */
    public function incrementFailedLogins($usr_id);

    /**
     * Reset the failed logins attempts for this user
     *
     * @param   integer $usr_id The ID of the user
     * @return  boolean
     */
    public function resetFailedLogins($usr_id);

    /**
     * Returns the true if the account is currently locked because of Back-Off locking
     *
     * @param   integer $usr_id The ID of the user
     * @return  boolean
     */
    public function isUserBackOffLocked($usr_id);

    /**
     * Returns a URL to redirect the user to when they attempt to login or null if the native login pages
     * should be used.
     *
     * @return  string The login url or null
     */
    public function getExternalLoginURL();

    /**
     * Called on every page load and can be used to process external authentication checks before the rest of the
     * authentication process happens.
     *
     * @return null
     */
    public function checkAuthentication();

    /**
     * Called when a user logs out.
     * @return mixed
     */
    public function logout();
}
