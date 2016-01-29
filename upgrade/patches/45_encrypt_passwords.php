<?php

use Eventum\Crypto\CryptoManager;
use Eventum\Crypto\EncryptedValue;

// rewrite known password fields in config
$config = Setup::get();
CryptoManager::upgradeConfig($config);
Setup::save();

// increase storage for password field
$db->query("alter table {{%email_account}} modify ema_password varchar(255) NOT NULL DEFAULT ''");

// encrypt email account passwords
$accounts = Email_Account::getList();
foreach ($accounts as $account) {
    $account = Email_Account::getDetails($account['ema_id']);
    /** @var EncryptedValue $password */
    $password = $account['ema_password'];
    // the raw value contains the original plaintext
    Email_Account::updatePassword($account['ema_id'], $password->getEncrypted());
}