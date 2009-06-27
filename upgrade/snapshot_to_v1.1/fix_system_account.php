<?php
require_once dirname(__FILE__) . '/../init.php';

$stmt = "SELECT MAX(usr_id)+1 FROM eventum_user";
$res = DB_Helper::getInstance()->getOne($stmt);
if (PEAR::isError($res)) {
	echo 'ERROR: ', $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
    exit(1);
}
$new_usr_id = $res;

$stmt = "UPDATE eventum_user SET usr_id = $new_usr_id WHERE usr_id = 1";
$res = DB_Helper::getInstance()->query($stmt);
if (PEAR::isError($res)) {
	echo 'ERROR: ', $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
    exit(1);
}

$fixes = array(
    "UPDATE eventum_custom_filter SET cst_usr_id = $new_usr_id WHERE cst_usr_id = 1",
    "UPDATE eventum_issue SET iss_usr_id = $new_usr_id WHERE iss_usr_id = 1",
    "UPDATE eventum_issue_attachment SET iat_usr_id = $new_usr_id WHERE iat_usr_id = 1",
    "UPDATE eventum_issue_requirement SET isr_usr_id = $new_usr_id WHERE isr_usr_id = 1",
    "UPDATE eventum_issue_user SET isu_usr_id = $new_usr_id WHERE isu_usr_id = 1",
    "UPDATE eventum_note SET not_usr_id = $new_usr_id WHERE not_usr_id = 1",
    "UPDATE eventum_project SET prj_lead_usr_id = $new_usr_id WHERE prj_lead_usr_id = 1",
    "UPDATE eventum_project_user SET pru_usr_id = $new_usr_id WHERE pru_usr_id = 1",
    "UPDATE eventum_subscription SET sub_usr_id = $new_usr_id WHERE sub_usr_id = 1",
    "UPDATE eventum_time_tracking SET ttr_usr_id = $new_usr_id WHERE ttr_usr_id = 1",
    "UPDATE eventum_phone_support SET phs_usr_id = $new_usr_id WHERE phs_usr_id = 1",
    "UPDATE eventum_reminder_action_list SET ral_usr_id = $new_usr_id WHERE ral_usr_id = 1",
    "UPDATE eventum_news SET nws_usr_id = $new_usr_id WHERE nws_usr_id = 1",
    "UPDATE eventum_round_robin_user SET rru_usr_id = $new_usr_id WHERE rru_usr_id = 1",
    "UPDATE eventum_email_draft SET emd_usr_id = $new_usr_id WHERE emd_usr_id = 1"
);
foreach ($fixes as $stmt) {
    $res = DB_Helper::getInstance()->query($stmt);
    if (PEAR::isError($res)) {
		echo 'ERROR: ', $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
        exit(1);
    }
}

// add the system account as user id == 1
$stmt = "INSERT INTO
            eventum_user
         (
            usr_id,
            usr_created_date,
            usr_password,
            usr_full_name,
            usr_email,
            usr_role,
            usr_preferences
         ) VALUES (
            1,
            '" . Date_Helper::getCurrentDateGMT() . "',
            '14589714398751513457adf349173434',
            'system',
            'system-account@example.com',
            5,
            ''
         )";
$res = DB_Helper::getInstance()->query($stmt);
if (PEAR::isError($res)) {
	echo 'ERROR: ', $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
    exit(1);
}
