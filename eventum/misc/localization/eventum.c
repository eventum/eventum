/* /work/eventum/templates/help/report_description.tpl.html */
gettext("Description Field");

/* /work/eventum/templates/help/report_description.tpl.html */
gettext("The description field should be used to describe the new issue. Good\npractices dictate that this field should have a description of what\nhappened, steps to reproduce the problem/issue and what you expected \nto happen instead.");

/* /work/eventum/templates/help/report_priority.tpl.html */
gettext("Priority Field");

/* /work/eventum/templates/help/report_priority.tpl.html */
gettext("This field is used to prioritize issues, as to make project management\na little easier. If you are not sure, or don't know what the appropriate\npriority should be for new issues, choose 'not prioritized' as the \noption and leave the issue to be prioritized by a project manager.");

/* /work/eventum/templates/help/report_priority.tpl.html */
gettext("Note: The values in this field can be changed by going in the administration\nsection of this application and editing the appropriate atributes of\na project. If you do not have the needed permissions to do so, please\ncontact your local Eventum administrator.");

/* /work/eventum/templates/help/view_attachment.tpl.html */
gettext("Attachments");

/* /work/eventum/templates/help/view_time.tpl.html */
gettext("Time Tracking");

/* /work/eventum/templates/help/scm_integration_usage.tpl.html */
gettext("Usage Examples");

/* /work/eventum/templates/help/scm_integration_usage.tpl.html */
gettext("An integration script will need to be installed in your CVS root \nrepository in order to send a message to Eventum whenever changes are\ncommitted to the repository. This message will then be processed by\nEventum and the changes to the appropriate files will be associated\nwith existing issue mentioned in your commit message.");

/* /work/eventum/templates/help/scm_integration_usage.tpl.html */
gettext("So to examplify its use, whenever the users are ready to commit the\nchanges to the CVS repository, they will add a special string to\nspecify which issue this is related to. The following would be a\ngood example of its use:");

/* /work/eventum/templates/help/scm_integration_usage.tpl.html */
gettext("[prompt]$ cvs -q commit -m \"Adding form validation as requested (issue: 13)\" form.php");

/* /work/eventum/templates/help/scm_integration_usage.tpl.html */
gettext("You may also use 'bug' to specify the issue ID - whichever you are more\ncomfortable with.");

/* /work/eventum/templates/help/scm_integration_usage.tpl.html */
gettext("This command will be parsed by the CVS integration script (provided to\nyou and available in %eventum_path%/misc/scm/process_cvs_commits.php) and it\nwill notify Eventum that these changes are to be associated with issue\n#13.");

/* /work/eventum/templates/help/adv_search.tpl.html */
gettext("Advanced Search / Creating Custom Queries");

/* /work/eventum/templates/help/adv_search.tpl.html */
gettext("This page allows you to create and modify saved custom searches, which\nwill save searches that can be executed from the Issue Listing screen.");

/* /work/eventum/templates/help/adv_search.tpl.html */
gettext("Most of the time users will want to run common used queries against\nthe issue database, and this is a feature perfect for such situations,\njust create a custom query in this screen and run it from the Issue\nListing page.");

/* /work/eventum/templates/help/view.tpl.html */
gettext("Viewing Issues");

/* /work/eventum/templates/help/view.tpl.html */
gettext("The issue details screen can be accessed quickly by using the 'Go'\ninput field in the top of your browser window. Just enter the issue \nnumber and it will take you to the appropriate screen.");

/* /work/eventum/templates/help/view.tpl.html */
gettext("The Issue Details page will also show '<< Previous Issue' and 'Next\nIssue >>' links that are related to the previous and next issues for\nthe current active filter, if appropriate.");

/* /work/eventum/templates/help/view.tpl.html */
gettext("The full history of changes related to the current issue is available\nby clickin on the 'History of Changes' link.");

/* /work/eventum/templates/help/list.tpl.html */
gettext("Listing / Searching for Issues");

/* /work/eventum/templates/help/list.tpl.html */
gettext("The Issue Listing page uses a grid layout to simplify the manual\nsearch for issues in a project. You may sort for (almost) any column\nin this grid form, and users with the appropriate permissions may also\nassign selected issues to another user.");

/* /work/eventum/templates/help/list.tpl.html */
gettext("The quick search table in the top of the screen helps the users find\nthe issues they want quickly. More advanced searches may be created\nusing the Advanced Search tool.");

/* /work/eventum/templates/help/support_emails.tpl.html */
gettext("Associate Emails");

/* /work/eventum/templates/help/support_emails.tpl.html */
gettext("This screen allows users with the appropriate permissions to associate\nemails with existing issues, or create new issues and \nassociate emails with them.");

/* /work/eventum/templates/help/support_emails.tpl.html */
gettext("In order to do that, however, the administrator of the system needs\nto configure email accounts to make the software download\nthe email messages from the appropriate POP3/IMAP server.");

/* /work/eventum/templates/help/support_emails.tpl.html */
gettext("One of the optimal uses of this feature is to create a separate \n'issues' or 'support' POP3/IMAP account and ask your customers or \nend-users to send support questions, issues or suggestions to that \nmailbox. Eventum will then download the emails and provide \nthem to the users of the system.");

/* /work/eventum/templates/help/report.tpl.html */
gettext("Reporting New Issues");

/* /work/eventum/templates/help/report.tpl.html */
gettext("To report new issues, click in the 'Create Issue' link in the top of \nyour browser window.");

/* /work/eventum/templates/help/segregate_reporter.tpl.html */
gettext("Segregate Reporter");

/* /work/eventum/templates/help/segregate_reporter.tpl.html */
gettext("If this option is enabled, users with a role of Reporter will only be able to see issues they reported.");

/* /work/eventum/templates/help/view_note.tpl.html */
gettext("Notes");

/* /work/eventum/templates/help/notifications.tpl.html */
gettext("Email Notifications");

/* /work/eventum/templates/help/notifications.tpl.html */
gettext("This feature allows system users to subscribe to email notifications\nwhen changes are done to specific issues. The current actions that\ntrigger email notifications are:");

/* /work/eventum/templates/help/notifications.tpl.html */
gettext("Issue details are updated");

/* /work/eventum/templates/help/notifications.tpl.html */
gettext("Issues are Closed");

/* /work/eventum/templates/help/notifications.tpl.html */
gettext("Notes are added to existing issues");

/* /work/eventum/templates/help/notifications.tpl.html */
gettext("Emails are associated to existing issues");

/* /work/eventum/templates/help/notifications.tpl.html */
gettext("Files are attached to existing issues");

/* /work/eventum/templates/help/notifications.tpl.html */
gettext("System users may subscribe to the actions above for specific issues\nwhen they report new issues or by visiting the issue details screen \nand subscribing manually by using the 'Edit Notification List' link.");

/* /work/eventum/templates/help/customize_listing.tpl.html */
gettext("Customize Issue Listing Screen");

/* /work/eventum/templates/help/customize_listing.tpl.html */
gettext("This page allows you to dynamically configure the values displayed in the \n\"Status Change Date\" column in the issue listing screen, for a particular\nproject.\n<br /><br />\nThis column is useful to display the amount of time since the last change\nin status for each issue. For example, if issue #1234 is set to status\n'Closed', you could configure Eventum to display the difference\nin time between \"now\" and the date value stored in the closed date\nfield.\n<br /><br />\nSince the list of statuses available per project is dynamic and \ndatabase driven, this manual process is needed to associate a status\nto a date field coming from the database.");

/* /work/eventum/templates/help/view_impact.tpl.html */
gettext("Impact Analysis");

/* /work/eventum/templates/help/email_blocking.tpl.html */
gettext("Email Blocking");

/* /work/eventum/templates/help/email_blocking.tpl.html */
gettext("To prevent inappropriate emails reaching the notification list, only users that are assigned\nto the issue are allowed to email through Eventum. If an un-authorized\nuser sends an email to <i>issue-XXXX@example.com</i> it is converted into a note and\nstored for later use. This note can be converted into an email at a later date.");

/* /work/eventum/templates/help/report_assignment.tpl.html */
gettext("Assignment Field");

/* /work/eventum/templates/help/report_assignment.tpl.html */
gettext("This field is used to determine who should be assigned to this new \nissue. You are be able to assign a new issue to several persons at the\nsame time.\n<br /><br />\nIf you don't know who should be the assigned person for this new issue,\nassign it to your Project Lead.");

/* /work/eventum/templates/help/report_category.tpl.html */
gettext("Category Field");

/* /work/eventum/templates/help/report_category.tpl.html */
gettext("This field is used to categorize issues by a common denominator, such\nas 'Feature Request', 'Bug' or 'Support Inquiry'.\n<br /><br />\nNote: The values in this field can be changed by going in the administration\nsection of this application and editing the appropriate atributes of\na project. If you do not have the needed permissions to do so, please\ncontact your local Eventum administrator.");

/* /work/eventum/templates/help/banner.tpl.html */
gettext("Close Window");

/* /work/eventum/templates/help/preferences.tpl.html */
gettext("Account Preferences");

/* /work/eventum/templates/help/preferences.tpl.html */
gettext("This screen allows users to change their appropriate full name, account\npassword and email address. This address will be used by the system to\nsend email notifications whenever details about issues you are \nsubscribed to changes.");

/* /work/eventum/templates/help/preferences.tpl.html */
gettext("You may also set the appropriate timezone where you live in this \nscreen, and all of the software will adjust the dates displayed in\nthe system accordingly.");

/* /work/eventum/templates/help/preferences.tpl.html */
gettext("The default options for email notifications are used to pre-select\nthe notification related fields when you report a new issue, or \nsubscribe manually for changes in the issue details page.");

/* /work/eventum/templates/help/scm_integration.tpl.html */
gettext("SCM Integration");

/* /work/eventum/templates/help/scm_integration.tpl.html */
gettext("This feature allows your software development teams to integrate your\nSource Control Management system with your Issue Tracking System.");

/* /work/eventum/templates/help/scm_integration.tpl.html */
gettext("The integration is implemented in such a way that it will be forward\ncompatible with pretty much any SCM system, such as CVS. When entering\nthe required information for the checkout page and diff page input\nfields, use the following placeholders:");

/* /work/eventum/templates/help/scm_integration.tpl.html */
gettext("The CVS module name");

/* /work/eventum/templates/help/scm_integration.tpl.html */
gettext("The filename that was committed");

/* /work/eventum/templates/help/scm_integration.tpl.html */
gettext("The old revision of the file");

/* /work/eventum/templates/help/scm_integration.tpl.html */
gettext("The new revision of the file");

/* /work/eventum/templates/help/scm_integration.tpl.html */
gettext("As an example, using the <a href=\"http://www.horde.org/chora/\" class=\"link\" target=\"_chora\">Chora CVS viewer</a> [highly recommended] from the Horde project you\nwould usually have the following URL as the diff page:");

/* /work/eventum/templates/help/scm_integration.tpl.html */
gettext("http://example.com/chora/diff.php/module/filename.ext?r1=1.3&r2=1.4&ty=h");

/* /work/eventum/templates/help/scm_integration.tpl.html */
gettext("With that information in mind, the appropriate value to be entered in\nthe 'Checkout page' input field is:");

/* /work/eventum/templates/help/index.tpl.html */
gettext("Available Related Topics:");

/* /work/eventum/templates/help/main.tpl.html */
gettext("Available Help Topics");

/* /work/eventum/templates/help/main.tpl.html */
gettext("Please refer to the following help sections for more information on \nspecific parts of the application:");

/* /work/eventum/templates/help/main.tpl.html */
gettext("Listing / Searching for Issues");

/* /work/eventum/templates/help/main.tpl.html */
gettext("Reporting New Issues");

/* /work/eventum/templates/help/main.tpl.html */
gettext("Advanced Search / Creating Custom Queries");

/* /work/eventum/templates/help/main.tpl.html */
gettext("Associate Emails");

/* /work/eventum/templates/help/main.tpl.html */
gettext("Account Preferences");

/* /work/eventum/templates/help/main.tpl.html */
gettext("Viewing Issues");

/* /work/eventum/templates/help/main.tpl.html */
gettext("Email Notifications");

/* /work/eventum/templates/help/main.tpl.html */
gettext("Email Blocking");

/* /work/eventum/templates/help/main.tpl.html */
gettext("Configuration Parameters");

/* /work/eventum/templates/help/main.tpl.html */
gettext("SCM Integration");

/* /work/eventum/templates/help/main.tpl.html */
gettext("Usage Examples");

/* /work/eventum/templates/help/main.tpl.html */
gettext("Installation Instructions");

/* /work/eventum/templates/help/main.tpl.html */
gettext("Customize Issue Listing Screen");

/* /work/eventum/templates/help/main.tpl.html */
gettext("Link Filters");

/* /work/eventum/templates/help/main.tpl.html */
gettext("Edit Fields to Display");

/* /work/eventum/templates/help/main.tpl.html */
gettext("Segregate Reporters");

/* /work/eventum/templates/help/main.tpl.html */
gettext("User Permission Levels");

/* /work/eventum/templates/help/scm_integration_installation.tpl.html */
gettext("Installation Instructions");

/* /work/eventum/templates/help/scm_integration_installation.tpl.html */
gettext("The process_commits.pl script, which is available in the misc \nsub-directory in your Eventum installation directory, will need to be \ninstalled in your CVSROOT CVS module by following the procedure below:");

/* /work/eventum/templates/help/scm_integration_installation.tpl.html */
gettext("The first thing to do is to checkout the CVSROOT module from your CVS\nrepository:");

/* /work/eventum/templates/help/scm_integration_installation.tpl.html */
gettext("The command above will checkout and create the CVSROOT directory that\nyou will need to work with. Next, open the <b>loginfo</b> file and\nadd the following line:");

/* /work/eventum/templates/help/scm_integration_installation.tpl.html */
gettext("Replace %repository path% by the appropriate absolute path in your\nCVS server, such as /home/username/repository for instance. Also make\nsure to put the appropriate path to your Perl binary.");

/* /work/eventum/templates/help/scm_integration_installation.tpl.html */
gettext("You may also turn the parsing of commit messages for just a single CVS\nmodule by substituting the 'ALL' in the line above to the appropriate\nCVS module name, as in:");

/* /work/eventum/templates/help/scm_integration_installation.tpl.html */
gettext("The last step of this installation process is to login into the CVS\nserver and copy the process_cvs_commits.php script into the CVSROOT \ndirectory. Make sure you give the appropriate permissions to the \nscript.");

/* /work/eventum/templates/help/report_release.tpl.html */
gettext("Scheduled Release Field");

/* /work/eventum/templates/help/report_release.tpl.html */
gettext("This field is used to determine what the deadline should be for when\nthis new issue should be completed and resolved. If you don't know \nwhat the deadline should be for this new issue, leave the field as\n'un-scheduled', and a project manager will set it appropriately.");

/* /work/eventum/templates/help/report_release.tpl.html */
gettext("Note: The values in this field can be changed by going in the administration\nsection of this application and editing the appropriate atributes of\na project. If you do not have the needed permissions to do so, please\ncontact your local Eventum administrator.");

/* /work/eventum/templates/help/report_estimated_dev_time.tpl.html */
gettext("Estimated Development Time Field");

/* /work/eventum/templates/help/report_estimated_dev_time.tpl.html */
gettext("This field is used by the reporters of new issues to estimate the \ntotal development time for the issue. It is especially important as a \nmetrics tool to get a simple estimate of how much time each issue will\ntake from discovery, going through implementation and testing up until\nrelease time.");

/* /work/eventum/templates/help/report_estimated_dev_time.tpl.html */
gettext("This field can also be used as a way to check the estimation abilities\nof project managers against the impact analysis given by the \ndevelopers themselves. That is, the value entered by a project manager\nhere can be compared against the impact analysis / estimated \ndevelopment time entered by the developers, and this way get more \nexperience estimating the required time for new projects.");

/* /work/eventum/templates/help/field_display.tpl.html */
gettext("Edit Fields to Display");

/* /work/eventum/templates/help/field_display.tpl.html */
gettext("This page allows you to dynamically control which fields are displayed \nto users of a certain minimum role.\nFor example, you could use this page so that only users of the role \"<i>standard user</i>\" \n(and higher ranking roles) are able to set the category or \nrelease fields when reporting a new issue.");

/* /work/eventum/templates/help/permission_levels.tpl.html */
gettext("User Permission Levels");

/* /work/eventum/templates/help/permission_levels.tpl.html */
gettext("The following is a brief overview of the available user permission levels \nin Eventum:");

/* /work/eventum/templates/help/permission_levels.tpl.html */
gettext("Viewer");

/* /work/eventum/templates/help/permission_levels.tpl.html */
gettext("Allowed to view all issues on the projects associated to \nthis user; cannot create new issues or edit existing issues.");

/* /work/eventum/templates/help/permission_levels.tpl.html */
gettext("Reporter");

/* /work/eventum/templates/help/permission_levels.tpl.html */
gettext("Allowed to view all issues on the projects associated to \nthis user; Allowed to create new issues and to send emails on existing\nissues.");

/* /work/eventum/templates/help/permission_levels.tpl.html */
gettext("Customer");

/* /work/eventum/templates/help/permission_levels.tpl.html */
gettext("This is a special permission level reserved for the Customer\nIntegration API, which allows you to integrate Eventum with your CRM database. \nWhen this feature is enabled, this type of user can only access issues associated\nwith their own customer. Allowed to create new issues, update and send emails\nto existing issues.");

/* /work/eventum/templates/help/permission_levels.tpl.html */
gettext("Standard User");

/* /work/eventum/templates/help/permission_levels.tpl.html */
gettext("Allowed to view all issues on the projects associated to\nthis user; Allowed to create new issues, update existing issues, and to send\nemails and notes to existing issues.");

/* /work/eventum/templates/help/permission_levels.tpl.html */
gettext("Developer");

/* /work/eventum/templates/help/permission_levels.tpl.html */
gettext("Similar in every way to the above permission level, but \nthis extra level allows you to segregate users who will deal with issues, and\noverall normal staff users who do not handle issues themselves.");

/* /work/eventum/templates/help/permission_levels.tpl.html */
gettext("Manager");

/* /work/eventum/templates/help/permission_levels.tpl.html */
gettext("Allowed to view all issues on the projects associated to\nthis user; Allowed to create new issues, update existing issues, and to send\nemails and notes to existing issues. Also, this type of user is also allowed on\nthe special administration section of Eventum to tweak most project-level \nfeatures and options.");

/* /work/eventum/templates/help/permission_levels.tpl.html */
gettext("Administrator");

/* /work/eventum/templates/help/permission_levels.tpl.html */
gettext("This type of user has full access to Eventum, including\nthe low level configuration parameters available through the administration\ninterface.");

/* /work/eventum/templates/help/column_display.tpl.html */
gettext("Edit Columns to Display");

/* /work/eventum/templates/help/column_display.tpl.html */
gettext("This page allows you to dynamically control which columns are displayed on the list issues page.");

/* /work/eventum/templates/help/column_display.tpl.html */
gettext("You can set the minimum role required to view a column. For example, if you set the mimimum role for 'Category'\nto be 'Manager' anyone with a role lower then 'Manager' will not be able to see that column. To hide a column\nfrom all users, select 'Never Display'.");

/* /work/eventum/templates/help/column_display.tpl.html */
gettext("Please note that some columns may be hidden even if you specify they should be shown. For example, if no releases\nare defined in the system the 'Release' column will be hidden.");

/* /work/eventum/templates/help/link_filters.tpl.html */
gettext("Link Filters");

/* /work/eventum/templates/help/link_filters.tpl.html */
gettext("Link filters are used to replace text such as 'Bug #42' with an automatic\nlink to some external resource. It uses regular expressions to replace the text.\nSpecify the search pattern in the pattern field without delimiters. Specify the entire\nstring you would like to use as a replacement with $x to insert the matched text. For example:\n<br /><br />\nPattern: \"bug #(d+)\"<br />\nReplacement: \"&lt;a href=http://example.com/bug.php?id=$1&gt;Bug #$1&lt;/a&gt;\"");

/* /work/eventum/templates/help/report_summary.tpl.html */
gettext("Summary Field");

/* /work/eventum/templates/help/report_summary.tpl.html */
gettext("This field is used as a simple and descriptive title to this new\nissue. As a suggestion, it should be descriptive and short enough to\nbe used by other users to remember quickly what the issue was all\nabout.");

/* /work/eventum/templates/tips/keyboard_shortcuts.tpl.html */
gettext("You can switch to the 'Search' or 'Go' boxes quickly by using a\nspecial shortcut keystroke in your keyboard.<br />\n<br />\nUse the following shortcuts:<br />\n<br />\n<b>ALT-3</b> (hold 'ALT' key and press '3' one time) - to access the 'Search' box<br />\n<br />\n<b>ALT-4</b> (hold 'ALT' key and press '4' one time) - to access the 'Go' box");

/* /work/eventum/templates/tips/custom_queries.tpl.html */
gettext("You can create as many custom queries as you want through the\n<a class=\"link\" href=\"adv_search.php\">Advanced Search</a> interface.\nThere is also the ability to save and modify custom queries and load\nthem quickly from the Issue Listing screen.");

/* /work/eventum/templates/tips/canned_responses.tpl.html */
gettext("You can create canned email responses and use them when sending emails from the\nsystem. That is an useful feature when dealing with lots of issues that relate\nto the same problem.\n<br /><br />\nIf no canned email responses are available through the Email window, please\ncontact an user with the appropriate permissions (administrator or manager) to\nadd some for you.");

/* /work/eventum/templates/mail_queue.tpl.html */
gettext("Sorry, you do not have permission to view this page");

/* /work/eventum/templates/mail_queue.tpl.html */
gettext("Mail Queue for Issue #%1");

/* /work/eventum/templates/mail_queue.tpl.html */
gettext("Recipient");

/* /work/eventum/templates/mail_queue.tpl.html */
gettext("Queued Date");

/* /work/eventum/templates/mail_queue.tpl.html */
gettext("Status");

/* /work/eventum/templates/mail_queue.tpl.html */
gettext("Subject");

/* /work/eventum/templates/mail_queue.tpl.html */
gettext("No mail queue could be found.");

/* /work/eventum/templates/clock_status.tpl.html */
gettext("Thank you, your account clocked-in status was changed successfully.");

/* /work/eventum/templates/clock_status.tpl.html */
gettext("An error was found while trying to change your account clocked-in status.");

/* /work/eventum/templates/quick_filter_form.tpl.html */
gettext("Please select the custom filter to search against.");

/* /work/eventum/templates/quick_filter_form.tpl.html */
gettext("Customer Identity (i.e. \"Example Inc.\", \"johndoe@example.com\", 12345)");

/* /work/eventum/templates/quick_filter_form.tpl.html */
gettext("All Text (emails, notes, etc)");

/* /work/eventum/templates/quick_filter_form.tpl.html */
gettext("Search");

/* /work/eventum/templates/quick_filter_form.tpl.html */
gettext("Clear Filters");

/* /work/eventum/templates/quick_filter_form.tpl.html */
gettext("Assigned");

/* /work/eventum/templates/quick_filter_form.tpl.html */
gettext("any");

/* /work/eventum/templates/quick_filter_form.tpl.html */
gettext("Priority");

/* /work/eventum/templates/quick_filter_form.tpl.html */
gettext("Status");

/* /work/eventum/templates/quick_filter_form.tpl.html */
gettext("quick search bar");

/* /work/eventum/templates/quick_filter_form.tpl.html */
gettext("Advanced Search");

/* /work/eventum/templates/quick_filter_form.tpl.html */
gettext("Saved Searches");

/* /work/eventum/templates/bulk_update.tpl.html */
gettext("Bulk Update Tool");

/* /work/eventum/templates/bulk_update.tpl.html */
gettext("Assignment");

/* /work/eventum/templates/bulk_update.tpl.html */
gettext("Status");

/* /work/eventum/templates/bulk_update.tpl.html */
gettext("Release");

/* /work/eventum/templates/bulk_update.tpl.html */
gettext("Priority");

/* /work/eventum/templates/bulk_update.tpl.html */
gettext("Category");

/* /work/eventum/templates/bulk_update.tpl.html */
gettext("Bulk Update");

/* /work/eventum/templates/bulk_update.tpl.html */
gettext("Reset");

/* /work/eventum/templates/reports/weekly_data.tpl.html */
gettext("Weekly");

/* /work/eventum/templates/reports/weekly_data.tpl.html */
gettext("Report");

/* /work/eventum/templates/reports/weekly_data.tpl.html */
gettext("issues worked on");

/* /work/eventum/templates/reports/weekly_data.tpl.html */
gettext("No issues touched this time period");

/* /work/eventum/templates/reports/weekly_data.tpl.html */
gettext("Issues Closed");

/* /work/eventum/templates/reports/weekly_data.tpl.html */
gettext("No issues closed this time period");

/* /work/eventum/templates/reports/weekly_data.tpl.html */
gettext("New Issues Assigned");

/* /work/eventum/templates/reports/weekly_data.tpl.html */
gettext("Total Issues");

/* /work/eventum/templates/reports/weekly_data.tpl.html */
gettext("Eventum Emails");

/* /work/eventum/templates/reports/weekly_data.tpl.html */
gettext("Other Emails");

/* /work/eventum/templates/reports/weekly_data.tpl.html */
gettext("Total Phone Calls");

/* /work/eventum/templates/reports/weekly_data.tpl.html */
gettext("Total Notes");

/* /work/eventum/templates/reports/weekly_data.tpl.html */
gettext("Phone Time Spent");

/* /work/eventum/templates/reports/weekly_data.tpl.html */
gettext("Email Time Spent");

/* /work/eventum/templates/reports/weekly_data.tpl.html */
gettext("Login Time Spent");

/* /work/eventum/templates/reports/weekly_data.tpl.html */
gettext("Total Time Spent");

/* /work/eventum/templates/reports/open_issues.tpl.html */
gettext("Showing all open issues older than ");

/* /work/eventum/templates/reports/open_issues.tpl.html */
gettext("days");

/* /work/eventum/templates/reports/open_issues.tpl.html */
gettext("Issue ID");

/* /work/eventum/templates/reports/open_issues.tpl.html */
gettext("Summary");

/* /work/eventum/templates/reports/open_issues.tpl.html */
gettext("Status");

/* /work/eventum/templates/reports/open_issues.tpl.html */
gettext("Time Spent");

/* /work/eventum/templates/reports/open_issues.tpl.html */
gettext("Created");

/* /work/eventum/templates/reports/open_issues.tpl.html */
gettext("Days and Hours Since");

