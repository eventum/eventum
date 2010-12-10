<?php
/*
 * Runonce script to set the history type for previously entered events
 */
require_once dirname(__FILE__) . '/../init.php';

$patterns = array();
$patterns[] = array(
    "pattern"   =>  "/Issue manually set to status '.*' by (.*)/i",
    "status"    =>  'status_changed',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/Attachment removed by (.*)/i",
    "status"    =>  'attachment_removed',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/Attachment uploaded by (.*)/i",
    "status"    =>  'attachment_added',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/Custom field updated by (.*)/i",
    "status"    =>  'custom_field_updated',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/Email message saved as a draft by (.*)/i",
    "status"    =>  'draft_added',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/Email response saved as a draft by (.*)/i",
    "status"    =>  'draft_added',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/Email message draft updated by (.*)/i",
    "status"    =>  'draft_updated',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/New requirement submitted by (.*)/i",
    "status"    =>  'impact_analysis_added',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/Impact analysis submitted by (.*)/i",
    "status"    =>  'impact_analysis_updated',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/Impact analysis removed by (.*)/i",
    "status"    =>  'impact_analysis_removed',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/Issue automatically set to status '.*/i",
    "status"    =>  'status_changed',
    "set_user"  =>  true,
    "user_id"   =>  APP_SYSTEM_USER_ID
);
$patterns[] = array(
    "pattern"   =>  "/Issue remotely locked by (.*)/i",
    "status"    =>  'remote_locked',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/Status changed to 'Assigned' because (.*) remotely locked the issue/i",
    "status"    =>  'remote_status_change',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/Issue remotely unlocked by (.*)/i",
    "status"    =>  'remote_unlock',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/Issue remotely assigned to .* by (.*)/i",
    "status"    =>  'remote_assigned',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/User .* remotely added to authorized repliers by (.*)/i",
    "status"    =>  'remote_replier_added',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/Status remotely changed to '.*' by (.*)/i",
    "status"    =>  'remote_status_change',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/Issue opened by (.*)/i",
    "status"    =>  'issue_opened',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/Issue auto-assigned to .*/i",
    "status"    =>  'issue_auto_assigned',
    "set_user"  =>  false
);
$patterns[] = array(
    "pattern"   =>  "/Issue auto-assigned to .* (RR)/i",
    "status"    =>  'rr_issue_assigned',
    "set_user"  =>  true,
    "user_id"   =>  APP_SYSTEM_USER_ID
);
$patterns[] = array(
    "pattern"   =>  "/Issue locked by (.*)/i",
    "status"    =>  'issue_locked',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/Status changed to 'Assigned' because (.*) locked the issue/i",
    "status"    =>  'status_changed',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/Issue unlocked by (.*)/i",
    "status"    =>  'issue_unlocked',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/The details for issue .* were updated by (.*) and the changes propagated to the duplicated issues/i",
    "status"    =>  'duplicate_update',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/Duplicate flag was reset by (.*)/i",
    "status"    =>  'duplicate_removed',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/Issue marked as a duplicate of issue #.* by (.*)/i",
    "status"    =>  'duplicate_added',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/Issue opened anonymously/i",
    "status"    =>  'issue_opened_anon',
    "set_user"  =>  true,
    "user_id"   =>  APP_SYSTEM_USER_ID
);
$patterns[] = array(
    "pattern"   =>  "/Issue opened remotely/i",
    "status"    =>  'remote_issue_created',
    "set_user"  =>  false
);
$patterns[] = array(
    "pattern"   =>  "/Issue updated to status '.*' by (.*)/i",
    "status"    =>  'issue_closed',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/Issue updated \(.*\) by (.*)/i",
    "status"    =>  'issue_updated',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/Issue assigned to .* by (.*)/i",
    "status"    =>  'user_associated',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/Issue assignments removed/i",
    "status"    =>  'user_all_unassociated',
    "set_user"  =>  false
);
$patterns[] = array(
    "pattern"   =>  "/Issue opened by (.*)/i",
    "status"    =>  'issue_opened',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/.* added to the authorized repliers list/i",
    "status"    =>  'replier_added',
    "set_user"  =>  false
);
$patterns[] = array(
    "pattern"   =>  "/Issue assigned to .* by (.*)/i",
    "status"    =>  'user_associated',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/Initial Impact Analysis for issue set by (.*)/i",
    "status"    =>  'impact_analysis_added',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/Remote note added by (.*)/i",
    "status"    =>  'remote_note_added',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/Note added by (.*)/i",
    "status"    =>  'note_added',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/Note removed by (.*)/i",
    "status"    =>  'note_removed',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/Notification list entry.*removed by (.*)/i",
    "status"    =>  'notification_removed',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/Notification list entry.*added by (.*)/i",
    "status"    =>  'notification_added',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/Notification list entry.*updated by (.*)/i",
    "status"    =>  'notification_updated',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/Phone Support entry submitted by (.*)/i",
    "status"    =>  'phone_entry_added',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/Phone Support entry removed by (.*)/i",
    "status"    =>  'phone_entry_removed',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/SCM Checkins removed by (.*)/i",
    "status"    =>  'scm_checkin_removed',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/SCM Checkins associated SCM user/i",
    "status"    =>  'scm_checkin_associated',
    "set_user"  =>  false
);
$patterns[] = array(
    "pattern"   =>  "/Support email \(subject:.*\) associated.*/i",
    "status"    =>  'email_associated',
    "set_user"  =>  false
);
$patterns[] = array(
    "pattern"   =>  "/Support email \(subject:.*\) disassociated.*/i",
    "status"    =>  'email_disassociated',
    "set_user"  =>  false
);
$patterns[] = array(
    "pattern"   =>  "/Outgoing email sent by (.*)/i",
    "status"    =>  'email_sent',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/Time tracking entry removed by (.*)/i",
    "status"    =>  'time_removed',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/Time tracking entry submitted by (.*)/i",
    "status"    =>  'time_added',
    "set_user"  =>  true
);
$patterns[] = array(
    "pattern"   =>  "/Time tracking entry submitted remotely by (.*)/i",
    "status"    =>  'remote_time_added',
    "set_user"  =>  true
);


