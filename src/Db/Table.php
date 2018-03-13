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

namespace Eventum\Db;

class Table
{
    /**
     * Return list of tables that Eventum uses.
     *
     * This method should be updated every time when new table is added to system
     *
     * @return array
     */
    public static function getTableList()
    {
        // keep this list sorted for better git diffs
        return [
            'api_token',
            'attachment_chunk',
            'attachment_path',
            'columns_to_display',
            'commit',
            'commit_file',
            'custom_field',
            'custom_field_option',
            'custom_filter',
            'customer_account_manager',
            'customer_note',
            'email_account',
            'email_draft',
            'email_draft_recipient',
            'email_response',
            'faq',
            'faq_support_level',
            'group',
            'history_type',
            'irc_notice',
            'issue',
            'issue_access_list',
            'issue_access_log',
            'issue_association',
            'issue_attachment',
            'issue_attachment_file',
            'issue_attachment_file_path',
            'issue_checkin',
            'issue_commit',
            'issue_custom_field',
            'issue_history',
            'issue_partner',
            'issue_product_version',
            'issue_quarantine',
            'issue_user',
            'issue_user_replier',
            'link_filter',
            'mail_queue',
            'mail_queue_log',
            'news',
            'note',
            'partner_project',
            'phinxlog',
            'phone_support',
            'product',
            'project',
            'project_category',
            'project_custom_field',
            'project_email_response',
            'project_field_display',
            'project_group',
            'project_link_filter',
            'project_news',
            'project_phone_category',
            'project_priority',
            'project_release',
            'project_round_robin',
            'project_severity',
            'project_status',
            'project_status_date',
            'project_user',
            'reminder_action',
            'reminder_action_list',
            'reminder_action_type',
            'reminder_field',
            'reminder_history',
            'reminder_level',
            'reminder_level_condition',
            'reminder_operator',
            'reminder_priority',
            'reminder_product',
            'reminder_requirement',
            'reminder_severity',
            'reminder_triggered_action',
            'resolution',
            'round_robin_user',
            'search_profile',
            'status',
            'subscription',
            'subscription_type',
            'support_email',
            'support_email_body',
            'time_tracking',
            'time_tracking_category',
            'user',
            'user_alias',
            'user_group',
            'user_preference',
            'user_project_preference',
        ];
    }
}
