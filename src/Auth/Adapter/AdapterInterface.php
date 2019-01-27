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

namespace Eventum\Auth\Adapter;

interface AdapterInterface
{
    /**
     * Checks whether the provided password match against the login or email
     * address provided.
     *
     * @param   string $login The login or email to check for
     * @param   string $password The password of the user to check for
     * @return  bool
     */
    public function verifyPassword(string $login, string $password): bool;

    /**
     * Method used to update the account password for a specific user.
     *
     * @param   int $usr_id The user ID
     * @param   string $password the password
     * @return  bool true if update worked, false otherwise
     */
    public function updatePassword(int $usr_id, string $password): bool;

    /**
     * Returns true if User Id exists.
     *
     * IMPORTANT: This method must not create local user!
     *
     * @param string $login email address, an alias, the external login id or any other info the backend can handle
     * @return bool
     * @since 3.0.8
     */
    public function userExists(string $login): bool;

    /**
     * Returns the user ID for the specified login. This can be the email address, an alias,
     * the external login id or any other info the backend can handle.
     *
     * @param $login
     * @return  int|null The user id or null
     */
    public function getUserId(string $login): ?int;

    /**
     * If this backend allows the user to update their name.
     *
     * @param int $usr_id
     * @return bool
     */
    public function canUserUpdateName(int $usr_id): bool;

    /**
     * If this backend allows the user to update their email.
     *
     * @param int $usr_id
     * @return bool
     */
    public function canUserUpdateEmail(int $usr_id): bool;

    /**
     * If this backend allows the user to update their password.
     *
     * @param int $usr_id
     * @return bool
     */
    public function canUserUpdatePassword(int $usr_id): bool;

    /**
     * Returns a URL to redirect the user to when they attempt to login or null if the native login pages
     * should be used.
     *
     * @return  string The login url or null
     */
    public function getExternalLoginURL(): ?string;

    /**
     * Returns true if the user should automatically be redirected to the external login URL, false otherwise
     *
     * @return  bool
     */
    public function autoRedirectToExternalLogin(): bool;

    /**
     * Called on every page load and can be used to process external authentication checks before the rest of the
     * authentication process happens.
     */
    public function checkAuthentication(): void;

    /**
     * Called when a user logs out.
     */
    public function logout(): void;
}
