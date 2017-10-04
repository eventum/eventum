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

class EventumAttachments extends AbstractMigration
{
    public function change()
    {
        $this->table('issue_attachment_file')
            ->addColumn('iaf_flysystem_path', 'string', ['length' => 255, 'null' => true])
            ->update();
        $this->table('issue_attachment')
            ->addColumn('iat_min_role', 'integer', ['after' => 'iat_usr_id', 'length' => '1', 'signed' => false, 'null' => false, 'default' => 1])
            ->update();
    }
}
