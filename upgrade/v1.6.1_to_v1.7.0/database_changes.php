<?php
require_once dirname(__FILE__) . '/../init.php';

$stmts = array();

$stmts[] = "ALTER TABLE eventum_issue_attachment ADD COLUMN iat_status enum('internal','public') NOT NULL default 'public';";
$stmts[] = "ALTER TABLE eventum_issue_attachment ADD COLUMN iat_not_id int(11) unsigned default NULL;";
$stmts[] = "ALTER TABLE eventum_mail_queue ADD COLUMN maq_type_id int(11) unsigned default NULL;";
$stmts[] = "ALTER TABLE eventum_mail_queue ADD INDEX maq_type (maq_type,maq_type_id);";
$stmts[] = "ALTER TABLE eventum_note ADD COLUMN not_has_attachment tinyint(1) NOT NULL default '0';";
$stmts[] = "ALTER TABLE eventum_note ADD COLUMN not_message_id varchar(255) default NULL;";
$stmts[] = "ALTER TABLE eventum_note ADD INDEX not_message_id (not_message_id);";
$stmts[] = "ALTER TABLE eventum_note ADD COLUMN not_removed tinyint(1) NOT NULL default '0';";
$stmts[] = "ALTER TABLE eventum_note ADD INDEX not_parent_id (not_parent_id);";
$stmts[] = "ALTER TABLE eventum_issue ADD COLUMN iss_root_message_id varchar(255);";

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
