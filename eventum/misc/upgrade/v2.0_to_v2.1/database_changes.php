<?php
require_once(dirname(__FILE__) . "/../../../init.php");
require_once(APP_INC_PATH . "db_access.php");

$stmts = array();

$stmts[] = "ALTER TABLE eventum_irc_notice ADD INDEX ino_status (ino_status)";

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
