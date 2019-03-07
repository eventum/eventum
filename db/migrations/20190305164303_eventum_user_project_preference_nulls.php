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

class EventumUserProjectPreferenceNulls extends AbstractMigration
{
    public function up(): void
    {
        $this->table('user_project_preference')
            ->changeColumn('upp_receive_assigned_email', 'boolean', ['default' => true])
            ->changeColumn('upp_receive_new_issue_email', 'boolean', ['default' => false])
            ->changeColumn('upp_receive_copy_of_own_action', 'boolean', ['default' => false])
            ->update();
    }

    public function down(): void
    {
    }
}
