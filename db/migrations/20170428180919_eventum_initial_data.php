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

use Eventum\Auth\PasswordHash;
use Eventum\Db\AbstractMigration;

class EventumInitialData extends AbstractMigration
{
    private const PROJECT_ID = 1;

    public function up(): void
    {
        $this->columns_to_display();
        $this->history_type();
        $this->project();
        $this->project_category();
        $this->project_field_display();
        $this->project_phone_category();
        $this->project_priority();
        $this->project_release();
        $this->project_severity();
        $this->project_status();
        $this->project_user();
        $this->reminder_action_type();
        $this->reminder_field();
        $this->reminder_operator();
        $this->resolution();
        $this->status();
        $this->time_tracking_category();
        $this->user();
    }

    /**
     * @see https://github.com/eventum/eventum/blob/v3.1.10/upgrade/schema.sql#L942-L957
     */
    private function columns_to_display(): void
    {
        $ctd_prj_id = self::PROJECT_ID;
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

            'iss_dev_time' => USER::ROLE_NEVER_DISPLAY,
            'iss_percent_complete' => USER::ROLE_NEVER_DISPLAY,
        ];

        $table = $this->table(__FUNCTION__);
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

    /**
     * @see https://github.com/eventum/eventum/blob/v3.1.10/upgrade/schema.sql#L73-L132
     */
    private function history_type(): void
    {
        $role_unset = 0;
        $role_user = User::ROLE_USER;

        $history_types = [
            'attachment_removed' => [1, $role_unset],
            'attachment_added' => [2, $role_unset],
            'custom_field_updated' => [3, $role_unset],
            'draft_added' => [4, $role_user],
            'draft_updated' => [5, $role_user],
            'status_changed' => [9, $role_unset],
            'remote_status_change' => [10, $role_unset],
            'remote_assigned' => [11, $role_unset],
            'remote_replier_added' => [12, $role_unset],
            'details_updated' => [13, $role_unset],
            'customer_details_updated' => [14, $role_unset],
            'issue_opened' => [15, $role_unset],
            'issue_auto_assigned' => [16, $role_unset],
            'rr_issue_assigned' => [17, $role_unset],
            'duplicate_update' => [18, $role_unset],
            'duplicate_removed' => [19, $role_unset],
            'duplicate_added' => [20, $role_unset],
            'issue_opened_anon' => [21, $role_unset],
            'remote_issue_created' => [22, $role_unset],
            'issue_closed' => [23, $role_unset],
            'issue_updated' => [24, $role_unset],
            'user_associated' => [25, $role_unset],
            'user_all_unassociated' => [26, $role_unset],
            'replier_added' => [27, $role_unset],
            'remote_note_added' => [28, $role_unset],
            'note_added' => [29, $role_user],
            'note_removed' => [30, $role_user],
            'note_converted_draft' => [31, $role_user],
            'note_converted_email' => [32, $role_user],
            'notification_removed' => [33, $role_unset],
            'notification_added' => [34, $role_unset],
            'notification_updated' => [35, $role_unset],
            'phone_entry_added' => [36, $role_user],
            'phone_entry_removed' => [37, $role_user],
            'scm_checkin_removed' => [38, $role_unset],
            'email_associated' => [39, $role_unset],
            'email_disassociated' => [40, $role_unset],
            'email_sent' => [41, $role_unset],
            'time_added' => [42, $role_user],
            'time_removed' => [43, $role_user],
            'remote_time_added' => [44, $role_user],
            'email_blocked' => [45, $role_user],
            'email_routed' => [46, $role_unset],
            'note_routed' => [47, $role_user],
            'replier_removed' => [48, $role_unset],
            'replier_other_added' => [49, $role_unset],
            'issue_associated' => [50, $role_unset],
            'issue_all_unassociated' => [51, $role_unset],
            'user_unassociated' => [52, $role_unset],
            'issue_unassociated' => [53, $role_unset],
            'group_changed' => [54, $role_user],
            'status_auto_changed' => [55, $role_user],
            'incident_redeemed' => [56, $role_user],
            'incident_unredeemed' => [57, $role_user],
            'scm_checkin_associated' => [58, $role_unset],
            'issue_bulk_updated' => [59, $role_unset],
            'draft_routed' => [60, $role_user],
            'version_details_updated' => [61, $role_user],
            'partner_added' => [62, $role_user],
            'partner_removed' => [63, $role_user],
            'issue_cloned_from' => [64, $role_user],
            'issue_cloned_to' => [65, $role_user],
            'access_level_changed' => [66, $role_user],
            'access_list_added' => [67, $role_user],
            'access_list_removed' => [68, $role_user],
            'time_update' => [69, $role_user],
        ];

        $table = $this->table(__FUNCTION__);
        foreach ($history_types as $htt_name => [$htt_id, $htt_role]) {
            $row = [
                'htt_id' => $htt_id,
                'htt_name' => $htt_name,
                'htt_role' => $htt_role,
            ];
            $table->insert($row);
        }

        $table->saveData();
    }

