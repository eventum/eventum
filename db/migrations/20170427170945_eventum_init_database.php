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

class EventumInitDatabase extends AbstractMigration
{
    public function change(): void
    {
        $this->table('api_token', ['id' => false, 'primary_key' => 'apt_id'])
            ->addColumn('apt_id', 'integer', ['length' => 10, 'signed' => false, 'identity' => true])
            ->addColumn('apt_usr_id', 'integer', ['length' => 10, 'signed' => false])
            ->addColumn('apt_created', 'datetime')
            ->addColumn('apt_status', 'string', ['length' => 10, 'default' => 'active'])
            ->addColumn('apt_token', 'string', ['length' => 32])
            ->addIndex(['apt_usr_id', 'apt_status'])
            ->addIndex(['apt_token'])
            ->create();

        $this->table('columns_to_display', ['id' => false, 'primary_key' => ['ctd_prj_id', 'ctd_page', 'ctd_field']])
            ->addColumn('ctd_prj_id', 'integer', ['signed' => false])
            ->addColumn('ctd_page', 'string', ['length' => 20])
            ->addColumn('ctd_field', 'string', ['length' => 30])
            ->addColumn('ctd_min_role', 'boolean', ['default' => 0])
            ->addColumn('ctd_rank', 'integer', ['length' => self::INT_TINY, 'default' => 0])
            ->addIndex(['ctd_prj_id', 'ctd_page'])
            ->create();

        $this->table('commit', ['id' => false, 'primary_key' => 'com_id'])
            ->addColumn('com_id', 'integer', ['length' => 10, 'signed' => false, 'identity' => true])
            ->addColumn('com_scm_name', 'string', ['default' => 'default'])
            ->addColumn('com_project_name', 'string', ['null' => true])
            ->addColumn('com_changeset', 'string', ['length' => 40])
            ->addColumn('com_branch', 'string', ['null' => true])
            ->addColumn('com_author_email', 'string', ['null' => true])
            ->addColumn('com_author_name', 'string', ['null' => true])
            ->addColumn('com_usr_id', 'integer', ['null' => true])
            ->addColumn('com_commit_date', 'datetime', ['default' => '0000-00-00 00:00:00'])
            ->addColumn('com_message', 'text', ['length' => self::INT_MEDIUM, 'null' => true])
            ->create();

        $this->table('commit_file', ['id' => false, 'primary_key' => 'cof_id'])
            ->addColumn('cof_id', 'integer', ['length' => 10, 'signed' => false, 'identity' => true])
            ->addColumn('cof_com_id', 'integer', ['length' => 10, 'signed' => false])
            ->addColumn('cof_filename', 'string', ['default' => ''])
            ->addColumn('cof_added', 'boolean', ['default' => 0])
            ->addColumn('cof_modified', 'boolean', ['default' => 0])
            ->addColumn('cof_removed', 'boolean', ['default' => 0])
            ->addColumn('cof_old_version', 'string', ['length' => 40, 'null' => true])
            ->addColumn('cof_new_version', 'string', ['length' => 40, 'null' => true])
            ->create();

        $this->table('custom_field', ['id' => false, 'primary_key' => 'fld_id'])
            ->addColumn('fld_id', 'integer', ['length' => 10, 'signed' => false, 'identity' => true])
            ->addColumn('fld_title', 'string', ['length' => 32, 'default' => ''])
            ->addColumn('fld_description', 'string', ['length' => 64, 'null' => true])
            ->addColumn('fld_type', 'string', ['length' => 8, 'default' => 'text'])
            ->addColumn('fld_report_form', 'integer', ['length' => 1, 'default' => 1])
            ->addColumn('fld_report_form_required', 'integer', ['length' => 1, 'default' => 0])
            ->addColumn('fld_anonymous_form', 'integer', ['length' => 1, 'default' => 1])
            ->addColumn('fld_anonymous_form_required', 'integer', ['length' => 1, 'default' => 0])
            ->addColumn('fld_close_form', 'boolean', ['default' => 0])
            ->addColumn('fld_close_form_required', 'boolean', ['default' => 0])
            ->addColumn('fld_edit_form_required', 'boolean', ['default' => 0])
            ->addColumn('fld_list_display', 'boolean', ['default' => 0])
            ->addColumn('fld_min_role', 'boolean', ['default' => 0])
            ->addColumn('fld_min_role_edit', 'boolean', ['default' => 0])
            ->addColumn('fld_rank', 'integer', ['length' => self::INT_SMALL, 'default' => 0])
            ->addColumn('fld_backend', 'string', ['length' => 100, 'null' => true])
            ->addColumn('fld_order_by', 'string', ['length' => 20, 'default' => 'cfo_id ASC'])
            ->create();

        $this->table('custom_field_option', ['id' => false, 'primary_key' => 'cfo_id'])
            ->addColumn('cfo_id', 'integer', ['length' => 10, 'signed' => false, 'identity' => true])
            ->addColumn('cfo_fld_id', 'integer', ['length' => 10, 'default' => 0, 'signed' => false])
            ->addColumn('cfo_rank', 'integer', ['length' => 10, 'default' => 0, 'signed' => false])
            ->addColumn('cfo_value', 'string', ['length' => 128, 'default' => ''])
            ->addIndex(['cfo_fld_id'], ['name' => 'icf_fld_id'])
            ->create();

        $this->table('custom_filter', ['id' => false, 'primary_key' => 'cst_id'])
            ->addColumn('cst_id', 'integer', ['length' => 10, 'signed' => false, 'identity' => true])
            ->addColumn('cst_usr_id', 'integer', ['length' => 10, 'default' => 0, 'signed' => false])
            ->addColumn('cst_prj_id', 'integer', ['length' => 10, 'default' => 0, 'signed' => false])
            ->addColumn('cst_title', 'string', ['length' => 64, 'default' => ''])
            ->addColumn('cst_iss_pri_id', 'integer', ['length' => 10, 'null' => true, 'signed' => false])
            ->addColumn('cst_iss_sev_id', 'integer', ['length' => 10, 'null' => true, 'signed' => false])
            ->addColumn('cst_keywords', 'string', ['length' => 64, 'null' => true])
            ->addColumn('cst_users', 'string', ['length' => 64, 'null' => true])
            ->addColumn('cst_reporter', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('cst_iss_prc_id', 'integer', ['length' => 10, 'null' => true, 'signed' => false])
            ->addColumn('cst_iss_sta_id', 'integer', ['length' => 10, 'null' => true, 'signed' => false])
            ->addColumn('cst_iss_pre_id', 'integer', ['length' => 10, 'null' => true, 'signed' => false])
            ->addColumn('cst_pro_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('cst_show_authorized', 'char', ['length' => 3, 'default' => '', 'null' => true])
            ->addColumn('cst_show_notification_list', 'char', ['length' => 3, 'default' => '', 'null' => true])
            ->addColumn('cst_created_date', 'date', ['null' => true])
            ->addColumn('cst_created_date_filter_type', 'string', ['length' => 7, 'null' => true])
            ->addColumn('cst_created_date_time_period', 'integer', ['length' => self::INT_SMALL, 'null' => true])
            ->addColumn('cst_created_date_end', 'date', ['null' => true])
            ->addColumn('cst_updated_date', 'date', ['null' => true])
            ->addColumn('cst_updated_date_filter_type', 'string', ['length' => 7, 'null' => true])
            ->addColumn('cst_updated_date_time_period', 'integer', ['length' => self::INT_SMALL, 'null' => true])
            ->addColumn('cst_updated_date_end', 'date', ['null' => true])
            ->addColumn('cst_last_response_date', 'date', ['null' => true])
            ->addColumn('cst_last_response_date_filter_type', 'string', ['length' => 7, 'null' => true])
            ->addColumn('cst_last_response_date_time_period', 'integer', ['length' => self::INT_SMALL, 'null' => true])
            ->addColumn('cst_last_response_date_end', 'date', ['null' => true])
            ->addColumn('cst_first_response_date', 'date', ['null' => true])
            ->addColumn('cst_first_response_date_filter_type', 'string', ['length' => 7, 'null' => true])
            ->addColumn('cst_first_response_date_time_period', 'integer', ['length' => self::INT_SMALL, 'null' => true])
            ->addColumn('cst_first_response_date_end', 'date', ['null' => true])
            ->addColumn('cst_closed_date', 'date', ['null' => true])
            ->addColumn('cst_closed_date_filter_type', 'string', ['length' => 7, 'null' => true])
            ->addColumn('cst_closed_date_time_period', 'integer', ['length' => self::INT_SMALL, 'null' => true])
            ->addColumn('cst_closed_date_end', 'date', ['null' => true])
            ->addColumn('cst_rows', 'char', ['length' => 3, 'null' => true])
            ->addColumn('cst_sort_by', 'string', ['length' => 32, 'null' => true])
            ->addColumn('cst_sort_order', 'string', ['length' => 4, 'null' => true])
            ->addColumn('cst_hide_closed', 'integer', ['length' => 1, 'null' => true])
            ->addColumn('cst_is_global', 'integer', ['length' => 1, 'default' => 0, 'null' => true])
            ->addColumn('cst_search_type', 'string', ['length' => 15, 'default' => 'customer'])
            ->addColumn('cst_custom_field', 'text', ['null' => true])
            ->addIndex(['cst_usr_id', 'cst_prj_id'])
            ->create();

        $this->table('customer_account_manager', ['id' => false, 'primary_key' => 'cam_id'])
            ->addColumn('cam_id', 'integer', ['length' => 11, 'signed' => false, 'identity' => true])
            ->addColumn('cam_prj_id', 'integer', ['signed' => false])
            ->addColumn('cam_customer_id', 'string', ['length' => 128])
            ->addColumn('cam_usr_id', 'integer', ['signed' => false])
            ->addColumn('cam_type', 'string', ['length' => 7])
            ->addIndex(['cam_prj_id', 'cam_customer_id', 'cam_usr_id'], ['name' => 'cam_manager', 'unique' => true])
            ->addIndex(['cam_customer_id'])
            ->create();

        $this->table('customer_note', ['id' => false, 'primary_key' => 'cno_id'])
            ->addColumn('cno_id', 'integer', ['length' => 11, 'signed' => false, 'identity' => true])
            ->addColumn('cno_prj_id', 'integer', ['signed' => false])
            ->addColumn('cno_customer_id', 'string', ['length' => 128])
            ->addColumn('cno_created_date', 'datetime')
            ->addColumn('cno_updated_date', 'datetime', ['null' => true])
            ->addColumn('cno_note', 'text', ['null' => true])
            ->addIndex(['cno_prj_id', 'cno_customer_id'], ['unique' => true])
            ->create();

        $this->table('email_account', ['id' => false, 'primary_key' => 'ema_id'])
            ->addColumn('ema_id', 'integer', ['length' => 10, 'signed' => false, 'identity' => true])
            ->addColumn('ema_prj_id', 'integer', ['length' => 10, 'default' => 0, 'signed' => false])
            ->addColumn('ema_type', 'string', ['length' => 32, 'default' => ''])
            ->addColumn('ema_folder', 'string', ['null' => true, 'encoding' => 'latin1'])
            ->addColumn('ema_hostname', 'string', ['default' => '', 'encoding' => 'latin1'])
            ->addColumn('ema_port', 'string', ['length' => 5, 'default' => ''])
            ->addColumn('ema_username', 'string', ['length' => 64, 'default' => ''])
            ->addColumn('ema_password', 'string', ['default' => ''])
            ->addColumn('ema_get_only_new', 'integer', ['length' => 1, 'default' => 0])
            ->addColumn('ema_leave_copy', 'integer', ['length' => 1, 'default' => 0])
            ->addColumn('ema_issue_auto_creation', 'string', ['length' => 8, 'default' => 'disabled'])
            ->addColumn('ema_issue_auto_creation_options', 'text', ['null' => true])
            ->addColumn('ema_use_routing', 'boolean', ['default' => 0, 'null' => true])
            ->addIndex(['ema_username', 'ema_hostname', 'ema_folder'], ['unique' => true])
            ->addIndex(['ema_prj_id'])
            ->create();

        $this->table('email_draft', ['id' => false, 'primary_key' => 'emd_id'])
            ->addColumn('emd_id', 'integer', ['length' => 11, 'signed' => false, 'identity' => true])
            ->addColumn('emd_usr_id', 'integer', ['signed' => false])
            ->addColumn('emd_iss_id', 'integer', ['signed' => false])
            ->addColumn('emd_sup_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('emd_status', 'enum', ['default' => 'pending', 'values' => [0 => 'pending', 1 => 'edited', 2 => 'sent']])
            ->addColumn('emd_updated_date', 'datetime')
            ->addColumn('emd_subject', 'string')
            ->addColumn('emd_body', 'text', ['length' => self::INT_REGULAR])
            ->addColumn('emd_unknown_user', 'string', ['null' => true])
            ->create();

        $this->table('email_draft_recipient', ['id' => false, 'primary_key' => 'edr_id'])
            ->addColumn('edr_id', 'integer', ['length' => 11, 'signed' => false, 'identity' => true])
            ->addColumn('edr_emd_id', 'integer', ['signed' => false])
            ->addColumn('edr_is_cc', 'boolean', ['default' => 0, 'signed' => false])
            ->addColumn('edr_email', 'string')
            ->create();

        $this->table('email_response', ['id' => false, 'primary_key' => 'ere_id'])
            ->addColumn('ere_id', 'integer', ['length' => 10, 'signed' => false, 'identity' => true])
            ->addColumn('ere_title', 'string', ['length' => 64])
            ->addColumn('ere_response_body', 'text')
            ->addIndex(['ere_title'], ['unique' => true])
            ->create();

        $this->table('faq', ['id' => false, 'primary_key' => 'faq_id'])
            ->addColumn('faq_id', 'integer', ['length' => 11, 'signed' => false, 'identity' => true])
            ->addColumn('faq_prj_id', 'integer', ['signed' => false])
            ->addColumn('faq_usr_id', 'integer', ['signed' => false])
            ->addColumn('faq_created_date', 'datetime')
            ->addColumn('faq_updated_date', 'datetime', ['null' => true])
            ->addColumn('faq_title', 'string', ['encoding' => 'utf8'])
            ->addColumn('faq_message', 'text', ['length' => self::INT_REGULAR])
            ->addColumn('faq_rank', 'integer', ['length' => self::INT_TINY, 'signed' => false])
            ->addIndex(['faq_title'], ['unique' => true])
            ->create();

        $this->table('faq_support_level', ['id' => false, 'primary_key' => ['fsl_faq_id', 'fsl_support_level_id']])
            ->addColumn('fsl_faq_id', 'integer', ['signed' => false])
            ->addColumn('fsl_support_level_id', 'string', ['length' => 50])
            ->create();

        $this->table('group', ['id' => false, 'primary_key' => 'grp_id'])
            ->addColumn('grp_id', 'integer', ['length' => 11, 'signed' => false, 'identity' => true])
            ->addColumn('grp_name', 'string', ['length' => 100])
            ->addColumn('grp_description', 'string', ['null' => true])
            ->addColumn('grp_manager_usr_id', 'integer', ['signed' => false])
            ->addIndex(['grp_name'], ['unique' => true])
            ->create();

        $this->table('history_type', ['id' => false, 'primary_key' => 'htt_id'])
            ->addColumn('htt_id', 'integer', ['length' => self::INT_TINY, 'signed' => false, 'identity' => true])
            ->addColumn('htt_name', 'string', ['length' => 25])
            ->addColumn('htt_role', 'boolean', ['default' => 0, 'null' => true])
            ->addIndex(['htt_name'], ['unique' => true])
            ->create();

        $this->table('irc_notice', ['id' => false, 'primary_key' => 'ino_id'])
            ->addColumn('ino_id', 'integer', ['length' => 11, 'signed' => false, 'identity' => true])
            ->addColumn('ino_prj_id', 'integer')
            ->addColumn('ino_iss_id', 'integer', ['signed' => false])
            ->addColumn('ino_created_date', 'datetime')
            ->addColumn('ino_message', 'string')
            ->addColumn('ino_status', 'string', ['length' => 8, 'default' => 'pending'])
            ->addColumn('ino_target_usr_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('ino_category', 'string', ['length' => 25, 'null' => true])
            ->create();

        $this->table('issue', ['id' => false, 'primary_key' => 'iss_id'])
            ->addColumn('iss_id', 'integer', ['length' => 11, 'signed' => false, 'identity' => true])
            ->addColumn('iss_customer_id', 'string', ['length' => 128, 'null' => true])
            ->addColumn('iss_customer_contact_id', 'string', ['length' => 128, 'null' => true])
            ->addColumn('iss_customer_contract_id', 'string', ['length' => 50, 'null' => true])
            ->addColumn('iss_usr_id', 'integer', ['length' => 10, 'default' => 0, 'signed' => false])
            ->addColumn('iss_grp_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('iss_prj_id', 'integer', ['default' => 0, 'signed' => false])
            ->addColumn('iss_prc_id', 'integer', ['default' => 0, 'signed' => false])
            ->addColumn('iss_pre_id', 'integer', ['length' => 10, 'signed' => false, 'default' => 0])
            ->addColumn('iss_pri_id', 'integer', ['length' => self::INT_SMALL, 'default' => 0, 'signed' => false])
            ->addColumn('iss_sev_id', 'integer', ['default' => 0, 'signed' => false])
            ->addColumn('iss_sta_id', 'boolean', ['default' => 0])
            ->addColumn('iss_res_id', 'integer', ['length' => 10, 'null' => true, 'signed' => false])
            ->addColumn('iss_duplicated_iss_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('iss_created_date', 'datetime', ['default' => '0000-00-00 00:00:00'])
            ->addColumn('iss_updated_date', 'datetime', ['null' => true])
            ->addColumn('iss_last_response_date', 'datetime', ['null' => true])
            ->addColumn('iss_first_response_date', 'datetime', ['null' => true])
            ->addColumn('iss_closed_date', 'datetime', ['null' => true])
            ->addColumn('iss_last_customer_action_date', 'datetime', ['null' => true])
            ->addColumn('iss_expected_resolution_date', 'date', ['null' => true])
            ->addColumn('iss_summary', 'string', ['length' => 128, 'default' => ''])
            ->addColumn('iss_description', 'text')
            ->addColumn('iss_dev_time', 'float', ['null' => true])
            ->addColumn('iss_developer_est_time', 'float', ['null' => true])
            ->addColumn('iss_contact_person_lname', 'string', ['length' => 64, 'null' => true])
            ->addColumn('iss_contact_person_fname', 'string', ['length' => 64, 'null' => true])
            ->addColumn('iss_contact_email', 'string', ['null' => true])
            ->addColumn('iss_contact_phone', 'string', ['length' => 32, 'null' => true])
            ->addColumn('iss_contact_timezone', 'string', ['length' => 64, 'null' => true])
            ->addColumn('iss_trigger_reminders', 'boolean', ['default' => 1, 'null' => true])
            ->addColumn('iss_last_public_action_date', 'datetime', ['null' => true])
            ->addColumn('iss_last_public_action_type', 'string', ['length' => 20, 'null' => true])
            ->addColumn('iss_last_internal_action_date', 'datetime', ['null' => true])
            ->addColumn('iss_last_internal_action_type', 'string', ['length' => 20, 'null' => true])
            ->addColumn('iss_percent_complete', 'integer', ['length' => self::INT_TINY, 'default' => 0, 'null' => true, 'signed' => false])
            ->addColumn('iss_root_message_id', 'string', ['null' => true])
            ->addColumn('iss_access_level', 'string', ['length' => 150, 'default' => 'normal'])
            ->addIndex(['iss_prj_id'])
            ->addIndex(['iss_prc_id'])
            ->addIndex(['iss_res_id'])
            ->addIndex(['iss_grp_id'])
            ->addIndex(['iss_duplicated_iss_id'])
            ->addIndex(['iss_summary', 'iss_description'], ['type' => 'fulltext', 'name' => 'ft_issue'])
            ->create();

        $this->table('issue_access_list', ['id' => false, 'primary_key' => 'ial_id'])
            ->addColumn('ial_id', 'integer', ['length' => 10, 'signed' => false, 'identity' => true])
            ->addColumn('ial_iss_id', 'integer', ['length' => 10, 'signed' => false])
            ->addColumn('ial_usr_id', 'integer', ['length' => 10, 'signed' => false])
            ->addColumn('ial_created', 'datetime')
            ->addIndex(['ial_iss_id', 'ial_usr_id'])
            ->create();

        $this->table('issue_access_log', ['id' => false, 'primary_key' => 'alg_id'])
            ->addColumn('alg_id', 'integer', ['length' => 10, 'signed' => false, 'identity' => true])
            ->addColumn('alg_iss_id', 'integer', ['length' => 10, 'signed' => false])
            ->addColumn('alg_usr_id', 'integer', ['length' => 10, 'signed' => false])
            ->addColumn('alg_failed', 'boolean', ['default' => 0])
            ->addColumn('alg_item_id', 'integer', ['length' => 10, 'null' => true, 'signed' => false])
            ->addColumn('alg_created', 'datetime')
            ->addColumn('alg_ip_address', 'string', ['length' => 15, 'null' => true])
            ->addColumn('alg_item', 'string', ['length' => 10, 'null' => true])
            ->addColumn('alg_url', 'string', ['null' => true])
            ->addIndex(['alg_iss_id'])
            ->create();

        $this->table('issue_association', ['id' => false])
            ->addColumn('isa_issue_id', 'integer', ['length' => 10, 'default' => 0, 'signed' => false])
            ->addColumn('isa_associated_id', 'integer', ['length' => 10, 'default' => 0, 'signed' => false])
            ->addIndex(['isa_issue_id', 'isa_associated_id'])
            ->create();

        $this->table('issue_attachment', ['id' => false, 'primary_key' => 'iat_id'])
            ->addColumn('iat_id', 'integer', ['length' => 10, 'signed' => false, 'identity' => true])
            ->addColumn('iat_iss_id', 'integer', ['length' => 10, 'default' => 0, 'signed' => false])
            ->addColumn('iat_usr_id', 'integer', ['length' => 10, 'default' => 0, 'signed' => false])
            ->addColumn('iat_created_date', 'datetime', ['default' => '0000-00-00 00:00:00'])
            ->addColumn('iat_description', 'text', ['null' => true])
            ->addColumn('iat_unknown_user', 'string', ['null' => true])
            ->addColumn('iat_status', 'enum', ['default' => 'public', 'values' => [0 => 'internal', 1 => 'public']])
            ->addColumn('iat_not_id', 'integer', ['null' => true, 'signed' => false])
            ->addIndex(['iat_iss_id', 'iat_usr_id'])
            ->create();

        $this->table('issue_attachment_file', ['id' => false, 'primary_key' => 'iaf_id'])
            ->addColumn('iaf_id', 'integer', ['length' => 10, 'signed' => false, 'identity' => true])
            ->addColumn('iaf_iat_id', 'integer', ['length' => 10, 'default' => 0, 'signed' => false])
            ->addColumn('iaf_file', self::PHINX_TYPE_BLOB, ['length' => self::BLOB_LONG, 'null' => true])
            ->addColumn('iaf_filename', 'string', ['default' => ''])
            ->addColumn('iaf_filetype', 'string', ['null' => true])
            ->addColumn('iaf_filesize', 'string', ['length' => 32, 'default' => ''])
            ->addColumn('iaf_created_date', 'datetime', ['default' => '0000-00-00 00:00:00'])
            ->addIndex(['iaf_iat_id'])
            ->create();

        $this->table('issue_checkin', ['id' => false, 'primary_key' => 'isc_id'])
            ->addColumn('isc_id', 'integer', ['length' => 10, 'signed' => false, 'identity' => true])
            ->addColumn('isc_iss_id', 'integer', ['length' => 10, 'default' => 0, 'signed' => false])
            ->addColumn('isc_commitid', 'string', ['length' => 40, 'null' => true, 'collation' => 'utf8_bin'])
            ->addColumn('isc_reponame', 'string', ['default' => ''])
            ->addColumn('isc_module', 'string', ['default' => ''])
            ->addColumn('isc_filename', 'string', ['default' => ''])
            ->addColumn('isc_old_version', 'string', ['length' => 40, 'null' => true])
            ->addColumn('isc_new_version', 'string', ['length' => 40, 'null' => true])
            ->addColumn('isc_created_date', 'datetime', ['default' => '0000-00-00 00:00:00'])
            ->addColumn('isc_username', 'string', ['length' => 32, 'default' => ''])
            ->addColumn('isc_commit_msg', 'text', ['null' => true])
            ->addIndex(['isc_iss_id'])
            ->addIndex(['isc_commitid'])
            ->create();

        $this->table('issue_commit', ['id' => false, 'primary_key' => 'isc_id'])
            ->addColumn('isc_id', 'integer', ['length' => 10, 'signed' => false, 'identity' => true])
            ->addColumn('isc_iss_id', 'integer', ['length' => 10, 'signed' => false])
            ->addColumn('isc_com_id', 'integer', ['length' => 10, 'signed' => false])
            ->create();

        $this->table('issue_custom_field', ['id' => false, 'primary_key' => 'icf_id'])
            ->addColumn('icf_id', 'integer', ['length' => 10, 'signed' => false, 'identity' => true])
            ->addColumn('icf_iss_id', 'integer', ['length' => 10, 'default' => 0, 'signed' => false])
            ->addColumn('icf_fld_id', 'integer', ['length' => 10, 'default' => 0, 'signed' => false])
            ->addColumn('icf_value', 'text', ['null' => true])
            ->addColumn('icf_value_integer', 'integer', ['null' => true])
            ->addColumn('icf_value_date', 'date', ['null' => true])
            ->addIndex(['icf_iss_id'])
            ->addIndex(['icf_fld_id'])
            ->addIndex(['icf_value'], ['type' => 'fulltext', 'name' => 'ft_icf_value'])
            ->create();

        $this->table('issue_history', ['id' => false, 'primary_key' => 'his_id'])
            ->addColumn('his_id', 'integer', ['length' => 10, 'signed' => false, 'identity' => true])
            ->addColumn('his_iss_id', 'integer', ['length' => 10, 'default' => 0, 'signed' => false])
            ->addColumn('his_usr_id', 'integer', ['default' => 0, 'signed' => false])
            ->addColumn('his_htt_id', 'integer', ['length' => self::INT_TINY, 'default' => 0])
            ->addColumn('his_is_hidden', 'boolean', ['default' => 0])
            ->addColumn('his_created_date', 'datetime', ['default' => '0000-00-00 00:00:00'])
            ->addColumn('his_summary', 'text')
            ->addColumn('his_context', 'text', ['length' => self::INT_MEDIUM])
            ->addColumn('his_min_role', 'boolean', ['default' => 1])
            ->addIndex(['his_id'])
            ->addIndex(['his_iss_id'])
            ->addIndex(['his_created_date'])
            ->create();

        $this->table('issue_partner', ['id' => false, 'primary_key' => ['ipa_iss_id', 'ipa_par_code']])
            ->addColumn('ipa_iss_id', 'integer', ['signed' => false])
            ->addColumn('ipa_par_code', 'string', ['length' => 30])
            ->addColumn('ipa_created_date', 'datetime')
            ->create();

        $this->table('issue_product_version', ['id' => false, 'primary_key' => 'ipv_id'])
            ->addColumn('ipv_id', 'integer', ['length' => 11, 'signed' => false, 'identity' => true])
            ->addColumn('ipv_iss_id', 'integer', ['signed' => false])
            ->addColumn('ipv_pro_id', 'integer', ['signed' => false])
            ->addColumn('ipv_version', 'string')
            ->addIndex(['ipv_iss_id'])
            ->create();

        $this->table('issue_quarantine', ['id' => false, 'primary_key' => 'iqu_iss_id'])
            ->addColumn('iqu_iss_id', 'integer', ['length' => 11, 'signed' => false, 'identity' => true])
            ->addColumn('iqu_expiration', 'datetime', ['null' => true])
            ->addColumn('iqu_status', 'boolean', ['null' => true])
            ->addIndex(['iqu_expiration'])
            ->create();

        $this->table('issue_user', ['id' => false, 'primary_key' => ['isu_iss_id', 'isu_usr_id']])
            ->addColumn('isu_iss_id', 'integer', ['length' => 10, 'default' => 0, 'signed' => false])
            ->addColumn('isu_usr_id', 'integer', ['length' => 10, 'default' => 0, 'signed' => false])
            ->addColumn('isu_assigned_date', 'datetime', ['null' => true])
            ->addColumn('isu_order', 'integer', ['default' => 0])
            ->addIndex(['isu_order'])
            ->addIndex(['isu_usr_id'])
            ->addIndex(['isu_iss_id'])
            ->create();

        $this->table('issue_user_replier', ['id' => false, 'primary_key' => 'iur_id'])
            ->addColumn('iur_id', 'integer', ['length' => 11, 'signed' => false, 'identity' => true])
            ->addColumn('iur_iss_id', 'integer', ['length' => 10, 'default' => 0, 'signed' => false])
            ->addColumn('iur_usr_id', 'integer', ['length' => 10, 'default' => 0, 'signed' => false])
            ->addColumn('iur_email', 'string', ['null' => true])
            ->addIndex(['iur_usr_id'])
            ->addIndex(['iur_iss_id'])
            ->create();

        $this->table('link_filter', ['id' => false, 'primary_key' => 'lfi_id'])
            ->addColumn('lfi_id', 'integer', ['length' => 11, 'signed' => false, 'identity' => true])
            ->addColumn('lfi_pattern', 'string')
            ->addColumn('lfi_replacement', 'string')
            ->addColumn('lfi_usr_role', 'integer', ['length' => self::INT_TINY, 'default' => 0])
            ->addColumn('lfi_description', 'string', ['null' => true])
            ->create();

        $this->table('mail_queue', ['id' => false, 'primary_key' => 'maq_id'])
            ->addColumn('maq_id', 'integer', ['length' => 11, 'signed' => false, 'identity' => true])
            ->addColumn('maq_iss_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('maq_queued_date', 'datetime')
            ->addColumn('maq_status', 'string', ['length' => 8, 'default' => 'pending'])
            ->addColumn('maq_save_copy', 'boolean', ['default' => 1])
            ->addColumn('maq_sender_ip_address', 'string', ['length' => 15])
            ->addColumn('maq_recipient', 'string')
            ->addColumn('maq_subject', 'string')
            ->addColumn('maq_message_id', 'string', ['null' => true])
            ->addColumn('maq_headers', 'text')
            ->addColumn('maq_body', self::PHINX_TYPE_BLOB, ['length' => self::BLOB_LONG])
            ->addColumn('maq_type', 'string', ['length' => 30, 'null' => true])
            ->addColumn('maq_usr_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('maq_type_id', 'integer', ['null' => true, 'signed' => false])
            ->addIndex(['maq_status'])
            ->addIndex(['maq_iss_id'])
            ->addIndex(['maq_type', 'maq_type_id'])
            ->create();

        $this->table('mail_queue_log', ['id' => false, 'primary_key' => 'mql_id'])
            ->addColumn('mql_id', 'integer', ['length' => 11, 'signed' => false, 'identity' => true])
            ->addColumn('mql_maq_id', 'integer', ['signed' => false])
            ->addColumn('mql_created_date', 'datetime')
            ->addColumn('mql_status', 'string', ['length' => 8, 'default' => 'error'])
            ->addColumn('mql_server_message', 'text', ['null' => true])
            ->addIndex(['mql_maq_id'])
            ->create();

        $this->table('news', ['id' => false, 'primary_key' => 'nws_id'])
            ->addColumn('nws_id', 'integer', ['length' => 11, 'signed' => false, 'identity' => true])
            ->addColumn('nws_usr_id', 'integer', ['signed' => false])
            ->addColumn('nws_created_date', 'datetime')
            ->addColumn('nws_title', 'string', ['encoding' => 'utf8'])
            ->addColumn('nws_message', 'text')
            ->addColumn('nws_status', 'string', ['length' => 8, 'default' => 'active'])
            ->addIndex(['nws_title'], ['unique' => true])
            ->create();

        $this->table('note', ['id' => false, 'primary_key' => 'not_id'])
            ->addColumn('not_id', 'integer', ['length' => 11, 'signed' => false, 'identity' => true])
            ->addColumn('not_iss_id', 'integer', ['default' => 0, 'signed' => false])
            ->addColumn('not_created_date', 'datetime', ['default' => '0000-00-00 00:00:00'])
            ->addColumn('not_usr_id', 'integer', ['default' => 0, 'signed' => false])
            ->addColumn('not_title', 'string')
            ->addColumn('not_note', 'text', ['length' => self::INT_REGULAR])
            ->addColumn('not_full_message', self::PHINX_TYPE_BLOB, ['length' => self::BLOB_LONG, 'null' => true])
            ->addColumn('not_parent_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('not_unknown_user', 'string', ['null' => true])
            ->addColumn('not_has_attachment', 'boolean', ['default' => 0])
            ->addColumn('not_message_id', 'string', ['null' => true])
            ->addColumn('not_removed', 'boolean', ['default' => 0])
            ->addColumn('not_is_blocked', 'boolean', ['default' => 0])
            ->addIndex(['not_iss_id', 'not_usr_id'], ['name' => 'not_bug_id'])
            ->addIndex(['not_message_id'])
            ->addIndex(['not_parent_id'])
            ->addIndex(['not_title', 'not_note'], ['type' => 'fulltext', 'name' => 'ft_note'])
            ->create();

        $this->table('partner_project', ['id' => false, 'primary_key' => ['pap_prj_id', 'pap_par_code']])
            ->addColumn('pap_prj_id', 'integer', ['signed' => false])
            ->addColumn('pap_par_code', 'string', ['length' => 30])
            ->create();

        $this->table('phone_support', ['id' => false, 'primary_key' => 'phs_id'])
            ->addColumn('phs_id', 'integer', ['length' => 10, 'signed' => false, 'identity' => true])
            ->addColumn('phs_usr_id', 'integer', ['length' => 10, 'default' => 0, 'signed' => false])
            ->addColumn('phs_iss_id', 'integer', ['length' => 10, 'default' => 0, 'signed' => false])
            ->addColumn('phs_ttr_id', 'integer', ['length' => 10, 'null' => true, 'signed' => false])
            ->addColumn('phs_call_from_lname', 'string', ['length' => 64, 'null' => true])
            ->addColumn('phs_call_from_fname', 'string', ['length' => 64, 'null' => true])
            ->addColumn('phs_call_to_lname', 'string', ['length' => 64, 'null' => true])
            ->addColumn('phs_call_to_fname', 'string', ['length' => 64, 'null' => true])
            ->addColumn('phs_created_date', 'datetime', ['default' => '0000-00-00 00:00:00'])
            ->addColumn('phs_type', 'enum', ['default' => 'incoming', 'values' => [0 => 'incoming', 1 => 'outgoing']])
            ->addColumn('phs_phone_number', 'string', ['length' => 32, 'default' => ''])
            ->addColumn('phs_phone_type', 'string', ['length' => 6])
            ->addColumn('phs_phc_id', 'integer', ['signed' => false])
            ->addColumn('phs_description', 'text')
            ->addIndex(['phs_iss_id'])
            ->addIndex(['phs_usr_id'])
            ->addIndex(['phs_description'], ['type' => 'fulltext', 'name' => 'ft_phone_support'])
            ->create();

        $this->table('product', ['id' => false, 'primary_key' => 'pro_id'])
            ->addColumn('pro_id', 'integer', ['length' => 11, 'signed' => false, 'identity' => true])
            ->addColumn('pro_title', 'string')
            ->addColumn('pro_version_howto', 'string')
            ->addColumn('pro_rank', 'integer', ['length' => self::INT_MEDIUM, 'default' => 0, 'signed' => false])
            ->addColumn('pro_removed', 'boolean', ['default' => 0, 'signed' => false])
            ->addColumn('pro_email', 'string', ['null' => true])
            ->addIndex(['pro_rank'])
            ->create();

        $this->table('project', ['id' => false, 'primary_key' => 'prj_id'])
            ->addColumn('prj_id', 'integer', ['length' => 11, 'signed' => false, 'identity' => true])
            ->addColumn('prj_created_date', 'datetime', ['default' => '0000-00-00 00:00:00'])
            ->addColumn('prj_title', 'string', ['length' => 64, 'default' => ''])
            ->addColumn('prj_status', 'set', ['default' => 'active', 'values' => ['active', 'archived']])
            ->addColumn('prj_lead_usr_id', 'integer', ['default' => 0, 'signed' => false])
            ->addColumn('prj_initial_sta_id', 'integer', ['length' => 10, 'default' => 0, 'signed' => false])
            ->addColumn('prj_remote_invocation', 'string', ['length' => 8, 'default' => 'disabled'])
            ->addColumn('prj_anonymous_post', 'string', ['length' => 8, 'default' => 'disabled'])
            ->addColumn('prj_anonymous_post_options', 'text', ['null' => true])
            ->addColumn('prj_outgoing_sender_name', 'string')
            ->addColumn('prj_outgoing_sender_email', 'string')
            ->addColumn('prj_sender_flag', 'string', ['null' => true])
            ->addColumn('prj_sender_flag_location', 'string', ['length' => 6, 'null' => true])
            ->addColumn('prj_mail_aliases', 'string', ['null' => true])
            ->addColumn('prj_customer_backend', 'string', ['length' => 64, 'null' => true])
            ->addColumn('prj_workflow_backend', 'string', ['length' => 64, 'null' => true])
            ->addColumn('prj_segregate_reporter', 'boolean', ['default' => 0, 'null' => true])
            ->addIndex(['prj_title'], ['unique' => true])
            ->addIndex(['prj_lead_usr_id'])
            ->create();

        $this->table('project_category', ['id' => false, 'primary_key' => 'prc_id'])
            ->addColumn('prc_id', 'integer', ['length' => 11, 'signed' => false, 'identity' => true])
            ->addColumn('prc_prj_id', 'integer', ['default' => 0, 'signed' => false])
            ->addColumn('prc_title', 'string', ['length' => 64, 'default' => ''])
            ->addIndex(['prc_prj_id', 'prc_title'], ['unique' => true, 'name' => 'uniq_category'])
            ->addIndex(['prc_prj_id'])
            ->create();

        $this->table('project_custom_field', ['id' => false, 'primary_key' => 'pcf_id'])
            ->addColumn('pcf_id', 'integer', ['length' => 10, 'signed' => false, 'identity' => true])
            ->addColumn('pcf_prj_id', 'integer', ['length' => 10, 'default' => 0, 'signed' => false])
            ->addColumn('pcf_fld_id', 'integer', ['length' => 10, 'default' => 0, 'signed' => false])
            ->addIndex(['pcf_prj_id'])
            ->addIndex(['pcf_fld_id'])
            ->create();

        $this->table('project_email_response', ['id' => false, 'primary_key' => ['per_prj_id', 'per_ere_id']])
            ->addColumn('per_prj_id', 'integer', ['signed' => false])
            ->addColumn('per_ere_id', 'integer', ['length' => 10, 'signed' => false])
            ->create();

        $this->table('project_field_display', ['id' => false, 'primary_key' => ['pfd_prj_id', 'pfd_field']])
            ->addColumn('pfd_prj_id', 'integer', ['signed' => false])
            ->addColumn('pfd_field', 'string', ['length' => 20])
            ->addColumn('pfd_min_role', 'boolean', ['default' => 0])
            ->addColumn('pfd_required', 'boolean', ['default' => 0])
            ->create();

        $this->table('project_group', ['id' => false, 'primary_key' => ['pgr_prj_id', 'pgr_grp_id']])
            ->addColumn('pgr_prj_id', 'integer', ['signed' => false])
            ->addColumn('pgr_grp_id', 'integer', ['signed' => false])
            ->create();

        $this->table('project_link_filter', ['id' => false, 'primary_key' => ['plf_prj_id', 'plf_lfi_id']])
            ->addColumn('plf_prj_id', 'integer')
            ->addColumn('plf_lfi_id', 'integer')
            ->create();

        $this->table('project_news', ['id' => false, 'primary_key' => ['prn_prj_id', 'prn_nws_id']])
            ->addColumn('prn_nws_id', 'integer', ['signed' => false])
            ->addColumn('prn_prj_id', 'integer', ['signed' => false])
            ->create();

        $this->table('project_phone_category', ['id' => false, 'primary_key' => 'phc_id'])
            ->addColumn('phc_id', 'integer', ['length' => 11, 'signed' => false, 'identity' => true])
            ->addColumn('phc_prj_id', 'integer', ['default' => 0, 'signed' => false])
            ->addColumn('phc_title', 'string', ['length' => 64, 'default' => ''])
            ->addIndex(['phc_prj_id', 'phc_title'], ['unique' => true, 'name' => 'uniq_category'])
            ->addIndex(['phc_prj_id'])
            ->create();

        $this->table('project_priority', ['id' => false, 'primary_key' => 'pri_id'])
            ->addColumn('pri_id', 'integer', ['length' => self::INT_SMALL, 'signed' => false, 'identity' => true])
            ->addColumn('pri_prj_id', 'integer', ['signed' => false])
            ->addColumn('pri_title', 'string', ['length' => 64, 'default' => ''])
            ->addColumn('pri_rank', 'boolean')
            ->addColumn('pri_icon', 'integer', ['length' => self::INT_TINY, 'default' => 0])
            ->addIndex(['pri_title', 'pri_prj_id'], ['unique' => true])
            ->create();

        $this->table('project_release', ['id' => false, 'primary_key' => 'pre_id'])
            ->addColumn('pre_id', 'integer', ['length' => 10, 'signed' => false, 'identity' => true])
            ->addColumn('pre_prj_id', 'integer', ['length' => 10, 'default' => 0, 'signed' => false])
            ->addColumn('pre_title', 'string', ['length' => 128, 'default' => ''])
            ->addColumn('pre_scheduled_date', 'date', ['default' => '0000-00-00'])
            ->addColumn('pre_status', 'enum', ['default' => 'available', 'values' => [0 => 'available', 1 => 'unavailable']])
            ->addIndex(['pre_prj_id', 'pre_title'], ['unique' => true, 'name' => 'pre_title'])
            ->create();

        $this->table('project_round_robin', ['id' => false, 'primary_key' => 'prr_id'])
            ->addColumn('prr_id', 'integer', ['length' => 11, 'signed' => false, 'identity' => true])
            ->addColumn('prr_prj_id', 'integer', ['signed' => false])
            ->addColumn('prr_blackout_start', 'time')
            ->addColumn('prr_blackout_end', 'time')
            ->addIndex(['prr_prj_id'], ['unique' => true])
            ->create();

        $this->table('project_severity', ['id' => false, 'primary_key' => 'sev_id'])
            ->addColumn('sev_id', 'integer', ['length' => self::INT_SMALL, 'signed' => false, 'identity' => true])
            ->addColumn('sev_prj_id', 'integer', ['signed' => false])
            ->addColumn('sev_title', 'string', ['length' => 64, 'default' => ''])
            ->addColumn('sev_description', 'string', ['null' => true])
            ->addColumn('sev_rank', 'boolean')
            ->addIndex(['sev_title', 'sev_prj_id'], ['unique' => true, 'name' => 'sev_title'])
            ->create();

        $this->table('project_status', ['id' => false, 'primary_key' => 'prs_id'])
            ->addColumn('prs_id', 'integer', ['length' => 10, 'signed' => false, 'identity' => true])
            ->addColumn('prs_prj_id', 'integer', ['length' => 10, 'signed' => false])
            ->addColumn('prs_sta_id', 'integer', ['length' => 10, 'signed' => false])
            ->addIndex(['prs_prj_id', 'prs_sta_id'])
            ->create();

        $this->table('project_status_date', ['id' => false, 'primary_key' => 'psd_id'])
            ->addColumn('psd_id', 'integer', ['length' => 11, 'signed' => false, 'identity' => true])
            ->addColumn('psd_prj_id', 'integer', ['signed' => false])
            ->addColumn('psd_sta_id', 'integer', ['length' => 10, 'signed' => false])
            ->addColumn('psd_date_field', 'string', ['length' => 64])
            ->addColumn('psd_label', 'string', ['length' => 32])
            ->addIndex(['psd_prj_id', 'psd_sta_id'], ['unique' => true])
            ->create();

        $this->table('project_user', ['id' => false, 'primary_key' => 'pru_id'])
            ->addColumn('pru_id', 'integer', ['length' => 11, 'signed' => false, 'identity' => true])
            ->addColumn('pru_prj_id', 'integer', ['default' => 0, 'signed' => false])
            ->addColumn('pru_usr_id', 'integer', ['default' => 0, 'signed' => false])
            ->addColumn('pru_role', 'boolean', ['default' => 1, 'signed' => false])
            ->addIndex(['pru_prj_id', 'pru_usr_id'], ['unique' => true])
            ->create();

        $this->table('reminder_action', ['id' => false, 'primary_key' => 'rma_id'])
            ->addColumn('rma_id', 'integer', ['length' => 11, 'signed' => false, 'identity' => true])
            ->addColumn('rma_rem_id', 'integer', ['signed' => false])
            ->addColumn('rma_rmt_id', 'integer', ['length' => self::INT_TINY, 'signed' => false])
            ->addColumn('rma_created_date', 'datetime')
            ->addColumn('rma_last_updated_date', 'datetime', ['null' => true])
            ->addColumn('rma_title', 'string', ['length' => 64])
            ->addColumn('rma_rank', 'integer', ['length' => self::INT_TINY, 'signed' => false])
            ->addColumn('rma_alert_irc', 'boolean', ['default' => 0, 'signed' => false])
            ->addColumn('rma_alert_group_leader', 'boolean', ['default' => 0, 'signed' => false])
            ->addColumn('rma_boilerplate', 'string', ['null' => true])
            ->create();

        $this->table('reminder_action_list', ['id' => false])
            ->addColumn('ral_rma_id', 'integer', ['signed' => false])
            ->addColumn('ral_email', 'string')
            ->addColumn('ral_usr_id', 'integer', ['signed' => false])
            ->create();

        $this->table('reminder_action_type', ['id' => false, 'primary_key' => 'rmt_id'])
            ->addColumn('rmt_id', 'integer', ['length' => self::INT_TINY, 'signed' => false, 'identity' => true])
            ->addColumn('rmt_type', 'string', ['length' => 32])
            ->addColumn('rmt_title', 'string', ['length' => 64])
            ->addIndex(['rmt_type'], ['unique' => true])
            ->addIndex(['rmt_title'], ['unique' => true])
            ->create();

        $this->table('reminder_field', ['id' => false, 'primary_key' => 'rmf_id'])
            ->addColumn('rmf_id', 'integer', ['length' => self::INT_TINY, 'signed' => false, 'identity' => true])
            ->addColumn('rmf_title', 'string', ['length' => 128])
            ->addColumn('rmf_sql_field', 'string', ['length' => 32])
            ->addColumn('rmf_sql_representation', 'string')
            ->addColumn('rmf_allow_column_compare', 'boolean', ['default' => 0, 'null' => true])
            ->addIndex(['rmf_title'], ['unique' => true])
            ->create();

        $this->table('reminder_history', ['id' => false, 'primary_key' => 'rmh_id'])
            ->addColumn('rmh_id', 'integer', ['length' => 11, 'signed' => false, 'identity' => true])
            ->addColumn('rmh_iss_id', 'integer')
            ->addColumn('rmh_rma_id', 'integer')
            ->addColumn('rmh_created_date', 'datetime')
            ->create();

        $this->table('reminder_level', ['id' => false, 'primary_key' => 'rem_id'])
            ->addColumn('rem_id', 'integer', ['length' => 11, 'signed' => false, 'identity' => true])
            ->addColumn('rem_created_date', 'datetime')
            ->addColumn('rem_rank', 'boolean')
            ->addColumn('rem_last_updated_date', 'datetime', ['null' => true])
            ->addColumn('rem_title', 'string', ['length' => 64])
            ->addColumn('rem_prj_id', 'integer', ['signed' => false])
            ->addColumn('rem_skip_weekend', 'boolean', ['default' => 0])
            ->create();

        $this->table('reminder_level_condition', ['id' => false, 'primary_key' => 'rlc_id'])
            ->addColumn('rlc_id', 'integer', ['length' => 11, 'signed' => false, 'identity' => true])
            ->addColumn('rlc_rma_id', 'integer', ['signed' => false])
            ->addColumn('rlc_rmf_id', 'integer', ['length' => self::INT_TINY, 'signed' => false])
            ->addColumn('rlc_rmo_id', 'boolean', ['signed' => false])
            ->addColumn('rlc_created_date', 'datetime')
            ->addColumn('rlc_last_updated_date', 'datetime', ['null' => true])
            ->addColumn('rlc_value', 'string', ['length' => 64])
            ->addColumn('rlc_comparison_rmf_id', 'integer', ['length' => self::INT_TINY, 'null' => true, 'signed' => false])
            ->create();

        $this->table('reminder_operator', ['id' => false, 'primary_key' => 'rmo_id'])
            ->addColumn('rmo_id', 'integer', ['length' => self::INT_TINY, 'signed' => false, 'identity' => true])
            ->addColumn('rmo_title', 'string', ['length' => 32, 'null' => true])
            ->addColumn('rmo_sql_representation', 'string', ['length' => 32, 'null' => true])
            ->addIndex(['rmo_title'], ['unique' => true])
            ->create();

        $this->table('reminder_priority', ['id' => false, 'primary_key' => 'rep_id'])
            ->addColumn('rep_id', 'integer', ['length' => 11, 'signed' => false, 'identity' => true])
            ->addColumn('rep_rem_id', 'integer', ['signed' => false])
            ->addColumn('rep_pri_id', 'integer', ['signed' => false])
            ->create();

        $this->table('reminder_product', ['id' => false, 'primary_key' => 'rpr_id'])
            ->addColumn('rpr_id', 'integer', ['length' => 11, 'signed' => false, 'identity' => true])
            ->addColumn('rpr_rem_id', 'integer', ['signed' => false])
            ->addColumn('rpr_pro_id', 'integer', ['signed' => false])
            ->create();

        $this->table('reminder_requirement', ['id' => false, 'primary_key' => 'rer_id'])
            ->addColumn('rer_id', 'integer', ['length' => 11, 'signed' => false, 'identity' => true])
            ->addColumn('rer_rem_id', 'integer', ['signed' => false])
            ->addColumn('rer_iss_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('rer_support_level_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('rer_customer_id', 'string', ['length' => 128, 'null' => true])
            ->addColumn('rer_trigger_all_issues', 'boolean', ['default' => 0, 'signed' => false])
            ->create();

        $this->table('reminder_severity', ['id' => false, 'primary_key' => 'rms_id'])
            ->addColumn('rms_id', 'integer', ['length' => 11, 'signed' => false, 'identity' => true])
            ->addColumn('rms_rem_id', 'integer', ['signed' => false])
            ->addColumn('rms_sev_id', 'integer', ['signed' => false])
            ->create();

        $this->table('reminder_triggered_action', ['id' => false, 'primary_key' => ['rta_iss_id']])
            ->addColumn('rta_iss_id', 'integer', ['signed' => false])
            ->addColumn('rta_rma_id', 'integer', ['signed' => false])
            ->create();

        $this->table('resolution', ['id' => false, 'primary_key' => 'res_id'])
            ->addColumn('res_id', 'integer', ['length' => 10, 'signed' => false, 'identity' => true])
            ->addColumn('res_title', 'string', ['length' => 64, 'default' => ''])
            ->addColumn('res_created_date', 'datetime', ['default' => '0000-00-00 00:00:00'])
            ->addColumn('res_rank', 'integer', ['length' => 2])
            ->addIndex(['res_title'], ['unique' => true])
            ->create();

        $this->table('round_robin_user', ['id' => false])
            ->addColumn('rru_prr_id', 'integer', ['signed' => false])
            ->addColumn('rru_usr_id', 'integer', ['signed' => false])
            ->addColumn('rru_next', 'boolean', ['null' => true, 'signed' => false])
            ->create();

        $this->table('search_profile', ['id' => false, 'primary_key' => 'sep_id'])
            ->addColumn('sep_id', 'integer', ['length' => 11, 'signed' => false, 'identity' => true])
            ->addColumn('sep_usr_id', 'integer', ['signed' => false])
            ->addColumn('sep_prj_id', 'integer', ['signed' => false])
            ->addColumn('sep_type', 'char', ['length' => 5])
            ->addColumn('sep_user_profile', self::PHINX_TYPE_BLOB)
            ->addIndex(['sep_usr_id', 'sep_prj_id', 'sep_type'], ['unique' => true])
            ->create();

        $this->table('status', ['id' => false, 'primary_key' => 'sta_id'])
            ->addColumn('sta_id', 'integer', ['length' => 10, 'signed' => false, 'identity' => true])
            ->addColumn('sta_title', 'string', ['length' => 64, 'default' => ''])
            ->addColumn('sta_abbreviation', 'char', ['length' => 3])
            ->addColumn('sta_rank', 'integer', ['length' => 2])
            ->addColumn('sta_color', 'string', ['length' => 7, 'default' => ''])
            ->addColumn('sta_is_closed', 'boolean', ['default' => 0])
            ->addIndex(['sta_abbreviation'], ['unique' => true])
            ->addIndex(['sta_rank'])
            ->addIndex(['sta_is_closed'])
            ->create();

        $this->table('subscription', ['id' => false, 'primary_key' => 'sub_id'])
            ->addColumn('sub_id', 'integer', ['length' => 10, 'signed' => false, 'identity' => true])
            ->addColumn('sub_iss_id', 'integer', ['length' => 10, 'default' => 0, 'signed' => false])
            ->addColumn('sub_usr_id', 'integer', ['length' => 10, 'null' => true, 'signed' => false])
            ->addColumn('sub_created_date', 'datetime', ['default' => '0000-00-00 00:00:00'])
            ->addColumn('sub_level', 'string', ['length' => 10, 'default' => 'user'])
            ->addColumn('sub_email', 'string', ['null' => true])
            ->addIndex(['sub_iss_id', 'sub_usr_id'])
            ->create();

        $this->table('subscription_type', ['id' => false, 'primary_key' => 'sbt_id'])
            ->addColumn('sbt_id', 'integer', ['length' => 10, 'signed' => false, 'identity' => true])
            ->addColumn('sbt_sub_id', 'integer', ['length' => 10, 'default' => 0, 'signed' => false])
            ->addColumn('sbt_type', 'string', ['length' => 10, 'default' => ''])
            ->addIndex(['sbt_sub_id'])
            ->create();

        $this->table('support_email', ['id' => false, 'primary_key' => 'sup_id'])
            ->addColumn('sup_id', 'integer', ['length' => 11, 'signed' => false, 'identity' => true])
            ->addColumn('sup_ema_id', 'integer', ['length' => 10, 'default' => 0, 'signed' => false])
            ->addColumn('sup_parent_id', 'integer', ['default' => 0, 'signed' => false])
            ->addColumn('sup_iss_id', 'integer', ['default' => 0, 'null' => true, 'signed' => false])
            ->addColumn('sup_usr_id', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('sup_customer_id', 'string', ['length' => 128, 'null' => true])
            ->addColumn('sup_message_id', 'string', ['default' => ''])
            ->addColumn('sup_date', 'datetime', ['default' => '0000-00-00 00:00:00'])
            ->addColumn('sup_from', 'string', ['default' => ''])
            ->addColumn('sup_to', 'text', ['null' => true])
            ->addColumn('sup_cc', 'text', ['null' => true])
            ->addColumn('sup_subject', 'string', ['default' => ''])
            ->addColumn('sup_has_attachment', 'boolean', ['default' => 0])
            ->addColumn('sup_removed', 'boolean', ['default' => 0])
            ->addIndex(['sup_parent_id'])
            ->addIndex(['sup_ema_id'])
            ->addIndex(['sup_removed'])
            ->addIndex(['sup_removed', 'sup_ema_id', 'sup_iss_id'])
            ->addIndex(['sup_removed', 'sup_ema_id', 'sup_date'])
            ->addIndex(['sup_usr_id'])
            ->create();

        $this->table('support_email_body', ['id' => false, 'primary_key' => ['seb_sup_id']])
            ->addColumn('seb_sup_id', 'integer', ['signed' => false])
            ->addColumn('seb_body', 'text', ['length' => self::INT_REGULAR])
            ->addColumn('seb_full_email', self::PHINX_TYPE_BLOB, ['length' => self::BLOB_LONG])
            ->addIndex(['seb_body'], ['type' => 'fulltext', 'name' => 'ft_support_email'])
            ->create();

        $this->table('time_tracking', ['id' => false, 'primary_key' => 'ttr_id'])
            ->addColumn('ttr_id', 'integer', ['length' => 10, 'signed' => false, 'identity' => true])
            ->addColumn('ttr_ttc_id', 'integer', ['length' => 10, 'default' => 0, 'signed' => false])
            ->addColumn('ttr_iss_id', 'integer', ['length' => 10, 'default' => 0, 'signed' => false])
            ->addColumn('ttr_usr_id', 'integer', ['length' => 10, 'default' => 0, 'signed' => false])
            ->addColumn('ttr_created_date', 'datetime', ['default' => '0000-00-00 00:00:00'])
            ->addColumn('ttr_time_spent', 'integer', ['default' => 0, 'signed' => false])
            ->addColumn('ttr_summary', 'string', ['default' => ''])
            ->addIndex(['ttr_ttc_id', 'ttr_iss_id', 'ttr_usr_id'])
            ->addIndex(['ttr_iss_id'])
            ->addIndex(['ttr_summary'], ['type' => 'fulltext', 'name' => 'ft_time_tracking'])
            ->create();

        $this->table('time_tracking_category', ['id' => false, 'primary_key' => 'ttc_id'])
            ->addColumn('ttc_id', 'integer', ['length' => 10, 'signed' => false, 'identity' => true])
            ->addColumn('ttc_prj_id', 'integer', ['length' => 10, 'default' => 0, 'signed' => false])
            ->addColumn('ttc_title', 'string', ['length' => 128, 'default' => ''])
            ->addColumn('ttc_created_date', 'datetime', ['default' => '0000-00-00 00:00:00'])
            ->addIndex(['ttc_prj_id', 'ttc_title'], ['name' => 'ttc_title'])
            ->create();

        $this->table('user', ['id' => false, 'primary_key' => 'usr_id'])
            ->addColumn('usr_id', 'integer', ['length' => 11, 'signed' => false, 'identity' => true])
            ->addColumn('usr_customer_id', 'string', ['length' => 128, 'null' => true])
            ->addColumn('usr_customer_contact_id', 'string', ['length' => 128, 'null' => true])
            ->addColumn('usr_created_date', 'datetime', ['default' => '0000-00-00 00:00:00'])
            ->addColumn('usr_status', 'string', ['length' => 8, 'default' => 'active'])
            ->addColumn('usr_password', 'string', ['default' => ''])
            ->addColumn('usr_full_name', 'string', ['default' => ''])
            ->addColumn('usr_email', 'string', ['default' => '', 'encoding' => 'latin1'])
            ->addColumn('usr_sms_email', 'string', ['null' => true])
            ->addColumn('usr_clocked_in', 'boolean', ['default' => 0, 'null' => true])
            ->addColumn('usr_lang', 'string', ['length' => 5, 'null' => true])
            ->addColumn('usr_external_id', 'string', ['length' => 100])
            ->addColumn('usr_last_login', 'datetime', ['null' => true])
            ->addColumn('usr_last_failed_login', 'datetime', ['null' => true])
            ->addColumn('usr_failed_logins', 'integer', ['default' => 0, 'signed' => false])
            ->addColumn('usr_par_code', 'string', ['length' => 30, 'null' => true])
            ->addIndex(['usr_email'], ['unique' => true])
            ->create();

        $this->table('user_alias', ['id' => false])
            ->addColumn('ual_usr_id', 'integer', ['signed' => false])
            ->addColumn('ual_email', 'string', ['null' => true, 'encoding' => 'latin1'])
            ->addIndex(['ual_email'], ['unique' => true])
            ->addIndex(['ual_usr_id', 'ual_email'])
            ->create();

        $this->table('user_group', ['id' => false, 'primary_key' => ['ugr_usr_id', 'ugr_grp_id']])
            ->addColumn('ugr_usr_id', 'integer', ['length' => 10, 'signed' => false])
            ->addColumn('ugr_grp_id', 'integer', ['length' => 10, 'signed' => false])
            ->addColumn('ugr_created', 'datetime')
            ->create();

        $this->table('user_preference', ['id' => false, 'primary_key' => ['upr_usr_id']])
            ->addColumn('upr_usr_id', 'integer', ['signed' => false])
            ->addColumn('upr_timezone', 'string', ['length' => 100])
            ->addColumn('upr_week_firstday', 'boolean', ['default' => 0])
            ->addColumn('upr_list_refresh_rate', 'integer', ['length' => 5, 'default' => 5, 'null' => true])
            ->addColumn('upr_email_refresh_rate', 'integer', ['length' => 5, 'default' => 5, 'null' => true])
            ->addColumn('upr_email_signature', 'text', ['length' => self::INT_REGULAR, 'null' => true])
            ->addColumn('upr_auto_append_email_sig', 'boolean', ['default' => 0, 'null' => true])
            ->addColumn('upr_auto_append_note_sig', 'boolean', ['default' => 0, 'null' => true])
            ->addColumn('upr_auto_close_popup_window', 'boolean', ['default' => 0, 'null' => true])
            ->addColumn('upr_relative_date', 'boolean', ['default' => 1, 'null' => true])
            ->addColumn('upr_collapsed_emails', 'boolean', ['default' => 1, 'null' => true])
            ->create();

        $this->table('user_project_preference', ['id' => false, 'primary_key' => ['upp_usr_id', 'upp_prj_id']])
            ->addColumn('upp_usr_id', 'integer', ['signed' => false])
            ->addColumn('upp_prj_id', 'integer', ['signed' => false])
            ->addColumn('upp_receive_assigned_email', 'boolean', ['default' => 1, 'null' => true])
            ->addColumn('upp_receive_new_issue_email', 'boolean', ['default' => 0, 'null' => true])
            ->addColumn('upp_receive_copy_of_own_action', 'boolean', ['default' => 0, 'null' => true])
            ->create();
    }
}
