<?php
require_once dirname(__FILE__) . '/../init.php';

$stmts = array();

$stmts[] = "ALTER TABLE eventum_support_email CHANGE COLUMN sup_to sup_to text;";
$stmts[] = "ALTER TABLE eventum_support_email CHANGE COLUMN sup_cc sup_cc text;";
$stmts[] = "ALTER TABLE eventum_user ADD COLUMN usr_lang varchar(5);";
$stmts[] = "ALTER TABLE eventum_custom_field_option CHANGE COLUMN cfo_value cfo_value varchar(128) NOT NULL;";
$stmts[] = "ALTER TABLE eventum_issue_custom_field ADD INDEX iss_id_fld_id (icf_iss_id, icf_fld_id);";
$stmts[] = "INSERT INTO eventum_history_type SET htt_name = 'draft_routed',  htt_role = 4;";

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