/* /work/eventum/templates/reports/open_issues.tpl.html */
gettext("Last Update");

/* /work/eventum/templates/reports/open_issues.tpl.html */
gettext("Last Outgoing Msg");

/* /work/eventum/templates/reports/tree.tpl.html */
gettext("Available Reports");

/* /work/eventum/templates/reports/tree.tpl.html */
gettext("Issues");

/* /work/eventum/templates/reports/tree.tpl.html */
gettext("Issues by User");

/* /work/eventum/templates/reports/tree.tpl.html */
gettext("Open Issues Report");

/* /work/eventum/templates/reports/tree.tpl.html */
gettext("Weekly Report");

/* /work/eventum/templates/reports/tree.tpl.html */
gettext("Workload by time period");

/* /work/eventum/templates/reports/tree.tpl.html */
gettext("Email by time period");

/* /work/eventum/templates/reports/tree.tpl.html */
gettext("Custom Fields");

/* /work/eventum/templates/reports/tree.tpl.html */
gettext("Customer Profile Stats");

/* /work/eventum/templates/reports/tree.tpl.html */
gettext("Recent Activity");

/* /work/eventum/templates/reports/tree.tpl.html */
gettext("Workload By Date Range");

/* /work/eventum/templates/reports/tree.tpl.html */
gettext("Stalled Issues");

/* /work/eventum/templates/reports/tree.tpl.html */
gettext("Estimated Development Time");

/* /work/eventum/templates/reports/issue_user.tpl.html */
gettext("Issue ID");

/* /work/eventum/templates/reports/issue_user.tpl.html */
gettext("Summary");

/* /work/eventum/templates/reports/issue_user.tpl.html */
gettext("Status");

/* /work/eventum/templates/reports/issue_user.tpl.html */
gettext("Time Spent");

/* /work/eventum/templates/reports/issue_user.tpl.html */
gettext("Created");

/* /work/eventum/templates/reports/workload_date_range.tpl.html */
gettext("Workload by Date Range Report");

/* /work/eventum/templates/reports/workload_date_range.tpl.html */
gettext("Interval");

/* /work/eventum/templates/reports/workload_date_range.tpl.html */
gettext("Start");

/* /work/eventum/templates/reports/workload_date_range.tpl.html */
gettext("End");

/* /work/eventum/templates/reports/workload_date_range.tpl.html */
gettext("Generate");

/* /work/eventum/templates/reports/workload_date_range.tpl.html */
gettext(" Warning: Some type and interval options, combined with large <br />\n    date ranges can produce extremely large graphs.");

/* /work/eventum/templates/reports/workload_date_range.tpl.html */
gettext("Day'");

/* /work/eventum/templates/reports/workload_date_range.tpl.html */
gettext("day");

/* /work/eventum/templates/reports/workload_date_range.tpl.html */
gettext("Week");

/* /work/eventum/templates/reports/workload_date_range.tpl.html */
gettext("week");

/* /work/eventum/templates/reports/workload_date_range.tpl.html */
gettext("Month");

/* /work/eventum/templates/reports/workload_date_range.tpl.html */
gettext("month");

/* /work/eventum/templates/reports/workload_date_range.tpl.html */
gettext("Day of Week");

/* /work/eventum/templates/reports/workload_date_range.tpl.html */
gettext("dow");

/* /work/eventum/templates/reports/workload_date_range.tpl.html */
gettext("Week");

/* /work/eventum/templates/reports/workload_date_range.tpl.html */
gettext("week");

/* /work/eventum/templates/reports/workload_date_range.tpl.html */
gettext("Day of Month");

/* /work/eventum/templates/reports/workload_date_range.tpl.html */
gettext("dom");

/* /work/eventum/templates/reports/workload_date_range.tpl.html */
gettext("Month");

/* /work/eventum/templates/reports/workload_date_range.tpl.html */
gettext("month");

/* /work/eventum/templates/reports/workload_date_range.tpl.html */
gettext("Avg/Med/Max Issues/Emails");

/* /work/eventum/templates/reports/customer_stats.tpl.html */
gettext("The current project does not have customer integration so this report can not be viewed.");

/* /work/eventum/templates/reports/customer_stats.tpl.html */
gettext("Include expired contracts");

/* /work/eventum/templates/reports/customer_stats.tpl.html */
gettext("Customer");

/* /work/eventum/templates/reports/customer_stats.tpl.html */
gettext("All");

/* /work/eventum/templates/reports/customer_stats.tpl.html */
gettext("Red values indicate value is higher than the aggregate one.");

/* /work/eventum/templates/reports/customer_stats.tpl.html */
gettext("Blue values indicate value is lower than the aggregate one.");

/* /work/eventum/templates/reports/customer_stats.tpl.html */
gettext("Customers");

/* /work/eventum/templates/reports/customer_stats.tpl.html */
gettext("Issues");

/* /work/eventum/templates/reports/customer_stats.tpl.html */
gettext("Emails by Customers");

/* /work/eventum/templates/reports/customer_stats.tpl.html */
gettext("Emails by Staff");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Recent Activity");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Recent Activity Report");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Report Type");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Recent");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Date Range");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Activity Type");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Activity in Past");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Start");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("End");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Developer");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("All");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Sort Order");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Ascending");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Descending");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Generate");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Recent Phone Calls");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Issue ID");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Customer");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Date");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Developer");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Type");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Line");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Description");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("No Phone Calls Found");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Recent Notes");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Issue ID");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Customer");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Posted Date");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("User");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Title");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("No Notes Found");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Recent Emails");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Issue ID");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Customer");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("From");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("To");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Date");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Subject");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("sent to notification list");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("No Emails Found");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Recent Drafts");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Issue ID");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Customer");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Status");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("From");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("To");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Date");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Subject");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("No Drafts Found");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Recent Time Entries");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Issue ID");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Customer");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Date of Work");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("User");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Time Spent");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Category");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Summary");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("No Time Entries Found");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Recent Reminder Actions");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Issue ID");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Customer");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Date Triggered");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("Title");

/* /work/eventum/templates/reports/recent_activity.tpl.html */
gettext("No Reminder Entries Found");

/* /work/eventum/templates/reports/estimated_dev_time.tpl.html */
gettext("Estimated Development Time by Category");

/* /work/eventum/templates/reports/estimated_dev_time.tpl.html */
gettext("Based on all open issue in Eventum for <b>%1</b>.");

/* /work/eventum/templates/reports/estimated_dev_time.tpl.html */
gettext("Category");

/* /work/eventum/templates/reports/estimated_dev_time.tpl.html */
gettext("Estimated time (Hours)");

/* /work/eventum/templates/reports/workload_time_period.tpl.html */
gettext("Email Workload by Time of day");

/* /work/eventum/templates/reports/workload_time_period.tpl.html */
gettext("Based on all issues recorded in Eventum since start to present.");

/* /work/eventum/templates/reports/workload_time_period.tpl.html */
gettext("Workload by Time of day");

/* /work/eventum/templates/reports/workload_time_period.tpl.html */
gettext("Based on all issues recorded in Eventum since start to present.\n        Actions are any event that shows up in the history of an issue, such as a user or a developer updating an issue, uploading a file, sending an email, etc.");

/* /work/eventum/templates/reports/workload_time_period.tpl.html */
gettext("Time Period");

/* /work/eventum/templates/reports/workload_time_period.tpl.html */
gettext("(GMT)");

/* /work/eventum/templates/reports/workload_time_period.tpl.html */
gettext("Developer");

/* /work/eventum/templates/reports/workload_time_period.tpl.html */
gettext("Emails");

/* /work/eventum/templates/reports/workload_time_period.tpl.html */
gettext("Actions");

/* /work/eventum/templates/reports/workload_time_period.tpl.html */
gettext("Customer");

/* /work/eventum/templates/reports/workload_time_period.tpl.html */
gettext("Emails");

/* /work/eventum/templates/reports/workload_time_period.tpl.html */
gettext("Actions");

/* /work/eventum/templates/reports/workload_time_period.tpl.html */
gettext("Time Period");

/* /work/eventum/templates/reports/stalled_issues.tpl.html */
gettext("Stalled Issues Report");

/* /work/eventum/templates/reports/stalled_issues.tpl.html */
gettext("Stalled Issues Report");

/* /work/eventum/templates/reports/stalled_issues.tpl.html */
gettext("Show Issues with no Response Between");

/* /work/eventum/templates/reports/stalled_issues.tpl.html */
gettext("Developers");

/* /work/eventum/templates/reports/stalled_issues.tpl.html */
gettext("Status");

/* /work/eventum/templates/reports/stalled_issues.tpl.html */
gettext("Sort Order");

/* /work/eventum/templates/reports/stalled_issues.tpl.html */
gettext("Ascending");

/* /work/eventum/templates/reports/stalled_issues.tpl.html */
gettext("Ascending");

/* /work/eventum/templates/reports/stalled_issues.tpl.html */
gettext("Descending");

/* /work/eventum/templates/reports/stalled_issues.tpl.html */
gettext("Descending");

/* /work/eventum/templates/reports/stalled_issues.tpl.html */
gettext("Generate");

/* /work/eventum/templates/reports/stalled_issues.tpl.html */
gettext("Issue ID");

/* /work/eventum/templates/reports/stalled_issues.tpl.html */
gettext("Summary");

/* /work/eventum/templates/reports/stalled_issues.tpl.html */
gettext("Status");

/* /work/eventum/templates/reports/stalled_issues.tpl.html */
gettext("Time Spent<");

/* /work/eventum/templates/reports/stalled_issues.tpl.html */
gettext("Created");

/* /work/eventum/templates/reports/stalled_issues.tpl.html */
gettext("Last Response<");

/* /work/eventum/templates/reports/stalled_issues.tpl.html */
gettext("Days and Hours Since");

/* /work/eventum/templates/reports/stalled_issues.tpl.html */
gettext("Last Update");

/* /work/eventum/templates/reports/stalled_issues.tpl.html */
gettext("Last Outgoing Msg");

/* /work/eventum/templates/reports/stalled_issues.tpl.html */
gettext("view issue details");

/* /work/eventum/templates/reports/stalled_issues.tpl.html */
gettext("view issue details");

/* /work/eventum/templates/reports/weekly.tpl.html */
gettext("Weekly Report");

/* /work/eventum/templates/reports/weekly.tpl.html */
gettext("Report Type:");

/* /work/eventum/templates/reports/weekly.tpl.html */
gettext("Weekly");

/* /work/eventum/templates/reports/weekly.tpl.html */
gettext("Date Range");

/* /work/eventum/templates/reports/weekly.tpl.html */
gettext("Generate");

/* /work/eventum/templates/reports/weekly.tpl.html */
gettext("Week");

/* /work/eventum/templates/reports/weekly.tpl.html */
gettext("Start");

/* /work/eventum/templates/reports/weekly.tpl.html */
gettext("End:");

/* /work/eventum/templates/reports/weekly.tpl.html */
gettext("Developer");

/* /work/eventum/templates/reports/weekly.tpl.html */
gettext("Options");

/* /work/eventum/templates/reports/weekly.tpl.html */
gettext("Separate Closed Issues");

/* /work/eventum/templates/reports/custom_fields.tpl.html */
gettext("Please select the custom field that you would like to generate a report against.");

/* /work/eventum/templates/reports/custom_fields.tpl.html */
gettext("Custom Fields Report");

/* /work/eventum/templates/reports/custom_fields.tpl.html */
gettext("Field to Graph");

/* /work/eventum/templates/reports/custom_fields.tpl.html */
gettext("Options to Graph");

/* /work/eventum/templates/reports/custom_fields.tpl.html */
gettext("Group By");

/* /work/eventum/templates/reports/custom_fields.tpl.html */
gettext("Issue");

/* /work/eventum/templates/reports/custom_fields.tpl.html */
gettext("Customer");

/* /work/eventum/templates/reports/custom_fields.tpl.html */
gettext("Generate");

/* /work/eventum/templates/reports/custom_fields.tpl.html */
gettext("Percentages may not add up to exactly 100% due to rounding");

/* /work/eventum/templates/reports/custom_fields.tpl.html */
gettext("Issues/Customers matching criteria");

/* /work/eventum/templates/reports/custom_fields.tpl.html */
gettext("Customer");

/* /work/eventum/templates/reports/custom_fields.tpl.html */
gettext("Issue Count");

/* /work/eventum/templates/reports/custom_fields.tpl.html */
gettext("Issue ID");

/* /work/eventum/templates/reports/custom_fields.tpl.html */
gettext("Summary");

/* /work/eventum/templates/reports/custom_fields.tpl.html */
gettext("No data found");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Advanced Search");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Please enter the title for this saved search.");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Please choose which entries need to be removed.");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("This action will permanently delete the selected entries.");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Advanced Search");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Keyword(s)");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Customer Identity (i.e. \"Example Inc.\", \"johndoe@example.com\", 12345)");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("All Text (emails, notes, etc)");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Assigned");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Category");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("any");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Priority");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("any");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Status");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("any");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Reporter");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Any");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Release");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("any");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Hide Closed Issues");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Rows Per Page:");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("ALL");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Sort By");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Priority");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Issue ID");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Status");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Summary");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Last Action Date");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Sort Order:");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("ascending");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("descending");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Show Issues in Which I Am:");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Authorized to Send Emails");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("In Notification List");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Show date fields to search by");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Created");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Greater Than");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Less Than");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Between");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("In Past");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("hours");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Last Updated");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Greater Than");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Less Than");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Between");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Is Null");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("In Past");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("hours");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Last Updated");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("End date");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("First Response by Staff");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Greater Than");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Less Than");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Between");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Is Null");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("In Past");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("hours");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("First Response By Staff");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("End date");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Last Response by Staff");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Greater Than");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Less Than");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Between");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Is Null");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("In Past");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("hours");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Last Response by Staff");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("End date");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Status Closed");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Greater Than");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Less Than");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Between");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Is Null");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("In Past");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("hours");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Status Closed");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("End date");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Show additional fields to search by");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Run Search");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Reset");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Search Title");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Global Search");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("Saved Searches");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("edit this custom search");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("global filter");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("RSS feed for this custom search");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("All");

/* /work/eventum/templates/adv_search.tpl.html */
gettext("No custom searches could be found.");

/* /work/eventum/templates/help_link.tpl.html */
gettext("get context sensitive help");

/* /work/eventum/templates/offline.tpl.html */
gettext("Database Error");

/* /work/eventum/templates/offline.tpl.html */
gettext("There seems to be a problem connecting to the database server specified in your configuration file. Please contact your local system administrator for further assistance.");

/* /work/eventum/templates/offline.tpl.html */
gettext("There seems to be a problem finding the required database tables in the database server specified in your configuration file. Please contact your local system administrator for further assistance.");

/* /work/eventum/templates/view_headers.tpl.html */
gettext("View Email Raw Headers");

/* /work/eventum/templates/view_headers.tpl.html */
gettext("Close");

/* /work/eventum/templates/view_headers.tpl.html */
gettext("Close");

/* /work/eventum/templates/view.tpl.html */
gettext("Error: The issue #%1 could not be found.");

/* /work/eventum/templates/view.tpl.html */
gettext("Go Back");

/* /work/eventum/templates/view.tpl.html */
gettext("Sorry, you do not have the required privileges to view this issue.");

/* /work/eventum/templates/view.tpl.html */
gettext("Go Back");

/* /work/eventum/templates/error_icon.tpl.html */
gettext("error condition detected");

/* /work/eventum/templates/error_icon.tpl.html */
gettext("error condition detected");

/* /work/eventum/templates/checkins.tpl.html */
gettext("Please choose which entries need to be removed.");

/* /work/eventum/templates/checkins.tpl.html */
gettext("This action will permanently delete the selected entries.");

/* /work/eventum/templates/checkins.tpl.html */
gettext("SCM Integration - Checkins");

/* /work/eventum/templates/checkins.tpl.html */
gettext("Date");

/* /work/eventum/templates/checkins.tpl.html */
gettext("User");

/* /work/eventum/templates/checkins.tpl.html */
gettext("Module / Directory");

/* /work/eventum/templates/checkins.tpl.html */
gettext("File");

/* /work/eventum/templates/checkins.tpl.html */
gettext("Commit Message");

/* /work/eventum/templates/checkins.tpl.html */
gettext("diff to %1");

/* /work/eventum/templates/checkins.tpl.html */
gettext("No checkins could be found.");

/* /work/eventum/templates/checkins.tpl.html */
gettext("All");

/* /work/eventum/templates/checkins.tpl.html */
gettext("Remove Selected");

/* /work/eventum/templates/history.tpl.html */
gettext("History of Changes to Issue");

/* /work/eventum/templates/history.tpl.html */
gettext("Date");

/* /work/eventum/templates/history.tpl.html */
gettext("Summary");

/* /work/eventum/templates/history.tpl.html */
gettext("No changes could be found.");

/* /work/eventum/templates/history.tpl.html */
gettext("Close");

/* /work/eventum/templates/history.tpl.html */
gettext("History of Reminders Triggered for Issue");

/* /work/eventum/templates/history.tpl.html */
gettext("Date");

/* /work/eventum/templates/history.tpl.html */
gettext("Triggered Action");

/* /work/eventum/templates/history.tpl.html */
gettext("No reminders could be found.");

/* /work/eventum/templates/history.tpl.html */
gettext("Close");

/* /work/eventum/templates/associate.tpl.html */
gettext("An error occurred while trying to associate the selected email message");

/* /work/eventum/templates/associate.tpl.html */
ngettext("Thank you, the selected email message was associated successfully.","Thank you, the selected email messages were associated successfully.",x);

/* /work/eventum/templates/associate.tpl.html */
gettext("Continue");

/* /work/eventum/templates/associate.tpl.html */
gettext("Warning: Unknown Contacts Found");

/* /work/eventum/templates/associate.tpl.html */
gettext("The following addresses could not be matched against the system user records:");

/* /work/eventum/templates/associate.tpl.html */
gettext("Please make sure you have selected the correct email messages to associate.");

/* /work/eventum/templates/associate.tpl.html */
gettext("Close Window");

/* /work/eventum/templates/associate.tpl.html */
gettext("Warning: Unknown contacts were found in the selected email messages. Please make sure you have selected the correct email messages to associate.");

/* /work/eventum/templates/associate.tpl.html */
ngettext("Associate Email Message to Issue #%1","Associate Email Messages to Issue #%1",x);

/* /work/eventum/templates/associate.tpl.html */
ngettext("Please choose one of the following actions to take in regards to the selected email message","Please choose one of the following actions to take in regards to the selected email messages",x);

/* /work/eventum/templates/associate.tpl.html */
gettext("Save Message");

/* /work/eventum/templates/associate.tpl.html */
gettext("as");

/* /work/eventum/templates/associate.tpl.html */
gettext("an");

/* /work/eventum/templates/associate.tpl.html */
ngettext("<b>NOTE:</b> Email will be broadcasted to the full notification list, including any customers, if this option is chosen.","<b>NOTE:</b> Emails will be broadcasted to the full notification list, including any customers, if this option is chosen.",x);

/* /work/eventum/templates/associate.tpl.html */
ngettext("Save Message as Reference Email","Save Message as Reference Emails",x);

/* /work/eventum/templates/associate.tpl.html */
ngettext("<b>NOTE:</b> Email will <b>NOT</b> be sent to the notification list, if this option if chosen. This is useful as way to backload a set of emails into an existing issue.","<b>NOTE:</b> Emails will <b>NOT</b> be sent to the notification list, if this option if chosen. This is useful as way to backload a set of emails into an existing issue.",x);

/* /work/eventum/templates/associate.tpl.html */
ngettext("Save Message as an Internal Note","Save Messages as an Internal Notes",x);

/* /work/eventum/templates/associate.tpl.html */
ngettext("<b>NOTE:</b> Email will be saved as a note and broadcasted only to staff users.","<b>NOTE:</b> Emails will be saved as notes and broadcasted only to staff users.",x);

/* /work/eventum/templates/associate.tpl.html */
gettext("Continue");

/* /work/eventum/templates/list.tpl.html */
gettext("Please choose which issues to update.");

/* /work/eventum/templates/list.tpl.html */
gettext("Please choose new values for the select issues");

/* /work/eventum/templates/list.tpl.html */
gettext("Warning: If you continue, you will change the ");

/* /work/eventum/templates/list.tpl.html */
gettext("for all selected issues. Are you sure you want to continue?");

/* /work/eventum/templates/list.tpl.html */
gettext("Search Results");

/* /work/eventum/templates/list.tpl.html */
gettext("issues found");

/* /work/eventum/templates/list.tpl.html */
gettext("shown");

/* /work/eventum/templates/list.tpl.html */
gettext("hide / show the quick search form");

/* /work/eventum/templates/list.tpl.html */
gettext("quick search");

/* /work/eventum/templates/list.tpl.html */
gettext("hide / show the advanced search form");

/* /work/eventum/templates/list.tpl.html */
gettext("advanced search");

/* /work/eventum/templates/list.tpl.html */
gettext("current filters");

/* /work/eventum/templates/list.tpl.html */
gettext("bulk update tool");

/* /work/eventum/templates/list.tpl.html */
gettext("All");

/* /work/eventum/templates/list.tpl.html */
gettext("sort by");

/* /work/eventum/templates/list.tpl.html */
gettext("sort by");

/* /work/eventum/templates/list.tpl.html */
gettext("sort by summary");

/* /work/eventum/templates/list.tpl.html */
gettext("Export Data:");

/* /work/eventum/templates/list.tpl.html */
gettext("Export to Excel");

/* /work/eventum/templates/list.tpl.html */
gettext("sort by");

/* /work/eventum/templates/list.tpl.html */
gettext("sort by");

/* /work/eventum/templates/list.tpl.html */
gettext("view issue details");

/* /work/eventum/templates/list.tpl.html */
gettext("view issue details");

/* /work/eventum/templates/list.tpl.html */
gettext("No issues could be found.");

/* /work/eventum/templates/list.tpl.html */
gettext("All");

/* /work/eventum/templates/list.tpl.html */
gettext("Go");

/* /work/eventum/templates/list.tpl.html */
gettext("Rows per Page:");

/* /work/eventum/templates/list.tpl.html */
gettext("ALL");

/* /work/eventum/templates/list.tpl.html */
gettext("Set");

/* /work/eventum/templates/list.tpl.html */
gettext("Hide Closed Issues");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Sorry, an error happened while trying to run your query.");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Also, all issues that are marked as duplicates from this one were updated as well.");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Return to Issue");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Details Page");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Please enter the summary for this issue.");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Please enter the description for this issue.");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Percentage complete should be between 0 and 100");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Please select an assignment for this issue");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Note: Project automatically switched to '%1' from '%2'.");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Update Issue Overview");

/* /work/eventum/templates/update_form.tpl.html */
gettext("edit the authorized repliers list for this issue");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Edit Authorized Replier List");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Edit Notification List");

/* /work/eventum/templates/update_form.tpl.html */
gettext("History of Changes");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Category:");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Status:");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Notification List:");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Status:");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Submitted Date:");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Priority:");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Update Date:");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Associated Issues:");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Reporter:");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Expected Resolution Date:");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Scheduled Release:");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Percentage Complete:");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Estimated Dev. Time:");

/* /work/eventum/templates/update_form.tpl.html */
gettext("in hours");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Assignment:");

/* /work/eventum/templates/update_form.tpl.html */
gettext("yes");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Keep Current Assignments:");

/* /work/eventum/templates/update_form.tpl.html */
gettext("no");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Change Assignments:");

/* /work/eventum/templates/update_form.tpl.html */
gettext("no");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Clear Selections");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Current Selections:");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Authorized Repliers:");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Staff:");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Other:");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Group:");

/* /work/eventum/templates/update_form.tpl.html */
gettext("yes");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Summary:");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Description:");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Private:");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Trigger Reminders:");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Update");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Cancel Update");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Reset");

/* /work/eventum/templates/update_form.tpl.html */
gettext("Close Issue");

/* /work/eventum/templates/support_emails.tpl.html */
gettext("Please choose which entries need to be disassociated with the current issue.");

/* /work/eventum/templates/support_emails.tpl.html */
gettext("This action will remove the association of the selected entries to the current issue.");

/* /work/eventum/templates/support_emails.tpl.html */
gettext("Associated Emails");

/* /work/eventum/templates/support_emails.tpl.html */
gettext("view the history of sent emails");

/* /work/eventum/templates/support_emails.tpl.html */
gettext("Mail Queue Log");

/* /work/eventum/templates/support_emails.tpl.html */
gettext("All");

/* /work/eventum/templates/support_emails.tpl.html */
gettext("Reply");

/* /work/eventum/templates/support_emails.tpl.html */
gettext("From");

/* /work/eventum/templates/support_emails.tpl.html */
gettext("To");

/* /work/eventum/templates/support_emails.tpl.html */
gettext("Date");

/* /work/eventum/templates/support_emails.tpl.html */
gettext("Subject");

/* /work/eventum/templates/support_emails.tpl.html */
gettext("reply to this email");

/* /work/eventum/templates/support_emails.tpl.html */
gettext("sent to notification list");

/* /work/eventum/templates/support_emails.tpl.html */
gettext("No associated emails could be found.");

/* /work/eventum/templates/support_emails.tpl.html */
gettext("All");

/* /work/eventum/templates/support_emails.tpl.html */
gettext("Disassociate Selected");

/* /work/eventum/templates/support_emails.tpl.html */
gettext("Send Email");

/* /work/eventum/templates/permission_denied.tpl.html */
gettext("Sorry, you do not have permission to access this page.");

/* /work/eventum/templates/permission_denied.tpl.html */
gettext("Go Back");

/* /work/eventum/templates/duplicate.tpl.html */
gettext("Sorry, an error happened while trying to run your query.");

/* /work/eventum/templates/duplicate.tpl.html */
gettext("Thank you, the issue was marked as a duplicate successfully. Please choose \n            from one of the options below:");

/* /work/eventum/templates/duplicate.tpl.html */
gettext("Open the Issue Details Page");

/* /work/eventum/templates/duplicate.tpl.html */
gettext("Open the Issue Listing Page");

/* /work/eventum/templates/duplicate.tpl.html */
gettext("Open the Emails Listing Page");

/* /work/eventum/templates/duplicate.tpl.html */
gettext("Otherwise, you will be automatically redirected to the Issue Details Page in 5 seconds.");

/* /work/eventum/templates/duplicate.tpl.html */
gettext("Please choose the duplicated issue.");

/* /work/eventum/templates/duplicate.tpl.html */
gettext("Mark Issue as Duplicate");

/* /work/eventum/templates/duplicate.tpl.html */
gettext("Issue ID:");

