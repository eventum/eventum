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

namespace Example\Auth;

use Eventum\Auth\Adapter\AdapterInterface;

class NullAuthAdapter implements AdapterInterface
{
    public function verifyPassword(string $login, string $password): bool
    {
        return false;
    }

    public function updatePassword(int $usr_id, string $password): bool
    {
        return false;
    }

    public function userExists(string $login): bool
    {
        return false;
    }

    public function getUserId(string $login): ?int
    {
        return null;
    }

    public function canUserUpdateName(int $usr_id): bool
    {
        return false;
    }

    public function canUserUpdateEmail(int $usr_id): bool
    {
        return false;
    }

    public function canUserUpdatePassword(int $usr_id): bool
    {
        return false;
    }

    public function getExternalLoginURL(): ?string
    {
        return null;
    }

    public function autoRedirectToExternalLogin(): bool
    {
        return false;
    }

    public function checkAuthentication(): void
    {
    }

    public function logout(): void
    {
    }
}
