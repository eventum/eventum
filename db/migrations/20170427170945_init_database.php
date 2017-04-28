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

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

class InitDatabase extends AbstractMigration
{
    // According to https://dev.mysql.com/doc/refman/5.0/en/blob.html BLOB sizes are the same as TEXT
    const BLOB_TINY = MysqlAdapter::BLOB_TINY;
    const BLOB_REGULAR = MysqlAdapter::BLOB_REGULAR;
    const BLOB_MEDIUM = MysqlAdapter::BLOB_MEDIUM;
    const BLOB_LONG = MysqlAdapter::BLOB_LONG;

    const INT_TINY = MysqlAdapter::INT_TINY;
    const INT_SMALL = MysqlAdapter::INT_SMALL;
    const INT_MEDIUM = MysqlAdapter::INT_MEDIUM;
    const INT_REGULAR = MysqlAdapter::INT_REGULAR;
    const INT_BIG = MysqlAdapter::INT_BIG;

    const PHINX_TYPE_BLOB = MysqlAdapter::PHINX_TYPE_BLOB;

    const MYSQL_ENGINE = 'MyISAM';

    public function change()
    {
        $this->table('api_token', ['id' => 'apt_id'])
            ->addColumn('apt_usr_id', 'integer', ['length' => 10])
            ->addColumn('apt_created', 'datetime')
            ->addColumn('apt_status', 'string', ['length' => 10, 'default' => 'active', 'collation' => 'utf8_general_ci'])
            ->addColumn('apt_token', 'string', ['length' => 32, 'collation' => 'utf8_general_ci'])
            ->addIndex(['apt_usr_id', 'apt_status'])
            ->addIndex(['apt_token'])
        ->create();

        $this->table('columns_to_display', ['id' => false, 'primary_key' => ['ctd_prj_id', 'ctd_page', 'ctd_field']])
            ->addColumn('ctd_prj_id', 'integer')
            ->addColumn('ctd_page', 'string', ['length' => 20, 'collation' => 'utf8_general_ci'])
            ->addColumn('ctd_field', 'string', ['length' => 30, 'collation' => 'utf8_general_ci'])
            ->addColumn('ctd_min_role', 'boolean', ['default' => 0])
            ->addColumn('ctd_rank', 'integer', ['length' => 255, 'default' => 0])
            ->addIndex(['ctd_prj_id', 'ctd_page'])
        ->create();

        $this->table('commit', ['id' => 'com_id'])
            ->addColumn('com_scm_name', 'string', ['default' => 'default', 'collation' => 'utf8_general_ci'])
            ->addColumn('com_project_name', 'string', ['null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('com_changeset', 'string', ['length' => 40, 'collation' => 'utf8_general_ci'])
            ->addColumn('com_branch', 'string', ['null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('com_author_email', 'string', ['null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('com_author_name', 'string', ['null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('com_usr_id', 'integer', ['null' => true])
            ->addColumn('com_commit_date', 'datetime', ['default' => '0000-00-00 00:00:00'])
            ->addColumn('com_message', 'text', ['length' => self::INT_MEDIUM, 'null' => true, 'collation' => 'utf8_general_ci'])
        ->create();

        $this->table('commit_file', ['id' => 'cof_id'])
            ->addColumn('cof_com_id', 'integer', ['length' => 10])
            ->addColumn('cof_filename', 'string', ['default' => '', 'collation' => 'utf8_general_ci'])
            ->addColumn('cof_added', 'boolean', ['default' => 0])
            ->addColumn('cof_modified', 'boolean', ['default' => 0])
            ->addColumn('cof_removed', 'boolean', ['default' => 0])
            ->addColumn('cof_old_version', 'string', ['length' => 40, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('cof_new_version', 'string', ['length' => 40, 'null' => true, 'collation' => 'utf8_general_ci'])
        ->create();

        $this->table('custom_field', ['id' => 'fld_id'])
            ->addColumn('fld_title', 'string', ['length' => 32, 'default' => '', 'collation' => 'utf8_general_ci'])
            ->addColumn('fld_description', 'string', ['length' => 64, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('fld_type', 'string', ['length' => 8, 'default' => 'text', 'collation' => 'utf8_general_ci'])
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
            ->addColumn('fld_backend', 'string', ['length' => 100, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('fld_order_by', 'string', ['length' => 20, 'default' => 'cfo_id ASC', 'collation' => 'utf8_general_ci'])
        ->create();

        $this->table('custom_field_option', ['id' => 'cfo_id'])
            ->addColumn('cfo_fld_id', 'integer', ['length' => 10, 'default' => 0])
            ->addColumn('cfo_rank', 'integer', ['length' => 10, 'default' => 0])
            ->addColumn('cfo_value', 'string', ['length' => 128, 'default' => '', 'collation' => 'utf8_general_ci'])
            ->addIndex(['cfo_fld_id'], ['name' => 'icf_fld_id'])
        ->create();

        $this->table('custom_filter', ['id' => 'cst_id'])
            ->addColumn('cst_usr_id', 'integer', ['length' => 10, 'default' => 0])
            ->addColumn('cst_prj_id', 'integer', ['length' => 10, 'default' => 0])
            ->addColumn('cst_title', 'string', ['length' => 64, 'default' => '', 'collation' => 'utf8_general_ci'])
            ->addColumn('cst_iss_pri_id', 'integer', ['length' => 10, 'null' => true])
            ->addColumn('cst_iss_sev_id', 'integer', ['length' => 10, 'null' => true])
            ->addColumn('cst_keywords', 'string', ['length' => 64, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('cst_users', 'string', ['length' => 64, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('cst_reporter', 'integer', ['null' => true])
            ->addColumn('cst_iss_prc_id', 'integer', ['length' => 10, 'null' => true])
            ->addColumn('cst_iss_sta_id', 'integer', ['length' => 10, 'null' => true])
            ->addColumn('cst_iss_pre_id', 'integer', ['length' => 10, 'null' => true])
            ->addColumn('cst_pro_id', 'integer', ['null' => true])
            ->addColumn('cst_show_authorized', 'char', ['length' => 3, 'default' => '', 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('cst_show_notification_list', 'char', ['length' => 3, 'default' => '', 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('cst_created_date', 'date', ['null' => true])
            ->addColumn('cst_created_date_filter_type', 'string', ['length' => 7, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('cst_created_date_time_period', 'integer', ['length' => self::INT_SMALL, 'null' => true])
            ->addColumn('cst_created_date_end', 'date', ['null' => true])
            ->addColumn('cst_updated_date', 'date', ['null' => true])
            ->addColumn('cst_updated_date_filter_type', 'string', ['length' => 7, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('cst_updated_date_time_period', 'integer', ['length' => self::INT_SMALL, 'null' => true])
            ->addColumn('cst_updated_date_end', 'date', ['null' => true])
            ->addColumn('cst_last_response_date', 'date', ['null' => true])
            ->addColumn('cst_last_response_date_filter_type', 'string', ['length' => 7, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('cst_last_response_date_time_period', 'integer', ['length' => self::INT_SMALL, 'null' => true])
            ->addColumn('cst_last_response_date_end', 'date', ['null' => true])
            ->addColumn('cst_first_response_date', 'date', ['null' => true])
            ->addColumn('cst_first_response_date_filter_type', 'string', ['length' => 7, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('cst_first_response_date_time_period', 'integer', ['length' => self::INT_SMALL, 'null' => true])
            ->addColumn('cst_first_response_date_end', 'date', ['null' => true])
            ->addColumn('cst_closed_date', 'date', ['null' => true])
            ->addColumn('cst_closed_date_filter_type', 'string', ['length' => 7, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('cst_closed_date_time_period', 'integer', ['length' => self::INT_SMALL, 'null' => true])
            ->addColumn('cst_closed_date_end', 'date', ['null' => true])
            ->addColumn('cst_rows', 'char', ['length' => 3, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('cst_sort_by', 'string', ['length' => 32, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('cst_sort_order', 'string', ['length' => 4, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('cst_hide_closed', 'integer', ['length' => 1, 'null' => true])
            ->addColumn('cst_is_global', 'integer', ['length' => 1, 'default' => 0, 'null' => true])
            ->addColumn('cst_search_type', 'string', ['length' => 15, 'default' => 'customer', 'collation' => 'utf8_general_ci'])
            ->addColumn('cst_custom_field', 'text', ['null' => true, 'collation' => 'utf8_general_ci'])
            ->addIndex(['cst_usr_id', 'cst_prj_id'])
        ->create();

        $this->table('customer_account_manager', ['id' => 'cam_id'])
            ->addColumn('cam_prj_id', 'integer')
            ->addColumn('cam_customer_id', 'string', ['length' => 128, 'collation' => 'utf8_general_ci'])
            ->addColumn('cam_usr_id', 'integer')
            ->addColumn('cam_type', 'string', ['length' => 7, 'collation' => 'utf8_general_ci'])
            ->addIndex(['cam_prj_id', 'cam_customer_id', 'cam_usr_id'], ['name' => 'cam_manager', 'unique' => true])
            ->addIndex(['cam_customer_id'])
        ->create();

        $this->table('customer_note', ['id' => 'cno_id'])
            ->addColumn('cno_prj_id', 'integer')
            ->addColumn('cno_customer_id', 'string', ['length' => 128, 'collation' => 'utf8_general_ci'])
            ->addColumn('cno_created_date', 'datetime')
            ->addColumn('cno_updated_date', 'datetime', ['null' => true])
            ->addColumn('cno_note', 'text', ['null' => true, 'collation' => 'utf8_general_ci'])
            ->addIndex(['cno_prj_id', 'cno_customer_id'], ['unique' => true])
        ->create();

        $this->table('email_account', ['id' => 'ema_id'])
            ->addColumn('ema_prj_id', 'integer', ['length' => 10, 'default' => 0])
            ->addColumn('ema_type', 'string', ['length' => 32, 'default' => '', 'collation' => 'utf8_general_ci'])
            ->addColumn('ema_folder', 'string', ['null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('ema_hostname', 'string', ['default' => '', 'collation' => 'utf8_general_ci'])
            ->addColumn('ema_port', 'string', ['length' => 5, 'default' => '', 'collation' => 'utf8_general_ci'])
            ->addColumn('ema_username', 'string', ['length' => 64, 'default' => '', 'collation' => 'utf8_general_ci'])
            ->addColumn('ema_password', 'string', ['default' => '', 'collation' => 'utf8_general_ci'])
            ->addColumn('ema_get_only_new', 'integer', ['length' => 1, 'default' => 0])
            ->addColumn('ema_leave_copy', 'integer', ['length' => 1, 'default' => 0])
            ->addColumn('ema_issue_auto_creation', 'string', ['length' => 8, 'default' => 'disabled', 'collation' => 'utf8_general_ci'])
            ->addColumn('ema_issue_auto_creation_options', 'text', ['null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('ema_use_routing', 'boolean', ['default' => 0, 'null' => true])
// TODO https://github.com/robmorgan/phinx/issues/1080
//            ->addIndex(['ema_username', 'ema_hostname', 'ema_folder'], ['unique' => true, 'limit' => 100])
            ->addIndex(['ema_prj_id'])
        ->create();

        $this->table('email_draft', ['id' => 'emd_id'])
            ->addColumn('emd_usr_id', 'integer')
            ->addColumn('emd_iss_id', 'integer')
            ->addColumn('emd_sup_id', 'integer', ['null' => true])
            ->addColumn('emd_status', 'enum', ['default' => 'pending', 'values' => [0 => 'pending',  1 => 'edited',  2 => 'sent']])
            ->addColumn('emd_updated_date', 'datetime')
            ->addColumn('emd_subject', 'string', ['collation' => 'utf8_general_ci'])
            ->addColumn('emd_body', 'text', ['length' => self::INT_REGULAR, 'collation' => 'utf8_general_ci'])
            ->addColumn('emd_unknown_user', 'string', ['null' => true, 'collation' => 'utf8_general_ci'])
        ->create();

        $this->table('email_draft_recipient', ['id' => 'edr_id'])
            ->addColumn('edr_emd_id', 'integer')
            ->addColumn('edr_is_cc', 'boolean', ['default' => 0])
            ->addColumn('edr_email', 'string', ['collation' => 'utf8_general_ci'])
        ->create();

        $this->table('email_response', ['id' => 'ere_id'])
            ->addColumn('ere_title', 'string', ['length' => 64, 'collation' => 'utf8_general_ci'])
            ->addColumn('ere_response_body', 'text', ['collation' => 'utf8_general_ci'])
            ->addIndex(['ere_title'], ['unique' => true])
        ->create();

        $this->table('faq', ['id' => 'faq_id'])
            ->addColumn('faq_prj_id', 'integer')
            ->addColumn('faq_usr_id', 'integer')
            ->addColumn('faq_created_date', 'datetime')
            ->addColumn('faq_updated_date', 'datetime', ['null' => true])
            ->addColumn('faq_title', 'string', ['collation' => 'utf8_general_ci'])
            ->addColumn('faq_message', 'text', ['length' => self::INT_REGULAR, 'collation' => 'utf8_general_ci'])
            ->addColumn('faq_rank', 'integer', ['length' => 255])
            ->addIndex(['faq_title'], ['unique' => true])
        ->create();

        $this->table('faq_support_level', ['id' => false, 'primary_key' => ['fsl_faq_id', 'fsl_support_level_id']])
            ->addColumn('fsl_faq_id', 'integer')
            ->addColumn('fsl_support_level_id', 'string', ['length' => 50, 'collation' => 'utf8_general_ci'])
        ->create();

        $this->table('group', ['id' => 'grp_id'])
            ->addColumn('grp_name', 'string', ['length' => 100, 'collation' => 'utf8_general_ci'])
            ->addColumn('grp_description', 'string', ['null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('grp_manager_usr_id', 'integer')
            ->addIndex(['grp_name'], ['unique' => true])
        ->create();

        $this->table('history_type', ['id' => 'htt_id'])
            ->addColumn('htt_name', 'string', ['length' => 25, 'collation' => 'utf8_general_ci'])
            ->addColumn('htt_role', 'boolean', ['default' => 0, 'null' => true])
            ->addIndex(['htt_name'], ['unique' => true])
        ->create();

        $this->table('irc_notice', ['id' => 'ino_id'])
            ->addColumn('ino_prj_id', 'integer')
            ->addColumn('ino_iss_id', 'integer')
            ->addColumn('ino_created_date', 'datetime')
            ->addColumn('ino_message', 'string', ['collation' => 'utf8_general_ci'])
            ->addColumn('ino_status', 'string', ['length' => 8, 'default' => 'pending', 'collation' => 'utf8_general_ci'])
            ->addColumn('ino_target_usr_id', 'integer', ['null' => true])
            ->addColumn('ino_category', 'string', ['length' => 25, 'null' => true, 'collation' => 'utf8_general_ci'])
        ->create();

        $this->table('issue', ['id' => 'iss_id'])
            ->addColumn('iss_customer_id', 'string', ['length' => 128, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('iss_customer_contact_id', 'string', ['length' => 128, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('iss_customer_contract_id', 'string', ['length' => 50, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('iss_usr_id', 'integer', ['length' => 10, 'default' => 0])
            ->addColumn('iss_grp_id', 'integer', ['null' => true])
            ->addColumn('iss_prj_id', 'integer', ['default' => 0])
            ->addColumn('iss_prc_id', 'integer', ['default' => 0])
            ->addColumn('iss_pre_id', 'integer', ['length' => 10, 'default' => 0])
            ->addColumn('iss_pri_id', 'integer', ['length' => self::INT_SMALL, 'default' => 0])
            ->addColumn('iss_sev_id', 'integer', ['default' => 0])
            ->addColumn('iss_sta_id', 'boolean', ['default' => 0])
            ->addColumn('iss_res_id', 'integer', ['length' => 10, 'null' => true])
            ->addColumn('iss_duplicated_iss_id', 'integer', ['null' => true])
            ->addColumn('iss_created_date', 'datetime', ['default' => '0000-00-00 00:00:00'])
            ->addColumn('iss_updated_date', 'datetime', ['null' => true])
            ->addColumn('iss_last_response_date', 'datetime', ['null' => true])
            ->addColumn('iss_first_response_date', 'datetime', ['null' => true])
            ->addColumn('iss_closed_date', 'datetime', ['null' => true])
            ->addColumn('iss_last_customer_action_date', 'datetime', ['null' => true])
            ->addColumn('iss_expected_resolution_date', 'date', ['null' => true])
            ->addColumn('iss_summary', 'string', ['length' => 128, 'default' => '', 'collation' => 'utf8_general_ci'])
            ->addColumn('iss_description', 'text', ['collation' => 'utf8_general_ci'])
            ->addColumn('iss_dev_time', 'float', ['null' => true])
            ->addColumn('iss_developer_est_time', 'float', ['null' => true])
            ->addColumn('iss_contact_person_lname', 'string', ['length' => 64, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('iss_contact_person_fname', 'string', ['length' => 64, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('iss_contact_email', 'string', ['null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('iss_contact_phone', 'string', ['length' => 32, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('iss_contact_timezone', 'string', ['length' => 64, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('iss_trigger_reminders', 'boolean', ['default' => 1, 'null' => true])
            ->addColumn('iss_last_public_action_date', 'datetime', ['null' => true])
            ->addColumn('iss_last_public_action_type', 'string', ['length' => 20, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('iss_last_internal_action_date', 'datetime', ['null' => true])
            ->addColumn('iss_last_internal_action_type', 'string', ['length' => 20, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('iss_percent_complete', 'integer', ['length' => 255, 'default' => 0, 'null' => true])
            ->addColumn('iss_root_message_id', 'string', ['null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('iss_access_level', 'string', ['length' => 150, 'default' => 'normal', 'collation' => 'utf8_general_ci'])
            ->addIndex(['iss_prj_id'])
            ->addIndex(['iss_prc_id'])
            ->addIndex(['iss_res_id'])
            ->addIndex(['iss_grp_id'])
            ->addIndex(['iss_duplicated_iss_id'])
            ->addIndex(['iss_summary', 'iss_description'], ['type' => 'fulltext', 'name' => 'ft_issue'])
        ->create();

        $this->table('issue_access_list', ['id' => 'ial_id'])
            ->addColumn('ial_iss_id', 'integer', ['length' => 10])
            ->addColumn('ial_usr_id', 'integer', ['length' => 10])
            ->addColumn('ial_created', 'datetime')
            ->addIndex(['ial_iss_id', 'ial_usr_id'])
        ->create();

        $this->table('issue_access_log', ['id' => 'alg_id'])
            ->addColumn('alg_iss_id', 'integer', ['length' => 10])
            ->addColumn('alg_usr_id', 'integer', ['length' => 10])
            ->addColumn('alg_failed', 'boolean', ['default' => 0])
            ->addColumn('alg_item_id', 'integer', ['length' => 10, 'null' => true])
            ->addColumn('alg_created', 'datetime')
            ->addColumn('alg_ip_address', 'string', ['length' => 15, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('alg_item', 'string', ['length' => 10, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('alg_url', 'string', ['null' => true, 'collation' => 'utf8_general_ci'])
            ->addIndex(['alg_iss_id'])
        ->create();

        $this->table('issue_association', ['id' => false])
            ->addColumn('isa_issue_id', 'integer', ['length' => 10, 'default' => 0])
            ->addColumn('isa_associated_id', 'integer', ['length' => 10, 'default' => 0])
            ->addIndex(['isa_issue_id', 'isa_associated_id'])
        ->create();

        $this->table('issue_attachment', ['id' => 'iat_id'])
            ->addColumn('iat_iss_id', 'integer', ['length' => 10, 'default' => 0])
            ->addColumn('iat_usr_id', 'integer', ['length' => 10, 'default' => 0])
            ->addColumn('iat_created_date', 'datetime', ['default' => '0000-00-00 00:00:00'])
            ->addColumn('iat_description', 'text', ['null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('iat_unknown_user', 'string', ['null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('iat_status', 'enum', ['default' => 'public', 'values' => [0 => 'internal',  1 => 'public']])
            ->addColumn('iat_not_id', 'integer', ['null' => true])
            ->addIndex(['iat_iss_id', 'iat_usr_id'])
        ->create();

        $this->table('issue_attachment_file', ['id' => 'iaf_id'])
            ->addColumn('iaf_iat_id', 'integer', ['length' => 10, 'default' => 0])
            ->addColumn('iaf_file', self::PHINX_TYPE_BLOB, ['length' => self::BLOB_LONG, 'null' => true])
            ->addColumn('iaf_filename', 'string', ['default' => '', 'collation' => 'utf8_general_ci'])
            ->addColumn('iaf_filetype', 'string', ['null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('iaf_filesize', 'string', ['length' => 32, 'default' => '', 'collation' => 'utf8_general_ci'])
            ->addColumn('iaf_created_date', 'datetime', ['default' => '0000-00-00 00:00:00'])
            ->addIndex(['iaf_iat_id'])
        ->create();

        $this->table('issue_checkin', ['id' => 'isc_id'])
            ->addColumn('isc_iss_id', 'integer', ['length' => 10, 'default' => 0])
            ->addColumn('isc_commitid', 'string', ['length' => 40, 'null' => true, 'collation' => 'utf8_bin'])
            ->addColumn('isc_reponame', 'string', ['default' => '', 'collation' => 'utf8_general_ci'])
            ->addColumn('isc_module', 'string', ['default' => '', 'collation' => 'utf8_general_ci'])
            ->addColumn('isc_filename', 'string', ['default' => '', 'collation' => 'utf8_general_ci'])
            ->addColumn('isc_old_version', 'string', ['length' => 40, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('isc_new_version', 'string', ['length' => 40, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('isc_created_date', 'datetime', ['default' => '0000-00-00 00:00:00'])
            ->addColumn('isc_username', 'string', ['length' => 32, 'default' => '', 'collation' => 'utf8_general_ci'])
            ->addColumn('isc_commit_msg', 'text', ['null' => true, 'collation' => 'utf8_general_ci'])
            ->addIndex(['isc_iss_id'])
            ->addIndex(['isc_commitid'])
        ->create();

        $this->table('issue_commit', ['id' => 'isc_id'])
            ->addColumn('isc_iss_id', 'integer', ['length' => 10])
            ->addColumn('isc_com_id', 'integer', ['length' => 10])
        ->create();

        $this->table('issue_custom_field', ['id' => 'icf_id'])
            ->addColumn('icf_iss_id', 'integer', ['length' => 10, 'default' => 0])
            ->addColumn('icf_fld_id', 'integer', ['length' => 10, 'default' => 0])
            ->addColumn('icf_value', 'text', ['null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('icf_value_integer', 'integer', ['null' => true])
            ->addColumn('icf_value_date', 'date', ['null' => true])
            ->addIndex(['icf_iss_id'])
            ->addIndex(['icf_fld_id'])
            ->addIndex(['icf_value'], ['type' => 'fulltext', 'name' => 'ft_icf_value'])
        ->create();

        $this->table('issue_history', ['id' => 'his_id'])
            ->addColumn('his_iss_id', 'integer', ['length' => 10, 'default' => 0])
            ->addColumn('his_usr_id', 'integer', ['default' => 0])
            ->addColumn('his_htt_id', 'integer', ['length' => 255, 'default' => 0])
            ->addColumn('his_is_hidden', 'boolean', ['default' => 0])
            ->addColumn('his_created_date', 'datetime', ['default' => '0000-00-00 00:00:00'])
            ->addColumn('his_summary', 'text', ['collation' => 'utf8_general_ci'])
            ->addColumn('his_context', 'text', ['length' => self::INT_MEDIUM, 'collation' => 'utf8_general_ci'])
            ->addColumn('his_min_role', 'boolean', ['default' => 1])
            ->addIndex(['his_id'])
            ->addIndex(['his_iss_id'])
            ->addIndex(['his_created_date'])
        ->create();

        $this->table('issue_partner', ['id' => false, 'primary_key' => ['ipa_iss_id', 'ipa_par_code']])
            ->addColumn('ipa_iss_id', 'integer')
            ->addColumn('ipa_par_code', 'string', ['length' => 30, 'collation' => 'utf8_general_ci'])
            ->addColumn('ipa_created_date', 'datetime')
        ->create();

        $this->table('issue_product_version', ['id' => 'ipv_id'])
            ->addColumn('ipv_iss_id', 'integer')
            ->addColumn('ipv_pro_id', 'integer')
            ->addColumn('ipv_version', 'string', ['collation' => 'utf8_general_ci'])
            ->addIndex(['ipv_iss_id'])
        ->create();

        $this->table('issue_quarantine', ['id' => 'iqu_iss_id'])
            ->addColumn('iqu_expiration', 'datetime', ['null' => true])
            ->addColumn('iqu_status', 'boolean', ['null' => true])
            ->addIndex(['iqu_expiration'])
        ->create();

        $this->table('issue_user', ['id' => false, 'primary_key' => ['isu_iss_id', 'isu_usr_id']])
            ->addColumn('isu_iss_id', 'integer', ['length' => 10, 'default' => 0])
            ->addColumn('isu_usr_id', 'integer', ['length' => 10, 'default' => 0])
            ->addColumn('isu_assigned_date', 'datetime', ['null' => true])
            ->addColumn('isu_order', 'integer', ['default' => 0])
            ->addIndex(['isu_order'])
            ->addIndex(['isu_usr_id'])
            ->addIndex(['isu_iss_id'])
        ->create();

        $this->table('issue_user_replier', ['id' => 'iur_id'])
            ->addColumn('iur_iss_id', 'integer', ['length' => 10, 'default' => 0])
            ->addColumn('iur_usr_id', 'integer', ['length' => 10, 'default' => 0])
            ->addColumn('iur_email', 'string', ['null' => true, 'collation' => 'utf8_general_ci'])
            ->addIndex(['iur_usr_id'])
            ->addIndex(['iur_iss_id'])
        ->create();

        $this->table('link_filter', ['id' => 'lfi_id'])
            ->addColumn('lfi_pattern', 'string', ['collation' => 'utf8_general_ci'])
            ->addColumn('lfi_replacement', 'string', ['collation' => 'utf8_general_ci'])
            ->addColumn('lfi_usr_role', 'integer', ['length' => 255, 'default' => 0])
            ->addColumn('lfi_description', 'string', ['null' => true, 'collation' => 'utf8_general_ci'])
        ->create();

        $this->table('mail_queue', ['id' => 'maq_id'])
            ->addColumn('maq_iss_id', 'integer', ['null' => true])
            ->addColumn('maq_queued_date', 'datetime')
            ->addColumn('maq_status', 'string', ['length' => 8, 'default' => 'pending', 'collation' => 'utf8_general_ci'])
            ->addColumn('maq_save_copy', 'boolean', ['default' => 1])
            ->addColumn('maq_sender_ip_address', 'string', ['length' => 15, 'collation' => 'utf8_general_ci'])
            ->addColumn('maq_recipient', 'string', ['collation' => 'utf8_general_ci'])
            ->addColumn('maq_subject', 'string', ['collation' => 'utf8_general_ci'])
            ->addColumn('maq_message_id', 'string', ['null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('maq_headers', 'text', ['collation' => 'utf8_general_ci'])
            ->addColumn('maq_body', self::PHINX_TYPE_BLOB, ['length' => self::BLOB_LONG])
            ->addColumn('maq_type', 'string', ['length' => 30, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('maq_usr_id', 'integer', ['null' => true])
            ->addColumn('maq_type_id', 'integer', ['null' => true])
            ->addIndex(['maq_status'])
            ->addIndex(['maq_iss_id'])
            ->addIndex(['maq_type', 'maq_type_id'])
        ->create();

        $this->table('mail_queue_log', ['id' => 'mql_id'])
            ->addColumn('mql_maq_id', 'integer')
            ->addColumn('mql_created_date', 'datetime')
            ->addColumn('mql_status', 'string', ['length' => 8, 'default' => 'error', 'collation' => 'utf8_general_ci'])
            ->addColumn('mql_server_message', 'text', ['null' => true, 'collation' => 'utf8_general_ci'])
            ->addIndex(['mql_maq_id'])
        ->create();

        $this->table('news', ['id' => 'nws_id'])
            ->addColumn('nws_usr_id', 'integer')
            ->addColumn('nws_created_date', 'datetime')
            ->addColumn('nws_title', 'string', ['collation' => 'utf8_general_ci'])
            ->addColumn('nws_message', 'text', ['collation' => 'utf8_general_ci'])
            ->addColumn('nws_status', 'string', ['length' => 8, 'default' => 'active', 'collation' => 'utf8_general_ci'])
            ->addIndex(['nws_title'], ['unique' => true])
        ->create();

        $this->table('note', ['id' => 'not_id'])
            ->addColumn('not_iss_id', 'integer', ['default' => 0])
            ->addColumn('not_created_date', 'datetime', ['default' => '0000-00-00 00:00:00'])
            ->addColumn('not_usr_id', 'integer', ['default' => 0])
            ->addColumn('not_title', 'string', ['collation' => 'utf8_general_ci'])
            ->addColumn('not_note', 'text', ['length' => self::INT_REGULAR, 'collation' => 'utf8_general_ci'])
            ->addColumn('not_full_message', self::PHINX_TYPE_BLOB, ['length' => self::BLOB_LONG, 'null' => true])
            ->addColumn('not_parent_id', 'integer', ['null' => true])
            ->addColumn('not_unknown_user', 'string', ['null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('not_has_attachment', 'boolean', ['default' => 0])
            ->addColumn('not_message_id', 'string', ['null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('not_removed', 'boolean', ['default' => 0])
            ->addColumn('not_is_blocked', 'boolean', ['default' => 0])
            ->addIndex(['not_iss_id', 'not_usr_id'], ['name' => 'not_bug_id'])
            ->addIndex(['not_message_id'])
            ->addIndex(['not_parent_id'])
            ->addIndex(['not_title', 'not_note'], ['type' => 'fulltext', 'name' => 'ft_note'])
        ->create();

        $this->table('partner_project', ['id' => false, 'primary_key' => ['pap_prj_id', 'pap_par_code']])
            ->addColumn('pap_prj_id', 'integer')
            ->addColumn('pap_par_code', 'string', ['length' => 30, 'collation' => 'utf8_general_ci'])
        ->create();

        $this->table('phone_support', ['id' => 'phs_id'])
            ->addColumn('phs_usr_id', 'integer', ['length' => 10, 'default' => 0])
            ->addColumn('phs_iss_id', 'integer', ['length' => 10, 'default' => 0])
            ->addColumn('phs_ttr_id', 'integer', ['length' => 10, 'null' => true])
            ->addColumn('phs_call_from_lname', 'string', ['length' => 64, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('phs_call_from_fname', 'string', ['length' => 64, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('phs_call_to_lname', 'string', ['length' => 64, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('phs_call_to_fname', 'string', ['length' => 64, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('phs_created_date', 'datetime', ['default' => '0000-00-00 00:00:00'])
            ->addColumn('phs_type', 'enum', ['default' => 'incoming', 'values' => [0 => 'incoming',  1 => 'outgoing']])
            ->addColumn('phs_phone_number', 'string', ['length' => 32, 'default' => '', 'collation' => 'utf8_general_ci'])
            ->addColumn('phs_phone_type', 'string', ['length' => 6, 'collation' => 'utf8_general_ci'])
            ->addColumn('phs_phc_id', 'integer')
            ->addColumn('phs_description', 'text', ['collation' => 'utf8_general_ci'])
            ->addIndex(['phs_iss_id'])
            ->addIndex(['phs_usr_id'])
            ->addIndex(['phs_description'], ['type' => 'fulltext', 'name' => 'ft_phone_support'])
        ->create();

        $this->table('product', ['id' => 'pro_id'])
            ->addColumn('pro_title', 'string', ['collation' => 'utf8_general_ci'])
            ->addColumn('pro_version_howto', 'string', ['collation' => 'utf8_general_ci'])
            ->addColumn('pro_rank', 'integer', ['length' => self::INT_MEDIUM, 'default' => 0])
            ->addColumn('pro_removed', 'boolean', ['default' => 0])
            ->addColumn('pro_email', 'string', ['null' => true, 'collation' => 'utf8_general_ci'])
            ->addIndex(['pro_rank'])
        ->create();

        $this->table('project', ['id' => 'prj_id'])
            ->addColumn('prj_created_date', 'datetime', ['default' => '0000-00-00 00:00:00'])
            ->addColumn('prj_title', 'string', ['length' => 64, 'default' => '', 'collation' => 'utf8_general_ci'])
            ->addColumn('prj_status', 'set', ['default' => 'active', 'values' => ['active', 'archived']])
            ->addColumn('prj_lead_usr_id', 'integer', ['default' => 0])
            ->addColumn('prj_initial_sta_id', 'integer', ['length' => 10, 'default' => 0])
            ->addColumn('prj_remote_invocation', 'string', ['length' => 8, 'default' => 'disabled', 'collation' => 'utf8_general_ci'])
            ->addColumn('prj_anonymous_post', 'string', ['length' => 8, 'default' => 'disabled', 'collation' => 'utf8_general_ci'])
            ->addColumn('prj_anonymous_post_options', 'text', ['null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('prj_outgoing_sender_name', 'string', ['collation' => 'utf8_general_ci'])
            ->addColumn('prj_outgoing_sender_email', 'string', ['collation' => 'utf8_general_ci'])
            ->addColumn('prj_sender_flag', 'string', ['null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('prj_sender_flag_location', 'string', ['length' => 6, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('prj_mail_aliases', 'string', ['null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('prj_customer_backend', 'string', ['length' => 64, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('prj_workflow_backend', 'string', ['length' => 64, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('prj_segregate_reporter', 'boolean', ['default' => 0, 'null' => true])
            ->addIndex(['prj_title'], ['unique' => true])
            ->addIndex(['prj_lead_usr_id'])
        ->create();

        $this->table('project_category', ['id' => 'prc_id'])
            ->addColumn('prc_prj_id', 'integer', ['default' => 0])
            ->addColumn('prc_title', 'string', ['length' => 64, 'default' => '', 'collation' => 'utf8_general_ci'])
            ->addIndex(['prc_prj_id', 'prc_title'], ['unique' => true, 'name' => 'uniq_category'])
            ->addIndex(['prc_prj_id'])
        ->create();

        $this->table('project_custom_field', ['id' => 'pcf_id'])
            ->addColumn('pcf_prj_id', 'integer', ['length' => 10, 'default' => 0])
            ->addColumn('pcf_fld_id', 'integer', ['length' => 10, 'default' => 0])
            ->addIndex(['pcf_prj_id'])
            ->addIndex(['pcf_fld_id'])
        ->create();

        $this->table('project_email_response', ['id' => false, 'primary_key' => ['per_prj_id', 'per_ere_id']])
            ->addColumn('per_prj_id', 'integer')
            ->addColumn('per_ere_id', 'integer', ['length' => 10])
        ->create();

        $this->table('project_field_display', ['id' => false, 'primary_key' => ['pfd_prj_id', 'pfd_field']])
            ->addColumn('pfd_prj_id', 'integer')
            ->addColumn('pfd_field', 'string', ['length' => 20, 'collation' => 'utf8_general_ci'])
            ->addColumn('pfd_min_role', 'boolean', ['default' => 0])
            ->addColumn('pfd_required', 'boolean', ['default' => 0])
        ->create();

        $this->table('project_group', ['id' => false, 'primary_key' => ['pgr_prj_id', 'pgr_grp_id']])
            ->addColumn('pgr_prj_id', 'integer')
            ->addColumn('pgr_grp_id', 'integer')
        ->create();

        $this->table('project_link_filter', ['id' => false, 'primary_key' => ['plf_prj_id', 'plf_lfi_id']])
            ->addColumn('plf_prj_id', 'integer')
            ->addColumn('plf_lfi_id', 'integer')
        ->create();

        $this->table('project_news', ['id' => false, 'primary_key' => ['prn_nws_id', 'prn_prj_id']])
            ->addColumn('prn_nws_id', 'integer')
            ->addColumn('prn_prj_id', 'integer')
        ->create();

        $this->table('project_phone_category', ['id' => 'phc_id'])
            ->addColumn('phc_prj_id', 'integer', ['default' => 0])
            ->addColumn('phc_title', 'string', ['length' => 64, 'default' => '', 'collation' => 'utf8_general_ci'])
            ->addIndex(['phc_prj_id', 'phc_title'], ['unique' => true, 'name' => 'uniq_category'])
            ->addIndex(['phc_prj_id'])
        ->create();

        $this->table('project_priority', ['id' => 'pri_id'])
            ->addColumn('pri_prj_id', 'integer')
            ->addColumn('pri_title', 'string', ['length' => 64, 'default' => '', 'collation' => 'utf8_general_ci'])
            ->addColumn('pri_rank', 'boolean')
            ->addColumn('pri_icon', 'integer', ['length' => 255, 'default' => 0])
            ->addIndex(['pri_title', 'pri_prj_id'], ['unique' => true])
        ->create();

        $this->table('project_release', ['id' => 'pre_id'])
            ->addColumn('pre_prj_id', 'integer', ['length' => 10, 'default' => 0])
            ->addColumn('pre_title', 'string', ['length' => 128, 'default' => '', 'collation' => 'utf8_general_ci'])
            ->addColumn('pre_scheduled_date', 'date', ['default' => '0000-00-00'])
            ->addColumn('pre_status', 'enum', ['default' => 'available', 'values' => [0 => 'available',  1 => 'unavailable']])
            ->addIndex(['pre_prj_id', 'pre_title'], ['unique' => true, 'name' => 'pre_title'])
        ->create();

        $this->table('project_round_robin', ['id' => 'prr_id'])
            ->addColumn('prr_prj_id', 'integer')
            ->addColumn('prr_blackout_start', 'time')
            ->addColumn('prr_blackout_end', 'time')
            ->addIndex(['prr_prj_id'], ['unique' => true])
        ->create();

        $this->table('project_severity', ['id' => 'sev_id'])
            ->addColumn('sev_prj_id', 'integer')
            ->addColumn('sev_title', 'string', ['length' => 64, 'default' => '', 'collation' => 'utf8_general_ci'])
            ->addColumn('sev_description', 'string', ['null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('sev_rank', 'boolean')
            ->addIndex(['sev_title', 'sev_prj_id'], ['unique' => true, 'name' => 'sev_title'])
        ->create();

        $this->table('project_status', ['id' => 'prs_id'])
            ->addColumn('prs_prj_id', 'integer', ['length' => 10])
            ->addColumn('prs_sta_id', 'integer', ['length' => 10])
            ->addIndex(['prs_prj_id', 'prs_sta_id'])
        ->create();

        $this->table('project_status_date', ['id' => 'psd_id'])
            ->addColumn('psd_prj_id', 'integer')
            ->addColumn('psd_sta_id', 'integer', ['length' => 10])
            ->addColumn('psd_date_field', 'string', ['length' => 64, 'collation' => 'utf8_general_ci'])
            ->addColumn('psd_label', 'string', ['length' => 32, 'collation' => 'utf8_general_ci'])
            ->addIndex(['psd_prj_id', 'psd_sta_id'], ['unique' => true])
        ->create();

        $this->table('project_user', ['id' => 'pru_id'])
            ->addColumn('pru_prj_id', 'integer', ['default' => 0])
            ->addColumn('pru_usr_id', 'integer', ['default' => 0])
            ->addColumn('pru_role', 'boolean', ['default' => 1])
            ->addIndex(['pru_prj_id', 'pru_usr_id'], ['unique' => true])
        ->create();

        $this->table('reminder_action', ['id' => 'rma_id'])
            ->addColumn('rma_rem_id', 'integer')
            ->addColumn('rma_rmt_id', 'integer', ['length' => 255])
            ->addColumn('rma_created_date', 'datetime')
            ->addColumn('rma_last_updated_date', 'datetime', ['null' => true])
            ->addColumn('rma_title', 'string', ['length' => 64, 'collation' => 'utf8_general_ci'])
            ->addColumn('rma_rank', 'integer', ['length' => 255])
            ->addColumn('rma_alert_irc', 'boolean', ['default' => 0])
            ->addColumn('rma_alert_group_leader', 'boolean', ['default' => 0])
            ->addColumn('rma_boilerplate', 'string', ['null' => true, 'collation' => 'utf8_general_ci'])
        ->create();

        $this->table('reminder_action_list', ['id' => false])
            ->addColumn('ral_rma_id', 'integer')
            ->addColumn('ral_email', 'string', ['collation' => 'utf8_general_ci'])
            ->addColumn('ral_usr_id', 'integer')
        ->create();

        $this->table('reminder_action_type', ['id' => 'rmt_id'])
            ->addColumn('rmt_type', 'string', ['length' => 32, 'collation' => 'utf8_general_ci'])
            ->addColumn('rmt_title', 'string', ['length' => 64, 'collation' => 'utf8_general_ci'])
            ->addIndex(['rmt_type'], ['unique' => true])
            ->addIndex(['rmt_title'], ['unique' => true])
        ->create();

        $this->table('reminder_field', ['id' => 'rmf_id'])
            ->addColumn('rmf_title', 'string', ['length' => 128, 'collation' => 'utf8_general_ci'])
            ->addColumn('rmf_sql_field', 'string', ['length' => 32, 'collation' => 'utf8_general_ci'])
            ->addColumn('rmf_sql_representation', 'string', ['collation' => 'utf8_general_ci'])
            ->addColumn('rmf_allow_column_compare', 'boolean', ['default' => 0, 'null' => true])
            ->addIndex(['rmf_title'], ['unique' => true])
        ->create();

        $this->table('reminder_history', ['id' => 'rmh_id'])
            ->addColumn('rmh_iss_id', 'integer')
            ->addColumn('rmh_rma_id', 'integer')
            ->addColumn('rmh_created_date', 'datetime')
        ->create();

        $this->table('reminder_level', ['id' => 'rem_id'])
            ->addColumn('rem_created_date', 'datetime')
            ->addColumn('rem_rank', 'boolean')
            ->addColumn('rem_last_updated_date', 'datetime', ['null' => true])
            ->addColumn('rem_title', 'string', ['length' => 64, 'collation' => 'utf8_general_ci'])
            ->addColumn('rem_prj_id', 'integer')
            ->addColumn('rem_skip_weekend', 'boolean', ['default' => 0])
        ->create();

        $this->table('reminder_level_condition', ['id' => 'rlc_id'])
            ->addColumn('rlc_rma_id', 'integer')
            ->addColumn('rlc_rmf_id', 'integer', ['length' => 255])
            ->addColumn('rlc_rmo_id', 'boolean')
            ->addColumn('rlc_created_date', 'datetime')
            ->addColumn('rlc_last_updated_date', 'datetime', ['null' => true])
            ->addColumn('rlc_value', 'string', ['length' => 64, 'collation' => 'utf8_general_ci'])
            ->addColumn('rlc_comparison_rmf_id', 'integer', ['length' => 255, 'null' => true])
        ->create();

        $this->table('reminder_operator', ['id' => 'rmo_id'])
            ->addColumn('rmo_title', 'string', ['length' => 32, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('rmo_sql_representation', 'string', ['length' => 32, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addIndex(['rmo_title'], ['unique' => true])
        ->create();

        $this->table('reminder_priority', ['id' => 'rep_id'])
            ->addColumn('rep_rem_id', 'integer')
            ->addColumn('rep_pri_id', 'integer')
        ->create();

        $this->table('reminder_product', ['id' => 'rpr_id'])
            ->addColumn('rpr_rem_id', 'integer')
            ->addColumn('rpr_pro_id', 'integer')
        ->create();

        $this->table('reminder_requirement', ['id' => 'rer_id'])
            ->addColumn('rer_rem_id', 'integer')
            ->addColumn('rer_iss_id', 'integer', ['null' => true])
            ->addColumn('rer_support_level_id', 'integer', ['null' => true])
            ->addColumn('rer_customer_id', 'string', ['length' => 128, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('rer_trigger_all_issues', 'boolean', ['default' => 0])
        ->create();

        $this->table('reminder_severity', ['id' => 'rms_id'])
            ->addColumn('rms_rem_id', 'integer')
            ->addColumn('rms_sev_id', 'integer')
        ->create();

        $this->table('reminder_triggered_action', ['id' => false, 'primary_key' => ['rta_iss_id']])
            ->addColumn('rta_iss_id', 'integer')
            ->addColumn('rta_rma_id', 'integer')
        ->create();

        $this->table('resolution', ['id' => 'res_id'])
            ->addColumn('res_title', 'string', ['length' => 64, 'default' => '', 'collation' => 'utf8_general_ci'])
            ->addColumn('res_created_date', 'datetime', ['default' => '0000-00-00 00:00:00'])
            ->addColumn('res_rank', 'integer', ['length' => 2])
            ->addIndex(['res_title'], ['unique' => true])
        ->create();

        $this->table('round_robin_user', ['id' => false])
            ->addColumn('rru_prr_id', 'integer')
            ->addColumn('rru_usr_id', 'integer')
            ->addColumn('rru_next', 'boolean', ['null' => true])
        ->create();

        $this->table('search_profile', ['id' => 'sep_id'])
            ->addColumn('sep_usr_id', 'integer')
            ->addColumn('sep_prj_id', 'integer')
            ->addColumn('sep_type', 'char', ['length' => 5, 'collation' => 'utf8_general_ci'])
            ->addColumn('sep_user_profile', self::PHINX_TYPE_BLOB)
            ->addIndex(['sep_usr_id', 'sep_prj_id', 'sep_type'], ['unique' => true])
        ->create();

        $this->table('status', ['id' => 'sta_id'])
            ->addColumn('sta_title', 'string', ['length' => 64, 'default' => '', 'collation' => 'utf8_general_ci'])
            ->addColumn('sta_abbreviation', 'char', ['length' => 3, 'collation' => 'utf8_general_ci'])
            ->addColumn('sta_rank', 'integer', ['length' => 2])
            ->addColumn('sta_color', 'string', ['length' => 7, 'default' => '', 'collation' => 'utf8_general_ci'])
            ->addColumn('sta_is_closed', 'boolean', ['default' => 0])
            ->addIndex(['sta_abbreviation'], ['unique' => true])
            ->addIndex(['sta_rank'])
            ->addIndex(['sta_is_closed'])
        ->create();

        $this->table('subscription', ['id' => 'sub_id'])
            ->addColumn('sub_iss_id', 'integer', ['length' => 10, 'default' => 0])
            ->addColumn('sub_usr_id', 'integer', ['length' => 10, 'null' => true])
            ->addColumn('sub_created_date', 'datetime', ['default' => '0000-00-00 00:00:00'])
            ->addColumn('sub_level', 'string', ['length' => 10, 'default' => 'user', 'collation' => 'utf8_general_ci'])
            ->addColumn('sub_email', 'string', ['null' => true, 'collation' => 'utf8_general_ci'])
            ->addIndex(['sub_iss_id', 'sub_usr_id'])
        ->create();

        $this->table('subscription_type', ['id' => 'sbt_id'])
            ->addColumn('sbt_sub_id', 'integer', ['length' => 10, 'default' => 0])
            ->addColumn('sbt_type', 'string', ['length' => 10, 'default' => '', 'collation' => 'utf8_general_ci'])
            ->addIndex(['sbt_sub_id'])
        ->create();

        $this->table('support_email', ['id' => 'sup_id'])
            ->addColumn('sup_ema_id', 'integer', ['length' => 10, 'default' => 0])
            ->addColumn('sup_parent_id', 'integer', ['default' => 0])
            ->addColumn('sup_iss_id', 'integer', ['default' => 0, 'null' => true])
            ->addColumn('sup_usr_id', 'integer', ['null' => true])
            ->addColumn('sup_customer_id', 'string', ['length' => 128, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('sup_message_id', 'string', ['default' => '', 'collation' => 'utf8_general_ci'])
            ->addColumn('sup_date', 'datetime', ['default' => '0000-00-00 00:00:00'])
            ->addColumn('sup_from', 'string', ['default' => '', 'collation' => 'utf8_general_ci'])
            ->addColumn('sup_to', 'text', ['null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('sup_cc', 'text', ['null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('sup_subject', 'string', ['default' => '', 'collation' => 'utf8_general_ci'])
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
            ->addColumn('seb_sup_id', 'integer')
            ->addColumn('seb_body', 'text', ['length' => self::INT_REGULAR, 'collation' => 'utf8_general_ci'])
            ->addColumn('seb_full_email', self::PHINX_TYPE_BLOB, ['length' => self::BLOB_LONG])
            ->addIndex(['seb_body'], ['type' => 'fulltext', 'name' => 'ft_support_email'])
        ->create();

        $this->table('time_tracking', ['id' => 'ttr_id'])
            ->addColumn('ttr_ttc_id', 'integer', ['length' => 10, 'default' => 0])
            ->addColumn('ttr_iss_id', 'integer', ['length' => 10, 'default' => 0])
            ->addColumn('ttr_usr_id', 'integer', ['length' => 10, 'default' => 0])
            ->addColumn('ttr_created_date', 'datetime', ['default' => '0000-00-00 00:00:00'])
            ->addColumn('ttr_time_spent', 'integer', ['default' => 0])
            ->addColumn('ttr_summary', 'string', ['default' => '', 'collation' => 'utf8_general_ci'])
            ->addIndex(['ttr_ttc_id', 'ttr_iss_id', 'ttr_usr_id'])
            ->addIndex(['ttr_iss_id'])
            ->addIndex(['ttr_summary'], ['type' => 'fulltext', 'name' => 'ft_time_tracking'])
        ->create();

        $this->table('time_tracking_category', ['id' => 'ttc_id'])
            ->addColumn('ttc_prj_id', 'integer', ['length' => 10, 'default' => 0])
            ->addColumn('ttc_title', 'string', ['length' => 128, 'default' => '', 'collation' => 'utf8_general_ci'])
            ->addColumn('ttc_created_date', 'datetime', ['default' => '0000-00-00 00:00:00'])
            ->addIndex(['ttc_prj_id', 'ttc_title'], ['name' => 'ttc_title'])
        ->create();

        $this->table('user', ['id' => 'usr_id'])
            ->addColumn('usr_customer_id', 'string', ['length' => 128, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('usr_customer_contact_id', 'string', ['length' => 128, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('usr_created_date', 'datetime', ['default' => '0000-00-00 00:00:00'])
            ->addColumn('usr_status', 'string', ['length' => 8, 'default' => 'active', 'collation' => 'utf8_general_ci'])
            ->addColumn('usr_password', 'string', ['default' => '', 'collation' => 'utf8_general_ci'])
            ->addColumn('usr_full_name', 'string', ['default' => '', 'collation' => 'utf8_general_ci'])
            ->addColumn('usr_email', 'string', ['default' => '', 'collation' => 'utf8_general_ci'])
            ->addColumn('usr_sms_email', 'string', ['null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('usr_clocked_in', 'boolean', ['default' => 0, 'null' => true])
            ->addColumn('usr_lang', 'string', ['length' => 5, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addColumn('usr_external_id', 'string', ['length' => 100, 'collation' => 'utf8_general_ci'])
            ->addColumn('usr_last_login', 'datetime', ['null' => true])
            ->addColumn('usr_last_failed_login', 'datetime', ['null' => true])
            ->addColumn('usr_failed_logins', 'integer', ['default' => 0])
            ->addColumn('usr_par_code', 'string', ['length' => 30, 'null' => true, 'collation' => 'utf8_general_ci'])
            ->addIndex(['usr_email'], ['unique' => true])
        ->create();

        // FIXME: upgrade/patches/02_usr_alias.sql has no engine=xxx, add patch
        $this->table('user_alias', ['id' => false])
            ->addColumn('ual_usr_id', 'integer')
            ->addColumn('ual_email', 'string', ['null' => true, 'collation' => 'latin1_swedish_ci'])
            ->addIndex(['ual_email'], ['unique' => true])
            ->addIndex(['ual_usr_id', 'ual_email'])
        ->create();

        // FIXME: upgrade/patches/45_multiple_groups.php has no ENGINE
        $this->table('user_group', ['id' => false, 'primary_key' => ['ugr_usr_id', 'ugr_grp_id']])
            ->addColumn('ugr_usr_id', 'integer', ['length' => 10])
            ->addColumn('ugr_grp_id', 'integer', ['length' => 10])
            ->addColumn('ugr_created', 'datetime')
        ->create();

        // FIXME: upgrade/patches/07_user_preference.php:24:
        $this->table('user_preference', ['id' => false, 'primary_key' => ['upr_usr_id']])
            ->addColumn('upr_usr_id', 'integer')
            ->addColumn('upr_timezone', 'string', ['length' => 100])
            ->addColumn('upr_week_firstday', 'boolean', ['default' => 0])
            ->addColumn('upr_list_refresh_rate', 'integer', ['length' => 5, 'default' => 5, 'null' => true])
            ->addColumn('upr_email_refresh_rate', 'integer', ['length' => 5, 'default' => 5, 'null' => true])
            ->addColumn('upr_email_signature', 'text', ['length' => self::INT_REGULAR, 'null' => true, 'collation' => 'latin1_swedish_ci'])
            ->addColumn('upr_auto_append_email_sig', 'boolean', ['default' => 0, 'null' => true])
            ->addColumn('upr_auto_append_note_sig', 'boolean', ['default' => 0, 'null' => true])
            ->addColumn('upr_auto_close_popup_window', 'boolean', ['default' => 0, 'null' => true])
            ->addColumn('upr_relative_date', 'boolean', ['default' => 1, 'null' => true])
            ->addColumn('upr_collapsed_emails', 'boolean', ['default' => 1, 'null' => true])
        ->create();

        $this->table('user_project_preference', ['id' => false, 'primary_key' => ['upp_usr_id', 'upp_prj_id']])
            ->addColumn('upp_usr_id', 'integer')
            ->addColumn('upp_prj_id', 'integer')
            ->addColumn('upp_receive_assigned_email', 'boolean', ['default' => 1, 'null' => true])
            ->addColumn('upp_receive_new_issue_email', 'boolean', ['default' => 0, 'null' => true])
            ->addColumn('upp_receive_copy_of_own_action', 'boolean', ['default' => 0, 'null' => true])
        ->create();
    }

    /**
     * {@inheritdoc}
     */
    public function table($tableName, $options = [])
    {
        $options['engine'] = self::MYSQL_ENGINE;

        return parent::table($tableName, $options);
    }
}
