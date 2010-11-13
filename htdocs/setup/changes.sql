DROP TABLE IF EXISTS eventum_project_round_robin;
CREATE TABLE eventum_project_round_robin (
  prr_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  prr_prj_id INT(11) UNSIGNED NOT NULL,
  prr_blackout_start TIME NOT NULL,
  prr_blackout_end TIME NOT NULL,
  PRIMARY KEY (prr_id),
  UNIQUE KEY prr_prj_id (prr_prj_id)
);

DROP TABLE IF EXISTS eventum_round_robin_user;
CREATE TABLE eventum_round_robin_user (
  rru_prr_id INT(11) UNSIGNED NOT NULL,
  rru_usr_id INT(11) UNSIGNED NOT NULL,
  rru_next TINYINT(1) UNSIGNED NULL
);

ALTER TABLE eventum_support_email DROP COLUMN sup_draft_response;

CREATE TABLE eventum_email_draft (
  emd_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  emd_usr_id INT(11) UNSIGNED NOT NULL,
  emd_iss_id INT(11) unsigned NOT NULL,
  emd_sup_id INT(11) UNSIGNED NULL DEFAULT NULL,
  emd_updated_date DATETIME NOT NULL,
  emd_subject VARCHAR(255) NOT NULL,
  emd_body LONGTEXT NOT NULL,
  PRIMARY KEY(emd_id)
);

CREATE TABLE eventum_email_draft_recipient (
  edr_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  edr_emd_id INT(11) UNSIGNED NOT NULL,
  edr_is_cc TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  edr_email VARCHAR(255) NOT NULL,
  PRIMARY KEY(edr_id)
);

ALTER TABLE eventum_note ADD COLUMN not_blocked_message longtext NULL;

# february 24

ALTER TABLE eventum_email_account ADD COLUMN ema_get_only_new int(1) NOT NULL DEFAULT 0;
ALTER TABLE eventum_email_account ADD COLUMN ema_leave_copy int(1) NOT NULL DEFAULT 0;

# march 1

ALTER TABLE eventum_news ADD COLUMN nws_status varchar(8) NOT NULL default 'active';

ALTER TABLE eventum_note ADD COLUMN not_title varchar(255) NOT NULL;
ALTER TABLE eventum_note ADD COLUMN not_parent_id int(11) unsigned NULL;

# march 8

ALTER TABLE eventum_reminder_level ADD COLUMN rem_rank TINYINT(1) NOT NULL;
INSERT INTO eventum_reminder_field (rmf_title, rmf_sql_field, rmf_sql_representation) VALUES ('Category', 'iss_prc_id', 'iss_prc_id');
ALTER TABLE eventum_issue ADD COLUMN iss_expected_resolution_date date default NULL;
ALTER TABLE eventum_status ADD COLUMN sta_abbreviation char(3) NOT NULL;
ALTER TABLE eventum_status ADD UNIQUE KEY sta_abbreviation (sta_abbreviation);

# march 15

DROP TABLE IF EXISTS eventum_reminder_action_list;
CREATE TABLE eventum_reminder_action_list (
  ral_rma_id INT(11) UNSIGNED NOT NULL,
  ral_email VARCHAR(255) NOT NULL,
  ral_usr_id INT(11) UNSIGNED NOT NULL
);

INSERT INTO eventum_reminder_action_type (rmt_type, rmt_title) VALUES ('email_list', 'Send Email Alert To...');
INSERT INTO eventum_reminder_action_type (rmt_type, rmt_title) VALUES ('sms_list', 'Send SMS Alert To...');

DROP TABLE IF EXISTS eventum_irc_notice;
CREATE TABLE eventum_irc_notice (
  ino_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  ino_prj_id INT(11) UNSIGNED NOT NULL,
  ino_created_date DATETIME NOT NULL,
  ino_message VARCHAR(255) NOT NULL,
  ino_status VARCHAR(8) NOT NULL DEFAULT 'pending',
  PRIMARY KEY(ino_id)
);



# April 8th, Issue #408, bryan
ALTER TABLE eventum_email_draft ADD COLUMN emd_unknown_user VARCHAR(255) NULL DEFAULT NULL;

ALTER TABLE eventum_note ADD COLUMN not_unknown_user VARCHAR(255) NULL DEFAULT NULL;

ALTER TABLE eventum_issue_attachment ADD column iat_unknown_user varchar(255) NULL DEFAULT NULL;

ALTER TABLE eventum_email_draft ADD COLUMN emd_updated_date DATETIME NOT NULL;

# after cancun

