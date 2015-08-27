## Installation Process ##

Installation is pretty simple and quick. Eventum already bundles the libraries that it needs to work properly:

-   JpGraph 1.5.3 (last GPL version)
-   Smarty 2.3.0 (http://smarty.php.net/)
-   PEAR packages (http://pear.php.net/)
-   dTree 2.0.5 (http://www.destroydrop.com/javascript/tree/)
-   dynCalendar.js (http://www.phpguru.org/static/dyncalendar.html)
-   overLIB 3.5.1 (http://www.bosrup.com/web/overlib/)
-   A few other small javascript libraries

Anyway, all you should have to do is place the Eventum files in a directory that is viewable from the web, and open it up with your browser. Eventum should redirect you to the installation screen, and it will try to guess some of required parameters, like path in the server and etc.

` http://yourserver.com/eventum/`

If Eventum's installation script finds that it needs a few directories or permissions changed, it will print the warnings before actually displaying the installation screen. Just fix what it says is wrong/missing and everything should go well.

After the installation is done, you should go and take all of the available privileges from the '/setup' directory, so other people are not allowed to go in there and mess with your configuration.

**IMPORTANT:** If you already have an installation of Eventum, please read the [UPGRADE](Upgrading "wikilink") file.

**IMPORTANT:** If you are having trouble getting Eventum to work, please read the trouble shooting section of the [FAQ](FAQ "wikilink") file.

**IMPORTANT:** By default, the admin user login is set to to admin@example.com during installation. **Be sure to change this to a valid email address with a new password immediately**. Note that eventum will attempt to send the new password to the specified address, which should be valid to prevent the password from being exposed if the email is bounced.

Scheduled Tasks
---------------

Regular maintenance in Eventum is accomplished by running scheduled tasks or cron jobs. Alternatively, some of these tasks may be performed with a GET request to a URL, if administrative access is not available on the host machine to run the scripts directly from the filesystem. It may be desirable to limit access to these URLs, in such a case.

**NOTE:** Be sure to specify the path to the same PHP binary used by the web server in all cron entries. This is especially important on machines with multiple installations of PHP.

### Mail Queue Process (crons/process_mail_queue.php)

In Eventum, emails are not sent immediately after clicking on the send button. Instead, they are added to a mail queue table that is periodically processed by a cron job or scheduled task. If an email cannot be sent, it will be marked as such in the mail queue log, and sending will be retried whenever the process_mail_queue.php script is run.

The SMTP server that Eventum uses to send these queued emails must be specified in:

`Administration >>> General Setup > SMTP (Outgoing Email) Settings`

This cron example will run the script every minute:

`* * * * * /usr/bin/php /path-to-eventum/crons/process_mail_queue.php`

There is a lock file that prevents the system from running multiple instances of the mail queue process. It is not common, but the lock file has been know to occasionally get stuck.

You may want to periodically process the mail queue with the 'fix lock' switch. Running it daily is more that sufficient enough to overcome any lock file issues.

`0 3 * * * /usr/bin/php /path-to-eventum/crons/process_mail_queue.php --fix-lock`

### Email Download (crons/download_emails.php)

To use email integration, you must check the Enabled button for:

`Administration >>> General Setup > Email Integration Feature`

To configure the accounts used with the email integration feature, go to:

`Administration >>> Manage Email Accounts`

In order for Eventum's email integration feature to work, you need to set up a cron job to run the download_emails.php script every so often. The following is an example of the required crontab line (using an IMAP account):

`0 * * * * /usr/bin/php -f /path-to-eventum/crons/download_emails.php username mail.example.com INBOX`

The above will run the command every hour, and will download emails associated with the given email account and IMAP mailbox. If you have more than one email account, you may add another crontab entry for the other accounts (or poll different IMAP mailboxes of the same account).

You can also call the download_emails script via the web, using the following URL:

`http://eventum_server/path-to-eventum/crons/download_emails.php?username=username&hostname=mail.example.com&mailbox=INBOX`

**NOTE:** The mailbox parameter shown in the examples as INBOX is ONLY required for IMAP accounts. Using that parameter on POP accounts causes "Error: Could not find a email account with the parameter provided. Please verify your email account settings and try again."

### Reminder System (crons/check_reminders.php)

The reminder system was designed to serve as a safety net for issues that need attention. Depending on what configuration you create, you may have several reminders (or alerts) to send out whenever an issue needs attention, for whatever parameter you may deem necessary.

`*/10 * * * * /usr/bin/php -f /path-to-eventum/crons/check_reminders.php `

It is recommended that you run the reminder cron job every 10 minutes, so it won't flood you with alerts, but it would still be enough to handle most cases.

### Heartbeat Monitor (misc/monitor.php)

The heartbeat monitor alerts the administrator whenever a common problem in Eventum is detected, such as the database server becoming unavailable, or if the recommended permissions for certain configuration files are changed. Please note that before running the heartbeat monitor, you may need to customize some of the checks to be appropriate for your own system, particularly the permission and file checks on Monitor::checkConfiguration().

`*/10 * * * * /usr/bin/php -f /path-to-eventum/crons/monitor.php`

Other Features Requiring System Setup
-------------------------------------

Note: Starting with Eventum 1.5.2 there is a new (optional) way of routing emails,notes and drafts. You will need to setup up a wild card address to route all messages that should be in eventum (usually issue-<number>@<domain>, note-<number>@<domain> and draft-<number>@<domain>) to an email account. Then add that email account to eventum by going to the email account administration page:

` Administration >>> Manage Email Accounts`
` `

When setting up the account, check 'Use account for email/note/draft routing'. Once the account is added, set the account to be downloaded as described above (in Email Download).

### Email Routing Script (/route_emails.php)

The email routing feature is used to automatically associate a thread of emails into an Eventum issue. By setting up the mail server (MTA) to pipe emails sent to a specific address (usually issue-<number>@<domain>) into the above script, users are able to use their email clients to reply to emails coming from Eventum, and those replies will be automatically associated with the issue and broadcast to the issue's notification list.

The entire email message should be passed as standard input to the script, and the only parameter to it should be the email account to which this email should be associated. The following is an example of a successful run of this script:

`cat example_email.txt | php -f route_emails.php 1`

This script also saves any routed messages it receives in a separate directory, so you would never lose email. Create a 'routed_emails' subdirectory under /path-to-eventum/misc/ and setup the proper permission bits on it.

**IMPORTANT:** Please be aware that depending on the MTA/MDA that you are using (qmail, postfix, procmail or whatever), you may need to manually change the exit codes used in this script to return the proper signals. For example, postfix uses exit code 78 to signal a configuration problem, but other agents may need different exit codes.

### Note Routing Script (/route_notes.php)

The note routing feature is used to automatically associate a thread of notes into an Eventum issue. By setting up the MTA/MDA to pipe email sent to a specific address (usually note-<number>@<domain>) into the above script, users are able to use their email clients to reply to internal notes coming from Eventum, and those replies will be automatically associated with the issue and broadcast to the issue's notification list staff members.

The entire email message should be passed as standard input to the script. The following is an example of a successful run of this script:

`cat example_note_email.txt | php -f route_notes.php`

This script also saves any routed messages it receives in a separate directory, so you would never lose notes. Create a 'routed_notes' subdirectory under /path-to-eventum/ and set the proper permission bits on it.

**IMPORTANT:** Please be aware that depending on the MTA/MDA that you are using (qmail, postfix, procmail or whatever), you may need to manually change the exit codes used in this script to handle the proper signals to the MDA. For example, postfix uses exit code 78 to signal a configuration problem, but other agents may need different exit codes.

### IRC Notification Bot (irc/eventum-irc-bot)

The IRC notification bot is a nice feature for remote teams that want to handle issues and want to have a quick and easy way to get simple notifications. The bot currently notifies of the following actions:

-   New Issues
-   Blocked emails
-   Issues with assignment list changes

The bot also provides a simple set of commands which can be invoked in a query to the bot user:

` `<user>` help`
` `<EventumBOT>` This is the list of available commands:`
` `<EventumBOT>` auth: Format is "auth user@example.com password"`
` `<EventumBOT>` clock: Format is "clock [in|out]"`
` `<EventumBOT>` list-clocked-in: Format is "list-clocked-in"`
` `<EventumBOT>` list-quarantined: Format is "list-quarantined"`

To invoke the notification bot and let it run on the server, run this command:

`php -q eventum-irc-bot &`

**NOTE:** You will need to provide a config/irc_config.php file with appropriate preferences, such as the IRC server and channel that the bot should join. An example setup file can be found in htdocs/setup/irc_config.php

### Command Line Interface (misc/cli/eventum)

The Eventum command line interface allows you to access most of the features of the web interface straight from a command shell. In order to install it, you will need PHP. If you use SSL, you will also need the curl and openssl PHP extensions.

Assuming you have the requirements properly set up:

-   Just copy the path-to-eventum/misc/cli directory to another location (i.e. copy to \~/bin/)
-   Add that location to your PATH environment variable
-   Set the required permission in the script (chmod 700 \~/bin/cli/eventum)
-   Copy the new .eventumrc example file to your home directory:

`  cp ~/bin/cli/eventumrc_example ~/.eventumrc`

-   Edit \~/.eventumrc and set the appropriate values
-   Run it:

` eventum --help`

-   Test it by displaying issue \#1 details:

` eventum 1`

Installing on SSL (https)
-------------------------

When you install for the first time, you have a checkbox to define if you will be using Eventum on a SSL Server. This sets the APP_BASE_URL parameter in config/config.php file.

If you install on http, but later you change from http to https, you must manually edit this file and apply the change, from

```php
define('APP_BASE_URL', 'http://' . APP_HOSTNAME . APP_RELATIVE_URL);
```

to

```php
define('APP_BASE_URL', 'https://' . APP_HOSTNAME . APP_RELATIVE_URL);
```

Installing with PHP on FastCGI
------------------------------

Using FastCGI you must consider the following:

-   Since Basic Authentication might not work, you might not be able to access the Custom Search RSS
-   Running php scripts from command line will probably use different php.ini for WEB and CLI. You should pass the php.ini file path in the command for CRON and manual executions:

`php -c /path-to-php-ini/php.ini -f /path-to-eventum/misc/process_mail_queue.php`