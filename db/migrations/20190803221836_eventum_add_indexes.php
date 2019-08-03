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

class EventumAddIndexes extends AbstractMigration
{
    public function change(): void
    {
        $this->table('support_email')
            ->addIndex(['sup_message_id'], ['unique' => false])
            ->update();
    }
}