ALTER TABLE eventum_irc_notice ADD COLUMN ino_iss_id INT(11) UNSIGNED NOT NULL;
ALTER TABLE eventum_irc_notice DROP COLUMN ino_prj_id;
UPDATE eventum_irc_notice SET ino_iss_id=substring(ino_message, LOCATE('#', ino_message)+1, 4);


CREATE TABLE eventum_issue_user_replier (
  iur_iss_id int(10) unsigned NOT NULL default '0',
  iur_usr_id int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (iur_iss_id,iur_usr_id),
  KEY iur_usr_id (iur_usr_id),
  KEY iur_iss_id (iur_iss_id)
);

ALTER TABLE eventum_custom_filter ADD COLUMN cst_show_authorized char(3) default '';
ALTER TABLE eventum_custom_filter ADD COLUMN cst_show_notification_list char(3) default '';


# Issue 158, weekly reports. This issue turned into changing the history system.
# please run script misc/runonce/set_history_type.php after applying these changes.

# lookup table for history type
CREATE TABLE eventum_history_type (
    htt_id tinyint(2) unsigned NOT NULL auto_increment,
    htt_name varchar(25) NOT NULL,
    PRIMARY KEY(htt_id),
    UNIQUE (htt_name)
);
INSERT INTO eventum_history_type SET htt_name = 'attachment_removed';
INSERT INTO eventum_history_type SET htt_name = 'attachment_added';
INSERT INTO eventum_history_type SET htt_name = 'custom_field_updated';
INSERT INTO eventum_history_type SET htt_name = 'draft_added';
INSERT INTO eventum_history_type SET htt_name = 'draft_updated';
INSERT INTO eventum_history_type SET htt_name = 'impact_analysis_added';
INSERT INTO eventum_history_type SET htt_name = 'impact_analysis_updated';
INSERT INTO eventum_history_type SET htt_name = 'impact_analysis_removed';
INSERT INTO eventum_history_type SET htt_name = 'status_changed';
INSERT INTO eventum_history_type SET htt_name = 'remote_locked';
INSERT INTO eventum_history_type SET htt_name = 'remote_status_change';
INSERT INTO eventum_history_type SET htt_name = 'remote_unlock';
INSERT INTO eventum_history_type SET htt_name = 'remote_assigned';
INSERT INTO eventum_history_type SET htt_name = 'remote_replier_added';
INSERT INTO eventum_history_type SET htt_name = 'details_updated';
INSERT INTO eventum_history_type SET htt_name = 'issue_opened';
INSERT INTO eventum_history_type SET htt_name = 'issue_auto_assigned';
INSERT INTO eventum_history_type SET htt_name = 'rr_issue_assigned';
INSERT INTO eventum_history_type SET htt_name = 'issue_locked';
INSERT INTO eventum_history_type SET htt_name = 'issue_unlocked';
INSERT INTO eventum_history_type SET htt_name = 'duplicate_update';
INSERT INTO eventum_history_type SET htt_name = 'duplicate_removed';
INSERT INTO eventum_history_type SET htt_name = 'duplicate_added';
INSERT INTO eventum_history_type SET htt_name = 'issue_opened_anon';
INSERT INTO eventum_history_type SET htt_name = 'remote_issue_created';
INSERT INTO eventum_history_type SET htt_name = 'issue_closed';
INSERT INTO eventum_history_type SET htt_name = 'issue_updated';
INSERT INTO eventum_history_type SET htt_name = 'user_associated';
INSERT INTO eventum_history_type SET htt_name = 'user_all_unassociated';
INSERT INTO eventum_history_type SET htt_name = 'replier_added';
INSERT INTO eventum_history_type SET htt_name = 'remote_note_added';
INSERT INTO eventum_history_type SET htt_name = 'note_added';
INSERT INTO eventum_history_type SET htt_name = 'note_removed';
INSERT INTO eventum_history_type SET htt_name = 'note_converted_draft';
INSERT INTO eventum_history_type SET htt_name = 'note_converted_email';
INSERT INTO eventum_history_type SET htt_name = 'notification_removed';
INSERT INTO eventum_history_type SET htt_name = 'notification_added';
INSERT INTO eventum_history_type SET htt_name = 'notification_updated';
INSERT INTO eventum_history_type SET htt_name = 'phone_entry_added';
INSERT INTO eventum_history_type SET htt_name = 'phone_entry_removed';
INSERT INTO eventum_history_type SET htt_name = 'scm_checkin_removed';
INSERT INTO eventum_history_type SET htt_name = 'email_associated';
INSERT INTO eventum_history_type SET htt_name = 'email_disassociated';
INSERT INTO eventum_history_type SET htt_name = 'email_sent';
INSERT INTO eventum_history_type SET htt_name = 'time_added';
INSERT INTO eventum_history_type SET htt_name = 'time_removed';
INSERT INTO eventum_history_type SET htt_name = 'remote_time_added';
INSERT INTO eventum_history_type SET htt_name = 'email_blocked';
INSERT INTO eventum_history_type SET htt_name = 'email_routed';
INSERT INTO eventum_history_type SET htt_name = 'note_routed';


