
DROP TABLE IF EXISTS %TABLE_PREFIX%custom_filter;
CREATE TABLE %TABLE_PREFIX%custom_filter (
  cst_id int(10) unsigned NOT NULL auto_increment,
  cst_usr_id int(10) unsigned NOT NULL default '0',
  cst_prj_id int(10) unsigned NOT NULL default '0',
  cst_title varchar(64) NOT NULL default '',
  cst_iss_pri_id int(10) unsigned default NULL,
  cst_keywords varchar(64) default NULL,
  cst_users varchar(64) default NULL,
  cst_iss_prc_id int(10) unsigned default NULL,
  cst_iss_sta_id int(10) unsigned default NULL,
  cst_iss_pre_id int(10) unsigned default NULL,
  cst_created_date date default NULL,
  cst_created_date_filter_type varchar(7) default NULL,
  cst_created_date_end date default NULL,
  cst_updated_date date default NULL,
  cst_updated_date_filter_type varchar(7) default NULL,
  cst_updated_date_end date default NULL,
  cst_last_response_date date default NULL,
  cst_last_response_date_filter_type varchar(7) default NULL,
  cst_last_response_date_end date default NULL,
  cst_first_response_date date default NULL,
  cst_first_response_date_filter_type varchar(7) default NULL,
  cst_first_response_date_end date default NULL,
  cst_closed_date date default NULL,
  cst_closed_date_filter_type varchar(7) default NULL,
  cst_closed_date_end date default NULL,
  cst_rows char(3) default NULL,
  cst_sort_by varchar(32) default NULL,
  cst_sort_order varchar(4) default NULL,
  cst_hide_closed int(1) default NULL,
  PRIMARY KEY  (cst_id),
  KEY cst_usr_id (cst_usr_id,cst_prj_id)
);

DROP TABLE IF EXISTS %TABLE_PREFIX%email_account;
CREATE TABLE %TABLE_PREFIX%email_account (
  ema_id int(10) unsigned NOT NULL auto_increment,
  ema_prj_id int(10) unsigned NOT NULL default '0',
  ema_type varchar(32) NOT NULL default '',
  ema_folder varchar(255) default NULL,
  ema_hostname varchar(255) NOT NULL default '',
  ema_port varchar(5) NOT NULL default '',
  ema_username varchar(64) NOT NULL default '',
  ema_password varchar(64) NOT NULL default '',
  ema_get_only_new int(1) NOT NULL DEFAULT 0,
  ema_leave_copy int(1) NOT NULL DEFAULT 0,
  PRIMARY KEY  (ema_id),
  KEY ema_prj_id (ema_prj_id)
);

DROP TABLE IF EXISTS %TABLE_PREFIX%issue;
CREATE TABLE %TABLE_PREFIX%issue (
  iss_id int(11) unsigned NOT NULL auto_increment,
  iss_usr_id int(10) unsigned NOT NULL default '0',
  iss_prj_id int(11) unsigned NOT NULL default '0',
  iss_prc_id int(11) unsigned NOT NULL default '0',
  iss_pre_id int(10) unsigned NOT NULL default '0',
  iss_pri_id tinyint(1) NOT NULL default '0',
  iss_sta_id tinyint(1) NOT NULL default '0',
  iss_res_id int(10) unsigned NOT NULL default '0',
  iss_duplicated_iss_id int(11) unsigned NULL default NULL,
  iss_created_date datetime NOT NULL default '0000-00-00 00:00:00',
  iss_updated_date datetime default NULL,
  iss_last_response_date datetime default NULL,
  iss_first_response_date datetime default NULL,
  iss_closed_date datetime default NULL,
  iss_expected_resolution_date date default NULL,
  iss_summary varchar(128) NOT NULL default '',
  iss_description text NOT NULL,
  iss_dev_time float default NULL,
  iss_developer_est_time float default NULL,
  iss_impact_analysis text,
  iss_lock_usr_id int(10) default NULL,
  iss_contact_person_lname varchar(64) default NULL,
  iss_contact_person_fname varchar(64) default NULL,
  iss_contact_email varchar(255) default NULL,
  iss_contact_phone varchar(32) default NULL,
  iss_contact_timezone varchar(64) default NULL,
  PRIMARY KEY  (iss_id),
  KEY iss_prj_id (iss_prj_id),
  KEY iss_prc_id (iss_prc_id),
  KEY iss_res_id (iss_res_id)
);

DROP TABLE IF EXISTS %TABLE_PREFIX%issue_association;
CREATE TABLE %TABLE_PREFIX%issue_association (
  isa_issue_id int(10) unsigned NOT NULL default '0',
  isa_associated_id int(10) unsigned NOT NULL default '0',
  KEY isa_issue_id (isa_issue_id,isa_associated_id)
);

