<?php
include_once("../../../config.inc.php");
include_once(APP_INC_PATH . "db_access.php");
include_once(APP_INC_PATH . "class.mime_helper.php");

ini_set("memory_limit", "512M");

$stmt = "SELECT
            seb_sup_id,
            seb_full_email
         FROM
            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email_body
         ORDER BY
            seb_sup_id";
$res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
foreach ($res as $sup_id => $full_message) {
    $structure = Mime_Helper::decode($full_message, true, true);
    $body = Mime_Helper::getMessageBody($structure);

    $stmt = "UPDATE
                " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email_body
             SET
                seb_body='" . Misc::escapeString($body) . "'
             WHERE
                seb_sup_id=$sup_id";
    $update = $GLOBALS["db_api"]->dbh->query($stmt);
    if (PEAR::isError($update)) {
        echo "<pre>";var_dump($update);echo "</pre>";
        exit(1);
    }
    echo "fixed email #$sup_id<br />";
    flush();
}
echo "complete";

?>