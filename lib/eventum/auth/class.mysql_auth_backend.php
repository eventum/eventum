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

use Eventum\Db\DatabaseException;

/**
 * MySQL (builtin) auth backend
 */
class Mysql_Auth_Backend implements Auth_Backend_Interface
{
    /**
     * Checks whether the provided password match against the email
     * address provided.
     *
     * @param   string $login The email address to check for
     * @param   string $password The password of the user to check for
     * @return  bool
     */
    public function verifyPassword($login, $password)
    {
        $usr_id = User::getUserIDByEmail($login, true);
        $user = User::getDetails($usr_id);
        $hash = $user['usr_password'];

        if (!AuthPassword::verify($password, $hash)) {
            self::incrementFailedLogins($usr_id);

            return false;
        }

        self::resetFailedLogins($usr_id);

        // check if hash needs rehashing,
        // old md5 or more secure default
        if (AuthPassword::needs_rehash($hash)) {
            self::updatePassword($usr_id, $password);
        }

        return true;
    }

    /**
     * Update the account password hash for a specific user.
     *
     * @param   int $usr_id The user ID
     * @param   string $password the password
     * @return  bool
     */
    public function updatePassword($usr_id, $password)
    {
        $stmt = 'UPDATE
                    `user`
                 SET
                    usr_password=?
                 WHERE
                    usr_id=?';
        $params = [AuthPassword::hash($password), $usr_id];
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    public function userExists($login)
    {
        $usr_id = $this->getUserIDByLogin($login);

        return $usr_id > 0;
    }

    public function getUserIDByLogin($login)
    {
        return User::getUserIDByEmail($login, true);
    }

    /**
     * Increment the failed logins attempts for this user
     *
     * @param   int $usr_id The ID of the user
     * @return  bool
     */
    public function incrementFailedLogins($usr_id)
    {
        $stmt = 'UPDATE
                    `user`
                 SET
                    usr_failed_logins = usr_failed_logins + 1,
                    usr_last_failed_login = NOW()
                 WHERE
                    usr_id=?';
        try {
            DB_Helper::getInstance()->query($stmt, [$usr_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Reset the failed logins attempts for this user
     *
     * @param   int $usr_id The ID of the user
     * @return  bool
     */
    public function resetFailedLogins($usr_id)
    {
        $stmt = 'UPDATE
                    `user`
                 SET
                    usr_failed_logins = 0,
                    usr_last_login = NOW(),
                    usr_last_failed_login = NULL
                 WHERE
                    usr_id=?';
        try {
            DB_Helper::getInstance()->query($stmt, [$usr_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Returns the true if the account is currently locked because of Back-Off locking
     *
     * @param   string $usr_id The email address to check for
     * @return  bool
     */
    public function isUserBackOffLocked($usr_id)
    {
        if (!is_int(APP_FAILED_LOGIN_BACKOFF_COUNT)) {
            return false;
        }
        $stmt = 'SELECT
                    IF( usr_failed_logins >= ?, NOW() < DATE_ADD(usr_last_failed_login, INTERVAL ' . APP_FAILED_LOGIN_BACKOFF_MINUTES . ' MINUTE), 0)
                 FROM
                    `user`
                 WHERE
                    usr_id=?';
        $params = [APP_FAILED_LOGIN_BACKOFF_COUNT, $usr_id];
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, $params);
        } catch (DatabaseException $e) {
            return true;
        }

        return $res == 1;
    }

    public function canUserUpdateName($usr_id)
    {
        return true;
    }

    public function canUserUpdateEmail($usr_id)
    {
        return true;
    }

    public function canUserUpdatePassword($usr_id)
    {
        return true;
    }

    /**
     * Returns a URL to redirect the user to when they attempt to login or null if the native login pages
     * should be used.
     *
     * @return  string The login url or null
     */
    public function getExternalLoginURL()
    {
        return null;
    }

    /**
     * Called on every page load and can be used to process external authentication checks before the rest of the
     * authentication process happens.
     */
    public function checkAuthentication()
    {
        return null;
    }

    /**
     * Called when a user logs out.
     *
     * @return mixed
     */
    public function logout()
    {
        return null;
    }

    /**
     * Returns true if the user should automatically be redirected to the external login URL, false otherwise
     *
     * @return  bool
     */
    public function autoRedirectToExternalLogin()
    {
        return false;
    }
}
