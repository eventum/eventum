<?php
require_once dirname(__FILE__) . '/../init.php';


$stmts = array();

$stmts[] = "ALTER TABLE eventum_email_account ADD column ema_use_routing tinyint(1) DEFAULT 0";

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
