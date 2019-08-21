### General Setup

This is a description of the options you will find in General Setup as an Administrator User in the Administration, Configuration options.

-   Administration
-   Configuration
    -   General Setup

## General Setup

### Tool Caption

The Tool Caption is the name for this particular Eventum Instance. The default value is "Eventum", but you can change it to something like "MyCompany Tracking System", "MyCompany Tickets" or "Tech Support". This caption will be displayed for all projects, on the browser tab, in the main login screen, inside Eventum Header menu, at the upper left corner and within email notifications, so choose wisely, or even better, just leave "Eventum".

### SMTP (Outgoing Email) Settings

Here you can define the settings for the SMTP server. A typical configuration will require setting only the Sender Email (the "From:" in the email), the Hostname or IP of the server, and the TCP/IP port, usually 25 for SMTP. The Sender Email MUST contain a real email address, e.g. `eventum@example.com` or `Eventum <eventum@example.com>`

For outgoing email to work, the [crontab](System-Admin%3A-Adding-a-cron-entry#adding-a-cron-entry) settings need to be in place.

### Open Account Signup

If enabled, a link will be displayed in the login screen, where any user will be able to signup for an account, only by entering Name, Email and Password. A confirmation email with an activation link is sent in order verificate and activate the account. The account is created with selected profile for the selected projects from multiple combo.

### Subject Based Routing

If enabled, Eventum will look in the subject line of incoming notes/emails (usually replies to notifications) to determine which issue they should be associated with. Subject-based routing uses only the first issue number in square brackets it finds in the subject to associate the email, e.g. [\#555]. Without subject-based or address-based routing, Eventum relies on mail headers, namely message-id and in-reply-to.

### Email Recipient Type Flag

This will be included in the From address of all emails sent by Eventum, before or after Sender Name, as configured.

### Email Routing Interface

The [Email Routing Interface](System-Admin:-Email-Routing-Interface) is an optional way to automatically associate emails (or notes or drafts) with an Eventum issue, with Email Integration feature enabled.

The default behavior is header (Message-ID/In-Reply-To) routing. Mail clients sometimes mangle these headers which cause problems for Eventum. Using subject based routing is better then header based while the most reliable is address `issue-XXX@example.com` based.

By setting up the mail server (MTA) to pipe emails sent to a specific address into PHP scripts, users are able to use their email clients to reply to emails coming from Eventum, and those replies will be automatically associated with the issue and broadcast to the issue's notification list.

### Note Recipient Type Flag

This will be included in the From address of all notes sent by Eventum, before or after Sender Name, as configured.

### Internal Note Routing Interface

Same as [Email Routing Interface](System-Admin:-Email-Routing-Interface) for Internal Notes.

### Email Draft Interface

Same as [Email Routing Interface](System-Admin:-Email-Routing-Interface) for Drafts.

### Email Integration Feature

Enable or disable the [Email Integration Feature](System-Admin:-Email-integration). If enabled it will allow to download the emails from configured accounts available in Manage Email Accounts.

### Display Description as Email #0

The feature converts the 'Initial Description' into an Associated Email. _The Associated Email created is assigned #0._ Allowing the engineer to see the 'Initial Description' as part of the mail conversation, instead of having to scroll to the top of the page page.

### Daily Tips (Removed in v3.0.0-pre1)

Enable or disable the Daily Tips displayed in Stats (general statistics) start page. The daily tips are selected randomly from the template files in /templates/tips directory.

You might add new Tip files with any name, but keeping the .tpl.html extension. Do not create any directories inside tips folder, since it could be considered as a tip file.

### Email Spell Checker

Enable or disable Email Spell Checker, requires aspell installed in your server.

### IRC Notifications

Lorem Ipsum.

### Default Options for Notifications

Sets the default value for notifications in the following cases:

-   Issues are Updated
-   Issues are Closed
-   Emails are Associated
-   Files are Attached

For a particular issue, you may change the notification options for each single user in the Edit Notification List popup.

### Email Reminder System Status Information

The reminder system was designed to serve as a safety net for issues that need attention. Depending on what configuration you create, you may have several reminders (or alerts) to send out whenever an issue needs attention, for whatever parameter you may deem necessary. It requires to set a For outgoing email to work, the [crontab](System-Admin%3A-Adding-a-cron-entry#adding-a-cron-entry) settings need to be in place. To add a reminder:

1. Go to Manage Issue Reminders

2. Create 1 (or more) reminder for "All Issues" (Reminder Type)

3. From "Existing Issue Reminders" List, select Details link (Actions)

4. Create 1 (or more) Action, choosing Send Email Alert To... (Action Type) - select some users

5. From "Existing Actions" List, select Details link (Conditions)

6. Create 1 (or more) Condition, Choose - Field "Last Update Date" Choose - Operator "greater or equal than" Enter - Value "2" (hours) or other condition.

7. Verify if the reminder emails are sent before and after the condition is true, from command line, run the [reminder script](System-Admin:-Doing-a-fresh-install#reminder-system-check_remindersphp).

Notice that you can add multiple simultaneous Conditions for a single Action.

### Email Error Logging System

When an error is detected in Eventum, it is recorded in the log file [path-to-eventum]/log/errors.log; additionally if this option is enabled, the error will be sent by email to the addresses defined here.

Requires the [crontab](System-Admin%3A-Adding-a-cron-entry#adding-a-cron-entry) settings to be in place.

---

### Allow Un-Assigned Issues?

If disabled, the issues must be assigned to an Eventum User.
