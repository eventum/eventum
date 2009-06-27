<?php
require_once dirname(__FILE__) . '/../init.php';

$stmts = array();

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
