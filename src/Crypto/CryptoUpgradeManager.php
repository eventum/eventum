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

use Email_Account;
use Setup;
use Zend\Config\Config;

class CryptoUpgradeManager
{
    /** @var Config */
    private $config;

    public function __construct()
    {
        $this->config = Setup::get();
    }

    /**
     * Enable encryption
     *
     * @throws CryptoException if that can not be performed
     */
    public function enable()
    {
        CryptoManager::canEncrypt();

        $this->config['encryption'] = 'enabled';
        if (!CryptoManager::encryptionEnabled()) {
            throw new CryptoException('bug');
        }

        $this->upgradeConfig();
        $this->upgradeEmailAccounts();

        Setup::save();
    }

    /**
     * Disable encryption
     */
    public function disable()
    {
        $this->downgradeConfig();
        $this->downgradeEmailAccounts();

        $this->config['encryption'] = 'disabled';
        if (CryptoManager::encryptionEnabled()) {
            throw new CryptoException('bug');
        }

        Setup::save();
    }

    /**
     * Generate new encryption key and re-encrypt data
     */
    public function regenerateKey()
    {
        throw new CryptoException('Not yet');
    }

    /**
     * Upgrade config so that values contain EncryptedValue where some secrecy is wanted
     */
    private function upgradeConfig()
    {
        if (!$this->config['database']['password'] instanceof EncryptedValue) {
            $config['database']['password'] = new EncryptedValue(
                CryptoManager::encrypt($this->config['database']['password'])
            );
        }

        if (count($this->config['ldap']) && !$this->config['ldap']['bindpw'] instanceof EncryptedValue) {
            $this->config['ldap']['bindpw'] = new EncryptedValue(CryptoManager::encrypt($this->config['ldap']['bindpw']));
        }
    }

    /**
     * Downgrade config: remove all EncryptedValue elements
     */
    private function downgradeConfig()
    {
        if ($this->config['database']['password'] instanceof EncryptedValue) {
            $value = (string)$this->config['database']['password'];
            $this->config['database']['password'] = $value;
        }

        if (count($this->config['ldap']) && $this->config['ldap']['bindpw'] instanceof EncryptedValue) {
            $value = (string)$this->config['ldap']['bindpw'];
            $this->config['ldap']['bindpw'] = $value;
        }
    }

    private function upgradeEmailAccounts()
    {
        // encrypt email account passwords
        $accounts = Email_Account::getList();
        foreach ($accounts as $account) {
            $account = Email_Account::getDetails($account['ema_id']);
            /** @var EncryptedValue $password */
            $password = $account['ema_password'];
            // the raw value contains the original plaintext
            Email_Account::updatePassword($account['ema_id'], $password->getEncrypted());
        }
    }

    private function downgradeEmailAccounts()
    {
        $accounts = Email_Account::getList();

        // collect passwords when encryption enabled
        $passwords = array();
        $this->config['encryption'] = 'enabled';
        foreach ($accounts as $account) {
            $account = Email_Account::getDetails($account['ema_id']);
            /** @var EncryptedValue $password */
            $password = $account['ema_password'];
            $passwords[$account['ema_id']] = $password->getValue();
        }

        // save passwords when encryption disabled
        $this->config['encryption'] = 'disabled';
        foreach ($passwords as $ema_id => $password) {
            Email_Account::updatePassword($ema_id, $password);
        }
    }

    /**
     * Key rotation method -- decrypt with your old key then re-encrypt with your new key
     *
     * @param string $ciphertext
     * @param string $key the new key
     * @return string
     */
    public function rotate($ciphertext, $key)
    {
        if (!CryptoManager::encryptionEnabled()) {
            return $ciphertext;
        }

        return CryptoManager::encrypt(CryptoManager::decrypt($ciphertext), $key);
    }
}