    /**
     * @see https://github.com/eventum/eventum/blob/v3.1.10/upgrade/schema.sql#L322
     */
    private function project(): void
    {
        $table = $this->table(__FUNCTION__);
        // TODO: use constants or values from config
        $row = [
            'prj_id' => self::PROJECT_ID,
            'prj_created_date' => $this->currentDateTime(),
            'prj_title' => 'Default Project',
            'prj_status' => 'active',
            'prj_lead_usr_id' => 2,
            'prj_initial_sta_id' => 1,
            'prj_remote_invocation' => '',
            'prj_anonymous_post' => '0',
            'prj_anonymous_post_options' => null,
            'prj_outgoing_sender_name' => 'Default Project',
            'prj_outgoing_sender_email' => 'default_project@example.com',
        ];
        $table->insert($row);
        $table->saveData();
    }

    /**
     * @see https://github.com/eventum/eventum/blob/v3.1.10/upgrade/schema.sql#L339-L341
     */
    private function project_category(): void
    {
        $categories = [
            'Bug',
            'Feature Request',
            'Technical Support',
        ];

        $table = $this->table(__FUNCTION__);
        foreach ($categories as $prc_title) {
            $row = [
                'prc_prj_id' => self::PROJECT_ID,
                'prc_title' => $prc_title,
            ];
            $table->insert($row);
        }

        $table->saveData();
    }

    /**
     * @see https://github.com/eventum/eventum/blob/v3.1.10/upgrade/patches/33_set_required_fields.php
     */
    private function project_field_display(): void
    {
        $fields = [
            'category' => 1,
            'priority' => 1,
            'severity' => 1,
            'assignment' => 0,
            'release' => 0,
            'estimated_dev_time' => 0,
            'expected_res_date' => 0,
            'group' => 0,
            'file' => 0,
            'product' => 0,
            'associated_issues' => 0,
            'access_level' => 0,
        ];

        $table = $this->table(__FUNCTION__);
        foreach ($fields as $pfd_field => $pfd_required) {
            $row = [
                'pfd_prj_id' => self::PROJECT_ID,
                'pfd_field' => $pfd_field,
                'pfd_min_role' => '0',
                'pfd_required' => $pfd_required,
            ];
            $table->insert($row);
        }

        $table->saveData();
    }

    /**
     * @see https://github.com/eventum/eventum/blob/v3.1.10/upgrade/schema.sql#L884-L887
     */
    private function project_phone_category(): void
    {
        $categories = [
            'Sales Issues',
            'Technical Issues',
            'Administrative Issues',
            'Other',
        ];
        $phc_id = 1;

        $table = $this->table(__FUNCTION__);
        foreach ($categories as $phc_title) {
            $row = [
                'phc_id' => $phc_id++,
                'phc_prj_id' => self::PROJECT_ID,
                'phc_title' => $phc_title,
            ];
            $table->insert($row);
        }

        $table->saveData();
    }

