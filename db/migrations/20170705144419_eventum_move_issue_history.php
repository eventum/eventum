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

class EventumMoveIssueHistory extends AbstractMigration
{
    /**
     * Create a history type for moving a project between issues
     */
    public function up(): void
    {
        $table = $this->table('history_type');
        $row = [
            'htt_name' => 'issue_moved',
            'htt_role' => 4,
        ];
        $table->insert($row)->saveData();
    }

    /**
     * Removes history type for moving project between issues
     */
    public function down(): void
    {
        $this->execute("DELETE FROM history_type WHERE htt_name='issue_moved'");
    }
}
