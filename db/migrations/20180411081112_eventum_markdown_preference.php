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

class EventumMarkdownPreference extends AbstractMigration
{
    public function change(): void
    {
        $config = ServiceContainer::getConfig();
        $default = (int) ($config['markdown'] === 'enabled');

        $this->table('user_preference')
            ->addColumn('upr_markdown', 'boolean', ['default' => $default, 'null' => true])
            ->update();
    }
}
