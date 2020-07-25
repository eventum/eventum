# Doing a fresh install

### Navigate this page:

1. [Installation Process](#installation-process)
1. [Scheduled Tasks](#scheduled-tasks)
    1. [Mail Queue Process (process_mail_queue.php)](#mail-queue-process-process_mail_queuephp)
    1. [Email Download (download_emails.php)](#email-download-download_emailsphp)
    1. [Reminder System (check_reminders.php)](#reminder-system-check_remindersphp)
    1. [Heartbeat Monitor (monitor.php)](#heartbeat-monitor-monitorphp)
1. [Other Features Requiring System Setup](#other-features-requiring-system-setup)
1. [Email Routing Script (route_emails.php)](#email-routing-script-route_emailsphp)
1. [Note Routing Script (route_notes.php)](#note-routing-script-route_notesphp)
1. [Draft Routing Script (route_drafts.php)](#draft-routing-script-route_draftsphp)
1. [IRC Notification Bot (irc/eventum-irc-bot)](#irc-notification-bot-irceventum-irc-bot)
1. [Command Line Interface (cli/eventum)](#command-line-interface-clieventum)
1. [Installing on SSL (https)](#installing-on-ssl-https)
1. [Installing with PHP on FastCGI](#installing-with-php-on-fastcgi)

## Installation Process

Before starting, check [System Requirements](../Prerequisites.md) first.

Installation is pretty simple and quick.
Download the [latest release](https://github.com/eventum/eventum/releases/latest) tarball, and unpack it.
Eventum already bundles PHP libraries that it needs to work properly, see
`docs/DEPENDENCIES.md` from release tarball for details.

Point your webserver to that `/path-to-eventum/htdocs/`. Open it up with your
browser and Eventum should redirect you to the installation screen, and it will
try to guess some of required parameters, like path in the server and etc.

http://yourserver.com/eventum/

**PLEASE NOTE**: The whole eventum directory should _NOT_ be accessible under the
webserver, only `/path-to-eventum/htdocs/`

If Eventum's installation script finds that it needs a few directories or permissions changed, it will print the warnings before actually displaying the installation screen. Just fix what it says is wrong/missing and everything should go well.

After the installation is done, you should go and take all of the available
privileges from the `htdocs/setup` directory, so other people are not allowed
to go in there and mess with your configuration.

**IMPORTANT:** If you already have an installation of Eventum, please read the [UPGRADE](../Upgrading.md) file.

**IMPORTANT:** If you are having trouble getting Eventum to work, please read the trouble shooting section of the [FAQ](../Basic-User/FAQ.md) file.

**IMPORTANT:** By default, the admin user login is set to to admin@example.com during installation. **Be sure to change this to a valid email address with a new password immediately**. Note that eventum will attempt to send the new password to the specified address, which should be valid to prevent the password from being exposed if the email is bounced.

## Install with docker

There's also docker compose based setup available:
- https://github.com/eventum/docker

## Scheduled Tasks

Regular maintenance in Eventum is accomplished by running scheduled tasks or cron jobs. Alternatively, some of these tasks may be performed with a GET request to a URL, if administrative access is not available on the host machine to run the scripts directly from the filesystem. It may be desirable to limit access to these URLs, in such a case.

**NOTE:** Be sure to specify the path to the same PHP binary used by the web server in all cron entries. This is especially important on machines with multiple installations of PHP.

### Mail Queue Process (process_mail_queue.php)

In Eventum, emails are not sent immediately after clicking on the send button. Instead, they are added to a mail queue table that is periodically processed by a cron job or scheduled task. If an email cannot be sent, it will be marked as such in the mail queue log, and sending will be retried whenever the `process_mail_queue.php` script is run.

The SMTP server that Eventum uses to send these queued emails must be specified in:

`Administration` >>> `General Setup` > `SMTP (Outgoing Email) Settings`

This cron example will run the script every minute:

    * * * * * <PATH-TO-EVENTUM>/bin/process_mail_queue.php

There is a lock file that prevents the system from running multiple instances of the mail queue process.

If you would like to keep the size of your mail queue table down you can
truncate (remove the body of) messages that are older then 1 month by running
the `bin/truncate_mail_queue.php` script.

### Email Download (download_emails.php)

To use email integration, you must check the Enabled button for:

`Administration` >>> `General Setup` > `Email Integration Feature`

To configure the accounts used with the email integration feature, go to:

`Administration` >>> `Manage Email Accounts`

In order for Eventum's email integration feature to work, you need to set up a cron job to run the `download_emails.php` script every so often. The following is an example of the required crontab line (using an IMAP account):

    0 * * * * <PATH-TO-EVENTUM>/bin/download_emails.php username mail.example.com INBOX

The above will run the command every hour, and will download emails associated with the given email account and `IMAP` mailbox. If you have more than one email account, you may add another crontab entry for the other accounts (or poll different `IMAP` mailboxes of the same account).

**NOTE:** The mailbox parameter shown in the examples as `INBOX` is **ONLY** required for `IMAP` accounts. Using that parameter on `POP` accounts causes "Error: Could not find a email account with the parameter provided. Please verify your email account settings and try again."

You can also call the `download_emails.php` script via the web using the following URL: `http://eventum_server/path-to-eventum/rpc/download_emails.php?username=username&hostname=mail.example.com&mailbox=INBOX`

**NB:** the web trick no longer works!

### Reminder System (check_reminders.php)

The reminder system was designed to serve as a safety net for issues that need attention. Depending on what configuration you create, you may have several reminders (or alerts) to send out whenever an issue needs attention, for whatever parameter you may deem necessary.

    */10 * * * * <PATH-TO-EVENTUM>/bin/check_reminders.php

It is recommended that you run the reminder cron job every 10 minutes, so it won't flood you with alerts, but it would still be enough to handle most cases.

### Heartbeat Monitor (monitor.php)

The heartbeat monitor alerts the administrator whenever a common problem in Eventum is detected, such as the database server becoming unavailable, or if the recommended permissions for certain configuration files are changed. Please note that before running the heartbeat monitor, you may need to customize some of the checks to be appropriate for your own system, particularly the permission and file checks on `Monitor::checkConfiguration()`.

    */10 * * * * <PATH-TO-EVENTUM>/bin/monitor.php

## Other Features Requiring System Setup

Note: Starting with Eventum 1.5.2 there is a new (optional) way of routing emails, notes and drafts. You will need to setup up a wildcard address to route all messages that should be in Eventum (usually `issue-<number>@<domain>`, `note-<number>@<domain>` and `draft-<number>@<domain>`) to an email account. Then add that email account to Eventum by going to the email account administration page:

`Administration` >>> `Manage Email Accounts`

When setting up the account, check `Use account for email/note/draft routing`. Once the account is added, set the account to be downloaded as described above (in [Email Download](#email-download-download_emailsphp)).

### Email Routing Script (route_emails.php)

The email routing feature is used to automatically associate a thread of emails into an Eventum issue. By setting up the mail server (MTA) to pipe emails sent to a specific address (usually `issue-<number>@<domain>`) into the above script, users are able to use their email clients to reply to emails coming from Eventum, and those replies will be automatically associated with the issue and broadcast to the issue's notification list.

The entire email message should be passed as standard input to the script, and the only parameter to it should be the email account to which this email should be associated. The following is an example of a successful run of this script:

    bin/route_emails.php "1" < example_note_email.txt

This script also saves any routed messages it receives in a separate directory, so you would never lose email. Create a `routed_emails` subdirectory under `misc/` and setup the proper permission bits on it.

**IMPORTANT:** Please be aware that depending on the MTA/MDA that you are using (qmail, postfix, procmail or whatever), you may need to manually change the exit codes used in this script to return the proper signals. For example, postfix uses exit code `78` to signal a configuration problem, but other agents may need different exit codes.

### Note Routing Script (route_notes.php)

The note routing feature is used to automatically associate a thread of notes into an Eventum issue. By setting up the MTA/MDA to pipe email sent to a specific address (usually `note-<number>@<domain>`) into the above script, users are able to use their email clients to reply to internal notes coming from Eventum, and those replies will be automatically associated with the issue and broadcast to the issue's notification list staff members.

The entire email message should be passed as standard input to the script. The following is an example of a successful run of this script:

    bin/route_notes.php < example_note_email.txt

This script also saves any routed messages it receives in a separate directory, so you would never lose notes. Create a `routed_notes` subdirectory under `misc/` and set the proper permission bits on it.

**IMPORTANT:** Please be aware that depending on the MTA/MDA that you are using (qmail, postfix, procmail or whatever), you may need to manually change the exit codes used in this script to handle the proper signals to the MDA. For example, postfix uses exit code `78` to signal a configuration problem, but other agents may need different exit codes.

### Draft Routing Script (route_drafts.php)

The draft routing feature is used to automatically associate a thread of drafts
into an Eventum issue. By setting up qmail (or even postfix) to deliver emails
sent to a specific address (usually `draft-<number>@<domain>`) to the above
script, users are able to send drafts written in their mail client to be stored
in Eventum. These drafts will NOT broadcasted to the notification list.

The entire email message should be passed as standard input to the script. The
following is an example of a successful run of this script:

    bin/route_drafts.php < example_note_email.txt

This script also saves any routed messages it receives in a separate directory,
so you would never lose drafts. Create a 'routed_drafts' sub-directory under
`misc/` and setup the proper permission bits on it.

**IMPORTANT**: Please be aware that depending on the MDA that you are using (qmail,
postfix or whatever), you may need to manually change the exit codes used in
this script to handle the proper signals to the MDA. For example, postfix uses
exit code 78 to signal a configuration problem, but other agents may need
different exit codes.

### IRC Notification Bot (irc/eventum-irc-bot)

The IRC notification bot is a nice feature for remote teams that want to handle issues and want to have a quick and easy way to get simple notifications.

See [IRC Bot page](../System-Advanced/Using-the-IRC-bot.md) for details.

### Command Line Interface (cli/eventum)

The Eventum command line interface allows you to access most of the features of the web interface straight from a command shell. In order to install it, you will need PHP. If you use SSL, you will also need the `curl` and `openssl` PHP extensions.

Assuming you have the requirements properly set up:

-   Copy the `cli` directory to another location (i.e. copy to `~/bin`)
-   Add that location to your `PATH` environment variable
-   Set the required permission in the script (`chmod +x ~/bin/cli/eventum`)
-   Copy the new `.eventumrc` example file to your home directory (`cp cli/eventumrc ~/.eventumrc`)
-   Edit `~/.eventumrc` and set the appropriate values, be sure to secure the file permissions (`chmod 600 ~/.evenumrc`)
-   Run it (`eventum --help`)
-   Test it with `eventum 1` (display issue #1 details)

## Installing on SSL (https)

When you install for the first time, you have a checkbox to define if you will be using Eventum on a SSL Server. This sets the APP_BASE_URL parameter in config/config.php file.

If you install on http, but later you change from http to https, you must manually edit this file and apply the change, from

```php
define('APP_BASE_URL', 'http://' . APP_HOSTNAME . APP_RELATIVE_URL);
```

to

```php
define('APP_BASE_URL', 'https://' . APP_HOSTNAME . APP_RELATIVE_URL);
```

## Installing with PHP on FastCGI

Using FastCGI you must consider the following:

-   Since Basic Authentication might not work, you might not be able to access the Custom Search RSS
-   Running php scripts from command line will probably use different php.ini for WEB and CLI. You should pass the php.ini file path in the command for CRON and manual executions:

`php -c /path-to-php-ini/php.ini -f /path-to-eventum/misc/process_mail_queue.php`
