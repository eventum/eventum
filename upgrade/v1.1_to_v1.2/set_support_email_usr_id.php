<?php
/*
 * Runonce script to set the sup_usr_id field in support_email
 */
require_once dirname(__FILE__) . '/../init.php';

$stmt = "SELECT
            sup_id,
            sup_from
         FROM
            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
         WHERE
            sup_usr_id IS NULL AND
            sup_iss_id != 0";
$res = DB_Helper::getInstance()->getAssoc($stmt);
foreach ($res as $sup_id => $email) {
    $usr_id = User::getUserIDByEmail(Mail_Helper::getEmailAddress($email));
    if (!empty($usr_id)) {
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                 SET
                    sup_usr_id = $usr_id
                 WHERE
                    sup_id = $sup_id";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
			echo 'ERROR: ', $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
            exit(1);
        }
    }
}
echo "complete\n";