ALTER TABLE eventum_issue_history ADD COLUMN his_usr_id int(11) UNSIGNED NOT NULL AFTER his_iss_id;
ALTER TABLE eventum_issue_history ADD COLUMN his_htt_id varchar(20) NOT NULL;





# Allowing authorized repliers not be real users
ALTER TABLE eventum_issue_user_replier DROP PRIMARY KEY;
ALTER TABLE eventum_issue_user_replier ADD column iur_id int(11) unsigned NOT NULL auto_increment FIRST, ADD PRIMARY KEY(iur_id);
ALTER TABLE eventum_issue_user_replier ADD COLUMN iur_email varchar(255) NULL;


INSERT INTO eventum_history_type SET htt_name = 'replier_removed';
INSERT INTO eventum_history_type SET htt_name = 'replier_other_added';

# mail_queue changes
CREATE TABLE eventum_mail_queue (
  maq_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  maq_queued_date DATETIME NOT NULL,
  maq_status VARCHAR(8) NOT NULL DEFAULT 'pending',
  maq_save_copy TINYINT(1) NOT NULL DEFAULT 1,
  maq_sender_ip_address VARCHAR(15) NOT NULL,
  maq_recipient VARCHAR(255) NOT NULL,
  maq_headers TEXT NOT NULL,
  maq_body LONGTEXT NOT NULL,
  KEY maq_status (maq_status),
  PRIMARY KEY(maq_id)
);

CREATE TABLE eventum_mail_queue_log (
  mql_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  mql_maq_id INT(11) UNSIGNED NOT NULL,
  mql_created_date DATETIME NOT NULL,
  mql_status VARCHAR(8) NOT NULL DEFAULT 'error',
  mql_server_message TEXT NULL,
  KEY mql_maq_id (mql_maq_id),
  PRIMARY KEY(mql_id)
);

INSERT INTO eventum_history_type SET htt_name = 'issue_associated';
INSERT INTO eventum_history_type SET htt_name = 'issue_all_unassociated';

# more weekly report related items
ALTER TABLE eventum_issue_history ADD COLUMN his_is_hidden tinyint(1) NOT NULL DEFAULT 0;

ALTER TABLE eventum_issue_user ADD COLUMN isu_assigned_date datetime;

INSERT INTO eventum_history_type SET htt_name = 'user_unassociated';
INSERT INTO eventum_history_type SET htt_name = 'issue_unassociated';

# may 25

ALTER TABLE eventum_issue ADD COLUMN iss_trigger_reminders tinyint(1) default 1;

# june 2

UPDATE eventum_user SET usr_email='system-account@example.com' WHERE usr_id=1;

DROP TABLE IF EXISTS eventum_project_status_date;
CREATE TABLE eventum_project_status_date (
  psd_id INT(11) UNSIGNED NOT NULL auto_increment,
  psd_prj_id INT(11) UNSIGNED NOT NULL,
  psd_sta_id INT(10) UNSIGNED NOT NULL,
  psd_date_field VARCHAR(64) NOT NULL,
  psd_label VARCHAR(32) NOT NULL,
  PRIMARY KEY (psd_id),
  UNIQUE KEY (psd_prj_id, psd_sta_id)
);

# june 7

DROP TABLE IF EXISTS eventum_support_email_body;
CREATE TABLE eventum_support_email_body (
  seb_sup_id int(11) unsigned NOT NULL,
  seb_body longtext NOT NULL,
  seb_full_email longtext NOT NULL,
  PRIMARY KEY (seb_sup_id)
);
INSERT INTO eventum_support_email_body (SELECT sup_id, sup_body, sup_full_email FROM eventum_support_email);
# Run the next 2 lines ONLY after you have run the above line and check that eventum_support_email_body has the data correctly.
# ALTER TABLE eventum_support_email DROP COLUMN sup_body;
# ALTER TABLE eventum_support_email DROP COLUMN sup_full_email;

