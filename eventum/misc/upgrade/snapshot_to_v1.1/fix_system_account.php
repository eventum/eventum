<?php
include_once("../../../config.inc.php");
include_once(APP_INC_PATH . "db_access.php");
include_once(APP_INC_PATH . "class.date.php");

$stmt = "SELECT MAX(usr_id)+1 FROM eventum_user";
$new_usr_id = $GLOBALS["db_api"]->dbh->getOne($stmt);
if (PEAR::isError($new_usr_id)) {
    echo "ERROR:<br /><br />";
    var_dump($new_usr_iod);
    exit(1);
}

$stmt = "UPDATE eventum_user SET usr_id = $new_usr_id WHERE usr_id = 1";
$res = $GLOBALS["db_api"]->dbh->query($stmt);
if (PEAR::isError($res)) {
    echo "ERROR:<br /><br />";
    var_dump($res);
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
    $res = $GLOBALS["db_api"]->dbh->query($stmt);
    if (PEAR::isError($res)) {
        echo "ERROR:<br /><br />";
        var_dump($res);
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
            '" . Date_API::getCurrentDateGMT() . "',
            '14589714398751513457adf349173434',
            'system',
            'system-account@example.com',
            5,
            ''
         )";
$res = $GLOBALS["db_api"]->dbh->query($stmt);
if (PEAR::isError($res)) {
    echo "ERROR:<br /><br />";
    var_dump($res);
    exit(1);
}

?>