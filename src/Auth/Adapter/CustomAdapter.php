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

/**
 * This sample auth backend integrates with a custom server if rewritten so
 */
class CustomAdapter implements AdapterInterface
{
    public const displayName = 'Custom authentication adapter';

    /**
     * {@inheritdoc}
     */
    public function verifyPassword(string $login, string $password): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function updatePassword(int $usr_id, string $password): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function userExists(string $login): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserId(string $login): ?int
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function canUserUpdateName(int $usr_id): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function canUserUpdateEmail(int $usr_id): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function canUserUpdatePassword(int $usr_id): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getExternalLoginURL(): ?string
    {
        return 'https://example.org/custom/auth';
    }

    /**
     * {@inheritdoc}
     */
    public function autoRedirectToExternalLogin(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function checkAuthentication(): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function logout(): void
    {
    }
}
