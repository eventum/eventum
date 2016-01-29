<?php

use Eventum\Crypto\EncryptedValue;
use Eventum\Crypto\CryptoManager;

$config = Setup::get();

// rewrite known password fields
if (!$config['database']['password'] instanceof EncryptedValue) {
    $config['database']['password'] = new EncryptedValue(CryptoManager::encrypt($config['database']['password']));
}

if (count($config['ldap']) && !$config['ldap']['bindpw'] instanceof EncryptedValue) {
    $config['ldap']['bindpw'] = new EncryptedValue(CryptoManager::encrypt($config['ldap']['bindpw']));
}

Setup::save();