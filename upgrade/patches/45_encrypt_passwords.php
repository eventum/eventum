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

use Eventum\Crypto\CryptoManager;

// rewrite known password fields in config
$config = Setup::get();
CryptoManager::upgradeConfig($config);
Setup::save();

// increase storage for password field
$db->query("alter table {{%email_account}} modify ema_password varchar(255) NOT NULL DEFAULT ''");

CryptoManager::upgradeEmailAccounts();