    /**
     * @see https://github.com/eventum/eventum/blob/v3.1.10/upgrade/schema.sql#L296-L300
     * @see https://github.com/eventum/eventum/blob/v3.1.10/upgrade/patches/63_project_priority_icon.sql
     */
    private function project_priority(): void
    {
        $priorities = [
            'Critical',
            'High',
            'Medium',
            'Low',
            'Not Prioritized',
        ];
        $pri_id = 1;
        $pri_rank = 1;

        $table = $this->table(__FUNCTION__);
        foreach ($priorities as $pri_title) {
            $row = [
                'pri_id' => $pri_id++,
                'pri_prj_id' => self::PROJECT_ID,
                'pri_title' => $pri_title,
                'pri_rank' => $pri_rank++,
                'pri_icon' => 0,
            ];
            $table->insert($row);
        }

        $table->saveData();
    }

    /**
     * @see https://github.com/eventum/eventum/blob/v3.1.10/upgrade/schema.sql#L352
     */
    private function project_release(): void
    {
        $table = $this->table(__FUNCTION__);
        $row = [
            'pre_id' => 1,
            'pre_prj_id' => self::PROJECT_ID,
            'pre_title' => 'Example Release',
            'pre_scheduled_date' => $this->currentDateTime('Y-m-d', 'P1M'),
            'pre_status' => 'available',
        ];
        $table->insert($row);

        $table->saveData();
    }

    /**
     * @see https://github.com/eventum/eventum/blob/v3.1.10/upgrade/patches/15_severity.sql
     */
    private function project_severity(): void
    {
        $severities = [
            'S1' => 'Total Production Outage',
            'S2' => 'Serious Production Failure',
            'S3' => 'Minor Failure',
            'S4' => 'General Requests',
        ];
        $sev_id = 1;
        $sev_rank = 0;

        $table = $this->table(__FUNCTION__);
        foreach ($severities as $sev_title => $sev_description) {
            $row = [
                'sev_id' => $sev_id++,
                'sev_prj_id' => self::PROJECT_ID,
                'sev_title' => $sev_title,
                'sev_description' => $sev_description,
                'sev_rank' => $sev_rank++,
            ];
            $table->insert($row);
        }

        $table->saveData();
    }

    /**
     * @see https://github.com/eventum/eventum/blob/v3.1.10/upgrade/schema.sql#L594-L599
     */
    private function project_status(): void
    {
        $prs_id = 1;

        $table = $this->table(__FUNCTION__);
        foreach (range(1, 6) as $prs_sta_id) {
            $row = [
                'prs_id' => $prs_id++,
                'prs_prj_id' => self::PROJECT_ID,
                'prs_sta_id' => $prs_sta_id,
            ];
            $table->insert($row);
        }

        $table->saveData();
    }

    /**
     * @see https://github.com/eventum/eventum/blob/v3.1.10/upgrade/schema.sql#L362
     */
    private function project_user(): void
    {
        $table = $this->table(__FUNCTION__);
        $row = [
            'pru_id' => 1,
            'pru_prj_id' => self::PROJECT_ID,
            'pru_usr_id' => 2,
            'pru_role' => USER::ROLE_ADMINISTRATOR,
        ];
        $table->insert($row);

        $table->saveData();
    }

    /**
     * @see https://github.com/eventum/eventum/blob/v3.1.10/upgrade/schema.sql#L687-L690
     */
    private function reminder_action_type(): void
    {
        $action_types = [
            'email_assignee' => 'Send Email Alert to Assignee',
            'sms_assignee' => 'Send SMS Alert to Assignee',
            'email_list' => 'Send Email Alert To...',
            'sms_list' => 'Send SMS Alert To...',
        ];
        $rmt_id = 1;

        $table = $this->table(__FUNCTION__);
        foreach ($action_types as $rmt_type => $rmt_title) {
            $row = [
                'rmt_id' => $rmt_id++,
                'rmt_type' => $rmt_type,
                'rmt_title' => $rmt_title,
            ];
            $table->insert($row);
        }

        $table->saveData();
    }

