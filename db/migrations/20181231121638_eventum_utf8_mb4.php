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

use Eventum\Db\AbstractMigration;
use Eventum\ServiceContainer;

/**
 * Upgrade MySQL charset to utf8mb4
 */
class EventumUtf8Mb4 extends AbstractMigration
{
    private const MIN_VERSION = '5.5.3';
    private const CHARSET = 'utf8mb4';
    private const COLLATION = 'utf8mb4_unicode_ci';

    public function up(): void
    {
        $version = $this->getMySqlVersion();

        if (!version_compare($version, self::MIN_VERSION, '>=')) {
            throw new RuntimeException('Requires MySQL Version >=' . self::MIN_VERSION);
        }

        $config = ServiceContainer::getConfig();
        $config['database']['charset_rollback'] = $config['database']['charset'];
        $config['database']['charset'] = self::CHARSET;
        $config['database']['collation'] = self::COLLATION;

        Setup::save();
    }

    public function down(): void
    {
        $config = ServiceContainer::getConfig();
        if (!$config['database']['charset_rollback']) {
            return;
        }

        $config['database']['charset'] = $config['database']['charset_rollback'];
        unset($config['database']['charset_rollback'], $config['database']['collation']);

        Setup::save();
    }

    private function getMySqlVersion(): string
    {
        return $this->queryOne('SELECT VERSION()');
    }
}
