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
    KEY htt_name (htt_name),
    unique(htt_name)
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