// loop through all history without a status
$sql = "SELECT
            his_id,
            his_summary
        FROM
            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_history
        WHERE
            his_htt_id = ''";

//$sql .= " AND his_summary LIKE 'Time tracking entry submitted remotely by %' LIMIT 10";
$result = DB_Helper::getInstance()->getAll($sql, DB_FETCHMODE_ASSOC);

$updated = 0;
$skipped = 0;
foreach ($result as $row) {
    echo $row["his_summary"] . "<br />";
    $match = getMatch($row["his_summary"]);
    if ($match) {
        update($row["his_id"], $match);
        $updated++;
    } else {
        $skipped++;
    }
}
echo "Updated: $updated<br />
Skipped: $skipped";

function getMatch($desc)
{
    GLOBAL $patterns;
    $return = array();
    foreach ($patterns as $id => $data) {
        if (preg_match($data["pattern"], $desc, $matches)) {
            $return["status"] = $data["status"];
            if ($data["set_user"] == true) {
                // try to get user id from name
                $return["user"] = getUser($matches[1]);
            }
            return $return;
        }
    }
    return false;
}

function getUser($name)
{
    $sql = "SELECT
                usr_id
            FROM
                " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
            WHERE
                usr_full_name = '" . trim(Misc::escapeString($name)) . "'";
    return DB_Helper::getInstance()->getOne($sql);
}

function update($his_id, $match)
{
    $sql = "UPDATE
                " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_history
            SET
                his_htt_id = '" . History::getTypeID($match["status"]) . "'";
            if ($match["user"] != false) {
                $sql .= ", his_usr_id = " . $match["user"] . " ";
            }
            $sql .= "
            WHERE
                his_id = $his_id";
    $res = DB_Helper::getInstance()->query($sql);
    if (PEAR::isError($res)) {
		echo "<pre>";
		print_r($res);
        exit(1);
    }
}