# june 8
ALTER TABLE eventum_support_email ADD COLUMN sup_usr_id int(11) unsigned DEFAULT NULL AFTER sup_iss_id;
ALTER TABLE eventum_support_email ADD KEY sup_usr_id(sup_usr_id);

# please run /misc/upgrade/v1.1_to_v1.2/set_support_email_usr_id.php

ALTER TABLE eventum_email_account ADD COLUMN ema_issue_auto_creation varchar(8) NOT NULL DEFAULT 'disabled';
ALTER TABLE eventum_email_account ADD COLUMN ema_issue_auto_creation_options text;




# eventum 1.3!!!!!!!!!!!!!!!!!!!!!!!!!!!!!11111111111

UPDATE eventum_user SET usr_role=usr_role+2 WHERE usr_role>3;
UPDATE eventum_user SET usr_role=4 WHERE usr_role=3;

ALTER TABLE eventum_project ADD COLUMN prj_customer_backend varchar(64) NULL;
ALTER TABLE eventum_custom_filter ADD COLUMN cst_customer_email varchar(64) default NULL;
ALTER TABLE eventum_issue ADD COLUMN iss_customer_id int(11) unsigned NULL;
ALTER TABLE eventum_issue ADD COLUMN iss_customer_contact_id int(11) unsigned NULL;
ALTER TABLE eventum_issue ADD COLUMN iss_last_customer_action_date datetime default NULL;
ALTER TABLE eventum_support_email ADD COLUMN sup_customer_id int(11) unsigned NULL;

ALTER TABLE eventum_user ADD COLUMN usr_customer_id int(11) unsigned NULL default NULL;
ALTER TABLE eventum_user ADD COLUMN usr_customer_contact_id int(11) unsigned NULL default NULL;

ALTER TABLE eventum_user ADD COLUMN usr_clocked_in tinyint(1) DEFAULT 0;

DROP TABLE IF EXISTS eventum_customer_note;
create table eventum_customer_note (
    cno_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    cno_prj_id int(11) unsigned NOT NULL,
    cno_customer_id INT(11) UNSIGNED NOT NULL,
    cno_created_date DATETIME NOT NULL,
    cno_updated_date DATETIME NULL,
    cno_note TEXT,
    primary key(cno_id),
    unique(cno_prj_id, cno_customer_id)
);

DROP TABLE IF EXISTS eventum_customer_account_manager;
CREATE TABLE eventum_customer_account_manager (
  cam_id int(11) unsigned NOT NULL auto_increment,
  cam_prj_id int(11) unsigned NOT NULL,
  cam_customer_id int(11) unsigned NOT NULL,
  cam_usr_id int(11) unsigned NOT NULL,
  cam_type varchar(7) NOT NULL,
  PRIMARY KEY (cam_id),
  KEY cam_customer_id (cam_customer_id),
  UNIQUE KEY cam_manager (cam_prj_id, cam_customer_id, cam_usr_id)
);


ALTER TABLE eventum_project ADD COLUMN prj_workflow_backend varchar(64) NULL DEFAULT NULL;

CREATE TABLE eventum_faq (
  faq_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  faq_prj_id INT(11) UNSIGNED NOT NULL,
  faq_usr_id INT(11) UNSIGNED NOT NULL,
  faq_created_date DATETIME NOT NULL,
  faq_updated_date DATETIME NULL,
  faq_title VARCHAR(255) NOT NULL,
  faq_message LONGTEXT NOT NULL,
  PRIMARY KEY (faq_id),
  UNIQUE KEY faq_title (faq_title)
);

CREATE TABLE eventum_faq_support_level (
  fsl_faq_id INT(11) UNSIGNED NOT NULL,
  fsl_support_level_id INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (fsl_faq_id, fsl_support_level_id)
);

ALTER TABLE eventum_reminder_requirement ADD COLUMN rer_support_level_id INT(11) UNSIGNED NULL;
ALTER TABLE eventum_reminder_requirement ADD COLUMN rer_customer_id INT(11) UNSIGNED NULL;

CREATE TABLE eventum_project_field_display (
  pfd_prj_id int(11) unsigned NOT NULL,
  pfd_field varchar(20) NOT NULL,
  pfd_min_role tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (pfd_prj_id, pfd_field)
);





# August 17
CREATE TABLE eventum_issue_quarantine (
    iqu_iss_id int(11) unsigned auto_increment,
    iqu_expiration datetime NULL,
    iqu_status tinyint(1),
    PRIMARY KEY(iqu_iss_id),
    INDEX(iqu_expiration)
);

