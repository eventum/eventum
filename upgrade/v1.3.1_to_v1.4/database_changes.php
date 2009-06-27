<?php
require_once dirname(__FILE__) . '/../init.php';


$stmt = "desc eventum_project_priority";
$stmt = str_replace('eventum_', APP_TABLE_PREFIX, $stmt);
$res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
if (PEAR::isError($res)) {
	echo 'ERROR: ', $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
    exit(1);
}
$columns = $res;
$stmts = array();
// need to handle problems where the auto_increment key was not added to pri_id
if (!strstr($columns[0]['Extra'], 'auto_increment')) {
    $stmts[] = "ALTER TABLE eventum_project_priority CHANGE COLUMN pri_id pri_id tinyint(1) unsigned NOT NULL auto_increment";
}
if (!strstr($columns[0]['Key'], 'PRI')) {
    $stmts[] = "ALTER TABLE eventum_project_priority DROP PRIMARY KEY";
    $stmts[] = "ALTER TABLE eventum_project_priority ADD PRIMARY KEY(pri_id)";
}

$stmt = "desc eventum_customer_note";
$stmt = str_replace('eventum_', APP_TABLE_PREFIX, $stmt);
$res = DB_Helper::getInstance()->getCol($stmt);
if (PEAR::isError($res)) {
	echo 'ERROR: ', $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
    exit(1);
}
$columns = $res;
// need to handle the problem in which upgrades from 1.2.2 to 1.3 didn't get the cno_prj_id field
if (!in_array('cno_prj_id', $columns)) {
    $stmts[] = "ALTER TABLE eventum_customer_note ADD COLUMN cno_prj_id int(11) unsigned NOT NULL";
}

$stmts[] = 'ALTER TABLE eventum_issue DROP COLUMN iss_lock_usr_id';

$stmts[] = 'CREATE TABLE eventum_link_filter (
  lfi_id int(11) unsigned NOT NULL auto_increment,
  lfi_pattern varchar(255) NOT NULL,
  lfi_replacement varchar(255) NOT NULL,
  lfi_usr_role tinyint(9) NOT NULL DEFAULT 0,
  lfi_description varchar(255) NULL,
  PRIMARY KEY  (lfi_id)
)';
$stmts[] = 'CREATE TABLE eventum_project_link_filter (
  plf_prj_id int(11) NOT NULL,
  plf_lfi_id int(11) NOT NULL,
  PRIMARY KEY  (plf_prj_id, plf_lfi_id)
)';

$stmts[] = 'ALTER TABLE eventum_reminder_field ADD column rmf_allow_column_compare tinyint(1) DEFAULT 0';
$stmts[] = "UPDATE eventum_reminder_field SET rmf_allow_column_compare = 1 WHERE rmf_title LIKE '%date%'";
$stmts[] = 'ALTER TABLE eventum_reminder_level_condition ADD COLUMN rlc_comparison_rmf_id tinyint(3) unsigned';

// add a project ID to irc notifications table so notifications aren't tied to issues.
$stmts[] = 'ALTER TABLE eventum_irc_notice ADD COLUMN ino_prj_id int(11) NOT NULL';

$stmts[] = 'ALTER TABLE eventum_custom_field ADD COLUMN fld_list_display tinyint(1) NOT NULL DEFAULT 0';

$stmts[] = 'ALTER TABLE eventum_reminder_level ADD COLUMN rem_skip_weekend tinyint(1) NOT NULL DEFAULT 0';

// fix wrong column type
$stmts[] = 'ALTER TABLE eventum_issue_history CHANGE COLUMN his_htt_id his_htt_id tinyint(2) NOT NULL DEFAULT 0';

// add table to control which columns to display
$stmts[] = "CREATE TABLE eventum_columns_to_display (
    ctd_prj_id int(11) unsigned NOT NULL,
    ctd_page varchar(20) NOT NULL,
    ctd_field varchar(30) NOT NULL,
    ctd_min_role tinyint(1) NOT NULL DEFAULT 0,
    ctd_rank tinyint(2) NOT NULL DEFAULT 0,
    PRIMARY KEY(ctd_prj_id, ctd_page, ctd_field),
    INDEX(ctd_prj_id, ctd_page)
)";

// add filtering by events in past x hours to advanced search
$stmts[] = "ALTER TABLE eventum_custom_filter ADD COLUMN cst_created_date_time_period smallint(4) AFTER cst_created_date_filter_type";
$stmts[] = "ALTER TABLE eventum_custom_filter ADD COLUMN cst_updated_date_time_period smallint(4) AFTER cst_updated_date_filter_type";
$stmts[] = "ALTER TABLE eventum_custom_filter ADD COLUMN cst_last_response_date_time_period smallint(4) AFTER cst_last_response_date_filter_type";
$stmts[] = "ALTER TABLE eventum_custom_filter ADD COLUMN cst_first_response_date_time_period smallint(4) AFTER cst_first_response_date_filter_type";
$stmts[] = "ALTER TABLE eventum_custom_filter ADD COLUMN cst_closed_date_time_period smallint(4) AFTER cst_closed_date_filter_type";

$stmts[] = "UPDATE eventum_user SET usr_status = 'inactive' WHERE usr_id = 1";

foreach ($stmts as $stmt) {
    $stmt = str_replace('eventum_', APP_TABLE_PREFIX, $stmt);
    $res = DB_Helper::getInstance()->query($stmt);
    if (PEAR::isError($res)) {
		echo 'ERROR: ', $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
        exit(1);
    }
}

?>
done
