<?php
require_once dirname(__FILE__) . '/../init.php';


$stmts = array();

// see if this category already exists and if not, add it
$sql = "SELECT
            count(*)
        FROM
            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "time_tracking_category
        WHERE
            ttc_title = 'Email Discussion'";
$res = DB_Helper::getInstance()->getOne($sql);
if ($res == 0) {
    // test if this works
    $stmts[] = "INSERT INTO eventum_time_tracking_category (ttc_title, ttc_created_date) VALUES ('Email Discussion', NOW())";
}

$stmts[] = "ALTER TABLE eventum_project_user ADD COLUMN pru_role tinyint(1) unsigned default 1";
$stmts[] = "ALTER TABLE eventum_project ADD COLUMN prj_segregate_reporter tinyint(1) DEFAULT 0";
$stmts[] = "ALTER TABLE eventum_issue ADD COLUMN iss_private tinyint(1) NOT NULL DEFAULT 0";
$stmts[] = "ALTER TABLE eventum_email_draft ADD COLUMN emd_status enum('pending', 'edited', 'sent') NOT NULL DEFAULT 'pending' AFTER emd_sup_id";
$stmts[] = "INSERT INTO eventum_history_type (htt_id, htt_name, htt_role) VALUES (NULL, 'scm_checkin_associated', 0)";
$stmts[] = "ALTER TABLE eventum_project_priority ADD COLUMN pri_rank TINYINT(1) NOT NULL";
$stmts[] = "UPDATE eventum_columns_to_display SET ctd_field='pri_rank' WHERE ctd_field='iss_pri_id'";

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
