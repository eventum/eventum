<?php
require_once dirname(__FILE__) . '/../init.php';


$changes = array();
$changes[] = "INSERT INTO eventum_time_tracking_category (ttc_id, ttc_title, ttc_created_date) VALUES (null, 'Telephone Discussion', NOW())";
$changes[] = 'ALTER TABLE eventum_phone_support DROP COLUMN phs_time_spent';
$changes[] = 'ALTER TABLE eventum_phone_support ADD COLUMN phs_ttr_id int(10) unsigned NULL AFTER phs_iss_id';

foreach ($changes as $stmt) {
    $stmt = str_replace('eventum_', APP_TABLE_PREFIX, $stmt);
    $res = DB_Helper::getInstance()->query($stmt);
    if (PEAR::isError($res)) {
		echo 'ERROR: ', $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
        exit(1);
    }
}
echo "complete<br />\n\n";



?>
now please run /upgrade/v1.2.1_to_v1.2.2/fix_email_bodies.php
