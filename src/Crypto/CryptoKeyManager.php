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

use Crypto;
use RandomLib;

/**
 * Class CryptoKeyManager dealing with key loading and generating
 *
 * @package Eventum\Crypto
 */
final class CryptoKeyManager
{
    /** @var string */
    private $keyfile;

    private $key;

    public function __construct()
    {
        $this->keyfile = APP_CONFIG_PATH . '/secret_key.php';
    }

    public function regen()
    {
        $this->generateKey();
    }

    /**
     * Checks if key file can be updated
     */
    public function canUpdate()
    {
        if (file_exists($this->keyfile) && !is_writable($this->keyfile)) {
            throw new CryptoException("Secret file '{$this->keyfile}' not writable");
        }
    }

    /**
     * Load or generate secret key used for crypt
     *
     * @return string
     */
    public function getKey()
    {
        if (!$this->key) {
            $this->loadPrivateKey() ?: $this->generateKey();
        }

        if (!$this->key) {
            throw new CryptoException('Unable to setup key');
        }

        return $this->key;
    }

    private function generateKey()
    {
        // use RandomLib to get most compatible implementation
        // Crypto uses mcrypt *ONLY* without any fallback
        $factory = new RandomLib\Factory();
        $generator = $factory->getMediumStrengthGenerator();
        $this->key = $generator->generate(Crypto::KEY_BYTE_SIZE);
        $this->storePrivateKey();
    }

    private function loadPrivateKey()
    {
        if (!file_exists($this->keyfile) || !filesize($this->keyfile)) {
            return null;
        }
        if (!is_readable($this->keyfile)) {
            throw new CryptoException("Secret file '{$this->keyfile}' not readable");
        }
        $key = trim(file_get_contents($this->keyfile));
        if (!$key) {
            throw new CryptoException("Unable to read secret file '{$this->keyfile}");
        }
        $this->key = $key;

        return true;
    }

    private function storePrivateKey()
    {
        $this->canUpdate();
        $res = file_put_contents($this->keyfile, $this->key);
        if (!$res) {
            throw new CryptoException("Unable to store secret file '{$this->keyfile}'");
        }
    }
}
