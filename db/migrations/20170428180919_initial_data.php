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
    const PROJECT_ID = 1;

    public function change()
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
     * @link https://github.com/eventum/eventum/blob/v3.1.10/upgrade/schema.sql#L942-L957
     */
    private function columns_to_display()
    {
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
     * @link https://github.com/eventum/eventum/blob/v3.1.10/upgrade/schema.sql#L73-L132
     */
    private function history_type()
    {
        $history_types = [
            'attachment_removed' => [1, 0],
            'attachment_added' => [2, 0],
            'custom_field_updated' => [3, 0],
            'draft_added' => [4, 4],
            'draft_updated' => [5, 4],
            'status_changed' => [9, 0],
            'remote_status_change' => [10, 0],
            'remote_assigned' => [11, 0],
            'remote_replier_added' => [12, 0],
            'details_updated' => [13, 0],
            'customer_details_updated' => [14, 0],
            'issue_opened' => [15, 0],
            'issue_auto_assigned' => [16, 0],
            'rr_issue_assigned' => [17, 0],
            'duplicate_update' => [18, 0],
            'duplicate_removed' => [19, 0],
            'duplicate_added' => [20, 0],
            'issue_opened_anon' => [21, 0],
            'remote_issue_created' => [22, 0],
            'issue_closed' => [23, 0],
            'issue_updated' => [24, 0],
            'user_associated' => [25, 0],
            'user_all_unassociated' => [26, 0],
            'replier_added' => [27, 0],
            'remote_note_added' => [28, 0],
            'note_added' => [29, 4],
            'note_removed' => [30, 4],
            'note_converted_draft' => [31, 4],
            'note_converted_email' => [32, 4],
            'notification_removed' => [33, 0],
            'notification_added' => [34, 0],
            'notification_updated' => [35, 0],
            'phone_entry_added' => [36, 4],
            'phone_entry_removed' => [37, 4],
            'scm_checkin_removed' => [38, 0],
            'email_associated' => [39, 0],
            'email_disassociated' => [40, 0],
            'email_sent' => [41, 0],
            'time_added' => [42, 4],
            'time_removed' => [43, 4],
            'remote_time_added' => [44, 4],
            'email_blocked' => [45, 4],
            'email_routed' => [46, 0],
            'note_routed' => [47, 4],
            'replier_removed' => [48, 0],
            'replier_other_added' => [49, 0],
            'issue_associated' => [50, 0],
            'issue_all_unassociated' => [51, 0],
            'user_unassociated' => [52, 0],
            'issue_unassociated' => [53, 0],
            'group_changed' => [54, 4],
            'status_auto_changed' => [55, 4],
            'incident_redeemed' => [56, 4],
            'incident_unredeemed' => [57, 4],
            'scm_checkin_associated' => [58, 0],
            'issue_bulk_updated' => [59, 0],
            'draft_routed' => [60, 4],
            'version_details_updated' => [61, 4],
            'partner_added' => [62, 4],
            'partner_removed' => [63, 4],
            'issue_cloned_from' => [64, 4],
            'issue_cloned_to' => [65, 4],
            'access_level_changed' => [66, 4],
            'access_list_added' => [67, 4],
            'access_list_removed' => [68, 4],
            'time_update' => [69, 4],
        ];

        $table = $this->table(__FUNCTION__);
        foreach ($history_types as $htt_name => $values) {
            list($htt_id, $htt_role) = $values;
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
     * @link https://github.com/eventum/eventum/blob/v3.1.10/upgrade/schema.sql#L322
     */
    private function project()
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
     * @link https://github.com/eventum/eventum/blob/v3.1.10/upgrade/schema.sql#L339-L341
     */
    private function project_category()
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

    private function project_field_display()
    {
    }

    private function project_phone_category()
    {
    }

    private function project_priority()
    {
    }

    private function project_release()
    {
    }

    private function project_severity()
    {
    }

    private function project_status()
    {
    }

    private function project_user()
    {
    }

    private function reminder_action_type()
    {
    }

    private function reminder_field()
    {
    }

    private function reminder_operator()
    {
    }

    private function resolution()
    {
    }

    private function status()
    {
    }

    private function time_tracking_category()
    {
    }

    private function user()
    {
    }

    /**
     * Return current date/time in MySQL ISO8601 compatible format.
     * the same format MySQL CURRENT_TIMESTAMP() uses.
     *
     * @return string
     */
    private function currentDateTime($format = 'Y-m-d H:i:s')
    {
        return (new DateTime())->format($format);
    }
}
