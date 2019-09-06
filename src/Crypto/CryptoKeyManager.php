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

use Defuse\Crypto\Key;
use Eventum\Opcache;
use Setup;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class CryptoKeyManager dealing with key loading and generating
 */
final class CryptoKeyManager
{
    /** @var string */
    private $keyfile;

    /** @var Key */
    private $key;

    public function __construct()
    {
        $this->keyfile = Setup::getConfigPath() . '/secret_key.php';
    }

    /**
     * @throws CryptoException
     */
    public function generateKey(): void
    {
        try {
            $this->key = Key::createNewRandomKey();
            $this->storePrivateKey();
        } catch (CryptoException $e) {
            throw new CryptoException('Cannot perform operation: ' . $e->getMessage());
        }
    }

    /**
     * Checks if key file can be updated
     * @throws CryptoException
     */
    public function canUpdate(): void
    {
        if (file_exists($this->keyfile) && !is_writable($this->keyfile)) {
            throw new CryptoException("Secret file '{$this->keyfile}' not writable");
        }
    }

    /**
     * Load or generate secret key used for crypt
     *
     * @throws CryptoException
     * @return Key
     */
    public function getKey(): Key
    {
        if (!$this->key) {
            $this->loadPrivateKey() ?: $this->generateKey();
        }

        if (!$this->key) {
            throw new CryptoException('Unable to setup key');
        }

        return $this->key;
    }

    /**
     * @throws CryptoException
     * @return bool|null
     */
    private function loadPrivateKey(): ?bool
    {
        if (!file_exists($this->keyfile) || !filesize($this->keyfile)) {
            return null;
        }
        if (!is_readable($this->keyfile)) {
            throw new CryptoException("Secret file '{$this->keyfile}' not readable");
        }

        // load first to see that it's php script
        // this would avoid printing secret key to output if in old or invalid format
        $rawKey = file_get_contents($this->keyfile);
        if (!$rawKey) {
            throw new CryptoException("Unable to read secret file '{$this->keyfile}");
        }

        // support legacy key format
        if (strpos($rawKey, '<?php') !== 0) {
            $this->key = $rawKey;

            return true;
        }

        // avoid opcode cache giving previous version. yet it still fails in some cases
        clearstatcache(true, $this->keyfile);

        $key = require $this->keyfile;
        if (!$key) {
            throw new CryptoException("Secret file corrupted: {$this->keyfile}");
        }

        if (strpos($rawKey, $key) === false) {
            // on macOS, "require" loads previous version, even if clearstatcache is invoked
            throw new CryptoException("Could not re-read secret key: {$this->keyfile}");
        }

        try {
            $this->key = Key::loadFromAsciiSafeString($key);
        } catch (CryptoException $e) {
            throw new CryptoException('Cannot perform operation: ' . $e->getMessage());
        }

        return true;
    }

    /**
     * @throws CryptoException
     */
    private function storePrivateKey(): void
    {
        $this->canUpdate();

        try {
            $fs = new Filesystem();
            $content = sprintf('<' . '?php return %s;', var_export($this->key->saveToAsciiSafeString(), 1));
            $fs->dumpFile($this->keyfile, $content);

            Opcache::invalidate($this->keyfile);
        } catch (IOException $e) {
            throw new CryptoException("Unable to store secret file '{$this->keyfile}': {$e->getMessage()}");
        }
    }
}
