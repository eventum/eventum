<?php

db_query("CREATE TABLE %TABLE_PREFIX%user_preference
(
    upr_usr_id int(11) unsigned NOT NULL,
    upr_timezone varchar(100) NOT NULL,
    upr_week_firstday tinyint(1) NOT NULL DEFAULT 0,
    upr_list_refresh_rate int(5) DEFAULT 5,
    upr_email_refresh_rate int(5) DEFAULT 5,
    upr_email_signature longtext,
    upr_auto_append_email_sig tinyint(1) DEFAULT 0,
    upr_auto_append_note_sig tinyint(1) DEFAULT 0,
    upr_auto_close_popup_window tinyint(1) DEFAULT 0,
    PRIMARY KEY(upr_usr_id)
)");

db_query("CREATE TABLE %TABLE_PREFIX%user_project_preference
(
    upp_usr_id int(11) unsigned NOT NULL,
    upp_prj_id int(11) unsigned NOT NULL,
    upp_receive_assigned_email tinyint(1) DEFAULT 1,
    upp_receive_new_issue_email tinyint(1) DEFAULT 0,
    upp_receive_copy_of_own_action tinyint(1) DEFAULT 0,
    PRIMARY KEY(upp_usr_id, upp_prj_id)
)");

$sql = "SELECT
            usr_id,
            usr_preferences
        FROM
            " . APP_DEFAULT_DB . '.' . APP_TABLE_PREFIX . "user
        ORDER BY
            usr_id DESC";
$res = db_getall($sql);
if (PEAR::isError($res)) {
    echo $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
    exit(1);
}

foreach ($res as $row) {
    $usr_id = $row['usr_id'];
    $old_preferences = unserialize($row['usr_preferences']);
    echo "$usr_id\n";

    $new_preferences = $old_preferences;

    $new_preferences['email_refresh_rate'] = $old_preferences['emails_refresh_rate'];
    unset($old_preferences['emails_refresh_rate']);

    $new_preferences['auto_append_email_sig'] = $old_preferences['auto_append_sig'];
    unset($old_preferences['auto_append_sig']);

    $new_preferences['receive_assigned_email'] = $old_preferences['receive_assigned_emails'];
    unset($new_preferences['receive_assigned_emails']);

    $new_preferences['receive_new_issue_email'] = $old_preferences['receive_new_emails'];
    unset($new_preferences['receive_new_email']);

    $new_preferences['receive_copy_of_own_action'][1] = 0;

    Prefs::set($usr_id, $new_preferences);
}
