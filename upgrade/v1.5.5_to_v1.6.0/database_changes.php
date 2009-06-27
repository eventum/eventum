<?php
require_once dirname(__FILE__) . '/../init.php';


$stmts = array();

$stmts[] = "ALTER TABLE eventum_custom_filter ADD COLUMN cst_custom_field TEXT";
$stmts[] = "ALTER TABLE eventum_custom_field ADD COLUMN fld_min_role tinyint(1) NOT NULL DEFAULT 0";
$stmts[] = "ALTER TABLE eventum_custom_field ADD COLUMN fld_rank smallint(2) NOT NULL DEFAULT 0";
$stmts[] = "ALTER TABLE eventum_custom_field ADD COLUMN fld_backend varchar(100)";
$stmts[] = "ALTER TABLE eventum_custom_filter ADD COLUMN cst_search_type varchar(15) not null default 'customer'";
$stmts[] = "CREATE FULLTEXT INDEX ft_icf_value ON eventum_issue_custom_field (icf_value)";
$stmts[] = "ALTER TABLE eventum_custom_filter ADD COLUMN cst_reporter int(11) unsigned DEFAULT NULL AFTER cst_users";
$stmts[] = "ALTER TABLE eventum_faq ADD COLUMN faq_rank TINYINT(2) UNSIGNED NOT NULL";
$stmts[] = "ALTER TABLE eventum_reminder_action ADD COLUMN rma_boilerplate varchar(255) DEFAULT NULL";
$stmts[] = "UPDATE eventum_reminder_action SET rma_boilerplate='Please take immediate action!'";
$stmts[] = "INSERT INTO eventum_time_tracking_category (ttc_title, ttc_created_date) VALUES ('Note Discussion', now())";

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
