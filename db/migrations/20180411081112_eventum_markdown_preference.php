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

class EventumMarkdownPreference extends AbstractMigration
{
    public function change()
    {
        $setup = Setup::get();
        $default = (int) ($setup['markdown'] == 'enabled');

        $this->table('user_preference')
            ->addColumn('upr_markdown', 'boolean', ['default' => $default, 'null' => true])
            ->update();
    }
}
