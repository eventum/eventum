<?php
require_once dirname(__FILE__) . '/../init.php';

$sql = "SELECT
            usr_id,
            usr_role
        FROM
            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
        WHERE
            usr_role != ''";
$res = DB_Helper::getInstance()->getAssoc($sql);
if (PEAR::isError($res)) {
	echo 'ERROR: ', $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
    exit(1);
}

foreach ($res as $usr_id => $role) {
    $sql = "UPDATE
                " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_user
            SET
                pru_role = $role
            WHERE
                pru_usr_id = $usr_id";
    $res = DB_Helper::getInstance()->query($sql);
    if (PEAR::isError($res)) {
		echo 'ERROR: ', $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
        exit(1);
    }

    // handle preferences
    $prefs = Prefs::get($usr_id);
    $receive_assigned_emails = @$prefs['receive_assigned_emails'];
    if (empty($receive_assigned_emails)) {
        $receive_assigned_emails = 0;
    }
    $receive_new_emails = @$prefs['receive_new_emails'];
    if (empty($receive_new_emails)) {
        $receive_new_emails = 0;
    }

    $projects = Project::getAssocList($usr_id);
    $prefs['receive_new_emails'] = array();
    $prefs['receive_assigned_emails'] = array();
    foreach ($projects as $prj_id => $project_title) {
        $prefs['receive_assigned_emails'][$prj_id] = $receive_assigned_emails;
        $prefs['receive_new_emails'][$prj_id] = $receive_new_emails;
    }
    $sql = "UPDATE
                " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
            SET
                usr_preferences='" . Misc::escapeString(serialize($prefs)) . "'
            WHERE
                usr_id=$usr_id";
    $res = DB_Helper::getInstance()->query($sql);
    if (PEAR::isError($res)) {
		echo 'ERROR: ', $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
		exit(1);
    }
}

?>
done.<br />

Please check that you can successfully login. If everything looks in order you can go ahead and
run the following statement to drop a now redundant column.<br />
<pre>
ALTER TABLE <?php echo APP_TABLE_PREFIX; ?>user DROP column usr_role;
</pre>
