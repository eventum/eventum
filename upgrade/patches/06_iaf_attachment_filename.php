<?php
/**
 * Decode attachment filenames from QuotedPrintable MIME encoding.
 */
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
