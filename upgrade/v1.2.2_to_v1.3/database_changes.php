<?php
require_once dirname(__FILE__) . '/../init.php';


$changes = array();
$changes[] = "UPDATE eventum_user SET usr_role=4 WHERE usr_role=3";
$changes[] = "UPDATE eventum_user SET usr_role=usr_role+2 WHERE usr_role>3";
$changes[] = "ALTER TABLE eventum_project ADD COLUMN prj_customer_backend varchar(64) NULL";
$changes[] = "ALTER TABLE eventum_custom_filter ADD COLUMN cst_customer_email varchar(64) default NULL";
$changes[] = "ALTER TABLE eventum_issue ADD COLUMN iss_customer_id int(11) unsigned NULL";
$changes[] = "ALTER TABLE eventum_issue ADD COLUMN iss_customer_contact_id int(11) unsigned NULL";
$changes[] = "ALTER TABLE eventum_issue ADD COLUMN iss_last_customer_action_date datetime default NULL";
$changes[] = "ALTER TABLE eventum_support_email ADD COLUMN sup_customer_id int(11) unsigned NULL";
$changes[] = "ALTER TABLE eventum_user ADD COLUMN usr_customer_id int(11) unsigned NULL default NULL";
$changes[] = "ALTER TABLE eventum_user ADD COLUMN usr_customer_contact_id int(11) unsigned NULL default NULL";
$changes[] = "create table eventum_customer_note (
    cno_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    cno_customer_id INT(11) UNSIGNED NOT NULL,
    cno_created_date DATETIME NOT NULL,
    cno_updated_date DATETIME NULL,
    cno_note TEXT,
    primary key(cno_id),
    unique(cno_customer_id)
)";
$changes[] = "CREATE TABLE eventum_customer_account_manager (
  cam_id int(11) unsigned NOT NULL auto_increment,
  cam_prj_id int(11) unsigned NOT NULL,
  cam_customer_id int(11) unsigned NOT NULL,
  cam_usr_id int(11) unsigned NOT NULL,
  cam_type varchar(7) NOT NULL,
  PRIMARY KEY (cam_id),
  KEY cam_customer_id (cam_customer_id),
  UNIQUE KEY cam_manager (cam_customer_id, cam_usr_id)
)";
$changes[] = "ALTER TABLE eventum_user ADD COLUMN usr_clocked_in tinyint(1) DEFAULT 0";
$changes[] = "ALTER TABLE eventum_project ADD COLUMN prj_workflow_backend varchar(64) NULL DEFAULT NULL";
$changes[] = "CREATE TABLE eventum_faq (
  faq_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  faq_prj_id INT(11) UNSIGNED NOT NULL,
  faq_usr_id INT(11) UNSIGNED NOT NULL,
  faq_created_date DATETIME NOT NULL,
  faq_updated_date DATETIME NULL,
  faq_title VARCHAR(255) NOT NULL,
  faq_message LONGTEXT NOT NULL,
  PRIMARY KEY (faq_id),
  UNIQUE KEY faq_title (faq_title)
)";
$changes[] = "CREATE TABLE eventum_faq_support_level (
  fsl_faq_id INT(11) UNSIGNED NOT NULL,
  fsl_support_level_id INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (fsl_faq_id, fsl_support_level_id)
)";
$changes[] = "ALTER TABLE eventum_reminder_requirement ADD COLUMN rer_support_level_id INT(11) UNSIGNED NULL";
$changes[] = "ALTER TABLE eventum_reminder_requirement ADD COLUMN rer_customer_id INT(11) UNSIGNED NULL";
$changes[] = "CREATE TABLE eventum_project_field_display (
  pfd_prj_id int(11) unsigned NOT NULL,
  pfd_field varchar(20) NOT NULL,
  pfd_min_role tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (pfd_prj_id, pfd_field)
)";
$changes[] = "CREATE TABLE eventum_issue_quarantine (
    iqu_iss_id int(11) unsigned auto_increment,
    iqu_expiration datetime NULL,
    iqu_status tinyint(1),
    PRIMARY KEY(iqu_iss_id),
    INDEX(iqu_expiration)
)";
$changes[] = "ALTER TABLE eventum_custom_filter ADD COLUMN cst_is_global int(1) default 0";
$changes[] = "ALTER TABLE eventum_mail_queue ADD COLUMN maq_iss_id int(11) unsigned AFTER maq_id";
$changes[] = "ALTER TABLE eventum_mail_queue ADD COLUMN maq_subject varchar(255) NOT NULL AFTER maq_recipient";
$changes[] = "ALTER TABLE eventum_mail_queue ADD INDEX maq_iss_id (maq_iss_id)";
$changes[] = "CREATE TABLE `eventum_group` (
    grp_id int(11) unsigned auto_increment,
    grp_name varchar(100) unique,
    grp_description varchar(255),
    grp_manager_usr_id int(11) unsigned,
    PRIMARY KEY(grp_id)
)";
$changes[] = "CREATE TABLE eventum_project_group (
    pgr_prj_id int(11)  unsigned,
    pgr_grp_id int(11) unsigned,
    index(pgr_prj_id),
    index(pgr_grp_id)
)";
$changes[] = "ALTER TABLE eventum_user ADD COLUMN usr_grp_id int(11) unsigned NULL default NULL AFTER usr_id";
$changes[] = "ALTER TABLE eventum_user ADD INDEX(usr_grp_id)";
$changes[] = "ALTER TABLE eventum_issue ADD COLUMN iss_grp_id int(11) unsigned NULL default NULL AFTER iss_usr_id";
$changes[] = "ALTER TABLE eventum_issue ADD INDEX(iss_grp_id)";
$changes[] = "INSERT INTO eventum_history_type SET htt_name = 'group_changed'";
$changes[] = "ALTER TABLE eventum_priority RENAME eventum_project_priority";
$changes[] = "ALTER TABLE eventum_project_priority CHANGE column pri_id pri_id tinyint(1) unsigned NOT NULL auto_increment";
$changes[] = "ALTER TABLE eventum_project_priority ADD COLUMN pri_prj_id int(11) unsigned NOT NULL";
$changes[] = "ALTER TABLE eventum_project_priority DROP PRIMARY KEY";
$changes[] = "ALTER TABLE eventum_project_priority ADD PRIMARY KEY(pri_id)";
$changes[] = "ALTER TABLE eventum_project_priority DROP KEY pri_id";
$changes[] = "ALTER TABLE eventum_project_priority DROP KEY pri_id_2";
$changes[] = "ALTER TABLE eventum_project_priority ADD KEY(pri_title)";
$changes[] = "ALTER TABLE eventum_project_priority ADD UNIQUE(pri_prj_id, pri_title)";
$changes[] = "CREATE TABLE eventum_project_email_response (
  per_prj_id int(11) unsigned NOT NULL,
  per_ere_id int(10) unsigned NOT NULL,
  PRIMARY KEY (per_prj_id, per_ere_id)
)";
$changes[] = "CREATE TABLE eventum_project_phone_category (
  phc_id int(11) unsigned NOT NULL auto_increment,
  phc_prj_id int(11) unsigned NOT NULL default '0',
  phc_title varchar(64) NOT NULL default '',
  PRIMARY KEY  (phc_id),
  UNIQUE KEY uniq_category (phc_prj_id,phc_title),
  KEY phc_prj_id (phc_prj_id)
)";
$changes[] = "INSERT INTO eventum_project_phone_category (phc_id, phc_prj_id, phc_title) VALUES (1, 1, 'Sales Issues')";
$changes[] = "INSERT INTO eventum_project_phone_category (phc_id, phc_prj_id, phc_title) VALUES (2, 1, 'Technical Issues')";
$changes[] = "INSERT INTO eventum_project_phone_category (phc_id, phc_prj_id, phc_title) VALUES (3, 1, 'Administrative Issues')";
$changes[] = "INSERT INTO eventum_project_phone_category (phc_id, phc_prj_id, phc_title) VALUES (4, 1, 'Other')";
$changes[] = "ALTER TABLE eventum_phone_support ADD COLUMN phs_phc_id int(11) unsigned NOT NULL";
$changes[] = "UPDATE eventum_phone_support SET phs_phc_id=1 WHERE phs_reason='sales'";
$changes[] = "UPDATE eventum_phone_support SET phs_phc_id=2 WHERE phs_reason='technical'";
$changes[] = "UPDATE eventum_phone_support SET phs_phc_id=3 WHERE phs_reason='administrative'";
$changes[] = "UPDATE eventum_phone_support SET phs_phc_id=4 WHERE phs_reason='other'";
$changes[] = "ALTER TABLE eventum_phone_support DROP COLUMN phs_reason";
$changes[] = "ALTER TABLE eventum_reminder_action ADD COLUMN rma_alert_irc TINYINT(1) unsigned NOT NULL DEFAULT 0";
$changes[] = "ALTER TABLE eventum_issue ADD COLUMN iss_last_public_action_date datetime NULL";
$changes[] = "ALTER TABLE eventum_issue ADD COLUMN iss_last_public_action_type varchar(20) NULL";
$changes[] = "ALTER TABLE eventum_issue ADD COLUMN iss_last_internal_action_date datetime NULL";
$changes[] = "ALTER TABLE eventum_issue ADD COLUMN iss_last_internal_action_type varchar(20) NULL";
$changes[] = "ALTER TABLE eventum_reminder_action ADD COLUMN rma_alert_group_leader TINYINT(1) unsigned NOT NULL DEFAULT 0";
$changes[] = "ALTER TABLE eventum_project_user DROP KEY pru_prj_id";
$changes[] = "ALTER TABLE eventum_project_user ADD UNIQUE KEY pru_prj_id (pru_prj_id,pru_usr_id)";
$changes[] = "ALTER TABLE eventum_history_type ADD COLUMN htt_role tinyint(1) DEFAULT '0'";
$changes[] = "UPDATE eventum_history_type SET htt_role = 4 WHERE htt_name IN('note_added', 'note_removed', 'note_converted_draft',
    'note_converted_email', 'phone_entry_added', 'phone_entry_removed', 'time_added', 'time_removed',
    'remote_time_added', 'email_blocked', 'note_routed', 'group_changed', 'draft_added', 'draft_updated')";
$changes[] = "INSERT INTO eventum_history_type SET htt_name = 'status_auto_changed', htt_role = 4";
$changes[] = "CREATE TABLE eventum_reminder_triggered_action (
  rta_iss_id int(11) unsigned not null,
  rta_rma_id int(11) unsigned not null,
  PRIMARY KEY (rta_iss_id)
)";

foreach ($changes as $stmt) {
    $stmt = str_replace('eventum_', APP_TABLE_PREFIX, $stmt);
    $res = DB_Helper::getInstance()->query($stmt);
    if (PEAR::isError($res)) {
		echo 'ERROR: ', $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
        exit(1);
    }
}


?>
done
