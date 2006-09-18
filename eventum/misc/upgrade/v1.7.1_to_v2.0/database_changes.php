<?php
include_once("../../../config.inc.php");
include_once(APP_INC_PATH . "db_access.php");

$stmts = array();

$stmts[] = "ALTER TABLE eventum_support_email CHANGE COLUMN sup_to sup_to tinytext;";
$stmts[] = "ALTER TABLE eventum_support_email CHANGE COLUMN sup_cc sup_cc tinytext;";

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