/* /work/eventum/templates/duplicate.tpl.html */
gettext("Duplicated Issue:");

/* /work/eventum/templates/duplicate.tpl.html */
gettext("Please select an issue");

/* /work/eventum/templates/duplicate.tpl.html */
gettext("Comments:");

/* /work/eventum/templates/duplicate.tpl.html */
gettext("Back");

/* /work/eventum/templates/duplicate.tpl.html */
gettext("Mark Issue as Duplicate");

/* /work/eventum/templates/duplicate.tpl.html */
gettext("Required fields");

/* /work/eventum/templates/post_note.tpl.html */
gettext("An error occurred while trying to run your query");

/* /work/eventum/templates/post_note.tpl.html */
gettext("Thank you, the internal note was posted successfully.");

/* /work/eventum/templates/post_note.tpl.html */
gettext("Continue");

/* /work/eventum/templates/post_note.tpl.html */
gettext("Please enter the title of this note.");

/* /work/eventum/templates/post_note.tpl.html */
gettext("Please enter the message body of this note.");

/* /work/eventum/templates/post_note.tpl.html */
gettext("Post New Internal Note");

/* /work/eventum/templates/post_note.tpl.html */
gettext("From:");

/* /work/eventum/templates/post_note.tpl.html */
gettext("Recipients:");

/* /work/eventum/templates/post_note.tpl.html */
gettext("Notification List");

/* /work/eventum/templates/post_note.tpl.html */
gettext("Title:");

/* /work/eventum/templates/post_note.tpl.html */
gettext("Extra Note Recipients:");

/* /work/eventum/templates/post_note.tpl.html */
gettext("Clear Selections");

/* /work/eventum/templates/post_note.tpl.html */
gettext("Add Extra Recipients To Notification List?");

/* /work/eventum/templates/post_note.tpl.html */
gettext("yes");

/* /work/eventum/templates/post_note.tpl.html */
gettext("Yes");

/* /work/eventum/templates/post_note.tpl.html */
gettext("no");

/* /work/eventum/templates/post_note.tpl.html */
gettext("No");

/* /work/eventum/templates/post_note.tpl.html */
gettext("New Status for Issue");

/* /work/eventum/templates/post_note.tpl.html */
gettext("Time Spent:");

/* /work/eventum/templates/post_note.tpl.html */
gettext("Post Internal Note");

/* /work/eventum/templates/post_note.tpl.html */
gettext("Cancel");

/* /work/eventum/templates/post_note.tpl.html */
gettext("yes");

/* /work/eventum/templates/post_note.tpl.html */
gettext("Add Email Signature");

/* /work/eventum/templates/post_note.tpl.html */
gettext("Required fields");

/* /work/eventum/templates/close.tpl.html */
gettext("Sorry, an error happened while trying to run your query.");

/* /work/eventum/templates/close.tpl.html */
gettext("Thank you, the issue was closed successfully. Please choose from one of the options below:");

/* /work/eventum/templates/close.tpl.html */
gettext("Open the Issue Details Page");

/* /work/eventum/templates/close.tpl.html */
gettext("Open the Issue Listing Page");

/* /work/eventum/templates/close.tpl.html */
gettext("Open the Emails Listing Page");

/* /work/eventum/templates/close.tpl.html */
gettext("Please choose the new status for this issue.");

/* /work/eventum/templates/close.tpl.html */
gettext("Please enter the reason for closing this issue.");

/* /work/eventum/templates/close.tpl.html */
gettext("Please enter integers (or floating point numbers) on the time spent field.");

/* /work/eventum/templates/close.tpl.html */
gettext("Please choose the time tracking category for this new entry.");

/* /work/eventum/templates/close.tpl.html */
gettext("This customer has a per incident contract. You have chosen not to redeem any incidents. Press 'OK' to confirm or 'Cancel' to revise.");

/* /work/eventum/templates/close.tpl.html */
gettext("Close Issue");

/* /work/eventum/templates/close.tpl.html */
gettext("Issue ID:");

/* /work/eventum/templates/close.tpl.html */
gettext("Status:");

/* /work/eventum/templates/close.tpl.html */
gettext("Please choose a status");

/* /work/eventum/templates/close.tpl.html */
gettext("Resolution:");

/* /work/eventum/templates/close.tpl.html */
gettext("Send Notification About Issue Being Closed?");

/* /work/eventum/templates/close.tpl.html */
gettext("Send Notification To:");

/* /work/eventum/templates/close.tpl.html */
gettext("Internal Users");

/* /work/eventum/templates/close.tpl.html */
gettext("All");

/* /work/eventum/templates/close.tpl.html */
gettext("Reason for closing issue:");

/* /work/eventum/templates/close.tpl.html */
gettext("Incident Types to Redeem:");

/* /work/eventum/templates/close.tpl.html */
gettext("Time Spent:");

/* /work/eventum/templates/close.tpl.html */
gettext("Time Category:");

/* /work/eventum/templates/close.tpl.html */
gettext("Please choose a category");

/* /work/eventum/templates/close.tpl.html */
gettext("Back");

/* /work/eventum/templates/close.tpl.html */
gettext("Close Issue");

/* /work/eventum/templates/popup.tpl.html */
gettext("An error occurred while trying to run your query");

/* /work/eventum/templates/popup.tpl.html */
gettext("Please enter the note text on the input box below.");

/* /work/eventum/templates/popup.tpl.html */
gettext("Thank you, the new note was created and associated with the issue below.");

/* /work/eventum/templates/popup.tpl.html */
gettext("An error occurred while trying to run your query");

/* /work/eventum/templates/popup.tpl.html */
gettext("You do not have permission to delete this note.");

/* /work/eventum/templates/popup.tpl.html */
gettext("Thank you, the note was removed successfully.");

/* /work/eventum/templates/popup.tpl.html */
gettext("An error occurred while trying to run your query");

/* /work/eventum/templates/popup.tpl.html */
gettext("Thank you, the time tracking entry was removed successfully.");

/* /work/eventum/templates/popup.tpl.html */
gettext("Thank you, the selected issues were updated successfully.");

/* /work/eventum/templates/popup.tpl.html */
gettext("An error occurred while trying to run your query");

/* /work/eventum/templates/popup.tpl.html */
gettext("An error occurred while trying to run your query");

/* /work/eventum/templates/popup.tpl.html */
gettext("Thank you, the inital impact analysis was set successfully.");

/* /work/eventum/templates/popup.tpl.html */
gettext("An error occurred while trying to run your query");

/* /work/eventum/templates/popup.tpl.html */
gettext("Thank you, the new requirement was added successfully.");

/* /work/eventum/templates/popup.tpl.html */
gettext("An error occurred while trying to run your query");

/* /work/eventum/templates/popup.tpl.html */
gettext("Thank you, the impact analysis was set successfully.");

/* /work/eventum/templates/popup.tpl.html */
gettext("An error occurred while trying to run your query");

/* /work/eventum/templates/popup.tpl.html */
gettext("Thank you, the selected requirements were removed successfully.");

/* /work/eventum/templates/popup.tpl.html */
gettext("An error occurred while trying to run your query");

/* /work/eventum/templates/popup.tpl.html */
gettext("Thank you, the custom filter was saved successfully.");

/* /work/eventum/templates/popup.tpl.html */
gettext("An error occurred while trying to run your query");

/* /work/eventum/templates/popup.tpl.html */
gettext("Thank you, the selected custom filters were removed successfully.");

/* /work/eventum/templates/popup.tpl.html */
gettext("An error occurred while trying to run your query");

/* /work/eventum/templates/popup.tpl.html */
gettext("Thank you, the association to the selected emails were removed successfully.");

/* /work/eventum/templates/popup.tpl.html */
gettext("An error occurred while trying to run your query");

/* /work/eventum/templates/popup.tpl.html */
gettext("You do not have the permission to remove this attachment.");

/* /work/eventum/templates/popup.tpl.html */
gettext("Thank you, the attachment was removed successfully.");

/* /work/eventum/templates/popup.tpl.html */
gettext("An error occurred while trying to run your query");

/* /work/eventum/templates/popup.tpl.html */
gettext("You do not have the permission to remove this file.");

/* /work/eventum/templates/popup.tpl.html */
gettext("Thank you, the file was removed successfully.");

/* /work/eventum/templates/popup.tpl.html */
gettext("An error occurred while trying to run your query");

/* /work/eventum/templates/popup.tpl.html */
gettext("Thank you, the selected checkin information entries were removed successfully.");

/* /work/eventum/templates/popup.tpl.html */
gettext("An error occurred while trying to run your query");

/* /work/eventum/templates/popup.tpl.html */
gettext("Thank you, the emails were marked as removed successfully.");

/* /work/eventum/templates/popup.tpl.html */
gettext("An error occurred while trying to run your query");

/* /work/eventum/templates/popup.tpl.html */
gettext("Thank you, the current issue is no longer marked as a duplicate.");

/* /work/eventum/templates/popup.tpl.html */
gettext("An error occurred while trying to run your query");

/* /work/eventum/templates/popup.tpl.html */
gettext("You do not have permission to remove this phone support entry.");

/* /work/eventum/templates/popup.tpl.html */
gettext("Thank you, the phone support entry was removed successfully.");

/* /work/eventum/templates/popup.tpl.html */
gettext("Thank you, the phone support entry was removed successfully.");

/* /work/eventum/templates/popup.tpl.html */
gettext("The associated time tracking entry was also deleted.");

/* /work/eventum/templates/popup.tpl.html */
gettext("An error occurred while trying to run your query");

/* /work/eventum/templates/popup.tpl.html */
gettext("Thank you, the issue was updated successfully.");

/* /work/eventum/templates/popup.tpl.html */
gettext("An error occurred while trying to run your query");

/* /work/eventum/templates/popup.tpl.html */
gettext("Error: the issue is already unassigned.");

/* /work/eventum/templates/popup.tpl.html */
gettext("Thank you, the issue was unassigned successfully.");

/* /work/eventum/templates/popup.tpl.html */
gettext("Error: you are already authorized to send emails in this issue.");

/* /work/eventum/templates/popup.tpl.html */
gettext("Thank you, you are now authorized to send emails in this issue.");

/* /work/eventum/templates/popup.tpl.html */
gettext("An error occurred while trying to run your query");

/* /work/eventum/templates/popup.tpl.html */
gettext("Thank you, this issue was removed from quarantine.");

/* /work/eventum/templates/popup.tpl.html */
gettext("Continue");

/* /work/eventum/templates/searchbar.tpl.html */
gettext("Quick Search");

/* /work/eventum/templates/searchbar.tpl.html */
gettext("Keyword(s):");

/* /work/eventum/templates/searchbar.tpl.html */
gettext("Assigned:");

/* /work/eventum/templates/searchbar.tpl.html */
gettext("any");

/* /work/eventum/templates/searchbar.tpl.html */
gettext("Status:");

/* /work/eventum/templates/searchbar.tpl.html */
gettext("any");

/* /work/eventum/templates/searchbar.tpl.html */
gettext("Category:");

/* /work/eventum/templates/searchbar.tpl.html */
gettext("any");

/* /work/eventum/templates/searchbar.tpl.html */
gettext("Priority:");

/* /work/eventum/templates/searchbar.tpl.html */
gettext("any");

/* /work/eventum/templates/searchbar.tpl.html */
gettext("Search");

/* /work/eventum/templates/searchbar.tpl.html */
gettext("Clear");

/* /work/eventum/templates/lookup_field.tpl.html */
gettext("paste or start typing here");

/* /work/eventum/templates/lookup_field.tpl.html */
gettext("paste or start typing here");

/* /work/eventum/templates/convert_note.tpl.html */
gettext("An error occurred while trying to convert the selected note.");

/* /work/eventum/templates/convert_note.tpl.html */
gettext("Thank you, the note was converted successfully.");

/* /work/eventum/templates/convert_note.tpl.html */
gettext("Continue");

/* /work/eventum/templates/convert_note.tpl.html */
gettext("WARNING: Converting this note to an email will send the email to any customers that may be listed in this issue's notification list.");

/* /work/eventum/templates/convert_note.tpl.html */
gettext("WARNING: Converting this note to an email will send the email to all users listed in this issue's notification list.");

/* /work/eventum/templates/convert_note.tpl.html */
gettext("WARNING: By converting this blocked message to a draft any attachments this message may have will be lost.");

/* /work/eventum/templates/convert_note.tpl.html */
gettext("Convert Note To Email");

/* /work/eventum/templates/convert_note.tpl.html */
gettext("Convert to Draft and Save For Later Editing");

/* /work/eventum/templates/convert_note.tpl.html */
gettext("<b>ALERT:</b> Email will be re-sent from your name, NOT original sender's, and without any attachments.");

/* /work/eventum/templates/convert_note.tpl.html */
gettext("Convert to Email and Send Now");

/* /work/eventum/templates/convert_note.tpl.html */
gettext("ALERT:");

/* /work/eventum/templates/convert_note.tpl.html */
gettext("Email will be re-sent from original sender, including any attachments.");

/* /work/eventum/templates/convert_note.tpl.html */
gettext("Add sender to authorized repliers list?");

/* /work/eventum/templates/convert_note.tpl.html */
gettext("Continue");

/* /work/eventum/templates/spell_check.tpl.html */
gettext("Spell Check");

/* /work/eventum/templates/spell_check.tpl.html */
gettext("No spelling mistakes could be found.");

/* /work/eventum/templates/spell_check.tpl.html */
gettext("Misspelled Words:");

/* /work/eventum/templates/spell_check.tpl.html */
gettext("Suggestions:");

/* /work/eventum/templates/spell_check.tpl.html */
gettext("Choose a misspelled word");

/* /work/eventum/templates/spell_check.tpl.html */
gettext("Fix Spelling");

/* /work/eventum/templates/email_filter_form.tpl.html */
gettext("Subject/Body:");

/* /work/eventum/templates/email_filter_form.tpl.html */
gettext("Sender:");

/* /work/eventum/templates/email_filter_form.tpl.html */
gettext("To:");

/* /work/eventum/templates/email_filter_form.tpl.html */
gettext("Email Account:");

/* /work/eventum/templates/email_filter_form.tpl.html */
gettext("any");

/* /work/eventum/templates/email_filter_form.tpl.html */
gettext("Search");

/* /work/eventum/templates/email_filter_form.tpl.html */
gettext("Clear");

/* /work/eventum/templates/email_filter_form.tpl.html */
gettext("Filter by Arrival Date:");

/* /work/eventum/templates/email_filter_form.tpl.html */
gettext("Greater Than");

/* /work/eventum/templates/email_filter_form.tpl.html */
gettext("Less Than");

/* /work/eventum/templates/email_filter_form.tpl.html */
gettext("Between");

/* /work/eventum/templates/email_filter_form.tpl.html */
gettext("Arrival Date:");

/* /work/eventum/templates/email_filter_form.tpl.html */
gettext("End date");

/* /work/eventum/templates/signup.tpl.html */
gettext("Sorry, but this feature has been disabled by the administrator.");

/* /work/eventum/templates/signup.tpl.html */
gettext("Go Back");

/* /work/eventum/templates/signup.tpl.html */
gettext("Please enter your full name.");

/* /work/eventum/templates/signup.tpl.html */
gettext("Please enter your email address.");

/* /work/eventum/templates/signup.tpl.html */
gettext("Please enter a valid email address.");

/* /work/eventum/templates/signup.tpl.html */
gettext("Please enter your password.");

/* /work/eventum/templates/signup.tpl.html */
gettext("Account Signup");

/* /work/eventum/templates/signup.tpl.html */
gettext("Error: An error occurred while trying to run your query.");

/* /work/eventum/templates/signup.tpl.html */
gettext("Error: The email address specified is already associated with an user in the system.");

/* /work/eventum/templates/signup.tpl.html */
gettext("Thank you, your account creation request was processed successfully. For security reasons a confirmation email was sent to the provided email address with instructions on how to confirm your request and activate your account.");

/* /work/eventum/templates/signup.tpl.html */
gettext("Full Name:");

/* /work/eventum/templates/signup.tpl.html */
gettext("Email Address:");

/* /work/eventum/templates/signup.tpl.html */
gettext("Password:");

/* /work/eventum/templates/signup.tpl.html */
gettext("Create Account");

/* /work/eventum/templates/signup.tpl.html */
gettext("Back to Login Form");

/* /work/eventum/templates/forgot_password.tpl.html */
gettext("Please enter your account email address.");

/* /work/eventum/templates/forgot_password.tpl.html */
gettext("<b>Note:</b> Please enter your email address below and a new random password will be created and assigned to your account. For security purposes a confirmation message will be sent to your email address and after confirming it the new password will be then activated and sent to you.");

/* /work/eventum/templates/forgot_password.tpl.html */
gettext("Request a Password");

/* /work/eventum/templates/forgot_password.tpl.html */
gettext("Error: An error occurred while trying to run your query.");

/* /work/eventum/templates/forgot_password.tpl.html */
gettext("Thank you, a confirmation message was just emailed to you. Please follow the instructions available in this message to confirm your password creation request.");

/* /work/eventum/templates/forgot_password.tpl.html */
gettext("Error: Your user status is currently set as inactive. Please\n              contact your local system administrator for further information.");

/* /work/eventum/templates/forgot_password.tpl.html */
gettext(" Error: Please provide your email address.");

/* /work/eventum/templates/forgot_password.tpl.html */
gettext("Error: No user account was found matching the entered email address.");

/* /work/eventum/templates/forgot_password.tpl.html */
gettext("Email Address:");

/* /work/eventum/templates/forgot_password.tpl.html */
gettext("Send New Password");

/* /work/eventum/templates/forgot_password.tpl.html */
gettext("Back to Login Form");

/* /work/eventum/templates/view_note.tpl.html */
gettext("The specified note does not exist. <br />\n      It could have been converted to an email.");

/* /work/eventum/templates/view_note.tpl.html */
gettext("Close");

/* /work/eventum/templates/view_note.tpl.html */
gettext("View Note Details");

/* /work/eventum/templates/view_note.tpl.html */
gettext("Associated with Issue");

/* /work/eventum/templates/view_note.tpl.html */
gettext("Previous Note");

/* /work/eventum/templates/view_note.tpl.html */
gettext("Next Note");

/* /work/eventum/templates/view_note.tpl.html */
gettext("Posted Date:");

/* /work/eventum/templates/view_note.tpl.html */
gettext("From:");

/* /work/eventum/templates/view_note.tpl.html */
gettext("Title:");

/* /work/eventum/templates/view_note.tpl.html */
gettext("Attachments:");

/* /work/eventum/templates/view_note.tpl.html */
gettext("download file");

/* /work/eventum/templates/view_note.tpl.html */
gettext("download file");

/* /work/eventum/templates/view_note.tpl.html */
gettext("Message:");

/* /work/eventum/templates/view_note.tpl.html */
gettext("display in fixed width font");

/* /work/eventum/templates/view_note.tpl.html */
gettext("Blocked Message Raw Headers");

/* /work/eventum/templates/view_note.tpl.html */
gettext("Reply");

/* /work/eventum/templates/view_note.tpl.html */
gettext("Close");

/* /work/eventum/templates/view_note.tpl.html */
gettext("Previous Note");

/* /work/eventum/templates/view_note.tpl.html */
gettext("Next Note");

/* /work/eventum/templates/latest_news.tpl.html */
gettext("News and Announcements");

/* /work/eventum/templates/latest_news.tpl.html */
gettext("full news entry");

/* /work/eventum/templates/latest_news.tpl.html */
gettext("Read All Notices");

/* /work/eventum/templates/time_tracking.tpl.html */
gettext("This action will permanently delete the specified time tracking entry.");

/* /work/eventum/templates/time_tracking.tpl.html */
gettext("Time Tracking");

/* /work/eventum/templates/time_tracking.tpl.html */
gettext("Date of Work");

/* /work/eventum/templates/time_tracking.tpl.html */
gettext("User");

/* /work/eventum/templates/time_tracking.tpl.html */
gettext("Time Spent");

/* /work/eventum/templates/time_tracking.tpl.html */
gettext("Category");

/* /work/eventum/templates/time_tracking.tpl.html */
gettext("Summary");

/* /work/eventum/templates/time_tracking.tpl.html */
gettext("Total Time Spent");

/* /work/eventum/templates/time_tracking.tpl.html */
gettext("No time tracking entries could be found.");

/* /work/eventum/templates/time_tracking.tpl.html */
gettext("Add Time Entry");

/* /work/eventum/templates/customer/example/customer_lookup.tpl.html */
gettext("Field");

/* /work/eventum/templates/customer/example/customer_lookup.tpl.html */
gettext("Value");

/* /work/eventum/templates/customer/example/customer_lookup.tpl.html */
gettext("Customer Lookup Tool");

/* /work/eventum/templates/customer/example/customer_lookup.tpl.html */
gettext("Field");

/* /work/eventum/templates/customer/example/customer_lookup.tpl.html */
gettext("Email Address");

/* /work/eventum/templates/customer/example/customer_lookup.tpl.html */
gettext("Customer ID");

/* /work/eventum/templates/customer/example/customer_lookup.tpl.html */
gettext("Value");

/* /work/eventum/templates/customer/example/customer_lookup.tpl.html */
gettext("Lookup");

/* /work/eventum/templates/customer/example/customer_lookup.tpl.html */
gettext("Cancel");

/* /work/eventum/templates/customer/example/customer_lookup.tpl.html */
gettext("Results");

/* /work/eventum/templates/customer/example/customer_lookup.tpl.html */
gettext("Customer");

/* /work/eventum/templates/customer/example/customer_lookup.tpl.html */
gettext("Support Type");

/* /work/eventum/templates/customer/example/customer_lookup.tpl.html */
gettext("Expiration Date");

/* /work/eventum/templates/customer/example/customer_lookup.tpl.html */
gettext("Status");

/* /work/eventum/templates/customer/example/customer_lookup.tpl.html */
gettext("No results could be found");

/* /work/eventum/templates/customer/example/report_form_fields.tpl.html */
gettext("Customer");

/* /work/eventum/templates/customer/example/report_form_fields.tpl.html */
gettext("Contact");

/* /work/eventum/templates/customer/example/report_form_fields.tpl.html */
gettext("Contact Person Last Name");

/* /work/eventum/templates/customer/example/report_form_fields.tpl.html */
gettext("Contact Person First Name");

/* /work/eventum/templates/customer/example/report_form_fields.tpl.html */
gettext("Contact Email");

/* /work/eventum/templates/customer/example/report_form_fields.tpl.html */
gettext("Contact Email");

/* /work/eventum/templates/customer/example/report_form_fields.tpl.html */
gettext("Customer Details");

/* /work/eventum/templates/customer/example/report_form_fields.tpl.html */
gettext("Customer");

/* /work/eventum/templates/customer/example/report_form_fields.tpl.html */
gettext("Lookup Customer");

/* /work/eventum/templates/customer/example/report_form_fields.tpl.html */
gettext("Contact");

/* /work/eventum/templates/customer/example/report_form_fields.tpl.html */
gettext("Add Primary Contact to Notification List? *");

/* /work/eventum/templates/customer/example/report_form_fields.tpl.html */
gettext("Yes");

/* /work/eventum/templates/customer/example/report_form_fields.tpl.html */
gettext("No");

/* /work/eventum/templates/customer/example/report_form_fields.tpl.html */
gettext("Notify Customer About New Issue? *");

/* /work/eventum/templates/customer/example/report_form_fields.tpl.html */
gettext("Yes");

/* /work/eventum/templates/customer/example/report_form_fields.tpl.html */
gettext("No");

/* /work/eventum/templates/customer/example/report_form_fields.tpl.html */
gettext("Last Name");

/* /work/eventum/templates/customer/example/report_form_fields.tpl.html */
gettext("First Name");

/* /work/eventum/templates/customer/example/report_form_fields.tpl.html */
gettext("Email");

/* /work/eventum/templates/customer/example/report_form_fields.tpl.html */
gettext("Phone Number");

/* /work/eventum/templates/customer/example/report_form_fields.tpl.html */
gettext("Timezone");

/* /work/eventum/templates/customer/example/report_form_fields.tpl.html */
gettext("Additional Contact Emails");

/* /work/eventum/templates/customer/example/report_form_fields.tpl.html */
gettext("(hold ctrl to select multiple options)");

/* /work/eventum/templates/customer/example/report_form_fields.tpl.html */
gettext("(only technical contacts listed on your contract)");

/* /work/eventum/templates/customer/example/customer_report.tpl.html */
gettext("Example customer API front page");

/* /work/eventum/templates/customer/example/customer_info.tpl.html */
gettext("Customer Details");

/* /work/eventum/templates/customer/example/customer_info.tpl.html */
gettext("Contact Person");

/* /work/eventum/templates/customer/example/customer_info.tpl.html */
gettext("Contact Email");

/* /work/eventum/templates/customer/example/customer_info.tpl.html */
gettext("Phone Number");

/* /work/eventum/templates/customer/example/customer_info.tpl.html */
gettext("Timezone");

/* /work/eventum/templates/customer/example/customer_info.tpl.html */
gettext("Contact's Local Time");

/* /work/eventum/templates/customer/example/customer_info.tpl.html */
gettext("Maximum First Response Time");

/* /work/eventum/templates/customer/example/customer_info.tpl.html */
gettext("Time Until First Response Deadline");

/* /work/eventum/templates/customer/example/customer_info.tpl.html */
gettext("Customer");

/* /work/eventum/templates/customer/example/customer_info.tpl.html */
gettext("Support Level");

/* /work/eventum/templates/customer/example/customer_info.tpl.html */
gettext("Support Expiration Date");

/* /work/eventum/templates/customer/example/customer_info.tpl.html */
gettext("Sales Account Manager");

/* /work/eventum/templates/customer/example/customer_info.tpl.html */
gettext("Notes About Customer");

/* /work/eventum/templates/customer/example/customer_info.tpl.html */
gettext("Add");

/* /work/eventum/templates/customer/example/customer_info.tpl.html */
gettext("Edit");

/* /work/eventum/templates/customer/example/quarantine.tpl.html */
gettext("Quarantine explanation goes here...");

/* /work/eventum/templates/customer/example/customer_expired.tpl.html */
gettext("Expired Customer");

/* /work/eventum/templates/customer/example/customer_expired.tpl.html */
gettext("Contact");

/* /work/eventum/templates/customer/example/customer_expired.tpl.html */
gettext("Company Name");

/* /work/eventum/templates/customer/example/customer_expired.tpl.html */
gettext("Contract #");

/* /work/eventum/templates/customer/example/customer_expired.tpl.html */
gettext("Support Level");

