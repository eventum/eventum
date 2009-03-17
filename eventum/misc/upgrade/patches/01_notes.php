<?php

function db_patch_1() {
	$stmts = array();
	$stmts[] = "ALTER TABLE %TABLE_PREFIX%note CHANGE COLUMN not_blocked_message not_full_message longblob NULL";
	$stmts[] = "ALTER TABLE %TABLE_PREFIX%support_email_body CHANGE seb_full_email seb_full_email longblob NOT NULL";
	$stmts[] = "ALTER TABLE %TABLE_PREFIX%mail_queue CHANGE COLUMN maq_body maq_body longblob NOT NULL";
	$stmts[] = "ALTER TABLE %TABLE_PREFIX%note ADD COLUMN not_is_blocked tinyint(1) NOT NULL DEFAULT 0";
	$stmts[] = "UPDATE %TABLE_PREFIX%note SET not_is_blocked = 1 WHERE not_full_message != ''";

	return $stmts;
}
