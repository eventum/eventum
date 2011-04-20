<?php
require_once dirname(__FILE__) . '/../init.php';


$stmts = array();

$stmt = "desc eventum_mail_queue";
$stmt = str_replace('eventum_', APP_TABLE_PREFIX, $stmt);
$res = DB_Helper::getInstance()->getCol($stmt);
if (PEAR::isError($res)) {
	echo 'ERROR: ', $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
    exit(1);
}
$columns = $res;
if (!in_array('maq_type', $columns)) {
    $stmts[] = "ALTER TABLE eventum_mail_queue ADD COLUMN maq_type varchar(30) DEFAULT ''";
}
if (!in_array('maq_usr_id', $columns)) {
    $stmts[] = "ALTER TABLE eventum_mail_queue ADD COLUMN maq_usr_id int(11) unsigned NULL DEFAULT NULL";
}

$stmt = "desc eventum_issue";
$stmt = str_replace('eventum_', APP_TABLE_PREFIX, $stmt);
$res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
if (PEAR::isError($res)) {
	echo 'ERROR: ', $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
    exit(1);
}
$columns = $res;
$ncolumns = count($columns);
for ($i = 0; $i < $ncolumns; $i++) {
    if ($columns[$i]['Field'] == 'iss_pri_id') {
        // check if the db change was already made or not
        if ($columns[$i]['Type'] != 'smallint(3)') {
            $stmts[] = "ALTER TABLE eventum_issue CHANGE COLUMN iss_pri_id iss_pri_id smallint(3) NOT NULL default 0";
        }
    }
}

$stmt = "desc eventum_project_priority";
$stmt = str_replace('eventum_', APP_TABLE_PREFIX, $stmt);
$res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
if (PEAR::isError($res)) {
	echo 'ERROR: ', $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
    exit(1);
}
$columns = $res;
$ncolumns = count($columns);
for ($i = 0; $i < $ncolumns; $i++) {
    if ($columns[$i]['Field'] == 'pri_id') {
        // check if the db change was already made or not
        if ($columns[$i]['Type'] != 'smallint(3) unsigned') {
            $stmts[] = "ALTER TABLE eventum_project_priority CHANGE COLUMN pri_id pri_id smallint(3) unsigned NOT NULL auto_increment";
        }
    }
}

$stmts[] = "CREATE TABLE eventum_search_profile (
  sep_id int(11) unsigned NOT NULL auto_increment,
  sep_usr_id int(11) unsigned NOT NULL,
  sep_prj_id int(11) unsigned NOT NULL,
  sep_type char(5) NOT NULL,
  sep_user_profile blob NOT NULL,
  PRIMARY KEY (sep_id),
  UNIQUE (sep_usr_id, sep_prj_id, sep_type)
)";

$stmts[] = "ALTER TABLE eventum_issue ADD INDEX (iss_duplicated_iss_id)";
$stmts[] = "ALTER TABLE eventum_time_tracking ADD INDEX (ttr_iss_id)";

$stmts[] = "ALTER TABLE eventum_issue ADD COLUMN iss_percent_complete tinyint(3) unsigned DEFAULT 0";

foreach ($stmts as $stmt) {
    $stmt = str_replace('eventum_', APP_TABLE_PREFIX, $stmt);
    $res = DB_Helper::getInstance()->query($stmt);
    if (PEAR::isError($res)) {
		echo 'ERROR: ', $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
        exit(1);
    }
}

?>
done