# august 18

ALTER TABLE eventum_custom_filter ADD COLUMN cst_is_global int(1) default 0;

# August 19th
ALTER TABLE eventum_mail_queue ADD COLUMN maq_iss_id int(11) unsigned AFTER maq_id;
ALTER TABLE eventum_mail_queue ADD COLUMN maq_subject varchar(255) NOT NULL AFTER maq_recipient;
ALTER TABLE eventum_mail_queue ADD INDEX maq_iss_id (maq_iss_id);

# August 23rd
CREATE TABLE eventum_group (
    grp_id int(11) unsigned auto_increment,
    grp_name varchar(100) unique,
    grp_description varchar(255),
    grp_manager_usr_id int(11) unsigned,
    PRIMARY KEY(grp_id)
);

CREATE TABLE eventum_project_group (
    pgr_prj_id int(11)  unsigned,
    pgr_grp_id int(11) unsigned,
    index(pgr_prj_id),
    index(pgr_grp_id)
);

ALTER TABLE eventum_user ADD COLUMN usr_grp_id int(11) unsigned NULL default NULL AFTER usr_id;
ALTER TABLE eventum_user ADD INDEX(usr_grp_id);

ALTER TABLE eventum_issue ADD COLUMN iss_grp_id int(11) unsigned NULL default NULL AFTER iss_usr_id;
ALTER TABLE eventum_issue ADD INDEX(iss_grp_id);

INSERT INTO eventum_history_type SET htt_name = 'group_changed';


# august 24th
ALTER TABLE eventum_priority RENAME eventum_project_priority;
ALTER TABLE eventum_project_priority CHANGE column pri_id pri_id tinyint(1) unsigned NOT NULL default '0' auto_increment;
ALTER TABLE eventum_project_priority ADD COLUMN pri_prj_id int(11) unsigned NOT NULL;
ALTER TABLE eventum_project_priority DROP PRIMARY KEY;
ALTER TABLE eventum_project_priority ADD PRIMARY KEY(pri_id);
ALTER TABLE eventum_project_priority DROP KEY pri_id;
ALTER TABLE eventum_project_priority DROP KEY pri_id_2;
ALTER TABLE eventum_project_priority ADD KEY(pri_title);
ALTER TABLE eventum_project_priority ADD UNIQUE(pri_prj_id, pri_title);

CREATE TABLE eventum_project_email_response (
  per_prj_id int(11) unsigned NOT NULL,
  per_ere_id int(10) unsigned NOT NULL,
  PRIMARY KEY (per_prj_id, per_ere_id)
);


CREATE TABLE eventum_project_phone_category (
  phc_id int(11) unsigned NOT NULL auto_increment,
  phc_prj_id int(11) unsigned NOT NULL default '0',
  phc_title varchar(64) NOT NULL default '',
  PRIMARY KEY  (phc_id),
  UNIQUE KEY uniq_category (phc_prj_id,phc_title),
  KEY phc_prj_id (phc_prj_id)
);
INSERT INTO eventum_project_phone_category (phc_id, phc_prj_id, phc_title) VALUES (1, 1, 'Sales Issues');
INSERT INTO eventum_project_phone_category (phc_id, phc_prj_id, phc_title) VALUES (2, 1, 'Technical Issues');
INSERT INTO eventum_project_phone_category (phc_id, phc_prj_id, phc_title) VALUES (3, 1, 'Administrative Issues');
INSERT INTO eventum_project_phone_category (phc_id, phc_prj_id, phc_title) VALUES (4, 1, 'Other');

ALTER TABLE eventum_phone_support ADD COLUMN phs_phc_id int(11) unsigned NOT NULL;

# fix old values
UPDATE eventum_phone_support SET phs_phc_id=1 WHERE phs_reason='sales';
UPDATE eventum_phone_support SET phs_phc_id=2 WHERE phs_reason='technical';
UPDATE eventum_phone_support SET phs_phc_id=3 WHERE phs_reason='administrative';
UPDATE eventum_phone_support SET phs_phc_id=4 WHERE phs_reason='other';

# check if everything is correct
# SELECT DISTINCT phs_reason, COUNT(*) total FROM eventum_phone_support GROUP BY phs_reason;
ALTER TABLE eventum_phone_support DROP COLUMN phs_reason;

ALTER TABLE eventum_reminder_action ADD COLUMN rma_alert_irc TINYINT(1) unsigned NOT NULL DEFAULT 0;


