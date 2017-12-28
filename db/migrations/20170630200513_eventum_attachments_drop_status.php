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

class EventumAttachmentsDropStatus extends AbstractMigration
{
    public function up()
    {
        $this->table('issue_attachment')
            ->removeColumn('iat_status')
            ->update();
    }

    /**
     * NOTE: the data for this column is gone
     * the default is 'internal' (different than in original db),
     * however this method should be used in development only.
     */
    public function down()
    {
        $this->table('issue_attachment')
            ->addColumn('iat_status', 'enum', ['default' => 'internal', 'values' => [0 => 'internal', 1 => 'public']])
            ->update();
    }
}
