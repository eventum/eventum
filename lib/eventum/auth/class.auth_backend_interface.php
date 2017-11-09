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
     * @return  bool
     */
    public function verifyPassword($login, $password);

    /**
     * Method used to update the account password for a specific user.
     *
     * @param   int $usr_id The user ID
     * @param   string $password the password
     * @return  bool true if update worked, false otherwise
     */
    public function updatePassword($usr_id, $password);

    /**
     * Returns true if User Id exists.
     *
     * IMPORTANT: This method must not create local user!
     *
     * @param string $login email address, an alias, the external login id or any other info the backend can handle
     * @return bool
     * @since 3.0.8
     */
    public function userExists($login);

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
     * @param   int $usr_id The ID of the user
     * @return  bool
     */
    public function incrementFailedLogins($usr_id);

    /**
     * Reset the failed logins attempts for this user
     *
     * @param   int $usr_id The ID of the user
     * @return  bool
     */
    public function resetFailedLogins($usr_id);

    /**
     * Returns the true if the account is currently locked because of Back-Off locking
     *
     * @param   int $usr_id The ID of the user
     * @return  bool
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
     * Returns true if the user should automatically be redirected to the external login URL, false otherwise
     *
     * @return  bool
     */
    public function autoRedirectToExternalLogin();

    /**
     * Called on every page load and can be used to process external authentication checks before the rest of the
     * authentication process happens.
     */
    public function checkAuthentication();

    /**
     * Called when a user logs out.
     */
    public function logout();
}
