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

namespace Eventum\Command;

use Eventum\Db\Migrate;
use Exception;

class UpgradeCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function execute()
    {
        global $argv;

        // see if certain patch is needed to be run
        $patch = isset($argv[1]) ? (int)$argv[1] : null;

        try {
            $dbmigrate = new Migrate(INSTALL_PATH . '/upgrade');
            $dbmigrate->patch_database($patch);
        } catch (Exception $e) {
            echo $e->getMessage(), "\n";
            exit(1);
        }
    }
}
