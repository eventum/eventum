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

class IssueSeeder extends AbstractSeed
{
    public const ISSUE_1 = 1;

    public function run(): void
    {
        $issues = [
            self::ISSUE_1 => [
                'Issue Summary',
            ],
        ];

        $table = $this->table('issue');
        foreach ($issues as $issue_id => [$summary]) {
            $row = [
                'iss_id' => 1,
                'iss_prj_id' => ProjectSeeder::DEFAULT_PROJECT_ID,
                'iss_summary' => $summary,
            ];
            $table->insert($row);
        }

        $table->saveData();
    }
}
