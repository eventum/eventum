<?php
include_once("../../../config.inc.php");
include_once(APP_INC_PATH . "db_access.php");


$stmt = "DESCRIBE eventum_custom_filter";
$res = $GLOBALS["db_api"]->dbh->getCol($stmt);
if (in_array('cst_use_fulltext', $res)) {
    $stmts = array();
    $stmts[] = "ALTER TABLE eventum_custom_filter DROP COLUMN cst_use_fulltext";
    foreach ($stmts as $stmt) {
        $stmt = str_replace('eventum_', APP_TABLE_PREFIX, $stmt);
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            echo "<pre>";var_dump($res);echo "</pre>";
            exit(1);
        }
    }
}

?>
done