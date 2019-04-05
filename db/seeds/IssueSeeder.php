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
    public const ISSUE_2 = 2;
    public const STATUS_DISCOVERY = 1;

    public function run(): void
    {
        $issues = [
            self::ISSUE_1 => [
                'Issue Summary',
                self::STATUS_DISCOVERY,
            ],
            self::ISSUE_2 => [
                'Issue Summary',
                self::STATUS_DISCOVERY,
            ],
            13 => [
                'Issue Summary',
                self::STATUS_DISCOVERY,
            ],
            14 => [
                'Issue Summary',
                self::STATUS_DISCOVERY,
            ],
            15 => [
                'Issue Summary',
                self::STATUS_DISCOVERY,
            ],
        ];

        $table = $this->table('issue');
        foreach ($issues as $issue_id => [$summary, $iss_sta_id]) {
            $row = [
                'iss_id' => $issue_id,
                'iss_prj_id' => ProjectSeeder::DEFAULT_PROJECT_ID,
                'iss_sta_id' => $iss_sta_id,
                'iss_summary' => "$summary #{$issue_id}",
            ];
            $table->insert($row);
        }

        $table->saveData();
    }
}
