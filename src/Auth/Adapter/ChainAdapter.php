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

class ChainAdapter implements AdapterInterface
{
    public const displayName = 'Chained authentication adapter';

    /** @var AdapterInterface[] */
    private $adapters = [];

    /**
     * @param AdapterInterface[] $adapters
     */
    public function __construct(array $adapters = [])
    {
        foreach ($adapters as $adapterName) {
            $this->adapters[] = Factory::create(['adapter' => $adapterName]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function verifyPassword(string $login, string $password): bool
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter->verifyPassword($login, $password)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function updatePassword(int $usr_id, string $password): bool
    {
        $result = false;
        foreach ($this->adapters as $adapter) {
            if ($adapter->updatePassword($usr_id, $password)) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function userExists(string $login): bool
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter->userExists($login)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserId(string $login): ?int
    {
        foreach ($this->adapters as $adapter) {
            $usr_id = $adapter->getUserId($login);
            if ($usr_id !== null) {
                return $usr_id;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function canUserUpdateName(int $usr_id): bool
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter->canUserUpdateName($usr_id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function canUserUpdateEmail(int $usr_id): bool
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter->canUserUpdateEmail($usr_id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function canUserUpdatePassword(int $usr_id): bool
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter->canUserUpdatePassword($usr_id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getExternalLoginURL(): ?string
    {
        foreach ($this->adapters as $adapter) {
            $url = $adapter->getExternalLoginURL();
            if ($url) {
                return $url;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function autoRedirectToExternalLogin(): bool
    {
        foreach ($this->adapters as $adapter) {
            $redirect = $adapter->autoRedirectToExternalLogin();
            if ($redirect !== false) {
                return $redirect;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function checkAuthentication(): void
    {
        foreach ($this->adapters as $adapter) {
            $adapter->checkAuthentication();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function logout(): void
    {
        foreach ($this->adapters as $adapter) {
            $adapter->logout();
        }
    }
}
