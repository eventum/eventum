# Deleting Issues

## Here's an example of how to make this possible through the web interface

1. Create a status of **deleted** with a rank of _x_ (_x_ can be any unusued number).
2. Create the php page above replacing **MY_WHERE_CLAUSE** with **iss_sta_id=_x_** (_x_ is your selected rank)
3. Save the file as **delete_issues.php** in your eventum/misc folder.
4. Add **delete_issues.php** to your cron jobs to run as often as you would like this delete task to run.

### Some more explanation

-   The iss_sta_id is _not_ the rank field!
-   It the value of the iss_sta_id field is based on the numbers of statuses in your installation an will increase by on if you add a new status.

#### Detailed Example

The following script worked for me:

```php
     <?php
     include_once("../config.inc.php");
     include_once(APP_INC_PATH . "db_access.php");

     $issues = $GLOBALS["db_api"]->dbh->getCol('SELECT iss_id FROM eventum_issue, eventum_status WHERE iss_sta_id = sta_id AND sta_title = "Delete this Issue"');

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
        echo "Issue #$issue_id deleted \n";
     }
     function delete_data($sql)
     {
         $res = $GLOBALS["db_api"]->dbh->query($sql);
        if (DB::isError($res)) {
            echo print_r($res);
            exit;
        }
     }
```

-   The important first line is the ending: The Issues will be selected by the value of the field of the the status title name, which is in this case "Delete this Issue".
-   you can also change the first line is the ending to the internal status id: **WHERE iss_sta_id = 8** the Status field number is the _selector_. It is the **internal number** of the status field. Do not confuse it with the **"RANK"**number which can be inserted in the "Custom fields" Backend mask.

You can get the value of the iss_sta_id by looking in the MySQL table or by looking in the URL field of your browser after editing a Status: the last part contains the id:

`.../eventum-1.7.1/manage/statuses.php?cat=edit&id=8`

-   Check carefully the values of your status ids before using the script!

[Raimunds](User:Raimunds "wikilink") 17:34, 2 Oct 2006 (CEST)
