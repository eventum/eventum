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

class CommitSeeder extends AbstractSeed
{
    public const ISSUE_1 = 1;
    public const ISSUE_2 = 2;
    public const STATUS_DISCOVERY = 1;

    public function run(): void
    {
        $commits = [
            self::ISSUE_1 => [
                'com_id' => 1,
                'com_scm_name' => 'cvs',
                'com_changeset' => 'z15ca8589fe52a9',
                'com_author_name' => 'Au Thor',
                'com_commit_date' => '2019-04-06 07:43:27',
                'com_message' => 'Mes-Sage',
                'files' => [
                    [
                        'cof_filename' => 'file',
                    ],
                ],
            ],
        ];

        $table = $this->table('commit');
        $isc_table = $this->table('issue_commit');
        $file_table = $this->table('commit_file');
        foreach ($commits as $issue_id => $row) {
            $files = $row['files'];
            unset($row['files']);
            $table->insert($row);

            $issue_commit = [
                'isc_iss_id' => $issue_id,
                'isc_com_id' => $row['com_id'],
            ];
            $isc_table->insert($issue_commit);

            foreach ($files as $file) {
                $file['cof_com_id'] = $row['com_id'];
                $file_table->insert($file);
            }
        }

        $table->saveData();
        $isc_table->saveData();
        $file_table->saveData();
    }
}
