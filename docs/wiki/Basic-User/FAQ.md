### FAQ

NOTE: this FAQ is a work in progress and will be updated as needed. If you cannot find the answers in this document, please see the eventum mailing list at <http://lists.mysql.com/eventum-users>. You can search the archive for answers, or subscribe and send your own questions to the list.

You may also reach the Eventum developers by joining irc.freenode.net on channel \#eventum. Help on simple problems can be obtained directly through IRC, but for more complex problems, please send an email to the mailing list above.

## Troubleshooting

### Problem: I get the message "Client does not support authentication protocol"

Solution: See <http://dev.mysql.com/doc/mysql/en/old-client.html>

### Problem: I get the message "Error: Cookies support seem to be disabled in your browser. Please enable this feature and try again".

Solution: There are many things that could cause this problem.

-   Double check that cookies are enabled in your browser.
-   Check that time settings on the client and server are correct. If the client time is set ahead of the server, the cookie could automatically delete itself.
-   Check if you have a firewall or antivirus program that could be blocking cookies from being set.
-   Check if your hostname is correct. Try to use an ip address as APP_HOSTNAME in config.inc.php and browse to that ip.
-   PHP has difficulties setting cookies to 'localhost' when you explicitly set the domain name, for this reason, if you define APP_COOKIE_DOMAIN as NULL it solves the problem:

`define("APP_COOKIE_DOMAIN", NULL);`

-   Also set APP_COOKIE_URL as NULL:

`define("APP_COOKIE_URL", NULL);`

### Problem: The filters on /list.php aren't working.