/* /work/eventum/templates/customer/example/customer_expired.tpl.html */
gettext("Expired");

/* /work/eventum/templates/customer/example/customer_expired.tpl.html */
gettext("Back");

/* /work/eventum/templates/add_phone_entry.tpl.html */
gettext("Issue");

/* /work/eventum/templates/add_phone_entry.tpl.html */
gettext("Add Phone Entry");

/* /work/eventum/templates/add_phone_entry.tpl.html */
gettext("An error occurred while trying to run your query");

/* /work/eventum/templates/add_phone_entry.tpl.html */
gettext("Thank you, the phone entry was added successfully.");

/* /work/eventum/templates/add_phone_entry.tpl.html */
gettext("Continue");

/* /work/eventum/templates/add_phone_entry.tpl.html */
gettext("Please select a valid date for when the phone call took place.");

/* /work/eventum/templates/add_phone_entry.tpl.html */
gettext("Please enter integers (or floating point numbers) on the time spent field.");

/* /work/eventum/templates/add_phone_entry.tpl.html */
gettext("Please enter the description for this new phone support entry.");

/* /work/eventum/templates/add_phone_entry.tpl.html */
gettext("Please choose the category for this new phone support entry.");

/* /work/eventum/templates/add_phone_entry.tpl.html */
gettext("Record Phone Call");

/* /work/eventum/templates/add_phone_entry.tpl.html */
gettext("Date of Call");

/* /work/eventum/templates/add_phone_entry.tpl.html */
gettext("Reason");

/* /work/eventum/templates/add_phone_entry.tpl.html */
gettext("Call From");

/* /work/eventum/templates/add_phone_entry.tpl.html */
gettext("last name");

/* /work/eventum/templates/add_phone_entry.tpl.html */
gettext("first name");

/* /work/eventum/templates/add_phone_entry.tpl.html */
gettext("Call To");

/* /work/eventum/templates/add_phone_entry.tpl.html */
gettext("last name");

/* /work/eventum/templates/add_phone_entry.tpl.html */
gettext("first name");

/* /work/eventum/templates/add_phone_entry.tpl.html */
gettext("Type");

/* /work/eventum/templates/add_phone_entry.tpl.html */
gettext("Incoming");

/* /work/eventum/templates/add_phone_entry.tpl.html */
gettext("Outgoing");

/* /work/eventum/templates/add_phone_entry.tpl.html */
gettext("Customer Phone Number");

/* /work/eventum/templates/add_phone_entry.tpl.html */
gettext("Office");

/* /work/eventum/templates/add_phone_entry.tpl.html */
gettext("Home");

/* /work/eventum/templates/add_phone_entry.tpl.html */
gettext("Mobile");

/* /work/eventum/templates/add_phone_entry.tpl.html */
gettext("Temp Number");

/* /work/eventum/templates/add_phone_entry.tpl.html */
gettext("Other");

/* /work/eventum/templates/add_phone_entry.tpl.html */
gettext("Time Spent");

/* /work/eventum/templates/add_phone_entry.tpl.html */
gettext("in minutes");

/* /work/eventum/templates/add_phone_entry.tpl.html */
gettext("Description");

/* /work/eventum/templates/add_phone_entry.tpl.html */
gettext("Save Phone Call");

/* /work/eventum/templates/emails.tpl.html */
gettext("Associate Emails");

/* /work/eventum/templates/emails.tpl.html */
gettext("Sorry, but this feature has been disabled by the administrator.");

/* /work/eventum/templates/emails.tpl.html */
gettext("Go Back");

/* /work/eventum/templates/emails.tpl.html */
gettext("Sorry, but you do not have access to this page.");

/* /work/eventum/templates/emails.tpl.html */
gettext("Go Back");

/* /work/eventum/templates/emails.tpl.html */
gettext("Please choose which emails need to be associated.");

/* /work/eventum/templates/emails.tpl.html */
gettext("Please choose which emails need to be marked as deleted.");

/* /work/eventum/templates/emails.tpl.html */
gettext("This action will mark the selected email messages as deleted.");

/* /work/eventum/templates/emails.tpl.html */
ngettext("Viewing Emails (%1 emails found)","Viewing Emails (%1 emails found, %2 - %3 shown)",x);

/* /work/eventum/templates/emails.tpl.html */
gettext("All");

/* /work/eventum/templates/emails.tpl.html */
gettext("Sender");

/* /work/eventum/templates/emails.tpl.html */
gettext("sort by sender");

/* /work/eventum/templates/emails.tpl.html */
gettext("Customer");

/* /work/eventum/templates/emails.tpl.html */
gettext("sort by customer");

/* /work/eventum/templates/emails.tpl.html */
gettext("Date");

/* /work/eventum/templates/emails.tpl.html */
gettext("sort by date");

/* /work/eventum/templates/emails.tpl.html */
gettext("To");

/* /work/eventum/templates/emails.tpl.html */
gettext("sort by recipient");

/* /work/eventum/templates/emails.tpl.html */
gettext("Status");

/* /work/eventum/templates/emails.tpl.html */
gettext("sort by status");

/* /work/eventum/templates/emails.tpl.html */
gettext("Subject");

/* /work/eventum/templates/emails.tpl.html */
gettext("sort by subject");

/* /work/eventum/templates/emails.tpl.html */
gettext("associated");

/* /work/eventum/templates/emails.tpl.html */
gettext("view issue details");

/* /work/eventum/templates/emails.tpl.html */
gettext("pending");

/* /work/eventum/templates/emails.tpl.html */
gettext("Empty Subject Header");

/* /work/eventum/templates/emails.tpl.html */
gettext("view email details");

/* /work/eventum/templates/emails.tpl.html */
gettext("view email details");

/* /work/eventum/templates/emails.tpl.html */
gettext("No emails could be found.");

/* /work/eventum/templates/emails.tpl.html */
gettext("All");

/* /work/eventum/templates/emails.tpl.html */
gettext("Associate");

/* /work/eventum/templates/emails.tpl.html */
gettext("New Issue");

/* /work/eventum/templates/emails.tpl.html */
gettext("lookup issues by their summaries");

/* /work/eventum/templates/emails.tpl.html */
gettext("ALL");

/* /work/eventum/templates/emails.tpl.html */
gettext("Set");

/* /work/eventum/templates/emails.tpl.html */
gettext("Hide Associated Emails");

/* /work/eventum/templates/emails.tpl.html */
gettext("Remove Selected Emails");

/* /work/eventum/templates/emails.tpl.html */
gettext("list all removed emails");

/* /work/eventum/templates/emails.tpl.html */
gettext("List Removed Emails");

/* /work/eventum/templates/setup.tpl.html */
gettext("Please enter the hostname for the server of this installation of Eventum.");

/* /work/eventum/templates/setup.tpl.html */
gettext("Please enter the relative URL of this installation of Eventum.");

/* /work/eventum/templates/setup.tpl.html */
gettext("Please enter the full path in the server of this installation of Eventum.");

/* /work/eventum/templates/setup.tpl.html */
gettext("Please enter the database hostname for this installation of Eventum.");

/* /work/eventum/templates/setup.tpl.html */
gettext("Please enter the database name for this installation of Eventum.");

/* /work/eventum/templates/setup.tpl.html */
gettext("Please enter the database username for this installation of Eventum.");

/* /work/eventum/templates/setup.tpl.html */
gettext("Please enter the alternate username for this installation of Eventum.");

/* /work/eventum/templates/setup.tpl.html */
gettext("Please enter the sender address that will be used for all outgoing notification emails.");

/* /work/eventum/templates/setup.tpl.html */
gettext("Please enter a valid email address for the sender address.");

/* /work/eventum/templates/setup.tpl.html */
gettext("Please enter the SMTP server hostname.");

/* /work/eventum/templates/setup.tpl.html */
gettext("Please enter the SMTP server port number.");

/* /work/eventum/templates/setup.tpl.html */
gettext("Please indicate whether the SMTP server requires authentication or not.");

/* /work/eventum/templates/setup.tpl.html */
gettext("Please enter the SMTP server username.");

/* /work/eventum/templates/setup.tpl.html */
gettext("Please enter the SMTP server password.");

/* /work/eventum/templates/setup.tpl.html */
gettext("An Error Was Found");

/* /work/eventum/templates/setup.tpl.html */
gettext("Details:");

/* /work/eventum/templates/setup.tpl.html */
gettext("Success!");

/* /work/eventum/templates/setup.tpl.html */
gettext("Thank You, Eventum is now properly setup and ready to be used. Open the following URL to login on it for the first time:");

/* /work/eventum/templates/setup.tpl.html */
gettext("Email Address: admin@example.com (literally)");

/* /work/eventum/templates/setup.tpl.html */
gettext("Password: admin");

/* /work/eventum/templates/setup.tpl.html */
gettext("NOTE: For security reasons it is highly recommended that the default password be changed as soon as possible.");

/* /work/eventum/templates/setup.tpl.html */
gettext("Remember to protect your 'setup' directory (like changing its permissions) to prevent anyone else\n            from changing your existing Eventum configuration.");

/* /work/eventum/templates/setup.tpl.html */
gettext("In order to check if your permissions are setup correctly visit the <a class=\"link\" href=\"check_permissions.php\">Check Permissions</a> page.");

/* /work/eventum/templates/setup.tpl.html */
gettext("WARNING: If you want to use the email integration features to download messages saved on a IMAP/POP3 server, you will need to\n            enable the IMAP extension in your PHP.INI configuration file. See the PHP manual for more details.");

/* /work/eventum/templates/setup.tpl.html */
gettext("Warning!");

/* /work/eventum/templates/setup.tpl.html */
gettext("You are running PHP version %1.\nWhile all effort has been made to ensure eventum works correctly with\nPHP 5 and greater, it has not been thoroughly tested and may not work properly.");

/* /work/eventum/templates/setup.tpl.html */
gettext("Please report any problems you find to eventum-users@lists.mysql.com.");

/* /work/eventum/templates/setup.tpl.html */
gettext("Eventum Installation");

/* /work/eventum/templates/setup.tpl.html */
gettext("Server Hostname:");

/* /work/eventum/templates/setup.tpl.html */
gettext("SSL Server");

/* /work/eventum/templates/setup.tpl.html */
gettext("Eventum Relative URL:");

/* /work/eventum/templates/setup.tpl.html */
gettext("Installation Path:");

/* /work/eventum/templates/setup.tpl.html */
gettext("MySQL Server Hostname:");

/* /work/eventum/templates/setup.tpl.html */
gettext("MySQL Database:");

/* /work/eventum/templates/setup.tpl.html */
gettext("Create Database");

/* /work/eventum/templates/setup.tpl.html */
gettext("MySQL Table Prefix:");

/* /work/eventum/templates/setup.tpl.html */
gettext("Drop Tables If They Already Exist");

/* /work/eventum/templates/setup.tpl.html */
gettext("MySQL Username:");

/* /work/eventum/templates/setup.tpl.html */
gettext("<b>Note:</b> This user requires permission to create and drop tables in the specified database.<br />This value is used only for these installation procedures, and is not saved if you provide a separate user below.");

/* /work/eventum/templates/setup.tpl.html */
gettext("MySQL Password:");

/* /work/eventum/templates/setup.tpl.html */
gettext("Use a Separate MySQL User for Normal Eventum Use");

/* /work/eventum/templates/setup.tpl.html */
gettext("Enter the details below:");

/* /work/eventum/templates/setup.tpl.html */
gettext("Username:");

/* /work/eventum/templates/setup.tpl.html */
gettext("Password:");

/* /work/eventum/templates/setup.tpl.html */
gettext("Create User and Permissions");

/* /work/eventum/templates/setup.tpl.html */
gettext("SMTP Configuration");

/* /work/eventum/templates/setup.tpl.html */
gettext("<b>Note:</b> The SMTP (outgoing mail) configuration is needed to make sure emails are properly sent when creating new users/projects.");

/* /work/eventum/templates/setup.tpl.html */
gettext("Sender:");

/* /work/eventum/templates/setup.tpl.html */
gettext("must be a valid email address");

/* /work/eventum/templates/setup.tpl.html */
gettext("Hostname:");

/* /work/eventum/templates/setup.tpl.html */
gettext("Port:");

/* /work/eventum/templates/setup.tpl.html */
gettext("Requires Authentication?");

/* /work/eventum/templates/setup.tpl.html */
gettext("Yes");

/* /work/eventum/templates/setup.tpl.html */
gettext("No");

/* /work/eventum/templates/setup.tpl.html */
gettext("Username:");

/* /work/eventum/templates/setup.tpl.html */
gettext("Password:");

/* /work/eventum/templates/setup.tpl.html */
gettext("Start Installation");

/* /work/eventum/templates/setup.tpl.html */
gettext("Required Fields");

/* /work/eventum/templates/current_filters.tpl.html */
gettext("Current filters:");

/* /work/eventum/templates/current_filters.tpl.html */
gettext("Fulltext");

/* /work/eventum/templates/current_filters.tpl.html */
gettext("In Past %1 hours");

/* /work/eventum/templates/current_filters.tpl.html */
gettext("Is NULL");

/* /work/eventum/templates/current_filters.tpl.html */
gettext("Is between %1-%2-%3 AND %4-%5-%6");

/* /work/eventum/templates/current_filters.tpl.html */
gettext("Is greater than %1-%2-%3");

/* /work/eventum/templates/current_filters.tpl.html */
gettext("Is less than %1-%2-%3");

/* /work/eventum/templates/current_filters.tpl.html */
gettext("un-assigned");

/* /work/eventum/templates/current_filters.tpl.html */
gettext("myself and un-assigned");

/* /work/eventum/templates/current_filters.tpl.html */
gettext("myself and my group");

/* /work/eventum/templates/current_filters.tpl.html */
gettext("myself, un-assigned and my group");

/* /work/eventum/templates/current_filters.tpl.html */
gettext("Yes");

/* /work/eventum/templates/current_filters.tpl.html */
gettext("None");

/* /work/eventum/templates/phone_support.tpl.html */
gettext("This action will permanently delete the specified phone support entry.");

/* /work/eventum/templates/phone_support.tpl.html */
gettext("Phone Calls");

/* /work/eventum/templates/phone_support.tpl.html */
gettext("Recorded Date");

/* /work/eventum/templates/phone_support.tpl.html */
gettext("Entered By");

/* /work/eventum/templates/phone_support.tpl.html */
gettext("From");

/* /work/eventum/templates/phone_support.tpl.html */
gettext("To");

/* /work/eventum/templates/phone_support.tpl.html */
gettext("Call Type");

/* /work/eventum/templates/phone_support.tpl.html */
gettext("Category");

/* /work/eventum/templates/phone_support.tpl.html */
gettext("Phone Number");

/* /work/eventum/templates/phone_support.tpl.html */
gettext("delete");

/* /work/eventum/templates/phone_support.tpl.html */
gettext("No phone calls recorded yet.");

/* /work/eventum/templates/phone_support.tpl.html */
gettext("Add Phone Call");

/* /work/eventum/templates/manage/resolution.tpl.html */
gettext("Please enter the title of this resolution.");

/* /work/eventum/templates/manage/resolution.tpl.html */
gettext("Manage Issue Resolutions");

/* /work/eventum/templates/manage/resolution.tpl.html */
gettext("An error occurred while trying to add the new issue resolution.");

/* /work/eventum/templates/manage/resolution.tpl.html */
gettext("Please enter the title for this new issue resolution.");

/* /work/eventum/templates/manage/resolution.tpl.html */
gettext("Thank you, the issue resolution was added successfully.");

/* /work/eventum/templates/manage/resolution.tpl.html */
gettext("An error occurred while trying to update the issue resolution information.");

/* /work/eventum/templates/manage/resolution.tpl.html */
gettext("Please enter the title for this issue resolution.");

/* /work/eventum/templates/manage/resolution.tpl.html */
gettext("Thank you, the issue resolution was updated successfully.");

/* /work/eventum/templates/manage/resolution.tpl.html */
gettext("Title:");

/* /work/eventum/templates/manage/resolution.tpl.html */
gettext("Update Resolution");

/* /work/eventum/templates/manage/resolution.tpl.html */
gettext("Create Resolution");

/* /work/eventum/templates/manage/resolution.tpl.html */
gettext("Reset");

/* /work/eventum/templates/manage/resolution.tpl.html */
gettext("Existing Resolutions:");

/* /work/eventum/templates/manage/resolution.tpl.html */
gettext("Please select at least one of the resolutions.");

/* /work/eventum/templates/manage/resolution.tpl.html */
gettext("This action will remove the selected entries.");

/* /work/eventum/templates/manage/resolution.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/resolution.tpl.html */
gettext("Title");

/* /work/eventum/templates/manage/resolution.tpl.html */
gettext("update this entry");

/* /work/eventum/templates/manage/resolution.tpl.html */
gettext("No resolutions could be found.");

/* /work/eventum/templates/manage/resolution.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/resolution.tpl.html */
gettext("Delete");

/* /work/eventum/templates/manage/round_robin.tpl.html */
gettext("Please assign the appropriate users for this round robin entry.");

/* /work/eventum/templates/manage/round_robin.tpl.html */
gettext("Manage Round Robin Assignments");

/* /work/eventum/templates/manage/round_robin.tpl.html */
gettext("An error occurred while trying to add the round robin entry.");

/* /work/eventum/templates/manage/round_robin.tpl.html */
gettext("Please enter the title for this round robin entry.");

/* /work/eventum/templates/manage/round_robin.tpl.html */
gettext("Please enter the message for this round robin entry.");

/* /work/eventum/templates/manage/round_robin.tpl.html */
gettext("Thank you, the round robin entry was added successfully.");

/* /work/eventum/templates/manage/round_robin.tpl.html */
gettext("An error occurred while trying to update the round robin entry information.");

/* /work/eventum/templates/manage/round_robin.tpl.html */
gettext("Please enter the title for this round robin entry.");

/* /work/eventum/templates/manage/round_robin.tpl.html */
gettext("Please enter the message for this round robin entry.");

/* /work/eventum/templates/manage/round_robin.tpl.html */
gettext("Thank you, the round robin entry was updated successfully.");

/* /work/eventum/templates/manage/round_robin.tpl.html */
gettext("Project:");

/* /work/eventum/templates/manage/round_robin.tpl.html */
gettext("Please choose an option");

/* /work/eventum/templates/manage/round_robin.tpl.html */
gettext("Assignable Users:");

/* /work/eventum/templates/manage/round_robin.tpl.html */
gettext("Blackout Time Range:");

/* /work/eventum/templates/manage/round_robin.tpl.html */
gettext("Update Round Robin Entry");

/* /work/eventum/templates/manage/round_robin.tpl.html */
gettext("Create Round Robin Entry");

/* /work/eventum/templates/manage/round_robin.tpl.html */
gettext("Reset");

/* /work/eventum/templates/manage/round_robin.tpl.html */
gettext("Existing Round Robin Entries:");

/* /work/eventum/templates/manage/round_robin.tpl.html */
gettext("Please select at least one of the round robin entries.");

/* /work/eventum/templates/manage/round_robin.tpl.html */
gettext("This action will permanently remove the selected round robin entries.");

/* /work/eventum/templates/manage/round_robin.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/round_robin.tpl.html */
gettext("Project");

/* /work/eventum/templates/manage/round_robin.tpl.html */
gettext("Assignable Users");

/* /work/eventum/templates/manage/round_robin.tpl.html */
gettext("update this entry");

/* /work/eventum/templates/manage/round_robin.tpl.html */
gettext("No round robin entries could be found.");

/* /work/eventum/templates/manage/round_robin.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/round_robin.tpl.html */
gettext("Delete");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("Action Type");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("Rank");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("Manage Reminder Actions");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("view reminder details");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("An error occurred while trying to add the new action.");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("Please enter the title for this new action.");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("Thank you, the action was added successfully.");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("An error occurred while trying to update the action information.");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("Please enter the title for this action.");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("Thank you, the action was updated successfully.");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("Title:");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("Action Type:");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("Please choose an option");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("Email List:");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("Add");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("Remove");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("Rank:");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("this will determine the order in which actions are triggered");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("Alert Group Leader:");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("Yes");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("No");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("Alert IRC:");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("Yes");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("No");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("Boilerplate:");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("this will show up on the bottom of the reminder messages");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("Update Action");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("Add Action");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("Reset");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("Existing Actions:");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("Back to Reminder List");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("Please select at least one of the actions.");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("This action will remove the selected entries.");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("Rank");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("Title");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("Type");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("Details");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("No actions could be found.");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/reminder_actions.tpl.html */
gettext("Delete");

/* /work/eventum/templates/manage/anonymous.tpl.html */
gettext("Please choose whether the anonymous posting feature should be allowed or not for this project");

/* /work/eventum/templates/manage/anonymous.tpl.html */
gettext("Please choose whether to show custom fields for remote invocations or not.");

/* /work/eventum/templates/manage/anonymous.tpl.html */
gettext("Please choose the reporter for remote invocations.");

/* /work/eventum/templates/manage/anonymous.tpl.html */
gettext("Please choose the default category for remote invocations.");

/* /work/eventum/templates/manage/anonymous.tpl.html */
gettext("Please choose the default priority for remote invocations.");

/* /work/eventum/templates/manage/anonymous.tpl.html */
gettext("Please choose at least one person to assign the new issues created remotely.");

/* /work/eventum/templates/manage/anonymous.tpl.html */
gettext("Anonymous Reporting of New Issues");

/* /work/eventum/templates/manage/anonymous.tpl.html */
gettext("Current Project:");

/* /work/eventum/templates/manage/anonymous.tpl.html */
gettext("An error occurred while trying to update the information.");

/* /work/eventum/templates/manage/anonymous.tpl.html */
gettext("Thank you, the information was updated successfully.");

/* /work/eventum/templates/manage/anonymous.tpl.html */
gettext("Anonymous Reporting:");

/* /work/eventum/templates/manage/anonymous.tpl.html */
gettext("Enabled");

/* /work/eventum/templates/manage/anonymous.tpl.html */
gettext("Disabled");

/* /work/eventum/templates/manage/anonymous.tpl.html */
gettext("Show Custom Fields ?");

/* /work/eventum/templates/manage/anonymous.tpl.html */
gettext("Enabled");

/* /work/eventum/templates/manage/anonymous.tpl.html */
gettext("Disabled");

/* /work/eventum/templates/manage/anonymous.tpl.html */
gettext("Reporter:");

/* /work/eventum/templates/manage/anonymous.tpl.html */
gettext("Please choose an user");

/* /work/eventum/templates/manage/anonymous.tpl.html */
gettext("Default Category:");

/* /work/eventum/templates/manage/anonymous.tpl.html */
gettext("Please choose a category");

/* /work/eventum/templates/manage/anonymous.tpl.html */
gettext("Default Priority:");

/* /work/eventum/templates/manage/anonymous.tpl.html */
gettext("Please choose a priority");

/* /work/eventum/templates/manage/anonymous.tpl.html */
gettext("Assignment:");

/* /work/eventum/templates/manage/anonymous.tpl.html */
gettext("Update Setup");

/* /work/eventum/templates/manage/anonymous.tpl.html */
gettext("Reset");

/* /work/eventum/templates/manage/issue_auto_creation.tpl.html */
gettext("Please choose whether the issue auto creation feature should be allowed or not for this email account");

/* /work/eventum/templates/manage/issue_auto_creation.tpl.html */
gettext("Please choose the default category.");

/* /work/eventum/templates/manage/issue_auto_creation.tpl.html */
gettext("Please choose the default priority.");

/* /work/eventum/templates/manage/issue_auto_creation.tpl.html */
gettext("Auto-Creation of Issues");

/* /work/eventum/templates/manage/issue_auto_creation.tpl.html */
gettext("Associated Project:");

/* /work/eventum/templates/manage/issue_auto_creation.tpl.html */
gettext("Auto-Creation of Issues:");

/* /work/eventum/templates/manage/issue_auto_creation.tpl.html */
gettext("Enabled");

/* /work/eventum/templates/manage/issue_auto_creation.tpl.html */
gettext("Disabled");

/* /work/eventum/templates/manage/issue_auto_creation.tpl.html */
gettext("Only for Known Customers?");

/* /work/eventum/templates/manage/issue_auto_creation.tpl.html */
gettext("Yes");

/* /work/eventum/templates/manage/issue_auto_creation.tpl.html */
gettext("No");

/* /work/eventum/templates/manage/issue_auto_creation.tpl.html */
gettext("Default Category:");

/* /work/eventum/templates/manage/issue_auto_creation.tpl.html */
gettext("Please choose a category");

/* /work/eventum/templates/manage/issue_auto_creation.tpl.html */
gettext("Default Priority:");

/* /work/eventum/templates/manage/issue_auto_creation.tpl.html */
gettext("Please choose a priority");

/* /work/eventum/templates/manage/issue_auto_creation.tpl.html */
gettext("Assignment:");

/* /work/eventum/templates/manage/issue_auto_creation.tpl.html */
gettext("Update Setup");

/* /work/eventum/templates/manage/issue_auto_creation.tpl.html */
gettext("Reset");

/* /work/eventum/templates/manage/email_responses.tpl.html */
gettext("Please enter the title of this email response.");

/* /work/eventum/templates/manage/email_responses.tpl.html */
gettext("Manage Canned Email Responses");

/* /work/eventum/templates/manage/email_responses.tpl.html */
gettext("An error occurred while trying to add the new email response.");

/* /work/eventum/templates/manage/email_responses.tpl.html */
gettext("Please enter the title for this new email response.");

/* /work/eventum/templates/manage/email_responses.tpl.html */
gettext("Thank you, the email response was added successfully.");

/* /work/eventum/templates/manage/email_responses.tpl.html */
gettext("An error occurred while trying to update the email response information.");

/* /work/eventum/templates/manage/email_responses.tpl.html */
gettext("Please enter the title for this email response.");

/* /work/eventum/templates/manage/email_responses.tpl.html */
gettext("Thank you, the email response was updated successfully.");

/* /work/eventum/templates/manage/email_responses.tpl.html */
gettext("Projects:");

/* /work/eventum/templates/manage/email_responses.tpl.html */
gettext("Title:");

/* /work/eventum/templates/manage/email_responses.tpl.html */
gettext("Response Body:");

/* /work/eventum/templates/manage/email_responses.tpl.html */
gettext("Update Email Response");

/* /work/eventum/templates/manage/email_responses.tpl.html */
gettext("Create Email Response");

/* /work/eventum/templates/manage/email_responses.tpl.html */
gettext("Reset");

/* /work/eventum/templates/manage/email_responses.tpl.html */
gettext("Existing Canned Email Responses:");

