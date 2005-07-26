<?php
include_once("../../../config.inc.php");
include_once(APP_INC_PATH . "db_access.php");


$changes = array();
$changes[] = "INSERT INTO eventum_time_tracking_category (ttc_id, ttc_title, ttc_created_date) VALUES (null, 'Telephone Discussion', NOW())";
$changes[] = 'ALTER TABLE eventum_phone_support DROP COLUMN phs_time_spent';
$changes[] = 'ALTER TABLE eventum_phone_support ADD COLUMN phs_ttr_id int(10) unsigned NULL AFTER phs_iss_id';

foreach ($changes as $stmt) {
    $stmt = str_replace('eventum_', APP_TABLE_PREFIX, $stmt);
    $update = $GLOBALS["db_api"]->dbh->query($stmt);
    if (PEAR::isError($update)) {
        echo "<pre>";var_dump($update);echo "</pre>";
        exit(1);
    }
}
echo "complete<br />\n\n";



?>
now please run /misc/upgrade/v1.2.1_to_v1.2.2/fix_email_bodies.php