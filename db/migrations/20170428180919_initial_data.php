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

use Phinx\Migration\AbstractMigration;

class InitialData extends AbstractMigration
{
    public function change()
    {
        $this->insertColumnsToDisplay();
    }

    /**
     * @link https://github.com/eventum/eventum/blob/v3.1.10/upgrade/schema.sql#L942-L957
     */
    private function insertColumnsToDisplay()
    {
        $table = $this->table('columns_to_display');

        $ctd_prj_id = 1;
        $ctd_page = 'list_issues';
        $rank = 1;
        $columns = [
            'pri_rank' => User::ROLE_VIEWER,
            'iss_id' => User::ROLE_VIEWER,
            'usr_full_name' => User::ROLE_VIEWER,
            'grp_name' => User::ROLE_VIEWER,
            'assigned' => User::ROLE_VIEWER,
            'time_spent' => User::ROLE_VIEWER,
            'prc_title' => User::ROLE_VIEWER,
            'pre_title' => User::ROLE_VIEWER,
            'iss_customer_id' => User::ROLE_VIEWER,
            'sta_rank' => User::ROLE_VIEWER,
            'sta_change_date' => User::ROLE_VIEWER,
            'last_action_date' => User::ROLE_VIEWER,
            'custom_fields' => User::ROLE_VIEWER,
            'iss_summary' => User::ROLE_VIEWER,

            // FIXME: what is role '9'?
            'iss_dev_time' => 9,
            'iss_percent_complete' => 9,
        ];

        foreach ($columns as $field => $min_role) {
            $row = [
                'ctd_prj_id' => $ctd_prj_id,
                'ctd_page' => $ctd_page,
                'ctd_field' => $field,
                'ctd_min_role' => $min_role,
                'ctd_rank' => $rank++,
            ];
            $table->insert($row);
        }

        $table->saveData();
    }
}