/* /work/eventum/templates/manage/email_responses.tpl.html */
gettext("Please select at least one of the email responses.");

/* /work/eventum/templates/manage/email_responses.tpl.html */
gettext("This action will remove the selected entries.");

/* /work/eventum/templates/manage/email_responses.tpl.html */
gettext("Title");

/* /work/eventum/templates/manage/email_responses.tpl.html */
gettext("Projects");

/* /work/eventum/templates/manage/email_responses.tpl.html */
gettext("update this entry");

/* /work/eventum/templates/manage/email_responses.tpl.html */
gettext("No canned email responses could be found.");

/* /work/eventum/templates/manage/email_responses.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/email_responses.tpl.html */
gettext("Delete");

/* /work/eventum/templates/manage/customize_listing.tpl.html */
gettext("Please choose the project that you wish to customize.");

/* /work/eventum/templates/manage/customize_listing.tpl.html */
gettext("Customize Issue Listing Screen");

/* /work/eventum/templates/manage/customize_listing.tpl.html */
gettext("An error occurred while trying to add the new customization.");

/* /work/eventum/templates/manage/customize_listing.tpl.html */
gettext("Please enter the title for this new customization.");

/* /work/eventum/templates/manage/customize_listing.tpl.html */
gettext("Thank you, the customization was added successfully.");

/* /work/eventum/templates/manage/customize_listing.tpl.html */
gettext("An error occurred while trying to update the customization information.");

/* /work/eventum/templates/manage/customize_listing.tpl.html */
gettext("Please enter the title for this customization.");

/* /work/eventum/templates/manage/customize_listing.tpl.html */
gettext("Thank you, the customization was updated successfully.");

/* /work/eventum/templates/manage/customize_listing.tpl.html */
gettext("Project:");

/* /work/eventum/templates/manage/customize_listing.tpl.html */
gettext("Please choose an option");

/* /work/eventum/templates/manage/customize_listing.tpl.html */
gettext("Status:");

/* /work/eventum/templates/manage/customize_listing.tpl.html */
gettext("Date Field:");

/* /work/eventum/templates/manage/customize_listing.tpl.html */
gettext("Label:");

/* /work/eventum/templates/manage/customize_listing.tpl.html */
gettext("Update Customization");

/* /work/eventum/templates/manage/customize_listing.tpl.html */
gettext("Create Customization");

/* /work/eventum/templates/manage/customize_listing.tpl.html */
gettext("Reset");

/* /work/eventum/templates/manage/customize_listing.tpl.html */
gettext("Existing Customizations:");

/* /work/eventum/templates/manage/customize_listing.tpl.html */
gettext("Please select at least one of the customizations.");

/* /work/eventum/templates/manage/customize_listing.tpl.html */
gettext("This action will remove the selected entries.");

/* /work/eventum/templates/manage/customize_listing.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/customize_listing.tpl.html */
gettext("Project");

/* /work/eventum/templates/manage/customize_listing.tpl.html */
gettext("Status");

/* /work/eventum/templates/manage/customize_listing.tpl.html */
gettext("Label");

/* /work/eventum/templates/manage/customize_listing.tpl.html */
gettext("Date Field");

/* /work/eventum/templates/manage/customize_listing.tpl.html */
gettext("No customizations could be found.");

/* /work/eventum/templates/manage/customize_listing.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/customize_listing.tpl.html */
gettext("Delete");

/* /work/eventum/templates/manage/time_tracking.tpl.html */
gettext("Please enter the title of this time tracking category");

/* /work/eventum/templates/manage/time_tracking.tpl.html */
gettext("Manage Time Tracking Categories");

/* /work/eventum/templates/manage/time_tracking.tpl.html */
gettext("An error occurred while trying to add the new time tracking category.");

/* /work/eventum/templates/manage/time_tracking.tpl.html */
gettext("Please enter the title for this new time tracking category.");

/* /work/eventum/templates/manage/time_tracking.tpl.html */
gettext("Thank you, the time tracking category was added successfully.");

/* /work/eventum/templates/manage/time_tracking.tpl.html */
gettext("An error occurred while trying to update the time tracking category information.");

/* /work/eventum/templates/manage/time_tracking.tpl.html */
gettext("Please enter the title for this time tracking category.");

/* /work/eventum/templates/manage/time_tracking.tpl.html */
gettext("Thank you, the time tracking category was updated successfully.");

/* /work/eventum/templates/manage/time_tracking.tpl.html */
gettext("Title:");

/* /work/eventum/templates/manage/time_tracking.tpl.html */
gettext("Update Category");

/* /work/eventum/templates/manage/time_tracking.tpl.html */
gettext("Create Category");

/* /work/eventum/templates/manage/time_tracking.tpl.html */
gettext("Reset");

/* /work/eventum/templates/manage/time_tracking.tpl.html */
gettext("Existing Categories:");

/* /work/eventum/templates/manage/time_tracking.tpl.html */
gettext("Please select at least one of the time tracking categories.");

/* /work/eventum/templates/manage/time_tracking.tpl.html */
gettext("This action will remove the selected entries.");

/* /work/eventum/templates/manage/time_tracking.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/time_tracking.tpl.html */
gettext("Title");

/* /work/eventum/templates/manage/time_tracking.tpl.html */
gettext("update this entry");

/* /work/eventum/templates/manage/time_tracking.tpl.html */
gettext("No time tracking categories could be found.");

/* /work/eventum/templates/manage/time_tracking.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/time_tracking.tpl.html */
gettext("Delete");

/* /work/eventum/templates/manage/time_tracking.tpl.html */
gettext("Note:");

/* /work/eventum/templates/manage/time_tracking.tpl.html */
gettext("'Note Discussion', 'Email Discussion' and 'Telephone Discussion' categories are\n                    required by Eventum and cannot be deleted.");

/* /work/eventum/templates/manage/groups.tpl.html */
gettext("Please enter the name of this group.");

/* /work/eventum/templates/manage/groups.tpl.html */
gettext("Please assign the appropriate projects for this group.");

/* /work/eventum/templates/manage/groups.tpl.html */
gettext("Please assign the appropriate users for this group.");

/* /work/eventum/templates/manage/groups.tpl.html */
gettext("Please assign the manager of this group.");

/* /work/eventum/templates/manage/groups.tpl.html */
gettext("Please select at least one of the groups.");

/* /work/eventum/templates/manage/groups.tpl.html */
gettext("WARNING: This action will remove the selected groups permanently.nPlease click OK to confirm.");

/* /work/eventum/templates/manage/groups.tpl.html */
gettext("Manage Groups");

/* /work/eventum/templates/manage/groups.tpl.html */
gettext("An error occurred while trying to add the new group.");

/* /work/eventum/templates/manage/groups.tpl.html */
gettext("Thank you, the group was added successfully.");

/* /work/eventum/templates/manage/groups.tpl.html */
gettext("An error occurred while trying to update the group information.");

/* /work/eventum/templates/manage/groups.tpl.html */
gettext("Thank you, the group was updated successfully.");

/* /work/eventum/templates/manage/groups.tpl.html */
gettext("Name: *");

/* /work/eventum/templates/manage/groups.tpl.html */
gettext("Description:");

/* /work/eventum/templates/manage/groups.tpl.html */
gettext("Assigned Projects: *");

/* /work/eventum/templates/manage/groups.tpl.html */
gettext("Users: *");

/* /work/eventum/templates/manage/groups.tpl.html */
gettext("Manager: *");

/* /work/eventum/templates/manage/groups.tpl.html */
gettext("-- Select One --");

/* /work/eventum/templates/manage/groups.tpl.html */
gettext("Update Group");

/* /work/eventum/templates/manage/groups.tpl.html */
gettext("Create Group");

/* /work/eventum/templates/manage/groups.tpl.html */
gettext("Reset");

/* /work/eventum/templates/manage/groups.tpl.html */
gettext("Existing Groups");

/* /work/eventum/templates/manage/groups.tpl.html */
gettext("Name");

/* /work/eventum/templates/manage/groups.tpl.html */
gettext("Description");

/* /work/eventum/templates/manage/groups.tpl.html */
gettext("Manager");

/* /work/eventum/templates/manage/groups.tpl.html */
gettext("Projects");

/* /work/eventum/templates/manage/groups.tpl.html */
gettext("No groups could be found.");

/* /work/eventum/templates/manage/groups.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/groups.tpl.html */
gettext("delete");

/* /work/eventum/templates/manage/groups.tpl.html */
gettext("Delete");

/* /work/eventum/templates/manage/releases.tpl.html */
gettext("Please enter the title of this release.");

/* /work/eventum/templates/manage/releases.tpl.html */
gettext("Manage Releases");

/* /work/eventum/templates/manage/releases.tpl.html */
gettext("Current Project:");

/* /work/eventum/templates/manage/releases.tpl.html */
gettext("An error occurred while trying to add the new release.");

/* /work/eventum/templates/manage/releases.tpl.html */
gettext("Please enter the title for this new release.");

/* /work/eventum/templates/manage/releases.tpl.html */
gettext("Thank you, the release was added successfully.");

/* /work/eventum/templates/manage/releases.tpl.html */
gettext("An error occurred while trying to update the release information.");

/* /work/eventum/templates/manage/releases.tpl.html */
gettext("Please enter the title for this release.");

/* /work/eventum/templates/manage/releases.tpl.html */
gettext("Thank you, the release was updated successfully.");

/* /work/eventum/templates/manage/releases.tpl.html */
gettext("Title:");

/* /work/eventum/templates/manage/releases.tpl.html */
gettext("Tentative Date:");

/* /work/eventum/templates/manage/releases.tpl.html */
gettext("Status:");

/* /work/eventum/templates/manage/releases.tpl.html */
gettext("Available - Users may use this release");

/* /work/eventum/templates/manage/releases.tpl.html */
gettext("Unavailable - Users may NOT use this release");

/* /work/eventum/templates/manage/releases.tpl.html */
gettext("Update Release");

/* /work/eventum/templates/manage/releases.tpl.html */
gettext("Create Release");

/* /work/eventum/templates/manage/releases.tpl.html */
gettext("Reset");

/* /work/eventum/templates/manage/releases.tpl.html */
gettext("Existing Releases:");

/* /work/eventum/templates/manage/releases.tpl.html */
gettext("Please select at least one of the releases.");

/* /work/eventum/templates/manage/releases.tpl.html */
gettext("This action will remove the selected entries.");

/* /work/eventum/templates/manage/releases.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/releases.tpl.html */
gettext("Title");

/* /work/eventum/templates/manage/releases.tpl.html */
gettext("Tentative Date");

/* /work/eventum/templates/manage/releases.tpl.html */
gettext("Status");

/* /work/eventum/templates/manage/releases.tpl.html */
gettext("update this entry");

/* /work/eventum/templates/manage/releases.tpl.html */
gettext("No releases could be found.");

/* /work/eventum/templates/manage/releases.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/releases.tpl.html */
gettext("Delete");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("Please enter the title of this status.");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("Please enter the abbreviation of this status.");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("Please enter the rank of this status.");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("Please assign the appropriate projects for this status.");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("Please enter the color of this status.");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("Manage Statuses");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("An error occurred while trying to add the new status.");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("Please enter the title for this new status.");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("Thank you, the status was added successfully.");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("An error occurred while trying to update the status information.");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("Please enter the title for this status.");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("Thank you, the status was updated successfully.");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("Title:");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("Abbreviation:");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("(three letter abbreviation)");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("Rank:");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("Closed Context ?");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("Yes");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("No");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("Assigned Projects:");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("Color:");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("(this color will be used in the issue listing page)");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("Update Status");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("Create Status");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("Reset");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("Existing Statuses:");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("Please select at least one of the statuses.");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("This action will remove the selected entries. This will also update any nissues currently set to this status to a new status 'undefined'.");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("Rank");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("Abbreviation");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("Title");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("Projects");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("Color");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("update this entry");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("No statuses could be found.");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/statuses.tpl.html */
gettext("Delete");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("Field");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("Operator");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("Value");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("Value");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("Manage Reminder Conditions");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("view reminder details");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("Reminder");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("view reminder action details");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("Action");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("An error occurred while trying to add the new condition.");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("Please enter the title for this new condition.");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("Thank you, the condition was added successfully.");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("An error occurred while trying to update the condition information.");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("Please enter the title for this condition.");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("Thank you, the condition was updated successfully.");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("Field:");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("Please choose an option");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("Operator:");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("Please choose an option");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("Value:");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("Please choose an option");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("Please choose a field");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("or");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("(in hours please)");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("Update Condition");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("Add Condition");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("Reset");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("Existing Conditions:");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("Back to Reminder Action List");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("Please select at least one of the conditions.");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("This action will remove the selected entries.");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("Field");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("Operator");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("Value");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("update this entry");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("No conditions could be found.");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("Delete");

/* /work/eventum/templates/manage/reminder_conditions.tpl.html */
gettext("Review SQL Query");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Please choose the project to be associated with this email account.");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Please choose the type of email server to be associated with this email account.");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Please enter the hostname for this email account.");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Please enter the port number for this email account.");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Please enter a valid port number for this email account.");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Please enter the port number for this email account.");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Please enter the IMAP folder for this email account.");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Please enter the username for this email account.");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Please enter the password for this email account.");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Manage Email Accounts");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("An error occurred while trying to add the new account.");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Thank you, the email account was added successfully.");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("An error occurred while trying to update the account information.");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Thank you, the account was updated successfully.");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Associated Project:");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Type:");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("IMAP");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("IMAP over SSL");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("IMAP over SSL (self-signed)");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("IMAP, no TLS");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("IMAP, with TLS");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("IMAP, with TLS (self-signed)");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("POP3");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("POP3 over SSL");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("POP3 over SSL (self-signed)");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("POP3, no TLS");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("POP3, with TLS");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("POP3, with TLS (self-signed)");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Hostname:");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Port:");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("(Tip: port defaults are 110 for POP3 servers and 143 for IMAP ones)");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("IMAP Folder:");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("(default folder is INBOX)");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Username:");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Password:");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Advanced Options:");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Only Download Unread Messages");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Leave Copy of Messages On Server");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Use account for non-subject based email/note/draft routing.\n                    <b> Note: </b>If you check this, you cannot leave a copy of messages on the server.</a>");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Test Settings");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Update Account");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Create Account");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Reset");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Existing Accounts:");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Please select at least one of the accounts.");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("This action will remove the selected entries.");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Associated Project");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Hostname");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Type");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Port");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Username");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Mailbox");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Auto-Creation of Issues");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("update this entry");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("No email accounts could be found.");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/email_accounts.tpl.html */
gettext("Delete");

/* /work/eventum/templates/manage/faq.tpl.html */
gettext("Please choose the project for this FAQ entry.");

/* /work/eventum/templates/manage/faq.tpl.html */
gettext("Please assign the appropriate support levels for this FAQ entry.");

/* /work/eventum/templates/manage/faq.tpl.html */
gettext("Please enter the rank of this FAQ entry.");

/* /work/eventum/templates/manage/faq.tpl.html */
gettext("Please enter a number for the rank of this FAQ entry.");

/* /work/eventum/templates/manage/faq.tpl.html */
gettext("Please enter the title of this FAQ entry.");

/* /work/eventum/templates/manage/faq.tpl.html */
gettext("Manage Internal FAQ");

/* /work/eventum/templates/manage/faq.tpl.html */
gettext("An error occurred while trying to add the FAQ entry.");

/* /work/eventum/templates/manage/faq.tpl.html */
gettext("Please enter the title for this FAQ entry.");

/* /work/eventum/templates/manage/faq.tpl.html */
gettext("Please enter the message for this FAQ entry.");

/* /work/eventum/templates/manage/faq.tpl.html */
gettext("Thank you, the FAQ entry was added successfully.");

/* /work/eventum/templates/manage/faq.tpl.html */
gettext("An error occurred while trying to update the FAQ entry information.");

/* /work/eventum/templates/manage/faq.tpl.html */
gettext("Please enter the title for this FAQ entry.");

/* /work/eventum/templates/manage/faq.tpl.html */
gettext("Please enter the message for this FAQ entry.");

/* /work/eventum/templates/manage/faq.tpl.html */
gettext("Thank you, the FAQ entry was updated successfully.");

/* /work/eventum/templates/manage/faq.tpl.html */
gettext("Project:");

/* /work/eventum/templates/manage/faq.tpl.html */
gettext("Please choose an option");

/* /work/eventum/templates/manage/faq.tpl.html */
gettext("Assigned Support");

/* /work/eventum/templates/manage/faq.tpl.html */
gettext("Levels:");

/* /work/eventum/templates/manage/faq.tpl.html */
gettext("Rank:");

/* /work/eventum/templates/manage/faq.tpl.html */
gettext("Title:");

/* /work/eventum/templates/manage/faq.tpl.html */
gettext("Message:");

/* /work/eventum/templates/manage/faq.tpl.html */
gettext("Update FAQ Entry");

/* /work/eventum/templates/manage/faq.tpl.html */
gettext("Create FAQ Entry");

/* /work/eventum/templates/manage/faq.tpl.html */
gettext("Reset");

/* /work/eventum/templates/manage/faq.tpl.html */
gettext("Existing Internal FAQ Entries:");

/* /work/eventum/templates/manage/faq.tpl.html */
gettext("Please select at least one of the FAQ entries.");

/* /work/eventum/templates/manage/faq.tpl.html */
gettext("This action will permanently remove the selected FAQ entries.");

/* /work/eventum/templates/manage/faq.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/faq.tpl.html */
gettext("Rank");

/* /work/eventum/templates/manage/faq.tpl.html */
gettext("Title");

/* /work/eventum/templates/manage/faq.tpl.html */
gettext("Support Levels");

/* /work/eventum/templates/manage/faq.tpl.html */
gettext("update this entry");

/* /work/eventum/templates/manage/faq.tpl.html */
gettext("No FAQ entries could be found.");

/* /work/eventum/templates/manage/faq.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/faq.tpl.html */
gettext("Delete");

/* /work/eventum/templates/manage/account_managers.tpl.html */
gettext("Manage Customer Account Managers");

/* /work/eventum/templates/manage/account_managers.tpl.html */
gettext("An error occurred while trying to add the new account manager.");

/* /work/eventum/templates/manage/account_managers.tpl.html */
gettext("Thank you, the account manager was added successfully.");

/* /work/eventum/templates/manage/account_managers.tpl.html */
gettext("An error occurred while trying to update the account manager information.");

/* /work/eventum/templates/manage/account_managers.tpl.html */
gettext("Thank you, the account manager was updated successfully.");

/* /work/eventum/templates/manage/account_managers.tpl.html */
gettext("Project:");

/* /work/eventum/templates/manage/account_managers.tpl.html */
gettext("Please choose an option");

/* /work/eventum/templates/manage/account_managers.tpl.html */
gettext("Customer:");

/* /work/eventum/templates/manage/account_managers.tpl.html */
gettext("Account Manager:");

/* /work/eventum/templates/manage/account_managers.tpl.html */
gettext("Type:");

/* /work/eventum/templates/manage/account_managers.tpl.html */
gettext("Primary Technical Account Manager");

/* /work/eventum/templates/manage/account_managers.tpl.html */
gettext("Backup Technical Account Manager");

/* /work/eventum/templates/manage/account_managers.tpl.html */
gettext("Update Account Manager");

/* /work/eventum/templates/manage/account_managers.tpl.html */
gettext("Create Account Manager");

/* /work/eventum/templates/manage/account_managers.tpl.html */
gettext("Reset");

/* /work/eventum/templates/manage/account_managers.tpl.html */
gettext("Existing Customer Account Managers:");

/* /work/eventum/templates/manage/account_managers.tpl.html */
gettext("Please select at least one of the account managers.");

/* /work/eventum/templates/manage/account_managers.tpl.html */
gettext("This action will remove the selected account managers.");

/* /work/eventum/templates/manage/account_managers.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/account_managers.tpl.html */
gettext("Customer");

/* /work/eventum/templates/manage/account_managers.tpl.html */
gettext("Account Manager");

/* /work/eventum/templates/manage/account_managers.tpl.html */
gettext("Type");

/* /work/eventum/templates/manage/account_managers.tpl.html */
gettext("update this entry");

/* /work/eventum/templates/manage/account_managers.tpl.html */
gettext("No account managers could be found.");

/* /work/eventum/templates/manage/account_managers.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/account_managers.tpl.html */
gettext("Delete");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Please enter the sender address that will be used for all outgoing notification emails.");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Please enter the SMTP server hostname.");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Please enter the SMTP server port number.");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Please indicate whether the SMTP server requires authentication or not.");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Please enter the SMTP server username.");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Please enter the SMTP server password.");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Please enter the email address of where copies of outgoing emails should be sent to.");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Please choose whether the system should allow visitors to signup for new accounts or not.");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Please select the assigned projects for users that create their own accounts.");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Please enter the email address prefix for the email routing interface.");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Please enter the email address hostname for the email routing interface.");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Please choose whether the SCM integration feature should be enabled or not.");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Please enter the checkout page URL for your SCM integration tool.");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Please enter the diff page URL for your SCM integration tool.");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Please choose whether the email integration feature should be enabled or not.");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Please choose whether the daily tips feature should be enabled or not.");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("General Setup");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("ERROR: The system doesn't have the appropriate permissions to\n                    create the configuration file in the setup directory");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Please contact your local system\n                    administrator and ask for write privileges on the provided path.");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("ERROR: The system doesn't have the appropriate permissions to\n                    update the configuration file in the setup directory");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Please contact your local system\n                    administrator and ask for write privileges on the provided filename.");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Thank you, the setup information was saved successfully.");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Tool Caption:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("SMTP (Outgoing Email) Settings:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Sender:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Hostname:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Port:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Requires Authentication?");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Yes");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("No");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Username:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Password:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Save a Copy of Every Outgoing Issue Notification Email");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Email Address to Send Saved Messages:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Open Account Signup:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Enabled");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Disabled");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Assigned Projects:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Assigned Role:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Subject Based Routing:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Enabled");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Disabled");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("If enabled, Eventum will look in the subject line of incoming notes/emails to determine which issue they should be associated with.");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Email Recipient Type Flag:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Recipient Type Flag:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("(This will be included in the From address of all emails sent by Eventum)");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Before Sender Name");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("After Sender Name");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Email Routing Interface:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Enabled");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Disabled");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Email Address Prefix:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("(i.e. <b>issue_</b>51@example.com)");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Address Hostname:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("(i.e. issue_51@<b>example.com</b>)");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Host Alias:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("(Alternate domains that point to 'Address Hostname')");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Warn Users Whether They Can Send Emails to Issue:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Yes");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("No");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Note Recipient Type Flag:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Recipient Type Flag:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("(This will be included in the From address of all notes sent by Eventum)");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Before Sender Name");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("After Sender Name");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Internal Note Routing Interface:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Enabled");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Disabled");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Note Address Prefix:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("(i.e. <b>note_</b>51@example.com)");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Address Hostname:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("(i.e. note_51@<b>example.com</b>)");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Email Draft Interface:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Enabled");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Disabled");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Draft Address Prefix:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("(i.e. <b>draft_</b>51@example.com)");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Address Hostname:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("(i.e. draft_51@<b>example.com</b>)");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("SCM <br />Integration:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Enabled");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Disabled");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Checkout Page:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Diff Page:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Email Integration Feature:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Enabled");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Disabled");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Daily Tips:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Enabled");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Disabled");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Email Spell Checker:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Enabled");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Disabled");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("(requires <a target=\"_aspell\" class=\"link\" href=\"http://aspell.sourceforge.net/\">aspell</a> installed in your server)");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("IRC Notifications:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Enabled");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Disabled");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Allow Un-Assigned Issues?");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Yes");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("No");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Default Options for Notifications:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Issues are Updated");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Issues are Closed");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Emails are Associated");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Files are Attached");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Email Reminder System Status Information:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Enabled");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Disabled");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Email Addresses To Send Information To:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("(separate multiple addresses with commas)");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Email Error Logging System:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Enabled");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Disabled");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Email Addresses To Send Errors To:");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("(separate multiple addresses with commas)");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Update Setup");

/* /work/eventum/templates/manage/general.tpl.html */
gettext("Reset");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("Please enter the email of this user.");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("Please enter a valid email address.");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("Please enter a password of at least 6 characters.");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("Please enter a password of at least 6 characters.");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("Please enter the full name of this user.");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("Please assign the appropriate projects for this user.");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("Manage Users");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("An error occurred while trying to add the new user.");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("Thank you, the user was added successfully.");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("An error occurred while trying to update the user information.");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("Thank you, the user was updated successfully.");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("Email Address");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("Password");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("leave empty to keep the current password");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("Full Name");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("Assigned Projects and Roles");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("Customer");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("Update User");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("Create User");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("Reset");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("Existing Users");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("You cannot change the status of the only active user left in the system.");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("You cannot inactivate all of the users in the system.");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("Please select at least one of the users.");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("This action will change the status of the selected users.");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("Full Name");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("Role");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("Email Address");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("Status");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("Group");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("update this entry");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("send email to");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("No users could be found.");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("Update Status");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("Active");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("Inactive");

/* /work/eventum/templates/manage/users.tpl.html */
gettext("Show Customers");

/* /work/eventum/templates/manage/categories.tpl.html */
gettext("Please enter the title of this category");

/* /work/eventum/templates/manage/categories.tpl.html */
gettext("Manage Categories");

/* /work/eventum/templates/manage/categories.tpl.html */
gettext("Current Project");

/* /work/eventum/templates/manage/categories.tpl.html */
gettext("An error occurred while trying to add the new category.");

/* /work/eventum/templates/manage/categories.tpl.html */
gettext("Please enter the title for this new category.");

/* /work/eventum/templates/manage/categories.tpl.html */
gettext("Thank you, the category was added successfully.");

/* /work/eventum/templates/manage/categories.tpl.html */
gettext("An error occurred while trying to update the category information.");

/* /work/eventum/templates/manage/categories.tpl.html */
gettext("Please enter the title for this category.");

/* /work/eventum/templates/manage/categories.tpl.html */
gettext("Thank you, the category was updated successfully.");

/* /work/eventum/templates/manage/categories.tpl.html */
gettext("Update Category");

/* /work/eventum/templates/manage/categories.tpl.html */
gettext("Create Category");

/* /work/eventum/templates/manage/categories.tpl.html */
gettext("Reset");

/* /work/eventum/templates/manage/categories.tpl.html */
gettext("Existing Categories:");

/* /work/eventum/templates/manage/categories.tpl.html */
gettext("Please select at least one of the categories.");

