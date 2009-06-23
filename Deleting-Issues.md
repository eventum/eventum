It is impossible to delete issues from Eventum through the user interface. However, there currently are two methods of removing issues.

1. Delete the project. By deleting the project, you will delete ALL issues in this project with no way to recover them.

2. Place the following code in a file in your eventum/misc directory. Change "MY_WHERE_CLAUSE" to be a specific issue ID, or change \$issues to be an array of issue IDs.

#### Original Script

    <?php
    include_once("../init.php");
    include_once(APP_INC_PATH . "db_access.php");


    $issues = $GLOBALS["db_api"]->dbh->getCol('SELECT iss_id FROM eventum_issue WHERE MY_WHERE_CLAUSE');

    foreach ($issues as $issue_id) {

        delete_data("DELETE FROM eventum_issue_association WHERE isa_issue_id = $issue_id");
        delete_data("DELETE FROM eventum_issue_attachment WHERE iat_iss_id = $issue_id");
        delete_data("DELETE FROM eventum_issue_checkin WHERE isc_iss_id = $issue_id");
        delete_data("DELETE FROM eventum_issue_history WHERE his_iss_id = $issue_id");
        delete_data("DELETE FROM eventum_issue_requirement WHERE isr_iss_id = $issue_id");
        delete_data("DELETE FROM eventum_issue_user WHERE isu_iss_id = $issue_id");

        delete_data("DELETE FROM eventum_note WHERE not_iss_id = $issue_id");
        delete_data("DELETE FROM eventum_subscription WHERE sub_iss_id = $issue_id");
        delete_data("DELETE FROM eventum_support_email WHERE sup_iss_id = $issue_id");
        delete_data("DELETE FROM eventum_time_tracking WHERE ttr_iss_id = $issue_id");
        delete_data("DELETE FROM eventum_issue_custom_field WHERE icf_iss_id = $issue_id");
        delete_data("DELETE FROM eventum_phone_support WHERE phs_iss_id = $issue_id");
        delete_data("DELETE FROM eventum_reminder_requirement WHERE rer_iss_id = $issue_id");
        delete_data("DELETE FROM eventum_reminder_history WHERE rmh_iss_id = $issue_id");
        delete_data("DELETE FROM eventum_email_draft WHERE emd_iss_id = $issue_id");
        delete_data("DELETE FROM eventum_irc_notice WHERE ino_iss_id = $issue_id");
        delete_data("DELETE FROM eventum_issue_user_replier WHERE iur_iss_id = $issue_id");
        delete_data("DELETE FROM eventum_mail_queue WHERE maq_iss_id = $issue_id");

        delete_data("DELETE FROM eventum_issue WHERE iss_id = $issue_id");

        echo "Issue #$issue_id deleted<br />\n";

    }

    function delete_data($sql)
    {
        $res = $GLOBALS["db_api"]->dbh->query($sql);
        if (DB::isError($res)) {
            echo print_r($res);
            exit;
        }
    }
    ?>

#### Modified script

