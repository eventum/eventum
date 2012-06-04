
DROP TABLE IF EXISTS `%TABLE_PREFIX%custom_filter`;
CREATE TABLE `%TABLE_PREFIX%custom_filter` (
  cst_id int(10) unsigned NOT NULL auto_increment,
  cst_usr_id int(10) unsigned NOT NULL default 0,
  cst_prj_id int(10) unsigned NOT NULL default 0,
  cst_title varchar(64) NOT NULL default '',
  cst_iss_pri_id int(10) unsigned default NULL,
  cst_iss_sev_id int(10) unsigned NULL,
  cst_keywords varchar(64) default NULL,
  cst_users varchar(64) default NULL,
  cst_reporter int(11) unsigned default NULL,
  cst_iss_prc_id int(10) unsigned default NULL,
  cst_iss_sta_id int(10) unsigned default NULL,
  cst_iss_pre_id int(10) unsigned default NULL,
  cst_show_authorized char(3) default '',
  cst_show_notification_list char(3) default '',
  cst_created_date date default NULL,
  cst_created_date_filter_type varchar(7) default NULL,
  cst_created_date_time_period smallint(4) default NULL,
  cst_created_date_end date default NULL,
  cst_updated_date date default NULL,
  cst_updated_date_filter_type varchar(7) default NULL,
  cst_updated_date_time_period smallint(4) default NULL,
  cst_updated_date_end date default NULL,
  cst_last_response_date date default NULL,
  cst_last_response_date_filter_type varchar(7) default NULL,
  cst_last_response_date_time_period smallint(4) default NULL,
  cst_last_response_date_end date default NULL,
  cst_first_response_date date default NULL,
  cst_first_response_date_filter_type varchar(7) default NULL,
  cst_first_response_date_time_period smallint(4) default NULL,
  cst_first_response_date_end date default NULL,
  cst_closed_date date default NULL,
  cst_closed_date_filter_type varchar(7) default NULL,
  cst_closed_date_time_period smallint(4) default NULL,
  cst_closed_date_end date default NULL,
  cst_rows char(3) default NULL,
  cst_sort_by varchar(32) default NULL,
  cst_sort_order varchar(4) default NULL,
  cst_hide_closed int(1) default NULL,
  cst_is_global int(1) default 0,
  cst_search_type varchar(15) not null default 'customer',
  cst_custom_field TEXT,
  PRIMARY KEY  (cst_id),
  KEY cst_usr_id (cst_usr_id,cst_prj_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%email_account`;
CREATE TABLE `%TABLE_PREFIX%email_account` (
  ema_id int(10) unsigned NOT NULL auto_increment,
  ema_prj_id int(10) unsigned NOT NULL default 0,
  ema_type varchar(32) NOT NULL default '',
  ema_folder varchar(255) default NULL,
  ema_hostname varchar(255) NOT NULL default '',
  ema_port varchar(5) NOT NULL default '',
  ema_username varchar(64) NOT NULL default '',
  ema_password varchar(64) NOT NULL default '',
  ema_get_only_new int(1) NOT NULL DEFAULT 0,
  ema_leave_copy int(1) NOT NULL DEFAULT 0,
  ema_issue_auto_creation varchar(8) NOT NULL DEFAULT 'disabled',
  ema_issue_auto_creation_options text,
  ema_use_routing tinyint(1) DEFAULT 0,
  PRIMARY KEY  (ema_id),
  KEY ema_prj_id (ema_prj_id),
  UNIQUE (ema_username, ema_hostname(100), ema_folder(100))
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%history_type`;
CREATE TABLE `%TABLE_PREFIX%history_type` (
  htt_id tinyint(2) unsigned NOT NULL auto_increment,
  htt_name varchar(25) NOT NULL,
  htt_role tinyint(1) DEFAULT 0,
  PRIMARY KEY(htt_id),
  UNIQUE (htt_name)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'attachment_removed';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'attachment_added';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'custom_field_updated';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'draft_added', htt_role = 4;
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'draft_updated', htt_role = 4;
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'impact_analysis_added';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'impact_analysis_updated';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'impact_analysis_removed';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'status_changed';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'remote_status_change';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'remote_assigned';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'remote_replier_added';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'details_updated';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'customer_details_updated';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'issue_opened';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'issue_auto_assigned';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'rr_issue_assigned';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'duplicate_update';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'duplicate_removed';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'duplicate_added';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'issue_opened_anon';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'remote_issue_created';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'issue_closed';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'issue_updated';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'user_associated';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'user_all_unassociated';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'replier_added';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'remote_note_added';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'note_added', htt_role = 4;
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'note_removed', htt_role = 4;
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'note_converted_draft', htt_role = 4;
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'note_converted_email', htt_role = 4;
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'notification_removed';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'notification_added';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'notification_updated';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'phone_entry_added', htt_role = 4;
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'phone_entry_removed', htt_role = 4;
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'scm_checkin_removed';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'email_associated';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'email_disassociated';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'email_sent';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'time_added', htt_role = 4;
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'time_removed', htt_role = 4;
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'remote_time_added', htt_role = 4;
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'email_blocked', htt_role = 4;
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'email_routed';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'note_routed', htt_role = 4;
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'replier_removed';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'replier_other_added';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'issue_associated';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'issue_all_unassociated';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'user_unassociated';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'issue_unassociated';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'group_changed', htt_role = 4;
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'status_auto_changed', htt_role = 4;
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'incident_redeemed',  htt_role = 4;
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'incident_unredeemed',  htt_role = 4;
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'scm_checkin_associated';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'issue_bulk_updated';
INSERT INTO `%TABLE_PREFIX%history_type` SET htt_name = 'draft_routed',  htt_role = 4;

DROP TABLE IF EXISTS `%TABLE_PREFIX%issue`;
CREATE TABLE `%TABLE_PREFIX%issue` (
  iss_id int(11) unsigned NOT NULL auto_increment,
  iss_customer_id varchar(128) NULL,
  iss_customer_contact_id varchar(128) NULL,
  iss_customer_contract_id varchar(50) NULL,
  iss_usr_id int(10) unsigned NOT NULL default 0,
  iss_grp_id int(11) unsigned NULL default NULL,
  iss_prj_id int(11) unsigned NOT NULL default 0,
  iss_prc_id int(11) unsigned NOT NULL default 0,
  iss_pre_id int(10) unsigned NOT NULL default 0,
  iss_pri_id smallint(3) NOT NULL default 0,
  iss_sev_id int(11) unsigned NOT NULL default 0,
  iss_sta_id tinyint(1) NOT NULL default 0,
  iss_res_id int(10) unsigned NULL default NULL,
  iss_duplicated_iss_id int(11) unsigned NULL default NULL,
  iss_created_date datetime NOT NULL default '0000-00-00 00:00:00',
  iss_updated_date datetime default NULL,
  iss_last_response_date datetime default NULL,
  iss_first_response_date datetime default NULL,
  iss_closed_date datetime default NULL,
  iss_last_customer_action_date datetime default NULL,
  iss_expected_resolution_date date default NULL,
  iss_summary varchar(128) NOT NULL default '',
  iss_description text NOT NULL,
  iss_dev_time float default NULL,
  iss_developer_est_time float default NULL,
  iss_impact_analysis text,
  iss_contact_person_lname varchar(64) default NULL,
  iss_contact_person_fname varchar(64) default NULL,
  iss_contact_email varchar(255) default NULL,
  iss_contact_phone varchar(32) default NULL,
  iss_contact_timezone varchar(64) default NULL,
  iss_trigger_reminders tinyint(1) default 1,
  iss_last_public_action_date datetime default NULL,
  iss_last_public_action_type varchar(20) default NULL,
  iss_last_internal_action_date datetime default NULL,
  iss_last_internal_action_type varchar(20) default NULL,
  iss_private tinyint(1) NOT NULL default 0,
  iss_percent_complete tinyint(3) unsigned default 0,
  iss_root_message_id varchar(255),
  PRIMARY KEY  (iss_id),
  KEY iss_prj_id (iss_prj_id),
  KEY iss_prc_id (iss_prc_id),
  KEY iss_res_id (iss_res_id),
  KEY iss_grp_id (iss_grp_id),
  KEY iss_duplicated_iss_id (iss_duplicated_iss_id),
  FULLTEXT ft_issue (iss_summary, iss_description)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%issue_association`;
CREATE TABLE `%TABLE_PREFIX%issue_association` (
  isa_issue_id int(10) unsigned NOT NULL default 0,
  isa_associated_id int(10) unsigned NOT NULL default 0,
  KEY isa_issue_id (isa_issue_id,isa_associated_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%issue_attachment`;
CREATE TABLE `%TABLE_PREFIX%issue_attachment` (
  iat_id int(10) unsigned NOT NULL auto_increment,
  iat_iss_id int(10) unsigned NOT NULL default 0,
  iat_usr_id int(10) unsigned NOT NULL default 0,
  iat_created_date datetime NOT NULL default '0000-00-00 00:00:00',
  iat_description text,
  iat_unknown_user varchar(255) NULL DEFAULT NULL,
  iat_status enum('internal', 'public') NOT NULL default 'public',
  iat_not_id int(11) unsigned DEFAULT NULL,
  PRIMARY KEY  (iat_id),
  KEY iat_iss_id (iat_iss_id,iat_usr_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%issue_attachment_file`;
CREATE TABLE `%TABLE_PREFIX%issue_attachment_file` (
  iaf_id int(10) unsigned NOT NULL auto_increment,
  iaf_iat_id int(10) unsigned NOT NULL default 0,
  iaf_file longblob NULL,
  iaf_filename varchar(255) NOT NULL default '',
  iaf_filetype varchar(255) NULL,
  iaf_filesize varchar(32) NOT NULL default '',
  PRIMARY KEY  (iaf_id),
  KEY iaf_iat_id (iaf_iat_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%issue_checkin`;
CREATE TABLE `%TABLE_PREFIX%issue_checkin` (
  isc_id int(10) unsigned NOT NULL auto_increment,
  isc_iss_id int(10) unsigned NOT NULL default 0,
  isc_module varchar(255) NOT NULL default '',
  isc_filename varchar(255) NOT NULL default '',
  isc_old_version varchar(32) NOT NULL default '',
  isc_new_version varchar(32) NOT NULL default '',
  isc_created_date datetime NOT NULL default '0000-00-00 00:00:00',
  isc_username varchar(32) NOT NULL default '',
  isc_commit_msg text,
  PRIMARY KEY  (isc_id),
  KEY isc_iss_id (isc_iss_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%issue_history`;
CREATE TABLE `%TABLE_PREFIX%issue_history` (
  his_id int(10) unsigned NOT NULL auto_increment,
  his_iss_id int(10) unsigned NOT NULL default 0,
  his_usr_id int(11) unsigned NOT NULL default 0,
  his_created_date datetime NOT NULL default '0000-00-00 00:00:00',
  his_summary text NOT NULL,
  his_htt_id tinyint(2) NOT NULL,
  his_is_hidden tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY  (his_id),
  KEY his_id (his_id),
  KEY his_iss_id (his_iss_id),
  KEY his_created_date (his_created_date)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%issue_requirement`;
CREATE TABLE `%TABLE_PREFIX%issue_requirement` (
  isr_id int(10) unsigned NOT NULL auto_increment,
  isr_iss_id int(10) unsigned NOT NULL default 0,
  isr_usr_id int(10) unsigned NOT NULL default 0,
  isr_updated_usr_id int(10) unsigned default NULL,
  isr_created_date datetime NOT NULL default '0000-00-00 00:00:00',
  isr_updated_date datetime default NULL,
  isr_requirement text NOT NULL,
  isr_dev_time float default NULL,
  isr_impact_analysis text,
  PRIMARY KEY  (isr_id),
  KEY isr_iss_id (isr_iss_id),
  KEY isr_usr_id (isr_usr_id),
  KEY isr_updated_usr_id (isr_updated_usr_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%issue_user`;
CREATE TABLE `%TABLE_PREFIX%issue_user` (
  isu_iss_id int(10) unsigned NOT NULL default 0,
  isu_usr_id int(10) unsigned NOT NULL default 0,
  isu_assigned_date datetime,
  isu_order int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY  (isu_iss_id,isu_usr_id),
  INDEX isu_order (isu_order),
  KEY isu_usr_id (isu_usr_id),
  KEY isu_iss_id (isu_iss_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%note`;
CREATE TABLE `%TABLE_PREFIX%note` (
  not_id int(11) unsigned NOT NULL auto_increment,
  not_iss_id int(11) unsigned NOT NULL default 0,
  not_created_date datetime NOT NULL default '0000-00-00 00:00:00',
  not_usr_id int(11) unsigned NOT NULL default 0,
  not_title varchar(255) NOT NULL,
  not_note longtext NOT NULL,
  not_full_message longtext NULL,
  not_parent_id int(11) unsigned NULL,
  not_unknown_user varchar(255) NULL DEFAULT NULL,
  not_has_attachment tinyint(1) NOT NULL default 0,
  not_message_id varchar(255) NULL,
  not_removed tinyint(1) NOT NULL DEFAULT 0,
  not_is_blocked tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY  (not_id),
  KEY not_bug_id (not_iss_id,not_usr_id),
  KEY not_message_id (not_message_id),
  KEY not_parent_id (not_parent_id),
  FULLTEXT ft_note (not_title,not_note)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%project_priority`;
CREATE TABLE `%TABLE_PREFIX%project_priority` (
  pri_id smallint(3) unsigned NOT NULL auto_increment,
  pri_prj_id int(11) unsigned NOT NULL,
  pri_title varchar(64) NOT NULL default '',
  pri_rank TINYINT(1) NOT NULL,
  PRIMARY KEY (pri_id),
  UNIQUE KEY pri_title (pri_title, pri_prj_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;
INSERT INTO `%TABLE_PREFIX%project_priority` (pri_id, pri_prj_id, pri_title, pri_rank) VALUES (1, 1, 'Critical', 1);
INSERT INTO `%TABLE_PREFIX%project_priority` (pri_id, pri_prj_id, pri_title, pri_rank) VALUES (2, 1, 'High', 2);
INSERT INTO `%TABLE_PREFIX%project_priority` (pri_id, pri_prj_id, pri_title, pri_rank) VALUES (3, 1, 'Medium', 3);
INSERT INTO `%TABLE_PREFIX%project_priority` (pri_id, pri_prj_id, pri_title, pri_rank) VALUES (4, 1, 'Low', 4);
INSERT INTO `%TABLE_PREFIX%project_priority` (pri_id, pri_prj_id, pri_title, pri_rank) VALUES (5, 1, 'Not Prioritized', 5);

DROP TABLE IF EXISTS `%TABLE_PREFIX%project`;
CREATE TABLE `%TABLE_PREFIX%project` (
  prj_id int(11) unsigned NOT NULL auto_increment,
  prj_created_date datetime NOT NULL default '0000-00-00 00:00:00',
  prj_title varchar(64) NOT NULL default '',
  prj_status set('active','archived') NOT NULL default 'active',
  prj_lead_usr_id int(11) unsigned NOT NULL default 0,
  prj_initial_sta_id int(10) unsigned NOT NULL default 0,
  prj_remote_invocation varchar(8) NOT NULL default 'disabled',
  prj_anonymous_post varchar(8) NOT NULL default 'disabled',
  prj_anonymous_post_options text,
  prj_outgoing_sender_name varchar(255) NOT NULL,
  prj_outgoing_sender_email varchar(255) NOT NULL,
  prj_mail_aliases varchar(255) NULL,
  prj_customer_backend varchar(64) NULL,
  prj_workflow_backend varchar(64) NULL,
  prj_segregate_reporter tinyint(1) DEFAULT 0,
  PRIMARY KEY  (prj_id),
  UNIQUE KEY prj_title (prj_title),
  KEY prj_lead_usr_id (prj_lead_usr_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;
INSERT INTO `%TABLE_PREFIX%project` (prj_id, prj_created_date, prj_title, prj_status, prj_lead_usr_id, prj_initial_sta_id, prj_remote_invocation, prj_anonymous_post, prj_anonymous_post_options, prj_outgoing_sender_name, prj_outgoing_sender_email) VALUES (1, NOW(), 'Default Project', 'active', 2, 1, '', '0', NULL, 'Default Project', 'default_project@example.com');

DROP TABLE IF EXISTS `%TABLE_PREFIX%project_field_display`;
CREATE TABLE `%TABLE_PREFIX%project_field_display` (
  pfd_prj_id int(11) unsigned NOT NULL,
  pfd_field varchar(20) NOT NULL,
  pfd_min_role tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (pfd_prj_id, pfd_field)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%project_category`;
CREATE TABLE `%TABLE_PREFIX%project_category` (
  prc_id int(11) unsigned NOT NULL auto_increment,
  prc_prj_id int(11) unsigned NOT NULL default 0,
  prc_title varchar(64) NOT NULL default '',
  PRIMARY KEY  (prc_id),
  UNIQUE KEY uniq_category (prc_prj_id,prc_title),
  KEY prc_prj_id (prc_prj_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;
INSERT INTO `%TABLE_PREFIX%project_category` (prc_id, prc_prj_id, prc_title) VALUES (1, 1, 'Bug');
INSERT INTO `%TABLE_PREFIX%project_category` (prc_id, prc_prj_id, prc_title) VALUES (2, 1, 'Feature Request');
INSERT INTO `%TABLE_PREFIX%project_category` (prc_id, prc_prj_id, prc_title) VALUES (3, 1, 'Technical Support');

DROP TABLE IF EXISTS `%TABLE_PREFIX%project_release`;
CREATE TABLE `%TABLE_PREFIX%project_release` (
  pre_id int(10) unsigned NOT NULL auto_increment,
  pre_prj_id int(10) unsigned NOT NULL default 0,
  pre_title varchar(128) NOT NULL default '',
  pre_scheduled_date date NOT NULL default '0000-00-00',
  pre_status enum('available','unavailable') NOT NULL default 'available',
  PRIMARY KEY  (pre_id),
  UNIQUE KEY pre_title (pre_prj_id, pre_title),
  KEY pre_prj_id (pre_prj_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;
INSERT INTO `%TABLE_PREFIX%project_release` (pre_id, pre_prj_id, pre_title, pre_scheduled_date, pre_status) VALUES (1, 1, 'Example Release', (CURDATE() + INTERVAL 1 MONTH), 'available');

DROP TABLE IF EXISTS `%TABLE_PREFIX%project_user`;
CREATE TABLE `%TABLE_PREFIX%project_user` (
  pru_id int(11) unsigned NOT NULL auto_increment,
  pru_prj_id int(11) unsigned NOT NULL default 0,
  pru_usr_id int(11) unsigned NOT NULL default 0,
  pru_role tinyint(1) unsigned NOT NULL default 1,
  PRIMARY KEY  (pru_id),
  UNIQUE KEY pru_prj_id (pru_prj_id,pru_usr_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;
INSERT INTO `%TABLE_PREFIX%project_user` (pru_id, pru_prj_id, pru_usr_id, pru_role) VALUES (1, 1, 2, 7);

DROP TABLE IF EXISTS `%TABLE_PREFIX%resolution`;
CREATE TABLE `%TABLE_PREFIX%resolution` (
  res_id int(10) unsigned NOT NULL auto_increment,
  res_title varchar(64) NOT NULL default '',
  res_created_date datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (res_id),
  UNIQUE KEY res_title (res_title)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;
INSERT INTO `%TABLE_PREFIX%resolution` (res_id, res_title, res_created_date) VALUES (2, 'fixed', NOW());
INSERT INTO `%TABLE_PREFIX%resolution` (res_id, res_title, res_created_date) VALUES (4, 'unable to reproduce', NOW());
INSERT INTO `%TABLE_PREFIX%resolution` (res_id, res_title, res_created_date) VALUES (5, 'not fixable', NOW());
INSERT INTO `%TABLE_PREFIX%resolution` (res_id, res_title, res_created_date) VALUES (6, 'duplicate', NOW());
INSERT INTO `%TABLE_PREFIX%resolution` (res_id, res_title, res_created_date) VALUES (7, 'not a bug', NOW());
INSERT INTO `%TABLE_PREFIX%resolution` (res_id, res_title, res_created_date) VALUES (8, 'suspended', NOW());
INSERT INTO `%TABLE_PREFIX%resolution` (res_id, res_title, res_created_date) VALUES (9, 'won\'t fix', NOW());


DROP TABLE IF EXISTS `%TABLE_PREFIX%project_severity`;
CREATE TABLE `%TABLE_PREFIX%project_severity` (
  sev_id smallint(3) unsigned NOT NULL auto_increment,
  sev_prj_id int(11) unsigned NOT NULL,
  sev_title varchar(64) NOT NULL default '',
  sev_description varchar(255) NULL,
  sev_rank TINYINT(1) NOT NULL,
  PRIMARY KEY (sev_id),
  UNIQUE KEY sev_title (sev_title, sev_prj_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%subscription`;
CREATE TABLE `%TABLE_PREFIX%subscription` (
  sub_id int(10) unsigned NOT NULL auto_increment,
  sub_iss_id int(10) unsigned NOT NULL default 0,
  sub_usr_id int(10) unsigned default NULL,
  sub_created_date datetime NOT NULL default '0000-00-00 00:00:00',
  sub_level varchar(10) NOT NULL default 'user',
  sub_email varchar(255) default NULL,
  PRIMARY KEY  (sub_id),
  KEY sub_iss_id (sub_iss_id,sub_usr_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%subscription_type`;
CREATE TABLE `%TABLE_PREFIX%subscription_type` (
  sbt_id int(10) unsigned NOT NULL auto_increment,
  sbt_sub_id int(10) unsigned NOT NULL default 0,
  sbt_type varchar(10) NOT NULL default '',
  PRIMARY KEY  (sbt_id),
  KEY sbt_sub_id (sbt_sub_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%support_email`;
CREATE TABLE `%TABLE_PREFIX%support_email` (
  sup_id int(11) unsigned NOT NULL auto_increment,
  sup_ema_id int(10) unsigned NOT NULL default 0,
  sup_parent_id int(11) unsigned NOT NULL default 0,
  sup_iss_id int(11) unsigned default 0,
  sup_usr_id int(11) unsigned default NULL,
  sup_customer_id varchar(128) NULL,
  sup_message_id varchar(255) NOT NULL default '',
  sup_date datetime NOT NULL default '0000-00-00 00:00:00',
  sup_from varchar(255) NOT NULL default '',
  sup_to text NOT NULL,
  sup_cc text default NULL,
  sup_subject varchar(255) NOT NULL default '',
  sup_has_attachment tinyint(1) NOT NULL default 0,
  sup_removed tinyint(1) NOT NULL default 0,
  PRIMARY KEY  (sup_id),
  KEY sup_parent_id (sup_parent_id),
  KEY sup_ema_id (sup_ema_id),
  KEY sup_removed (sup_removed),
  KEY (sup_removed, sup_ema_id, sup_iss_id),
  KEY (sup_removed, sup_ema_id, sup_date),
  KEY sup_usr_id(sup_usr_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%support_email_body`;
CREATE TABLE `%TABLE_PREFIX%support_email_body` (
  seb_sup_id int(11) unsigned NOT NULL,
  seb_body longtext NOT NULL,
  seb_full_email longblob NOT NULL,
  PRIMARY KEY (seb_sup_id),
  FULLTEXT ft_support_email (seb_body)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%time_tracking`;
CREATE TABLE `%TABLE_PREFIX%time_tracking` (
  ttr_id int(10) unsigned NOT NULL auto_increment,
  ttr_ttc_id int(10) unsigned NOT NULL default 0,
  ttr_iss_id int(10) unsigned NOT NULL default 0,
  ttr_usr_id int(10) unsigned NOT NULL default 0,
  ttr_created_date datetime NOT NULL default '0000-00-00 00:00:00',
  ttr_time_spent int(11) unsigned NOT NULL default 0,
  ttr_summary varchar(255) NOT NULL default '',
  PRIMARY KEY  (ttr_id),
  KEY ttr_ttc_id (ttr_ttc_id,ttr_iss_id,ttr_usr_id),
  KEY ttr_iss_id (ttr_iss_id),
  FULLTEXT ft_time_tracking (ttr_summary)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%time_tracking_category`;
CREATE TABLE `%TABLE_PREFIX%time_tracking_category` (
  ttc_id int(10) unsigned NOT NULL auto_increment,
  ttc_title varchar(128) NOT NULL default '',
  ttc_created_date datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (ttc_id),
  UNIQUE KEY ttc_title (ttc_title)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;
INSERT INTO `%TABLE_PREFIX%time_tracking_category` (ttc_id, ttc_title, ttc_created_date) VALUES (1, 'Development', NOW());
INSERT INTO `%TABLE_PREFIX%time_tracking_category` (ttc_id, ttc_title, ttc_created_date) VALUES (2, 'Design', NOW());
INSERT INTO `%TABLE_PREFIX%time_tracking_category` (ttc_id, ttc_title, ttc_created_date) VALUES (3, 'Planning', NOW());
INSERT INTO `%TABLE_PREFIX%time_tracking_category` (ttc_id, ttc_title, ttc_created_date) VALUES (4, 'Gathering Requirements', NOW());
INSERT INTO `%TABLE_PREFIX%time_tracking_category` (ttc_id, ttc_title, ttc_created_date) VALUES (5, 'Database Changes', NOW());
INSERT INTO `%TABLE_PREFIX%time_tracking_category` (ttc_id, ttc_title, ttc_created_date) VALUES (6, 'Tech-Support', NOW());
INSERT INTO `%TABLE_PREFIX%time_tracking_category` (ttc_id, ttc_title, ttc_created_date) VALUES (7, 'Release', NOW());
INSERT INTO `%TABLE_PREFIX%time_tracking_category` (ttc_id, ttc_title, ttc_created_date) VALUES (8, 'Telephone Discussion', NOW());
INSERT INTO `%TABLE_PREFIX%time_tracking_category` (ttc_id, ttc_title, ttc_created_date) VALUES (9, 'Email Discussion', NOW());
INSERT INTO `%TABLE_PREFIX%time_tracking_category` (ttc_id, ttc_title, ttc_created_date) VALUES (10, 'Note Discussion', NOW());

DROP TABLE IF EXISTS `%TABLE_PREFIX%user`;
CREATE TABLE `%TABLE_PREFIX%user` (
  usr_id int(11) unsigned NOT NULL auto_increment,
  usr_grp_id int(11) unsigned NULL default NULL,
  usr_customer_id varchar(128) NULL default NULL,
  usr_customer_contact_id varchar(128) NULL default NULL,
  usr_created_date datetime NOT NULL default '0000-00-00 00:00:00',
  usr_status varchar(8) NOT NULL default 'active',
  usr_password varchar(32) NOT NULL default '',
  usr_full_name varchar(255) NOT NULL default '',
  usr_email varchar(255) NOT NULL default '',
  usr_preferences longtext,
  usr_sms_email varchar(255) NULL,
  usr_clocked_in tinyint(1) DEFAULT 0,
  usr_lang varchar(5),
  PRIMARY KEY  (usr_id),
  UNIQUE KEY usr_email (usr_email),
  KEY usr_email_password (usr_email, usr_password),
  INDEX(usr_grp_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;
INSERT INTO `%TABLE_PREFIX%user` (usr_id, usr_created_date, usr_status, usr_password, usr_full_name, usr_email, usr_preferences) VALUES (1, NOW(), 'inactive', '14589714398751513457adf349173434', 'system', 'system-account@example.com', '');
INSERT INTO `%TABLE_PREFIX%user` (usr_id, usr_created_date, usr_password, usr_full_name, usr_email, usr_preferences) VALUES (2, NOW(), '21232f297a57a5a743894a0e4a801fc3', 'Admin User', 'admin@example.com', '');

DROP TABLE IF EXISTS `%TABLE_PREFIX%user_alias`;
CREATE TABLE `%TABLE_PREFIX%user_alias` (
    ual_usr_id int(11) unsigned not null,
    ual_email varchar(255),
    KEY(ual_usr_id, ual_email),
    UNIQUE(ual_email)
);

DROP TABLE IF EXISTS `%TABLE_PREFIX%custom_field`;
CREATE TABLE `%TABLE_PREFIX%custom_field` (
  fld_id int(10) unsigned NOT NULL auto_increment,
  fld_title varchar(32) NOT NULL default '',
  fld_description varchar(64) default NULL,
  fld_type varchar(8) NOT NULL default 'text',
  fld_report_form int(1) NOT NULL default 1,
  fld_report_form_required int(1) NOT NULL default 0,
  fld_anonymous_form int(1) NOT NULL default 1,
  fld_anonymous_form_required int(1) NOT NULL default 0,
  fld_close_form tinyint(1) NOT NULL DEFAULT 0,
  fld_close_form_required tinyint(1) NOT NULL DEFAULT 0,
  fld_list_display tinyint(1) NOT NULL default 0,
  fld_min_role tinyint(1) NOT NULL default 0,
  fld_rank smallint(2) NOT NULL default 0,
  fld_backend varchar(100),
  PRIMARY KEY  (fld_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%custom_field_option`;
CREATE TABLE `%TABLE_PREFIX%custom_field_option` (
  cfo_id int(10) unsigned NOT NULL auto_increment,
  cfo_fld_id int(10) unsigned NOT NULL default 0,
  cfo_value varchar(128) NOT NULL default '',
  PRIMARY KEY  (cfo_id),
  KEY icf_fld_id (cfo_fld_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%issue_custom_field`;
CREATE TABLE `%TABLE_PREFIX%issue_custom_field` (
  icf_id int(10) unsigned NOT NULL auto_increment,
  icf_iss_id int(10) unsigned NOT NULL default 0,
  icf_fld_id int(10) unsigned NOT NULL default 0,
  icf_value text default NULL,
  icf_value_integer int(11) default NULL,
  icf_value_date date default NULL,
  PRIMARY KEY  (icf_id),
  KEY icf_iss_id (icf_iss_id),
  KEY icf_fld_id (icf_fld_id),
  FULLTEXT KEY `ft_icf_value` (`icf_value`)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%project_custom_field`;
CREATE TABLE `%TABLE_PREFIX%project_custom_field` (
  pcf_id int(10) unsigned NOT NULL auto_increment,
  pcf_prj_id int(10) unsigned NOT NULL default 0,
  pcf_fld_id int(10) unsigned NOT NULL default 0,
  PRIMARY KEY  (pcf_id),
  KEY pcf_prj_id (pcf_prj_id),
  KEY pcf_fld_id (pcf_fld_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%email_response`;
CREATE TABLE `%TABLE_PREFIX%email_response` (
  ere_id int(10) unsigned NOT NULL auto_increment,
  ere_title varchar(64) NOT NULL,
  ere_response_body text NOT NULL,
  PRIMARY KEY  (ere_id),
  UNIQUE KEY ere_title (ere_title)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%phone_support`;
CREATE TABLE `%TABLE_PREFIX%phone_support` (
  phs_id int(10) unsigned NOT NULL auto_increment,
  phs_usr_id int(10) unsigned NOT NULL default 0,
  phs_iss_id int(10) unsigned NOT NULL default 0,
  phs_ttr_id int(10) unsigned NULL,
  phs_call_from_lname varchar(64) NULL,
  phs_call_from_fname varchar(64) NULL,
  phs_call_to_lname varchar(64) NULL,
  phs_call_to_fname varchar(64) NULL,
  phs_created_date datetime NOT NULL default '0000-00-00 00:00:00',
  phs_type enum('incoming','outgoing') NOT NULL default 'incoming',
  phs_phone_number varchar(32) NOT NULL default '',
  phs_phone_type varchar(6) NOT NULL,
  phs_phc_id int(11) unsigned NOT NULL,
  phs_description text NOT NULL,
  PRIMARY KEY (phs_id),
  KEY phs_iss_id (phs_iss_id),
  KEY phs_usr_id (phs_usr_id),
  FULLTEXT ft_phone_support (phs_description)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%status`;
CREATE TABLE `%TABLE_PREFIX%status` (
  sta_id int(10) unsigned NOT NULL auto_increment,
  sta_title varchar(64) NOT NULL default '',
  sta_abbreviation char(3) NOT NULL,
  sta_rank int(2) NOT NULL,
  sta_color varchar(7) NOT NULL default '',
  sta_is_closed tinyint(1) NOT NULL default 0,
  PRIMARY KEY (sta_id),
  UNIQUE KEY sta_abbreviation (sta_abbreviation),
  KEY sta_rank (sta_rank),
  KEY sta_is_closed (sta_is_closed)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;
INSERT INTO `%TABLE_PREFIX%status` (sta_id, sta_title, sta_abbreviation, sta_rank, sta_color, sta_is_closed) VALUES (1, 'discovery', 'DSC', 1, '#CCFFFF', 0);
INSERT INTO `%TABLE_PREFIX%status` (sta_id, sta_title, sta_abbreviation, sta_rank, sta_color, sta_is_closed) VALUES (2, 'requirements', 'REQ', 2, '#99CC66', 0);
INSERT INTO `%TABLE_PREFIX%status` (sta_id, sta_title, sta_abbreviation, sta_rank, sta_color, sta_is_closed) VALUES (3, 'implementation', 'IMP', 3, '#6699CC', 0);
INSERT INTO `%TABLE_PREFIX%status` (sta_id, sta_title, sta_abbreviation, sta_rank, sta_color, sta_is_closed) VALUES (4, 'evaluation and testing', 'TST', 4, '#FFCC99', 0);
INSERT INTO `%TABLE_PREFIX%status` (sta_id, sta_title, sta_abbreviation, sta_rank, sta_color, sta_is_closed) VALUES (5, 'released', 'REL', 5, '#CCCCCC', 1);
INSERT INTO `%TABLE_PREFIX%status` (sta_id, sta_title, sta_abbreviation, sta_rank, sta_color, sta_is_closed) VALUES (6, 'killed', 'KIL', 6, '#FFFFFF', 1);

DROP TABLE IF EXISTS `%TABLE_PREFIX%project_status`;
CREATE TABLE `%TABLE_PREFIX%project_status` (
  prs_id int(10) unsigned NOT NULL auto_increment,
  prs_prj_id int(10) unsigned NOT NULL,
  prs_sta_id int(10) unsigned NOT NULL,
  PRIMARY KEY (prs_id),
  KEY prs_prj_id (prs_prj_id, prs_sta_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;
INSERT INTO `%TABLE_PREFIX%project_status` (prs_prj_id, prs_sta_id) VALUES (1, 1);
INSERT INTO `%TABLE_PREFIX%project_status` (prs_prj_id, prs_sta_id) VALUES (1, 2);
INSERT INTO `%TABLE_PREFIX%project_status` (prs_prj_id, prs_sta_id) VALUES (1, 3);
INSERT INTO `%TABLE_PREFIX%project_status` (prs_prj_id, prs_sta_id) VALUES (1, 4);
INSERT INTO `%TABLE_PREFIX%project_status` (prs_prj_id, prs_sta_id) VALUES (1, 5);
INSERT INTO `%TABLE_PREFIX%project_status` (prs_prj_id, prs_sta_id) VALUES (1, 6);

DROP TABLE IF EXISTS `%TABLE_PREFIX%customer_note`;
CREATE TABLE `%TABLE_PREFIX%customer_note` (
  cno_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  cno_prj_id int(11) unsigned NOT NULL,
  cno_customer_id varchar(128) NOT NULL,
  cno_created_date DATETIME NOT NULL,
  cno_updated_date DATETIME NULL,
  cno_note TEXT,
  PRIMARY KEY(cno_id),
  UNIQUE(cno_prj_id, cno_customer_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%customer_account_manager`;
CREATE TABLE `%TABLE_PREFIX%customer_account_manager` (
  cam_id int(11) unsigned NOT NULL auto_increment,
  cam_prj_id int(11) unsigned NOT NULL,
  cam_customer_id int(11) unsigned NOT NULL,
  cam_usr_id int(11) unsigned NOT NULL,
  cam_type varchar(7) NOT NULL,
  PRIMARY KEY (cam_id),
  KEY cam_customer_id (cam_customer_id),
  UNIQUE KEY cam_manager (cam_prj_id, cam_customer_id, cam_usr_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%reminder_level`;
CREATE TABLE `%TABLE_PREFIX%reminder_level` (
  rem_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  rem_created_date DATETIME NOT NULL,
  rem_rank TINYINT(1) NOT NULL,
  rem_last_updated_date DATETIME NULL,
  rem_title VARCHAR(64) NOT NULL,
  rem_prj_id INT(11) UNSIGNED NOT NULL,
  rem_skip_weekend TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY(rem_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%reminder_priority`;
CREATE TABLE `%TABLE_PREFIX%reminder_priority` (
  rep_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  rep_rem_id INT(11) UNSIGNED NOT NULL,
  rep_pri_id INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY(rep_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%reminder_severity`;
CREATE TABLE `%TABLE_PREFIX%reminder_severity` (
  rms_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  rms_rem_id INT(11) UNSIGNED NOT NULL,
  rms_sev_id INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY(rms_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%reminder_requirement`;
CREATE TABLE `%TABLE_PREFIX%reminder_requirement` (
  rer_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  rer_rem_id INT(11) UNSIGNED NOT NULL,
  rer_iss_id INT(11) UNSIGNED NULL,
  rer_support_level_id INT(11) UNSIGNED NULL,
  rer_customer_id INT(11) UNSIGNED NULL,
  rer_trigger_all_issues TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY(rer_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%reminder_history`;
CREATE TABLE `%TABLE_PREFIX%reminder_history` (
  rmh_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  rmh_iss_id INT(11) NOT NULL,
  rmh_rma_id INT(11) NOT NULL,
  rmh_created_date DATETIME NOT NULL,
  PRIMARY KEY(rmh_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%reminder_action`;
CREATE TABLE `%TABLE_PREFIX%reminder_action` (
  rma_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  rma_rem_id INT(11) UNSIGNED NOT NULL,
  rma_rmt_id TINYINT(3) UNSIGNED NOT NULL,
  rma_created_date DATETIME NOT NULL,
  rma_last_updated_date DATETIME NULL,
  rma_title VARCHAR(64) NOT NULL,
  rma_rank TINYINT(2) UNSIGNED NOT NULL,
  rma_alert_irc TINYINT(1) unsigned NOT NULL DEFAULT 0,
  rma_alert_group_leader TINYINT(1) unsigned NOT NULL DEFAULT 0,
  rma_boilerplate varchar(255) DEFAULT NULL,
  PRIMARY KEY(rma_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%reminder_action_list`;
CREATE TABLE `%TABLE_PREFIX%reminder_action_list` (
  ral_rma_id INT(11) UNSIGNED NOT NULL,
  ral_email VARCHAR(255) NOT NULL,
  ral_usr_id INT(11) UNSIGNED NOT NULL
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%reminder_action_type`;
CREATE TABLE `%TABLE_PREFIX%reminder_action_type` (
  rmt_id TINYINT(3) UNSIGNED NOT NULL AUTO_INCREMENT,
  rmt_type VARCHAR(32) NOT NULL,
  rmt_title VARCHAR(64) NOT NULL,
  PRIMARY KEY(rmt_id),
  UNIQUE INDEX rmt_type (rmt_type),
  UNIQUE INDEX rmt_title (rmt_title)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;
INSERT INTO `%TABLE_PREFIX%reminder_action_type` (rmt_type, rmt_title) VALUES ('email_assignee', 'Send Email Alert to Assignee');
INSERT INTO `%TABLE_PREFIX%reminder_action_type` (rmt_type, rmt_title) VALUES ('sms_assignee', 'Send SMS Alert to Assignee');
INSERT INTO `%TABLE_PREFIX%reminder_action_type` (rmt_type, rmt_title) VALUES ('email_list', 'Send Email Alert To...');
INSERT INTO `%TABLE_PREFIX%reminder_action_type` (rmt_type, rmt_title) VALUES ('sms_list', 'Send SMS Alert To...');

DROP TABLE IF EXISTS `%TABLE_PREFIX%reminder_level_condition`;
CREATE TABLE `%TABLE_PREFIX%reminder_level_condition` (
  rlc_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  rlc_rma_id INT(11) UNSIGNED NOT NULL,
  rlc_rmf_id TINYINT(3) UNSIGNED NOT NULL,
  rlc_rmo_id TINYINT(1) UNSIGNED NOT NULL,
  rlc_created_date DATETIME NOT NULL,
  rlc_last_updated_date DATETIME NULL,
  rlc_value VARCHAR(64) NOT NULL,
  rlc_comparison_rmf_id tinyint(3) unsigned,
  PRIMARY KEY(rlc_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%reminder_field`;
CREATE TABLE `%TABLE_PREFIX%reminder_field` (
  rmf_id TINYINT(3) UNSIGNED NOT NULL AUTO_INCREMENT,
  rmf_title VARCHAR(128) NOT NULL,
  rmf_sql_field VARCHAR(32) NOT NULL,
  rmf_sql_representation VARCHAR(255) NOT NULL,
  rmf_allow_column_compare tinyint(1) DEFAULT 0,
  PRIMARY KEY(rmf_id),
  UNIQUE INDEX rmf_title(rmf_title)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;
INSERT INTO `%TABLE_PREFIX%reminder_field` (rmf_title, rmf_sql_field, rmf_sql_representation, rmf_allow_column_compare) VALUES ('Status', 'iss_sta_id', 'iss_sta_id', 0);
INSERT INTO `%TABLE_PREFIX%reminder_field` (rmf_title, rmf_sql_field, rmf_sql_representation, rmf_allow_column_compare) VALUES ('Last Response Date', 'iss_last_response_date', '(UNIX_TIMESTAMP() - IFNULL(UNIX_TIMESTAMP(iss_last_response_date), 0))', 1);
INSERT INTO `%TABLE_PREFIX%reminder_field` (rmf_title, rmf_sql_field, rmf_sql_representation, rmf_allow_column_compare) VALUES ('Last Customer Action Date', 'iss_last_customer_action_date', '(UNIX_TIMESTAMP() - IFNULL(UNIX_TIMESTAMP(iss_last_customer_action_date), 0))', 1);
INSERT INTO `%TABLE_PREFIX%reminder_field` (rmf_title, rmf_sql_field, rmf_sql_representation, rmf_allow_column_compare) VALUES ('Last Update Date', 'iss_updated_date', '(UNIX_TIMESTAMP() - IFNULL(UNIX_TIMESTAMP(iss_updated_date), 0))', 1);
INSERT INTO `%TABLE_PREFIX%reminder_field` (rmf_title, rmf_sql_field, rmf_sql_representation, rmf_allow_column_compare) VALUES ('Created Date', 'iss_created_date', '(UNIX_TIMESTAMP() - IFNULL(UNIX_TIMESTAMP(iss_created_date), 0))', 1);
INSERT INTO `%TABLE_PREFIX%reminder_field` (rmf_title, rmf_sql_field, rmf_sql_representation, rmf_allow_column_compare) VALUES ('First Response Date', 'iss_first_response_date', '(UNIX_TIMESTAMP() - IFNULL(UNIX_TIMESTAMP(iss_first_response_date), 0))', 1);
INSERT INTO `%TABLE_PREFIX%reminder_field` (rmf_title, rmf_sql_field, rmf_sql_representation, rmf_allow_column_compare) VALUES ('Closed Date', 'iss_closed_date', '(UNIX_TIMESTAMP() - IFNULL(UNIX_TIMESTAMP(iss_closed_date), 0))', 1);
INSERT INTO `%TABLE_PREFIX%reminder_field` (rmf_title, rmf_sql_field, rmf_sql_representation, rmf_allow_column_compare) VALUES ('Category', 'iss_prc_id', 'iss_prc_id', 0);

DROP TABLE IF EXISTS `%TABLE_PREFIX%reminder_operator`;
CREATE TABLE `%TABLE_PREFIX%reminder_operator` (
  rmo_id TINYINT(1) UNSIGNED NOT NULL AUTO_INCREMENT,
  rmo_title VARCHAR(32) NULL,
  rmo_sql_representation VARCHAR(32) NULL,
  PRIMARY KEY(rmo_id),
  UNIQUE INDEX rmo_title(rmo_title)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;
INSERT INTO `%TABLE_PREFIX%reminder_operator` (rmo_title, rmo_sql_representation) VALUES ('equal to', '=');
INSERT INTO `%TABLE_PREFIX%reminder_operator` (rmo_title, rmo_sql_representation) VALUES ('not equal to', '<>');
INSERT INTO `%TABLE_PREFIX%reminder_operator` (rmo_title, rmo_sql_representation) VALUES ('is', 'IS');
INSERT INTO `%TABLE_PREFIX%reminder_operator` (rmo_title, rmo_sql_representation) VALUES ('is not', 'IS NOT');
INSERT INTO `%TABLE_PREFIX%reminder_operator` (rmo_title, rmo_sql_representation) VALUES ('greater than', '>');
INSERT INTO `%TABLE_PREFIX%reminder_operator` (rmo_title, rmo_sql_representation) VALUES ('less than', '<');
INSERT INTO `%TABLE_PREFIX%reminder_operator` (rmo_title, rmo_sql_representation) VALUES ('greater or equal than', '>=');
INSERT INTO `%TABLE_PREFIX%reminder_operator` (rmo_title, rmo_sql_representation) VALUES ('less or equal than', '<=');

DROP TABLE IF EXISTS `%TABLE_PREFIX%news`;
CREATE TABLE `%TABLE_PREFIX%news` (
  nws_id int(11) unsigned NOT NULL auto_increment,
  nws_usr_id int(11) unsigned NOT NULL,
  nws_created_date datetime NOT NULL,
  nws_title varchar(255) NOT NULL,
  nws_message text NOT NULL,
  nws_status varchar(8) NOT NULL default 'active',
  PRIMARY KEY (nws_id),
  UNIQUE KEY nws_title (nws_title)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%project_news`;
CREATE TABLE `%TABLE_PREFIX%project_news` (
  prn_nws_id int(11) unsigned NOT NULL,
  prn_prj_id int(11) unsigned NOT NULL,
  PRIMARY KEY (prn_prj_id, prn_nws_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%project_round_robin`;
CREATE TABLE `%TABLE_PREFIX%project_round_robin` (
  prr_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  prr_prj_id INT(11) UNSIGNED NOT NULL,
  prr_blackout_start TIME NOT NULL,
  prr_blackout_end TIME NOT NULL,
  PRIMARY KEY (prr_id),
  UNIQUE KEY prr_prj_id (prr_prj_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%round_robin_user`;
CREATE TABLE `%TABLE_PREFIX%round_robin_user` (
  rru_prr_id INT(11) UNSIGNED NOT NULL,
  rru_usr_id INT(11) UNSIGNED NOT NULL,
  rru_next TINYINT(1) UNSIGNED NULL
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%email_draft`;
CREATE TABLE `%TABLE_PREFIX%email_draft` (
  emd_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  emd_usr_id INT(11) UNSIGNED NOT NULL,
  emd_iss_id INT(11) unsigned NOT NULL,
  emd_sup_id INT(11) UNSIGNED NULL DEFAULT NULL,
  emd_status enum('pending','edited','sent') NOT NULL DEFAULT 'pending',
  emd_updated_date DATETIME NOT NULL,
  emd_subject VARCHAR(255) NOT NULL,
  emd_body LONGTEXT NOT NULL,
  emd_unknown_user varchar(255) NULL DEFAULT NULL,
  PRIMARY KEY(emd_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%email_draft_recipient`;
CREATE TABLE `%TABLE_PREFIX%email_draft_recipient` (
  edr_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  edr_emd_id INT(11) UNSIGNED NOT NULL,
  edr_is_cc TINYINT(1) UNSIGNED NOT NULL default 0,
  edr_email VARCHAR(255) NOT NULL,
  PRIMARY KEY(edr_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%irc_notice`;
CREATE TABLE `%TABLE_PREFIX%irc_notice` (
  ino_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  ino_prj_id int(11) NOT NULL,
  ino_iss_id INT(11) UNSIGNED NOT NULL,
  ino_created_date DATETIME NOT NULL,
  ino_message VARCHAR(255) NOT NULL,
  ino_status VARCHAR(8) NOT NULL DEFAULT 'pending',
  PRIMARY KEY(ino_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%issue_user_replier`;
CREATE TABLE `%TABLE_PREFIX%issue_user_replier` (
  iur_id int(11) unsigned NOT NULL auto_increment,
  iur_iss_id int(10) unsigned NOT NULL default 0,
  iur_usr_id int(10) unsigned NOT NULL default 0,
  iur_email varchar(255) default NULL,
  PRIMARY KEY  (iur_id),
  KEY iur_usr_id (iur_usr_id),
  KEY iur_iss_id (iur_iss_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%mail_queue`;
CREATE TABLE `%TABLE_PREFIX%mail_queue` (
  maq_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  maq_iss_id int(11) unsigned default NULL,
  maq_queued_date DATETIME NOT NULL,
  maq_status VARCHAR(8) NOT NULL DEFAULT 'pending',
  maq_save_copy TINYINT(1) NOT NULL DEFAULT 1,
  maq_sender_ip_address VARCHAR(15) NOT NULL,
  maq_recipient VARCHAR(255) NOT NULL,
  maq_subject varchar(255) NOT NULL,
  maq_headers TEXT NOT NULL,
  maq_body longblob NOT NULL,
  maq_type varchar(30) NULL,
  maq_usr_id int(11) unsigned NULL DEFAULT NULL,
  maq_type_id int(11) unsigned NULL DEFAULT NULL,
  KEY maq_status (maq_status),
  KEY maq_iss_id (maq_iss_id),
  KEY (maq_type, maq_type_id),
  PRIMARY KEY(maq_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%mail_queue_log`;
CREATE TABLE `%TABLE_PREFIX%mail_queue_log` (
  mql_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  mql_maq_id INT(11) UNSIGNED NOT NULL,
  mql_created_date DATETIME NOT NULL,
  mql_status VARCHAR(8) NOT NULL DEFAULT 'error',
  mql_server_message TEXT NULL,
  KEY mql_maq_id (mql_maq_id),
  PRIMARY KEY(mql_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%project_status_date`;
CREATE TABLE `%TABLE_PREFIX%project_status_date` (
  psd_id INT(11) UNSIGNED NOT NULL auto_increment,
  psd_prj_id INT(11) UNSIGNED NOT NULL,
  psd_sta_id INT(10) UNSIGNED NOT NULL,
  psd_date_field VARCHAR(64) NOT NULL,
  psd_label VARCHAR(32) NOT NULL,
  PRIMARY KEY (psd_id),
  UNIQUE KEY (psd_prj_id, psd_sta_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%faq`;
CREATE TABLE `%TABLE_PREFIX%faq` (
  faq_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  faq_prj_id INT(11) UNSIGNED NOT NULL,
  faq_usr_id INT(11) UNSIGNED NOT NULL,
  faq_created_date DATETIME NOT NULL,
  faq_updated_date DATETIME NULL,
  faq_title VARCHAR(255) NOT NULL,
  faq_message LONGTEXT NOT NULL,
  faq_rank TINYINT(2) UNSIGNED NOT NULL,
  PRIMARY KEY (faq_id),
  UNIQUE KEY faq_title (faq_title)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%faq_support_level`;
CREATE TABLE `%TABLE_PREFIX%faq_support_level` (
  fsl_faq_id INT(11) UNSIGNED NOT NULL,
  fsl_support_level_id INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (fsl_faq_id, fsl_support_level_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%project_email_response`;
CREATE TABLE `%TABLE_PREFIX%project_email_response` (
  per_prj_id int(11) unsigned NOT NULL,
  per_ere_id int(10) unsigned NOT NULL,
  PRIMARY KEY (per_prj_id, per_ere_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%project_phone_category`;
CREATE TABLE `%TABLE_PREFIX%project_phone_category` (
  phc_id int(11) unsigned NOT NULL auto_increment,
  phc_prj_id int(11) unsigned NOT NULL default 0,
  phc_title varchar(64) NOT NULL default '',
  PRIMARY KEY  (phc_id),
  UNIQUE KEY uniq_category (phc_prj_id,phc_title),
  KEY phc_prj_id (phc_prj_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;
INSERT INTO `%TABLE_PREFIX%project_phone_category` (phc_id, phc_prj_id, phc_title) VALUES (1, 1, 'Sales Issues');
INSERT INTO `%TABLE_PREFIX%project_phone_category` (phc_id, phc_prj_id, phc_title) VALUES (2, 1, 'Technical Issues');
INSERT INTO `%TABLE_PREFIX%project_phone_category` (phc_id, phc_prj_id, phc_title) VALUES (3, 1, 'Administrative Issues');
INSERT INTO `%TABLE_PREFIX%project_phone_category` (phc_id, phc_prj_id, phc_title) VALUES (4, 1, 'Other');

DROP TABLE IF EXISTS `%TABLE_PREFIX%group`;
CREATE TABLE `%TABLE_PREFIX%group` (
  grp_id int(11) unsigned not null auto_increment,
  grp_name varchar(100) not null,
  grp_description varchar(255) default null,
  grp_manager_usr_id int(11) unsigned not null,
  PRIMARY KEY(grp_id),
  UNIQUE KEY (grp_name)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%project_group`;
CREATE TABLE `%TABLE_PREFIX%project_group` (
  pgr_prj_id int(11) unsigned not null,
  pgr_grp_id int(11) unsigned not null,
  PRIMARY KEY (pgr_prj_id, pgr_grp_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%reminder_triggered_action`;
CREATE TABLE `%TABLE_PREFIX%reminder_triggered_action` (
  rta_iss_id int(11) unsigned not null,
  rta_rma_id int(11) unsigned not null,
  PRIMARY KEY (rta_iss_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%issue_quarantine`;
CREATE TABLE `%TABLE_PREFIX%issue_quarantine` (
    iqu_iss_id int(11) unsigned auto_increment,
    iqu_expiration datetime NULL,
    iqu_status tinyint(1),
    PRIMARY KEY(iqu_iss_id),
    INDEX(iqu_expiration)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%link_filter`;
CREATE TABLE `%TABLE_PREFIX%link_filter` (
  lfi_id int(11) unsigned NOT NULL auto_increment,
  lfi_pattern varchar(255) NOT NULL,
  lfi_replacement varchar(255) NOT NULL,
  lfi_usr_role tinyint(9) NOT NULL DEFAULT 0,
  lfi_description varchar(255) NULL,
  PRIMARY KEY  (lfi_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%project_link_filter`;
CREATE TABLE `%TABLE_PREFIX%project_link_filter` (
  plf_prj_id int(11) NOT NULL,
  plf_lfi_id int(11) NOT NULL,
  PRIMARY KEY  (plf_prj_id, plf_lfi_id)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%columns_to_display`;
CREATE TABLE `%TABLE_PREFIX%columns_to_display` (
  ctd_prj_id int(11) unsigned NOT NULL,
  ctd_page varchar(20) NOT NULL,
  ctd_field varchar(30) NOT NULL,
  ctd_min_role tinyint(1) NOT NULL DEFAULT 0,
  ctd_rank tinyint(2) NOT NULL DEFAULT 0,
  PRIMARY KEY(ctd_prj_id, ctd_page, ctd_field),
  INDEX(ctd_prj_id, ctd_page)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;
INSERT INTO `%TABLE_PREFIX%columns_to_display` VALUES (1,'list_issues','pri_rank',1,1);
INSERT INTO `%TABLE_PREFIX%columns_to_display` VALUES (1,'list_issues','iss_id',1,2);
INSERT INTO `%TABLE_PREFIX%columns_to_display` VALUES (1,'list_issues','usr_full_name',1,3);
INSERT INTO `%TABLE_PREFIX%columns_to_display` VALUES (1,'list_issues','iss_grp_id',1,4);
INSERT INTO `%TABLE_PREFIX%columns_to_display` VALUES (1,'list_issues','assigned',1,5);
INSERT INTO `%TABLE_PREFIX%columns_to_display` VALUES (1,'list_issues','time_spent',1,6);
INSERT INTO `%TABLE_PREFIX%columns_to_display` VALUES (1,'list_issues','prc_title',1,7);
INSERT INTO `%TABLE_PREFIX%columns_to_display` VALUES (1,'list_issues','pre_title',1,8);
INSERT INTO `%TABLE_PREFIX%columns_to_display` VALUES (1,'list_issues','iss_customer_id',1,9);
INSERT INTO `%TABLE_PREFIX%columns_to_display` VALUES (1,'list_issues','sta_rank',1,10);
INSERT INTO `%TABLE_PREFIX%columns_to_display` VALUES (1,'list_issues','sta_change_date',1,11);
INSERT INTO `%TABLE_PREFIX%columns_to_display` VALUES (1,'list_issues','last_action_date',1,12);
INSERT INTO `%TABLE_PREFIX%columns_to_display` VALUES (1,'list_issues','custom_fields',1,13);
INSERT INTO `%TABLE_PREFIX%columns_to_display` VALUES (1,'list_issues','iss_summary',1,14);
INSERT INTO `%TABLE_PREFIX%columns_to_display` VALUES (1,'list_issues','iss_dev_time',9,15);
INSERT INTO `%TABLE_PREFIX%columns_to_display` VALUES (1,'list_issues','iss_percent_complete',9,16);

DROP TABLE IF EXISTS `%TABLE_PREFIX%search_profile`;
CREATE TABLE `%TABLE_PREFIX%search_profile` (
  sep_id int(11) unsigned NOT NULL auto_increment,
  sep_usr_id int(11) unsigned NOT NULL,
  sep_prj_id int(11) unsigned NOT NULL,
  sep_type char(5) NOT NULL,
  sep_user_profile blob NOT NULL,
  PRIMARY KEY (sep_id),
  UNIQUE (sep_usr_id, sep_prj_id, sep_type)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `%TABLE_PREFIX%version`;
CREATE TABLE `%TABLE_PREFIX%version` (
    ver_version int(11) unsigned NOT NULL DEFAULT 0
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

INSERT INTO `%TABLE_PREFIX%version` SET ver_version=5;
