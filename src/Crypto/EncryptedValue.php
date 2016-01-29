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

use InvalidArgumentException;

/**
 * Class Encrypted Value
 *
 * Provides object which behaves as regular string providing transparent decryption of the value
 *
 * @package Eventum
 */
class EncryptedValue
{
    /** @var string Encrypted value */
    private $ciphertext;

    /**
     * Construct object using encrypted data
     *
     * @param string $encrypted
     */
    final public function __construct($encrypted = null)
    {
        $this->ciphertext = $encrypted;
    }

    /**
     * @param string $plaintext
     */
    public final function setValue($plaintext)
    {
        $this->ciphertext = CryptoManager::encrypt($plaintext);
    }

    public final function getValue()
    {
        if (!$this->ciphertext) {
            throw new InvalidArgumentException('Value not initialized yet');
        }
        return CryptoManager::decrypt($this->ciphertext);
    }

    public final function __toString()
    {
        return $this->getValue();
    }
}
