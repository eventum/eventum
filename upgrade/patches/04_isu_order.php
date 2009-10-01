<?php

function db_patch_4() {
	$stmts = array();

	$columns = db_getCol('DESC %TABLE_PREFIX%issue_user');
	if (in_array('isu_order', $columns)) {
		return $stmts;
	}

	$stmts[] = "ALTER TABLE %TABLE_PREFIX%issue_user ADD isu_order int(11) NOT NULL DEFAULT '0' AFTER isu_assigned_date, ADD INDEX isu_order (isu_order)";
	$stmts[] = "UPDATE %TABLE_PREFIX%issue_user set isu_order=isu_iss_id";

	return $stmts;
}
