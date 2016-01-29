<?php

use Eventum\Crypto\CryptoManager;

$config = Setup::get();

// rewrite known password fields
CryptoManager::upgradeConfig($config);

Setup::save();