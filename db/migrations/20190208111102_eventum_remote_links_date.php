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

class EventumRemoteLinksDate extends AbstractMigration
{
    public function change(): void
    {
        $this->table('remote_link')
            ->addColumn('rel_created_date', 'datetime', ['after' => 'rel_iss_id', 'null' => false])
            ->addColumn('rel_updated_date', 'timestamp', ['after' => 'rel_created_date', 'null' => false, 'default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->save();
    }
}
