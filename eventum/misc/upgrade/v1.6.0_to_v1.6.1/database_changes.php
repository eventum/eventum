<?php
include_once("../../../config.inc.php");
include_once(APP_INC_PATH . "db_access.php");


$stmts = array();

$stmts[] = "INSERT INTO eventum_history_type VALUES(null, 'issue_bulk_updated', 0);";

foreach ($stmts as $stmt) {
    $stmt = str_replace('eventum_', APP_TABLE_PREFIX, $stmt);
    $res = $GLOBALS["db_api"]->dbh->query($stmt);
    if (PEAR::isError($res)) {
        echo "<pre>";var_dump($res);echo "</pre>";
        exit(1);
    }
}

?>
done