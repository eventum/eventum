<?php
require_once dirname(__FILE__) . '/../init.php';

$stmts = array();

$stmts[] = "UPDATE eventum_columns_to_display SET ctd_field='sta_rank' WHERE ctd_field='iss_sta_id'";

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
