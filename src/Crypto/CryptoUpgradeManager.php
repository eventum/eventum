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
    /**
     * Enable encryption
     *
     * @throws CryptoException if that can not be performed
     */
    public function enable()
    {
        CryptoManager::canEncrypt();
        $config = Setup::get();
        $config['encryption'] = 'enabled';
        if (!CryptoManager::encryptionEnabled()) {
            throw new CryptoException('bug');
        }

        // upgrade config
        $this->upgradeConfig($config);
        $this->upgradeEmailAccounts();
        Setup::save();
    }

    /**
     * Disable encryption
     */
    public function disable()
    {
        $config = Setup::get();

        self::downgradeConfig($config);
        self::downgradeEmailAccounts();

        $config['encryption'] = 'disabled';
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
     *
     * @param Config $config
     */
    private function upgradeConfig(Config $config)
    {
        if (!$config['database']['password'] instanceof EncryptedValue) {
            $config['database']['password'] = new EncryptedValue(
                CryptoManager::encrypt($config['database']['password'])
            );
        }

        if (count($config['ldap']) && !$config['ldap']['bindpw'] instanceof EncryptedValue) {
            $config['ldap']['bindpw'] = new EncryptedValue(CryptoManager::encrypt($config['ldap']['bindpw']));
        }
    }

    /**
     * Downgrade config: remove all EncryptedValue elements
     *
     * @param Config $config
     */
    private function downgradeConfig(Config $config)
    {
        if ($config['database']['password'] instanceof EncryptedValue) {
            $value = (string)$config['database']['password'];
            $config['database']['password'] = $value;
        }

        if (count($config['ldap']) && $config['ldap']['bindpw'] instanceof EncryptedValue) {
            $value = (string)$config['ldap']['bindpw'];
            $config['ldap']['bindpw'] = $value;
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
        $config = Setup::get();
        $accounts = Email_Account::getList();

        // collect passwords when encryption enabled
        $config['encryption'] = 'enabled';
        $passwords = array();
        foreach ($accounts as $account) {
            $account = Email_Account::getDetails($account['ema_id']);
            /** @var EncryptedValue $password */
            $password = $account['ema_password'];
            $passwords[$account['ema_id']] = $password->getValue();
        }

        // save passwords when encryption disabled
        $config['encryption'] = 'disabled';
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
