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

use Phinx\Seed\AbstractSeed;

class NoteSeeder extends AbstractSeed
{
    public const NOTE_1 = 1;

    public function run(): void
    {
        $issues = [
            self::NOTE_1 => [
                'not_iss_id' => IssueSeeder::ISSUE_2,
                'not_created_date' => '2019-03-28 10:09:16',
                'not_usr_id' => UserSeeder::ACTIVE_ACCOUNT,
                'not_title' => 'a note',
                'not_note' => 'note',
                'not_full_message' => null,
                'not_parent_id' => null,
                'not_unknown_user' => null,
                'not_has_attachment' => 0,
                'not_message_id' => '<eventum.md5.5i9ws1rjd.2lai3m7a6ckkg@eventum.127.0.0.1.xip.io:8080>',
                'not_removed' => 0,
                'not_is_blocked' => 0,
            ],
        ];

        $table = $this->table('note');
        foreach ($issues as $not_id => $row) {
            $table->insert($row);
        }

        $table->saveData();
    }
}
