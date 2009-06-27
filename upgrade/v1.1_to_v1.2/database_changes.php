<?php
require_once dirname(__FILE__) . '/../init.php';

$changes = array();
$changes[] = 'CREATE TABLE eventum_support_email_body (
  seb_sup_id int(11) unsigned NOT NULL,
  seb_body longtext NOT NULL,
  seb_full_email longtext NOT NULL,
  PRIMARY KEY (seb_sup_id)
);
';
$changes[] = 'INSERT INTO eventum_support_email_body (SELECT sup_id, sup_body, sup_full_email FROM eventum_support_email);';
$changes[] = 'ALTER TABLE eventum_support_email DROP COLUMN sup_body;';
$changes[] = 'ALTER TABLE eventum_support_email DROP COLUMN sup_full_email;';
$changes[] = 'ALTER TABLE eventum_support_email ADD COLUMN sup_usr_id int(11) unsigned DEFAULT NULL AFTER sup_iss_id;';
$changes[] = 'ALTER TABLE eventum_support_email ADD KEY sup_usr_id(sup_usr_id);';
$changes[] = 'ALTER TABLE eventum_email_account ADD COLUMN ema_issue_auto_creation varchar(8) NOT NULL DEFAULT \'disabled\';';
$changes[] = 'ALTER TABLE eventum_email_account ADD COLUMN ema_issue_auto_creation_options text;';

foreach ($changes as $stmt) {
    $stmt = str_replace('eventum_', APP_TABLE_PREFIX, $stmt);
    $res = DB_Helper::getInstance()->query($stmt);
    if (PEAR::isError($res)) {
		echo 'ERROR: ', $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
        exit(1);
    }
}
echo "complete<br />\n\n";



?>
now please run /misc/upgrade/v1.1_to_v1.2/set_support_email_usr_id.php
