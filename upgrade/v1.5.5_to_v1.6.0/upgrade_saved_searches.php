<?php
require_once dirname(__FILE__) . '/../init.php';

$sql = "SELECT
            cst_id,
            cst_prj_id,
            cst_keywords,
            cst_customer_email
        FROM
            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_filter";
$res = DB_Helper::getInstance()->getAll($sql, DB_FETCHMODE_ASSOC);
if (PEAR::isError($res)) {
	echo 'ERROR: ', $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
    exit(1);
}
foreach ($res as $row) {
    if (!empty($row['cst_customer_email'])) {
        $search_type = 'customer';
        $keywords = $row['cst_customer_email'];
    } else {
        $search_type = 'all_text';
        $keywords = $row['cst_keywords'];
    }
    $sql = "UPDATE
                " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_filter
            SET
                cst_search_type = '$search_type',
                cst_keywords = '" . Misc::escapeString($keywords) . "'
            WHERE
                cst_id = " . $row['cst_id'];
    $res = DB_Helper::getInstance()->query($sql);
    if (PEAR::isError($res)) {
		echo 'ERROR: ', $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
        exit(1);
    } else {
        echo "Setting saved search #" . $row['cst_id'] . " to type $search_type<br />\n";
    }
}
?>
done
