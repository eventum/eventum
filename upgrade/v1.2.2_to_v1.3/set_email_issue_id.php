<?php
require_once dirname(__FILE__) . '/../init.php';

ini_set("memory_limit", '1024M');

$stmt = "SELECT
            maq_id,
            CONCAT(maq_headers,'\n',maq_body)
         FROM
            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "mail_queue
         WHERE
            maq_iss_id IS NULL";
$res = DB_Helper::getInstance()->getAssoc($stmt);
foreach ($res as $id => $headers) {
    $decode = new Mail_mimeDecode($headers);
    $structure = $decode->decode();
    $headers = $structure->headers;
    if (count($headers) > 1) {
        preg_match("/Issue #(\d*)/", $headers["subject"], $matches);
        if (count($matches) < 1) {
            preg_match("/[issue|note]\-(\d*)@.*/", $headers["from"], $matches);
            if (count($matches) < 1) {
                continue;
            }
        }
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "mail_queue
                 SET
                    maq_iss_id = '" . $matches[1] . "',
                    maq_subject = '" . Misc::escapeString($headers["subject"]) . "'
                 WHERE
                    maq_id = $id";
        $res = DB_Helper::getInstance()->query($stmt);
    }
}

?>
done
