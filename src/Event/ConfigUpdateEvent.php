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

namespace Eventum\Event;

use Eventum\Config\Config;
use Eventum\Crypto\CryptoException;
use Eventum\Crypto\CryptoManager;
use Eventum\Crypto\EncryptedValue;
use Symfony\Contracts\EventDispatcher\Event;

final class ConfigUpdateEvent extends Event
{
    /** @var Config */
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Return true if encryption is enabled
     *
     * @see CryptoManager::encryptionEnabled()
     */
    public function hasEncryption(): bool
    {
        return $this->config['encryption'] === 'enabled';
    }

    /**
     * @param string|null|EncryptedValue $value
     * @throws CryptoException
     */
    public function encrypt(&$value): void
    {
        // value not present or already encrypted
        if ($value === null || $value instanceof EncryptedValue) {
            return;
        }

        $encrypted = (new EncryptedValue())->setValue($value);
        $value = $encrypted;
    }

    /**
     * @param EncryptedValue|string $value
     * @throws CryptoException
     */
    public function decrypt(&$value): void
    {
        if ($value instanceof EncryptedValue) {
            $plaintext = $value->getValue();
            $value = $plaintext;
        }
    }
}
