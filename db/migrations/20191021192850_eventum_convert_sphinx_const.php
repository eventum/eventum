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

use Eventum\Config\Config;

use Eventum\Db\AbstractMigration;
use Eventum\ServiceContainer;

class EventumConvertSphinxConst extends AbstractMigration
{
    public function up(): void
    {
        $config = ServiceContainer::getConfig();

        $this->convertConstants($config, [
            'SPHINX_LOG_PATH' => '/var/log/sphinx/',
            'SPHINX_RUN_PATH' => '/var/run/sphinx/',
            'SPHINX_DATA_PATH' => '/var/lib/sphinx/eventum/',
            'SPHINX_SEARCHD_HOST' => 'localhost',
            'SPHINX_SEARCHD_PORT' => 3312,
        ]);

        $config['sphinx_searchd_port'] = (int)$config['sphinx_searchd_port'];

        Setup::save();
    }

    public function down(): void
    {
    }

    private function convertConstants(Config $setup, $constants): void
    {
        foreach ($constants as $constName => $defaultValue) {
            $value = defined($constName) ? constant($constName) : $defaultValue;
            $key = strtolower($constName);

            // avoid overwriting from previous migrate or value set by setup
            if ($setup[$key] === null) {
                $setup[$key] = $value;
            }

            // fixup: remove the trailing slash
            $setup[$key] = rtrim($setup[$key], '/');
        }
    }
}
