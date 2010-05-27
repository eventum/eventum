<?php
/**
 * Decode attachment filenames from QuotedPrintable MIME encoding.
 * Also set Untitled.jpg to unnamed attachments (Usually inline).
 */

// Attachments that need to be decoded
$res = db_getAll("SELECT iaf_id, iaf_filename FROM %TABLE_PREFIX%issue_attachment_file WHERE iaf_filename LIKE '%=?%'");
if (PEAR::isError($res)) {
    echo $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
    exit(1);
}

foreach ($res as $idx => $row) {
	$iaf_filename = Mime_Helper::decodeQuotedPrintable($row['iaf_filename']);
	db_query("UPDATE %TABLE_PREFIX%issue_attachment_file ".
		"SET iaf_filename='". DB_Helper::escapeString($iaf_filename). "' ".
		"WHERE iaf_id=".$row['iaf_id']
	);
}

// Unnamed attachments
$res = db_getAll("SELECT iaf_id, iaf_filetype FROM %TABLE_PREFIX%issue_attachment_file WHERE iaf_filename=''");
if (PEAR::isError($res)) {
    echo $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
    exit(1);
}

foreach ($res as $idx => $row) {
	list($type, $ext) = explode('/', $row['iaf_filetype']);
	$iaf_filename = sprintf(ev_gettext('Untitled.%s'), $ext);

	db_query("UPDATE %TABLE_PREFIX%issue_attachment_file ".
		"SET iaf_filename='". DB_Helper::escapeString($iaf_filename). "' ".
		"WHERE iaf_id=".$row['iaf_id']
	);
}
