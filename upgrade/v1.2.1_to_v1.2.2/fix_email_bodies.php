<?php
require_once dirname(__FILE__) . '/../init.php';

ini_set("memory_limit", "512M");

$stmt = "SELECT
            seb_sup_id,
            seb_full_email
         FROM
            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email_body
         ORDER BY
            seb_sup_id";
$res = DB_Helper::getInstance()->getAssoc($stmt);
foreach ($res as $sup_id => $full_message) {
    $structure = Mime_Helper::decode($full_message, true, true);
    $body = Mime_Helper::getMessageBody($structure);

    $stmt = "UPDATE
                " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email_body
             SET
                seb_body='" . Misc::escapeString($body) . "'
             WHERE
                seb_sup_id=$sup_id";
    $res = DB_Helper::getInstance()->query($stmt);
    if (PEAR::isError($res)) {
		echo 'ERROR: ', $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
        exit(1);
    }
    echo "fixed email #$sup_id<br />";
    flush();
}
echo "complete";
