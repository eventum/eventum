<?php
require_once(dirname(__FILE__) . "/../../../init.php");
require_once(APP_INC_PATH . "db_access.php");

$stmts = array();

$stmts[] = "INSERT INTO eventum_history_type SET htt_name = 'draft_routed',  htt_role = 4;";

foreach ($stmts as $stmt) {
    $stmt = str_replace('eventum_', APP_TABLE_PREFIX, $stmt);
    $res = $GLOBALS["db_api"]->dbh->query($stmt);
    if (PEAR::isError($res)) {
		echo 'ERROR: ', $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
        exit(1);
    }
}
?>
done