DROP TABLE IF EXISTS %TABLE_PREFIX%issue_attachment;
CREATE TABLE %TABLE_PREFIX%issue_attachment (
  iat_id int(10) unsigned NOT NULL auto_increment,
  iat_iss_id int(10) unsigned NOT NULL default '0',
  iat_usr_id int(10) unsigned NOT NULL default '0',
  iat_created_date datetime NOT NULL default '0000-00-00 00:00:00',
  iat_description text,
  PRIMARY KEY  (iat_id),
  KEY iat_iss_id (iat_iss_id,iat_usr_id)
);

DROP TABLE IF EXISTS %TABLE_PREFIX%issue_attachment_file;
CREATE TABLE %TABLE_PREFIX%issue_attachment_file (
  iaf_id int(10) unsigned NOT NULL auto_increment,
  iaf_iat_id int(10) unsigned NOT NULL default '0',
  iaf_file longblob NULL,
  iaf_filename varchar(255) NOT NULL default '',
  iaf_filetype varchar(255) NULL,
  iaf_filesize varchar(32) NOT NULL default '',
  PRIMARY KEY  (iaf_id),
  KEY iaf_iat_id (iaf_iat_id)
);

DROP TABLE IF EXISTS %TABLE_PREFIX%issue_checkin;
CREATE TABLE %TABLE_PREFIX%issue_checkin (
  isc_id int(10) unsigned NOT NULL auto_increment,
  isc_iss_id int(10) unsigned NOT NULL default '0',
  isc_module varchar(255) NOT NULL default '',
  isc_filename varchar(255) NOT NULL default '',
  isc_old_version varchar(32) NOT NULL default '',
  isc_new_version varchar(32) NOT NULL default '',
  isc_created_date datetime NOT NULL default '0000-00-00 00:00:00',
  isc_username varchar(32) NOT NULL default '',
  isc_commit_msg text,
  PRIMARY KEY  (isc_id),
  KEY isc_iss_id (isc_iss_id)
);

DROP TABLE IF EXISTS %TABLE_PREFIX%issue_history;
CREATE TABLE %TABLE_PREFIX%issue_history (
  his_id int(10) unsigned NOT NULL auto_increment,
  his_iss_id int(10) unsigned NOT NULL default '0',
  his_created_date datetime NOT NULL default '0000-00-00 00:00:00',
  his_summary text NOT NULL default '',
  PRIMARY KEY  (his_id),
  KEY his_id (his_id),
  KEY his_iss_id (his_iss_id),
  KEY his_created_date (his_created_date)
);

DROP TABLE IF EXISTS %TABLE_PREFIX%issue_requirement;
CREATE TABLE %TABLE_PREFIX%issue_requirement (
  isr_id int(10) unsigned NOT NULL auto_increment,
  isr_iss_id int(10) unsigned NOT NULL default '0',
  isr_usr_id int(10) unsigned NOT NULL default '0',
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
);

DROP TABLE IF EXISTS %TABLE_PREFIX%issue_user;
CREATE TABLE %TABLE_PREFIX%issue_user (
  isu_iss_id int(10) unsigned NOT NULL default '0',
  isu_usr_id int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (isu_iss_id,isu_usr_id),
  KEY isu_usr_id (isu_usr_id),
  KEY isu_iss_id (isu_iss_id)
);

DROP TABLE IF EXISTS %TABLE_PREFIX%note;
CREATE TABLE %TABLE_PREFIX%note (
  not_id int(11) unsigned NOT NULL auto_increment,
  not_iss_id int(11) unsigned NOT NULL default '0',
  not_created_date datetime NOT NULL default '0000-00-00 00:00:00',
  not_usr_id int(11) unsigned NOT NULL default '0',
  not_title varchar(255) NOT NULL,
  not_note longtext NOT NULL,
  not_blocked_message longtext NULL,
  not_parent_id int(11) unsigned NULL,
  PRIMARY KEY  (not_id),
  KEY not_bug_id (not_iss_id,not_usr_id)
);

DROP TABLE IF EXISTS %TABLE_PREFIX%priority;
CREATE TABLE %TABLE_PREFIX%priority (
  pri_id tinyint(1) NOT NULL default '0',
  pri_title varchar(64) NOT NULL default '',
  PRIMARY KEY  (pri_title),
  UNIQUE KEY pri_id (pri_id),
  KEY pri_id_2 (pri_id)
);
INSERT INTO %TABLE_PREFIX%priority (pri_id, pri_title) VALUES (5, 'Not Prioritized');
INSERT INTO %TABLE_PREFIX%priority (pri_id, pri_title) VALUES (1, 'Critical');
INSERT INTO %TABLE_PREFIX%priority (pri_id, pri_title) VALUES (2, 'High');
INSERT INTO %TABLE_PREFIX%priority (pri_id, pri_title) VALUES (3, 'Medium');
INSERT INTO %TABLE_PREFIX%priority (pri_id, pri_title) VALUES (4, 'Low');

