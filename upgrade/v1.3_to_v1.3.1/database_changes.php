<?php
require_once dirname(__FILE__) . '/../init.php';


$stmt = "desc eventum_mail_queue";
$stmt = str_replace('eventum_', APP_TABLE_PREFIX, $stmt);
$res = DB_Helper::getInstance()->getCol($stmt);
if (PEAR::isError($res)) {
	echo 'ERROR: ', $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
    exit(1);
}
$columns = $res;

if (!in_array('maq_iss_id', $columns)) {
    $stmt = "ALTER TABLE eventum_mail_queue ADD COLUMN maq_iss_id int(11) unsigned AFTER maq_id";
    $stmt = str_replace('eventum_', APP_TABLE_PREFIX, $stmt);
    $res = DB_Helper::getInstance()->query($stmt);
    if (PEAR::isError($res)) {
		echo 'ERROR: ', $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
        exit(1);
    }
    $stmt = "ALTER TABLE eventum_mail_queue ADD INDEX maq_iss_id (maq_iss_id)";
    $stmt = str_replace('eventum_', APP_TABLE_PREFIX, $stmt);
    $res = DB_Helper::getInstance()->query($stmt);
    if (PEAR::isError($res)) {
		echo 'ERROR: ', $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
        exit(1);
    }
}

if (!in_array('maq_subject', $columns)) {
    $stmt = "ALTER TABLE eventum_mail_queue ADD COLUMN maq_subject varchar(255) NOT NULL AFTER maq_recipient";
    $stmt = str_replace('eventum_', APP_TABLE_PREFIX, $stmt);
    $res = DB_Helper::getInstance()->query($stmt);
    if (PEAR::isError($res)) {
		echo 'ERROR: ', $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
        exit(1);
    }
}
?>
done