    /**
     * @see https://github.com/eventum/eventum/blob/v3.1.10/upgrade/schema.sql#L713-L720
     */
    private function reminder_field(): void
    {
        $reminder_fields = [
            'Status' => [
                'iss_sta_id',
                'iss_sta_id',
                0,
            ],
            'Last Response Date' => [
                'iss_last_response_date',
                '(UNIX_TIMESTAMP() - IFNULL(UNIX_TIMESTAMP(iss_last_response_date), 0))',
                1,
            ],
            'Last Customer Action Date' => [
                'iss_last_customer_action_date',
                '(UNIX_TIMESTAMP() - IFNULL(UNIX_TIMESTAMP(iss_last_customer_action_date), 0))',
                1,
            ],
            'Last Update Date' => [
                'iss_updated_date',
                '(UNIX_TIMESTAMP() - IFNULL(UNIX_TIMESTAMP(iss_updated_date), 0))',
                1,
            ],
            'Created Date' => [
                'iss_created_date',
                '(UNIX_TIMESTAMP() - IFNULL(UNIX_TIMESTAMP(iss_created_date), 0))',
                1,
            ],
            'First Response Date' => [
                'iss_first_response_date',
                '(UNIX_TIMESTAMP() - IFNULL(UNIX_TIMESTAMP(iss_first_response_date), 0))',
                1,
            ],
            'Closed Date' => [
                'iss_closed_date', '(UNIX_TIMESTAMP() - IFNULL(UNIX_TIMESTAMP(iss_closed_date), 0))',
                1,
            ],
            'Category' => [
                'iss_prc_id',
                'iss_prc_id',
                0,
            ],
            'Group' => [
                'iss_grp_id',
                'iss_grp_id',
                0,
            ],
            'Active Group' => [
                'iss_grp_id',
                '',
                0,
            ],
            'Expected Resolution Date' => [
                'iss_expected_resolution_date',
                '(UNIX_TIMESTAMP() - IFNULL(UNIX_TIMESTAMP(iss_expected_resolution_date), 0))',
                1,
            ],
        ];
        $rmt_id = 1;

        $table = $this->table(__FUNCTION__);
        foreach ($reminder_fields as $rmf_title => [$rmf_sql_field, $rmf_sql_representation, $rmf_allow_column_compare]) {
            $row = [
                'rmf_id' => $rmt_id++,
                'rmf_title' => $rmf_title,
                'rmf_sql_field' => $rmf_sql_field,
                'rmf_sql_representation' => $rmf_sql_representation,
                'rmf_allow_column_compare' => $rmf_allow_column_compare,
            ];
            $table->insert($row);
        }

        $table->saveData();
    }

    /**
     * @see https://github.com/eventum/eventum/blob/v3.1.10/upgrade/schema.sql#L729-L736
     */
    private function reminder_operator(): void
    {
        $reminder_operators = [
            'equal to' => '=',
            'not equal to' => '<>',
            'is' => 'IS',
            'is not' => 'IS NOT',
            'greater than' => '>',
            'less than' => '<',
            'greater or equal than' => '>=',
            'less or equal than' => '<=',
        ];
        $rmo_id = 1;

        $table = $this->table(__FUNCTION__);
        foreach ($reminder_operators as $rmo_title => $rmo_sql_representation) {
            $row = [
                'rmo_id' => $rmo_id++,
                'rmo_title' => $rmo_title,
                'rmo_sql_representation' => $rmo_sql_representation,
            ];
            $table->insert($row);
        }

        $table->saveData();
    }

    /**
     * @see https://github.com/eventum/eventum/blob/v3.1.10/upgrade/schema.sql#L371-L377
     * @see https://github.com/eventum/eventum/blob/v3.1.10/upgrade/patches/09_resolution_rank.sql
     */
    private function resolution(): void
    {
        $resolutions = [
            2 => 'fixed',
            4 => 'unable to reproduce',
            5 => 'not fixable',
            6 => 'duplicate',
            7 => 'not a bug',
            8 => 'suspended',
            9 => "won't fix",
        ];

        $table = $this->table(__FUNCTION__);
        foreach ($resolutions as $res_id => $res_title) {
            $row = [
                'res_id' => $res_id,
                'res_title' => $res_title,
                'res_created_date' => $this->currentDateTime(),
                'res_rank' => $res_id,
            ];
            $table->insert($row);
        }

        $table->saveData();
    }

