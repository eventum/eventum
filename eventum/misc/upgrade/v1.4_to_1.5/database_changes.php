<?php
include_once("../../../config.inc.php");
include_once(APP_INC_PATH . "db_access.php");


$stmts = array();

// see if this category already exists and if not, add it
$sql = "SELECT
            count(*)
        FROM
            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "time_tracking_category
        WHERE
            ttc_title = 'Email Discussion'";
$res = $GLOBALS["db_api"]->dbh->getOne($sql);
if ($res == 0) {
    // test if this works
    $stmts[] = "INSERT INTO eventum_time_tracking_category (ttc_id, ttc_title, ttc_created_date) VALUES (9, 'Email Discussion', NOW())";
}

$stmts[] = "ALTER TABLE eventum_project_user ADD COLUMN pru_role tinyint(1) unsigned default 1";

$stmts[] = "ALTER TABLE eventum_project ADD COLUMN prj_segregate_reporter tinyint(1) DEFAULT 0";

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