DROP TABLE IF EXISTS %TABLE_PREFIX%project;
CREATE TABLE %TABLE_PREFIX%project (
  prj_id int(11) unsigned NOT NULL auto_increment,
  prj_created_date datetime NOT NULL default '0000-00-00 00:00:00',
  prj_title varchar(64) NOT NULL default '',
  prj_status set('active','archived') NOT NULL default 'active',
  prj_lead_usr_id int(11) unsigned NOT NULL default '0',
  prj_initial_sta_id int(10) unsigned NOT NULL default '0',
  prj_remote_invocation varchar(8) NOT NULL default 'disabled',
  prj_remote_invocation_options text,
  prj_anonymous_post varchar(8) NOT NULL default 'disabled',
  prj_anonymous_post_options text,
  prj_outgoing_sender_name varchar(255) NOT NULL,
  prj_outgoing_sender_email varchar(255) NOT NULL,
  PRIMARY KEY  (prj_id),
  UNIQUE KEY prj_title (prj_title),
  KEY prj_lead_usr_id (prj_lead_usr_id)
);
INSERT INTO %TABLE_PREFIX%project (prj_id, prj_created_date, prj_title, prj_status, prj_lead_usr_id, prj_initial_sta_id, prj_remote_invocation, prj_remote_invocation_options, prj_anonymous_post, prj_anonymous_post_options, prj_outgoing_sender_name, prj_outgoing_sender_email) VALUES (1, NOW(), 'Default Project', 'active', 1, 1, '', NULL, '0', NULL, 'Default Project', 'default_project@domain.com');

DROP TABLE IF EXISTS %TABLE_PREFIX%project_category;
CREATE TABLE %TABLE_PREFIX%project_category (
  prc_id int(11) unsigned NOT NULL auto_increment,
  prc_prj_id int(11) unsigned NOT NULL default '0',
  prc_title varchar(64) NOT NULL default '',
  PRIMARY KEY  (prc_id),
  UNIQUE KEY uniq_category (prc_prj_id,prc_title),
  KEY prc_prj_id (prc_prj_id)
);
INSERT INTO %TABLE_PREFIX%project_category (prc_id, prc_prj_id, prc_title) VALUES (1, 1, 'Bug');
INSERT INTO %TABLE_PREFIX%project_category (prc_id, prc_prj_id, prc_title) VALUES (2, 1, 'Feature Request');
INSERT INTO %TABLE_PREFIX%project_category (prc_id, prc_prj_id, prc_title) VALUES (3, 1, 'Technical Support');

DROP TABLE IF EXISTS %TABLE_PREFIX%project_release;
CREATE TABLE %TABLE_PREFIX%project_release (
  pre_id int(10) unsigned NOT NULL auto_increment,
  pre_prj_id int(10) unsigned NOT NULL default '0',
  pre_title varchar(128) NOT NULL default '',
  pre_scheduled_date date NOT NULL default '0000-00-00',
  pre_status enum('available','unavailable') NOT NULL default 'available',
  PRIMARY KEY  (pre_id),
  UNIQUE KEY pre_title (pre_prj_id, pre_title),
  KEY pre_prj_id (pre_prj_id)
);
INSERT INTO %TABLE_PREFIX%project_release (pre_id, pre_prj_id, pre_title, pre_scheduled_date, pre_status) VALUES (1, 1, 'Example Release', '2002-09-26', 'available');

DROP TABLE IF EXISTS %TABLE_PREFIX%project_user;
CREATE TABLE %TABLE_PREFIX%project_user (
  pru_id int(11) unsigned NOT NULL auto_increment,
  pru_prj_id int(11) unsigned NOT NULL default '0',
  pru_usr_id int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (pru_id),
  KEY pru_prj_id (pru_prj_id,pru_usr_id)
);
INSERT INTO %TABLE_PREFIX%project_user (pru_id, pru_prj_id, pru_usr_id) VALUES (1, 1, 1);

DROP TABLE IF EXISTS %TABLE_PREFIX%resolution;
CREATE TABLE %TABLE_PREFIX%resolution (
  res_id int(10) unsigned NOT NULL auto_increment,
  res_title varchar(64) NOT NULL default '',
  res_created_date datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (res_id),
  UNIQUE KEY res_title (res_title)
);
INSERT INTO %TABLE_PREFIX%resolution (res_id, res_title, res_created_date) VALUES (1, 'open', NOW());
INSERT INTO %TABLE_PREFIX%resolution (res_id, res_title, res_created_date) VALUES (2, 'fixed', NOW());
INSERT INTO %TABLE_PREFIX%resolution (res_id, res_title, res_created_date) VALUES (3, 'reopened', NOW());
INSERT INTO %TABLE_PREFIX%resolution (res_id, res_title, res_created_date) VALUES (4, 'unable to reproduce', NOW());
INSERT INTO %TABLE_PREFIX%resolution (res_id, res_title, res_created_date) VALUES (5, 'not fixable', NOW());
INSERT INTO %TABLE_PREFIX%resolution (res_id, res_title, res_created_date) VALUES (6, 'duplicate', NOW());
INSERT INTO %TABLE_PREFIX%resolution (res_id, res_title, res_created_date) VALUES (7, 'not a bug', NOW());
INSERT INTO %TABLE_PREFIX%resolution (res_id, res_title, res_created_date) VALUES (8, 'suspended', NOW());
INSERT INTO %TABLE_PREFIX%resolution (res_id, res_title, res_created_date) VALUES (9, 'won\'t fix', NOW());

