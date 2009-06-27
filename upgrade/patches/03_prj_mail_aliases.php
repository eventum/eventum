<?php

function db_patch_3() {
	$stmts = array();

	$columns = db_getCol('DESC %TABLE_PREFIX%project');
	if (!in_array('prj_mail_aliases', $columns)) {
		$stmts[] = "ALTER TABLE %TABLE_PREFIX%project ADD COLUMN prj_mail_aliases varchar(255)";
	}

	return $stmts;
}