/* /work/eventum/templates/manage/categories.tpl.html */
gettext("This action will remove the selected entries.");

/* /work/eventum/templates/manage/categories.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/categories.tpl.html */
gettext("Title");

/* /work/eventum/templates/manage/categories.tpl.html */
gettext("update this entry");

/* /work/eventum/templates/manage/categories.tpl.html */
gettext("No categories could be found.");

/* /work/eventum/templates/manage/categories.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/categories.tpl.html */
gettext("Delete");

/* /work/eventum/templates/manage/priorities.tpl.html */
gettext("Please enter the title of this priority");

/* /work/eventum/templates/manage/priorities.tpl.html */
gettext("Please enter the rank of this priority");

/* /work/eventum/templates/manage/priorities.tpl.html */
gettext("Manage Priorities");

/* /work/eventum/templates/manage/priorities.tpl.html */
gettext("Current Project");

/* /work/eventum/templates/manage/priorities.tpl.html */
gettext("An error occurred while trying to add the new priority.");

/* /work/eventum/templates/manage/priorities.tpl.html */
gettext("Please enter the title for this new priority.");

/* /work/eventum/templates/manage/priorities.tpl.html */
gettext("Thank you, the priority was added successfully.");

/* /work/eventum/templates/manage/priorities.tpl.html */
gettext("An error occurred while trying to update the priority information.");

/* /work/eventum/templates/manage/priorities.tpl.html */
gettext("Please enter the title for this priority.");

/* /work/eventum/templates/manage/priorities.tpl.html */
gettext("Thank you, the priority was updated successfully.");

/* /work/eventum/templates/manage/priorities.tpl.html */
gettext("Title");

/* /work/eventum/templates/manage/priorities.tpl.html */
gettext("Rank");

/* /work/eventum/templates/manage/priorities.tpl.html */
gettext("Update Priority");

/* /work/eventum/templates/manage/priorities.tpl.html */
gettext("Create Priority");

/* /work/eventum/templates/manage/priorities.tpl.html */
gettext("Reset");

/* /work/eventum/templates/manage/priorities.tpl.html */
gettext("Existing Priorities");

/* /work/eventum/templates/manage/priorities.tpl.html */
gettext("Please select at least one of the priorities.");

/* /work/eventum/templates/manage/priorities.tpl.html */
gettext("This action will remove the selected entries.");

/* /work/eventum/templates/manage/priorities.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/priorities.tpl.html */
gettext("Rank");

/* /work/eventum/templates/manage/priorities.tpl.html */
gettext("Title");

/* /work/eventum/templates/manage/priorities.tpl.html */
gettext("update this entry");

/* /work/eventum/templates/manage/priorities.tpl.html */
gettext("No priorities could be found.");

/* /work/eventum/templates/manage/priorities.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/priorities.tpl.html */
gettext("Delete");

/* /work/eventum/templates/manage/customer_notes.tpl.html */
gettext("Please choose the customer for this new note.");

/* /work/eventum/templates/manage/customer_notes.tpl.html */
gettext("Manage Customer Quick Notes");

/* /work/eventum/templates/manage/customer_notes.tpl.html */
gettext("An error occurred while trying to add the new note.");

/* /work/eventum/templates/manage/customer_notes.tpl.html */
gettext("Thank you, the note was added successfully.");

/* /work/eventum/templates/manage/customer_notes.tpl.html */
gettext("An error occurred while trying to update the note.");

/* /work/eventum/templates/manage/customer_notes.tpl.html */
gettext("Thank you, the note was updated successfully.");

/* /work/eventum/templates/manage/customer_notes.tpl.html */
gettext("An error occurred while trying to delete the note.");

/* /work/eventum/templates/manage/customer_notes.tpl.html */
gettext("Thank you, the note was deleted successfully.");

/* /work/eventum/templates/manage/customer_notes.tpl.html */
gettext("Project");

/* /work/eventum/templates/manage/customer_notes.tpl.html */
gettext("Please choose an option");

/* /work/eventum/templates/manage/customer_notes.tpl.html */
gettext("Customer");

/* /work/eventum/templates/manage/customer_notes.tpl.html */
gettext("Please choose a customer");

/* /work/eventum/templates/manage/customer_notes.tpl.html */
gettext("Note");

/* /work/eventum/templates/manage/customer_notes.tpl.html */
gettext("Update Note");

/* /work/eventum/templates/manage/customer_notes.tpl.html */
gettext("Create Note");

/* /work/eventum/templates/manage/customer_notes.tpl.html */
gettext("Reset");

/* /work/eventum/templates/manage/customer_notes.tpl.html */
gettext("Existing Customer Quick Notes");

/* /work/eventum/templates/manage/customer_notes.tpl.html */
gettext("Please select at least one of the notes.");

/* /work/eventum/templates/manage/customer_notes.tpl.html */
gettext("This action will permanently remove the selected entries.");

/* /work/eventum/templates/manage/customer_notes.tpl.html */
gettext("Customer");

/* /work/eventum/templates/manage/customer_notes.tpl.html */
gettext("Note");

/* /work/eventum/templates/manage/customer_notes.tpl.html */
gettext("No notes could be found.");

/* /work/eventum/templates/manage/customer_notes.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/customer_notes.tpl.html */
gettext("Delete");

/* /work/eventum/templates/manage/field_display.tpl.html */
gettext("This page can only be accessed in relation to a project. Please go to the project page and choose\n\"Edit Fields to Display\" to access this page.");

/* /work/eventum/templates/manage/field_display.tpl.html */
gettext("Manage Projects");

/* /work/eventum/templates/manage/field_display.tpl.html */
gettext("Edit Fields to Display");

/* /work/eventum/templates/manage/field_display.tpl.html */
gettext("An error occurred while trying to update field display settings.");

/* /work/eventum/templates/manage/field_display.tpl.html */
gettext("Thank you, field display settings were updated successfully.");

/* /work/eventum/templates/manage/field_display.tpl.html */
gettext("Field");

/* /work/eventum/templates/manage/field_display.tpl.html */
gettext("Set Display Preferences");

/* /work/eventum/templates/manage/field_display.tpl.html */
gettext("Reset");

/* /work/eventum/templates/manage/column_display.tpl.html */
gettext("This page can only be accessed in relation to a project. Please go to the project page and choose\n\"Edit Fields to Display\" to access this page.");

/* /work/eventum/templates/manage/column_display.tpl.html */
gettext("Manage Projects");

/* /work/eventum/templates/manage/column_display.tpl.html */
gettext("Manage Columns to Display");

/* /work/eventum/templates/manage/column_display.tpl.html */
gettext("Current Project");

/* /work/eventum/templates/manage/column_display.tpl.html */
gettext("An error occurred while trying to save columns to display.");

/* /work/eventum/templates/manage/column_display.tpl.html */
gettext("Thank you, columns to display was saved successfully.");

/* /work/eventum/templates/manage/column_display.tpl.html */
gettext("Column Name");

/* /work/eventum/templates/manage/column_display.tpl.html */
gettext("Minimum Role");

/* /work/eventum/templates/manage/column_display.tpl.html */
gettext("Order");

/* /work/eventum/templates/manage/column_display.tpl.html */
gettext("Save");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Please enter the title of this custom field.");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Please assign the appropriate projects for this custom field.");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("WARNING: You have removed project(s)");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("from the list");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("of associated projects. This will remove all data for this field from the selected project(s).");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Do you want to continue?");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Please enter the new value for the combo box.");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("The specified value already exists in the list of options.");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Please enter the updated value.");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Please select an option from the list.");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Please select an option from the list.");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("enter a new option above");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Manage Custom Fields");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("An error occurred while trying to add the new custom field.");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Thank you, the custom field was added successfully.");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("An error occurred while trying to update the custom field information.");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Thank you, the custom field was updated successfully.");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Title");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Short Description");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("it will show up by the side of the field");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Assigned Projects");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Target Forms");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Report Form");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Required Field");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Anonymous Form");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Required Field");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Display on List Issues Page");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Yes");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("No");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Field Type");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Text Input");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Textarea");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Combo Box");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Multiple Combo Box");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Date");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Field Options");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Set available options");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Add");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Update Value");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("OR");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Choose Custom Field Backend");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Please select a backend");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Please select a backend");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("enter a new option above");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Edit Option");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Remove");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Minimum Role");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Rank");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Update Custom Field");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Create Custom Field");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Reset");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Existing Custom Fields");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Please select at least one of the custom fields.");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("This action will permanently remove the selected custom fields.");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("delete");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Rank");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Title");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Assigned Projects");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Min. Role");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Type");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Options");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("move field down");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("move field up");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("update this entry");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Combo Box");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Multiple Combo Box");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Textarea");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Date");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Text Input");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("No custom fields could be found.");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/custom_fields.tpl.html */
gettext("Delete");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("Manage Issue Reminders");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("Updating Reminder");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("Creating New Reminder");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("An error occurred while trying to add the new reminder.");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("Please enter the title for this new reminder.");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("Thank you, the reminder was added successfully.");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("An error occurred while trying to update the reminder information.");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("Please enter the title for this reminder.");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("Thank you, the reminder was updated successfully.");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("Please choose a project that will be associated with this reminder.");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("Please enter the title for this reminder.");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("Please enter the rank for this reminder.");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("Please choose the support levels that will be associated with this reminder.");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("Please choose the customers that will be associated with this reminder.");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("Please enter the issue IDs that will be associated with this reminder.");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("Please choose the priorities that will be associated with this reminder.");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("Please choose an option");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("Title");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("Rank");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("Reminder Type");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("By Support Level");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("By Customer");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("By Issue ID");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("All Issues");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("Also Filter By Issue Priorities");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("Skip Weekends");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("Yes");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("No");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("If yes, this reminder will not activate on weekends and time will not accumulate on the weekends.");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("Update Reminder");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("Create Reminder");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("Reset");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("Existing Issue Reminders");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("Please select at least one of the reminders.");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("This action will remove the selected entries.");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("ID");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("Rank");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("Title");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("Project");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("Type");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("Issue Priorities");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("Details");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("update this entry");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("All Issues");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("By Support Level");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("By Customer");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("By Issue ID");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("No reminders could be found.");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/reminders.tpl.html */
gettext("Delete");

/* /work/eventum/templates/manage/link_filters.tpl.html */
gettext("Please enter a pattern.");

/* /work/eventum/templates/manage/link_filters.tpl.html */
gettext("Please enter a replacement value.");

/* /work/eventum/templates/manage/link_filters.tpl.html */
gettext("Please select projects this link filter should be active for.");

/* /work/eventum/templates/manage/link_filters.tpl.html */
gettext("Please select the minimum user role that should be able to see this link filter.");

/* /work/eventum/templates/manage/link_filters.tpl.html */
gettext("Please select at least one link filter.");

/* /work/eventum/templates/manage/link_filters.tpl.html */
gettext("WARNING: This action will remove the selected link filters permanently.");

/* /work/eventum/templates/manage/link_filters.tpl.html */
gettext("Please click OK to confirm.");

/* /work/eventum/templates/manage/link_filters.tpl.html */
gettext("Manage Link Filters");

/* /work/eventum/templates/manage/link_filters.tpl.html */
gettext("An error occurred while trying to add the new link filter.");

/* /work/eventum/templates/manage/link_filters.tpl.html */
gettext("Thank you, the link filter was added successfully.");

/* /work/eventum/templates/manage/link_filters.tpl.html */
gettext("An error occurred while trying to update the link filter.");

/* /work/eventum/templates/manage/link_filters.tpl.html */
gettext("Thank you, the link filter was updated successfully.");

/* /work/eventum/templates/manage/link_filters.tpl.html */
gettext("An error occurred while trying to delete the link filter.");

/* /work/eventum/templates/manage/link_filters.tpl.html */
gettext("Thank you, the link filter was deleted successfully.");

/* /work/eventum/templates/manage/link_filters.tpl.html */
gettext("Pattern");

/* /work/eventum/templates/manage/link_filters.tpl.html */
gettext("Replacement");

/* /work/eventum/templates/manage/link_filters.tpl.html */
gettext("Description");

/* /work/eventum/templates/manage/link_filters.tpl.html */
gettext("Assigned Projects");

/* /work/eventum/templates/manage/link_filters.tpl.html */
gettext("Minimum User Role");

/* /work/eventum/templates/manage/link_filters.tpl.html */
gettext("Update Link Filter");

/* /work/eventum/templates/manage/link_filters.tpl.html */
gettext("Create Link Filter");

/* /work/eventum/templates/manage/link_filters.tpl.html */
gettext("Reset");

/* /work/eventum/templates/manage/link_filters.tpl.html */
gettext("Existing Link Filters");

/* /work/eventum/templates/manage/link_filters.tpl.html */
gettext("Pattern");

/* /work/eventum/templates/manage/link_filters.tpl.html */
gettext("Replacement");

/* /work/eventum/templates/manage/link_filters.tpl.html */
gettext("Description");

/* /work/eventum/templates/manage/link_filters.tpl.html */
gettext("Minimum Role");

/* /work/eventum/templates/manage/link_filters.tpl.html */
gettext("Projects");

/* /work/eventum/templates/manage/link_filters.tpl.html */
gettext("update this entry");

/* /work/eventum/templates/manage/link_filters.tpl.html */
gettext("No link filters could be found.");

/* /work/eventum/templates/manage/link_filters.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/link_filters.tpl.html */
gettext("Delete");

/* /work/eventum/templates/manage/news.tpl.html */
gettext("Please enter the title of this news entry.");

/* /work/eventum/templates/manage/news.tpl.html */
gettext("Please assign the appropriate projects for this news entry.");

/* /work/eventum/templates/manage/news.tpl.html */
gettext("Manage News");

/* /work/eventum/templates/manage/news.tpl.html */
gettext("An error occurred while trying to add the news entry.");

/* /work/eventum/templates/manage/news.tpl.html */
gettext("Please enter the title for this news entry.");

/* /work/eventum/templates/manage/news.tpl.html */
gettext("Please enter the message for this news entry.");

/* /work/eventum/templates/manage/news.tpl.html */
gettext("Thank you, the news entry was added successfully.");

/* /work/eventum/templates/manage/news.tpl.html */
gettext("An error occurred while trying to update the news entry information.");

/* /work/eventum/templates/manage/news.tpl.html */
gettext("Please enter the title for this news entry.");

/* /work/eventum/templates/manage/news.tpl.html */
gettext("Please enter the message for this news entry.");

/* /work/eventum/templates/manage/news.tpl.html */
gettext("Thank you, the news entry was updated successfully.");

/* /work/eventum/templates/manage/news.tpl.html */
gettext("Assigned Projects");

/* /work/eventum/templates/manage/news.tpl.html */
gettext("Status");

/* /work/eventum/templates/manage/news.tpl.html */
gettext("Active");

/* /work/eventum/templates/manage/news.tpl.html */
gettext("Inactive");

/* /work/eventum/templates/manage/news.tpl.html */
gettext("Title");

/* /work/eventum/templates/manage/news.tpl.html */
gettext("Message");

/* /work/eventum/templates/manage/news.tpl.html */
gettext("Update News Entry");

/* /work/eventum/templates/manage/news.tpl.html */
gettext("Create News Entry");

/* /work/eventum/templates/manage/news.tpl.html */
gettext("Reset");

/* /work/eventum/templates/manage/news.tpl.html */
gettext("Existing News Entries");

/* /work/eventum/templates/manage/news.tpl.html */
gettext("Please select at least one of the news entries.");

/* /work/eventum/templates/manage/news.tpl.html */
gettext("This action will permanently remove the selected news entries.");

/* /work/eventum/templates/manage/news.tpl.html */
gettext("Title");

/* /work/eventum/templates/manage/news.tpl.html */
gettext("Projects");

/* /work/eventum/templates/manage/news.tpl.html */
gettext("Status");

/* /work/eventum/templates/manage/news.tpl.html */
gettext("update this entry");

/* /work/eventum/templates/manage/news.tpl.html */
gettext("No news entries could be found.");

/* /work/eventum/templates/manage/news.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/news.tpl.html */
gettext("Delete");

/* /work/eventum/templates/manage/manage.tpl.html */
gettext("Sorry, but you do not have the required permission level to access this screen.");

/* /work/eventum/templates/manage/manage.tpl.html */
gettext("Go Back");

/* /work/eventum/templates/manage/manage.tpl.html */
gettext("Configuration");

/* /work/eventum/templates/manage/manage.tpl.html */
gettext("General Setup");

/* /work/eventum/templates/manage/manage.tpl.html */
gettext("Manage Email Accounts");

/* /work/eventum/templates/manage/manage.tpl.html */
gettext("Manage Custom Fields");

/* /work/eventum/templates/manage/manage.tpl.html */
gettext("Customize Issue Listing Screen");

/* /work/eventum/templates/manage/manage.tpl.html */
gettext("Areas");

/* /work/eventum/templates/manage/manage.tpl.html */
gettext("Manage Internal FAQ");

/* /work/eventum/templates/manage/manage.tpl.html */
gettext("Manage Round Robin Assignments");

/* /work/eventum/templates/manage/manage.tpl.html */
gettext("Manage News");

/* /work/eventum/templates/manage/manage.tpl.html */
gettext("Manage Issue Reminders");

/* /work/eventum/templates/manage/manage.tpl.html */
gettext("Manage Customer Account Managers");

/* /work/eventum/templates/manage/manage.tpl.html */
gettext("Manage Customer Quick Notes");

/* /work/eventum/templates/manage/manage.tpl.html */
gettext("Manage Statuses");

/* /work/eventum/templates/manage/manage.tpl.html */
gettext("Manage Projects");

/* /work/eventum/templates/manage/manage.tpl.html */
gettext("Add / Edit Releases");

/* /work/eventum/templates/manage/manage.tpl.html */
gettext("Add / Edit Categories");

/* /work/eventum/templates/manage/manage.tpl.html */
gettext("Add / Edit Priorities");

/* /work/eventum/templates/manage/manage.tpl.html */
gettext("Add / Edit Phone Support Categories");

/* /work/eventum/templates/manage/manage.tpl.html */
gettext("Anonymous Reporting Options");

/* /work/eventum/templates/manage/manage.tpl.html */
gettext("Edit Fields to Display");

/* /work/eventum/templates/manage/manage.tpl.html */
gettext("Edit Columns to Display");

/* /work/eventum/templates/manage/manage.tpl.html */
gettext("Manage Users");

/* /work/eventum/templates/manage/manage.tpl.html */
gettext("Manage Groups");

/* /work/eventum/templates/manage/manage.tpl.html */
gettext("Manage Time Tracking Categories");

/* /work/eventum/templates/manage/manage.tpl.html */
gettext("Manage Issue Resolutions");

/* /work/eventum/templates/manage/manage.tpl.html */
gettext("Manage Canned Email Responses");

/* /work/eventum/templates/manage/manage.tpl.html */
gettext("Manage Link Filters");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Please enter the title of this project.");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Please assign the users for this project.");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Please assign the statuses for this project.");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Please choose the initial status from one of the assigned statuses of this project.");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Please enter a valid outgoing sender address for this project.");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Manage Projects");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("An error occurred while trying to add the new project.");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Please enter the title for this new project.");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Thank you, the project was added successfully.");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("An error occurred while trying to update the project information.");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Please enter the title for this project.");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Thank you, the project was updated successfully.");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Title");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Status");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Active");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Archived");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Customer Integration Backend");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("No Customer Integration");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Workflow Backend");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("No Workflow Management");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Project Lead");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Users");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Statuses");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Initial Status for New Issues");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Outgoing Email Sender Name");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Outgoing Email Sender Address");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Remote Invocation");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Enabled");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Disabled");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Segregate Reporters");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Yes");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("No");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Update Project");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Create Project");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Reset");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Existing Projects");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("You cannot remove all of the projects in the system.");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Please select at least one of the projects.");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("WARNING: This action will remove the selected projects permanently.");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("It will remove all of its associated entries as well (issues, notes, attachments,netc), so please click OK to confirm.");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Title");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Project Lead");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Status");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Actions");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Edit Releases");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Edit Categories");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Edit Priorities");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Edit Phone Support Categories");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Anonymous Reporting");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Edit Fields to Display");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Edit Columns to Display");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("No projects could be found.");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/projects.tpl.html */
gettext("Delete");

/* /work/eventum/templates/manage/phone_categories.tpl.html */
gettext("Please enter the title of this category");

/* /work/eventum/templates/manage/phone_categories.tpl.html */
gettext("Manage Phone Support Categories");

/* /work/eventum/templates/manage/phone_categories.tpl.html */
gettext("Current Project");

/* /work/eventum/templates/manage/phone_categories.tpl.html */
gettext("An error occurred while trying to add the new category.");

/* /work/eventum/templates/manage/phone_categories.tpl.html */
gettext("Please enter the title for this new category.");

/* /work/eventum/templates/manage/phone_categories.tpl.html */
gettext("Thank you, the category was added successfully.");

/* /work/eventum/templates/manage/phone_categories.tpl.html */
gettext("An error occurred while trying to update the category information.");

/* /work/eventum/templates/manage/phone_categories.tpl.html */
gettext("Please enter the title for this category.");

/* /work/eventum/templates/manage/phone_categories.tpl.html */
gettext("Thank you, the category was updated successfully.");

/* /work/eventum/templates/manage/phone_categories.tpl.html */
gettext("Title");

/* /work/eventum/templates/manage/phone_categories.tpl.html */
gettext("Update Category");

/* /work/eventum/templates/manage/phone_categories.tpl.html */
gettext("Create Category");

/* /work/eventum/templates/manage/phone_categories.tpl.html */
gettext("Reset");

/* /work/eventum/templates/manage/phone_categories.tpl.html */
gettext("Existing Phone Support Categories");

/* /work/eventum/templates/manage/phone_categories.tpl.html */
gettext("Please select at least one of the categories.");

/* /work/eventum/templates/manage/phone_categories.tpl.html */
gettext("This action will remove the selected entries.");

/* /work/eventum/templates/manage/phone_categories.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/phone_categories.tpl.html */
gettext("Title");

/* /work/eventum/templates/manage/phone_categories.tpl.html */
gettext("update this entry");

/* /work/eventum/templates/manage/phone_categories.tpl.html */
gettext("No phone support categories could be found.");

/* /work/eventum/templates/manage/phone_categories.tpl.html */
gettext("All");

/* /work/eventum/templates/manage/phone_categories.tpl.html */
gettext("Delete");

/* /work/eventum/templates/attached_emails.tpl.html */
gettext("Please choose which entries need to be removed.");

/* /work/eventum/templates/attached_emails.tpl.html */
gettext("Attached Emails");

/* /work/eventum/templates/attached_emails.tpl.html */
gettext("Remove?");

/* /work/eventum/templates/attached_emails.tpl.html */
gettext("Sender");

/* /work/eventum/templates/attached_emails.tpl.html */
gettext("Subject");

/* /work/eventum/templates/attached_emails.tpl.html */
gettext("Remove Selected");

/* /work/eventum/templates/preferences.tpl.html */
gettext("Please enter your full name.");

/* /work/eventum/templates/preferences.tpl.html */
gettext("Please enter a valid email address.");

/* /work/eventum/templates/preferences.tpl.html */
gettext("Please enter your new password with at least 6 characters.");

/* /work/eventum/templates/preferences.tpl.html */
gettext("The two passwords do not match. Please review your information and try again.");

/* /work/eventum/templates/preferences.tpl.html */
gettext("User Details");

/* /work/eventum/templates/preferences.tpl.html */
gettext("An error occurred while trying to run your query.");

/* /work/eventum/templates/preferences.tpl.html */
gettext("Thank you, your full name was updated successfully.");

/* /work/eventum/templates/preferences.tpl.html */
gettext("Full Name");

/* /work/eventum/templates/preferences.tpl.html */
gettext("Update Full Name");

/* /work/eventum/templates/preferences.tpl.html */
gettext("Reset");

/* /work/eventum/templates/preferences.tpl.html */
gettext("An error occurred while trying to run your query.");

/* /work/eventum/templates/preferences.tpl.html */
gettext("Thank you, your email address was updated successfully.");

/* /work/eventum/templates/preferences.tpl.html */
gettext("Login");

/* /work/eventum/templates/preferences.tpl.html */
gettext("Email Address");

/* /work/eventum/templates/preferences.tpl.html */
gettext("Update Email Address");

/* /work/eventum/templates/preferences.tpl.html */
gettext("Reset");

/* /work/eventum/templates/preferences.tpl.html */
gettext("An error occurred while trying to run your query.");

/* /work/eventum/templates/preferences.tpl.html */
gettext("Thank you, your password was updated successfully.");

/* /work/eventum/templates/preferences.tpl.html */
gettext("Change Password");

/* /work/eventum/templates/preferences.tpl.html */
gettext("New Password");

/* /work/eventum/templates/preferences.tpl.html */
gettext("Confirm New Password");

/* /work/eventum/templates/preferences.tpl.html */
gettext("Update Password");

/* /work/eventum/templates/preferences.tpl.html */
gettext("Reset");

/* /work/eventum/templates/preferences.tpl.html */
gettext("Account Preferences");

/* /work/eventum/templates/preferences.tpl.html */
gettext("An error occurred while trying to run your query.");

/* /work/eventum/templates/preferences.tpl.html */
gettext("Thank you, your account preferences were updated successfully.");

/* /work/eventum/templates/preferences.tpl.html */
gettext("Timezone");

/* /work/eventum/templates/preferences.tpl.html */
gettext("Automatically close confirmation popup windows ?");

/* /work/eventum/templates/preferences.tpl.html */
gettext("Yes");

/* /work/eventum/templates/preferences.tpl.html */
gettext("No");

/* /work/eventum/templates/preferences.tpl.html */
gettext("Receive emails when all issues are created ?");

/* /work/eventum/templates/preferences.tpl.html */
gettext("Yes");

/* /work/eventum/templates/preferences.tpl.html */
gettext("No");

/* /work/eventum/templates/preferences.tpl.html */
gettext("Receive emails when new issues are assigned to you ?");

/* /work/eventum/templates/preferences.tpl.html */
gettext("Yes");

/* /work/eventum/templates/preferences.tpl.html */
gettext("No");

/* /work/eventum/templates/preferences.tpl.html */
gettext("Refresh Rate for Issue Listing Page");

/* /work/eventum/templates/preferences.tpl.html */
gettext("in minutes");

/* /work/eventum/templates/preferences.tpl.html */
gettext("Refresh Rate for Email Listing Page");

/* /work/eventum/templates/preferences.tpl.html */
gettext("in minutes");

/* /work/eventum/templates/preferences.tpl.html */
gettext("Email Signature");