DROP TABLE IF EXISTS %TABLE_PREFIX%subscription;
CREATE TABLE %TABLE_PREFIX%subscription (
  sub_id int(10) unsigned NOT NULL auto_increment,
  sub_iss_id int(10) unsigned NOT NULL default '0',
  sub_usr_id int(10) unsigned default NULL,
  sub_created_date datetime NOT NULL default '0000-00-00 00:00:00',
  sub_level varchar(10) NOT NULL default 'user',
  sub_email varchar(255) default NULL,
  PRIMARY KEY  (sub_id),
  KEY sub_iss_id (sub_iss_id,sub_usr_id)
);

DROP TABLE IF EXISTS %TABLE_PREFIX%subscription_type;
CREATE TABLE %TABLE_PREFIX%subscription_type (
  sbt_id int(10) unsigned NOT NULL auto_increment,
  sbt_sub_id int(10) unsigned NOT NULL default '0',
  sbt_type varchar(10) NOT NULL default '',
  PRIMARY KEY  (sbt_id),
  KEY sbt_sub_id (sbt_sub_id)
);

DROP TABLE IF EXISTS %TABLE_PREFIX%support_email;
CREATE TABLE %TABLE_PREFIX%support_email (
  sup_id int(11) unsigned NOT NULL auto_increment,
  sup_ema_id int(10) unsigned NOT NULL default '0',
  sup_parent_id int(11) unsigned NOT NULL default '0',
  sup_iss_id int(11) unsigned default '0',
  sup_message_id varchar(255) NOT NULL default '',
  sup_date datetime NOT NULL default '0000-00-00 00:00:00',
  sup_from varchar(255) NOT NULL default '',
  sup_to varchar(255) NOT NULL default '',
  sup_cc varchar(255) default NULL,
  sup_subject varchar(255) NOT NULL default '',
  sup_body longtext NOT NULL,
  sup_full_email longtext NOT NULL,
  sup_has_attachment tinyint(1) NOT NULL default '0',
  sup_removed tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (sup_id),
  KEY sup_parent_id (sup_parent_id),
  KEY sup_ema_id (sup_ema_id),
  KEY sup_removed (sup_removed),
  KEY (sup_removed, sup_ema_id, sup_iss_id),
  KEY (sup_removed, sup_ema_id, sup_date)
);

DROP TABLE IF EXISTS %TABLE_PREFIX%time_tracking;
CREATE TABLE %TABLE_PREFIX%time_tracking (
  ttr_id int(10) unsigned NOT NULL auto_increment,
  ttr_ttc_id int(10) unsigned NOT NULL default '0',
  ttr_iss_id int(10) unsigned NOT NULL default '0',
  ttr_usr_id int(10) unsigned NOT NULL default '0',
  ttr_created_date datetime NOT NULL default '0000-00-00 00:00:00',
  ttr_time_spent int(11) unsigned NOT NULL default '0',
  ttr_summary varchar(255) NOT NULL default '',
  PRIMARY KEY  (ttr_id),
  KEY ttr_ttc_id (ttr_ttc_id,ttr_iss_id,ttr_usr_id)
);

DROP TABLE IF EXISTS %TABLE_PREFIX%time_tracking_category;
CREATE TABLE %TABLE_PREFIX%time_tracking_category (
  ttc_id int(10) unsigned NOT NULL auto_increment,
  ttc_title varchar(128) NOT NULL default '',
  ttc_created_date datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (ttc_id),
  UNIQUE KEY ttc_title (ttc_title)
);
INSERT INTO %TABLE_PREFIX%time_tracking_category (ttc_id, ttc_title, ttc_created_date) VALUES (1, 'Development', NOW());
INSERT INTO %TABLE_PREFIX%time_tracking_category (ttc_id, ttc_title, ttc_created_date) VALUES (2, 'Design', NOW());
INSERT INTO %TABLE_PREFIX%time_tracking_category (ttc_id, ttc_title, ttc_created_date) VALUES (3, 'Planning', NOW());
INSERT INTO %TABLE_PREFIX%time_tracking_category (ttc_id, ttc_title, ttc_created_date) VALUES (4, 'Gathering Requirements', NOW());
INSERT INTO %TABLE_PREFIX%time_tracking_category (ttc_id, ttc_title, ttc_created_date) VALUES (5, 'Database Changes', NOW());
INSERT INTO %TABLE_PREFIX%time_tracking_category (ttc_id, ttc_title, ttc_created_date) VALUES (6, 'Tech-Support', NOW());
INSERT INTO %TABLE_PREFIX%time_tracking_category (ttc_id, ttc_title, ttc_created_date) VALUES (7, 'Release', NOW());

