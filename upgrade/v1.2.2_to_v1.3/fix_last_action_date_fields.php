<?php
require_once dirname(__FILE__) . '/../init.php';

function updateActionDate($type, $issue_id, $max, $action_type)
{
    if (!empty($max)) {
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 SET";
        if ($type == 'public') {
            $stmt .= "
                        iss_last_public_action_date='$max',
                        iss_last_public_action_type='$action_type'
                     WHERE
                        iss_id=$issue_id AND
                        '$max' > IFNULL(iss_last_public_action_date, '0000-00-00 00:00:00')";
        } else {
            $stmt .= "
                        iss_last_internal_action_date='$max',
                        iss_last_internal_action_type='$action_type'
                     WHERE
                        iss_id=$issue_id AND
                        '$max' > IFNULL(iss_last_internal_action_date, '0000-00-00 00:00:00')";
        }
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
			echo "<pre>";
			print_r($res);
			exit(1);
        }
    }
}

/*
  iss_created_date datetime NOT NULL default '0000-00-00 00:00:00',
  iss_updated_date datetime default NULL,
  iss_last_response_date datetime default NULL,
  iss_first_response_date datetime default NULL,
  iss_closed_date datetime default NULL,
  iss_last_customer_action_date datetime default NULL,

  iss_last_public_action_date datetime default NULL,
  iss_last_public_action_type varchar(20) default NULL,
  iss_last_internal_action_date datetime default NULL,
  iss_last_internal_action_type varchar(20) default NULL,
*/
$fields = array(
    "iss_created_date"              => "created",
    "iss_updated_date"              => "updated",
    "iss_first_response_date"       => "staff response",
    "iss_last_response_date"        => "staff response",
    "iss_last_customer_action_date" => "customer action",
    "iss_closed_date"               => "closed",
);

foreach ($fields as $date_field => $action_type) {
    $stmt = "UPDATE
                " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
             SET
                iss_last_public_action_date=$date_field,
                iss_last_public_action_type='$action_type'
             WHERE
                $date_field > IFNULL(iss_last_public_action_date, '0000-00-00 00:00:00')";
    $res = DB_Helper::getInstance()->query($stmt);
    if (PEAR::isError($res)) {
		echo "<pre>";
		print_r($res);
		exit(1);
    }
}

$stmt = "SELECT iss_id FROM " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue ORDER BY iss_id ASC";
$issues = DB_Helper::getInstance()->getCol($stmt);
foreach ($issues as $issue_id) {
    echo "Updating issue #$issue_id<br />";
    flush();

    // even more public stuff - emails, files
    $stmt = "SELECT sup_usr_id, sup_date FROM " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email WHERE sup_iss_id=$issue_id ORDER BY sup_date DESC LIMIT 1";
    $res = DB_Helper::getInstance()->getRow($stmt);
    if (!empty($res[0])) {
        if (User::getRoleByUser($res[0]) == User::getRoleID('Customer')) {
            updateActionDate('public', $issue_id, $res[1], 'customer action');
        } else {
            updateActionDate('public', $issue_id, $res[1], 'staff response');
        }
    }

    $stmt = "SELECT MAX(iat_created_date) FROM " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_attachment WHERE iat_iss_id=$issue_id";
    $max = DB_Helper::getInstance()->getOne($stmt);
    updateActionDate('public', $issue_id, $max, 'file uploaded');


    // internal only stuff - drafts, notes, phone calls
    $stmt = "SELECT MAX(emd_updated_date) FROM " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_draft WHERE emd_iss_id=$issue_id";
    $max = DB_Helper::getInstance()->getOne($stmt);
    updateActionDate('internal', $issue_id, $max, 'draft saved');

    $stmt = "SELECT MAX(not_created_date) FROM " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "note WHERE not_iss_id=$issue_id";
    $max = DB_Helper::getInstance()->getOne($stmt);
    updateActionDate('internal', $issue_id, $max, 'note');

    $stmt = "SELECT MAX(phs_created_date) FROM " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "phone_support WHERE phs_iss_id=$issue_id";
    $max = DB_Helper::getInstance()->getOne($stmt);
    updateActionDate('internal', $issue_id, $max, 'phone call');
}

?>
done