# August 31st
ALTER TABLE eventum_issue ADD COLUMN iss_last_public_action_date datetime NULL;
ALTER TABLE eventum_issue ADD COLUMN iss_last_public_action_type varchar(20) NULL;
ALTER TABLE eventum_issue ADD COLUMN iss_last_internal_action_date datetime NULL;
ALTER TABLE eventum_issue ADD COLUMN iss_last_internal_action_type varchar(20) NULL;

ALTER TABLE eventum_reminder_action ADD COLUMN rma_alert_group_leader TINYINT(1) unsigned NOT NULL DEFAULT 0;


# september 2nd
ALTER TABLE eventum_project_user DROP KEY pru_prj_id;
ALTER TABLE eventum_project_user ADD UNIQUE KEY pru_prj_id (pru_prj_id,pru_usr_id);


# september 3rd
ALTER TABLE eventum_history_type ADD COLUMN htt_role tinyint(1) DEFAULT '0';
UPDATE eventum_history_type SET htt_role = 4 WHERE htt_name IN('note_added', 'note_removed', 'note_converted_draft',
    'note_converted_email', 'phone_entry_added', 'phone_entry_removed', 'time_added', 'time_removed',
    'remote_time_added', 'email_blocked', 'note_routed', 'group_changed', 'draft_added', 'draft_updated');
INSERT INTO eventum_history_type SET htt_name = 'status_auto_changed', htt_role = 4;


CREATE TABLE eventum_reminder_triggered_action (
  rta_iss_id int(11) unsigned not null,
  rta_rma_id int(11) unsigned not null,
  PRIMARY KEY (rta_iss_id)
);

# september 24th
INSERT INTO eventum_history_type SET htt_name = 'issue_quarantine_removed', htt_role = 4;

ALTER TABLE eventum_issue DROP COLUMN iss_lock_usr_id;

# september 28th
DROP TABLE IF EXISTS eventum_link_filter;
CREATE TABLE eventum_link_filter (
  lfi_id int(11) unsigned NOT NULL auto_increment,
  lfi_pattern varchar(255) NOT NULL,
  lfi_replacement varchar(255) NOT NULL,
  lfi_usr_role tinyint(9) NOT NULL DEFAULT 0,
  lfi_description varchar(255) NULL,
  PRIMARY KEY  (lfi_id)
);

DROP TABLE IF EXISTS eventum_project_link_filter;
CREATE TABLE eventum_project_link_filter (
  plf_prj_id int(11) NOT NULL,
  plf_lfi_id int(11) NOT NULL,
  PRIMARY KEY  (plf_prj_id, plf_lfi_id)
);

# October 4th
ALTER TABLE eventum_irc_notice ADD COLUMN ino_prj_id int(11) NOT NULL;

# October 7th
ALTER TABLE eventum_reminder_field ADD column rmf_allow_column_compare tinyint(1) DEFAULT 0;
UPDATE eventum_reminder_field SET rmf_allow_column_compare = 1 WHERE rmf_title LIKE '%date%';
ALTER TABLE eventum_reminder_level_condition ADD COLUMN rlc_comparison_rmf_id tinyint(3) unsigned;

# October 14th
ALTER TABLE eventum_reminder_level ADD COLUMN rem_skip_weekend tinyint(1) NOT NULL DEFAULT 0;

# October 18th
ALTER TABLE eventum_custom_field ADD COLUMN fld_list_display tinyint(1) NOT NULL DEFAULT 0;

# October 22nd
INSERT INTO eventum_history_type SET htt_name = 'draft_routed', htt_role = 4;

# November 8th
DROP TABLE IF EXISTS eventum_columns_to_display;
CREATE TABLE eventum_columns_to_display (
    ctd_prj_id int(11) unsigned NOT NULL,
    ctd_page varchar(20) NOT NULL,
    ctd_field varchar(30) NOT NULL,
    ctd_min_role tinyint(1) NOT NULL DEFAULT 0,
    ctd_rank tinyint(2) NOT NULL DEFAULT 0,
    PRIMARY KEY(ctd_prj_id, ctd_page, ctd_field),
    INDEX(ctd_prj_id, ctd_page)
);