DROP TABLE IF EXISTS %TABLE_PREFIX%user;
CREATE TABLE %TABLE_PREFIX%user (
  usr_id int(11) unsigned NOT NULL auto_increment,
  usr_created_date datetime NOT NULL default '0000-00-00 00:00:00',
  usr_status varchar(8) NOT NULL default 'active',
  usr_password varchar(32) NOT NULL default '',
  usr_full_name varchar(255) NOT NULL default '',
  usr_email varchar(255) NOT NULL default '',
  usr_role tinyint(1) unsigned NOT NULL default '1',
  usr_preferences longtext,
  usr_sms_email varchar(255) NULL,
  PRIMARY KEY  (usr_id),
  UNIQUE KEY usr_email (usr_email),
  KEY usr_email_password (usr_email, usr_password)
);
INSERT INTO %TABLE_PREFIX%user (usr_id, usr_created_date, usr_password, usr_full_name, usr_email, usr_role, usr_preferences) VALUES (1, NOW(), '21232f297a57a5a743894a0e4a801fc3', 'Admin User', 'admin@domain.com', 5, '');

DROP TABLE IF EXISTS %TABLE_PREFIX%custom_field;
CREATE TABLE %TABLE_PREFIX%custom_field (
  fld_id int(10) unsigned NOT NULL auto_increment,
  fld_title varchar(32) NOT NULL default '',
  fld_description varchar(64) default NULL,
  fld_type varchar(8) NOT NULL default 'text',
  fld_report_form int(1) NOT NULL default 1,
  fld_report_form_required int(1) NOT NULL default 0,
  fld_anonymous_form int(1) NOT NULL default 1,
  fld_anonymous_form_required int(1) NOT NULL default 0,
  PRIMARY KEY  (fld_id)
);

DROP TABLE IF EXISTS %TABLE_PREFIX%custom_field_option;
CREATE TABLE %TABLE_PREFIX%custom_field_option (
  cfo_id int(10) unsigned NOT NULL auto_increment,
  cfo_fld_id int(10) unsigned NOT NULL default '0',
  cfo_value varchar(64) NOT NULL default '',
  PRIMARY KEY  (cfo_id),
  KEY icf_fld_id (cfo_fld_id)
);

DROP TABLE IF EXISTS %TABLE_PREFIX%issue_custom_field;
CREATE TABLE %TABLE_PREFIX%issue_custom_field (
  icf_id int(10) unsigned NOT NULL auto_increment,
  icf_iss_id int(10) unsigned NOT NULL default '0',
  icf_fld_id int(10) unsigned NOT NULL default '0',
  icf_value text default NULL,
  PRIMARY KEY  (icf_id),
  KEY icf_iss_id (icf_iss_id),
  KEY icf_fld_id (icf_fld_id)
);

DROP TABLE IF EXISTS %TABLE_PREFIX%project_custom_field;
CREATE TABLE %TABLE_PREFIX%project_custom_field (
  pcf_id int(10) unsigned NOT NULL auto_increment,
  pcf_prj_id int(10) unsigned NOT NULL default '0',
  pcf_fld_id int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (pcf_id),
  KEY pcf_prj_id (pcf_prj_id),
  KEY pcf_fld_id (pcf_fld_id)
);

DROP TABLE IF EXISTS %TABLE_PREFIX%email_response;
CREATE TABLE %TABLE_PREFIX%email_response (
  ere_id int(10) unsigned NOT NULL auto_increment,
  ere_title varchar(64) NOT NULL,
  ere_response_body text NOT NULL,
  PRIMARY KEY  (ere_id),
  UNIQUE KEY ere_title (ere_title)
);

DROP TABLE IF EXISTS %TABLE_PREFIX%phone_support;
CREATE TABLE %TABLE_PREFIX%phone_support (
  phs_id int(10) unsigned NOT NULL auto_increment,
  phs_usr_id int(10) unsigned NOT NULL default '0',
  phs_iss_id int(10) unsigned NOT NULL default '0',
  phs_call_from_lname varchar(64) NULL,
  phs_call_from_fname varchar(64) NULL,
  phs_call_to_lname varchar(64) NULL,
  phs_call_to_fname varchar(64) NULL,
  phs_created_date datetime NOT NULL default '0000-00-00 00:00:00',
  phs_type enum('incoming','outgoing') NOT NULL default 'incoming',
  phs_phone_number varchar(32) NOT NULL default '',
  phs_phone_type varchar(6) NOT NULL,
  phs_reason varchar(16) NOT NULL,
  phs_time_spent int(10) unsigned NOT NULL default '0',
  phs_description text NOT NULL,
  PRIMARY KEY (phs_id),
  KEY phs_iss_id (phs_iss_id),
  KEY phs_usr_id (phs_usr_id)
);

