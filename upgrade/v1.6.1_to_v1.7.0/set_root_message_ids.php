<?php
/*
 * Runonce script to set iss_root_message_id
 */
require_once dirname(__FILE__) . '/../init.php';

$stmt = "SELECT
            iss_id
         FROM
            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
         WHERE
            iss_root_message_id IS NULL";
$issues = DB_Helper::getInstance()->getCol($stmt);
foreach ($issues as $issue_id) {
    $sql = "SELECT
                sup_message_id
            FROM
                " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
            WHERE
                sup_iss_id = $issue_id
            ORDER BY
                sup_date ASC
            LIMIT 1";
    $res = DB_Helper::getInstance()->getOne($sql);
    if (PEAR::isError($res)) {
		echo "<pre>";
		print_r($res);
		echo "</pre>";
        exit;
    }
    if (empty($res)) {
        $msg_id = Mail_Helper::generateMessageID();
    } else {
        $msg_id = $res;
    }
    $sql = "UPDATE
                " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
            SET
                iss_root_message_id = '" . Misc::escapeString($msg_id) . "'
            WHERE
                iss_id = $issue_id";
    $res = DB_Helper::getInstance()->query($sql);
    if (PEAR::isError($res)) {
		echo "<pre>";
		print_r($res);
		echo "</pre>";
        exit;
    }
}
?>
done
