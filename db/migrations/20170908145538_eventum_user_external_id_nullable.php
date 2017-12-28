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

class EventumUserExternalIdNullable extends AbstractMigration
{
    public function up()
    {
        $this->table('user')
            ->changeColumn(
                'usr_external_id', 'string',
                ['length' => 255, 'null' => true]
            )
            ->save();

        $this->query("UPDATE user SET usr_external_id=NULL WHERE usr_external_id=''");
    }

    public function down()
    {
        $this->table('user')
            ->changeColumn(
                'usr_external_id', 'string',
                ['length' => 100, 'null' => false]
            )
            ->save();
    }
}