The above script must be modified for Eventum 2.01:

    <?php
    include_once("../init.php");

    require_once(APP_INC_PATH . "db_access.php");

    $res = $GLOBALS["db_api"]->dbh->query('SELECT iss_id FROM eventum_issue where iss_id in (x, y, z)');

    while ($res->fetchInto($row)) {

        $issue_id = $row[0];

        delete_data("DELETE FROM eventum_issue_association WHERE isa_issue_id = $issue_id");
        delete_data("DELETE FROM eventum_issue_attachment WHERE iat_iss_id = $issue_id");
        delete_data("DELETE FROM eventum_issue_checkin WHERE isc_iss_id = $issue_id");
        delete_data("DELETE FROM eventum_issue_history WHERE his_iss_id = $issue_id");
        delete_data("DELETE FROM eventum_issue_requirement WHERE isr_iss_id = $issue_id");
        delete_data("DELETE FROM eventum_issue_user WHERE isu_iss_id = $issue_id");

        delete_data("DELETE FROM eventum_note WHERE not_iss_id = $issue_id");
        delete_data("DELETE FROM eventum_subscription WHERE sub_iss_id = $issue_id");
        delete_data("DELETE FROM eventum_support_email WHERE sup_iss_id = $issue_id");
        delete_data("DELETE FROM eventum_time_tracking WHERE ttr_iss_id = $issue_id");
        delete_data("DELETE FROM eventum_issue_custom_field WHERE icf_iss_id = $issue_id");
        delete_data("DELETE FROM eventum_phone_support WHERE phs_iss_id = $issue_id");
        delete_data("DELETE FROM eventum_reminder_requirement WHERE rer_iss_id = $issue_id");
        delete_data("DELETE FROM eventum_reminder_history WHERE rmh_iss_id = $issue_id");
        delete_data("DELETE FROM eventum_email_draft WHERE emd_iss_id = $issue_id");
        delete_data("DELETE FROM eventum_irc_notice WHERE ino_iss_id = $issue_id");
        delete_data("DELETE FROM eventum_issue_user_replier WHERE iur_iss_id = $issue_id");
        delete_data("DELETE FROM eventum_mail_queue WHERE maq_iss_id = $issue_id");

        delete_data("DELETE FROM eventum_issue WHERE iss_id = $issue_id");

        echo "Issue #$issue_id deleted<br />\n";

    }

    function delete_data($sql)
    {
        $res = $GLOBALS["db_api"]->dbh->query($sql);
        if (DB::isError($res)) {
            echo "<pre>";print_r($res);echo "

";

`       exit;`
`   }`

} ?\>

</pre>
#### Modified script

A modified script for Eventum 2.1.1: This script use the global variable APP_DEFAULT_DB and APP_TABLE_PREFIX

    <?php
    require_once(dirname(__FILE__) . "/../init.php");
    require_once(APP_INC_PATH . "db_access.php");

    // rank of "delete" state is 99
    $res = $GLOBALS["db_api"]->dbh->query("SELECT iss_id FROM " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue, " .
    APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status WHERE iss_sta_id = sta_id AND sta_rank = 99");

    while ($res->fetchInto($row)) {

        $issue_id = $row[0];
        // echo "deleting issue number  $issue_id\n";
        delete_data("DELETE FROM " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_association WHERE isa_issue_id = $issue_id");
        delete_data("DELETE FROM " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_attachment WHERE iat_iss_id = $issue_id");
        delete_data("DELETE FROM " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_checkin WHERE isc_iss_id = $issue_id");
        delete_data("DELETE FROM " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_history WHERE his_iss_id = $issue_id");
        delete_data("DELETE FROM " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_requirement WHERE isr_iss_id = $issue_id");
        delete_data("DELETE FROM " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user WHERE isu_iss_id = $issue_id");

        delete_data("DELETE FROM " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "note WHERE not_iss_id = $issue_id");
        delete_data("DELETE FROM " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "subscription WHERE sub_iss_id = $issue_id");
        delete_data("DELETE FROM " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email WHERE sup_iss_id = $issue_id");
        delete_data("DELETE FROM " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "time_tracking WHERE ttr_iss_id = $issue_id");
        delete_data("DELETE FROM " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_custom_field WHERE icf_iss_id = $issue_id");
        delete_data("DELETE FROM " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "phone_support WHERE phs_iss_id = $issue_id");
        delete_data("DELETE FROM " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_requirement WHERE rer_iss_id = $issue_id");
        delete_data("DELETE FROM " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_history WHERE rmh_iss_id = $issue_id");
        delete_data("DELETE FROM " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_draft WHERE emd_iss_id = $issue_id");
        delete_data("DELETE FROM " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "irc_notice WHERE ino_iss_id = $issue_id");
        delete_data("DELETE FROM " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user_replier WHERE iur_iss_id = $issue_id");
        delete_data("DELETE FROM " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "mail_queue WHERE maq_iss_id = $issue_id");

        delete_data("DELETE FROM " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue WHERE iss_id = $issue_id");

        echo "Issue #$issue_id deleted<br />\n";

    }

    function delete_data($sql)
    {
        $res = $GLOBALS["db_api"]->dbh->query($sql);
        if (DB::isError($res)) {
            echo "<pre>";print_r($res);echo "

";

`       exit;`
`   }`

}

?\>

</pre>
A modified Script can be found on the [discussion page](Talk:Deleting Issues "wikilink")