Solution: Are you using suPHP? There is a known bug in suPHP (see <http://lists.marsching.biz/pipermail/suphp/2004-June/000746.html>) where if a URL ends with '=', parameters to a page are not processed correctly. Please disable suPHP and see if the problem is fixed.

### Problem: None of the pages work

Solution: In the file /path-to-eventum/config/config.php change the following lines

`ini_set("display_errors", 0);`
`error_reporting(0);`

to:

`ini_set("display_errors", 1);`
`error_reporting(E_ALL);`

This enables PHP error reporting so any problems you have will be displayed to the screen when you try to access a page.

### Problem: I get the error "unable to set sender to [@localhost]"

Solution: Many things can cause this error. Here are a few to check.

-   Your hostname is set correctly.
-   Email information is filled out correctly in manage/general.php (web admin page).

### Problem: I get the error "Fatal error: Allowed memory size of 8388608 bytes exhausted"

Solution: Your PHP memory limit is too low. Try increasing it by adding the line

ini_set('memory_limit', '256M'); to /path-to-eventum/config/config.php

### Problem: I want to close an issue but I don't see any Statuses

Solution: Statuses are defined by the Administrator on the Areas/Manage Statuses page. Only those statuses that have been marked as **Closed Context? "Yes"** will show when closing an item, and **Closed Context? "Yes"** statuses are only available when closing an item. These statuses are special because issues that are **Closed** can be hidden in the list view of issues by using the **Hide Close Issues** check box in the lower right hand corner of the footer.

### Problem: I want to close an issue but all I get is a blank screen

Solution: In 'Manage Projects' check to see if you have selected a 'Customer Integration Backend' without actually implementing the back end database. This causes the close dialog to show a blank screen since it is looking up non-existent information. Select 'No Customer Integration' in order to close the trouble tickets.

### Problem: Eventum is using UTC as the default time zone

Solution: Log in and click `Preferences`. On the `Account Preferences` frame, pick your time zone from the `Timezone:` list. To set the default time zone for new users, add this statement to `config/config.php`:

```php
define('APP_DEFAULT_TIMEZONE', 'Europe/Tallinn');
```

The value should be one of the items you see in `Preferences` dropdown.

### Problem: Graph and diagram images not working

Try to increase the _memory_limit_ option in your php.ini file from 8MB (default) to 16MB or maybe 32MB.

### Problem: download_emails script not working

A common problem reported by users is emails not being downloaded. Solution: There are many things that could cause this problem.

-   You read the [[Doing a Fresh Install|System-Admin:-Doing-a-fresh-install]] page, Email Download section, right?
-   Double check [[Troubleshooting Email Integration|System-Admin:-Email-integration#troubleshooting]]
-   Make sure you configured correctly the Email Account, Use the Test Settings Button
-   Verify if there are messages in Associate Emails list, with Standard User or higher
-   Check if Auto-Creation of Issues is enabled
-   Manually check if there are any unread mails to download
-   Run the script manually from cli and web
-   Enable error report in config.php and run the script again

### Problem: Local latin/german/dutch/etc characters display wrong in pages and graphs

Default APP_CHARSET is UTF-8. If you use the proper encoding in your browser, these characters will display fine in pages, but the graphs will still show wrong. You may change the UTF-8 to your local charset in config.php, which should fix your graphs and pages, but not custom fields in 2.1.1.

However, you are recommended to use UTF-8 and fix the graph characters by using custom true type font. See <http://lists.mysql.com/eventum-users/4909>

### Problem: I am not receiving notifications when issues are created/closed

Assuming the CRONs are correctly set and you are receiving other emails from Eventum.

Creation: you do not receive the notifications when you create an issue from Create Issue Form, but you have selected Yes for "Receive emails when all issues are created ?" in your Preferences for that project. This is correct, you will not receive notification of Issue Created for an issue that you submitted from From.

Closing: you do not receive notifications when you close an issue, but you have checked the "Issues are Closed" in "Default Options for Notifications" (General Setup) and you are in the notification list of the issue and have the "Issue is Closed" action associated. This is correct, you will not receive notification of Closing Issue for an issue that you closed.

## Setup

### Problem: I want an incoming email to create an issue and email a pool of people to warn them of the new issue, but I can't see whether the system supports this or not

You need to set up Eventum so that it receives emails from defined email account (a shitty job to do imo). Then enable automatic issue creation from certain email account. After this you can set up a notification message of new issue to who ever it might be useful.

IMAP extension for PHP is required for automatic email retrieval. [1](http://fi.php.net/manual/fi/ref.imap.php).

### Problem: Auto creation of issues does not work when non subject based routing is on

When the same email account is used for both non subject based routing (i.e. when option "Use account for non-subject based email/note/draft routing. Note: If you check this, you cannot leave a copy of messages on the server." is checked) the auto creation does not work and you need to patch one line in one class. Here is the patch

```diff
--- eventum/include/class.support.php~ 2006-03-15 17:34:38.094124892 +0200
+++ eventum/include/class.support.php  2006-03-15 17:34:44.914566574 +0200
@@ -570,7 +570,6 @@
                         return $return;
                     }
                 }
-                return false;
             }
 
             $sender_email = Mail_API::getEmailAddress($email->fromaddress);
```

i.e, by taking out the line that says "return false", both non subject based routing and email creation works using the same email account.

### Problem: I just installed eventum and I cannot log in

You just installed Eventum or renamed your database and cannot log in, even using the admin account. If your database name has a dash "-", you should remove it or replace it with underscore "\_".

Invalid database name examples: "eventum-01", "db-05", "my-eventum".

## General Usage

### I want to delete one or more wrongly created issues

A script to delete issues has been posted at <http://eventum.mysql.org/scripts/delete_issues.php.tar>. (See also comments under [Deleting Issues](../System-Advanced/Deleting-Issues.md) about this script.)

### I have several bogus issues that I need to delete

**Note added by JRM Sep 8/05** - I use the script below as a cron job and delete all issues that have a status set to "Delete this Issue". I created a new status that is called "Delete this Issue" with a color of \#FF0000 (red) so users can use the bulk update tool to set junk/test issues to this status. The cron job runs every minute and cleans out these issues with that status. My SQL to get these issues is as follows:

SELECT iss_id FROM eventum_issue,eventum_status WHERE iss_sta_id=sta_id AND sta_title='Delete This Issue'

END OF NOTE

Solution: Run the script below AND PAY A LOT OF ATTENTION AT THE WHERE CLAUSE.

```php
  <?php
   include_once("../config/config.php");
   include_once(APP_INC_PATH . "db_access.php");

   $issues = $GLOBALS["db_api"]->`dbh->query('SELECT iss_id FROM eventum_issue WHERE MY_WHERE_CLAUSE');`
  // The next two lines inserted by JRM as a fix and it works for me. Note the name of the status I use.
  //$sqlstr  = "SELECT iss_id FROM eventum_issue,eventum_status WHERE iss_sta_id=sta_id AND sta_title='Delete Issue'";
  //$issues = $GLOBALS["db_api"]->dbh->getAll($sqlstr, DB_FETCHMODE_ASSOC);

  // With this query instead, you can use Bulk Update to mark issues for deletion without even closing them first (if you like)
  // $sqlstr  = "SELECT iss_id FROM eventum_project_status, eventum_status, eventum_issue WHERE prs_prj_id = iss_prj_id AND prs_sta_id = iss_sta_id AND sta_id = iss_sta_id AND sta_title = 'Delete this issue'";

  // This line is for debugging and can be commented out when you are sure the delete is ok.
  echo "<pre>";print_r($issues);exit;
  
  foreach ($issues as $issue_id) {
      // Line added by JRM. This seems necessary to get the issue id. Old code here didn't work right.
      //$issue_id = $issue['iss_id'];
  
      delete_data("DELETE FROM eventum_issue_association WHERE isa_issue_id = $issue_id");
      delete_data("DELETE FROM eventum_issue_attachment WHERE iat_iss_id = $issue_id");
      delete_data("DELETE FROM eventum_issue_checkin WHERE isc_iss_id = $issue_id");
      delete_data("DELETE FROM eventum_issue_history WHERE his_iss_id = $issue_id");
      delete_data("DELETE FROM eventum_issue_requirement WHERE isr_iss_id = $issue_id");
      delete_data("DELETE FROM eventum_issue_user WHERE isu_iss_id = $issue_id");
      
      delete_data("DELETE FROM eventum_note WHERE not_iss_id = $issue_id");
      delete_data("DELETE FROM eventum_subscription WHERE sub_iss_id = $issue_id");
      delete_data("DELETE FROM eventum_support_email WHERE sup_iss_id = $issue_id");
      delete_data("DELETE FROM eventum_time_tracking WHERE ttr_iss_id = $issue_id");
      delete_data("DELETE FROM eventum_issue_custom_field WHERE icf_iss_id = $issue_id");
      delete_data("DELETE FROM eventum_phone_support WHERE phs_iss_id = $issue_id");
      delete_data("DELETE FROM eventum_reminder_requirement WHERE rer_iss_id = $issue_id");
      delete_data("DELETE FROM eventum_reminder_history WHERE rmh_iss_id = $issue_id");
      delete_data("DELETE FROM eventum_email_draft WHERE emd_iss_id = $issue_id");
      delete_data("DELETE FROM eventum_irc_notice WHERE ino_iss_id = $issue_id");
      delete_data("DELETE FROM eventum_issue_user_replier WHERE iur_iss_id = $issue_id");
      delete_data("DELETE FROM eventum_mail_queue WHERE maq_iss_id = $issue_id");
      
      delete_data("DELETE FROM eventum_issue WHERE iss_id = $issue_id");
      delete_data("DELETE eventum_mail_queue_log FROM eventum_mail_queue_log LEFT JOIN eventum_mail_queue ON mql_maq_id=maq_id WHERE maq_id IS NULL");
      
      echo "Issue #$issue_id deleted
\n";
  }
  
  function delete_data($sql)
  {
      $res = $GLOBALS["db_api"]->dbh->query($sql);
      if (DB::isError($res)) {
          echo "<pre>";print_r($res);echo "</pre>";
          exit;
      }
  }
  ?>
```

### I want to close multiple issues at once

As an Administrator User, you can use Bulk Update for change status (among other issue fields), but you can't change to a closed status, right?

That's because you should not close multiple issues without the proper closing procedure, adding closing comments and filling other fields. However, some users have requested multiple issue closing for particular cases.

In order to close multiple issues, you can use a workaround, but you will need to have administrator privileges:

-   Go to Administration, then Manage Statuses.
-   Change the closed required status (killed, release or other) Closed Context? to No.
-   Go to the List Issues page and select all the issues you want to close (checkboxes).
-   Using Bulk Update, you can now select the closed status. Select it and run Bulk Update.
-   Go to Manage Statuses again and change the Closed Context? for the status back to Yes.

### I want to change the minimum password length

You will need to change two template files:

`/path-to-eventum/templates/preferences.tpl.html: line 27`
`/path-to-eventum/templates/manage/users.tpl.html: line 22`

Change the default value of 6 characters to whatever value you deem adequate.

### I want to merge two similar projects into one

Back up your DB so you don't do something dangerous before trying this:

```sql
 UPDATE issue SET iss_prj_id = `<new prj_id>` WHERE iss_prj_id = `<old_prj_id>`
```

From <http://lists.mysql.com/eventum-users/4932>

Projects have their own categories. Issues assigned to redundant categories must be reassigned:

```
 SELECT * FROM project_category ORDER BY prc_title;
 
 +--------+------------+-----------------------+
 | prc_id | prc_prj_id | prc_title             |
 +--------+------------+-----------------------+
 |      7 |          1 | Bar                   |
 |      8 |          2 | Bar                   |
 |      9 |          2 | Foo                   |
 +--------+------------+-----------------------+
```

We are merging Project 2 into Project 1. We now need to reassign the moved issues to the correct "Bar" category, then delete the deprecated one:

```sql
 UPDATE issue SET iss_prc_id = 7 WHERE iss_prc_id = 8;
 DELETE from project_category WHERE prc_id = 8;
```

Project 2's "Foo" category is unique, so we simply assign it to Project 1:

```sql
 UPDATE project_category SET prc_prj_id = 1 WHERE prc_id = 9;
```

Projects have their own priorities. These might be completely redundant, and must be reassigned:

```
 mysql> SELECT * FROM project_priority ORDER BY pri_title;
 +--------+------------+-----------------+----------+
 | pri_id | pri_prj_id | pri_title       | pri_rank |
 +--------+------------+-----------------+----------+
 |      1 |          1 | Critical        |        1 |
 |      6 |          2 | Critical        |        1 |
 |      2 |          1 | High            |        2 |
 |      7 |          2 | High            |        2 |
 |      4 |          1 | Low             |        4 |
 |      9 |          2 | Low             |        4 |
 |      3 |          1 | Medium          |        3 |
 |      8 |          2 | Medium          |        3 |
 |      5 |          1 | Not Prioritized |        5 |
 |     10 |          2 | Not Prioritized |        5 |
 +--------+------------+-----------------+----------+
 10 rows in set (0.00 sec)
```

```sql
 UPDATE issue SET iss_pri_id = 1 WHERE iss_pri_id = 6;
 UPDATE issue SET iss_pri_id = 2 WHERE iss_pri_id = 7;
```

and so on...

Statuses, resolutions and custom fields are defined globally, and should transfer with the issue when reassigned to another project.

Depending on your version of Eventum, associated email may not transfer with the issue. Unassociated emails may also need to be transferred. One way to do this is to carefully check the email_account to see which accounts are associated with which project:

`SELECT * FROM email_account;`

You might be able to reassign the received emails to the other email account (which is the same as using the "Move Message To" form when viewing an individual unassociated email in the "Associate Emails" interface):

`UPDATE support_email SET sup_ema_id = 1 WHERE sup_ema_id = 2;`

Keep in mind that this is a very simplistic approach that might not be suitable for all environments, especially more complex ones with multiple projects and email accounts. Back up your data first, then verify the results afterwards.
