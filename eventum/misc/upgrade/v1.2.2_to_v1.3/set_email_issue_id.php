<?php
include_once("../../../config.inc.php");
include_once(APP_INC_PATH . "db_access.php");
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.mail.php");
include_once(APP_PEAR_PATH . "Mail/mimeDecode.php");

ini_set("memory_limit", '1024M');

$stmt = "SELECT
            maq_id,
            CONCAT(maq_headers,'\n',maq_body)
         FROM
            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "mail_queue
         WHERE
            maq_iss_id IS NULL";
$res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
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
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
    }
}

?>
done