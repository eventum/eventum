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
    public function verifyPassword($login, $password)
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
    public function updatePassword($usr_id, $password)
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
    public function userExists($login)
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
    public function getUserIDByLogin($login)
    {
        foreach ($this->adapters as $adapter) {
            $usr_id = $adapter->getUserIDByLogin($login);
            if ($usr_id !== null) {
                return $usr_id;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function canUserUpdateName($usr_id)
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
    public function canUserUpdateEmail($usr_id)
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
    public function canUserUpdatePassword($usr_id)
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
    public function incrementFailedLogins($usr_id)
    {
        $result = false;
        foreach ($this->adapters as $adapter) {
            if ($adapter->incrementFailedLogins($usr_id)) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function resetFailedLogins($usr_id)
    {
        $result = false;
        foreach ($this->adapters as $adapter) {
            if ($adapter->resetFailedLogins($usr_id)) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function isUserBackOffLocked($usr_id)
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter->isUserBackOffLocked($usr_id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getExternalLoginURL()
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
    public function autoRedirectToExternalLogin()
    {
        foreach ($this->adapters as $adapter) {
            $redirect = $adapter->autoRedirectToExternalLogin();
            if ($redirect !== null) {
                return $redirect;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function checkAuthentication()
    {
        foreach ($this->adapters as $adapter) {
            $adapter->checkAuthentication();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function logout()
    {
        foreach ($this->adapters as $adapter) {
            $adapter->logout();
        }

        return null;
    }
}
