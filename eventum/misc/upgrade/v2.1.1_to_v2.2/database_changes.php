<?php
// avoid setup redirecting us
define('INSTALL_PATH', realpath(dirname(__FILE__) . '/../../..'));
define('CONFIG_PATH', INSTALL_PATH.'/config');

if (!file_exists(CONFIG_PATH. '/config.php')) {
	die("Can't find config.php from ". CONFIG_PATH . ". Did you forgot to copy config from old install?");
}

require_once INSTALL_PATH . '/init.php';
require_once APP_INC_PATH . 'db_access.php';

$stmts = array();

$stmts[] = "ALTER TABLE eventum_note CHANGE COLUMN not_blocked_message not_blocked_message longblob NULL";
$stmts[] = "ALTER TABLE eventum_support_email_body CHANGE seb_full_email seb_full_email longblob NOT NULL";
$stmts[] = "ALTER TABLE eventum_mail_queue CHANGE COLUMN maq_body maq_body longblob NOT NULL";
$stmts[] = "ALTER TABLE eventum_note CHANGE COLUMN not_blocked_message not_full_message longblob NOT NULL";
$stmts[] = "ALTER TABLE eventum_note ADD COLUMN not_is_blocked tinyint(1) NOT NULL DEFAULT 0";
$stmts[] = "UPDATE eventum_note SET not_is_blocked = 1 WHERE not_full_message != ''";

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