    /**
     * @see https://github.com/eventum/eventum/blob/v3.1.10/upgrade/schema.sql#L580-L585
     */
    private function status(): void
    {
        $statuses = [
            1 => ['discovery', 'DSC', 1, '#CCFFFF', 0],
            2 => ['requirements', 'REQ', 2, '#99CC66', 0],
            3 => ['implementation', 'IMP', 3, '#6699CC', 0],
            4 => ['evaluation and testing', 'TST', 4, '#FFCC99', 0],
            5 => ['released', 'REL', 5, '#CCCCCC', 1],
            6 => ['killed', 'KIL', 6, '#FFFFFF', 1],
        ];

        $table = $this->table(__FUNCTION__);
        foreach ($statuses as $sta_id => [$sta_title, $sta_abbreviation, $sta_rank, $sta_color, $sta_is_closed]) {
            $row = [
                'sta_id' => $sta_id,
                'sta_title' => $sta_title,
                'sta_abbreviation' => $sta_abbreviation,
                'sta_rank' => $sta_rank,
                'sta_color' => $sta_color,
                'sta_is_closed' => $sta_is_closed,
            ];
            $table->insert($row);
        }

        $table->saveData();
    }

    /**
     * @see https://github.com/eventum/eventum/blob/v3.1.10/upgrade/schema.sql#L451-L460
     */
    private function time_tracking_category(): void
    {
        $titles = [
            'Development',
            'Design',
            'Planning',
            'Gathering Requirements',
            'Database Changes',
            'Tech-Support',
            'Release',
            'Telephone Discussion',
            'Email Discussion',
            'Note Discussion',
        ];
        $ttc_id = 1;

        $table = $this->table(__FUNCTION__);
        foreach ($titles as $ttc_title) {
            $row = [
                'ttc_id' => $ttc_id++,
                'ttc_prj_id' => self::PROJECT_ID,
                'ttc_title' => $ttc_title,
                'ttc_created_date' => $this->currentDateTime(),
            ];
            $table->insert($row);
        }

        $table->saveData();
    }

    /**
     * @see https://github.com/eventum/eventum/blob/v3.1.10/upgrade/schema.sql#L481-L482
     */
    private function user(): void
    {
        $titles = [
            1 => [
                'system',
                'system-account@example.com',
                '',
                'inactive',

            ],
            2 => [
                'Admin User',
                'admin@example.com',
                // TODO: issue for changing this: https://github.com/eventum/eventum/issues/138
                PasswordHash::hash('admin'),
                'active',
            ],

        ];

        $table = $this->table(__FUNCTION__);
        foreach ($titles as $usr_id => [$usr_full_name, $usr_email, $usr_password, $usr_status]) {
            $row = [
                'usr_id' => $usr_id,
                'usr_created_date' => $this->currentDateTime(),
                'usr_status' => $usr_status,
                'usr_password' => $usr_password,
                'usr_full_name' => $usr_full_name,
                'usr_email' => $usr_email,
                'usr_external_id' => '',
            ];
            $table->insert($row);
        }

        $table->saveData();
    }

    /**
     * Return current date/time in MySQL ISO8601 compatible format.
     * the same format MySQL CURRENT_TIMESTAMP() uses.
     *
     * @param string $dateFormat
     * @param string $dateAdd
     * @return string
     */
    private function currentDateTime($dateFormat = 'Y-m-d H:i:s', $dateAdd = null): string
    {
        $dateTime = new DateTime();
        if ($dateAdd) {
            $dateTime->add(new DateInterval($dateAdd));
        }

        return $dateTime->format($dateFormat);
    }
}