INSERT INTO eventum_columns_to_display VALUES (1,'list_issues','iss_pri_id',1,1);
INSERT INTO eventum_columns_to_display VALUES (1,'list_issues','iss_id',1,2);
INSERT INTO eventum_columns_to_display VALUES (1,'list_issues','iss_grp_id',1,3);
INSERT INTO eventum_columns_to_display VALUES (1,'list_issues','assigned',1,4);
INSERT INTO eventum_columns_to_display VALUES (1,'list_issues','time_spent',1,5);
INSERT INTO eventum_columns_to_display VALUES (1,'list_issues','prc_title',1,6);
INSERT INTO eventum_columns_to_display VALUES (1,'list_issues','pre_title',1,7);
INSERT INTO eventum_columns_to_display VALUES (1,'list_issues','iss_customer_id',1,8);
INSERT INTO eventum_columns_to_display VALUES (1,'list_issues','iss_sta_id',1,9);
INSERT INTO eventum_columns_to_display VALUES (1,'list_issues','sta_change_date',1,10);
INSERT INTO eventum_columns_to_display VALUES (1,'list_issues','last_action_date',1,11);
INSERT INTO eventum_columns_to_display VALUES (1,'list_issues','custom_fields',1,12);
INSERT INTO eventum_columns_to_display VALUES (1,'list_issues','iss_summary',1,13);

# November 24
INSERT INTO eventum_history_type (htt_name, htt_role) VALUES ('incident_redeemed', 4);
INSERT INTO eventum_history_type (htt_name, htt_role) VALUES ('incident_unredeemed', 4);


# December 4th


# December 4th
ALTER TABLE eventum_custom_filter ADD COLUMN cst_created_date_time_period smallint(4) AFTER cst_created_date_filter_type;
ALTER TABLE eventum_custom_filter ADD COLUMN cst_updated_date_time_period smallint(4) AFTER cst_updated_date_filter_type;
ALTER TABLE eventum_custom_filter ADD COLUMN cst_last_response_date_time_period smallint(4) AFTER cst_last_response_date_filter_type;
ALTER TABLE eventum_custom_filter ADD COLUMN cst_first_response_date_time_period smallint(4) AFTER cst_first_response_date_filter_type;
ALTER TABLE eventum_custom_filter ADD COLUMN cst_closed_date_time_period smallint(4) AFTER cst_closed_date_filter_type;

# December 28th
UPDATE eventum_user SET usr_status = 'inactive' WHERE usr_id = 1;



# January 6th
ALTER TABLE eventum_project_user ADD COLUMN pru_role tinyint(1) unsigned default 1;

ALTER TABLE eventum_user DROP column usr_role;

# January 23th
ALTER TABLE eventum_email_draft ADD COLUMN emd_status enum('pending', 'edited', 'sent') NOT NULL DEFAULT 'pending' AFTER emd_sup_id;

# January 26th
ALTER TABLE eventum_project ADD COLUMN prj_segregate_reporter tinyint(1) DEFAULT 0;

ALTER TABLE eventum_issue ADD COLUMN iss_private tinyint(1) NOT NULL DEFAULT 0;

# February 16th
UPDATE eventum_reminder_field SET rmf_allow_column_compare = 0 WHERE rmf_title='Status';

INSERT INTO eventum_history_type (htt_id, htt_name, htt_role) VALUES (NULL, 'scm_checkin_associated', 0);

ALTER TABLE eventum_project_priority ADD COLUMN pri_rank TINYINT(1) NOT NULL;
UPDATE eventum_columns_to_display SET ctd_field='pri_rank' WHERE ctd_field='iss_pri_id';


# February 28th
ALTER TABLE eventum_mail_queue ADD COLUMN maq_type varchar(30) DEFAULT '';
ALTER TABLE eventum_mail_queue ADD COLUMN maq_usr_id int(11) unsigned NULL DEFAULT NULL;

# March 3rd
CREATE TABLE eventum_search_profile (
  sep_id int(11) unsigned NOT NULL auto_increment,
  sep_usr_id int(11) unsigned NOT NULL,
  sep_prj_id int(11) unsigned NOT NULL,
  sep_type char(5) NOT NULL,
  sep_user_profile blob NOT NULL,
  PRIMARY KEY (sep_id),
  UNIQUE (sep_usr_id, sep_prj_id, sep_type)
);

# March 3rd
ALTER TABLE eventum_issue ADD INDEX (iss_duplicated_iss_id);
ALTER TABLE eventum_time_tracking ADD INDEX (ttr_iss_id)


# March 7th
ALTER TABLE eventum_issue ADD COLUMN iss_percent_complete tinyint(3) unsigned DEFAULT 0;

# March 17th
ALTER TABLE eventum_email_account ADD column ema_use_routing tinyint(1) DEFAULT 0;

# April 20th
UPDATE eventum_columns_to_display SET ctd_field='sta_rank' WHERE ctd_field='iss_sta_id';