/* /work/eventum/templates/preferences.tpl.html */
gettext("Edit Signature");

/* /work/eventum/templates/preferences.tpl.html */
gettext("Upload New Signature");

/* /work/eventum/templates/preferences.tpl.html */
gettext("Automatically append email signature when composing web based emails");

/* /work/eventum/templates/preferences.tpl.html */
gettext("Automatically append email signature when composing internal notes");

/* /work/eventum/templates/preferences.tpl.html */
gettext("SMS Email Address");

/* /work/eventum/templates/preferences.tpl.html */
gettext("only used for automatic issue reminders");

/* /work/eventum/templates/resize_textarea.tpl.html */
gettext("Widen the field");

/* /work/eventum/templates/resize_textarea.tpl.html */
gettext("Shorten the field");

/* /work/eventum/templates/localized.tpl.html */
gettext("The provided trial account email address could not be\nconfirmed. Please contact the local Technical Support staff for\nfurther assistance.");

/* /work/eventum/templates/notes.tpl.html */
gettext("This action will permanently delete the specified note.");

/* /work/eventum/templates/notes.tpl.html */
gettext("This note will be deleted & converted to an email, one either sent immediately or saved as a draft.");

/* /work/eventum/templates/notes.tpl.html */
gettext("Internal Notes");

/* /work/eventum/templates/notes.tpl.html */
gettext("Reply");

/* /work/eventum/templates/notes.tpl.html */
gettext("Posted Date");

/* /work/eventum/templates/notes.tpl.html */
gettext("User");

/* /work/eventum/templates/notes.tpl.html */
gettext("Title");

/* /work/eventum/templates/notes.tpl.html */
gettext("reply to this note");

/* /work/eventum/templates/notes.tpl.html */
gettext("delete");

/* /work/eventum/templates/notes.tpl.html */
gettext("convert note");

/* /work/eventum/templates/notes.tpl.html */
gettext("No internal notes could be found.");

/* /work/eventum/templates/notes.tpl.html */
gettext("Post Internal Note");

/* /work/eventum/templates/add_time_tracking.tpl.html */
gettext("An error occurred while trying to run your query");

/* /work/eventum/templates/add_time_tracking.tpl.html */
gettext("Thank you, the time tracking entry was added successfully.");

/* /work/eventum/templates/add_time_tracking.tpl.html */
gettext("Continue");

/* /work/eventum/templates/add_time_tracking.tpl.html */
gettext("Please enter the summary for this new time tracking entry.");

/* /work/eventum/templates/add_time_tracking.tpl.html */
gettext("Please choose the time tracking category for this new entry.");

/* /work/eventum/templates/add_time_tracking.tpl.html */
gettext("Please enter integers (or floating point numbers) on the time spent field.");

/* /work/eventum/templates/add_time_tracking.tpl.html */
gettext("Please select a valid date of work.");

/* /work/eventum/templates/add_time_tracking.tpl.html */
gettext("Record Time Worked");

/* /work/eventum/templates/add_time_tracking.tpl.html */
gettext("Summary");

/* /work/eventum/templates/add_time_tracking.tpl.html */
gettext("Category");

/* /work/eventum/templates/add_time_tracking.tpl.html */
gettext("Please choose a category");

/* /work/eventum/templates/add_time_tracking.tpl.html */
gettext("Time Spent");

/* /work/eventum/templates/add_time_tracking.tpl.html */
gettext("in minutes");

/* /work/eventum/templates/add_time_tracking.tpl.html */
gettext("Date of Work");

/* /work/eventum/templates/add_time_tracking.tpl.html */
gettext("Add Time Entry");

/* /work/eventum/templates/top_link.tpl.html */
gettext("Back to Top");

/* /work/eventum/templates/view_email.tpl.html */
gettext("Re-directing the parent window to the issue report page. This window will be closed automatically.");

/* /work/eventum/templates/view_email.tpl.html */
gettext("This message already belongs to that account");

/* /work/eventum/templates/view_email.tpl.html */
gettext("An error occurred while trying to run your query");

/* /work/eventum/templates/view_email.tpl.html */
gettext("Thank you, the email was successfully moved.");

/* /work/eventum/templates/view_email.tpl.html */
gettext("Continue");

/* /work/eventum/templates/view_email.tpl.html */
gettext("View Email Details");

/* /work/eventum/templates/view_email.tpl.html */
gettext("Associated with Issue");

/* /work/eventum/templates/view_email.tpl.html */
gettext("Previous Message");

/* /work/eventum/templates/view_email.tpl.html */
gettext("Next Message");

/* /work/eventum/templates/view_email.tpl.html */
gettext("Received");

/* /work/eventum/templates/view_email.tpl.html */
gettext("From");

/* /work/eventum/templates/view_email.tpl.html */
gettext("To");

/* /work/eventum/templates/view_email.tpl.html */
gettext("sent to notification list");

/* /work/eventum/templates/view_email.tpl.html */
gettext("Cc");

/* /work/eventum/templates/view_email.tpl.html */
gettext("Subject");

/* /work/eventum/templates/view_email.tpl.html */
gettext("Attachments");

/* /work/eventum/templates/view_email.tpl.html */
gettext("Message");

/* /work/eventum/templates/view_email.tpl.html */
gettext("display in fixed width font");

/* /work/eventum/templates/view_email.tpl.html */
gettext("Raw Headers");

/* /work/eventum/templates/view_email.tpl.html */
gettext("Reply");

/* /work/eventum/templates/view_email.tpl.html */
gettext("Close");

/* /work/eventum/templates/view_email.tpl.html */
gettext("Previous Message");

/* /work/eventum/templates/view_email.tpl.html */
gettext("Next Message");

/* /work/eventum/templates/view_email.tpl.html */
gettext("Move Message To");

/* /work/eventum/templates/authorized_replier.tpl.html */
gettext("Please enter a valid email address.");

/* /work/eventum/templates/authorized_replier.tpl.html */
gettext("Authorized Repliers");

/* /work/eventum/templates/authorized_replier.tpl.html */
gettext("An error occurred while trying to insert the authorized replier.");

/* /work/eventum/templates/authorized_replier.tpl.html */
gettext("Users with a role of \"customer\" or below are not allowed to be added to the authorized repliers list.");

/* /work/eventum/templates/authorized_replier.tpl.html */
gettext("Thank you, the authorized replier was inserted successfully.");

/* /work/eventum/templates/authorized_replier.tpl.html */
gettext("Email");

/* /work/eventum/templates/authorized_replier.tpl.html */
gettext("Add Authorized Replier");

/* /work/eventum/templates/authorized_replier.tpl.html */
gettext("Reset");

/* /work/eventum/templates/authorized_replier.tpl.html */
gettext("Existing Authorized Repliers for this Issue");

/* /work/eventum/templates/authorized_replier.tpl.html */
gettext("Please select at least one of the authorized repliers.");

/* /work/eventum/templates/authorized_replier.tpl.html */
gettext("This action will remove the selected entries.");

/* /work/eventum/templates/authorized_replier.tpl.html */
gettext("Email");

/* /work/eventum/templates/authorized_replier.tpl.html */
gettext("No authorized repliers could be found.");

/* /work/eventum/templates/authorized_replier.tpl.html */
gettext("Remove Selected");

/* /work/eventum/templates/authorized_replier.tpl.html */
gettext("Close");

/* /work/eventum/templates/faq.tpl.html */
gettext("Error: You are not allowed to view the requested FAQ entry.");

/* /work/eventum/templates/faq.tpl.html */
gettext("Last updated");

/* /work/eventum/templates/faq.tpl.html */
gettext("Close Window");

/* /work/eventum/templates/faq.tpl.html */
gettext("Article Entries");

/* /work/eventum/templates/faq.tpl.html */
gettext("Title");

/* /work/eventum/templates/faq.tpl.html */
gettext("Last Updated Date");

/* /work/eventum/templates/faq.tpl.html */
gettext("read faq entry");

/* /work/eventum/templates/navigation.tpl.html */
gettext("logout from");

/* /work/eventum/templates/navigation.tpl.html */
gettext("Logout");

/* /work/eventum/templates/navigation.tpl.html */
gettext("manage the application settings, users, projects, etc");

/* /work/eventum/templates/navigation.tpl.html */
gettext("Administration");

/* /work/eventum/templates/navigation.tpl.html */
gettext("create a new issue");

/* /work/eventum/templates/navigation.tpl.html */
gettext("Create Issue");

/* /work/eventum/templates/navigation.tpl.html */
gettext("list the issues stored in the system");

/* /work/eventum/templates/navigation.tpl.html */
gettext("List Issues");

/* /work/eventum/templates/navigation.tpl.html */
gettext("get access to advanced search parameters");

/* /work/eventum/templates/navigation.tpl.html */
gettext("Advanced Search");

/* /work/eventum/templates/navigation.tpl.html */
gettext("list available emails");

/* /work/eventum/templates/navigation.tpl.html */
gettext("Associate Emails");

/* /work/eventum/templates/navigation.tpl.html */
gettext("list all issues assigned to you");

/* /work/eventum/templates/navigation.tpl.html */
gettext("My Assignments");

/* /work/eventum/templates/navigation.tpl.html */
gettext("general statistics");

/* /work/eventum/templates/navigation.tpl.html */
gettext("Stats");

/* /work/eventum/templates/navigation.tpl.html */
gettext("reporting system");

/* /work/eventum/templates/navigation.tpl.html */
gettext("Reports");

/* /work/eventum/templates/navigation.tpl.html */
gettext("internal faq");

/* /work/eventum/templates/navigation.tpl.html */
gettext("Internal FAQ");

/* /work/eventum/templates/navigation.tpl.html */
gettext("help documentation");

/* /work/eventum/templates/navigation.tpl.html */
gettext("Help");

/* /work/eventum/templates/navigation.tpl.html */
gettext("Project");

/* /work/eventum/templates/navigation.tpl.html */
gettext("Please enter a valid issue ID.");

/* /work/eventum/templates/navigation.tpl.html */
gettext("Switch");

/* /work/eventum/templates/navigation.tpl.html */
gettext("CLOCKED");

/* /work/eventum/templates/navigation.tpl.html */
gettext("IN");

/* /work/eventum/templates/navigation.tpl.html */
gettext("OUT");

/* /work/eventum/templates/navigation.tpl.html */
gettext("modify your account details and preferences");

/* /work/eventum/templates/navigation.tpl.html */
gettext("Preferences");

/* /work/eventum/templates/navigation.tpl.html */
gettext("change your account clocked-in status");

/* /work/eventum/templates/navigation.tpl.html */
gettext("Clock");

/* /work/eventum/templates/navigation.tpl.html */
gettext("Out");

/* /work/eventum/templates/navigation.tpl.html */
gettext("In");

/* /work/eventum/templates/navigation.tpl.html */
gettext("Search");

/* /work/eventum/templates/navigation.tpl.html */
gettext("Go");

/* /work/eventum/templates/expandable_cell/buttons.tpl.html */
gettext("Expand all collapsed cells");

/* /work/eventum/templates/expandable_cell/buttons.tpl.html */
gettext("Expand all collapsed cells");

/* /work/eventum/templates/expandable_cell/buttons.tpl.html */
gettext("Expand collapsed cell");

/* /work/eventum/templates/expandable_cell/buttons.tpl.html */
gettext("Collapse expanded cell");

/* /work/eventum/templates/main.tpl.html */
gettext("Overall Stats");

/* /work/eventum/templates/main.tpl.html */
gettext("Issues by Status");

/* /work/eventum/templates/main.tpl.html */
gettext("No issues could be found.");

/* /work/eventum/templates/main.tpl.html */
gettext("Issues by Release");

/* /work/eventum/templates/main.tpl.html */
gettext("No issues could be found.");

/* /work/eventum/templates/main.tpl.html */
gettext("Issues by Priority");

/* /work/eventum/templates/main.tpl.html */
gettext("No issues could be found.");

/* /work/eventum/templates/main.tpl.html */
gettext("Issues by Category");

/* /work/eventum/templates/main.tpl.html */
gettext("No issues could be found.");

/* /work/eventum/templates/main.tpl.html */
gettext("Assigned Issues");

/* /work/eventum/templates/main.tpl.html */
gettext("No issues could be found.");

/* /work/eventum/templates/main.tpl.html */
gettext("Emails");

/* /work/eventum/templates/main.tpl.html */
gettext("Associated");

/* /work/eventum/templates/main.tpl.html */
gettext("Pending");

/* /work/eventum/templates/main.tpl.html */
gettext("Removed");

/* /work/eventum/templates/main.tpl.html */
gettext("Did you Know?");

/* /work/eventum/templates/main.tpl.html */
gettext("Graphical Stats (All Issues)");

/* /work/eventum/templates/confirm.tpl.html */
gettext("Password Confirmation");

/* /work/eventum/templates/confirm.tpl.html */
gettext("Account Creation");

/* /work/eventum/templates/confirm.tpl.html */
gettext("Error");

/* /work/eventum/templates/confirm.tpl.html */
gettext("Password Confirmation Success");

/* /work/eventum/templates/confirm.tpl.html */
gettext("The provided trial account email address could not be\nconfirmed. Please contact the local Technical Support staff for\nfurther assistance.");

/* /work/eventum/templates/confirm.tpl.html */
gettext("The provided trial account email address could not be\n            found. Please contact the local Technical Support staff for\n            further assistance.");

/* /work/eventum/templates/confirm.tpl.html */
gettext("The provided trial account encrypted hash could not be\n            authenticated. Please contact the local Technical\n            Support staff for further assistance.");

/* /work/eventum/templates/confirm.tpl.html */
gettext("Thank you, your request for a new password was confirmed successfully. You should receive an email with your new password shortly.");

/* /work/eventum/templates/confirm.tpl.html */
gettext("Back to Login Form");

/* /work/eventum/templates/custom_fields_form.tpl.html */
gettext("An error occurred while trying to run your query");

/* /work/eventum/templates/custom_fields_form.tpl.html */
gettext("Thank you, the custom field values were updated successfully.");

/* /work/eventum/templates/custom_fields_form.tpl.html */
gettext("Continue");

/* /work/eventum/templates/custom_fields_form.tpl.html */
gettext("Update Issue Details");

/* /work/eventum/templates/custom_fields_form.tpl.html */
gettext("Please choose an option");

/* /work/eventum/templates/custom_fields_form.tpl.html */
gettext("No custom field could be found.");

/* /work/eventum/templates/custom_fields_form.tpl.html */
gettext("Update Values");

/* /work/eventum/templates/custom_fields_form.tpl.html */
gettext("Close");

/* /work/eventum/templates/reminders/alert_no_recipients.tpl.text */
gettext("The following %1 reminder could not be sent out because no recipients could be found");

/* /work/eventum/templates/reminders/alert_no_recipients.tpl.text */
gettext("Automated Issue");

/* /work/eventum/templates/reminders/alert_no_recipients.tpl.text */
gettext("Reminder Alert");

/* /work/eventum/templates/reminders/alert_no_recipients.tpl.text */
gettext("URL");

/* /work/eventum/templates/reminders/alert_no_recipients.tpl.text */
gettext("Summary");

/* /work/eventum/templates/reminders/alert_no_recipients.tpl.text */
gettext("Assignment");

/* /work/eventum/templates/reminders/alert_no_recipients.tpl.text */
gettext("Customer");

/* /work/eventum/templates/reminders/alert_no_recipients.tpl.text */
gettext("Support Level");

/* /work/eventum/templates/reminders/alert_no_recipients.tpl.text */
gettext("Alert Reason");

/* /work/eventum/templates/reminders/alert_no_recipients.tpl.text */
gettext("Triggered Reminder");

/* /work/eventum/templates/reminders/alert_no_recipients.tpl.text */
gettext("Action");

/* /work/eventum/templates/reminders/alert_no_recipients.tpl.text */
gettext("Alert Query");

/* /work/eventum/templates/reminders/email_alert.tpl.text */
gettext("Automated Issue # %1 Reminder Alert");

/* /work/eventum/templates/reminders/email_alert.tpl.text */
gettext("URL");

/* /work/eventum/templates/reminders/email_alert.tpl.text */
gettext("Summary");

/* /work/eventum/templates/reminders/email_alert.tpl.text */
gettext("Assignment");

/* /work/eventum/templates/reminders/email_alert.tpl.text */
gettext("Customer");

/* /work/eventum/templates/reminders/email_alert.tpl.text */
gettext("Support Level");

/* /work/eventum/templates/reminders/email_alert.tpl.text */
gettext("Alert Reason");

/* /work/eventum/templates/reminders/email_alert.tpl.text */
gettext("Triggered Reminder");

/* /work/eventum/templates/reminders/email_alert.tpl.text */
gettext("Action");

/* /work/eventum/templates/reminders/email_alert.tpl.text */
gettext("Alert Query");

/* /work/eventum/templates/reminders/sms_alert.tpl.text */
gettext("This is a SMS reminder alert regarding issue # %1. Certain conditions triggered this action, and this issue may require immediate action in your part.");

/* /work/eventum/templates/self_assign.tpl.html */
gettext("An error occurred while trying to run your query");

/* /work/eventum/templates/self_assign.tpl.html */
gettext("Thank you, you are now assigned to the issue");

/* /work/eventum/templates/self_assign.tpl.html */
gettext("Continue");

/* /work/eventum/templates/self_assign.tpl.html */
gettext("WARNING");

/* /work/eventum/templates/self_assign.tpl.html */
ngettext("The following user is already assigned to this issue","The following users are already assigned to this issue",x);

/* /work/eventum/templates/self_assign.tpl.html */
ngettext("Replace current assignee with Myself.","Replace current assignees with Myself.",x);

/* /work/eventum/templates/self_assign.tpl.html */
gettext("Add Myself to list of assignees.");

/* /work/eventum/templates/self_assign.tpl.html */
gettext("Continue");

/* /work/eventum/templates/edit_custom_fields.tpl.html */
gettext("Please choose an option");

/* /work/eventum/templates/post.tpl.html */
gettext("Sorry, but there are no projects currently setup as allowing anonymous posting.");

/* /work/eventum/templates/post.tpl.html */
gettext("Thank you, the new issue was created successfully. For your records, the new issue ID is <font color=\"red\">%1</font>");

/* /work/eventum/templates/post.tpl.html */
gettext("You may <a class=\"link\" href=\"%1\">%2</a> if you so wish.");

/* /work/eventum/templates/post.tpl.html */
gettext("Please choose the project that this new issue will apply to.");

/* /work/eventum/templates/post.tpl.html */
gettext("Report New Issue");

/* /work/eventum/templates/post.tpl.html */
gettext("Project");

/* /work/eventum/templates/post.tpl.html */
gettext("Please choose a project");

/* /work/eventum/templates/post.tpl.html */
gettext("Next");

/* /work/eventum/templates/post.tpl.html */
gettext("Summary");

/* /work/eventum/templates/post.tpl.html */
gettext("Description");

/* /work/eventum/templates/post.tpl.html */
gettext("Report New Issue");

/* /work/eventum/templates/post.tpl.html */
gettext("Project");

/* /work/eventum/templates/post.tpl.html */
gettext("Summary");

/* /work/eventum/templates/post.tpl.html */
gettext("Description");

/* /work/eventum/templates/post.tpl.html */
gettext("Attach Files");

/* /work/eventum/templates/post.tpl.html */
gettext("Keep Form Open");

/* /work/eventum/templates/post.tpl.html */
gettext("Submit");

/* /work/eventum/templates/post.tpl.html */
gettext("Reset");

/* /work/eventum/templates/post.tpl.html */
gettext("Required fields");

/* /work/eventum/templates/send.tpl.html */
gettext("An error occurred while trying to run your query");

/* /work/eventum/templates/send.tpl.html */
gettext("Sorry, but the email could not be queued. This might be related to problems with your SMTP account settings.\n  Please contact the administrator of this application for further assistance.");

/* /work/eventum/templates/send.tpl.html */
gettext("Thank you, the email was queued to be sent successfully.");

/* /work/eventum/templates/send.tpl.html */
gettext("Continue");

/* /work/eventum/templates/send.tpl.html */
gettext("An error occurred while trying to run your query");

/* /work/eventum/templates/send.tpl.html */
gettext("Thank you, the email message was saved as a draft successfully.");

/* /work/eventum/templates/send.tpl.html */
gettext("Continue");

/* /work/eventum/templates/send.tpl.html */
gettext("If you close this window, you will lose your message");

/* /work/eventum/templates/send.tpl.html */
gettext("Please enter the recipient of this email.");

/* /work/eventum/templates/send.tpl.html */
gettext("Please enter the subject of this email.");

/* /work/eventum/templates/send.tpl.html */
gettext("Please enter the message body of this email.");

/* /work/eventum/templates/send.tpl.html */
gettext("WARNING: You are not assigned to this issue so your email will be blocked.\nYour blocked email will be converted to a note that can be recovered later.\nFor more information, please see the topic 'email blocking' in help.");

/* /work/eventum/templates/send.tpl.html */
gettext("WARNING: This email will be sent to all names on this issue's Notification List, including CUSTOMERS.\nIf you want the CUSTOMER to receive your message now, press OK.\nOtherwise, to return to your editing window, press CANCEL.");

/* /work/eventum/templates/send.tpl.html */
gettext("WARNING: This email will be sent to all names on this issue's Notification List.\nIf you want all users to receive your message now, press OK.\nOtherwise, to return to your editing window, press CANCEL.");

/* /work/eventum/templates/send.tpl.html */
gettext("Warning: This draft has already been sent. You cannot resend it.");

/* /work/eventum/templates/send.tpl.html */
gettext("Warning: This draft has already been edited. You cannot send or edit it.");

/* /work/eventum/templates/send.tpl.html */
gettext("Create Draft");

/* /work/eventum/templates/send.tpl.html */
gettext("Send Email");

/* /work/eventum/templates/send.tpl.html */
gettext("An error occurred while trying to run your query");

/* /work/eventum/templates/send.tpl.html */
gettext("Sorry, but the email could not be sent. This might be related to problems with your SMTP account settings.\n              Please contact the administrator of this application for assistance.");

/* /work/eventum/templates/send.tpl.html */
gettext("Thank you, the email was sent successfully.");

/* /work/eventum/templates/send.tpl.html */
gettext("From");

/* /work/eventum/templates/send.tpl.html */
gettext("To");

/* /work/eventum/templates/send.tpl.html */
gettext("Issue");

/* /work/eventum/templates/send.tpl.html */
gettext("Notification List");

/* /work/eventum/templates/send.tpl.html */
gettext("Members");

/* /work/eventum/templates/send.tpl.html */
gettext("Cc");

/* /work/eventum/templates/send.tpl.html */
gettext("Add Unknown Recipients to Issue Notification List");

/* /work/eventum/templates/send.tpl.html */
gettext("Subject");

/* /work/eventum/templates/send.tpl.html */
gettext("Canned Responses");

/* /work/eventum/templates/send.tpl.html */
gettext("Use Canned Response");

/* /work/eventum/templates/send.tpl.html */
gettext("New Status for Issue");

/* /work/eventum/templates/send.tpl.html */
gettext("Time Spent");

/* /work/eventum/templates/send.tpl.html */
gettext("in minutes");

/* /work/eventum/templates/send.tpl.html */
gettext("Send Email");

/* /work/eventum/templates/send.tpl.html */
gettext("Reset");

/* /work/eventum/templates/send.tpl.html */
gettext("Cancel");

/* /work/eventum/templates/send.tpl.html */
gettext("Check Spelling");

/* /work/eventum/templates/send.tpl.html */
gettext("Add Email Signature");

/* /work/eventum/templates/send.tpl.html */
gettext("Save Draft Changes");

/* /work/eventum/templates/send.tpl.html */
gettext("Save as Draft");

/* /work/eventum/templates/send.tpl.html */
gettext("Required fields");

/* /work/eventum/templates/app_info.tpl.html */
gettext("Page generated in %1 seconds");

/* /work/eventum/templates/app_info.tpl.html */
gettext("queries");

/* /work/eventum/templates/app_info.tpl.html */
gettext("Benchmark Statistics");

/* /work/eventum/templates/login_form.tpl.html */
gettext("Email Address");

/* /work/eventum/templates/login_form.tpl.html */
gettext("Password");

/* /work/eventum/templates/login_form.tpl.html */
gettext("Login");

/* /work/eventum/templates/login_form.tpl.html */
gettext("Error: Please provide your email address.");

/* /work/eventum/templates/login_form.tpl.html */
gettext("Error: Please provide your password.");

/* /work/eventum/templates/login_form.tpl.html */
gettext("Error: The email address / password combination could not be found in the system.");

/* /work/eventum/templates/login_form.tpl.html */
gettext("Your session has expired. Please login again to continue.");

/* /work/eventum/templates/login_form.tpl.html */
gettext("Thank you, you are now logged out of %1");

/* /work/eventum/templates/login_form.tpl.html */
gettext("Error: Your user status is currently set as inactive. Please\n              contact your local system administrator for further information.");

/* /work/eventum/templates/login_form.tpl.html */
gettext("Thank you, your account is now active and ready to be\n              used. Use the form below to login.");

/* /work/eventum/templates/login_form.tpl.html */
gettext("Error: Your user status is currently set as pending. This\n              means that you still need to confirm your account\n              creation request. Please contact your local system\n              administrator for further information.");

/* /work/eventum/templates/login_form.tpl.html */
gettext("Error: Cookies support seem to be disabled in your browser. Please enable this feature and try again.");

/* /work/eventum/templates/login_form.tpl.html */
gettext("Error: In order for %1 to work properly, you must enable cookie support in your browser. Please login\n              again and accept all cookies coming from it.");

/* /work/eventum/templates/login_form.tpl.html */
gettext("Email Address");

/* /work/eventum/templates/login_form.tpl.html */
gettext("Password");

/* /work/eventum/templates/login_form.tpl.html */
gettext("Login");

/* /work/eventum/templates/login_form.tpl.html */
gettext("Remember Login");

/* /work/eventum/templates/login_form.tpl.html */
gettext("I Forgot My Password");

/* /work/eventum/templates/login_form.tpl.html */
gettext("Signup for an Account");

/* /work/eventum/templates/login_form.tpl.html */
gettext("Requires support for cookies and javascript in your browser");

/* /work/eventum/templates/login_form.tpl.html */
gettext("NOTE: You may report issues without the need to login by using the following URL");

/* /work/eventum/templates/notification.tpl.html */
gettext("Please enter a valid email address.");

/* /work/eventum/templates/notification.tpl.html */
gettext("The given email address");

/* /work/eventum/templates/notification.tpl.html */
gettext("is neither a known staff member or customer technical contact.");

