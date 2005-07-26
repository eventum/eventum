<?php
include_once("../../../config.inc.php");
include_once(APP_INC_PATH . "db_access.php");


$stmt = "desc eventum_mail_queue";
$stmt = str_replace('eventum_', APP_TABLE_PREFIX, $stmt);
$columns = $GLOBALS["db_api"]->dbh->getCol($stmt);
if (PEAR::isError($columns)) {
    echo "<pre>";var_dump($columns);echo "</pre>";
    exit(1);
}

if (!in_array('maq_iss_id', $columns)) {
    $stmt = "ALTER TABLE eventum_mail_queue ADD COLUMN maq_iss_id int(11) unsigned AFTER maq_id";
    $stmt = str_replace('eventum_', APP_TABLE_PREFIX, $stmt);
    $update = $GLOBALS["db_api"]->dbh->query($stmt);
    if (PEAR::isError($update)) {
        echo "<pre>";var_dump($update);echo "</pre>";
        exit(1);
    }
    $stmt = "ALTER TABLE eventum_mail_queue ADD INDEX maq_iss_id (maq_iss_id)";
    $stmt = str_replace('eventum_', APP_TABLE_PREFIX, $stmt);
    $update = $GLOBALS["db_api"]->dbh->query($stmt);
    if (PEAR::isError($update)) {
        echo "<pre>";var_dump($update);echo "</pre>";
        exit(1);
    }
}

if (!in_array('maq_subject', $columns)) {
    $stmt = "ALTER TABLE eventum_mail_queue ADD COLUMN maq_subject varchar(255) NOT NULL AFTER maq_recipient";
    $stmt = str_replace('eventum_', APP_TABLE_PREFIX, $stmt);
    $update = $GLOBALS["db_api"]->dbh->query($stmt);
    if (PEAR::isError($update)) {
        echo "<pre>";var_dump($update);echo "</pre>";
        exit(1);
    }
}
?>
done