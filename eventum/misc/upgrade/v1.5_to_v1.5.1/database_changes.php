<?php
include_once("../../../config.inc.php");
include_once(APP_INC_PATH . "db_access.php");


$stmts = array();

$stmt = "desc eventum_mail_queue";
$stmt = str_replace('eventum_', APP_TABLE_PREFIX, $stmt);
$columns = $GLOBALS["db_api"]->dbh->getCol($stmt);
if (PEAR::isError($columns)) {
    echo "<pre>";var_dump($columns);echo "</pre>";
    exit;
}
if (!in_array('maq_type', $columns)) {
    $stmts[] = "ALTER TABLE eventum_mail_queue ADD COLUMN maq_type varchar(30) DEFAULT ''";
}
if (!in_array('maq_usr_id', $columns)) {
    $stmts[] = "ALTER TABLE eventum_mail_queue ADD COLUMN maq_usr_id int(11) unsigned NULL DEFAULT NULL";
}



foreach ($stmts as $stmt) {
    $stmt = str_replace('eventum_', APP_TABLE_PREFIX, $stmt);
    $res = $GLOBALS["db_api"]->dbh->query($stmt);
    if (PEAR::isError($res)) {
        echo "<pre>";var_dump($res);echo "</pre>";
        exit;
    }
}

?>
done