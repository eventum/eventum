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
        Setup::save(array('encryption' => 'enabled'));
        if (!CryptoManager::encryptionEnabled()) {
            throw new CryptoException('bug');
        }

        // upgrade config
        $config = Setup::get();
        self::upgradeConfig($config);
        Setup::save();
        self::upgradeEmailAccounts();
    }

    /**
     * Disable encryption
     */
    public function disable()
    {
        Setup::save(array('encryption' => 'disabled'));
        if (CryptoManager::encryptionEnabled()) {
            throw new CryptoException('bug');
        }
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
    public function upgradeConfig(Config $config)
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

    public function upgradeEmailAccounts()
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
