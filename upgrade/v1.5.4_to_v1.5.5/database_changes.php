<?php
require_once dirname(__FILE__) . '/../init.php';


$stmt = "DESCRIBE eventum_custom_filter";
$res = DB_Helper::getInstance()->getCol($stmt);
if (in_array('cst_use_fulltext', $res)) {
    $stmts = array();
    $stmts[] = "ALTER TABLE eventum_custom_filter DROP COLUMN cst_use_fulltext";
    foreach ($stmts as $stmt) {
        $stmt = str_replace('eventum_', APP_TABLE_PREFIX, $stmt);
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
			echo 'ERROR: ', $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
            exit(1);
        }
    }
}

?>
done
