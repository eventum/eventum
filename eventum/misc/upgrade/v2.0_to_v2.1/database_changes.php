<?php
define('APP_PATH', realpath(dirname(__FILE__) . '/../../..'));
if (!file_exists(APP_PATH . '/config/config.php')) {
	die("Can't find config.php from ". APP_PATH . "/config. Did you forgot to copy config from old install?");
}

require_once APP_PATH . '/init.php';
require_once APP_INC_PATH . 'db_access.php');

$stmts = array();

$stmts[] = "ALTER TABLE eventum_irc_notice ADD INDEX ino_status (ino_status)";
$stmts[] = "ALTER TABLE eventum_issue_custom_field ADD COLUMN icf_value_integer int(11) NULL DEFAULT NULL";
$stmts[] = "ALTER TABLE eventum_issue_custom_field ADD COLUMN icf_value_date date NULL DEFAULT NULL";
$stmts[] = "ALTER TABLE eventum_custom_field ADD COLUMN fld_close_form tinyint(1) NOT NULL DEFAULT 0";
$stmts[] = "ALTER TABLE eventum_custom_field ADD COLUMN fld_close_form_required tinyint(1) NOT NULL DEFAULT 0";
$stmts[] = "ALTER TABLE eventum_issue ADD COLUMN iss_customer_contract_id varchar(50) NULL AFTER iss_customer_contact_id";

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
