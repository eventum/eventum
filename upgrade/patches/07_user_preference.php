<?php

/**
 * Move user preferences to a separate table.
 *
 * Note: the usr_preference column has not been dropped,
 * as want to make sure everyone migrates their preferences before deleting this.
 */

/** @var DbInterface $db */
$db->query('CREATE TABLE {{%user_preference}}
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
)');

$db->query('CREATE TABLE {{%user_project_preference}}
(
    upp_usr_id int(11) unsigned NOT NULL,
    upp_prj_id int(11) unsigned NOT NULL,
    upp_receive_assigned_email tinyint(1) DEFAULT 1,
    upp_receive_new_issue_email tinyint(1) DEFAULT 0,
    upp_receive_copy_of_own_action tinyint(1) DEFAULT 0,
    PRIMARY KEY(upp_usr_id, upp_prj_id)
)');

$sql = 'SELECT
            usr_id,
            usr_preferences
        FROM
            {{%user}}
        ORDER BY
            usr_id DESC';
$res = $db->getAll($sql);

/** @var Closure $log */

foreach ($res as $row) {
    $usr_id = $row['usr_id'];
    $log($usr_id);

    $old_preferences = unserialize($row['usr_preferences']);
    if ($old_preferences === false) {
        $log('... skipped');
        continue;
    }

    $new_preferences = $old_preferences;

    $new_preferences['email_refresh_rate'] = $old_preferences['emails_refresh_rate'];
    unset($old_preferences['emails_refresh_rate']);

    $new_preferences['auto_append_email_sig'] = !empty($old_preferences['auto_append_sig']) ? $old_preferences['auto_append_sig'] : 0;
    unset($old_preferences['auto_append_sig']);

    $new_preferences['receive_assigned_email'] = $old_preferences['receive_assigned_emails'];
    unset($new_preferences['receive_assigned_emails']);

    $new_preferences['receive_new_issue_email'] = $old_preferences['receive_new_emails'];
    unset($new_preferences['receive_new_emails']);

    // FIXME: is the 1 here hardcoded project id? boo!
    $new_preferences['receive_copy_of_own_action'][1] = 0;

    Prefs::set($usr_id, $new_preferences);
}
