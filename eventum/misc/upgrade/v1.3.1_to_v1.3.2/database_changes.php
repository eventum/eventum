<?php
include_once("../../../config.inc.php");
include_once(APP_INC_PATH . "db_access.php");


$stmt = "desc eventum_project_priority";
$stmt = str_replace('eventum_', APP_TABLE_PREFIX, $stmt);
$columns = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
if (PEAR::isError($columns)) {
    echo "<pre>";var_dump($columns);echo "</pre>";
    exit;
}
$stmts = array();
// need to handle problems where the auto_increment key was not added to pri_id
if (!strstr($columns[0]['Extra'], 'auto_increment')) {
    $stmts[] = "ALTER TABLE eventum_project_priority CHANGE COLUMN pri_id pri_id tinyint(1) unsigned NOT NULL auto_increment";
}
if (!strstr($columns[0]['Key'], 'PRI')) {
    $stmts[] = "ALTER TABLE eventum_project_priority DROP PRIMARY KEY";
    $stmts[] = "ALTER TABLE eventum_project_priority ADD PRIMARY KEY(pri_id)";
}

$stmt = "desc eventum_customer_note";
$stmt = str_replace('eventum_', APP_TABLE_PREFIX, $stmt);
$columns = $GLOBALS["db_api"]->dbh->getCol($stmt);
if (PEAR::isError($columns)) {
    echo "<pre>";var_dump($columns);echo "</pre>";
    exit;
}
// need to handle the problem in which upgrades from 1.2.2 to 1.3 didn't get the cno_prj_id field
if (!in_array('cno_prj_id', $columns)) {
    $stmts[] = "ALTER TABLE eventum_customer_note ADD COLUMN cno_prj_id int(11) unsigned NOT NULL";
}

$stmts[] = 'ALTER TABLE eventum_issue DROP COLUMN iss_lock_usr_id';

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