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

use Throwable;

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
     * @param string $cipherText
     */
    public function __construct(?string $cipherText = null)
    {
        $this->ciphertext = $cipherText;
    }

    /**
     * Set plain text value.
     * The encrypted value is stored in object property.
     *
     * @param string $plainText
     * @throws CryptoException
     * @return EncryptedValue
     */
    public function setValue(string $plainText): self
    {
        $this->ciphertext = CryptoManager::encrypt($plainText);

        return $this;
    }

    /**
     * Return plain text value
     *
     * @throws CryptoException
     * @return string
     */
    public function getValue(): string
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
    public function getEncrypted(): string
    {
        if ($this->ciphertext === null) {
            throw new CryptoException('Value not initialized yet');
        }

        return $this->ciphertext;
    }

    /**
     * @throws Throwable
     * @return string
     */
    public function __toString(): string
    {
        try {
            $value = $this->getValue();
        } catch (Throwable $e) {
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
    public static function __set_state(array $data): self
    {
        return new self($data['ciphertext']);
    }
}