DROP TABLE IF EXISTS %TABLE_PREFIX%status;
CREATE TABLE %TABLE_PREFIX%status (
  sta_id int(10) NOT NULL default '0' auto_increment,
  sta_title varchar(64) NOT NULL default '',
  sta_abbreviation char(3) NOT NULL,
  sta_rank int(2) NOT NULL,
  sta_color varchar(7) NOT NULL default '',
  sta_is_closed tinyint(1) NOT NULL default 0,
  PRIMARY KEY (sta_id),
  UNIQUE KEY sta_abbreviation (sta_abbreviation),
  KEY sta_rank (sta_rank),
  KEY sta_is_closed (sta_is_closed)
);
INSERT INTO %TABLE_PREFIX%status (sta_id, sta_title, sta_abbreviation, sta_rank, sta_color, sta_is_closed) VALUES (1, 'discovery', 'DSC', 1, '#CCFFFF', 0);
INSERT INTO %TABLE_PREFIX%status (sta_id, sta_title, sta_abbreviation, sta_rank, sta_color, sta_is_closed) VALUES (2, 'requirements', 'REQ', 2, '#99CC66', 0);
INSERT INTO %TABLE_PREFIX%status (sta_id, sta_title, sta_abbreviation, sta_rank, sta_color, sta_is_closed) VALUES (3, 'implementation', 'IMP', 3, '#6699CC', 0);
INSERT INTO %TABLE_PREFIX%status (sta_id, sta_title, sta_abbreviation, sta_rank, sta_color, sta_is_closed) VALUES (4, 'evaluation and testing', 'TST', 4, '#FFCC99', 0);
INSERT INTO %TABLE_PREFIX%status (sta_id, sta_title, sta_abbreviation, sta_rank, sta_color, sta_is_closed) VALUES (5, 'released', 'REL', 5, '#CCCCCC', 1);
INSERT INTO %TABLE_PREFIX%status (sta_id, sta_title, sta_abbreviation, sta_rank, sta_color, sta_is_closed) VALUES (6, 'killed', 'KIL', 6, '#FFFFFF', 1);

DROP TABLE IF EXISTS %TABLE_PREFIX%project_status;
CREATE TABLE %TABLE_PREFIX%project_status (
  prs_id int(10) unsigned NOT NULL auto_increment,
  prs_prj_id int(10) unsigned NOT NULL,
  prs_sta_id int(10) unsigned NOT NULL,
  PRIMARY KEY (prs_id),
  KEY prs_prj_id (prs_prj_id, prs_sta_id)
);
INSERT INTO %TABLE_PREFIX%project_status (prs_prj_id, prs_sta_id) VALUES (1, 1);
INSERT INTO %TABLE_PREFIX%project_status (prs_prj_id, prs_sta_id) VALUES (1, 2);
INSERT INTO %TABLE_PREFIX%project_status (prs_prj_id, prs_sta_id) VALUES (1, 3);
INSERT INTO %TABLE_PREFIX%project_status (prs_prj_id, prs_sta_id) VALUES (1, 4);
INSERT INTO %TABLE_PREFIX%project_status (prs_prj_id, prs_sta_id) VALUES (1, 5);
INSERT INTO %TABLE_PREFIX%project_status (prs_prj_id, prs_sta_id) VALUES (1, 6);

DROP TABLE IF EXISTS %TABLE_PREFIX%reminder_level;
CREATE TABLE %TABLE_PREFIX%reminder_level (
  rem_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  rem_created_date DATETIME NOT NULL,
  rem_rank TINYINT(1) NOT NULL,
  rem_last_updated_date DATETIME NULL,
  rem_title VARCHAR(64) NOT NULL,
  rem_prj_id INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY(rem_id)
);

DROP TABLE IF EXISTS %TABLE_PREFIX%reminder_priority;
CREATE TABLE %TABLE_PREFIX%reminder_priority (
  rep_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  rep_rem_id INT(11) UNSIGNED NOT NULL,
  rep_pri_id INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY(rep_id)
);

DROP TABLE IF EXISTS %TABLE_PREFIX%reminder_requirement;
CREATE TABLE %TABLE_PREFIX%reminder_requirement (
  rer_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  rer_rem_id INT(11) UNSIGNED NOT NULL,
  rer_iss_id INT(11) UNSIGNED NULL,
  rer_trigger_all_issues TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY(rer_id)
);

DROP TABLE IF EXISTS %TABLE_PREFIX%reminder_history;
CREATE TABLE %TABLE_PREFIX%reminder_history (
  rmh_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  rmh_iss_id INT(11) NOT NULL,
  rmh_rma_id INT(11) NOT NULL,
  rmh_created_date DATETIME NOT NULL,
  PRIMARY KEY(rmh_id)
);

