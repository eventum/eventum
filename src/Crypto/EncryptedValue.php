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

/**
 * Class Encrypted Value
 *
 * Provides object which behaves as regular string
 * providing transparent decryption of the value
 *
 * This class is final to prevent breaking security and overriding some methods
 */
final class EncryptedValue
{
    /** @var string Encrypted value */
    private $ciphertext;

    /**
     * Construct object using encrypted data.
     *
     * @param string $ciphertext
     */
    public function __construct($ciphertext = null)
    {
        $this->ciphertext = $ciphertext;
    }

    /**
     * Set plain text value.
     * The encrypted value is stored in object property.
     *
     * @param string $plaintext
     * @throws CryptoException
     */
    public function setValue($plaintext)
    {
        $this->ciphertext = CryptoManager::encrypt($plaintext);
    }

    /**
     * Return plain text value
     *
     * @throws CryptoException
     * @return string
     */
    public function getValue()
    {
        if ($this->ciphertext === null) {
            throw new CryptoException('Value not initialized yet');
        }

        return CryptoManager::decrypt($this->ciphertext);
    }

    /**
     * Get encrypted value, for storing it to Database or Config
     *
     * @throws CryptoException
     * @return string
     */
    public function getEncrypted()
    {
        if ($this->ciphertext === null) {
            throw new CryptoException('Value not initialized yet');
        }

        return $this->ciphertext;
    }

    public function __toString()
    {
        try {
            $value = $this->getValue();
        } catch (\Exception $e) {
            error_log($e->getMessage());
            throw $e;
        }

        return $value;
    }

    /**
     * Method invoked when loading dumped config
     *
     * @param array $data
     * @return EncryptedValue
     */
    public static function __set_state($data)
    {
        return new self($data['ciphertext']);
    }
}
