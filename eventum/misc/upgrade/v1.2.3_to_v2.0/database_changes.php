<?php
include_once("../../../config.inc.php");
include_once(APP_INC_PATH . "db_access.php");


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
  cam_customer_id int(11) unsigned NOT NULL,
  cam_usr_id int(11) unsigned NOT NULL,
  cam_type varchar(7) NOT NULL,
  PRIMARY KEY (cam_id),
  KEY cam_customer_id (cam_customer_id),
  UNIQUE KEY cam_manager (cam_customer_id, cam_usr_id)
)";
$changes[] = "ALTER TABLE eventum_user ADD COLUMN usr_clocked_in tinyint(1) DEFAULT 0";
$changes[] = "";
$changes[] = "";
$changes[] = "";
$changes[] = "";
$changes[] = "";
$changes[] = "";
$changes[] = "";
$changes[] = "";
$changes[] = "";
$changes[] = "";
$changes[] = "";
$changes[] = "";
$changes[] = "";
$changes[] = "";

foreach ($changes as $stmt) {
    $stmt = str_replace('eventum_', APP_TABLE_PREFIX, $stmt);
    $update = $GLOBALS["db_api"]->dbh->query($stmt);
    if (PEAR::isError($update)) {
        echo "<pre>";var_dump($update);echo "</pre>";
        exit;
    }
}
echo "complete<br />\n\n";



?>
now please run /misc/upgrade/v1.2.3_to_v2.0/fix_email_bodies.php