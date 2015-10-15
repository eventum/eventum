_This wiki is being updated and restructured._  
_Original content was moved from an older wiki site._

![](https://launchpadlibrarian.net/41243495/64.png)

# What is Eventum

Eventum is a user friendly and very flexible issue tracking system, that can be used by a support department to track incoming technical support requests, or by a software development team to quickly organize tasks and bugs.

* * * * *
# New Documentation
_Under Construction_

* Basic User Information
 * [[Creating Issues|Basic-User:-Creating-Issues]]
 * [[Editing Personal Preferences|Basic-User:-Editing-Preferences]]
 * [[FAQ|Basic-User:-FAQ]]
 * [[How Issues are Handled|Basic-User:-How-issues-are-handled-in-Eventum]]
 * [[Listing Issues|Basic-User:-Listing-Issues]]
 * [[Projects|Basic-User:-Projects]]
 * [[RSS Feeds|Basic-User:-RSS-feeds]]
 * [[Reports|Basic-User:-Reporting-System]]
 * [[Working with Issues|Basic-User:-Working-with-Issues]]
* System Administration
 * Installation
    * [[System Requirements|Prerequisites]]
    * Download the latest release ___[[here|https://github.com/eventum/eventum/releases/latest]]___.
    * [[Doing a Fresh Install|System-Admin:-Doing-a-fresh-install]]
      * [[Troubleshooting: Displaying PHP Errors|System-Admin:-Displaying-PHP-errors]]
      * [[Installation Notes|System-Admin:-Installation-Notes]]
    * [[Email Intergration|System-Admin:-Email-integration]]
 * [[General Setup|System-Admin:-General-Setup]]
 * [[Adding Users|System-Admin:-Users]]
 * [[Adding cron entries|System-Admin:-Adding-a-cron-entry]]
 * [[SCM Intergration|System-Admin:-SCM-integration]]
 * [[Add Timeout - Outgoing SMTP (171)|System-Admin:-Add-a-timeout-for-outgoing-smtp-connections-171]]
 * [[Add Timeout - Outgoing SMTP|System-Admin:-Add-a-timeout-for-outgoing-smtp-connections]]
 * [[Become Super Administrator|System-Admin:-Become-Super-Administrator]]
 * [[Custom Fields|System-Admin:-Custom-Fields]]
 * [[Extending and Intergrating|System-Admin:-Extending-and-Integrating-Eventum]]
 * [[Full Text Search|System-Admin:-Fulltext-Search]]
 * [[Full Text Search|System-Admin:-Setting-up-fulltext-searching]]
 * [[Subversion Intergrations|System-Admin:-Subversion-integration]]
 * [[Workflow API|System-Admin:-Workflow-API]]
* Advanced System Documentation
 * [[Custom Field API|System-Advanced:-CustomFieldAPI]]
 * [[Customer API|System-Advanced:-Customer-API]]
 * [[Dynamic Custom Fields|System-Advanced:-DynamicCustomFieldExample]]
 * [[Importing Users|System-Advanced:-Import-Users]]
 * [[IRC Bot|System-Advanced:-Using-the-IRC-bot]]
 * [[Link Filters|System-Advanced:-Manage-Link-Filters]]
 * [x] [[Localization|System-Advanced:-Localization]]
 * [[Workflow: Docs|System-Advanced:-WorkflowDocumentation]]
 * [[Workflow: Examples|System-Advanced:-WorkflowExamples]]
 * [x] [[Patches and Modifications|System Advanced:-Patches]]
   

* * * * *
_Original Documentation_  
_**Most of these links are broken**_
-------------

-   Installation
    -   Before you start, check the [prerequisites](Prerequisites "wikilink") for Eventum.
        -   [System Requirements](Prerequisites#System_Requirements "wikilink")
            -   [Checking PHP Requirements](Prerequisites#Checking_PHP_Requirements "wikilink")
                -   [Via the Command Line](Prerequisites#Via_the_Command_Line "wikilink")
                -   [Via the Web](Prerequisites#Via_the_Web "wikilink")
    -   [Download](http://dev.mysql.com/downloads/other/eventum/)
    -   **[Doing a fresh install](Doing a fresh install "wikilink")**
        -   [Installation Process](Doing a fresh install#Installation_Process "wikilink")
        -   [Scheduled Tasks](Doing a fresh install#Scheduled_Tasks "wikilink")
            -   [Mail Queue Process (crons/process_mail_queue.php)](Doing a fresh install#Mail_Queue_Process_.28misc.2Fprocess_mail_queue.php.29 "wikilink")
            -   [Email Download (crons/download_emails.php)](Doing a fresh install#Email_Download_.28misc.2Fdownload_emails.php.29 "wikilink")
            -   [Reminder System (crons/check_reminders.php)](Doing a fresh install#Reminder_System_.28misc.2Fcheck_reminders.php.29 "wikilink")
            -   [Heartbeat Monitor (crons/monitor.php)](Doing a fresh install#Heartbeat_Monitor_.28misc.2Fmonitor.php.29 "wikilink")
        -   [Other Features Requiring System Setup](Doing a fresh install#Other_Features_Requiring_System_Setup "wikilink")
            -   [Email Routing Script (crons/route_emails.php)](Doing a fresh install#Email_Routing_Script_.28misc.2Froute_emails.php.29 "wikilink")
            -   [Note Routing Script (misc/route_notes.php)](Doing a fresh install#Note_Routing_Script_.28misc.2Froute_notes.php.29 "wikilink")
            -   [IRC Notification Bot (misc/irc/bot.php)](Doing a fresh install#IRC_Notification_Bot_.28misc.2Firc.2Fbot.php.29 "wikilink")
            -   [Command Line Interface (misc/cli/eventum)](Doing a fresh install#Command_Line_Interface_.28misc.2Fcli.2Feventum.29 "wikilink")
        -   [Installing on SSL (https)](Doing a fresh install#Installing_on_SSL_.28https.29 "wikilink")
        -   [Installing with PHP on FastCGI](Doing a fresh install#Installing_with_PHP_on_FastCGI "wikilink")
    -   [Upgrading](Upgrading "wikilink")
    -   Installation Notes
        -   [Linux based systems](Installation notes for Linux based Systems "wikilink")
            -   [Slackware 12.0](Installation notes for Linux based Systems#Slackware_12.0 "wikilink")
            -   [Ubuntu 5.10](Installation notes for Linux based Systems#Ubuntu_5.10 "wikilink")
            -   [Fedora_Core 4](Installation notes for Linux based Systems#Fedora_Core_4 "wikilink")
            -   [Debian Linux](Installation notes for Linux based Systems#Debian_Linux "wikilink")
            -   [gettext and Translation](Installation notes for Linux based Systems#gettext_and_Translation "wikilink")
        -   [PLD Linux](Installation notes for PLD Linux "wikilink")
        -   [FreeBSD](Installation notes for FreeBSD 4.x "wikilink")
        -   [Windows](Installation notes for Windows "wikilink")
            -   [Allowing file uploads](Installation notes for Windows#Allowing_file_uploads "wikilink")
            -   [Setting up Windows Task Scheduler (for sending email queue or downloading new emails)](Installation notes for Windows#Setting_up_Windows_Task_Scheduler_.28for_sending_email_queue_or_downloading_new_emails.29 "wikilink")
            -   [Solving 'CGI Timeout'](Installation notes for Windows#Solving_.27CGI_Timeout.27 "wikilink")
            -   [Solving 'pages do not refresh'](Installation notes for Windows#Solving_.27pages_do_not_refresh.27 "wikilink")
        -   [NetWare](Installation notes for NetWare "wikilink")
        -   [Shared Hosting](Installation notes for shared hosts "wikilink")
-   [FAQ](FAQ "wikilink")
    -   [Troubleshooting](FAQ#Troubleshooting "wikilink")
    -   [Setup](FAQ#Setup "wikilink")
    -   [General Usage](FAQ#General_Usage "wikilink")
-   Usage
    -   [How issues are handled in Eventum](How issues are handled in Eventum "wikilink")
    -   [Creating Issues](Creating Issues "wikilink")
    -   [Working with Issues](Working with Issues "wikilink")
    -   [Listing Issues](Listing Issues "wikilink")
    -   [Editing Preferences](Editing Preferences "wikilink")
-   Features
    -   [Projects](Projects "wikilink")
    -   [Users](Users "wikilink")
        -   [Import Users](Import Users "wikilink")
    -   [Email integration](Email integration "wikilink")
    -   Administration
        -   [General Setup](General Setup "wikilink")
            -   [Tool Caption](General Setup#Tool_Caption "wikilink")
            -   [SMTP (Outgoing Email) Settings](General Setup#SMTP_.28Outgoing_Email.29_Settings "wikilink")
            -   [Open Account Signup](General Setup#Open_Account_Signup "wikilink")
            -   [Subject Based Routing](General Setup#Subject_Based_Routing "wikilink")
            -   [Email Recipient Type Flag](General Setup#Email_Recipient_Type_Flag "wikilink")
            -   [Email Routing Interface](General Setup#Email_Routing_Interface "wikilink")
            -   [Note Recipient Type Flag](General Setup#Note_Recipient_Type_Flag "wikilink")
            -   [Internal Note Routing Interface](General Setup#Internal_Note_Routing_Interface "wikilink")
            -   [Email Draft Interface](General Setup#Email_Draft_Interface "wikilink")
            -   [SCM Integration](General Setup#SCM_Integration "wikilink")
            -   [Email Integration Feature](General Setup#Email_Integration_Feature "wikilink")
            -   [Daily Tips](General Setup#Daily_Tips "wikilink")
            -   [Email Spell Checker](General Setup#Email_Spell_Checker "wikilink")
            -   IRC Notifications
            -   [Allow Un-Assigned Issues?](General Setup#Allow_Un-Assigned_Issues.3F "wikilink")
            -   [Options for Notifications](General Setup#Default_Options_for_Notifications "wikilink")
            -   [Email Reminder System Status Information](General Setup#Email_Reminder_System_Status_Information "wikilink")
            -   [Email Error Logging System](General Setup#Email_Error_Logging_System "wikilink")
        -   [Manage Link Filters](Manage Link Filters "wikilink")
        -   [Become Super-Administrator](Become Super-Administrator "wikilink")
        -   [Creating and Managing Projects](Creating and Managing Projects "wikilink")
    -   Email Routing Interface
        -   [Setting up email routing with Sendmail](Setting up email routing with Sendmail "wikilink")
        -   [Setting up email routing with qmail](Setting up email routing with qmail "wikilink")
        -   [Setting up email routing with postfix](Setting up email routing with postfix "wikilink")
        -   [Setting up email routing with exim](Setting up email routing with exim "wikilink")
        -   [Setting up email routing with 1 email account for multiple projects](Setting up email routing with 1 email account for multiple projects "wikilink")
    -   [Reporting System](Reporting System "wikilink")
    -   [RSS feeds](RSS feeds "wikilink")
    -   Configuration
        -   [Setting up fulltext searching](Setting up fulltext searching "wikilink")
        -   [Extending and Integrating Eventum](Extending and Integrating Eventum "wikilink")
        -   [Adding a cron entry](Adding a cron entry "wikilink")
    -   Miscellaneous
        -   [Using the IRC bot](Using the IRC bot "wikilink")
        -   [Deleting Issues](Deleting Issues "wikilink")
        -   [Fulltext Search](Fulltext Search "wikilink")
-   Development Related
    -   [Changelog](Changelog "wikilink")
    -   [Roadmap](Roadmap "wikilink")
    -   [Localization](Localization "wikilink")
    -   [Workflow API](WorkflowDocumentation "wikilink")
        -   [Workflow Examples](WorkflowExamples "wikilink")
    -   [Custom Fields](Custom Fields "wikilink")
        -   [Custom Field API](CustomFieldAPI "wikilink")
        -   [Dynamic Custom Field Example](DynamicCustomFieldExample "wikilink")
    -   [Pending Contributions](Pending Contributions "wikilink") (not yet merged into Eventum)
    -   [How to Contribute to the Project](HowToContribute "wikilink")
    -   [Article on setting up LDAP Authentication in Eventum](http://www.bieberlabs.com/wordpress/archives/2007/10/20/ldap-enabling-the-eventum-defect-tracking-system/)
-   Debugging Problems
    -   [Displaying PHP errors](Displaying PHP errors "wikilink")
-   Presentations
    -   [Houston PHP / MySQL Meetup (June 3 2005)](http://eventum.mysql.org/meetup_presentation.ppt) - Eventum presentation

Asking Questions
----------------

To ask questions or get more information please join/mail our mailing list: <http://lists.mysql.org/#eventum>