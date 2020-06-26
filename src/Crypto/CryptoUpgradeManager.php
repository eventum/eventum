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

namespace Eventum\Crypto;

use Eventum\Config\Config;
use Eventum\Event\ConfigUpdateEvent;
use Eventum\Event\SystemEvents;
use Eventum\EventDispatcher\EventManager;
use Eventum\ServiceContainer;
use Setup;

class CryptoUpgradeManager
{
    /** @var Config */
    private $config;

    public function __construct()
    {
        $this->config = ServiceContainer::getConfig();
    }

    /**
     * Perform few checks that enable/disable can be performed
     *
     * @param bool $enable TRUE if action is to enable, FALSE if action is to disable
     * @throws CryptoException
     */
    private function canPerform(bool $enable): void
    {
        $enabled = CryptoManager::encryptionEnabled();
        if (($enabled && $enable) || (!$enabled && !$enable)) {
            $state = $enabled ? 'enabled' : 'disabled';
            throw new CryptoException("Can not perform, already $state");
        }

        CryptoManager::canEncrypt();

        // test that config can be saved before doing anything
        $res = Setup::save();
        if ($res !== 1) {
            throw new CryptoException('Can not save config');
        }

        // test that key file can be updated
        $km = new CryptoKeyManager();
        $km->canUpdate();
    }

    /**
     * Enable encryption
     *
     * @throws CryptoException if that can not be performed
     */
    public function enable(): void
    {
        $this->canPerform(true);

        $this->config['encryption'] = 'enabled';
        if (!CryptoManager::encryptionEnabled()) {
            throw new CryptoException('bug');
        }

        $event = new ConfigUpdateEvent($this->config);
        EventManager::dispatch(SystemEvents::CONFIG_CRYPTO_UPGRADE, $event);

        Setup::save();
    }

    /**
     * Disable encryption
     */
    public function disable(): void
    {
        $this->canPerform(false);

        $event = new ConfigUpdateEvent($this->config);
        EventManager::dispatch(SystemEvents::CONFIG_CRYPTO_DOWNGRADE, $event);

        $this->config['encryption'] = 'disabled';
        if (CryptoManager::encryptionEnabled()) {
            throw new CryptoException('bug');
        }

        Setup::save();
    }

    /**
     * Generate new encryption key and re-encrypt data
     */
    public function regenerateKey(): void
    {
        if (!CryptoManager::encryptionEnabled()) {
            throw new CryptoException('Encryption not enabled');
        }

        $this->disable();
        $km = new CryptoKeyManager();
        $km->generateKey();
        $this->enable();
    }
}
