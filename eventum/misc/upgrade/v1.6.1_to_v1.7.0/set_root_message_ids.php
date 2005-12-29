<?php
/*
 * Runonce script to set iss_root_message_id
 */
include_once("../../../config.inc.php");
include_once(APP_INC_PATH . "db_access.php");
include_once(APP_INC_PATH . "class.issue.php");

$stmt = "SELECT
            iss_id
         FROM
            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
         WHERE
            iss_root_message_id IS NULL";
$issues = $GLOBALS["db_api"]->dbh->getCol($stmt);
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
    $res = $GLOBALS["db_api"]->dbh->getOne($sql);
    if (PEAR::isError($res)) {
        echo "<pre>";print_r($res);echo "</pre>";
        exit;
    }
    if (empty($res)) {
        $msg_id = Mail_API::generateMessageID();
    } else {
        $msg_id = $res;
    }
    $sql = "UPDATE
                " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
            SET
                iss_root_message_id = '" . Misc::escapeString($msg_id) . "'
            WHERE
                iss_id = $issue_id";
    $res = $GLOBALS["db_api"]->dbh->query($sql);
    if (PEAR::isError($res)) {
        echo "<pre>";print_r($res);echo "</pre>";
        exit;
    }
}
?>
done