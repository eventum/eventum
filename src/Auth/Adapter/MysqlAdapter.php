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

use Auth;
use DB_Helper;
use Eventum\Auth\AuthException;
use Eventum\Auth\PasswordHash;
use Eventum\Db\DatabaseException;
use Eventum\ServiceContainer;
use User;

/**
 * MySQL (builtin) auth backend
 */
class MysqlAdapter implements AdapterInterface
{
    public const displayName = 'MySQL builtin authentication adapter';

    /**
     * Checks whether the provided password match against the email
     * address provided.
     *
     * @param   string $login The email address to check for
     * @param   string $password The password of the user to check for
     * @return  bool
     */
    public function verifyPassword(string $login, string $password): bool
    {
        $usr_id = $this->getUserId($login);
        if (!$usr_id) {
            return false;
        }

        if ($this->isUserBackOffLocked($usr_id)) {
            throw new AuthException('account back-off locked', AuthException::ACCOUNT_BACKOFF_LOCKED);
        }

        $user = User::getDetails($usr_id);
        $hash = $user['usr_password'];

        if (!PasswordHash::verify($password, $hash)) {
            $this->incrementFailedLogins($usr_id);

            return false;
        }

        $this->resetFailedLogins($usr_id);

        // check if hash needs rehashing,
        // old md5 or more secure default
        if (PasswordHash::needs_rehash($hash)) {
            $this->updatePassword($usr_id, $password);
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
    public function updatePassword(int $usr_id, string $password): bool
    {
        $stmt = 'UPDATE
                    `user`
                 SET
                    usr_password=?
                 WHERE
                    usr_id=?';
        $params = [PasswordHash::hash($password), $usr_id];
        DB_Helper::getInstance()->query($stmt, $params);

        return true;
    }

    public function userExists(string $login): bool
    {
        $usr_id = $this->getUserId($login);

        return $usr_id > 0;
    }

    public function getUserId(string $login): ?int
    {
        return User::getUserIDByEmail($login, true);
    }

    /**
     * Increment the failed logins attempts for this user
     */
    private function incrementFailedLogins(int $usr_id): void
    {
        $stmt = 'UPDATE
                    `user`
                 SET
                    usr_failed_logins = usr_failed_logins + 1,
                    usr_last_failed_login = NOW()
                 WHERE
                    usr_id=?';
        DB_Helper::getInstance()->query($stmt, [$usr_id]);
    }

    /**
     * Reset the failed logins attempts for this user
     */
    private function resetFailedLogins(int $usr_id): void
    {
        $stmt = 'UPDATE
                    `user`
                 SET
                    usr_failed_logins = 0,
                    usr_last_login = NOW(),
                    usr_last_failed_login = NULL
                 WHERE
                    usr_id=?';
        DB_Helper::getInstance()->query($stmt, [$usr_id]);
    }

    /**
     * Returns the true if the account is currently locked because of Back-Off locking
     */
    private function isUserBackOffLocked(int $usr_id): bool
    {
        if (!$this->loginBackoffEnabled()) {
            return false;
        }

        $config = $this->getBackOffConfig();
        $stmt = 'SELECT
                    IF( usr_failed_logins >= ?, NOW() < DATE_ADD(usr_last_failed_login, INTERVAL ' . $config['minutes'] . ' MINUTE), 0)
                 FROM
                    `user`
                 WHERE
                    usr_id=?';
        $params = [$config['count'], $usr_id];
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, $params);
        } catch (DatabaseException $e) {
            return true;
        }

        return $res == 1;
    }

    public function canUserUpdateName(int $usr_id): bool
    {
        return true;
    }

    public function canUserUpdateEmail(int $usr_id): bool
    {
        return true;
    }

    public function canUserUpdatePassword(int $usr_id): bool
    {
        return true;
    }

    /**
     * Returns a URL to redirect the user to when they attempt to login or null if the native login pages
     * should be used.
     *
     * @return  string The login url or null
     */
    public function getExternalLoginURL(): ?string
    {
        return null;
    }

    /**
     * Called on every page load and can be used to process external authentication checks before the rest of the
     * authentication process happens.
     */
    public function checkAuthentication(): void
    {
    }

    /**
     * Called when a user logs out.
     */
    public function logout(): void
    {
    }

    /**
     * Returns true if the user should automatically be redirected to the external login URL, false otherwise
     *
     * @return  bool
     */
    public function autoRedirectToExternalLogin(): bool
    {
        return false;
    }

    private function getBackOffConfig(): array
    {
        $config = ServiceContainer::getConfig()['auth']['login_backoff'] ?? null;

        return [
            'count' => $config['count'] ?? null,
            'minutes' => $config['minutes'] ?? 15,
        ];
    }

    private function loginBackoffEnabled(): bool
    {
        return $this->getBackOffConfig()['count'] !== null;
    }
}