DROP TABLE IF EXISTS %TABLE_PREFIX%reminder_action;
CREATE TABLE %TABLE_PREFIX%reminder_action (
  rma_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  rma_rem_id INT(11) UNSIGNED NOT NULL,
  rma_rmt_id TINYINT(3) UNSIGNED NOT NULL,
  rma_created_date DATETIME NOT NULL,
  rma_last_updated_date DATETIME NULL,
  rma_title VARCHAR(64) NOT NULL,
  rma_rank TINYINT(2) UNSIGNED NOT NULL,
  PRIMARY KEY(rma_id)
);

DROP TABLE IF EXISTS %TABLE_PREFIX%reminder_action_list;
CREATE TABLE %TABLE_PREFIX%reminder_action_list (
  ral_rma_id INT(11) UNSIGNED NOT NULL,
  ral_email VARCHAR(255) NOT NULL,
  ral_usr_id INT(11) UNSIGNED NOT NULL
);

DROP TABLE IF EXISTS %TABLE_PREFIX%reminder_action_type;
CREATE TABLE %TABLE_PREFIX%reminder_action_type (
  rmt_id TINYINT(3) UNSIGNED NOT NULL AUTO_INCREMENT,
  rmt_type VARCHAR(32) NOT NULL,
  rmt_title VARCHAR(64) NOT NULL,
  PRIMARY KEY(rmt_id),
  UNIQUE INDEX rmt_type (rmt_type),
  UNIQUE INDEX rmt_title (rmt_title)
);
INSERT INTO %TABLE_PREFIX%reminder_action_type (rmt_type, rmt_title) VALUES ('email_assignee', 'Send Email Alert to Assignee');
INSERT INTO %TABLE_PREFIX%reminder_action_type (rmt_type, rmt_title) VALUES ('sms_assignee', 'Send SMS Alert to Assignee');
INSERT INTO %TABLE_PREFIX%reminder_action_type (rmt_type, rmt_title) VALUES ('email_list', 'Send Email Alert To...');
INSERT INTO %TABLE_PREFIX%reminder_action_type (rmt_type, rmt_title) VALUES ('sms_list', 'Send SMS Alert To...');

DROP TABLE IF EXISTS %TABLE_PREFIX%reminder_level_condition;
CREATE TABLE %TABLE_PREFIX%reminder_level_condition (
  rlc_id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  rlc_rma_id INT(11) UNSIGNED NOT NULL,
  rlc_rmf_id TINYINT(3) UNSIGNED NOT NULL,
  rlc_rmo_id TINYINT(1) UNSIGNED NOT NULL,
  rlc_created_date DATETIME NOT NULL,
  rlc_last_updated_date DATETIME NULL,
  rlc_value VARCHAR(64) NOT NULL,
  PRIMARY KEY(rlc_id)
);

DROP TABLE IF EXISTS %TABLE_PREFIX%reminder_field;
CREATE TABLE %TABLE_PREFIX%reminder_field (
  rmf_id TINYINT(3) UNSIGNED NOT NULL AUTO_INCREMENT,
  rmf_title VARCHAR(128) NOT NULL,
  rmf_sql_field VARCHAR(32) NOT NULL,
  rmf_sql_representation VARCHAR(255) NOT NULL,
  PRIMARY KEY(rmf_id),
  UNIQUE INDEX rmf_title(rmf_title)
);
INSERT INTO %TABLE_PREFIX%reminder_field (rmf_title, rmf_sql_field, rmf_sql_representation) VALUES ('Status', 'iss_sta_id', 'iss_sta_id');
INSERT INTO %TABLE_PREFIX%reminder_field (rmf_title, rmf_sql_field, rmf_sql_representation) VALUES ('Last Response Date', 'iss_last_response_date', '(UNIX_TIMESTAMP() - IFNULL(UNIX_TIMESTAMP(iss_last_response_date), 0))');
INSERT INTO %TABLE_PREFIX%reminder_field (rmf_title, rmf_sql_field, rmf_sql_representation) VALUES ('Last Update Date', 'iss_updated_date', '(UNIX_TIMESTAMP() - IFNULL(UNIX_TIMESTAMP(iss_updated_date), 0))');
INSERT INTO %TABLE_PREFIX%reminder_field (rmf_title, rmf_sql_field, rmf_sql_representation) VALUES ('Created Date', 'iss_created_date', '(UNIX_TIMESTAMP() - IFNULL(UNIX_TIMESTAMP(iss_created_date), 0))');
INSERT INTO %TABLE_PREFIX%reminder_field (rmf_title, rmf_sql_field, rmf_sql_representation) VALUES ('First Response Date', 'iss_first_response_date', '(UNIX_TIMESTAMP() - IFNULL(UNIX_TIMESTAMP(iss_first_response_date), 0))');
INSERT INTO %TABLE_PREFIX%reminder_field (rmf_title, rmf_sql_field, rmf_sql_representation) VALUES ('Closed Date', 'iss_closed_date', '(UNIX_TIMESTAMP() - IFNULL(UNIX_TIMESTAMP(iss_closed_date), 0))');
INSERT INTO %TABLE_PREFIX%reminder_field (rmf_title, rmf_sql_field, rmf_sql_representation) VALUES ('Category', 'iss_prc_id', 'iss_prc_id');

