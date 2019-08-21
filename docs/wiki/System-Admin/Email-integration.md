### Email integration

## Overview

Eventum's email integration features allow emails to be sent from and received by Eventum.

Incoming emails are input to the system in one of two ways:

-   A cron job (scheduled task) that checks a POP mail account, or a particular folder of an IMAP account
-   A email routing interface that is run on-demand by an email system (MTA) such as postfix in response to newly received messages

Outgoing emails are put into a queue, and sent out by a cron job (scheduled task). If the mail server is not available, the emails remain in the queue.

See the [installation guide](Doing-a-fresh-install.md) for information on setting up the cron jobs, and [Email Routing Interface](Email-Routing-Interface.md) for information on setting up that feature.

### Outgoing Mail

Eventum supports a single SMTP account for sending mail, but each project can have it's own mail address. The mail settings found under `General Setup` define the system wide SMTP account. The `Sender Email` setting is used for `Email Reminder System Status Information` and `Email Error Logging System` messages.

Each project uses it's own dedicated mail address, but relies on the system wide SMTP settings. The project mail address setting can be found on the `Manage Projects` screen.

### Incoming mail

Once inside Eventum, incoming email messages can be associated with issues:

-   By inspecting the email's headers (for example, if the email is a reply to another email already associated with an issue)
-   By inspecting the email's subject line for reference to an issue id in the format [\#1234] (optional feature, inactive by default)
-   When processed by the [Email Routing Interface](Email-Routing-Interface.md), by extracting the issue id from the address (ex: issue-234@example.com)
-   By auto-creating a new issue if the email cannot be automatically associated with an existing issue using the above methods (optional feature)
-   By manually associating the email with an issue from the queue of unassociated emails (assuming the auto-create feature is not active)

Note that the email integration features expect that one email address will be set up for each project. A single email address can be used for multiple projects using the [multiple project email workaround](../System-Admin/setting-up-email-routing-with-1-email-account-for-multiple-projects.md).

## Who is automatically assigned an issue? How do I change which user gets a new issue?

Go to Administration -\> Manage Email Accounts. Under "Existing Accounts", go to "Auto-Creation of Issues" and click on the "Enabled" link to see options for this e-mail account. Change the user who is auto-assigned new tickets.

## How To Receive Email For Issues That Are Assigned To You

It is simple to receive email for issues which are assigned to you. Go to Preferences for your user account and indicate if you wish to "Receive emails when all issues are created", or "Receive emails when new issues are assigned to you". Each of these options has a radio button for yes or no for each project you have been assigned to, so it is easy to specify the exact kind of email notifications you receive.

In order for the email function to work, the "SMTP (Outgoing Email) Settings" in Administration-\>General Preference needs to be configured properly. You also need to use a valid email address as your login ID for Eventum.

## Troubleshooting

A common mistake for the email integration is forgetting to set the mail scripts to run. The scripts are:

1.  `bin/download_emails.php` (Downloads e-mail to Eventum)
2.  `bin/process_mail_queue.php` (Sends e-mail from Eventum)

For user on a Unix/Linux system, the timing is generally set [using cron](Adding-a-cron-entry.md). For Windows, use either the Task Scheduler, or find a "cron equivalent" program.

Look in the INSTALL file for information on how to set up the mail scripts.

If you are running under PHP on Unix/Linux, you may have a problem getting the process_mail_queue.php script to work.
Edit your local configuration file (php.ini) and increase the amount of memory a script may have (memory_limit).

The `download_emails.php` script requires [some parameters](Doing-a-fresh-install.md#email-download-download_emailsphp). Look in the source of that file or run from commandline to see what they are (username, hostname). You must provide these parameters when setting up your cron.

1.  Notes about [Japanese character sets](Localization:Japanese "wikilink") and E-mail integration.

1.  Some [additional troubleshooting resources.](Mail Additional Troubleshooting "wikilink")

## Email Blocking

To prevent inappropriate emails reaching the notification list, only users that are assigned to the issue are allowed to email through Eventum. If an un-authorized user sends an email to <i>issue-XXXX@example.com</i> it is converted into a note and stored for later use. This note can be converted into an email at a later date.

## Customizing the System Generated Mail Messages

Eventum uses the [Smarty](http://www.smarty.net/) Template Engine. See the [Online Documentation](http://www.smarty.net/docs.php) for Smarty templating syntax.

You should also check out [Eventum:Localization:Templates](Localization:Templates "wikilink") to understand what the `{t} {/t}` tags are for.

File names are pretty much self-explanatory. Location: `../path_to_eventum/templates/notifications/`

1.  account_details.tpl.text
2.  assigned.tpl.text
3.  closed.tpl.text
4.  files.tpl.text
5.  new_auto_created_issue.tpl.text
6.  new_issue.tpl.text
7.  new.tpl.text
8.  new_user.tpl.text
9.  notes.tpl.text
10. password_confirmation.tpl.text
11. updated_account.tpl.text
12. updated_password.tpl.text
13. updated.tpl.text
14. visitor_account.tpl.text