/* /work/eventum/templates/notification.tpl.html */
gettext("Are you sure you want to add this address to the notification list?");

/* /work/eventum/templates/notification.tpl.html */
gettext("Notification Options");

/* /work/eventum/templates/notification.tpl.html */
gettext("An error occurred while trying to update the notification entry.");

/* /work/eventum/templates/notification.tpl.html */
gettext("Error: the given email address is not allowed to be added to the notification list.");

/* /work/eventum/templates/notification.tpl.html */
gettext("Thank you, the notification entry was updated successfully.");

/* /work/eventum/templates/notification.tpl.html */
gettext("Email");

/* /work/eventum/templates/notification.tpl.html */
gettext("Get a Notification When");

/* /work/eventum/templates/notification.tpl.html */
gettext("Emails are Received or Sent");

/* /work/eventum/templates/notification.tpl.html */
gettext("Overview or Details are Changed");

/* /work/eventum/templates/notification.tpl.html */
gettext("Issue is Closed");

/* /work/eventum/templates/notification.tpl.html */
gettext("Files are Attached");

/* /work/eventum/templates/notification.tpl.html */
gettext("Update Subscription");

/* /work/eventum/templates/notification.tpl.html */
gettext("Add Subscription");

/* /work/eventum/templates/notification.tpl.html */
gettext("Reset");

/* /work/eventum/templates/notification.tpl.html */
gettext("Existing Subscribers for this Issue");

/* /work/eventum/templates/notification.tpl.html */
gettext("Please select at least one of the subscribers.");

/* /work/eventum/templates/notification.tpl.html */
gettext("This action will remove the selected entries.");

/* /work/eventum/templates/notification.tpl.html */
gettext("Email");

/* /work/eventum/templates/notification.tpl.html */
gettext("click to edit");

/* /work/eventum/templates/notification.tpl.html */
gettext("Actions");

/* /work/eventum/templates/notification.tpl.html */
gettext("update this entry");

/* /work/eventum/templates/notification.tpl.html */
gettext("No subscribers could be found.");

/* /work/eventum/templates/notification.tpl.html */
gettext("Remove Selected");

/* /work/eventum/templates/notification.tpl.html */
gettext("Close");

/* /work/eventum/templates/file_upload.tpl.html */
gettext("An error occurred while trying to process the uploaded file.");

/* /work/eventum/templates/file_upload.tpl.html */
gettext("The uploaded file is already attached to the current issue. Please rename the file and try again.");

/* /work/eventum/templates/file_upload.tpl.html */
gettext("Thank you, the uploaded file was associated with the issue below.");

/* /work/eventum/templates/file_upload.tpl.html */
gettext("Continue");

/* /work/eventum/templates/file_upload.tpl.html */
gettext("Add New Files");

/* /work/eventum/templates/file_upload.tpl.html */
gettext("Status");

/* /work/eventum/templates/file_upload.tpl.html */
gettext("Public");

/* /work/eventum/templates/file_upload.tpl.html */
gettext("visible to all");

/* /work/eventum/templates/file_upload.tpl.html */
gettext("Private");

/* /work/eventum/templates/file_upload.tpl.html */
gettext("standard user and above only");

/* /work/eventum/templates/file_upload.tpl.html */
gettext("Filenames");

/* /work/eventum/templates/file_upload.tpl.html */
gettext("ote: The current maximum allowed upload file size is");

/* /work/eventum/templates/file_upload.tpl.html */
gettext("Description");

/* /work/eventum/templates/file_upload.tpl.html */
gettext("Upload File");

/* /work/eventum/templates/file_upload.tpl.html */
gettext("You do not have the correct role to access this page");

/* /work/eventum/templates/update.tpl.html */
gettext("Error: The issue could not be found.");

/* /work/eventum/templates/update.tpl.html */
gettext("Go Back");

/* /work/eventum/templates/update.tpl.html */
gettext("Sorry, you do not have the required privileges to view this issue.");

/* /work/eventum/templates/update.tpl.html */
gettext("Go Back");

/* /work/eventum/templates/update.tpl.html */
gettext("Sorry, but you do not have the required permission level to access this screen.");

/* /work/eventum/templates/update.tpl.html */
gettext("Go Back");

/* /work/eventum/templates/requirement.tpl.html */
gettext("An error occurred while trying to run your query");

/* /work/eventum/templates/requirement.tpl.html */
gettext("Thank you, the impact analysis was updated successfully.");

/* /work/eventum/templates/requirement.tpl.html */
gettext("Please use only floating point numbers on the estimated development time field.");

/* /work/eventum/templates/requirement.tpl.html */
gettext("Please enter the impact analysis for this new requirement.");

/* /work/eventum/templates/requirement.tpl.html */
gettext("Enter Impact Analysis");

/* /work/eventum/templates/requirement.tpl.html */
gettext("Estimated Dev. Time");

/* /work/eventum/templates/requirement.tpl.html */
gettext("in hours");

/* /work/eventum/templates/requirement.tpl.html */
gettext("Impact <br />Analysis");

/* /work/eventum/templates/notifications/new.tpl.text */
gettext("This is an automated message sent at your request from %1");

/* /work/eventum/templates/notifications/new.tpl.text */
gettext("A new issue was just created and assigned to you.");

/* /work/eventum/templates/notifications/new.tpl.text */
gettext("To view more details of this issue, or to update it, please visit the following URL");

/* /work/eventum/templates/notifications/new.tpl.text */
gettext("ID");

/* /work/eventum/templates/notifications/new.tpl.text */
gettext("Summary");

/* /work/eventum/templates/notifications/new.tpl.text */
gettext("Project");

/* /work/eventum/templates/notifications/new.tpl.text */
gettext("Reported By");

/* /work/eventum/templates/notifications/new.tpl.text */
gettext("Priority");

/* /work/eventum/templates/notifications/new.tpl.text */
gettext("Description");

/* /work/eventum/templates/notifications/new.tpl.text */
gettext("Please Note: If you do not wish to receive any future email\nnotifications from %1, please change your account preferences by\nvisiting the URL below");

/* /work/eventum/templates/notifications/new_user.tpl.text */
gettext("A new user was just created for you in the system.");

/* /work/eventum/templates/notifications/new_user.tpl.text */
gettext("To start using the system, please load the URL below");

/* /work/eventum/templates/notifications/new_user.tpl.text */
gettext("Full Name");

/* /work/eventum/templates/notifications/new_user.tpl.text */
gettext("Email Address");

/* /work/eventum/templates/notifications/new_user.tpl.text */
gettext("Password");

/* /work/eventum/templates/notifications/new_user.tpl.text */
gettext("Assigned Projects");

/* /work/eventum/templates/notifications/updated_password.tpl.text */
gettext("Your user account password has been updated in %1");

/* /work/eventum/templates/notifications/updated_password.tpl.text */
gettext("Your account information as it now exists appears below.");

/* /work/eventum/templates/notifications/updated_password.tpl.text */
gettext("Full Name");

/* /work/eventum/templates/notifications/updated_password.tpl.text */
gettext("Email Address");

/* /work/eventum/templates/notifications/updated_password.tpl.text */
gettext("Password");

/* /work/eventum/templates/notifications/updated_password.tpl.text */
gettext("Assigned Projects");

/* /work/eventum/templates/notifications/closed.tpl.text */
gettext("This is an automated message sent at your request from %1.");

/* /work/eventum/templates/notifications/closed.tpl.text */
gettext("This issue was just closed by");

/* /work/eventum/templates/notifications/closed.tpl.text */
gettext("with the message");

/* /work/eventum/templates/notifications/closed.tpl.text */
gettext("To view more details of this issue, or to update it, please visit the following URL");

/* /work/eventum/templates/notifications/closed.tpl.text */
gettext("ID");

/* /work/eventum/templates/notifications/closed.tpl.text */
gettext("Summary");

/* /work/eventum/templates/notifications/closed.tpl.text */
gettext("Status");

/* /work/eventum/templates/notifications/closed.tpl.text */
gettext("Project");

/* /work/eventum/templates/notifications/closed.tpl.text */
gettext("Reported By");

/* /work/eventum/templates/notifications/closed.tpl.text */
gettext("Priority");

/* /work/eventum/templates/notifications/closed.tpl.text */
gettext("Description");

/* /work/eventum/templates/notifications/closed.tpl.text */
gettext("Please Note: If you do not wish to receive any future email notifications from %1, please change your account preferences by visiting the URL below");

/* /work/eventum/templates/notifications/new_auto_created_issue.tpl.text */
gettext("Dear");

/* /work/eventum/templates/notifications/new_auto_created_issue.tpl.text */
gettext("This is an automated message sent at your request from %1");

/* /work/eventum/templates/notifications/new_auto_created_issue.tpl.text */
gettext("We received a message from you and for your convenience, we created an issue that will be used by our staff to handle your message.");

/* /work/eventum/templates/notifications/new_auto_created_issue.tpl.text */
gettext("Date");

/* /work/eventum/templates/notifications/new_auto_created_issue.tpl.text */
gettext("From");

/* /work/eventum/templates/notifications/new_auto_created_issue.tpl.text */
gettext("Subject");

/* /work/eventum/templates/notifications/new_auto_created_issue.tpl.text */
gettext("To view more details of this issue, or to update it, please visit the\nfollowing URL");

/* /work/eventum/templates/notifications/new_auto_created_issue.tpl.text */
gettext("Issue");

/* /work/eventum/templates/notifications/new_auto_created_issue.tpl.text */
gettext("Summary");

/* /work/eventum/templates/notifications/new_auto_created_issue.tpl.text */
gettext("Priority");

/* /work/eventum/templates/notifications/new_auto_created_issue.tpl.text */
gettext("Submitted");

/* /work/eventum/templates/notifications/new_auto_created_issue.tpl.text */
gettext("Please Note: If you do not wish to receive any future email\nnotifications from %1, please change your account preferences by\nvisiting the URL below");

/* /work/eventum/templates/notifications/notes.tpl.text */
gettext("These are the current issue details");

/* /work/eventum/templates/notifications/notes.tpl.text */
gettext("ID");

/* /work/eventum/templates/notifications/notes.tpl.text */
gettext("Summary");

/* /work/eventum/templates/notifications/notes.tpl.text */
gettext("Status");

/* /work/eventum/templates/notifications/notes.tpl.text */
gettext("Project");

/* /work/eventum/templates/notifications/notes.tpl.text */
gettext("Reported By");

/* /work/eventum/templates/notifications/notes.tpl.text */
gettext("Priority");

/* /work/eventum/templates/notifications/notes.tpl.text */
gettext("Description");

/* /work/eventum/templates/notifications/notes.tpl.text */
gettext("To view more details of this issue, or to update it, please visit the following URL");

/* /work/eventum/templates/notifications/notes.tpl.text */
gettext("Please Note: If you do not wish to receive any future email notifications from %1, please change your account preferences by visiting the URL below");

/* /work/eventum/templates/notifications/account_details.tpl.text */
gettext("This is an automated message sent at your request from %1.");

/* /work/eventum/templates/notifications/account_details.tpl.text */
gettext("Your full account information is available below.");

/* /work/eventum/templates/notifications/account_details.tpl.text */
gettext("Full Name");

/* /work/eventum/templates/notifications/account_details.tpl.text */
gettext("Email Address");

/* /work/eventum/templates/notifications/account_details.tpl.text */
gettext("Assigned Projects");

/* /work/eventum/templates/notifications/updated_account.tpl.text */
gettext("Your user account has been updated in %1");

/* /work/eventum/templates/notifications/updated_account.tpl.text */
gettext("Your account information as it now exists appears below.");

/* /work/eventum/templates/notifications/updated_account.tpl.text */
gettext("Full Name");

/* /work/eventum/templates/notifications/updated_account.tpl.text */
gettext("Email Address");

/* /work/eventum/templates/notifications/updated_account.tpl.text */
gettext("Assigned Projects");

/* /work/eventum/templates/notifications/assigned.tpl.text */
gettext("This is an automated message sent at your request from %1");

/* /work/eventum/templates/notifications/assigned.tpl.text */
gettext("An issue was assigned to you by %1");

/* /work/eventum/templates/notifications/assigned.tpl.text */
gettext("To view more details of this issue, or to update it, please visit the following URL");

/* /work/eventum/templates/notifications/assigned.tpl.text */
gettext("ID");

/* /work/eventum/templates/notifications/assigned.tpl.text */
gettext("Summary");

/* /work/eventum/templates/notifications/assigned.tpl.text */
gettext("Project");

/* /work/eventum/templates/notifications/assigned.tpl.text */
gettext("Reported By");

/* /work/eventum/templates/notifications/assigned.tpl.text */
gettext("Assignment");

/* /work/eventum/templates/notifications/assigned.tpl.text */
gettext("Priority");

/* /work/eventum/templates/notifications/assigned.tpl.text */
gettext("Description");

/* /work/eventum/templates/notifications/assigned.tpl.text */
gettext("Please Note: If you do not wish to receive any future email notifications from %1, please change your account preferences by visiting the URL below");

/* /work/eventum/templates/notifications/files.tpl.text */
gettext("This is an automated message sent at your request from");

/* /work/eventum/templates/notifications/files.tpl.text */
gettext("To view more details of this issue, or to update it, please visit the following URL");

/* /work/eventum/templates/notifications/files.tpl.text */
gettext("New Attachment");

/* /work/eventum/templates/notifications/files.tpl.text */
gettext("Owner");

/* /work/eventum/templates/notifications/files.tpl.text */
gettext("Date");

/* /work/eventum/templates/notifications/files.tpl.text */
gettext("Files");

/* /work/eventum/templates/notifications/files.tpl.text */
gettext("Description");

/* /work/eventum/templates/notifications/files.tpl.text */
gettext("These are the current issue details");

/* /work/eventum/templates/notifications/files.tpl.text */
gettext("ID");

/* /work/eventum/templates/notifications/files.tpl.text */
gettext("Summary");

/* /work/eventum/templates/notifications/files.tpl.text */
gettext("Status");

/* /work/eventum/templates/notifications/files.tpl.text */
gettext("Project");

/* /work/eventum/templates/notifications/files.tpl.text */
gettext("Reported By");

/* /work/eventum/templates/notifications/files.tpl.text */
gettext("Priority");

/* /work/eventum/templates/notifications/files.tpl.text */
gettext("Description");

/* /work/eventum/templates/notifications/files.tpl.text */
gettext("Please Note: If you do not wish to receive any future email notifications from %1, please change your account preferences by visiting the URL below");

/* /work/eventum/templates/notifications/new_issue.tpl.text */
gettext("This is an automated message sent at your request from");

/* /work/eventum/templates/notifications/new_issue.tpl.text */
gettext("A new issue was just created in the system.");

/* /work/eventum/templates/notifications/new_issue.tpl.text */
gettext("To view more details of this issue, or to update it, please visit the following URL");

/* /work/eventum/templates/notifications/new_issue.tpl.text */
gettext("ID");

/* /work/eventum/templates/notifications/new_issue.tpl.text */
gettext("Summary");

/* /work/eventum/templates/notifications/new_issue.tpl.text */
gettext("Project");

/* /work/eventum/templates/notifications/new_issue.tpl.text */
gettext("Reported");

/* /work/eventum/templates/notifications/new_issue.tpl.text */
gettext("Assignment");

/* /work/eventum/templates/notifications/new_issue.tpl.text */
gettext("Priority");

/* /work/eventum/templates/notifications/new_issue.tpl.text */
gettext("Description");

/* /work/eventum/templates/notifications/new_issue.tpl.text */
gettext("Issue Details");

/* /work/eventum/templates/notifications/new_issue.tpl.text */
gettext("Attachments");

/* /work/eventum/templates/notifications/new_issue.tpl.text */
gettext("Files");

/* /work/eventum/templates/notifications/new_issue.tpl.text */
gettext("Description");

/* /work/eventum/templates/notifications/new_issue.tpl.text */
gettext("Please Note: If you do not wish to receive any future email notifications from %1, please change your account preferences by visiting the URL below");

/* /work/eventum/templates/notifications/updated.tpl.text */
gettext("This is an automated message sent at your request from");

/* /work/eventum/templates/notifications/updated.tpl.text */
gettext("To view more details of this issue, or to update it, please visit the following URL");

/* /work/eventum/templates/notifications/updated.tpl.text */
gettext("Issue #");

/* /work/eventum/templates/notifications/updated.tpl.text */
gettext("Summary");

/* /work/eventum/templates/notifications/updated.tpl.text */
gettext("Changed Fields");

/* /work/eventum/templates/notifications/updated.tpl.text */
gettext("Please Note: If you do not wish to receive any future email notifications from %1, please change your account preferences by visiting the URL below");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Please select the new status for this issue.");

/* /work/eventum/templates/view_form.tpl.html */
gettext("NOTE: If you need to send new information regarding this issue, please use the EMAIL related buttons available at the bottom of the screen.");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Previous Issue");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Next Issue");

/* /work/eventum/templates/view_form.tpl.html */
gettext("This Issue is Currently Quarantined");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Quarantine expires in %1");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Please see the <a class=\"link\" href=\"faq.php\">FAQ</a> for information regarding quarantined issues.");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Remove Quarantine");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Note: ");

/* /work/eventum/templates/view_form.tpl.html */
gettext("This issue is marked private. Only Managers, the reporter and users assigned to the issue can view it.");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Issue Overview");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Edit Authorized Replier List");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Edit Notification List");

/* /work/eventum/templates/view_form.tpl.html */
gettext("History of Changes");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Customer");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Complete Details");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Customer Contract");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Support Level");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Support Options");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Redeemed Incident Types");

/* /work/eventum/templates/view_form.tpl.html */
gettext("None");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Category");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Status");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Notification List");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Staff");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Other");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Status");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Submitted Date");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Priority");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Last Updated Date");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Scheduled Release");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Associated Issues");

/* /work/eventum/templates/view_form.tpl.html */
gettext("No issues associated");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Resolution");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Expected Resolution Date");

/* /work/eventum/templates/view_form.tpl.html */
gettext("No resolution date given");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Percentage Complete");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Estimated Dev. Time");

/* /work/eventum/templates/view_form.tpl.html */
gettext("hours");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Reporter");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Duplicates");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Duplicate of");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Duplicated by");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Assignment");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Authorized Repliers");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Staff");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Other");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Group");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Summary");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Initial Description");

/* /work/eventum/templates/view_form.tpl.html */
gettext("fixed width font");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Description is currently collapsed");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Click to expand.");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Unassign Issue");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Assign Issue To Myself");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Update Issue");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Reply");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Clear Duplicate Status");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Mark as Duplicate");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Close Issue");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Signup as Authorized Replier");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Edit Incident Redemption");

/* /work/eventum/templates/view_form.tpl.html */
gettext("Change Status To");

/* /work/eventum/templates/column_display.tpl.html */
gettext("Please choose which entries need to be removed.");

/* /work/eventum/templates/column_display.tpl.html */
gettext("Attached Emails");

/* /work/eventum/templates/column_display.tpl.html */
gettext("Remove?");

/* /work/eventum/templates/column_display.tpl.html */
gettext("Sender");

/* /work/eventum/templates/column_display.tpl.html */
gettext("Subject");

/* /work/eventum/templates/column_display.tpl.html */
gettext("Notify Sender?");

/* /work/eventum/templates/column_display.tpl.html */
gettext("Remove Selected");

/* /work/eventum/templates/email_drafts.tpl.html */
gettext("Drafts");

/* /work/eventum/templates/email_drafts.tpl.html */
gettext("Status");

/* /work/eventum/templates/email_drafts.tpl.html */
gettext("From");

/* /work/eventum/templates/email_drafts.tpl.html */
gettext("To");

/* /work/eventum/templates/email_drafts.tpl.html */
gettext("Last Updated Date");

/* /work/eventum/templates/email_drafts.tpl.html */
gettext("Subject");

/* /work/eventum/templates/email_drafts.tpl.html */
gettext("No email drafts could be found.");

/* /work/eventum/templates/email_drafts.tpl.html */
gettext("Create Draft");

/* /work/eventum/templates/email_drafts.tpl.html */
gettext("Show All Drafts");

/* /work/eventum/templates/redeem_incident.tpl.html */
gettext("There was an error marking this issue as redeemed");

/* /work/eventum/templates/redeem_incident.tpl.html */
gettext("This issue already has been marked as redeemed");

/* /work/eventum/templates/redeem_incident.tpl.html */
gettext("Thank you, the issue was successfully marked.");

/* /work/eventum/templates/redeem_incident.tpl.html */
gettext("Please choose the incident types to redeem for this issue.");

/* /work/eventum/templates/redeem_incident.tpl.html */
gettext("Total");

/* /work/eventum/templates/redeem_incident.tpl.html */
gettext("Left");

/* /work/eventum/templates/redeem_incident.tpl.html */
gettext("Redeem Incidents");

/* /work/eventum/templates/redeem_incident.tpl.html */
gettext("Continue");

/* /work/eventum/templates/custom_fields.tpl.html */
gettext("Custom Fields");

/* /work/eventum/templates/custom_fields.tpl.html */
gettext("No custom fields could be found.");

/* /work/eventum/templates/custom_fields.tpl.html */
gettext("Update");

/* /work/eventum/templates/switch.tpl.html */
gettext("Thank you, your current selected project was changed successfully.");

/* /work/eventum/templates/switch.tpl.html */
gettext("Continue");

/* /work/eventum/templates/news.tpl.html */
gettext("Important Notices");

/* /work/eventum/templates/impact_analysis.tpl.html */
gettext("Please enter the estimated development time for this task.");

/* /work/eventum/templates/impact_analysis.tpl.html */
gettext("Please use only floating point numbers (or integers) on the estimated development time field.");

/* /work/eventum/templates/impact_analysis.tpl.html */
gettext("Please enter the analysis for the changes required by this issue.");

/* /work/eventum/templates/impact_analysis.tpl.html */
gettext("Impact Analysis");

/* /work/eventum/templates/impact_analysis.tpl.html */
gettext("Total Estimated Dev. Time");

/* /work/eventum/templates/impact_analysis.tpl.html */
gettext("in hours");

/* /work/eventum/templates/impact_analysis.tpl.html */
gettext("hours");

/* /work/eventum/templates/impact_analysis.tpl.html */
gettext("Initial Impact Analysis");

/* /work/eventum/templates/impact_analysis.tpl.html */
gettext("Update");

/* /work/eventum/templates/impact_analysis.tpl.html */
gettext("Please choose which entries need to be removed.");

/* /work/eventum/templates/impact_analysis.tpl.html */
gettext("This action will permanently delete the selected entries.");

/* /work/eventum/templates/impact_analysis.tpl.html */
gettext("Further Requirements");

/* /work/eventum/templates/impact_analysis.tpl.html */
gettext("All");

/* /work/eventum/templates/impact_analysis.tpl.html */
gettext("Handler");

/* /work/eventum/templates/impact_analysis.tpl.html */
gettext("Requirement");

/* /work/eventum/templates/impact_analysis.tpl.html */
gettext("Estimated Dev. Time");

/* /work/eventum/templates/impact_analysis.tpl.html */
gettext("Impact Analysis");

/* /work/eventum/templates/impact_analysis.tpl.html */
gettext("update entry");

/* /work/eventum/templates/impact_analysis.tpl.html */
gettext("update entry");

/* /work/eventum/templates/impact_analysis.tpl.html */
gettext("All");

/* /work/eventum/templates/impact_analysis.tpl.html */
gettext("Remove Selected");

/* /work/eventum/templates/impact_analysis.tpl.html */
gettext("No entries could be found.");

/* /work/eventum/templates/impact_analysis.tpl.html */
gettext("Please enter the new requirement for this issue.");

/* /work/eventum/templates/impact_analysis.tpl.html */
gettext("Add New Requirement");

/* /work/eventum/templates/attachments.tpl.html */
gettext("This action will permanently delete the selected attachment.");

/* /work/eventum/templates/attachments.tpl.html */
gettext("This action will permanently delete the selected file.");

/* /work/eventum/templates/attachments.tpl.html */
gettext("Attached Files");

/* /work/eventum/templates/attachments.tpl.html */
gettext("Files");

/* /work/eventum/templates/attachments.tpl.html */
gettext("Owner");

/* /work/eventum/templates/attachments.tpl.html */
gettext("Status");

/* /work/eventum/templates/attachments.tpl.html */
gettext("Date");

/* /work/eventum/templates/attachments.tpl.html */
gettext("Description");

/* /work/eventum/templates/attachments.tpl.html */
gettext("delete");

/* /work/eventum/templates/attachments.tpl.html */
gettext("delete");

/* /work/eventum/templates/attachments.tpl.html */
gettext("No attachments could be found.");

/* /work/eventum/templates/attachments.tpl.html */
gettext("Upload File");

/* /work/eventum/templates/select_project.tpl.html */
gettext("Please choose the project.");

/* /work/eventum/templates/select_project.tpl.html */
gettext("Select Project");

/* /work/eventum/templates/select_project.tpl.html */
gettext("You are not allowed to use the selected project.");

/* /work/eventum/templates/select_project.tpl.html */
gettext("Project");

/* /work/eventum/templates/select_project.tpl.html */
gettext("Remember Selection");

/* /work/eventum/templates/select_project.tpl.html */
gettext("Continue");

/* /work/eventum/templates/removed_emails.tpl.html */
gettext("An error occurred while trying to run your query");

/* /work/eventum/templates/removed_emails.tpl.html */
gettext("Removed Emails");

/* /work/eventum/templates/removed_emails.tpl.html */
gettext("Please choose which emails need to be restored.");

/* /work/eventum/templates/removed_emails.tpl.html */
gettext("Please choose which emails need to be permanently removed.");

/* /work/eventum/templates/removed_emails.tpl.html */
gettext("WARNING: This action will permanently remove the selected emails from your email account.");

/* /work/eventum/templates/removed_emails.tpl.html */
gettext("All");

/* /work/eventum/templates/removed_emails.tpl.html */
gettext("Date");

/* /work/eventum/templates/removed_emails.tpl.html */
gettext("From");

/* /work/eventum/templates/removed_emails.tpl.html */
gettext("Subject");

/* /work/eventum/templates/removed_emails.tpl.html */
gettext("No emails could be found.");

/* /work/eventum/templates/removed_emails.tpl.html */
gettext("All");

/* /work/eventum/templates/removed_emails.tpl.html */
gettext("Restore Emails");

/* /work/eventum/templates/removed_emails.tpl.html */
gettext("Close");

/* /work/eventum/templates/removed_emails.tpl.html */
gettext("Permanently Remove");