DROP TABLE IF EXISTS %TABLE_PREFIX%reminder_operator;
CREATE TABLE %TABLE_PREFIX%reminder_operator (
  rmo_id TINYINT(1) UNSIGNED NOT NULL AUTO_INCREMENT,
  rmo_title VARCHAR(32) NULL,
  rmo_sql_representation VARCHAR(32) NULL,
  PRIMARY KEY(rmo_id),
  UNIQUE INDEX rmo_title(rmo_title)
);
INSERT INTO %TABLE_PREFIX%reminder_operator (rmo_title, rmo_sql_representation) VALUES ('equal to', '=');
INSERT INTO %TABLE_PREFIX%reminder_operator (rmo_title, rmo_sql_representation) VALUES ('not equal to', '<>');
INSERT INTO %TABLE_PREFIX%reminder_operator (rmo_title, rmo_sql_representation) VALUES ('is', 'IS');
INSERT INTO %TABLE_PREFIX%reminder_operator (rmo_title, rmo_sql_representation) VALUES ('is not', 'IS NOT');
INSERT INTO %TABLE_PREFIX%reminder_operator (rmo_title, rmo_sql_representation) VALUES ('greater than', '>');
INSERT INTO %TABLE_PREFIX%reminder_operator (rmo_title, rmo_sql_representation) VALUES ('less than', '<');
INSERT INTO %TABLE_PREFIX%reminder_operator (rmo_title, rmo_sql_representation) VALUES ('greater or equal than', '>=');
INSERT INTO %TABLE_PREFIX%reminder_operator (rmo_title, rmo_sql_representation) VALUES ('less or equal than', '<=');

DROP TABLE IF EXISTS %TABLE_PREFIX%news;
CREATE TABLE %TABLE_PREFIX%news (
  nws_id int(11) unsigned NOT NULL auto_increment,
  nws_usr_id int(11) unsigned NOT NULL,
  nws_created_date datetime NOT NULL,
  nws_title varchar(255) NOT NULL,
  nws_message text NOT NULL,
  nws_status varchar(8) NOT NULL default 'active',
  PRIMARY KEY (nws_id),
  UNIQUE KEY nws_title (nws_title)
);

DROP TABLE IF EXISTS %TABLE_PREFIX%project_news;
CREATE TABLE %TABLE_PREFIX%project_news (
  prn_nws_id int(11) unsigned NOT NULL,
  prn_prj_id int(11) unsigned NOT NULL,
  PRIMARY KEY (prn_prj_id, prn_nws_id)
);

DROP TABLE IF EXISTS %TABLE_PREFIX%project_round_robin;
CREATE TABLE %TABLE_PREFIX%project_round_robin (
  prr_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  prr_prj_id INT(11) UNSIGNED NOT NULL,
  prr_blackout_start TIME NOT NULL,
  prr_blackout_end TIME NOT NULL,
  PRIMARY KEY (prr_id),
  UNIQUE KEY prr_prj_id (prr_prj_id)
);

DROP TABLE IF EXISTS %TABLE_PREFIX%round_robin_user;
CREATE TABLE %TABLE_PREFIX%round_robin_user (
  rru_prr_id INT(11) UNSIGNED NOT NULL,
  rru_usr_id INT(11) UNSIGNED NOT NULL,
  rru_next TINYINT(1) UNSIGNED NULL
);

DROP TABLE IF EXISTS %TABLE_PREFIX%email_draft;
CREATE TABLE %TABLE_PREFIX%email_draft (
  emd_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  emd_usr_id INT(11) UNSIGNED NOT NULL,
  emd_iss_id INT(11) unsigned NOT NULL,
  emd_sup_id INT(11) UNSIGNED NULL DEFAULT NULL,
  emd_updated_date DATETIME NOT NULL,
  emd_subject VARCHAR(255) NOT NULL,
  emd_body LONGTEXT NOT NULL,
  PRIMARY KEY(emd_id)
);

DROP TABLE IF EXISTS %TABLE_PREFIX%email_draft_recipient;
CREATE TABLE %TABLE_PREFIX%email_draft_recipient (
  edr_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  edr_emd_id INT(11) UNSIGNED NOT NULL,
  edr_is_cc TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  edr_email VARCHAR(255) NOT NULL,
  PRIMARY KEY(edr_id)
);

