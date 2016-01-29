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
     * Construct object using encrypted data.
     *
     * @param string $ciphertext
     */
    final public function __construct($ciphertext = null)
    {
        $this->ciphertext = $ciphertext;
    }

    /**
     * Set plain text value.
     * The encrypted value is stored in object property.
     *
     * @param string $plaintext
     */
    public final function setValue($plaintext)
    {
        $this->ciphertext = CryptoManager::encrypt($plaintext);
    }

    /**
     * Return plain text value
     *
     * @return string
     */
    public final function getValue()
    {
        if ($this->ciphertext === null) {
            throw new InvalidArgumentException('Value not initialized yet');
        }
        return CryptoManager::decrypt($this->ciphertext);
    }

    /**
     * Get encrypted value, for storing it to Database or Config
     *
     * @return string
     */
    public final function getEncrypted()
    {
        if ($this->ciphertext === null) {
            throw new InvalidArgumentException('Value not initialized yet');
        }
        return $this->ciphertext;
    }

    public final function __toString()
    {
        return $this->getValue();
    }
}
