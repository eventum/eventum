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
use Eventum\Crypto\CryptoUpgradeManager;
use Eventum\Db\AbstractMigration;

class EventumCrypto2Upgrade extends AbstractMigration
{
    public function up()
    {
        if (CryptoManager::encryptionEnabled()) {
            $cm = new CryptoUpgradeManager();
            $cm->regenerateKey();
        }
    }

    /**
     * We can not migrate back to old key format,
     * so just to keep installation usable, disable encryption.
     */
    public function down()
    {
        if (CryptoManager::encryptionEnabled()) {
            $cm = new CryptoUpgradeManager();
            $cm->disable();
        }
    }
}
