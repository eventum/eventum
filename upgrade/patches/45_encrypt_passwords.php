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

use Eventum\Crypto\CryptoException;
use Eventum\Crypto\CryptoUpgradeManager;
use Eventum\Db\Adapter\AdapterInterface;

/** @var Closure $log */
/** @var AdapterInterface $db */

// increase storage for password field
$db->query("ALTER TABLE {{%email_account}} MODIFY ema_password VARCHAR(255) NOT NULL DEFAULT ''");

/*
$cm = new CryptoUpgradeManager();
try {
    // try to enable, but do not fail if can't
    $cm->enable();
} catch (CryptoException $e) {
    $log("Can't enable encryption: {$e->getMessage()}");
}
*/