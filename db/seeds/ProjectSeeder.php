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

class ProjectSeeder extends AbstractSeed
{
    public const DEFAULT_PROJECT_ID = 1;
    public const EXTRA_PROJECT_ID = 10;

    public function run(): void
    {
        $data = [
            [
                'prj_id' => self::EXTRA_PROJECT_ID,
                'prj_created_date' => $this->currentDateTime(),
                'prj_title' => 'Project Two',
                'prj_status' => 'active',
                'prj_lead_usr_id' => 2,
                'prj_initial_sta_id' => 1,
                'prj_remote_invocation' => '',
                'prj_anonymous_post' => '0',
                'prj_anonymous_post_options' => null,
                'prj_outgoing_sender_name' => 'Project Two',
                'prj_outgoing_sender_email' => 'second_project@example.com',
            ],
        ];

        $posts = $this->table('project');
        $posts->insert($data)->save();
    }

    /**
     * Return current date/time in MySQL ISO8601 compatible format.
     * the same format MySQL CURRENT_TIMESTAMP() uses.
     *
     * @param string $dateFormat
     * @return string
     */
    private function currentDateTime($dateFormat = 'Y-m-d H:i:s'): string
    {
        $dateTime = new DateTime();

        return $dateTime->format($dateFormat);
    }
}
