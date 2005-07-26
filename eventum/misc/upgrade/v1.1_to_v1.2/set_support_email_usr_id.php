<?php
/*
 * Runonce script to set the sup_usr_id field in support_email
 */
include_once("../../../config.inc.php");
include_once(APP_INC_PATH . "db_access.php");


$stmt = "SELECT
            sup_id,
            sup_from
         FROM
            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
         WHERE
            sup_usr_id IS NULL AND
            sup_iss_id != 0";
$res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
foreach ($res as $sup_id => $email) {
    $usr_id = User::getUserIDByEmail(Mail_API::getEmailAddress($email));
    if (!empty($usr_id)) {
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                 SET
                    sup_usr_id = $usr_id
                 WHERE
                    sup_id = $sup_id";
        $update = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($update)) {
            echo "<pre>";var_dump($update);echo "</pre>";
            exit(1);
        }
    }
}
echo "complete";

?>