# May 23rd - FULL TEXT
CREATE FULLTEXT INDEX ft_issue ON eventum_issue (iss_summary, iss_description);
CREATE FULLTEXT INDEX ft_support_email ON eventum_support_email_body (seb_body);
CREATE FULLTEXT INDEX ft_note ON eventum_note (not_title,not_note);
CREATE FULLTEXT INDEX ft_time_tracking ON eventum_time_tracking (ttr_summary);
CREATE FULLTEXT INDEX ft_phone_support ON eventum_phone_support (phs_description);

ALTER TABLE eventum_time_tracking ADD INDEX ttr_iss_id(ttr_iss_id);

# July 27th - Custom field changes
ALTER TABLE eventum_custom_filter ADD COLUMN cst_custom_field TEXT;
ALTER TABLE eventum_custom_field ADD COLUMN fld_min_role tinyint(1) NOT NULL DEFAULT 0;
ALTER TABLE eventum_custom_field ADD COLUMN fld_rank smallint(2) NOT NULL DEFAULT 0;
ALTER TABLE eventum_custom_field ADD COLUMN fld_backend varchar(100);

ALTER TABLE eventum_custom_filter ADD COLUMN cst_search_type varchar(15) not null default 'customer';


CREATE FULLTEXT INDEX ft_icf_value ON eventum_issue_custom_field (icf_value);

# July 28th - Adding reporter to advanced search page
ALTER TABLE eventum_custom_filter ADD COLUMN cst_reporter int(11) unsigned DEFAULT NULL AFTER cst_users;

# July 29th
ALTER TABLE eventum_faq ADD COLUMN faq_rank TINYINT(2) UNSIGNED NOT NULL;
ALTER TABLE eventum_reminder_action ADD COLUMN rma_boilerplate varchar(255) DEFAULT NULL;
UPDATE eventum_reminder_action SET rma_boilerplate='Please take immediate action!';

# July 30th
INSERT INTO eventum_time_tracking_category (ttc_title, ttc_created_date) VALUES ('Note Discussion', now());

# Aug 17th
INSERT INTO eventum_history_type VALUES(null, 'issue_bulk_updated', 0);

# November 3rd
ALTER TABLE eventum_mail_queue ADD COLUMN maq_type_id int(11) unsigned default NULL;
ALTER TABLE eventum_mail_queue ADD INDEX (maq_type, maq_type_id);

ALTER TABLE eventum_issue ADD COLUMN iss_root_message_id varchar(255);

ALTER TABLE eventum_note ADD INDEX not_parent_id (not_parent_id);
ALTER TABLE eventum_note ADD COLUMN not_message_id varchar(255);
ALTER TABLE eventum_note ADD INDEX not_message_id (not_message_id);
ALTER TABLE eventum_note ADD COLUMN not_removed tinyint(1) NOT NULL DEFAULT 0;


ALTER TABLE eventum_issue_attachment ADD COLUMN iat_status enum('internal', 'public') NOT NULL default 'public';
ALTER TABLE eventum_issue_attachment ADD COLUMN iat_not_id int(11) unsigned DEFAULT NULL;

ALTER TABLE eventum_note ADD COLUMN not_has_attachment tinyint(1) NOT NULL default 0;

# May 12th
ALTER TABLE eventum_support_email CHANGE COLUMN sup_to sup_to text;
ALTER TABLE eventum_support_email CHANGE COLUMN sup_cc sup_cc text;

# October 2nd
ALTER TABLE eventum_user ADD COLUMN usr_lang varchar(5);


ALTER TABLE eventum_custom_field_option CHANGE COLUMN cfo_value cfo_value varchar(128) NOT NULL;


# March 16th (adding missing type)
INSERT INTO eventum_history_type SET htt_name = 'draft_routed',  htt_role = 4;


# May 22nd
ALTER TABLE eventum_irc_notice ADD INDEX ino_status (ino_status);

# June 21st
ALTER TABLE eventum_issue_custom_field ADD COLUMN icf_value_integer int(11) NULL DEFAULT NULL;
ALTER TABLE eventum_issue_custom_field ADD COLUMN icf_value_date date NULL DEFAULT NULL;

# October 6th
ALTER TABLE eventum_issue ADD COLUMN iss_customer_contract_id int(11) unsigned AFTER iss_customer_id;


##########################################
### From here down needs merging into schema.sql and sql patch system

# November 13th
INSERT INTO reminder_field VALUES (null, 'Group', 'iss_grp_id', 'iss_grp_id', 0);

ALTER TABLE resolution ADD COLUMN res_rank int(2) NOT NULL;



