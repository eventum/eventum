<?php
require_once("../../../config.inc.php");
require_once(APP_INC_PATH . "db_access.php");

$stmts = array();

$stmts[] = "ALTER TABLE eventum_support_email CHANGE COLUMN sup_to sup_to tinytext;";
$stmts[] = "ALTER TABLE eventum_support_email CHANGE COLUMN sup_cc sup_cc tinytext;";
$stmts[] = "ALTER TABLE eventum_user ADD COLUMN usr_lang varchar(5);";
$stmts[] = "ALTER TABLE eventum_custom_field_option CHANGE COLUMN cfo_value cfo_value varchar(128) NOT NULL;";

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
