/* templates//help/report_description.tpl.html */
ev_gettext("Description Field");

/* templates//help/report_description.tpl.html */
ev_gettext("The description field should be used to describe the new issue. Good\npractices dictate that this field should have a description of what\nhappened, steps to reproduce the problem/issue and what you expected \nto happen instead.");

/* templates//help/report_priority.tpl.html */
ev_gettext("Priority Field");

/* templates//help/report_priority.tpl.html */
ev_gettext("This field is used to prioritize issues, as to make project management\na little easier. If you are not sure, or don't know what the appropriate\npriority should be for new issues, choose 'not prioritized' as the \noption and leave the issue to be prioritized by a project manager.");

/* templates//help/report_priority.tpl.html */
ev_gettext("Note: The values in this field can be changed by going in the administration\nsection of this application and editing the appropriate atributes of\na project. If you do not have the needed permissions to do so, please\ncontact your local Eventum administrator.");

/* templates//help/view_attachment.tpl.html */
ev_gettext("Attachments");

/* templates//help/view_time.tpl.html */
ev_gettext("Time Tracking");

/* templates//help/scm_integration_usage.tpl.html */
ev_gettext("Usage Examples");

/* templates//help/scm_integration_usage.tpl.html */
ev_gettext("An integration script will need to be installed in your CVS root \nrepository in order to send a message to Eventum whenever changes are\ncommitted to the repository. This message will then be processed by\nEventum and the changes to the appropriate files will be associated\nwith existing issue mentioned in your commit message.");

/* templates//help/scm_integration_usage.tpl.html */
ev_gettext("So to examplify its use, whenever the users are ready to commit the\nchanges to the CVS repository, they will add a special string to\nspecify which issue this is related to. The following would be a\ngood example of its use:");

/* templates//help/scm_integration_usage.tpl.html */
ev_gettext("[prompt]$ cvs -q commit -m \"Adding form validation as requested (issue: 13)\" form.php");

/* templates//help/scm_integration_usage.tpl.html */
ev_gettext("You may also use 'bug' to specify the issue ID - whichever you are more\ncomfortable with.");

/* templates//help/scm_integration_usage.tpl.html */
ev_gettext("This command will be parsed by the CVS integration script (provided to\nyou and available in %eventum_path%/misc/scm/process_cvs_commits.php) and it\nwill notify Eventum that these changes are to be associated with issue\n#13.");

/* templates//help/adv_search.tpl.html */
ev_gettext("Advanced Search / Creating Custom Queries");

/* templates//help/adv_search.tpl.html */
ev_gettext("This page allows you to create and modify saved custom searches, which\nwill save searches that can be executed from the Issue Listing screen.");

/* templates//help/adv_search.tpl.html */
ev_gettext("Most of the time users will want to run common used queries against\nthe issue database, and this is a feature perfect for such situations,\njust create a custom query in this screen and run it from the Issue\nListing page.");

/* templates//help/view.tpl.html */
ev_gettext("Viewing Issues");

/* templates//help/view.tpl.html */
ev_gettext("The issue details screen can be accessed quickly by using the 'Go'\ninput field in the top of your browser window. Just enter the issue \nnumber and it will take you to the appropriate screen.");

/* templates//help/view.tpl.html */
ev_gettext("The Issue Details page will also show '<< Previous Issue' and 'Next\nIssue >>' links that are related to the previous and next issues for\nthe current active filter, if appropriate.");

/* templates//help/view.tpl.html */
ev_gettext("The full history of changes related to the current issue is available\nby clickin on the 'History of Changes' link.");

/* templates//help/list.tpl.html */
ev_gettext("Listing / Searching for Issues");

/* templates//help/list.tpl.html */
ev_gettext("The Issue Listing page uses a grid layout to simplify the manual\nsearch for issues in a project. You may sort for (almost) any column\nin this grid form, and users with the appropriate permissions may also\nassign selected issues to another user.");

/* templates//help/list.tpl.html */
ev_gettext("The quick search table in the top of the screen helps the users find\nthe issues they want quickly. More advanced searches may be created\nusing the Advanced Search tool.");

/* templates//help/support_emails.tpl.html */
ev_gettext("Associate Emails");

/* templates//help/support_emails.tpl.html */
ev_gettext("This screen allows users with the appropriate permissions to associate\nemails with existing issues, or create new issues and \nassociate emails with them.");

/* templates//help/support_emails.tpl.html */
ev_gettext("In order to do that, however, the administrator of the system needs\nto configure email accounts to make the software download\nthe email messages from the appropriate POP3/IMAP server.");

/* templates//help/support_emails.tpl.html */
ev_gettext("One of the optimal uses of this feature is to create a separate \n'issues' or 'support' POP3/IMAP account and ask your customers or \nend-users to send support questions, issues or suggestions to that \nmailbox. Eventum will then download the emails and provide \nthem to the users of the system.");

/* templates//help/report.tpl.html */
ev_gettext("Reporting New Issues");

/* templates//help/report.tpl.html */
ev_gettext("To report new issues, click in the 'Create Issue' link in the top of \nyour browser window.");

/* templates//help/segregate_reporter.tpl.html */
ev_gettext("Segregate Reporter");

/* templates//help/segregate_reporter.tpl.html */
ev_gettext("If this option is enabled, users with a role of Reporter will only be able to see issues they reported.");

/* templates//help/view_note.tpl.html */
ev_gettext("Notes");

/* templates//help/notifications.tpl.html */
ev_gettext("Email Notifications");

/* templates//help/notifications.tpl.html */
ev_gettext("This feature allows system users to subscribe to email notifications\nwhen changes are done to specific issues. The current actions that\ntrigger email notifications are:");

/* templates//help/notifications.tpl.html */
ev_gettext("Issue details are updated");

/* templates//help/notifications.tpl.html */
ev_gettext("Issues are Closed");

/* templates//help/notifications.tpl.html */
ev_gettext("Notes are added to existing issues");

/* templates//help/notifications.tpl.html */
ev_gettext("Emails are associated to existing issues");

/* templates//help/notifications.tpl.html */
ev_gettext("Files are attached to existing issues");

/* templates//help/notifications.tpl.html */
ev_gettext("System users may subscribe to the actions above for specific issues\nwhen they report new issues or by visiting the issue details screen \nand subscribing manually by using the 'Edit Notification List' link.");

/* templates//help/customize_listing.tpl.html */
ev_gettext("Customize Issue Listing Screen");

/* templates//help/customize_listing.tpl.html */
ev_gettext("This page allows you to dynamically configure the values displayed in the \n\"Status Change Date\" column in the issue listing screen, for a particular\nproject.\n<br /><br />\nThis column is useful to display the amount of time since the last change\nin status for each issue. For example, if issue #1234 is set to status\n'Closed', you could configure Eventum to display the difference\nin time between \"now\" and the date value stored in the closed date\nfield.\n<br /><br />\nSince the list of statuses available per project is dynamic and \ndatabase driven, this manual process is needed to associate a status\nto a date field coming from the database.");

/* templates//help/view_impact.tpl.html */
ev_gettext("Impact Analysis");

/* templates//help/email_blocking.tpl.html */
ev_gettext("Email Blocking");

/* templates//help/email_blocking.tpl.html */
ev_gettext("To prevent inappropriate emails reaching the notification list, only users that are assigned\nto the issue are allowed to email through Eventum. If an un-authorized\nuser sends an email to <i>issue-XXXX@example.com</i> it is converted into a note and\nstored for later use. This note can be converted into an email at a later date.");

/* templates//help/report_assignment.tpl.html */
ev_gettext("Assignment Field");

/* templates//help/report_assignment.tpl.html */
ev_gettext("This field is used to determine who should be assigned to this new \nissue. You are be able to assign a new issue to several persons at the\nsame time.\n<br /><br />\nIf you don't know who should be the assigned person for this new issue,\nassign it to your Project Lead.");

/* templates//help/report_category.tpl.html */
ev_gettext("Category Field");

/* templates//help/report_category.tpl.html */
ev_gettext("This field is used to categorize issues by a common denominator, such\nas 'Feature Request', 'Bug' or 'Support Inquiry'.\n<br /><br />\nNote: The values in this field can be changed by going in the administration\nsection of this application and editing the appropriate atributes of\na project. If you do not have the needed permissions to do so, please\ncontact your local Eventum administrator.");

/* templates//help/banner.tpl.html */
ev_gettext("Close Window");

/* templates//help/preferences.tpl.html */
ev_gettext("Account Preferences");

/* templates//help/preferences.tpl.html */
ev_gettext("This screen allows users to change their appropriate full name, account\npassword and email address. This address will be used by the system to\nsend email notifications whenever details about issues you are \nsubscribed to changes.");

/* templates//help/preferences.tpl.html */
ev_gettext("You may also set the appropriate timezone where you live in this \nscreen, and all of the software will adjust the dates displayed in\nthe system accordingly.");

/* templates//help/preferences.tpl.html */
ev_gettext("The default options for email notifications are used to pre-select\nthe notification related fields when you report a new issue, or \nsubscribe manually for changes in the issue details page.");

/* templates//help/scm_integration.tpl.html */
ev_gettext("SCM Integration");

/* templates//help/scm_integration.tpl.html */
ev_gettext("This feature allows your software development teams to integrate your\nSource Control Management system with your Issue Tracking System.");

/* templates//help/scm_integration.tpl.html */
ev_gettext("The integration is implemented in such a way that it will be forward\ncompatible with pretty much any SCM system, such as CVS. When entering\nthe required information for the checkout page and diff page input\nfields, use the following placeholders:");

/* templates//help/scm_integration.tpl.html */
ev_gettext("The CVS module name");

/* templates//help/scm_integration.tpl.html */
ev_gettext("The filename that was committed");

/* templates//help/scm_integration.tpl.html */
ev_gettext("The old revision of the file");

/* templates//help/scm_integration.tpl.html */
ev_gettext("The new revision of the file");

/* templates//help/scm_integration.tpl.html */
ev_gettext("As an example, using the <a href=\"http://www.horde.org/chora/\" class=\"link\" target=\"_chora\">Chora CVS viewer</a> [highly recommended] from the Horde project you\nwould usually have the following URL as the diff page:");

/* templates//help/scm_integration.tpl.html */
ev_gettext("http://example.com/chora/diff.php/module/filename.ext?r1=1.3&r2=1.4&ty=h");

/* templates//help/scm_integration.tpl.html */
ev_gettext("With that information in mind, the appropriate value to be entered in\nthe 'Checkout page' input field is:");

/* templates//help/index.tpl.html */
ev_gettext("Available Related Topics:");

/* templates//help/main.tpl.html */
ev_gettext("Available Help Topics");

/* templates//help/main.tpl.html */
ev_gettext("Please refer to the following help sections for more information on \nspecific parts of the application:");

/* templates//help/main.tpl.html */
ev_gettext("Listing / Searching for Issues");

/* templates//help/main.tpl.html */
ev_gettext("Reporting New Issues");

/* templates//help/main.tpl.html */
ev_gettext("Advanced Search / Creating Custom Queries");

/* templates//help/main.tpl.html */
ev_gettext("Associate Emails");

/* templates//help/main.tpl.html */
ev_gettext("Account Preferences");

/* templates//help/main.tpl.html */
ev_gettext("Viewing Issues");

/* templates//help/main.tpl.html */
ev_gettext("Email Notifications");

/* templates//help/main.tpl.html */
ev_gettext("Email Blocking");

/* templates//help/main.tpl.html */
ev_gettext("Configuration Parameters");

/* templates//help/main.tpl.html */
ev_gettext("SCM Integration");

/* templates//help/main.tpl.html */
ev_gettext("Usage Examples");

/* templates//help/main.tpl.html */
ev_gettext("Installation Instructions");

/* templates//help/main.tpl.html */
ev_gettext("Customize Issue Listing Screen");

/* templates//help/main.tpl.html */
ev_gettext("Link Filters");

/* templates//help/main.tpl.html */
ev_gettext("Edit Fields to Display");

/* templates//help/main.tpl.html */
ev_gettext("Segregate Reporters");

/* templates//help/main.tpl.html */
ev_gettext("User Permission Levels");

/* templates//help/scm_integration_installation.tpl.html */
ev_gettext("Installation Instructions");

/* templates//help/scm_integration_installation.tpl.html */
ev_gettext("The process_commits.pl script, which is available in the misc \nsub-directory in your Eventum installation directory, will need to be \ninstalled in your CVSROOT CVS module by following the procedure below:");

/* templates//help/scm_integration_installation.tpl.html */
ev_gettext("The first thing to do is to checkout the CVSROOT module from your CVS\nrepository:");

/* templates//help/scm_integration_installation.tpl.html */
ev_gettext("The command above will checkout and create the CVSROOT directory that\nyou will need to work with. Next, open the <b>loginfo</b> file and\nadd the following line:");

/* templates//help/scm_integration_installation.tpl.html */
ev_gettext("Replace %repository path% by the appropriate absolute path in your\nCVS server, such as /home/username/repository for instance. Also make\nsure to put the appropriate path to your Perl binary.");

/* templates//help/scm_integration_installation.tpl.html */
ev_gettext("You may also turn the parsing of commit messages for just a single CVS\nmodule by substituting the 'ALL' in the line above to the appropriate\nCVS module name, as in:");

/* templates//help/scm_integration_installation.tpl.html */
ev_gettext("The last step of this installation process is to login into the CVS\nserver and copy the process_cvs_commits.php script into the CVSROOT \ndirectory. Make sure you give the appropriate permissions to the \nscript.");

/* templates//help/report_release.tpl.html */
ev_gettext("Scheduled Release Field");

/* templates//help/report_release.tpl.html */
ev_gettext("This field is used to determine what the deadline should be for when\nthis new issue should be completed and resolved. If you don't know \nwhat the deadline should be for this new issue, leave the field as\n'un-scheduled', and a project manager will set it appropriately.");

/* templates//help/report_release.tpl.html */
ev_gettext("Note: The values in this field can be changed by going in the administration\nsection of this application and editing the appropriate atributes of\na project. If you do not have the needed permissions to do so, please\ncontact your local Eventum administrator.");

/* templates//help/report_estimated_dev_time.tpl.html */
ev_gettext("Estimated Development Time Field");

/* templates//help/report_estimated_dev_time.tpl.html */
ev_gettext("This field is used by the reporters of new issues to estimate the \ntotal development time for the issue. It is especially important as a \nmetrics tool to get a simple estimate of how much time each issue will\ntake from discovery, going through implementation and testing up until\nrelease time.");

/* templates//help/report_estimated_dev_time.tpl.html */
ev_gettext("This field can also be used as a way to check the estimation abilities\nof project managers against the impact analysis given by the \ndevelopers themselves. That is, the value entered by a project manager\nhere can be compared against the impact analysis / estimated \ndevelopment time entered by the developers, and this way get more \nexperience estimating the required time for new projects.");

/* templates//help/field_display.tpl.html */
ev_gettext("Edit Fields to Display");

/* templates//help/field_display.tpl.html */
ev_gettext("This page allows you to dynamically control which fields are displayed \nto users of a certain minimum role.\nFor example, you could use this page so that only users of the role \"<i>standard user</i>\" \n(and higher ranking roles) are able to set the category or \nrelease fields when reporting a new issue.");

/* templates//help/permission_levels.tpl.html */
ev_gettext("User Permission Levels");

/* templates//help/permission_levels.tpl.html */
ev_gettext("The following is a brief overview of the available user permission levels \nin Eventum:");

/* templates//help/permission_levels.tpl.html */
ev_gettext("Viewer");

/* templates//help/permission_levels.tpl.html */
ev_gettext("Allowed to view all issues on the projects associated to \nthis user; cannot create new issues or edit existing issues.");

/* templates//help/permission_levels.tpl.html */
ev_gettext("Reporter");

/* templates//help/permission_levels.tpl.html */
ev_gettext("Allowed to view all issues on the projects associated to \nthis user; Allowed to create new issues and to send emails on existing\nissues.");

/* templates//help/permission_levels.tpl.html */
ev_gettext("Customer");

/* templates//help/permission_levels.tpl.html */
ev_gettext("This is a special permission level reserved for the Customer\nIntegration API, which allows you to integrate Eventum with your CRM database. \nWhen this feature is enabled, this type of user can only access issues associated\nwith their own customer. Allowed to create new issues, update and send emails\nto existing issues.");

/* templates//help/permission_levels.tpl.html */
ev_gettext("Standard User");

/* templates//help/permission_levels.tpl.html */
ev_gettext("Allowed to view all issues on the projects associated to\nthis user; Allowed to create new issues, update existing issues, and to send\nemails and notes to existing issues.");

/* templates//help/permission_levels.tpl.html */
ev_gettext("Developer");

/* templates//help/permission_levels.tpl.html */
ev_gettext("Similar in every way to the above permission level, but \nthis extra level allows you to segregate users who will deal with issues, and\noverall normal staff users who do not handle issues themselves.");

/* templates//help/permission_levels.tpl.html */
ev_gettext("Manager");

/* templates//help/permission_levels.tpl.html */
ev_gettext("Allowed to view all issues on the projects associated to\nthis user; Allowed to create new issues, update existing issues, and to send\nemails and notes to existing issues. Also, this type of user is also allowed on\nthe special administration section of Eventum to tweak most project-level \nfeatures and options.");

/* templates//help/permission_levels.tpl.html */
ev_gettext("Administrator");

/* templates//help/permission_levels.tpl.html */
ev_gettext("This type of user has full access to Eventum, including\nthe low level configuration parameters available through the administration\ninterface.");

/* templates//help/column_display.tpl.html */
ev_gettext("Edit Columns to Display");

/* templates//help/column_display.tpl.html */
ev_gettext("This page allows you to dynamically control which columns are displayed on the list issues page.");

/* templates//help/column_display.tpl.html */
ev_gettext("You can set the minimum role required to view a column. For example, if you set the mimimum role for 'Category'\nto be 'Manager' anyone with a role lower then 'Manager' will not be able to see that column. To hide a column\nfrom all users, select 'Never Display'.");

/* templates//help/column_display.tpl.html */
ev_gettext("Please note that some columns may be hidden even if you specify they should be shown. For example, if no releases\nare defined in the system the 'Release' column will be hidden.");

/* templates//help/link_filters.tpl.html */
ev_gettext("Link Filters");

/* templates//help/link_filters.tpl.html */
ev_gettext("Link filters are used to replace text such as 'Bug #42' with an automatic\nlink to some external resource. It uses regular expressions to replace the text.\nSpecify the search pattern in the pattern field without delimiters. Specify the entire\nstring you would like to use as a replacement with $x to insert the matched text. For example:\n<br /><br />\nPattern: \"bug #(d+)\"<br />\nReplacement: \"&lt;a href=http://example.com/bug.php?id=$1&gt;Bug #$1&lt;/a&gt;\"");

/* templates//help/report_summary.tpl.html */
ev_gettext("Summary Field");

/* templates//help/report_summary.tpl.html */
ev_gettext("This field is used as a simple and descriptive title to this new\nissue. As a suggestion, it should be descriptive and short enough to\nbe used by other users to remember quickly what the issue was all\nabout.");

/* templates//tips/keyboard_shortcuts.tpl.html */
ev_gettext("You can switch to the 'Search' or 'Go' boxes quickly by using a\nspecial shortcut keystroke in your keyboard.<br />\n<br />\nUse the following shortcuts:<br />\n<br />\n<b>ALT-3</b> (hold 'ALT' key and press '3' one time) - to access the 'Search' box<br />\n<br />\n<b>ALT-4</b> (hold 'ALT' key and press '4' one time) - to access the 'Go' box");

/* templates//tips/custom_queries.tpl.html */
ev_gettext("You can create as many custom queries as you want through the\n<a class=\"link\" href=\"adv_search.php\">Advanced Search</a> interface.\nThere is also the ability to save and modify custom queries and load\nthem quickly from the Issue Listing screen.");

/* templates//tips/canned_responses.tpl.html */
ev_gettext("You can create canned email responses and use them when sending emails from the\nsystem. That is an useful feature when dealing with lots of issues that relate\nto the same problem.\n<br /><br />\nIf no canned email responses are available through the Email window, please\ncontact an user with the appropriate permissions (administrator or manager) to\nadd some for you.");

/* templates//mail_queue.tpl.html */
ev_gettext("Sorry, you do not have permission to view this page");

/* templates//mail_queue.tpl.html */
ev_gettext("Mail Queue for Issue #%1");

/* templates//mail_queue.tpl.html */
ev_gettext("Recipient");

/* templates//mail_queue.tpl.html */
ev_gettext("Queued Date");

/* templates//mail_queue.tpl.html */
ev_gettext("Status");

/* templates//mail_queue.tpl.html */
ev_gettext("Subject");

/* templates//mail_queue.tpl.html */
ev_gettext("No mail queue could be found.");

/* templates//clock_status.tpl.html */
ev_gettext("Thank you, your account clocked-in status was changed successfully.");

/* templates//clock_status.tpl.html */
ev_gettext("An error was found while trying to change your account clocked-in status.");

/* templates//quick_filter_form.tpl.html */
ev_gettext("Please select the custom filter to search against.");

/* templates//quick_filter_form.tpl.html */
ev_gettext("Keyword(s)");

/* templates//quick_filter_form.tpl.html */
ev_gettext("Customer Identity (i.e. \"Example Inc.\", \"johndoe@example.com\", 12345)");

/* templates//quick_filter_form.tpl.html */
ev_gettext("All Text (emails, notes, etc)");

/* templates//quick_filter_form.tpl.html */
ev_gettext("Search");

/* templates//quick_filter_form.tpl.html */
ev_gettext("Clear Filters");

/* templates//quick_filter_form.tpl.html */
ev_gettext("Assigned");

/* templates//quick_filter_form.tpl.html */
ev_gettext("Category");

/* templates//quick_filter_form.tpl.html */
ev_gettext("any");

/* templates//quick_filter_form.tpl.html */
ev_gettext("Priority");

/* templates//quick_filter_form.tpl.html */
ev_gettext("any");

/* templates//quick_filter_form.tpl.html */
ev_gettext("Status");

/* templates//quick_filter_form.tpl.html */
ev_gettext("any");

/* templates//quick_filter_form.tpl.html */
ev_gettext("quick search bar");

/* templates//quick_filter_form.tpl.html */
ev_gettext("Advanced Search");

/* templates//quick_filter_form.tpl.html */
ev_gettext("Saved Searches");

/* templates//new.tpl.html */
ev_gettext("There was an error creating your issue.");

/* templates//new.tpl.html */
ev_gettext("Thank you, the new issue was created successfully. Please choose from one of the options below");

/* templates//new.tpl.html */
ev_gettext("Thank you, the new issue was created successfully.");

/* templates//new.tpl.html */
ev_gettext("However, the following errors were encountered:");

/* templates//new.tpl.html */
ev_gettext("Please choose from one of the options below:");

/* templates//new.tpl.html */
ev_gettext("Open the Issue Details Page");

/* templates//new.tpl.html */
ev_gettext("Open the Issue Listing Page");

/* templates//new.tpl.html */
ev_gettext("Open the Emails Listing Page");

/* templates//new.tpl.html */
ev_gettext("Report a New Issue");

/* templates//new.tpl.html */
ev_gettext("Otherwise, you will be automatically redirected to the Issue Details Page in 5 seconds.");

/* templates//new.tpl.html */
ev_gettext("Warning: your issue is currently quarantined.\n                Please see the <a href=\"faq.php\">FAQ</a> for information regarding quarantined issues.");

/* templates//new.tpl.html */
ev_gettext("Category");

/* templates//new.tpl.html */
ev_gettext("Priority");

/* templates//new.tpl.html */
ev_gettext("Assignment");

/* templates//new.tpl.html */
ev_gettext("Summary");

/* templates//new.tpl.html */
ev_gettext("Initial Description");

/* templates//new.tpl.html */
ev_gettext("Estimated Dev. Time (only numbers)");

/* templates//new.tpl.html */
ev_gettext("Create New Issue");

/* templates//new.tpl.html */
ev_gettext("Current Project");

/* templates//new.tpl.html */
ev_gettext("Category");

/* templates//new.tpl.html */
ev_gettext("Please choose a category");

/* templates//new.tpl.html */
ev_gettext("Priority");

/* templates//new.tpl.html */
ev_gettext("Please choose a priority");

/* templates//new.tpl.html */
ev_gettext("Assignment");

/* templates//new.tpl.html */
ev_gettext("Group");

/* templates//new.tpl.html */
ev_gettext("Scheduled Release");

/* templates//new.tpl.html */
ev_gettext("un-scheduled");

/* templates//new.tpl.html */
ev_gettext("Summary");

/* templates//new.tpl.html */
ev_gettext("Initial Description");

/* templates//new.tpl.html */
ev_gettext("Estimated Dev. Time");

/* templates//new.tpl.html */
ev_gettext("Private");

/* templates//new.tpl.html */
ev_gettext("Add Files");

/* templates//new.tpl.html */
ev_gettext("Files");

/* templates//new.tpl.html */
ev_gettext("Note: The current maximum allowed upload file size is %1");

/* templates//new.tpl.html */
ev_gettext("Keep form open to report another issue");

/* templates//new.tpl.html */
ev_gettext("Submit");

/* templates//new.tpl.html */
ev_gettext("Reset");

/* templates//new.tpl.html */
ev_gettext("Required fields");

/* templates//bulk_update.tpl.html */
ev_gettext("Bulk Update Tool");

/* templates//bulk_update.tpl.html */
ev_gettext("Assignment");

/* templates//bulk_update.tpl.html */
ev_gettext("Status");

/* templates//bulk_update.tpl.html */
ev_gettext("Release");

/* templates//bulk_update.tpl.html */
ev_gettext("Priority");

/* templates//bulk_update.tpl.html */
ev_gettext("Category");

/* templates//bulk_update.tpl.html */
ev_gettext("Bulk Update");

/* templates//bulk_update.tpl.html */
ev_gettext("Reset");

/* templates//reports/weekly_data.tpl.html */
ev_gettext("Weekly");

/* templates//reports/weekly_data.tpl.html */
ev_gettext("Report");

/* templates//reports/weekly_data.tpl.html */
ev_gettext("issues worked on");

/* templates//reports/weekly_data.tpl.html */
ev_gettext("No issues touched this time period");

/* templates//reports/weekly_data.tpl.html */
ev_gettext("Issues Closed");

/* templates//reports/weekly_data.tpl.html */
ev_gettext("No issues closed this time period");

/* templates//reports/weekly_data.tpl.html */
ev_gettext("New Issues Assigned");

/* templates//reports/weekly_data.tpl.html */
ev_gettext("Total Issues");

/* templates//reports/weekly_data.tpl.html */
ev_gettext("Eventum Emails");

/* templates//reports/weekly_data.tpl.html */
ev_gettext("Other Emails");

/* templates//reports/weekly_data.tpl.html */
ev_gettext("Total Phone Calls");

/* templates//reports/weekly_data.tpl.html */
ev_gettext("Total Notes");

/* templates//reports/weekly_data.tpl.html */
ev_gettext("Phone Time Spent");

/* templates//reports/weekly_data.tpl.html */
ev_gettext("Email Time Spent");

/* templates//reports/weekly_data.tpl.html */
ev_gettext("Login Time Spent");

/* templates//reports/weekly_data.tpl.html */
ev_gettext("Total Time Spent");

/* templates//reports/open_issues.tpl.html */
ev_gettext("Showing all open issues older than ");

/* templates//reports/open_issues.tpl.html */
ev_gettext("days");

/* templates//reports/open_issues.tpl.html */
ev_gettext("Number of Days");

/* templates//reports/open_issues.tpl.html */
ev_gettext("Submit");

/* templates//reports/open_issues.tpl.html */
ev_gettext("Issue ID");

/* templates//reports/open_issues.tpl.html */
ev_gettext("Summary");

/* templates//reports/open_issues.tpl.html */
ev_gettext("Status");

/* templates//reports/open_issues.tpl.html */
ev_gettext("Time Spent");

/* templates//reports/open_issues.tpl.html */
ev_gettext("Created");

/* templates//reports/open_issues.tpl.html */
ev_gettext("Days and Hours Since");

/* templates//reports/open_issues.tpl.html */
ev_gettext("Last Update");

/* templates//reports/open_issues.tpl.html */
ev_gettext("Last Outgoing Msg");

/* templates//reports/tree.tpl.html */
ev_gettext("Available Reports");

/* templates//reports/tree.tpl.html */
ev_gettext("Issues");

/* templates//reports/tree.tpl.html */
ev_gettext("Issues by User");

/* templates//reports/tree.tpl.html */
ev_gettext("Open Issues By Assignee");

/* templates//reports/tree.tpl.html */
ev_gettext("Open Issues By Reporter");

/* templates//reports/tree.tpl.html */
ev_gettext("Weekly Report");

/* templates//reports/tree.tpl.html */
ev_gettext("Workload by time period");

/* templates//reports/tree.tpl.html */
ev_gettext("Email by time period");

/* templates//reports/tree.tpl.html */
ev_gettext("Custom Fields");

/* templates//reports/tree.tpl.html */
ev_gettext("Customer Profile Stats");

/* templates//reports/tree.tpl.html */
ev_gettext("Recent Activity");

/* templates//reports/tree.tpl.html */
ev_gettext("Workload By Date Range");

/* templates//reports/tree.tpl.html */
ev_gettext("Stalled Issues");

/* templates//reports/tree.tpl.html */
ev_gettext("Estimated Development Time");

/* templates//reports/issue_user.tpl.html */
ev_gettext("Issue ID");

/* templates//reports/issue_user.tpl.html */
ev_gettext("Summary");

/* templates//reports/issue_user.tpl.html */
ev_gettext("Status");

/* templates//reports/issue_user.tpl.html */
ev_gettext("Time Spent");

/* templates//reports/issue_user.tpl.html */
ev_gettext("Created");

/* templates//reports/workload_date_range.tpl.html */
ev_gettext("Workload by Date Range Report");

/* templates//reports/workload_date_range.tpl.html */
ev_gettext("Type");

/* templates//reports/workload_date_range.tpl.html */
ev_gettext("Interval");

/* templates//reports/workload_date_range.tpl.html */
ev_gettext("Start");

/* templates//reports/workload_date_range.tpl.html */
ev_gettext("End");

/* templates//reports/workload_date_range.tpl.html */
ev_gettext("Generate");

/* templates//reports/workload_date_range.tpl.html */
ev_gettext(" Warning: Some type and interval options, combined with large <br />\n    date ranges can produce extremely large graphs.");

/* templates//reports/workload_date_range.tpl.html */
ev_gettext("Day");

/* templates//reports/workload_date_range.tpl.html */
ev_gettext("day");

/* templates//reports/workload_date_range.tpl.html */
ev_gettext("Week");

/* templates//reports/workload_date_range.tpl.html */
ev_gettext("week");

/* templates//reports/workload_date_range.tpl.html */
ev_gettext("Month");

/* templates//reports/workload_date_range.tpl.html */
ev_gettext("month");

/* templates//reports/workload_date_range.tpl.html */
ev_gettext("Day of Week");

/* templates//reports/workload_date_range.tpl.html */
ev_gettext("dow");

/* templates//reports/workload_date_range.tpl.html */
ev_gettext("Week");

/* templates//reports/workload_date_range.tpl.html */
ev_gettext("week");

/* templates//reports/workload_date_range.tpl.html */
ev_gettext("Day of Month");

/* templates//reports/workload_date_range.tpl.html */
ev_gettext("dom");

/* templates//reports/workload_date_range.tpl.html */
ev_gettext("Month");

/* templates//reports/workload_date_range.tpl.html */
ev_gettext("month");

/* templates//reports/workload_date_range.tpl.html */
ev_gettext("Avg/Med/Max Issues/Emails");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("The current project does not have customer integration so this report can not be viewed.");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Customer Stats Report");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Date Range");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Sections to Display");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("From");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("year");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("mon");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("day");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("To");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Options");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Include expired contracts");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Customer");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("All");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Generate");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Feedback");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Red values indicate value is higher than the aggregate one.");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Blue values indicate value is lower than the aggregate one.");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Customers");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Issues");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Emails by Customers");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Emails by Staff");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Count");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Using CSC");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Issues in CSC");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Tot");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Avg");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Med");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Max");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Tot");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Avg");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Med");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Tot");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Avg");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Med");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Time To First Response");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Time To Close");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Min");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Avg");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Med");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Max");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Min");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Avg");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Med");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Max");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Support Level");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Time Tracking");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Total");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Avg");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Med");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Refers to the number of issues in eventum for the given support level or customer.\n    Average and median counts do not include customers who have never opened an issue.");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Refers to the number of emails sent by customers in eventum per issue. Does <b>not</b> include emails sent to general support mailbox.");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Refers to the number of emails sent by developers in eventum per issue. Does <b>not</b> include emails sent to general support mailbox.");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("Date issue was opened - Date issue was closed for all closed issues.");

/* templates//reports/customer_stats.tpl.html */
ev_gettext("All time tracking information for the given support level or customer. Issues without any time tracking data do not affect the average or median.");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Recent Activity");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Recent Activity Report");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Report Type");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Recent");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Date Range");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Activity Type");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Activity in Past");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Start");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("End");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Developer");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("All");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Sort Order");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Ascending");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Descending");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Generate");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Recent Phone Calls");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Issue ID");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Customer");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Date");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Developer");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Type");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Line");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Description");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("No Phone Calls Found");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Recent Notes");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Issue ID");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Customer");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Posted Date");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("User");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Title");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("No Notes Found");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Recent Emails");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Issue ID");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Customer");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("From");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("To");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Date");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Subject");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("sent to notification list");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("No Emails Found");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Recent Drafts");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Issue ID");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Customer");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Status");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("From");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("To");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Date");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Subject");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("No Drafts Found");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Recent Time Entries");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Issue ID");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Customer");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Date of Work");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("User");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Time Spent");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Category");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Summary");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("No Time Entries Found");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Recent Reminder Actions");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Issue ID");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Customer");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Date Triggered");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("Title");

/* templates//reports/recent_activity.tpl.html */
ev_gettext("No Reminder Entries Found");

/* templates//reports/estimated_dev_time.tpl.html */
ev_gettext("Estimated Development Time by Category");

/* templates//reports/estimated_dev_time.tpl.html */
ev_gettext("Based on all open issue in Eventum for <b>%1</b>.");

/* templates//reports/estimated_dev_time.tpl.html */
ev_gettext("Category");

/* templates//reports/estimated_dev_time.tpl.html */
ev_gettext("Estimated time (Hours)");

/* templates//reports/workload_time_period.tpl.html */
ev_gettext("Email Workload by Time of day");

/* templates//reports/workload_time_period.tpl.html */
ev_gettext("Based on all issues recorded in Eventum since start to present.");

/* templates//reports/workload_time_period.tpl.html */
ev_gettext("Workload by Time of day");

/* templates//reports/workload_time_period.tpl.html */
ev_gettext("Based on all issues recorded in Eventum since start to present.\n        Actions are any event that shows up in the history of an issue, such as a user or a developer updating an issue, uploading a file, sending an email, etc.");

/* templates//reports/workload_time_period.tpl.html */
ev_gettext("Time Period");

/* templates//reports/workload_time_period.tpl.html */
ev_gettext("(GMT)");

/* templates//reports/workload_time_period.tpl.html */
ev_gettext("Developer");

/* templates//reports/workload_time_period.tpl.html */
ev_gettext("Emails");

/* templates//reports/workload_time_period.tpl.html */
ev_gettext("Actions");

/* templates//reports/workload_time_period.tpl.html */
ev_gettext("Customer");

/* templates//reports/workload_time_period.tpl.html */
ev_gettext("Emails");

/* templates//reports/workload_time_period.tpl.html */
ev_gettext("Actions");

/* templates//reports/workload_time_period.tpl.html */
ev_gettext("Time Period");

/* templates//reports/stalled_issues.tpl.html */
ev_gettext("Stalled Issues Report");

/* templates//reports/stalled_issues.tpl.html */
ev_gettext("Stalled Issues Report");

/* templates//reports/stalled_issues.tpl.html */
ev_gettext("Show Issues with no Response Between");

/* templates//reports/stalled_issues.tpl.html */
ev_gettext("Developers");

/* templates//reports/stalled_issues.tpl.html */
ev_gettext("Status");

/* templates//reports/stalled_issues.tpl.html */
ev_gettext("Sort Order");

/* templates//reports/stalled_issues.tpl.html */
ev_gettext("Ascending");

/* templates//reports/stalled_issues.tpl.html */
ev_gettext("Ascending");

/* templates//reports/stalled_issues.tpl.html */
ev_gettext("Descending");

/* templates//reports/stalled_issues.tpl.html */
ev_gettext("Descending");

/* templates//reports/stalled_issues.tpl.html */
ev_gettext("Generate");

/* templates//reports/stalled_issues.tpl.html */
ev_gettext("Issue ID");

/* templates//reports/stalled_issues.tpl.html */
ev_gettext("Summary");

/* templates//reports/stalled_issues.tpl.html */
ev_gettext("Status");

/* templates//reports/stalled_issues.tpl.html */
ev_gettext("Time Spent");

/* templates//reports/stalled_issues.tpl.html */
ev_gettext("Created");

/* templates//reports/stalled_issues.tpl.html */
ev_gettext("Last Response");

/* templates//reports/stalled_issues.tpl.html */
ev_gettext("Days and Hours Since");

/* templates//reports/stalled_issues.tpl.html */
ev_gettext("Last Update");

/* templates//reports/stalled_issues.tpl.html */
ev_gettext("Last Outgoing Msg");

/* templates//reports/stalled_issues.tpl.html */
ev_gettext("view issue details");

/* templates//reports/stalled_issues.tpl.html */
ev_gettext("view issue details");

/* templates//reports/weekly.tpl.html */
ev_gettext("Weekly Report");

/* templates//reports/weekly.tpl.html */
ev_gettext("Report Type:");

/* templates//reports/weekly.tpl.html */
ev_gettext("Weekly");

/* templates//reports/weekly.tpl.html */
ev_gettext("Date Range");

/* templates//reports/weekly.tpl.html */
ev_gettext("Generate");

/* templates//reports/weekly.tpl.html */
ev_gettext("Week");

/* templates//reports/weekly.tpl.html */
ev_gettext("Start");

/* templates//reports/weekly.tpl.html */
ev_gettext("End:");

/* templates//reports/weekly.tpl.html */
ev_gettext("Developer");

/* templates//reports/weekly.tpl.html */
ev_gettext("Options");

/* templates//reports/weekly.tpl.html */
ev_gettext("Separate Closed Issues");

/* templates//reports/weekly.tpl.html */
ev_gettext("Ignore Issue Status Changes");

/* templates//reports/custom_fields.tpl.html */
ev_gettext("Please select the custom field that you would like to generate a report against.");

/* templates//reports/custom_fields.tpl.html */
ev_gettext("Custom Fields Report");

/* templates//reports/custom_fields.tpl.html */
ev_gettext("Field to Graph");

/* templates//reports/custom_fields.tpl.html */
ev_gettext("Options to Graph");

/* templates//reports/custom_fields.tpl.html */
ev_gettext("Group By");

/* templates//reports/custom_fields.tpl.html */
ev_gettext("Issue");

/* templates//reports/custom_fields.tpl.html */
ev_gettext("Customer");

/* templates//reports/custom_fields.tpl.html */
ev_gettext("Generate");

/* templates//reports/custom_fields.tpl.html */
ev_gettext("Percentages may not add up to exactly 100% due to rounding");

/* templates//reports/custom_fields.tpl.html */
ev_gettext("Issues/Customers matching criteria");

/* templates//reports/custom_fields.tpl.html */
ev_gettext("Customer");

/* templates//reports/custom_fields.tpl.html */
ev_gettext("Issue Count");

/* templates//reports/custom_fields.tpl.html */
ev_gettext("Issue ID");

/* templates//reports/custom_fields.tpl.html */
ev_gettext("Summary");

/* templates//reports/custom_fields.tpl.html */
ev_gettext("No data found");

/* templates//adv_search.tpl.html */
ev_gettext("Advanced Search");

/* templates//adv_search.tpl.html */
ev_gettext("Please enter the title for this saved search.");

/* templates//adv_search.tpl.html */
ev_gettext("Please choose which entries need to be removed.");

/* templates//adv_search.tpl.html */
ev_gettext("This action will permanently delete the selected entries.");

/* templates//adv_search.tpl.html */
ev_gettext("Advanced Search");

/* templates//adv_search.tpl.html */
ev_gettext("Keyword(s)");

/* templates//adv_search.tpl.html */
ev_gettext("Customer Identity (i.e. \"Example Inc.\", \"johndoe@example.com\", 12345)");

/* templates//adv_search.tpl.html */
ev_gettext("All Text (emails, notes, etc)");

/* templates//adv_search.tpl.html */
ev_gettext("Assigned");

/* templates//adv_search.tpl.html */
ev_gettext("Category");

/* templates//adv_search.tpl.html */
ev_gettext("any");

/* templates//adv_search.tpl.html */
ev_gettext("Priority");

/* templates//adv_search.tpl.html */
ev_gettext("any");

/* templates//adv_search.tpl.html */
ev_gettext("Status");

/* templates//adv_search.tpl.html */
ev_gettext("any");

/* templates//adv_search.tpl.html */
ev_gettext("Reporter");

/* templates//adv_search.tpl.html */
ev_gettext("Any");

/* templates//adv_search.tpl.html */
ev_gettext("Release");

/* templates//adv_search.tpl.html */
ev_gettext("any");

/* templates//adv_search.tpl.html */
ev_gettext("Hide Closed Issues");

/* templates//adv_search.tpl.html */
ev_gettext("Rows Per Page:");

/* templates//adv_search.tpl.html */
ev_gettext("ALL");

/* templates//adv_search.tpl.html */
ev_gettext("Sort By");

/* templates//adv_search.tpl.html */
ev_gettext("Priority");

/* templates//adv_search.tpl.html */
ev_gettext("Issue ID");

/* templates//adv_search.tpl.html */
ev_gettext("Status");

/* templates//adv_search.tpl.html */
ev_gettext("Summary");

/* templates//adv_search.tpl.html */
ev_gettext("Last Action Date");

/* templates//adv_search.tpl.html */
ev_gettext("Sort Order:");

/* templates//adv_search.tpl.html */
ev_gettext("ascending");

/* templates//adv_search.tpl.html */
ev_gettext("descending");

/* templates//adv_search.tpl.html */
ev_gettext("Show Issues in Which I Am:");

/* templates//adv_search.tpl.html */
ev_gettext("Authorized to Send Emails");

/* templates//adv_search.tpl.html */
ev_gettext("In Notification List");

/* templates//adv_search.tpl.html */
ev_gettext("Show date fields to search by");

/* templates//adv_search.tpl.html */
ev_gettext("Created");

/* templates//adv_search.tpl.html */
ev_gettext("Greater Than");

/* templates//adv_search.tpl.html */
ev_gettext("Less Than");

/* templates//adv_search.tpl.html */
ev_gettext("Between");

/* templates//adv_search.tpl.html */
ev_gettext("In Past");

/* templates//adv_search.tpl.html */
ev_gettext("hours");

/* templates//adv_search.tpl.html */
ev_gettext("Last Updated");

/* templates//adv_search.tpl.html */
ev_gettext("Greater Than");

/* templates//adv_search.tpl.html */
ev_gettext("Less Than");

/* templates//adv_search.tpl.html */
ev_gettext("Between");

/* templates//adv_search.tpl.html */
ev_gettext("Is Null");

/* templates//adv_search.tpl.html */
ev_gettext("In Past");

/* templates//adv_search.tpl.html */
ev_gettext("hours");

/* templates//adv_search.tpl.html */
ev_gettext("Last Updated");

/* templates//adv_search.tpl.html */
ev_gettext("End date");

/* templates//adv_search.tpl.html */
ev_gettext("First Response by Staff");

/* templates//adv_search.tpl.html */
ev_gettext("Greater Than");

/* templates//adv_search.tpl.html */
ev_gettext("Less Than");

/* templates//adv_search.tpl.html */
ev_gettext("Between");

/* templates//adv_search.tpl.html */
ev_gettext("Is Null");

/* templates//adv_search.tpl.html */
ev_gettext("In Past");

/* templates//adv_search.tpl.html */
ev_gettext("hours");

/* templates//adv_search.tpl.html */
ev_gettext("First Response By Staff");

/* templates//adv_search.tpl.html */
ev_gettext("End date");

/* templates//adv_search.tpl.html */
ev_gettext("Last Response by Staff");

/* templates//adv_search.tpl.html */
ev_gettext("Greater Than");

/* templates//adv_search.tpl.html */
ev_gettext("Less Than");

/* templates//adv_search.tpl.html */
ev_gettext("Between");

/* templates//adv_search.tpl.html */
ev_gettext("Is Null");

/* templates//adv_search.tpl.html */
ev_gettext("In Past");

/* templates//adv_search.tpl.html */
ev_gettext("hours");

/* templates//adv_search.tpl.html */
ev_gettext("Last Response by Staff");

/* templates//adv_search.tpl.html */
ev_gettext("End date");

/* templates//adv_search.tpl.html */
ev_gettext("Status Closed");

/* templates//adv_search.tpl.html */
ev_gettext("Greater Than");

/* templates//adv_search.tpl.html */
ev_gettext("Less Than");

/* templates//adv_search.tpl.html */
ev_gettext("Between");

/* templates//adv_search.tpl.html */
ev_gettext("Is Null");

/* templates//adv_search.tpl.html */
ev_gettext("In Past");

/* templates//adv_search.tpl.html */
ev_gettext("hours");

/* templates//adv_search.tpl.html */
ev_gettext("Status Closed");

/* templates//adv_search.tpl.html */
ev_gettext("End date");

/* templates//adv_search.tpl.html */
ev_gettext("Show additional fields to search by");

/* templates//adv_search.tpl.html */
ev_gettext("Run Search");

/* templates//adv_search.tpl.html */
ev_gettext("Reset");

/* templates//adv_search.tpl.html */
ev_gettext("Search Title");

/* templates//adv_search.tpl.html */
ev_gettext("Global Search");

/* templates//adv_search.tpl.html */
ev_gettext("Saved Searches");

/* templates//adv_search.tpl.html */
ev_gettext("edit this custom search");

/* templates//adv_search.tpl.html */
ev_gettext("global filter");

/* templates//adv_search.tpl.html */
ev_gettext("RSS feed for this custom search");

/* templates//adv_search.tpl.html */
ev_gettext("All");

/* templates//adv_search.tpl.html */
ev_gettext("No custom searches could be found.");

/* templates//help_link.tpl.html */
ev_gettext("get context sensitive help");

/* templates//offline.tpl.html */
ev_gettext("Database Error");

/* templates//offline.tpl.html */
ev_gettext("There seems to be a problem connecting to the database server specified in your configuration file. Please contact your local system administrator for further assistance.");

/* templates//offline.tpl.html */
ev_gettext("There seems to be a problem finding the required database tables in the database server specified in your configuration file. Please contact your local system administrator for further assistance.");

/* templates//view_headers.tpl.html */
ev_gettext("View Email Raw Headers");

/* templates//view_headers.tpl.html */
ev_gettext("Close");

/* templates//view_headers.tpl.html */
ev_gettext("Close");

/* templates//view.tpl.html */
ev_gettext("Error: The issue #%1 could not be found.");

/* templates//view.tpl.html */
ev_gettext("Go Back");

/* templates//view.tpl.html */
ev_gettext("Sorry, you do not have the required privileges to view this issue.");

/* templates//view.tpl.html */
ev_gettext("Go Back");

/* templates//error_icon.tpl.html */
ev_gettext("error condition detected");

/* templates//error_icon.tpl.html */
ev_gettext("error condition detected");

/* templates//checkins.tpl.html */
ev_gettext("Please choose which entries need to be removed.");

/* templates//checkins.tpl.html */
ev_gettext("This action will permanently delete the selected entries.");

/* templates//checkins.tpl.html */
ev_gettext("SCM Integration - Checkins");

/* templates//checkins.tpl.html */
ev_gettext("Back to Top");

/* templates//checkins.tpl.html */
ev_gettext("Date");

/* templates//checkins.tpl.html */
ev_gettext("User");

/* templates//checkins.tpl.html */
ev_gettext("Module / Directory");

/* templates//checkins.tpl.html */
ev_gettext("File");

/* templates//checkins.tpl.html */
ev_gettext("Commit Message");

/* templates//checkins.tpl.html */
ev_gettext("see the source of revision %1 of %2");

/* templates//checkins.tpl.html */
ev_gettext("see the diff to revision %1");

/* templates//checkins.tpl.html */
ev_gettext("diff to %1");

/* templates//checkins.tpl.html */
ev_gettext("No checkins could be found.");

/* templates//checkins.tpl.html */
ev_gettext("All");

/* templates//checkins.tpl.html */
ev_gettext("Remove Selected");

/* templates//history.tpl.html */
ev_gettext("History of Changes to Issue");

/* templates//history.tpl.html */
ev_gettext("Date");

/* templates//history.tpl.html */
ev_gettext("Summary");

/* templates//history.tpl.html */
ev_gettext("No changes could be found.");

/* templates//history.tpl.html */
ev_gettext("Close");

/* templates//history.tpl.html */
ev_gettext("History of Reminders Triggered for Issue");

/* templates//history.tpl.html */
ev_gettext("Date");

/* templates//history.tpl.html */
ev_gettext("Triggered Action");

/* templates//history.tpl.html */
ev_gettext("No reminders could be found.");

/* templates//history.tpl.html */
ev_gettext("Close");

/* templates//associate.tpl.html */
ev_gettext("An error occurred while trying to associate the selected email message");

/* templates//associate.tpl.html */
ngettext("Thank you, the selected email message was associated successfully.","Thank you, the selected email messages were associated successfully.",x);

/* templates//associate.tpl.html */
ev_gettext("Continue");

/* templates//associate.tpl.html */
ev_gettext("Warning: Unknown Contacts Found");

/* templates//associate.tpl.html */
ev_gettext("The following addresses could not be matched against the system user records:");

/* templates//associate.tpl.html */
ev_gettext("Please make sure you have selected the correct email messages to associate.");

/* templates//associate.tpl.html */
ev_gettext("Close Window");

/* templates//associate.tpl.html */
ev_gettext("Warning: Unknown contacts were found in the selected email messages. Please make sure you have selected the correct email messages to associate.");

/* templates//associate.tpl.html */
ngettext("Associate Email Message to Issue #%1","Associate Email Messages to Issue #%1",x);

/* templates//associate.tpl.html */
ngettext("Please choose one of the following actions to take in regards to the selected email message","Please choose one of the following actions to take in regards to the selected email messages",x);

/* templates//associate.tpl.html */
ev_gettext("Save Message");

/* templates//associate.tpl.html */
ev_gettext("as");

/* templates//associate.tpl.html */
ev_gettext("an");

/* templates//associate.tpl.html */
ngettext("<b>NOTE:</b> Email will be broadcasted to the full notification list, including any customers, if this option is chosen.","<b>NOTE:</b> Emails will be broadcasted to the full notification list, including any customers, if this option is chosen.",x);

/* templates//associate.tpl.html */
ngettext("Save Message as Reference Email","Save Message as Reference Emails",x);

/* templates//associate.tpl.html */
ngettext("<b>NOTE:</b> Email will <b>NOT</b> be sent to the notification list, if this option if chosen. This is useful as way to backload a set of emails into an existing issue.","<b>NOTE:</b> Emails will <b>NOT</b> be sent to the notification list, if this option if chosen. This is useful as way to backload a set of emails into an existing issue.",x);

/* templates//associate.tpl.html */
ngettext("Save Message as an Internal Note","Save Messages as an Internal Notes",x);

/* templates//associate.tpl.html */
ngettext("<b>NOTE:</b> Email will be saved as a note and broadcasted only to staff users.","<b>NOTE:</b> Emails will be saved as notes and broadcasted only to staff users.",x);

/* templates//associate.tpl.html */
ev_gettext("Continue");

/* templates//list.tpl.html */
ev_gettext("Please choose which issues to update.");

/* templates//list.tpl.html */
ev_gettext("Please choose new values for the select issues");

/* templates//list.tpl.html */
ev_gettext("Warning: If you continue, you will change the ");

/* templates//list.tpl.html */
ev_gettext("for all selected issues. Are you sure you want to continue?");

/* templates//list.tpl.html */
ev_gettext("Search Results");

/* templates//list.tpl.html */
ev_gettext("issues found");

/* templates//list.tpl.html */
ev_gettext("shown");

/* templates//list.tpl.html */
ev_gettext("hide/show");

/* templates//list.tpl.html */
ev_gettext("hide / show the quick search form");

/* templates//list.tpl.html */
ev_gettext("quick search");

/* templates//list.tpl.html */
ev_gettext("hide / show the advanced search form");

/* templates//list.tpl.html */
ev_gettext("advanced search");

/* templates//list.tpl.html */
ev_gettext("current filters");

/* templates//list.tpl.html */
ev_gettext("bulk update tool");

/* templates//list.tpl.html */
ev_gettext("All");

/* templates//list.tpl.html */
ev_gettext("sort by");

/* templates//list.tpl.html */
ev_gettext("sort by");

/* templates//list.tpl.html */
ev_gettext("Summary");

/* templates//list.tpl.html */
ev_gettext("sort by summary");

/* templates//list.tpl.html */
ev_gettext("Export Data:");

/* templates//list.tpl.html */
ev_gettext("generate excel-friendly report");

/* templates//list.tpl.html */
ev_gettext("Export to Excel");

/* templates//list.tpl.html */
ev_gettext("sort by");

/* templates//list.tpl.html */
ev_gettext("sort by");

/* templates//list.tpl.html */
ev_gettext("view issue details");

/* templates//list.tpl.html */
ev_gettext("view issue details");

/* templates//list.tpl.html */
ev_gettext("No issues could be found.");

/* templates//list.tpl.html */
ev_gettext("All");

/* templates//list.tpl.html */
ev_gettext("Go");

/* templates//list.tpl.html */
ev_gettext("Rows per Page:");

/* templates//list.tpl.html */
ev_gettext("ALL");

/* templates//list.tpl.html */
ev_gettext("Set");

/* templates//list.tpl.html */
ev_gettext("Hide Closed Issues");

/* templates//update_form.tpl.html */
ev_gettext("Sorry, an error happened while trying to run your query.");

/* templates//update_form.tpl.html */
ev_gettext("Thank you, issue #%1 was updated successfully.");

/* templates//update_form.tpl.html */
ev_gettext("Also, all issues that are marked as duplicates from this one were updated as well.");

/* templates//update_form.tpl.html */
ev_gettext("Return to Issue #%1 Details Page");

/* templates//update_form.tpl.html */
ev_gettext("Please enter the summary for this issue.");

/* templates//update_form.tpl.html */
ev_gettext("Please enter the description for this issue.");

/* templates//update_form.tpl.html */
ev_gettext("Percentage complete should be between 0 and 100");

/* templates//update_form.tpl.html */
ev_gettext("Please select an assignment for this issue");

/* templates//update_form.tpl.html */
ev_gettext("Warning: All changes to this issue will be lost if you continue and close this issue.");

/* templates//update_form.tpl.html */
ev_gettext("Note: Project automatically switched to '%1' from '%2'.");

/* templates//update_form.tpl.html */
ev_gettext("Update Issue Overview");

/* templates//update_form.tpl.html */
ev_gettext("edit the authorized repliers list for this issue");

/* templates//update_form.tpl.html */
ev_gettext("Edit Authorized Replier List");

/* templates//update_form.tpl.html */
ev_gettext("Edit Notification List");

/* templates//update_form.tpl.html */
ev_gettext("History of Changes");

/* templates//update_form.tpl.html */
ev_gettext("Category:");

/* templates//update_form.tpl.html */
ev_gettext("Status:");

/* templates//update_form.tpl.html */
ev_gettext("Notification List:");

/* templates//update_form.tpl.html */
ev_gettext("Status:");

/* templates//update_form.tpl.html */
ev_gettext("Submitted Date:");

/* templates//update_form.tpl.html */
ev_gettext("Priority:");

/* templates//update_form.tpl.html */
ev_gettext("Update Date:");

/* templates//update_form.tpl.html */
ev_gettext("Associated Issues:");

/* templates//update_form.tpl.html */
ev_gettext("Reporter:");

/* templates//update_form.tpl.html */
ev_gettext("Expected Resolution Date:");

/* templates//update_form.tpl.html */
ev_gettext("Scheduled Release:");

/* templates//update_form.tpl.html */
ev_gettext("Percentage Complete:");

/* templates//update_form.tpl.html */
ev_gettext("Estimated Dev. Time:");

/* templates//update_form.tpl.html */
ev_gettext("in hours");

/* templates//update_form.tpl.html */
ev_gettext("Assignment:");

/* templates//update_form.tpl.html */
ev_gettext("Keep Current Assignments:");

/* templates//update_form.tpl.html */
ev_gettext("Change Assignments:");

/* templates//update_form.tpl.html */
ev_gettext("Clear Selections");

/* templates//update_form.tpl.html */
ev_gettext("Current Selections:");

/* templates//update_form.tpl.html */
ev_gettext("Authorized Repliers:");

/* templates//update_form.tpl.html */
ev_gettext("Staff:");

/* templates//update_form.tpl.html */
ev_gettext("Other:");

/* templates//update_form.tpl.html */
ev_gettext("Group:");

/* templates//update_form.tpl.html */
ev_gettext("yes");

/* templates//update_form.tpl.html */
ev_gettext("Summary:");

/* templates//update_form.tpl.html */
ev_gettext("Description:");

/* templates//update_form.tpl.html */
ev_gettext("Private:");

/* templates//update_form.tpl.html */
ev_gettext("Trigger Reminders:");

/* templates//update_form.tpl.html */
ev_gettext("Update");

/* templates//update_form.tpl.html */
ev_gettext("Cancel Update");

/* templates//update_form.tpl.html */
ev_gettext("Reset");

/* templates//update_form.tpl.html */
ev_gettext("Close Issue");

/* templates//support_emails.tpl.html */
ev_gettext("Please choose which entries need to be disassociated with the current issue.");

/* templates//support_emails.tpl.html */
ev_gettext("This action will remove the association of the selected entries to the current issue.");

/* templates//support_emails.tpl.html */
ev_gettext("Associated Emails");

/* templates//support_emails.tpl.html */
ev_gettext("Back to Top");

/* templates//support_emails.tpl.html */
ev_gettext("view the history of sent emails");

/* templates//support_emails.tpl.html */
ev_gettext("Mail Queue Log");

/* templates//support_emails.tpl.html */
ev_gettext("All");

/* templates//support_emails.tpl.html */
ev_gettext("Reply");

/* templates//support_emails.tpl.html */
ev_gettext("From");

/* templates//support_emails.tpl.html */
ev_gettext("Recipients");

/* templates//support_emails.tpl.html */
ev_gettext("Date");

/* templates//support_emails.tpl.html */
ev_gettext("Subject");

/* templates//support_emails.tpl.html */
ev_gettext("reply to this email");

/* templates//support_emails.tpl.html */
ev_gettext("sent to notification list");

/* templates//support_emails.tpl.html */
ev_gettext("No associated emails could be found.");

/* templates//support_emails.tpl.html */
ev_gettext("All");

/* templates//support_emails.tpl.html */
ev_gettext("Disassociate Selected");

/* templates//support_emails.tpl.html */
ev_gettext("Send Email");

/* templates//permission_denied.tpl.html */
ev_gettext("Sorry, you do not have permission to access this page.");

/* templates//permission_denied.tpl.html */
ev_gettext("Go Back");

/* templates//duplicate.tpl.html */
ev_gettext("Sorry, an error happened while trying to run your query.");

/* templates//duplicate.tpl.html */
ev_gettext("Thank you, the issue was marked as a duplicate successfully. Please choose \n            from one of the options below:");

/* templates//duplicate.tpl.html */
ev_gettext("Open the Issue Details Page");

/* templates//duplicate.tpl.html */
ev_gettext("Open the Issue Listing Page");

/* templates//duplicate.tpl.html */
ev_gettext("Open the Emails Listing Page");

/* templates//duplicate.tpl.html */
ev_gettext("Otherwise, you will be automatically redirected to the Issue Details Page in 5 seconds.");

/* templates//duplicate.tpl.html */
ev_gettext("Please choose the duplicated issue.");

/* templates//duplicate.tpl.html */
ev_gettext("Mark Issue as Duplicate");

/* templates//duplicate.tpl.html */
ev_gettext("Issue ID:");

/* templates//duplicate.tpl.html */
ev_gettext("Duplicated Issue:");

/* templates//duplicate.tpl.html */
ev_gettext("Please select an issue");

/* templates//duplicate.tpl.html */
ev_gettext("Comments:");

/* templates//duplicate.tpl.html */
ev_gettext("Back");

/* templates//duplicate.tpl.html */
ev_gettext("Mark Issue as Duplicate");

/* templates//duplicate.tpl.html */
ev_gettext("Required fields");

/* templates//post_note.tpl.html */
ev_gettext("An error occurred while trying to run your query");

/* templates//post_note.tpl.html */
ev_gettext("Thank you, the internal note was posted successfully.");

/* templates//post_note.tpl.html */
ev_gettext("Continue");

/* templates//post_note.tpl.html */
ev_gettext("Please enter the title of this note.");

/* templates//post_note.tpl.html */
ev_gettext("Please enter the message body of this note.");

/* templates//post_note.tpl.html */
ev_gettext("Post New Internal Note");

/* templates//post_note.tpl.html */
ev_gettext("From:");

/* templates//post_note.tpl.html */
ev_gettext("Recipients:");

/* templates//post_note.tpl.html */
ev_gettext("Notification List");

/* templates//post_note.tpl.html */
ev_gettext("Title:");

/* templates//post_note.tpl.html */
ev_gettext("Extra Note Recipients:");

/* templates//post_note.tpl.html */
ev_gettext("Clear Selections");

/* templates//post_note.tpl.html */
ev_gettext("Add Extra Recipients To Notification List?");

/* templates//post_note.tpl.html */
ev_gettext("yes");

/* templates//post_note.tpl.html */
ev_gettext("Yes");

/* templates//post_note.tpl.html */
ev_gettext("no");

/* templates//post_note.tpl.html */
ev_gettext("No");

/* templates//post_note.tpl.html */
ev_gettext("New Status for Issue");

/* templates//post_note.tpl.html */
ev_gettext("Time Spent:");

/* templates//post_note.tpl.html */
ev_gettext("Post Internal Note");

/* templates//post_note.tpl.html */
ev_gettext("Cancel");

/* templates//post_note.tpl.html */
ev_gettext("yes");

/* templates//post_note.tpl.html */
ev_gettext("Add Email Signature");

/* templates//post_note.tpl.html */
ev_gettext("Required fields");

/* templates//close.tpl.html */
ev_gettext("Error: The issue #%1 could not be found.");

/* templates//close.tpl.html */
ev_gettext("Go Back");

/* templates//close.tpl.html */
ev_gettext("Sorry, an error happened while trying to run your query.");

/* templates//close.tpl.html */
ev_gettext("Thank you, the issue was closed successfully. Please choose from one of the options below:");

/* templates//close.tpl.html */
ev_gettext("Open the Issue Details Page");

/* templates//close.tpl.html */
ev_gettext("Open the Issue Listing Page");

/* templates//close.tpl.html */
ev_gettext("Open the Emails Listing Page");

/* templates//close.tpl.html */
ev_gettext("Please choose the new status for this issue.");

/* templates//close.tpl.html */
ev_gettext("Please enter the reason for closing this issue.");

/* templates//close.tpl.html */
ev_gettext("Please enter integers (or floating point numbers) on the time spent field.");

/* templates//close.tpl.html */
ev_gettext("Please choose the time tracking category for this new entry.");

/* templates//close.tpl.html */
ev_gettext("This customer has a per incident contract. You have chosen not to redeem any incidents. Press 'OK' to confirm or 'Cancel' to revise.");

/* templates//close.tpl.html */
ev_gettext("Close Issue");

/* templates//close.tpl.html */
ev_gettext("Issue ID:");

/* templates//close.tpl.html */
ev_gettext("Status:");

/* templates//close.tpl.html */
ev_gettext("Please choose a status");

/* templates//close.tpl.html */
ev_gettext("Resolution:");

/* templates//close.tpl.html */
ev_gettext("Send Notification About Issue Being Closed?");

/* templates//close.tpl.html */
ev_gettext("Send Notification To:");

/* templates//close.tpl.html */
ev_gettext("Internal Users");

/* templates//close.tpl.html */
ev_gettext("All");

/* templates//close.tpl.html */
ev_gettext("Reason for closing issue:");

/* templates//close.tpl.html */
ev_gettext("Incident Types to Redeem:");

/* templates//close.tpl.html */
ev_gettext("Time Spent:");

/* templates//close.tpl.html */
ev_gettext("Time Category:");

/* templates//close.tpl.html */
ev_gettext("Please choose a category");

/* templates//close.tpl.html */
ev_gettext("Back");

/* templates//close.tpl.html */
ev_gettext("Close Issue");

/* templates//popup.tpl.html */
ev_gettext("An error occurred while trying to run your query");

/* templates//popup.tpl.html */
ev_gettext("Please enter the note text on the input box below.");

/* templates//popup.tpl.html */
ev_gettext("Thank you, the new note was created and associated with the issue below.");

/* templates//popup.tpl.html */
ev_gettext("An error occurred while trying to run your query");

/* templates//popup.tpl.html */
ev_gettext("You do not have permission to delete this note.");

/* templates//popup.tpl.html */
ev_gettext("Thank you, the note was removed successfully.");

/* templates//popup.tpl.html */
ev_gettext("An error occurred while trying to run your query");

/* templates//popup.tpl.html */
ev_gettext("Thank you, the time tracking entry was removed successfully.");

/* templates//popup.tpl.html */
ev_gettext("Thank you, the selected issues were updated successfully.");

/* templates//popup.tpl.html */
ev_gettext("An error occurred while trying to run your query");

/* templates//popup.tpl.html */
ev_gettext("An error occurred while trying to run your query");

/* templates//popup.tpl.html */
ev_gettext("Thank you, the inital impact analysis was set successfully.");

/* templates//popup.tpl.html */
ev_gettext("An error occurred while trying to run your query");

/* templates//popup.tpl.html */
ev_gettext("Thank you, the new requirement was added successfully.");

/* templates//popup.tpl.html */
ev_gettext("An error occurred while trying to run your query");

/* templates//popup.tpl.html */
ev_gettext("Thank you, the impact analysis was set successfully.");

/* templates//popup.tpl.html */
ev_gettext("An error occurred while trying to run your query");

/* templates//popup.tpl.html */
ev_gettext("Thank you, the selected requirements were removed successfully.");

/* templates//popup.tpl.html */
ev_gettext("An error occurred while trying to run your query");

/* templates//popup.tpl.html */
ev_gettext("Thank you, the custom filter was saved successfully.");

/* templates//popup.tpl.html */
ev_gettext("An error occurred while trying to run your query");

/* templates//popup.tpl.html */
ev_gettext("Thank you, the selected custom filters were removed successfully.");

/* templates//popup.tpl.html */
ev_gettext("An error occurred while trying to run your query");

/* templates//popup.tpl.html */
ev_gettext("Thank you, the association to the selected emails were removed successfully.");

/* templates//popup.tpl.html */
ev_gettext("An error occurred while trying to run your query");

/* templates//popup.tpl.html */
ev_gettext("You do not have the permission to remove this attachment.");

/* templates//popup.tpl.html */
ev_gettext("Thank you, the attachment was removed successfully.");

/* templates//popup.tpl.html */
ev_gettext("An error occurred while trying to run your query");

/* templates//popup.tpl.html */
ev_gettext("You do not have the permission to remove this file.");

/* templates//popup.tpl.html */
ev_gettext("Thank you, the file was removed successfully.");

/* templates//popup.tpl.html */
ev_gettext("An error occurred while trying to run your query");

/* templates//popup.tpl.html */
ev_gettext("Thank you, the selected checkin information entries were removed successfully.");

/* templates//popup.tpl.html */
ev_gettext("An error occurred while trying to run your query");

/* templates//popup.tpl.html */
ev_gettext("Thank you, the emails were marked as removed successfully.");

/* templates//popup.tpl.html */
ev_gettext("An error occurred while trying to run your query");

/* templates//popup.tpl.html */
ev_gettext("Thank you, the current issue is no longer marked as a duplicate.");

/* templates//popup.tpl.html */
ev_gettext("An error occurred while trying to run your query");

/* templates//popup.tpl.html */
ev_gettext("You do not have permission to remove this phone support entry.");

/* templates//popup.tpl.html */
ev_gettext("Thank you, the phone support entry was removed successfully.");

/* templates//popup.tpl.html */
ev_gettext("Thank you, the phone support entry was removed successfully.");

/* templates//popup.tpl.html */
ev_gettext("The associated time tracking entry was also deleted.");

/* templates//popup.tpl.html */
ev_gettext("An error occurred while trying to run your query");

/* templates//popup.tpl.html */
ev_gettext("Thank you, the issue was updated successfully.");

/* templates//popup.tpl.html */
ev_gettext("An error occurred while trying to run your query");

/* templates//popup.tpl.html */
ev_gettext("Error: the issue is already unassigned.");

/* templates//popup.tpl.html */
ev_gettext("Thank you, the issue was unassigned successfully.");

/* templates//popup.tpl.html */
ev_gettext("Error: you are already authorized to send emails in this issue.");

/* templates//popup.tpl.html */
ev_gettext("Thank you, you are now authorized to send emails in this issue.");

/* templates//popup.tpl.html */
ev_gettext("An error occurred while trying to run your query");

/* templates//popup.tpl.html */
ev_gettext("Thank you, this issue was removed from quarantine.");

/* templates//popup.tpl.html */
ev_gettext("Continue");

/* templates//searchbar.tpl.html */
ev_gettext("Quick Search");

/* templates//searchbar.tpl.html */
ev_gettext("Keyword(s):");

/* templates//searchbar.tpl.html */
ev_gettext("Assigned:");

/* templates//searchbar.tpl.html */
ev_gettext("any");

/* templates//searchbar.tpl.html */
ev_gettext("Status:");

/* templates//searchbar.tpl.html */
ev_gettext("any");

/* templates//searchbar.tpl.html */
ev_gettext("Category:");

/* templates//searchbar.tpl.html */
ev_gettext("any");

/* templates//searchbar.tpl.html */
ev_gettext("Priority:");

/* templates//searchbar.tpl.html */
ev_gettext("any");

/* templates//searchbar.tpl.html */
ev_gettext("Search");

/* templates//searchbar.tpl.html */
ev_gettext("Clear");

/* templates//lookup_field.tpl.html */
ev_gettext("paste or start typing here");

/* templates//lookup_field.tpl.html */
ev_gettext("paste or start typing here");

/* templates//convert_note.tpl.html */
ev_gettext("An error occurred while trying to convert the selected note.");

/* templates//convert_note.tpl.html */
ev_gettext("Thank you, the note was converted successfully.");

/* templates//convert_note.tpl.html */
ev_gettext("Continue");

/* templates//convert_note.tpl.html */
ev_gettext("WARNING: Converting this note to an email will send the email to any customers that may be listed in this issue's notification list.");

/* templates//convert_note.tpl.html */
ev_gettext("WARNING: Converting this note to an email will send the email to all users listed in this issue's notification list.");

/* templates//convert_note.tpl.html */
ev_gettext("WARNING: By converting this blocked message to a draft any attachments this message may have will be lost.");

/* templates//convert_note.tpl.html */
ev_gettext("Convert Note To Email");

/* templates//convert_note.tpl.html */
ev_gettext("Convert to Draft and Save For Later Editing");

/* templates//convert_note.tpl.html */
ev_gettext("<b>ALERT:</b> Email will be re-sent from your name, NOT original sender's, and without any attachments.");

/* templates//convert_note.tpl.html */
ev_gettext("Convert to Email and Send Now");

/* templates//convert_note.tpl.html */
ev_gettext("ALERT:");

/* templates//convert_note.tpl.html */
ev_gettext("Email will be re-sent from original sender, including any attachments.");

/* templates//convert_note.tpl.html */
ev_gettext("Add sender to authorized repliers list?");

/* templates//convert_note.tpl.html */
ev_gettext("Continue");

/* templates//spell_check.tpl.html */
ev_gettext("Spell Check");

/* templates//spell_check.tpl.html */
ev_gettext("No spelling mistakes could be found.");

/* templates//spell_check.tpl.html */
ev_gettext("Misspelled Words:");

/* templates//spell_check.tpl.html */
ev_gettext("Suggestions:");

/* templates//spell_check.tpl.html */
ev_gettext("Choose a misspelled word");

/* templates//spell_check.tpl.html */
ev_gettext("Fix Spelling");

/* templates//email_filter_form.tpl.html */
ev_gettext("Subject/Body:");

/* templates//email_filter_form.tpl.html */
ev_gettext("Sender:");

/* templates//email_filter_form.tpl.html */
ev_gettext("To:");

/* templates//email_filter_form.tpl.html */
ev_gettext("Email Account:");

/* templates//email_filter_form.tpl.html */
ev_gettext("any");

/* templates//email_filter_form.tpl.html */
ev_gettext("Search");

/* templates//email_filter_form.tpl.html */
ev_gettext("Clear");

/* templates//email_filter_form.tpl.html */
ev_gettext("Filter by Arrival Date:");

/* templates//email_filter_form.tpl.html */
ev_gettext("Greater Than");

/* templates//email_filter_form.tpl.html */
ev_gettext("Less Than");

/* templates//email_filter_form.tpl.html */
ev_gettext("Between");

/* templates//email_filter_form.tpl.html */
ev_gettext("Arrival Date:");

/* templates//email_filter_form.tpl.html */
ev_gettext("End date");

/* templates//signup.tpl.html */
ev_gettext("Sorry, but this feature has been disabled by the administrator.");

/* templates//signup.tpl.html */
ev_gettext("Go Back");

/* templates//signup.tpl.html */
ev_gettext("Please enter your full name.");

/* templates//signup.tpl.html */
ev_gettext("Please enter your email address.");

/* templates//signup.tpl.html */
ev_gettext("Please enter a valid email address.");

/* templates//signup.tpl.html */
ev_gettext("Please enter your password.");

/* templates//signup.tpl.html */
ev_gettext("Account Signup");

/* templates//signup.tpl.html */
ev_gettext("Error: An error occurred while trying to run your query.");

/* templates//signup.tpl.html */
ev_gettext("Error: The email address specified is already associated with an user in the system.");

/* templates//signup.tpl.html */
ev_gettext("Thank you, your account creation request was processed successfully. For security reasons a confirmation email was sent to the provided email address with instructions on how to confirm your request and activate your account.");

/* templates//signup.tpl.html */
ev_gettext("Full Name:");

/* templates//signup.tpl.html */
ev_gettext("Email Address:");

/* templates//signup.tpl.html */
ev_gettext("Password:");

/* templates//signup.tpl.html */
ev_gettext("Create Account");

/* templates//signup.tpl.html */
ev_gettext("Back to Login Form");

/* templates//forgot_password.tpl.html */
ev_gettext("Please enter your account email address.");

/* templates//forgot_password.tpl.html */
ev_gettext("<b>Note:</b> Please enter your email address below and a new random password will be created and assigned to your account. For security purposes a confirmation message will be sent to your email address and after confirming it the new password will be then activated and sent to you.");

/* templates//forgot_password.tpl.html */
ev_gettext("Request a Password");

/* templates//forgot_password.tpl.html */
ev_gettext("Error: An error occurred while trying to run your query.");

/* templates//forgot_password.tpl.html */
ev_gettext("Thank you, a confirmation message was just emailed to you. Please follow the instructions available in this message to confirm your password creation request.");

/* templates//forgot_password.tpl.html */
ev_gettext("Error: Your user status is currently set as inactive. Please\n              contact your local system administrator for further information.");

/* templates//forgot_password.tpl.html */
ev_gettext(" Error: Please provide your email address.");

/* templates//forgot_password.tpl.html */
ev_gettext("Error: No user account was found matching the entered email address.");

/* templates//forgot_password.tpl.html */
ev_gettext("Email Address:");

/* templates//forgot_password.tpl.html */
ev_gettext("Send New Password");

/* templates//forgot_password.tpl.html */
ev_gettext("Back to Login Form");

/* templates//view_note.tpl.html */
ev_gettext("The specified note does not exist. <br />\n      It could have been converted to an email.");

/* templates//view_note.tpl.html */
ev_gettext("Close");

/* templates//view_note.tpl.html */
ev_gettext("View Note Details");

/* templates//view_note.tpl.html */
ev_gettext("Associated with Issue");

/* templates//view_note.tpl.html */
ev_gettext("Previous Note");

/* templates//view_note.tpl.html */
ev_gettext("Next Note");

/* templates//view_note.tpl.html */
ev_gettext("Posted Date:");

/* templates//view_note.tpl.html */
ev_gettext("From:");

/* templates//view_note.tpl.html */
ev_gettext("Title:");

/* templates//view_note.tpl.html */
ev_gettext("Attachments:");

/* templates//view_note.tpl.html */
ev_gettext("download file");

/* templates//view_note.tpl.html */
ev_gettext("download file");

/* templates//view_note.tpl.html */
ev_gettext("Message:");

/* templates//view_note.tpl.html */
ev_gettext("display in fixed width font");

/* templates//view_note.tpl.html */
ev_gettext("Blocked Message Raw Headers");

/* templates//view_note.tpl.html */
ev_gettext("Reply");

/* templates//view_note.tpl.html */
ev_gettext("Close");

/* templates//view_note.tpl.html */
ev_gettext("Previous Note");

/* templates//view_note.tpl.html */
ev_gettext("Next Note");

/* templates//latest_news.tpl.html */
ev_gettext("News and Announcements");

/* templates//latest_news.tpl.html */
ev_gettext("full news entry");

/* templates//latest_news.tpl.html */
ev_gettext("Read All Notices");

/* templates//time_tracking.tpl.html */
ev_gettext("This action will permanently delete the specified time tracking entry.");

/* templates//time_tracking.tpl.html */
ev_gettext("Time Tracking");

/* templates//time_tracking.tpl.html */
ev_gettext("Back to Top");

/* templates//time_tracking.tpl.html */
ev_gettext("Date of Work");

/* templates//time_tracking.tpl.html */
ev_gettext("User");

/* templates//time_tracking.tpl.html */
ev_gettext("Time Spent");

/* templates//time_tracking.tpl.html */
ev_gettext("Category");

/* templates//time_tracking.tpl.html */
ev_gettext("Summary");

/* templates//time_tracking.tpl.html */
ev_gettext("Total Time Spent");

/* templates//time_tracking.tpl.html */
ev_gettext("No time tracking entries could be found.");

/* templates//time_tracking.tpl.html */
ev_gettext("Add Time Entry");

/* templates//customer/example/customer_lookup.tpl.html */
ev_gettext("Field");

/* templates//customer/example/customer_lookup.tpl.html */
ev_gettext("Value");

/* templates//customer/example/customer_lookup.tpl.html */
ev_gettext("Customer Lookup Tool");

/* templates//customer/example/customer_lookup.tpl.html */
ev_gettext("Field");

/* templates//customer/example/customer_lookup.tpl.html */
ev_gettext("Email Address");

/* templates//customer/example/customer_lookup.tpl.html */
ev_gettext("Customer ID");

/* templates//customer/example/customer_lookup.tpl.html */
ev_gettext("Value");

/* templates//customer/example/customer_lookup.tpl.html */
ev_gettext("Lookup");

/* templates//customer/example/customer_lookup.tpl.html */
ev_gettext("Cancel");

/* templates//customer/example/customer_lookup.tpl.html */
ev_gettext("Results");

/* templates//customer/example/customer_lookup.tpl.html */
ev_gettext("Customer");

/* templates//customer/example/customer_lookup.tpl.html */
ev_gettext("Support Type");

/* templates//customer/example/customer_lookup.tpl.html */
ev_gettext("Expiration Date");

/* templates//customer/example/customer_lookup.tpl.html */
ev_gettext("Status");

/* templates//customer/example/customer_lookup.tpl.html */
ev_gettext("No results could be found");

/* templates//customer/example/report_form_fields.tpl.html */
ev_gettext("Customer");

/* templates//customer/example/report_form_fields.tpl.html */
ev_gettext("Contact");

/* templates//customer/example/report_form_fields.tpl.html */
ev_gettext("Contact Person Last Name");

/* templates//customer/example/report_form_fields.tpl.html */
ev_gettext("Contact Person First Name");

/* templates//customer/example/report_form_fields.tpl.html */
ev_gettext("Contact Email");

/* templates//customer/example/report_form_fields.tpl.html */
ev_gettext("Contact Email");

/* templates//customer/example/report_form_fields.tpl.html */
ev_gettext("Customer Details");

/* templates//customer/example/report_form_fields.tpl.html */
ev_gettext("Customer");

/* templates//customer/example/report_form_fields.tpl.html */
ev_gettext("Lookup Customer");

/* templates//customer/example/report_form_fields.tpl.html */
ev_gettext("Contact");

/* templates//customer/example/report_form_fields.tpl.html */
ev_gettext("Add Primary Contact to Notification List? *");

/* templates//customer/example/report_form_fields.tpl.html */
ev_gettext("Yes");

/* templates//customer/example/report_form_fields.tpl.html */
ev_gettext("No");

/* templates//customer/example/report_form_fields.tpl.html */
ev_gettext("Notify Customer About New Issue? *");

/* templates//customer/example/report_form_fields.tpl.html */
ev_gettext("Yes");

/* templates//customer/example/report_form_fields.tpl.html */
ev_gettext("No");

/* templates//customer/example/report_form_fields.tpl.html */
ev_gettext("Last Name");

/* templates//customer/example/report_form_fields.tpl.html */
ev_gettext("First Name");

/* templates//customer/example/report_form_fields.tpl.html */
ev_gettext("Email");

/* templates//customer/example/report_form_fields.tpl.html */
ev_gettext("Phone Number");

/* templates//customer/example/report_form_fields.tpl.html */
ev_gettext("Timezone");

/* templates//customer/example/report_form_fields.tpl.html */
ev_gettext("Additional Contact Emails");

/* templates//customer/example/report_form_fields.tpl.html */
ev_gettext("(hold ctrl to select multiple options)");

/* templates//customer/example/report_form_fields.tpl.html */
ev_gettext("(only technical contacts listed on your contract)");

/* templates//customer/example/customer_report.tpl.html */
ev_gettext("Example customer API front page");

/* templates//customer/example/customer_info.tpl.html */
ev_gettext("Customer Details");

/* templates//customer/example/customer_info.tpl.html */
ev_gettext("Contact Person");

/* templates//customer/example/customer_info.tpl.html */
ev_gettext("Contact Email");

/* templates//customer/example/customer_info.tpl.html */
ev_gettext("Phone Number");

/* templates//customer/example/customer_info.tpl.html */
ev_gettext("Timezone");

/* templates//customer/example/customer_info.tpl.html */
ev_gettext("Contact's Local Time");

/* templates//customer/example/customer_info.tpl.html */
ev_gettext("Maximum First Response Time");

/* templates//customer/example/customer_info.tpl.html */
ev_gettext("Time Until First Response Deadline");

/* templates//customer/example/customer_info.tpl.html */
ev_gettext("Customer");

/* templates//customer/example/customer_info.tpl.html */
ev_gettext("Support Level");

/* templates//customer/example/customer_info.tpl.html */
ev_gettext("Support Expiration Date");

/* templates//customer/example/customer_info.tpl.html */
ev_gettext("Sales Account Manager");

/* templates//customer/example/customer_info.tpl.html */
ev_gettext("Notes About Customer");

/* templates//customer/example/customer_info.tpl.html */
ev_gettext("Add");

/* templates//customer/example/customer_info.tpl.html */
ev_gettext("Edit");

/* templates//customer/example/quarantine.tpl.html */
ev_gettext("Quarantine explanation goes here...");

/* templates//customer/example/customer_expired.tpl.html */
ev_gettext("Expired Customer");

/* templates//customer/example/customer_expired.tpl.html */
ev_gettext("Contact");

/* templates//customer/example/customer_expired.tpl.html */
ev_gettext("Company Name");

/* templates//customer/example/customer_expired.tpl.html */
ev_gettext("Contract #");

/* templates//customer/example/customer_expired.tpl.html */
ev_gettext("Support Level");

/* templates//customer/example/customer_expired.tpl.html */
ev_gettext("Expired");

/* templates//customer/example/customer_expired.tpl.html */
ev_gettext("Back");

/* templates//add_phone_entry.tpl.html */
ev_gettext("Issue");

/* templates//add_phone_entry.tpl.html */
ev_gettext("Add Phone Entry");

/* templates//add_phone_entry.tpl.html */
ev_gettext("An error occurred while trying to run your query");

/* templates//add_phone_entry.tpl.html */
ev_gettext("Thank you, the phone entry was added successfully.");

/* templates//add_phone_entry.tpl.html */
ev_gettext("Continue");

/* templates//add_phone_entry.tpl.html */
ev_gettext("Please select a valid date for when the phone call took place.");

/* templates//add_phone_entry.tpl.html */
ev_gettext("Please enter integers (or floating point numbers) on the time spent field.");

/* templates//add_phone_entry.tpl.html */
ev_gettext("Please enter the description for this new phone support entry.");

/* templates//add_phone_entry.tpl.html */
ev_gettext("Please choose the category for this new phone support entry.");

/* templates//add_phone_entry.tpl.html */
ev_gettext("Record Phone Call");

/* templates//add_phone_entry.tpl.html */
ev_gettext("Date of Call");

/* templates//add_phone_entry.tpl.html */
ev_gettext("Reason");

/* templates//add_phone_entry.tpl.html */
ev_gettext("Call From");

/* templates//add_phone_entry.tpl.html */
ev_gettext("last name");

/* templates//add_phone_entry.tpl.html */
ev_gettext("first name");

/* templates//add_phone_entry.tpl.html */
ev_gettext("Call To");

/* templates//add_phone_entry.tpl.html */
ev_gettext("last name");

/* templates//add_phone_entry.tpl.html */
ev_gettext("first name");

/* templates//add_phone_entry.tpl.html */
ev_gettext("Type");

/* templates//add_phone_entry.tpl.html */
ev_gettext("Incoming");

/* templates//add_phone_entry.tpl.html */
ev_gettext("Outgoing");

/* templates//add_phone_entry.tpl.html */
ev_gettext("Customer Phone Number");

/* templates//add_phone_entry.tpl.html */
ev_gettext("Office");

/* templates//add_phone_entry.tpl.html */
ev_gettext("Home");

/* templates//add_phone_entry.tpl.html */
ev_gettext("Mobile");

/* templates//add_phone_entry.tpl.html */
ev_gettext("Temp Number");

/* templates//add_phone_entry.tpl.html */
ev_gettext("Other");

/* templates//add_phone_entry.tpl.html */
ev_gettext("Time Spent");

/* templates//add_phone_entry.tpl.html */
ev_gettext("in minutes");

/* templates//add_phone_entry.tpl.html */
ev_gettext("Description");

/* templates//add_phone_entry.tpl.html */
ev_gettext("Save Phone Call");

/* templates//emails.tpl.html */
ev_gettext("Associate Emails");

/* templates//emails.tpl.html */
ev_gettext("Sorry, but this feature has been disabled by the administrator.");

/* templates//emails.tpl.html */
ev_gettext("Go Back");

/* templates//emails.tpl.html */
ev_gettext("Sorry, but you do not have access to this page.");

/* templates//emails.tpl.html */
ev_gettext("Go Back");

/* templates//emails.tpl.html */
ev_gettext("Please choose which emails need to be associated.");

/* templates//emails.tpl.html */
ev_gettext("Please choose which emails need to be marked as deleted.");

/* templates//emails.tpl.html */
ev_gettext("This action will mark the selected email messages as deleted.");

/* templates//emails.tpl.html */
ngettext("Viewing Emails (%1 emails found)","Viewing Emails (%1 emails found, %2 - %3 shown)",x);

/* templates//emails.tpl.html */
ev_gettext("All");

/* templates//emails.tpl.html */
ev_gettext("Sender");

/* templates//emails.tpl.html */
ev_gettext("sort by sender");

/* templates//emails.tpl.html */
ev_gettext("Customer");

/* templates//emails.tpl.html */
ev_gettext("sort by customer");

/* templates//emails.tpl.html */
ev_gettext("Date");

/* templates//emails.tpl.html */
ev_gettext("sort by date");

/* templates//emails.tpl.html */
ev_gettext("To");

/* templates//emails.tpl.html */
ev_gettext("sort by recipient");

/* templates//emails.tpl.html */
ev_gettext("Status");

/* templates//emails.tpl.html */
ev_gettext("sort by status");

/* templates//emails.tpl.html */
ev_gettext("Subject");

/* templates//emails.tpl.html */
ev_gettext("sort by subject");

/* templates//emails.tpl.html */
ev_gettext("associated");

/* templates//emails.tpl.html */
ev_gettext("view issue details");

/* templates//emails.tpl.html */
ev_gettext("pending");

/* templates//emails.tpl.html */
ev_gettext("Empty Subject Header");

/* templates//emails.tpl.html */
ev_gettext("view email details");

/* templates//emails.tpl.html */
ev_gettext("view email details");

/* templates//emails.tpl.html */
ev_gettext("No emails could be found.");

/* templates//emails.tpl.html */
ev_gettext("All");

/* templates//emails.tpl.html */
ev_gettext("Associate");

/* templates//emails.tpl.html */
ev_gettext("New Issue");

/* templates//emails.tpl.html */
ev_gettext("lookup issues by their summaries");

/* templates//emails.tpl.html */
ev_gettext("ALL");

/* templates//emails.tpl.html */
ev_gettext("Set");

/* templates//emails.tpl.html */
ev_gettext("Hide Associated Emails");

/* templates//emails.tpl.html */
ev_gettext("Remove Selected Emails");

/* templates//emails.tpl.html */
ev_gettext("list all removed emails");

/* templates//emails.tpl.html */
ev_gettext("List Removed Emails");

/* templates//setup.tpl.html */
ev_gettext("Please enter the hostname for the server of this installation of Eventum.");

/* templates//setup.tpl.html */
ev_gettext("Please enter the relative URL of this installation of Eventum.");

/* templates//setup.tpl.html */
ev_gettext("Please enter the database hostname for this installation of Eventum.");

/* templates//setup.tpl.html */
ev_gettext("Please enter the database name for this installation of Eventum.");

/* templates//setup.tpl.html */
ev_gettext("Please enter the database username for this installation of Eventum.");

/* templates//setup.tpl.html */
ev_gettext("Please enter the alternate username for this installation of Eventum.");

/* templates//setup.tpl.html */
ev_gettext("Please enter the sender address that will be used for all outgoing notification emails.");

/* templates//setup.tpl.html */
ev_gettext("Please enter a valid email address for the sender address.");

/* templates//setup.tpl.html */
ev_gettext("Please enter the SMTP server hostname.");

/* templates//setup.tpl.html */
ev_gettext("Please enter the SMTP server port number.");

/* templates//setup.tpl.html */
ev_gettext("Please indicate whether the SMTP server requires authentication or not.");

/* templates//setup.tpl.html */
ev_gettext("Please enter the SMTP server username.");

/* templates//setup.tpl.html */
ev_gettext("Please enter the SMTP server password.");

/* templates//setup.tpl.html */
ev_gettext("An Error Was Found");

/* templates//setup.tpl.html */
ev_gettext("Details:");

/* templates//setup.tpl.html */
ev_gettext("Success!");

/* templates//setup.tpl.html */
ev_gettext("Thank You, Eventum is now properly setup and ready to be used. Open the following URL to login on it for the first time:");

/* templates//setup.tpl.html */
ev_gettext("Email Address: admin@example.com (literally)");

/* templates//setup.tpl.html */
ev_gettext("Password: admin");

/* templates//setup.tpl.html */
ev_gettext("NOTE: For security reasons it is highly recommended that the default password be changed as soon as possible.");

/* templates//setup.tpl.html */
ev_gettext("Remember to protect your 'setup' directory (like changing its permissions) to prevent anyone else\n            from changing your existing Eventum configuration.");

/* templates//setup.tpl.html */
ev_gettext("In order to check if your permissions are setup correctly visit the <a class=\"link\" href=\"check_permissions.php\">Check Permissions</a> page.");

/* templates//setup.tpl.html */
ev_gettext("WARNING: If you want to use the email integration features to download messages saved on a IMAP/POP3 server, you will need to\n            enable the IMAP extension in your PHP.INI configuration file. See the PHP manual for more details.");

/* templates//setup.tpl.html */
ev_gettext("Eventum Installation");

/* templates//setup.tpl.html */
ev_gettext("Server Hostname:");

/* templates//setup.tpl.html */
ev_gettext("SSL Server");

/* templates//setup.tpl.html */
ev_gettext("Eventum Relative URL:");

/* templates//setup.tpl.html */
ev_gettext("MySQL Server Hostname:");

/* templates//setup.tpl.html */
ev_gettext("MySQL Database:");

/* templates//setup.tpl.html */
ev_gettext("Create Database");

/* templates//setup.tpl.html */
ev_gettext("MySQL Table Prefix:");

/* templates//setup.tpl.html */
ev_gettext("Drop Tables If They Already Exist");

/* templates//setup.tpl.html */
ev_gettext("MySQL Username:");

/* templates//setup.tpl.html */
ev_gettext("<b>Note:</b> This user requires permission to create and drop tables in the specified database.<br />This value is used only for these installation procedures, and is not saved if you provide a separate user below.");

/* templates//setup.tpl.html */
ev_gettext("MySQL Password:");

/* templates//setup.tpl.html */
ev_gettext("Use a Separate MySQL User for Normal Eventum Use");

/* templates//setup.tpl.html */
ev_gettext("Enter the details below:");

/* templates//setup.tpl.html */
ev_gettext("Username:");

/* templates//setup.tpl.html */
ev_gettext("Password:");

/* templates//setup.tpl.html */
ev_gettext("Create User and Permissions");

/* templates//setup.tpl.html */
ev_gettext("SMTP Configuration");

/* templates//setup.tpl.html */
ev_gettext("<b>Note:</b> The SMTP (outgoing mail) configuration is needed to make sure emails are properly sent when creating new users/projects.");

/* templates//setup.tpl.html */
ev_gettext("Sender:");

/* templates//setup.tpl.html */
ev_gettext("must be a valid email address");

/* templates//setup.tpl.html */
ev_gettext("Hostname:");

/* templates//setup.tpl.html */
ev_gettext("Port:");

/* templates//setup.tpl.html */
ev_gettext("Requires Authentication?");

/* templates//setup.tpl.html */
ev_gettext("Yes");

/* templates//setup.tpl.html */
ev_gettext("No");

/* templates//setup.tpl.html */
ev_gettext("Username:");

/* templates//setup.tpl.html */
ev_gettext("Password:");

/* templates//setup.tpl.html */
ev_gettext("Start Installation");

/* templates//setup.tpl.html */
ev_gettext("Required Fields");

/* templates//current_filters.tpl.html */
ev_gettext("Current filters:");

/* templates//current_filters.tpl.html */
ev_gettext("Fulltext");

/* templates//current_filters.tpl.html */
ev_gettext("In Past %1 hours");

/* templates//current_filters.tpl.html */
ev_gettext("Is NULL");

/* templates//current_filters.tpl.html */
ev_gettext("Is between %1-%2-%3 AND %4-%5-%6");

/* templates//current_filters.tpl.html */
ev_gettext("Is greater than %1-%2-%3");

/* templates//current_filters.tpl.html */
ev_gettext("Is less than %1-%2-%3");

/* templates//current_filters.tpl.html */
ev_gettext("un-assigned");

/* templates//current_filters.tpl.html */
ev_gettext("myself and un-assigned");

/* templates//current_filters.tpl.html */
ev_gettext("myself and my group");

/* templates//current_filters.tpl.html */
ev_gettext("myself, un-assigned and my group");

/* templates//current_filters.tpl.html */
ev_gettext("Yes");

/* templates//current_filters.tpl.html */
ev_gettext("None");

/* templates//phone_support.tpl.html */
ev_gettext("This action will permanently delete the specified phone support entry.");

/* templates//phone_support.tpl.html */
ev_gettext("Phone Calls");

/* templates//phone_support.tpl.html */
ev_gettext("Back to Top");

/* templates//phone_support.tpl.html */
ev_gettext("Recorded Date");

/* templates//phone_support.tpl.html */
ev_gettext("Entered By");

/* templates//phone_support.tpl.html */
ev_gettext("From");

/* templates//phone_support.tpl.html */
ev_gettext("To");

/* templates//phone_support.tpl.html */
ev_gettext("Call Type");

/* templates//phone_support.tpl.html */
ev_gettext("Category");

/* templates//phone_support.tpl.html */
ev_gettext("Phone Number");

/* templates//phone_support.tpl.html */
ev_gettext("delete");

/* templates//phone_support.tpl.html */
ev_gettext("No phone calls recorded yet.");

/* templates//phone_support.tpl.html */
ev_gettext("Add Phone Call");

/* templates//manage/resolution.tpl.html */
ev_gettext("Please enter the title of this resolution.");

/* templates//manage/resolution.tpl.html */
ev_gettext("Manage Issue Resolutions");

/* templates//manage/resolution.tpl.html */
ev_gettext("An error occurred while trying to add the new issue resolution.");

/* templates//manage/resolution.tpl.html */
ev_gettext("Please enter the title for this new issue resolution.");

/* templates//manage/resolution.tpl.html */
ev_gettext("Thank you, the issue resolution was added successfully.");

/* templates//manage/resolution.tpl.html */
ev_gettext("An error occurred while trying to update the issue resolution information.");

/* templates//manage/resolution.tpl.html */
ev_gettext("Please enter the title for this issue resolution.");

/* templates//manage/resolution.tpl.html */
ev_gettext("Thank you, the issue resolution was updated successfully.");

/* templates//manage/resolution.tpl.html */
ev_gettext("Title:");

/* templates//manage/resolution.tpl.html */
ev_gettext("Update Resolution");

/* templates//manage/resolution.tpl.html */
ev_gettext("Create Resolution");

/* templates//manage/resolution.tpl.html */
ev_gettext("Reset");

/* templates//manage/resolution.tpl.html */
ev_gettext("Existing Resolutions:");

/* templates//manage/resolution.tpl.html */
ev_gettext("Please select at least one of the resolutions.");

/* templates//manage/resolution.tpl.html */
ev_gettext("This action will remove the selected entries.");

/* templates//manage/resolution.tpl.html */
ev_gettext("All");

/* templates//manage/resolution.tpl.html */
ev_gettext("Title");

/* templates//manage/resolution.tpl.html */
ev_gettext("update this entry");

/* templates//manage/resolution.tpl.html */
ev_gettext("No resolutions could be found.");

/* templates//manage/resolution.tpl.html */
ev_gettext("All");

/* templates//manage/resolution.tpl.html */
ev_gettext("Delete");

/* templates//manage/round_robin.tpl.html */
ev_gettext("Please assign the appropriate users for this round robin entry.");

/* templates//manage/round_robin.tpl.html */
ev_gettext("Manage Round Robin Assignments");

/* templates//manage/round_robin.tpl.html */
ev_gettext("An error occurred while trying to add the round robin entry.");

/* templates//manage/round_robin.tpl.html */
ev_gettext("Please enter the title for this round robin entry.");

/* templates//manage/round_robin.tpl.html */
ev_gettext("Please enter the message for this round robin entry.");

/* templates//manage/round_robin.tpl.html */
ev_gettext("Thank you, the round robin entry was added successfully.");

/* templates//manage/round_robin.tpl.html */
ev_gettext("An error occurred while trying to update the round robin entry information.");

/* templates//manage/round_robin.tpl.html */
ev_gettext("Please enter the title for this round robin entry.");

/* templates//manage/round_robin.tpl.html */
ev_gettext("Please enter the message for this round robin entry.");

/* templates//manage/round_robin.tpl.html */
ev_gettext("Thank you, the round robin entry was updated successfully.");

/* templates//manage/round_robin.tpl.html */
ev_gettext("Project:");

/* templates//manage/round_robin.tpl.html */
ev_gettext("Please choose an option");

/* templates//manage/round_robin.tpl.html */
ev_gettext("Assignable Users:");

/* templates//manage/round_robin.tpl.html */
ev_gettext("Blackout Time Range:");

/* templates//manage/round_robin.tpl.html */
ev_gettext("Update Round Robin Entry");

/* templates//manage/round_robin.tpl.html */
ev_gettext("Create Round Robin Entry");

/* templates//manage/round_robin.tpl.html */
ev_gettext("Reset");

/* templates//manage/round_robin.tpl.html */
ev_gettext("Existing Round Robin Entries:");

/* templates//manage/round_robin.tpl.html */
ev_gettext("Please select at least one of the round robin entries.");

/* templates//manage/round_robin.tpl.html */
ev_gettext("This action will permanently remove the selected round robin entries.");

/* templates//manage/round_robin.tpl.html */
ev_gettext("All");

/* templates//manage/round_robin.tpl.html */
ev_gettext("Project");

/* templates//manage/round_robin.tpl.html */
ev_gettext("Assignable Users");

/* templates//manage/round_robin.tpl.html */
ev_gettext("update this entry");

/* templates//manage/round_robin.tpl.html */
ev_gettext("No round robin entries could be found.");

/* templates//manage/round_robin.tpl.html */
ev_gettext("All");

/* templates//manage/round_robin.tpl.html */
ev_gettext("Delete");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("Action Type");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("Rank");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("Manage Reminder Actions");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("view reminder details");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("An error occurred while trying to add the new action.");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("Please enter the title for this new action.");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("Thank you, the action was added successfully.");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("An error occurred while trying to update the action information.");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("Please enter the title for this action.");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("Thank you, the action was updated successfully.");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("Title:");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("Action Type:");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("Please choose an option");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("Email List:");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("Add");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("Remove");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("Rank:");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("this will determine the order in which actions are triggered");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("Alert Group Leader:");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("Yes");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("No");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("Alert IRC:");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("Yes");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("No");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("Boilerplate:");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("this will show up on the bottom of the reminder messages");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("Update Action");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("Add Action");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("Reset");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("Existing Actions:");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("Back to Reminder List");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("Please select at least one of the actions.");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("This action will remove the selected entries.");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("Rank");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("Title");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("Type");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("Details");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("No actions could be found.");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("All");

/* templates//manage/reminder_actions.tpl.html */
ev_gettext("Delete");

/* templates//manage/anonymous.tpl.html */
ev_gettext("Please choose whether the anonymous posting feature should be allowed or not for this project");

/* templates//manage/anonymous.tpl.html */
ev_gettext("Please choose whether to show custom fields for remote invocations or not.");

/* templates//manage/anonymous.tpl.html */
ev_gettext("Please choose the reporter for remote invocations.");

/* templates//manage/anonymous.tpl.html */
ev_gettext("Please choose the default category for remote invocations.");

/* templates//manage/anonymous.tpl.html */
ev_gettext("Please choose the default priority for remote invocations.");

/* templates//manage/anonymous.tpl.html */
ev_gettext("Please choose at least one person to assign the new issues created remotely.");

/* templates//manage/anonymous.tpl.html */
ev_gettext("Anonymous Reporting of New Issues");

/* templates//manage/anonymous.tpl.html */
ev_gettext("Current Project:");

/* templates//manage/anonymous.tpl.html */
ev_gettext("An error occurred while trying to update the information.");

/* templates//manage/anonymous.tpl.html */
ev_gettext("Thank you, the information was updated successfully.");

/* templates//manage/anonymous.tpl.html */
ev_gettext("Anonymous Reporting:");

/* templates//manage/anonymous.tpl.html */
ev_gettext("Enabled");

/* templates//manage/anonymous.tpl.html */
ev_gettext("Disabled");

/* templates//manage/anonymous.tpl.html */
ev_gettext("Show Custom Fields ?");

/* templates//manage/anonymous.tpl.html */
ev_gettext("Enabled");

/* templates//manage/anonymous.tpl.html */
ev_gettext("Disabled");

/* templates//manage/anonymous.tpl.html */
ev_gettext("Reporter:");

/* templates//manage/anonymous.tpl.html */
ev_gettext("Please choose an user");

/* templates//manage/anonymous.tpl.html */
ev_gettext("Default Category:");

/* templates//manage/anonymous.tpl.html */
ev_gettext("Please choose a category");

/* templates//manage/anonymous.tpl.html */
ev_gettext("Default Priority:");

/* templates//manage/anonymous.tpl.html */
ev_gettext("Please choose a priority");

/* templates//manage/anonymous.tpl.html */
ev_gettext("Assignment:");

/* templates//manage/anonymous.tpl.html */
ev_gettext("Update Setup");

/* templates//manage/anonymous.tpl.html */
ev_gettext("Reset");

/* templates//manage/issue_auto_creation.tpl.html */
ev_gettext("Please choose whether the issue auto creation feature should be allowed or not for this email account");

/* templates//manage/issue_auto_creation.tpl.html */
ev_gettext("Please choose the default category.");

/* templates//manage/issue_auto_creation.tpl.html */
ev_gettext("Please choose the default priority.");

/* templates//manage/issue_auto_creation.tpl.html */
ev_gettext("Auto-Creation of Issues");

/* templates//manage/issue_auto_creation.tpl.html */
ev_gettext("Associated Project:");

/* templates//manage/issue_auto_creation.tpl.html */
ev_gettext("Auto-Creation of Issues:");

/* templates//manage/issue_auto_creation.tpl.html */
ev_gettext("Enabled");

/* templates//manage/issue_auto_creation.tpl.html */
ev_gettext("Disabled");

/* templates//manage/issue_auto_creation.tpl.html */
ev_gettext("Only for Known Customers?");

/* templates//manage/issue_auto_creation.tpl.html */
ev_gettext("Yes");

/* templates//manage/issue_auto_creation.tpl.html */
ev_gettext("No");

/* templates//manage/issue_auto_creation.tpl.html */
ev_gettext("Default Category:");

/* templates//manage/issue_auto_creation.tpl.html */
ev_gettext("Please choose a category");

/* templates//manage/issue_auto_creation.tpl.html */
ev_gettext("Default Priority:");

/* templates//manage/issue_auto_creation.tpl.html */
ev_gettext("Please choose a priority");

/* templates//manage/issue_auto_creation.tpl.html */
ev_gettext("Assignment:");

/* templates//manage/issue_auto_creation.tpl.html */
ev_gettext("Update Setup");

/* templates//manage/issue_auto_creation.tpl.html */
ev_gettext("Reset");

/* templates//manage/email_responses.tpl.html */
ev_gettext("Please enter the title of this email response.");

/* templates//manage/email_responses.tpl.html */
ev_gettext("Manage Canned Email Responses");

/* templates//manage/email_responses.tpl.html */
ev_gettext("An error occurred while trying to add the new email response.");

/* templates//manage/email_responses.tpl.html */
ev_gettext("Please enter the title for this new email response.");

/* templates//manage/email_responses.tpl.html */
ev_gettext("Thank you, the email response was added successfully.");

/* templates//manage/email_responses.tpl.html */
ev_gettext("An error occurred while trying to update the email response information.");

/* templates//manage/email_responses.tpl.html */
ev_gettext("Please enter the title for this email response.");

/* templates//manage/email_responses.tpl.html */
ev_gettext("Thank you, the email response was updated successfully.");

/* templates//manage/email_responses.tpl.html */
ev_gettext("Projects:");

/* templates//manage/email_responses.tpl.html */
ev_gettext("Title:");

/* templates//manage/email_responses.tpl.html */
ev_gettext("Response Body:");

/* templates//manage/email_responses.tpl.html */
ev_gettext("Update Email Response");

/* templates//manage/email_responses.tpl.html */
ev_gettext("Create Email Response");

/* templates//manage/email_responses.tpl.html */
ev_gettext("Reset");

/* templates//manage/email_responses.tpl.html */
ev_gettext("Existing Canned Email Responses:");

/* templates//manage/email_responses.tpl.html */
ev_gettext("Please select at least one of the email responses.");

/* templates//manage/email_responses.tpl.html */
ev_gettext("This action will remove the selected entries.");

/* templates//manage/email_responses.tpl.html */
ev_gettext("Title");

/* templates//manage/email_responses.tpl.html */
ev_gettext("Projects");

/* templates//manage/email_responses.tpl.html */
ev_gettext("update this entry");

/* templates//manage/email_responses.tpl.html */
ev_gettext("No canned email responses could be found.");

/* templates//manage/email_responses.tpl.html */
ev_gettext("All");

/* templates//manage/email_responses.tpl.html */
ev_gettext("Delete");

/* templates//manage/customize_listing.tpl.html */
ev_gettext("Please choose the project that you wish to customize.");

/* templates//manage/customize_listing.tpl.html */
ev_gettext("Customize Issue Listing Screen");

/* templates//manage/customize_listing.tpl.html */
ev_gettext("An error occurred while trying to add the new customization.");

/* templates//manage/customize_listing.tpl.html */
ev_gettext("Please enter the title for this new customization.");

/* templates//manage/customize_listing.tpl.html */
ev_gettext("Thank you, the customization was added successfully.");

/* templates//manage/customize_listing.tpl.html */
ev_gettext("An error occurred while trying to update the customization information.");

/* templates//manage/customize_listing.tpl.html */
ev_gettext("Please enter the title for this customization.");

/* templates//manage/customize_listing.tpl.html */
ev_gettext("Thank you, the customization was updated successfully.");

/* templates//manage/customize_listing.tpl.html */
ev_gettext("Project:");

/* templates//manage/customize_listing.tpl.html */
ev_gettext("Please choose an option");

/* templates//manage/customize_listing.tpl.html */
ev_gettext("Status:");

/* templates//manage/customize_listing.tpl.html */
ev_gettext("Date Field:");

/* templates//manage/customize_listing.tpl.html */
ev_gettext("Label:");

/* templates//manage/customize_listing.tpl.html */
ev_gettext("Update Customization");

/* templates//manage/customize_listing.tpl.html */
ev_gettext("Create Customization");

/* templates//manage/customize_listing.tpl.html */
ev_gettext("Reset");

/* templates//manage/customize_listing.tpl.html */
ev_gettext("Existing Customizations:");

/* templates//manage/customize_listing.tpl.html */
ev_gettext("Please select at least one of the customizations.");

/* templates//manage/customize_listing.tpl.html */
ev_gettext("This action will remove the selected entries.");

/* templates//manage/customize_listing.tpl.html */
ev_gettext("All");

/* templates//manage/customize_listing.tpl.html */
ev_gettext("Project");

/* templates//manage/customize_listing.tpl.html */
ev_gettext("Status");

/* templates//manage/customize_listing.tpl.html */
ev_gettext("Label");

/* templates//manage/customize_listing.tpl.html */
ev_gettext("Date Field");

/* templates//manage/customize_listing.tpl.html */
ev_gettext("No customizations could be found.");

/* templates//manage/customize_listing.tpl.html */
ev_gettext("All");

/* templates//manage/customize_listing.tpl.html */
ev_gettext("Delete");

/* templates//manage/time_tracking.tpl.html */
ev_gettext("Please enter the title of this time tracking category");

/* templates//manage/time_tracking.tpl.html */
ev_gettext("Manage Time Tracking Categories");

/* templates//manage/time_tracking.tpl.html */
ev_gettext("An error occurred while trying to add the new time tracking category.");

/* templates//manage/time_tracking.tpl.html */
ev_gettext("Please enter the title for this new time tracking category.");

/* templates//manage/time_tracking.tpl.html */
ev_gettext("Thank you, the time tracking category was added successfully.");

/* templates//manage/time_tracking.tpl.html */
ev_gettext("An error occurred while trying to update the time tracking category information.");

/* templates//manage/time_tracking.tpl.html */
ev_gettext("Please enter the title for this time tracking category.");

/* templates//manage/time_tracking.tpl.html */
ev_gettext("Thank you, the time tracking category was updated successfully.");

/* templates//manage/time_tracking.tpl.html */
ev_gettext("Title:");

/* templates//manage/time_tracking.tpl.html */
ev_gettext("Update Category");

/* templates//manage/time_tracking.tpl.html */
ev_gettext("Create Category");

/* templates//manage/time_tracking.tpl.html */
ev_gettext("Reset");

/* templates//manage/time_tracking.tpl.html */
ev_gettext("Existing Categories:");

/* templates//manage/time_tracking.tpl.html */
ev_gettext("Please select at least one of the time tracking categories.");

/* templates//manage/time_tracking.tpl.html */
ev_gettext("This action will remove the selected entries.");

/* templates//manage/time_tracking.tpl.html */
ev_gettext("All");

/* templates//manage/time_tracking.tpl.html */
ev_gettext("Title");

/* templates//manage/time_tracking.tpl.html */
ev_gettext("update this entry");

/* templates//manage/time_tracking.tpl.html */
ev_gettext("No time tracking categories could be found.");

/* templates//manage/time_tracking.tpl.html */
ev_gettext("All");

/* templates//manage/time_tracking.tpl.html */
ev_gettext("Delete");

/* templates//manage/time_tracking.tpl.html */
ev_gettext("Note:");

/* templates//manage/time_tracking.tpl.html */
ev_gettext("'Note Discussion', 'Email Discussion' and 'Telephone Discussion' categories are\n                    required by Eventum and cannot be deleted.");

/* templates//manage/groups.tpl.html */
ev_gettext("Please enter the name of this group.");

/* templates//manage/groups.tpl.html */
ev_gettext("Please assign the appropriate projects for this group.");

/* templates//manage/groups.tpl.html */
ev_gettext("Please assign the appropriate users for this group.");

/* templates//manage/groups.tpl.html */
ev_gettext("Please assign the manager of this group.");

/* templates//manage/groups.tpl.html */
ev_gettext("Please select at least one of the groups.");

/* templates//manage/groups.tpl.html */
ev_gettext("WARNING: This action will remove the selected groups permanently.nPlease click OK to confirm.");

/* templates//manage/groups.tpl.html */
ev_gettext("Manage Groups");

/* templates//manage/groups.tpl.html */
ev_gettext("An error occurred while trying to add the new group.");

/* templates//manage/groups.tpl.html */
ev_gettext("Thank you, the group was added successfully.");

/* templates//manage/groups.tpl.html */
ev_gettext("An error occurred while trying to update the group information.");

/* templates//manage/groups.tpl.html */
ev_gettext("Thank you, the group was updated successfully.");

/* templates//manage/groups.tpl.html */
ev_gettext("Name: *");

/* templates//manage/groups.tpl.html */
ev_gettext("Description:");

/* templates//manage/groups.tpl.html */
ev_gettext("Assigned Projects: *");

/* templates//manage/groups.tpl.html */
ev_gettext("Users: *");

/* templates//manage/groups.tpl.html */
ev_gettext("Manager: *");

/* templates//manage/groups.tpl.html */
ev_gettext("-- Select One --");

/* templates//manage/groups.tpl.html */
ev_gettext("Update Group");

/* templates//manage/groups.tpl.html */
ev_gettext("Create Group");

/* templates//manage/groups.tpl.html */
ev_gettext("Reset");

/* templates//manage/groups.tpl.html */
ev_gettext("Existing Groups");

/* templates//manage/groups.tpl.html */
ev_gettext("Name");

/* templates//manage/groups.tpl.html */
ev_gettext("Description");

/* templates//manage/groups.tpl.html */
ev_gettext("Manager");

/* templates//manage/groups.tpl.html */
ev_gettext("Projects");

/* templates//manage/groups.tpl.html */
ev_gettext("No groups could be found.");

/* templates//manage/groups.tpl.html */
ev_gettext("All");

/* templates//manage/groups.tpl.html */
ev_gettext("delete");

/* templates//manage/groups.tpl.html */
ev_gettext("Delete");

/* templates//manage/releases.tpl.html */
ev_gettext("Please enter the title of this release.");

/* templates//manage/releases.tpl.html */
ev_gettext("Manage Releases");

/* templates//manage/releases.tpl.html */
ev_gettext("Current Project:");

/* templates//manage/releases.tpl.html */
ev_gettext("An error occurred while trying to add the new release.");

/* templates//manage/releases.tpl.html */
ev_gettext("Please enter the title for this new release.");

/* templates//manage/releases.tpl.html */
ev_gettext("Thank you, the release was added successfully.");

/* templates//manage/releases.tpl.html */
ev_gettext("An error occurred while trying to update the release information.");

/* templates//manage/releases.tpl.html */
ev_gettext("Please enter the title for this release.");

/* templates//manage/releases.tpl.html */
ev_gettext("Thank you, the release was updated successfully.");

/* templates//manage/releases.tpl.html */
ev_gettext("Title:");

/* templates//manage/releases.tpl.html */
ev_gettext("Tentative Date:");

/* templates//manage/releases.tpl.html */
ev_gettext("Status:");

/* templates//manage/releases.tpl.html */
ev_gettext("Available - Users may use this release");

/* templates//manage/releases.tpl.html */
ev_gettext("Unavailable - Users may NOT use this release");

/* templates//manage/releases.tpl.html */
ev_gettext("Update Release");

/* templates//manage/releases.tpl.html */
ev_gettext("Create Release");

/* templates//manage/releases.tpl.html */
ev_gettext("Reset");

/* templates//manage/releases.tpl.html */
ev_gettext("Existing Releases:");

/* templates//manage/releases.tpl.html */
ev_gettext("Please select at least one of the releases.");

/* templates//manage/releases.tpl.html */
ev_gettext("This action will remove the selected entries.");

/* templates//manage/releases.tpl.html */
ev_gettext("All");

/* templates//manage/releases.tpl.html */
ev_gettext("Title");

/* templates//manage/releases.tpl.html */
ev_gettext("Tentative Date");

/* templates//manage/releases.tpl.html */
ev_gettext("Status");

/* templates//manage/releases.tpl.html */
ev_gettext("update this entry");

/* templates//manage/releases.tpl.html */
ev_gettext("No releases could be found.");

/* templates//manage/releases.tpl.html */
ev_gettext("All");

/* templates//manage/releases.tpl.html */
ev_gettext("Delete");

/* templates//manage/statuses.tpl.html */
ev_gettext("Please enter the title of this status.");

/* templates//manage/statuses.tpl.html */
ev_gettext("Please enter the abbreviation of this status.");

/* templates//manage/statuses.tpl.html */
ev_gettext("Please enter the rank of this status.");

/* templates//manage/statuses.tpl.html */
ev_gettext("Please assign the appropriate projects for this status.");

/* templates//manage/statuses.tpl.html */
ev_gettext("Please enter the color of this status.");

/* templates//manage/statuses.tpl.html */
ev_gettext("Manage Statuses");

/* templates//manage/statuses.tpl.html */
ev_gettext("An error occurred while trying to add the new status.");

/* templates//manage/statuses.tpl.html */
ev_gettext("Please enter the title for this new status.");

/* templates//manage/statuses.tpl.html */
ev_gettext("Thank you, the status was added successfully.");

/* templates//manage/statuses.tpl.html */
ev_gettext("An error occurred while trying to update the status information.");

/* templates//manage/statuses.tpl.html */
ev_gettext("Please enter the title for this status.");

/* templates//manage/statuses.tpl.html */
ev_gettext("Thank you, the status was updated successfully.");

/* templates//manage/statuses.tpl.html */
ev_gettext("Title:");

/* templates//manage/statuses.tpl.html */
ev_gettext("Abbreviation:");

/* templates//manage/statuses.tpl.html */
ev_gettext("(three letter abbreviation)");

/* templates//manage/statuses.tpl.html */
ev_gettext("Rank:");

/* templates//manage/statuses.tpl.html */
ev_gettext("Closed Context ?");

/* templates//manage/statuses.tpl.html */
ev_gettext("Yes");

/* templates//manage/statuses.tpl.html */
ev_gettext("No");

/* templates//manage/statuses.tpl.html */
ev_gettext("Assigned Projects:");

/* templates//manage/statuses.tpl.html */
ev_gettext("Color:");

/* templates//manage/statuses.tpl.html */
ev_gettext("(this color will be used in the issue listing page)");

/* templates//manage/statuses.tpl.html */
ev_gettext("Update Status");

/* templates//manage/statuses.tpl.html */
ev_gettext("Create Status");

/* templates//manage/statuses.tpl.html */
ev_gettext("Reset");

/* templates//manage/statuses.tpl.html */
ev_gettext("Existing Statuses:");

/* templates//manage/statuses.tpl.html */
ev_gettext("Please select at least one of the statuses.");

/* templates//manage/statuses.tpl.html */
ev_gettext("This action will remove the selected entries. This will also update any nissues currently set to this status to a new status 'undefined'.");

/* templates//manage/statuses.tpl.html */
ev_gettext("All");

/* templates//manage/statuses.tpl.html */
ev_gettext("Rank");

/* templates//manage/statuses.tpl.html */
ev_gettext("Abbreviation");

/* templates//manage/statuses.tpl.html */
ev_gettext("Title");

/* templates//manage/statuses.tpl.html */
ev_gettext("Projects");

/* templates//manage/statuses.tpl.html */
ev_gettext("Color");

/* templates//manage/statuses.tpl.html */
ev_gettext("update this entry");

/* templates//manage/statuses.tpl.html */
ev_gettext("No statuses could be found.");

/* templates//manage/statuses.tpl.html */
ev_gettext("All");

/* templates//manage/statuses.tpl.html */
ev_gettext("Delete");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("Field");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("Operator");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("Value");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("Value");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("Manage Reminder Conditions");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("view reminder details");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("Reminder");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("view reminder action details");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("Action");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("An error occurred while trying to add the new condition.");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("Please enter the title for this new condition.");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("Thank you, the condition was added successfully.");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("An error occurred while trying to update the condition information.");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("Please enter the title for this condition.");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("Thank you, the condition was updated successfully.");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("Field:");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("Please choose an option");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("Operator:");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("Please choose an option");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("Value:");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("Please choose an option");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("Please choose a field");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("or");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("(in hours please)");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("Update Condition");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("Add Condition");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("Reset");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("Existing Conditions:");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("Back to Reminder Action List");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("Please select at least one of the conditions.");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("This action will remove the selected entries.");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("All");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("Field");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("Operator");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("Value");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("update this entry");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("No conditions could be found.");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("All");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("Delete");

/* templates//manage/reminder_conditions.tpl.html */
ev_gettext("Review SQL Query");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Please choose the project to be associated with this email account.");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Please choose the type of email server to be associated with this email account.");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Please enter the hostname for this email account.");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Please enter the port number for this email account.");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Please enter a valid port number for this email account.");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Please enter the port number for this email account.");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Please enter the IMAP folder for this email account.");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Please enter the username for this email account.");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Please enter the password for this email account.");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Manage Email Accounts");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("An error occurred while trying to add the new account.");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Thank you, the email account was added successfully.");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("An error occurred while trying to update the account information.");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Thank you, the account was updated successfully.");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Associated Project:");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Type:");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("IMAP");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("IMAP over SSL");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("IMAP over SSL (self-signed)");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("IMAP, no TLS");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("IMAP, with TLS");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("IMAP, with TLS (self-signed)");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("POP3");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("POP3 over SSL");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("POP3 over SSL (self-signed)");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("POP3, no TLS");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("POP3, with TLS");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("POP3, with TLS (self-signed)");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Hostname:");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Port:");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("(Tip: port defaults are 110 for POP3 servers and 143 for IMAP ones)");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("IMAP Folder:");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("(default folder is INBOX)");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Username:");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Password:");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Advanced Options:");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Only Download Unread Messages");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Leave Copy of Messages On Server");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Use account for non-subject based email/note/draft routing.\n                    <b> Note: </b>If you check this, you cannot leave a copy of messages on the server.</a>");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Test Settings");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Update Account");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Create Account");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Reset");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Existing Accounts:");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Please select at least one of the accounts.");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("This action will remove the selected entries.");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("All");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Associated Project");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Hostname");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Type");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Port");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Username");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Mailbox");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Auto-Creation of Issues");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("update this entry");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("No email accounts could be found.");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("All");

/* templates//manage/email_accounts.tpl.html */
ev_gettext("Delete");

/* templates//manage/faq.tpl.html */
ev_gettext("Please choose the project for this FAQ entry.");

/* templates//manage/faq.tpl.html */
ev_gettext("Please assign the appropriate support levels for this FAQ entry.");

/* templates//manage/faq.tpl.html */
ev_gettext("Please enter the rank of this FAQ entry.");

/* templates//manage/faq.tpl.html */
ev_gettext("Please enter a number for the rank of this FAQ entry.");

/* templates//manage/faq.tpl.html */
ev_gettext("Please enter the title of this FAQ entry.");

/* templates//manage/faq.tpl.html */
ev_gettext("Manage Internal FAQ");

/* templates//manage/faq.tpl.html */
ev_gettext("An error occurred while trying to add the FAQ entry.");

/* templates//manage/faq.tpl.html */
ev_gettext("Please enter the title for this FAQ entry.");

/* templates//manage/faq.tpl.html */
ev_gettext("Please enter the message for this FAQ entry.");

/* templates//manage/faq.tpl.html */
ev_gettext("Thank you, the FAQ entry was added successfully.");

/* templates//manage/faq.tpl.html */
ev_gettext("An error occurred while trying to update the FAQ entry information.");

/* templates//manage/faq.tpl.html */
ev_gettext("Please enter the title for this FAQ entry.");

/* templates//manage/faq.tpl.html */
ev_gettext("Please enter the message for this FAQ entry.");

/* templates//manage/faq.tpl.html */
ev_gettext("Thank you, the FAQ entry was updated successfully.");

/* templates//manage/faq.tpl.html */
ev_gettext("Project:");

/* templates//manage/faq.tpl.html */
ev_gettext("Please choose an option");

/* templates//manage/faq.tpl.html */
ev_gettext("Assigned Support");

/* templates//manage/faq.tpl.html */
ev_gettext("Levels:");

/* templates//manage/faq.tpl.html */
ev_gettext("Rank:");

/* templates//manage/faq.tpl.html */
ev_gettext("Title:");

/* templates//manage/faq.tpl.html */
ev_gettext("Message:");

/* templates//manage/faq.tpl.html */
ev_gettext("Update FAQ Entry");

/* templates//manage/faq.tpl.html */
ev_gettext("Create FAQ Entry");

/* templates//manage/faq.tpl.html */
ev_gettext("Reset");

/* templates//manage/faq.tpl.html */
ev_gettext("Existing Internal FAQ Entries:");

/* templates//manage/faq.tpl.html */
ev_gettext("Please select at least one of the FAQ entries.");

/* templates//manage/faq.tpl.html */
ev_gettext("This action will permanently remove the selected FAQ entries.");

/* templates//manage/faq.tpl.html */
ev_gettext("All");

/* templates//manage/faq.tpl.html */
ev_gettext("Rank");

/* templates//manage/faq.tpl.html */
ev_gettext("Title");

/* templates//manage/faq.tpl.html */
ev_gettext("Support Levels");

/* templates//manage/faq.tpl.html */
ev_gettext("update this entry");

/* templates//manage/faq.tpl.html */
ev_gettext("No FAQ entries could be found.");

/* templates//manage/faq.tpl.html */
ev_gettext("All");

/* templates//manage/faq.tpl.html */
ev_gettext("Delete");

/* templates//manage/account_managers.tpl.html */
ev_gettext("Manage Customer Account Managers");

/* templates//manage/account_managers.tpl.html */
ev_gettext("An error occurred while trying to add the new account manager.");

/* templates//manage/account_managers.tpl.html */
ev_gettext("Thank you, the account manager was added successfully.");

/* templates//manage/account_managers.tpl.html */
ev_gettext("An error occurred while trying to update the account manager information.");

/* templates//manage/account_managers.tpl.html */
ev_gettext("Thank you, the account manager was updated successfully.");

/* templates//manage/account_managers.tpl.html */
ev_gettext("Project:");

/* templates//manage/account_managers.tpl.html */
ev_gettext("Please choose an option");

/* templates//manage/account_managers.tpl.html */
ev_gettext("Customer:");

/* templates//manage/account_managers.tpl.html */
ev_gettext("Account Manager:");

/* templates//manage/account_managers.tpl.html */
ev_gettext("Type:");

/* templates//manage/account_managers.tpl.html */
ev_gettext("Primary Technical Account Manager");

/* templates//manage/account_managers.tpl.html */
ev_gettext("Backup Technical Account Manager");

/* templates//manage/account_managers.tpl.html */
ev_gettext("Update Account Manager");

/* templates//manage/account_managers.tpl.html */
ev_gettext("Create Account Manager");

/* templates//manage/account_managers.tpl.html */
ev_gettext("Reset");

/* templates//manage/account_managers.tpl.html */
ev_gettext("Existing Customer Account Managers:");

/* templates//manage/account_managers.tpl.html */
ev_gettext("Please select at least one of the account managers.");

/* templates//manage/account_managers.tpl.html */
ev_gettext("This action will remove the selected account managers.");

/* templates//manage/account_managers.tpl.html */
ev_gettext("All");

/* templates//manage/account_managers.tpl.html */
ev_gettext("Customer");

/* templates//manage/account_managers.tpl.html */
ev_gettext("Account Manager");

/* templates//manage/account_managers.tpl.html */
ev_gettext("Type");

/* templates//manage/account_managers.tpl.html */
ev_gettext("update this entry");

/* templates//manage/account_managers.tpl.html */
ev_gettext("No account managers could be found.");

/* templates//manage/account_managers.tpl.html */
ev_gettext("All");

/* templates//manage/account_managers.tpl.html */
ev_gettext("Delete");

/* templates//manage/general.tpl.html */
ev_gettext("Please enter the sender address that will be used for all outgoing notification emails.");

/* templates//manage/general.tpl.html */
ev_gettext("Please enter the SMTP server hostname.");

/* templates//manage/general.tpl.html */
ev_gettext("Please enter the SMTP server port number.");

/* templates//manage/general.tpl.html */
ev_gettext("Please indicate whether the SMTP server requires authentication or not.");

/* templates//manage/general.tpl.html */
ev_gettext("Please enter the SMTP server username.");

/* templates//manage/general.tpl.html */
ev_gettext("Please enter the SMTP server password.");

/* templates//manage/general.tpl.html */
ev_gettext("Please enter the email address of where copies of outgoing emails should be sent to.");

/* templates//manage/general.tpl.html */
ev_gettext("Please choose whether the system should allow visitors to signup for new accounts or not.");

/* templates//manage/general.tpl.html */
ev_gettext("Please select the assigned projects for users that create their own accounts.");

/* templates//manage/general.tpl.html */
ev_gettext("Please enter the email address prefix for the email routing interface.");

/* templates//manage/general.tpl.html */
ev_gettext("Please enter the email address hostname for the email routing interface.");

/* templates//manage/general.tpl.html */
ev_gettext("Please choose whether the SCM integration feature should be enabled or not.");

/* templates//manage/general.tpl.html */
ev_gettext("Please enter the checkout page URL for your SCM integration tool.");

/* templates//manage/general.tpl.html */
ev_gettext("Please enter the diff page URL for your SCM integration tool.");

/* templates//manage/general.tpl.html */
ev_gettext("Please choose whether the email integration feature should be enabled or not.");

/* templates//manage/general.tpl.html */
ev_gettext("Please choose whether the daily tips feature should be enabled or not.");

/* templates//manage/general.tpl.html */
ev_gettext("General Setup");

/* templates//manage/general.tpl.html */
ev_gettext("ERROR: The system doesn't have the appropriate permissions to\n                    create the configuration file in the setup directory");

/* templates//manage/general.tpl.html */
ev_gettext("Please contact your local system\n                    administrator and ask for write privileges on the provided path.");

/* templates//manage/general.tpl.html */
ev_gettext("ERROR: The system doesn't have the appropriate permissions to\n                    update the configuration file in the setup directory");

/* templates//manage/general.tpl.html */
ev_gettext("Please contact your local system\n                    administrator and ask for write privileges on the provided filename.");

/* templates//manage/general.tpl.html */
ev_gettext("Thank you, the setup information was saved successfully.");

/* templates//manage/general.tpl.html */
ev_gettext("Tool Caption:");

/* templates//manage/general.tpl.html */
ev_gettext("SMTP (Outgoing Email) Settings:");

/* templates//manage/general.tpl.html */
ev_gettext("Sender Email");

/* templates//manage/general.tpl.html */
ev_gettext("(This MUST contain a real email address, i.e. \"eventum@example.com\" or \"Eventum <eventum@example.com>\")");

/* templates//manage/general.tpl.html */
ev_gettext("Hostname:");

/* templates//manage/general.tpl.html */
ev_gettext("Port:");

/* templates//manage/general.tpl.html */
ev_gettext("Requires Authentication?");

/* templates//manage/general.tpl.html */
ev_gettext("Yes");

/* templates//manage/general.tpl.html */
ev_gettext("No");

/* templates//manage/general.tpl.html */
ev_gettext("Username:");

/* templates//manage/general.tpl.html */
ev_gettext("Password:");

/* templates//manage/general.tpl.html */
ev_gettext("Save a Copy of Every Outgoing Issue Notification Email");

/* templates//manage/general.tpl.html */
ev_gettext("Email Address to Send Saved Messages:");

/* templates//manage/general.tpl.html */
ev_gettext("Open Account Signup:");

/* templates//manage/general.tpl.html */
ev_gettext("Enabled");

/* templates//manage/general.tpl.html */
ev_gettext("Disabled");

/* templates//manage/general.tpl.html */
ev_gettext("Assigned Projects:");

/* templates//manage/general.tpl.html */
ev_gettext("Assigned Role:");

/* templates//manage/general.tpl.html */
ev_gettext("Subject Based Routing:");

/* templates//manage/general.tpl.html */
ev_gettext("Enabled");

/* templates//manage/general.tpl.html */
ev_gettext("Disabled");

/* templates//manage/general.tpl.html */
ev_gettext("If enabled, Eventum will look in the subject line of incoming notes/emails to determine which issue they should be associated with.");

/* templates//manage/general.tpl.html */
ev_gettext("Email Recipient Type Flag:");

/* templates//manage/general.tpl.html */
ev_gettext("Recipient Type Flag:");

/* templates//manage/general.tpl.html */
ev_gettext("(This will be included in the From address of all emails sent by Eventum)");

/* templates//manage/general.tpl.html */
ev_gettext("Before Sender Name");

/* templates//manage/general.tpl.html */
ev_gettext("After Sender Name");

/* templates//manage/general.tpl.html */
ev_gettext("Email Routing Interface:");

/* templates//manage/general.tpl.html */
ev_gettext("Enabled");

/* templates//manage/general.tpl.html */
ev_gettext("Disabled");

/* templates//manage/general.tpl.html */
ev_gettext("Email Address Prefix:");

/* templates//manage/general.tpl.html */
ev_gettext("(i.e. <b>issue_</b>51@example.com)");

/* templates//manage/general.tpl.html */
ev_gettext("Address Hostname:");

/* templates//manage/general.tpl.html */
ev_gettext("(i.e. issue_51@<b>example.com</b>)");

/* templates//manage/general.tpl.html */
ev_gettext("Host Alias:");

/* templates//manage/general.tpl.html */
ev_gettext("(Alternate domains that point to 'Address Hostname')");

/* templates//manage/general.tpl.html */
ev_gettext("Warn Users Whether They Can Send Emails to Issue:");

/* templates//manage/general.tpl.html */
ev_gettext("Yes");

/* templates//manage/general.tpl.html */
ev_gettext("No");

/* templates//manage/general.tpl.html */
ev_gettext("Note Recipient Type Flag:");

/* templates//manage/general.tpl.html */
ev_gettext("Recipient Type Flag:");

/* templates//manage/general.tpl.html */
ev_gettext("(This will be included in the From address of all notes sent by Eventum)");

/* templates//manage/general.tpl.html */
ev_gettext("Before Sender Name");

/* templates//manage/general.tpl.html */
ev_gettext("After Sender Name");

/* templates//manage/general.tpl.html */
ev_gettext("Internal Note Routing Interface:");

/* templates//manage/general.tpl.html */
ev_gettext("Enabled");

/* templates//manage/general.tpl.html */
ev_gettext("Disabled");

/* templates//manage/general.tpl.html */
ev_gettext("Note Address Prefix:");

/* templates//manage/general.tpl.html */
ev_gettext("(i.e. <b>note_</b>51@example.com)");

/* templates//manage/general.tpl.html */
ev_gettext("Address Hostname:");

/* templates//manage/general.tpl.html */
ev_gettext("(i.e. note_51@<b>example.com</b>)");

/* templates//manage/general.tpl.html */
ev_gettext("Email Draft Interface:");

/* templates//manage/general.tpl.html */
ev_gettext("Enabled");

/* templates//manage/general.tpl.html */
ev_gettext("Disabled");

/* templates//manage/general.tpl.html */
ev_gettext("Draft Address Prefix:");

/* templates//manage/general.tpl.html */
ev_gettext("(i.e. <b>draft_</b>51@example.com)");

/* templates//manage/general.tpl.html */
ev_gettext("Address Hostname:");

/* templates//manage/general.tpl.html */
ev_gettext("(i.e. draft_51@<b>example.com</b>)");

/* templates//manage/general.tpl.html */
ev_gettext("SCM <br />Integration:");

/* templates//manage/general.tpl.html */
ev_gettext("Enabled");

/* templates//manage/general.tpl.html */
ev_gettext("Disabled");

/* templates//manage/general.tpl.html */
ev_gettext("Checkout Page:");

/* templates//manage/general.tpl.html */
ev_gettext("Diff Page:");

/* templates//manage/general.tpl.html */
ev_gettext("Email Integration Feature:");

/* templates//manage/general.tpl.html */
ev_gettext("Enabled");

/* templates//manage/general.tpl.html */
ev_gettext("Disabled");

/* templates//manage/general.tpl.html */
ev_gettext("Daily Tips:");

/* templates//manage/general.tpl.html */
ev_gettext("Enabled");

/* templates//manage/general.tpl.html */
ev_gettext("Disabled");

/* templates//manage/general.tpl.html */
ev_gettext("Email Spell Checker:");

/* templates//manage/general.tpl.html */
ev_gettext("Enabled");

/* templates//manage/general.tpl.html */
ev_gettext("Disabled");

/* templates//manage/general.tpl.html */
ev_gettext("(requires <a target=\"_aspell\" class=\"link\" href=\"http://aspell.sourceforge.net/\">aspell</a> installed in your server)");

/* templates//manage/general.tpl.html */
ev_gettext("IRC Notifications:");

/* templates//manage/general.tpl.html */
ev_gettext("Enabled");

/* templates//manage/general.tpl.html */
ev_gettext("Disabled");

/* templates//manage/general.tpl.html */
ev_gettext("Allow Un-Assigned Issues?");

/* templates//manage/general.tpl.html */
ev_gettext("Yes");

/* templates//manage/general.tpl.html */
ev_gettext("No");

/* templates//manage/general.tpl.html */
ev_gettext("Default Options for Notifications:");

/* templates//manage/general.tpl.html */
ev_gettext("Issues are Updated");

/* templates//manage/general.tpl.html */
ev_gettext("Issues are Closed");

/* templates//manage/general.tpl.html */
ev_gettext("Emails are Associated");

/* templates//manage/general.tpl.html */
ev_gettext("Files are Attached");

/* templates//manage/general.tpl.html */
ev_gettext("Email Reminder System Status Information:");

/* templates//manage/general.tpl.html */
ev_gettext("Enabled");

/* templates//manage/general.tpl.html */
ev_gettext("Disabled");

/* templates//manage/general.tpl.html */
ev_gettext("Email Addresses To Send Information To:");

/* templates//manage/general.tpl.html */
ev_gettext("(separate multiple addresses with commas)");

/* templates//manage/general.tpl.html */
ev_gettext("Email Error Logging System:");

/* templates//manage/general.tpl.html */
ev_gettext("Enabled");

/* templates//manage/general.tpl.html */
ev_gettext("Disabled");

/* templates//manage/general.tpl.html */
ev_gettext("Email Addresses To Send Errors To:");

/* templates//manage/general.tpl.html */
ev_gettext("(separate multiple addresses with commas)");

/* templates//manage/general.tpl.html */
ev_gettext("Update Setup");

/* templates//manage/general.tpl.html */
ev_gettext("Reset");

/* templates//manage/users.tpl.html */
ev_gettext("Please enter the email of this user.");

/* templates//manage/users.tpl.html */
ev_gettext("Please enter a valid email address.");

/* templates//manage/users.tpl.html */
ev_gettext("Please enter a password of at least 6 characters.");

/* templates//manage/users.tpl.html */
ev_gettext("Please enter a password of at least 6 characters.");

/* templates//manage/users.tpl.html */
ev_gettext("Please enter the full name of this user.");

/* templates//manage/users.tpl.html */
ev_gettext("Please assign the appropriate projects for this user.");

/* templates//manage/users.tpl.html */
ev_gettext("Manage Users");

/* templates//manage/users.tpl.html */
ev_gettext("An error occurred while trying to add the new user.");

/* templates//manage/users.tpl.html */
ev_gettext("Thank you, the user was added successfully.");

/* templates//manage/users.tpl.html */
ev_gettext("An error occurred while trying to update the user information.");

/* templates//manage/users.tpl.html */
ev_gettext("Thank you, the user was updated successfully.");

/* templates//manage/users.tpl.html */
ev_gettext("Email Address");

/* templates//manage/users.tpl.html */
ev_gettext("Password");

/* templates//manage/users.tpl.html */
ev_gettext("leave empty to keep the current password");

/* templates//manage/users.tpl.html */
ev_gettext("Full Name");

/* templates//manage/users.tpl.html */
ev_gettext("Assigned Projects and Roles");

/* templates//manage/users.tpl.html */
ev_gettext("Customer");

/* templates//manage/users.tpl.html */
ev_gettext("Update User");

/* templates//manage/users.tpl.html */
ev_gettext("Create User");

/* templates//manage/users.tpl.html */
ev_gettext("Reset");

/* templates//manage/users.tpl.html */
ev_gettext("Existing Users");

/* templates//manage/users.tpl.html */
ev_gettext("You cannot change the status of the only active user left in the system.");

/* templates//manage/users.tpl.html */
ev_gettext("You cannot inactivate all of the users in the system.");

/* templates//manage/users.tpl.html */
ev_gettext("Please select at least one of the users.");

/* templates//manage/users.tpl.html */
ev_gettext("This action will change the status of the selected users.");

/* templates//manage/users.tpl.html */
ev_gettext("All");

/* templates//manage/users.tpl.html */
ev_gettext("Full Name");

/* templates//manage/users.tpl.html */
ev_gettext("Role");

/* templates//manage/users.tpl.html */
ev_gettext("Email Address");

/* templates//manage/users.tpl.html */
ev_gettext("Status");

/* templates//manage/users.tpl.html */
ev_gettext("Group");

/* templates//manage/users.tpl.html */
ev_gettext("update this entry");

/* templates//manage/users.tpl.html */
ev_gettext("send email to");

/* templates//manage/users.tpl.html */
ev_gettext("No users could be found.");

/* templates//manage/users.tpl.html */
ev_gettext("All");

/* templates//manage/users.tpl.html */
ev_gettext("Update Status");

/* templates//manage/users.tpl.html */
ev_gettext("Active");

/* templates//manage/users.tpl.html */
ev_gettext("Inactive");

/* templates//manage/users.tpl.html */
ev_gettext("Show Customers");

/* templates//manage/categories.tpl.html */
ev_gettext("Please enter the title of this category");

/* templates//manage/categories.tpl.html */
ev_gettext("Manage Categories");

/* templates//manage/categories.tpl.html */
ev_gettext("Current Project");

/* templates//manage/categories.tpl.html */
ev_gettext("An error occurred while trying to add the new category.");

/* templates//manage/categories.tpl.html */
ev_gettext("Please enter the title for this new category.");

/* templates//manage/categories.tpl.html */
ev_gettext("Thank you, the category was added successfully.");

/* templates//manage/categories.tpl.html */
ev_gettext("An error occurred while trying to update the category information.");

/* templates//manage/categories.tpl.html */
ev_gettext("Please enter the title for this category.");

/* templates//manage/categories.tpl.html */
ev_gettext("Thank you, the category was updated successfully.");

/* templates//manage/categories.tpl.html */
ev_gettext("Update Category");

/* templates//manage/categories.tpl.html */
ev_gettext("Create Category");

/* templates//manage/categories.tpl.html */
ev_gettext("Reset");

/* templates//manage/categories.tpl.html */
ev_gettext("Existing Categories:");

/* templates//manage/categories.tpl.html */
ev_gettext("Please select at least one of the categories.");

/* templates//manage/categories.tpl.html */
ev_gettext("This action will remove the selected entries.");

/* templates//manage/categories.tpl.html */
ev_gettext("All");

/* templates//manage/categories.tpl.html */
ev_gettext("Title");

/* templates//manage/categories.tpl.html */
ev_gettext("update this entry");

/* templates//manage/categories.tpl.html */
ev_gettext("No categories could be found.");

/* templates//manage/categories.tpl.html */
ev_gettext("All");

/* templates//manage/categories.tpl.html */
ev_gettext("Delete");

/* templates//manage/priorities.tpl.html */
ev_gettext("Please enter the title of this priority");

/* templates//manage/priorities.tpl.html */
ev_gettext("Please enter the rank of this priority");

/* templates//manage/priorities.tpl.html */
ev_gettext("Manage Priorities");

/* templates//manage/priorities.tpl.html */
ev_gettext("Current Project");

/* templates//manage/priorities.tpl.html */
ev_gettext("An error occurred while trying to add the new priority.");

/* templates//manage/priorities.tpl.html */
ev_gettext("Please enter the title for this new priority.");

/* templates//manage/priorities.tpl.html */
ev_gettext("Thank you, the priority was added successfully.");

/* templates//manage/priorities.tpl.html */
ev_gettext("An error occurred while trying to update the priority information.");

/* templates//manage/priorities.tpl.html */
ev_gettext("Please enter the title for this priority.");

/* templates//manage/priorities.tpl.html */
ev_gettext("Thank you, the priority was updated successfully.");

/* templates//manage/priorities.tpl.html */
ev_gettext("Title");

/* templates//manage/priorities.tpl.html */
ev_gettext("Rank");

/* templates//manage/priorities.tpl.html */
ev_gettext("Update Priority");

/* templates//manage/priorities.tpl.html */
ev_gettext("Create Priority");

/* templates//manage/priorities.tpl.html */
ev_gettext("Reset");

/* templates//manage/priorities.tpl.html */
ev_gettext("Existing Priorities");

/* templates//manage/priorities.tpl.html */
ev_gettext("Please select at least one of the priorities.");

/* templates//manage/priorities.tpl.html */
ev_gettext("This action will remove the selected entries.");

/* templates//manage/priorities.tpl.html */
ev_gettext("All");

/* templates//manage/priorities.tpl.html */
ev_gettext("Rank");

/* templates//manage/priorities.tpl.html */
ev_gettext("Title");

/* templates//manage/priorities.tpl.html */
ev_gettext("update this entry");

/* templates//manage/priorities.tpl.html */
ev_gettext("No priorities could be found.");

/* templates//manage/priorities.tpl.html */
ev_gettext("All");

/* templates//manage/priorities.tpl.html */
ev_gettext("Delete");

/* templates//manage/customer_notes.tpl.html */
ev_gettext("Please choose the customer for this new note.");

/* templates//manage/customer_notes.tpl.html */
ev_gettext("Manage Customer Quick Notes");

/* templates//manage/customer_notes.tpl.html */
ev_gettext("An error occurred while trying to add the new note.");

/* templates//manage/customer_notes.tpl.html */
ev_gettext("Thank you, the note was added successfully.");

/* templates//manage/customer_notes.tpl.html */
ev_gettext("An error occurred while trying to update the note.");

/* templates//manage/customer_notes.tpl.html */
ev_gettext("Thank you, the note was updated successfully.");

/* templates//manage/customer_notes.tpl.html */
ev_gettext("An error occurred while trying to delete the note.");

/* templates//manage/customer_notes.tpl.html */
ev_gettext("Thank you, the note was deleted successfully.");

/* templates//manage/customer_notes.tpl.html */
ev_gettext("Project");

/* templates//manage/customer_notes.tpl.html */
ev_gettext("Please choose an option");

/* templates//manage/customer_notes.tpl.html */
ev_gettext("Customer");

/* templates//manage/customer_notes.tpl.html */
ev_gettext("Please choose a customer");

/* templates//manage/customer_notes.tpl.html */
ev_gettext("Note");

/* templates//manage/customer_notes.tpl.html */
ev_gettext("Update Note");

/* templates//manage/customer_notes.tpl.html */
ev_gettext("Create Note");

/* templates//manage/customer_notes.tpl.html */
ev_gettext("Reset");

/* templates//manage/customer_notes.tpl.html */
ev_gettext("Existing Customer Quick Notes");

/* templates//manage/customer_notes.tpl.html */
ev_gettext("Please select at least one of the notes.");

/* templates//manage/customer_notes.tpl.html */
ev_gettext("This action will permanently remove the selected entries.");

/* templates//manage/customer_notes.tpl.html */
ev_gettext("Customer");

/* templates//manage/customer_notes.tpl.html */
ev_gettext("Note");

/* templates//manage/customer_notes.tpl.html */
ev_gettext("No notes could be found.");

/* templates//manage/customer_notes.tpl.html */
ev_gettext("All");

/* templates//manage/customer_notes.tpl.html */
ev_gettext("Delete");

/* templates//manage/field_display.tpl.html */
ev_gettext("This page can only be accessed in relation to a project. Please go to the project page and choose\n\"Edit Fields to Display\" to access this page.");

/* templates//manage/field_display.tpl.html */
ev_gettext("Manage Projects");

/* templates//manage/field_display.tpl.html */
ev_gettext("Edit Fields to Display");

/* templates//manage/field_display.tpl.html */
ev_gettext("An error occurred while trying to update field display settings.");

/* templates//manage/field_display.tpl.html */
ev_gettext("Thank you, field display settings were updated successfully.");

/* templates//manage/field_display.tpl.html */
ev_gettext("Field");

/* templates//manage/field_display.tpl.html */
ev_gettext("Set Display Preferences");

/* templates//manage/field_display.tpl.html */
ev_gettext("Reset");

/* templates//manage/column_display.tpl.html */
ev_gettext("This page can only be accessed in relation to a project. Please go to the project page and choose\n\"Edit Fields to Display\" to access this page.");

/* templates//manage/column_display.tpl.html */
ev_gettext("Manage Projects");

/* templates//manage/column_display.tpl.html */
ev_gettext("Manage Columns to Display");

/* templates//manage/column_display.tpl.html */
ev_gettext("Current Project");

/* templates//manage/column_display.tpl.html */
ev_gettext("An error occurred while trying to save columns to display.");

/* templates//manage/column_display.tpl.html */
ev_gettext("Thank you, columns to display was saved successfully.");

/* templates//manage/column_display.tpl.html */
ev_gettext("Column Name");

/* templates//manage/column_display.tpl.html */
ev_gettext("Minimum Role");

/* templates//manage/column_display.tpl.html */
ev_gettext("Order");

/* templates//manage/column_display.tpl.html */
ev_gettext("Save");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Please enter the title of this custom field.");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Please assign the appropriate projects for this custom field.");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("WARNING: You have removed project(s)");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("from the list");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("of associated projects. This will remove all data for this field from the selected project(s).");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Do you want to continue?");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Please enter the new value for the combo box.");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("The specified value already exists in the list of options.");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Please enter the updated value.");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Please select an option from the list.");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Please select an option from the list.");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("enter a new option above");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Manage Custom Fields");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("An error occurred while trying to add the new custom field.");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Thank you, the custom field was added successfully.");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("An error occurred while trying to update the custom field information.");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Thank you, the custom field was updated successfully.");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Title");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Short Description");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("it will show up by the side of the field");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Assigned Projects");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Target Forms");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Report Form");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Required Field");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Anonymous Form");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Required Field");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Display on List Issues Page");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Yes");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("No");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Field Type");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Text Input");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Textarea");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Combo Box");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Multiple Combo Box");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Date");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Field Options");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Set available options");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Add");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Update Value");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("OR");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Choose Custom Field Backend");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Please select a backend");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Please select a backend");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("enter a new option above");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Edit Option");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Remove");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Minimum Role");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Rank");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Update Custom Field");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Create Custom Field");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Reset");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Existing Custom Fields");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Please select at least one of the custom fields.");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("This action will permanently remove the selected custom fields.");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("delete");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Rank");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Title");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Assigned Projects");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Min. Role");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Type");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Options");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("move field down");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("move field up");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("update this entry");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Combo Box");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Multiple Combo Box");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Textarea");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Date");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Text Input");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("No custom fields could be found.");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("All");

/* templates//manage/custom_fields.tpl.html */
ev_gettext("Delete");

/* templates//manage/reminders.tpl.html */
ev_gettext("Manage Issue Reminders");

/* templates//manage/reminders.tpl.html */
ev_gettext("Updating Reminder");

/* templates//manage/reminders.tpl.html */
ev_gettext("Creating New Reminder");

/* templates//manage/reminders.tpl.html */
ev_gettext("An error occurred while trying to add the new reminder.");

/* templates//manage/reminders.tpl.html */
ev_gettext("Please enter the title for this new reminder.");

/* templates//manage/reminders.tpl.html */
ev_gettext("Thank you, the reminder was added successfully.");

/* templates//manage/reminders.tpl.html */
ev_gettext("An error occurred while trying to update the reminder information.");

/* templates//manage/reminders.tpl.html */
ev_gettext("Please enter the title for this reminder.");

/* templates//manage/reminders.tpl.html */
ev_gettext("Thank you, the reminder was updated successfully.");

/* templates//manage/reminders.tpl.html */
ev_gettext("Please choose a project that will be associated with this reminder.");

/* templates//manage/reminders.tpl.html */
ev_gettext("Please enter the title for this reminder.");

/* templates//manage/reminders.tpl.html */
ev_gettext("Please enter the rank for this reminder.");

/* templates//manage/reminders.tpl.html */
ev_gettext("Please choose the support levels that will be associated with this reminder.");

/* templates//manage/reminders.tpl.html */
ev_gettext("Please choose the customers that will be associated with this reminder.");

/* templates//manage/reminders.tpl.html */
ev_gettext("Please enter the issue IDs that will be associated with this reminder.");

/* templates//manage/reminders.tpl.html */
ev_gettext("Please choose the priorities that will be associated with this reminder.");

/* templates//manage/reminders.tpl.html */
ev_gettext("Please choose an option");

/* templates//manage/reminders.tpl.html */
ev_gettext("Title");

/* templates//manage/reminders.tpl.html */
ev_gettext("Rank");

/* templates//manage/reminders.tpl.html */
ev_gettext("Reminder Type");

/* templates//manage/reminders.tpl.html */
ev_gettext("By Support Level");

/* templates//manage/reminders.tpl.html */
ev_gettext("By Customer");

/* templates//manage/reminders.tpl.html */
ev_gettext("By Issue ID");

/* templates//manage/reminders.tpl.html */
ev_gettext("All Issues");

/* templates//manage/reminders.tpl.html */
ev_gettext("Also Filter By Issue Priorities");

/* templates//manage/reminders.tpl.html */
ev_gettext("Skip Weekends");

/* templates//manage/reminders.tpl.html */
ev_gettext("Yes");

/* templates//manage/reminders.tpl.html */
ev_gettext("No");

/* templates//manage/reminders.tpl.html */
ev_gettext("If yes, this reminder will not activate on weekends and time will not accumulate on the weekends.");

/* templates//manage/reminders.tpl.html */
ev_gettext("Update Reminder");

/* templates//manage/reminders.tpl.html */
ev_gettext("Create Reminder");

/* templates//manage/reminders.tpl.html */
ev_gettext("Reset");

/* templates//manage/reminders.tpl.html */
ev_gettext("Existing Issue Reminders");

/* templates//manage/reminders.tpl.html */
ev_gettext("Please select at least one of the reminders.");

/* templates//manage/reminders.tpl.html */
ev_gettext("This action will remove the selected entries.");

/* templates//manage/reminders.tpl.html */
ev_gettext("ID");

/* templates//manage/reminders.tpl.html */
ev_gettext("Rank");

/* templates//manage/reminders.tpl.html */
ev_gettext("Title");

/* templates//manage/reminders.tpl.html */
ev_gettext("Project");

/* templates//manage/reminders.tpl.html */
ev_gettext("Type");

/* templates//manage/reminders.tpl.html */
ev_gettext("Issue Priorities");

/* templates//manage/reminders.tpl.html */
ev_gettext("Details");

/* templates//manage/reminders.tpl.html */
ev_gettext("update this entry");

/* templates//manage/reminders.tpl.html */
ev_gettext("All Issues");

/* templates//manage/reminders.tpl.html */
ev_gettext("By Support Level");

/* templates//manage/reminders.tpl.html */
ev_gettext("By Customer");

/* templates//manage/reminders.tpl.html */
ev_gettext("By Issue ID");

/* templates//manage/reminders.tpl.html */
ev_gettext("No reminders could be found.");

/* templates//manage/reminders.tpl.html */
ev_gettext("All");

/* templates//manage/reminders.tpl.html */
ev_gettext("Delete");

/* templates//manage/link_filters.tpl.html */
ev_gettext("Please enter a pattern.");

/* templates//manage/link_filters.tpl.html */
ev_gettext("Please enter a replacement value.");

/* templates//manage/link_filters.tpl.html */
ev_gettext("Please select projects this link filter should be active for.");

/* templates//manage/link_filters.tpl.html */
ev_gettext("Please select the minimum user role that should be able to see this link filter.");

/* templates//manage/link_filters.tpl.html */
ev_gettext("Please select at least one link filter.");

/* templates//manage/link_filters.tpl.html */
ev_gettext("WARNING: This action will remove the selected link filters permanently.");

/* templates//manage/link_filters.tpl.html */
ev_gettext("Please click OK to confirm.");

/* templates//manage/link_filters.tpl.html */
ev_gettext("Manage Link Filters");

/* templates//manage/link_filters.tpl.html */
ev_gettext("An error occurred while trying to add the new link filter.");

/* templates//manage/link_filters.tpl.html */
ev_gettext("Thank you, the link filter was added successfully.");

/* templates//manage/link_filters.tpl.html */
ev_gettext("An error occurred while trying to update the link filter.");

/* templates//manage/link_filters.tpl.html */
ev_gettext("Thank you, the link filter was updated successfully.");

/* templates//manage/link_filters.tpl.html */
ev_gettext("An error occurred while trying to delete the link filter.");

/* templates//manage/link_filters.tpl.html */
ev_gettext("Thank you, the link filter was deleted successfully.");

/* templates//manage/link_filters.tpl.html */
ev_gettext("Pattern");

/* templates//manage/link_filters.tpl.html */
ev_gettext("Replacement");

/* templates//manage/link_filters.tpl.html */
ev_gettext("Description");

/* templates//manage/link_filters.tpl.html */
ev_gettext("Assigned Projects");

/* templates//manage/link_filters.tpl.html */
ev_gettext("Minimum User Role");

/* templates//manage/link_filters.tpl.html */
ev_gettext("Update Link Filter");

/* templates//manage/link_filters.tpl.html */
ev_gettext("Create Link Filter");

/* templates//manage/link_filters.tpl.html */
ev_gettext("Reset");

/* templates//manage/link_filters.tpl.html */
ev_gettext("Existing Link Filters");

/* templates//manage/link_filters.tpl.html */
ev_gettext("Pattern");

/* templates//manage/link_filters.tpl.html */
ev_gettext("Replacement");

/* templates//manage/link_filters.tpl.html */
ev_gettext("Description");

/* templates//manage/link_filters.tpl.html */
ev_gettext("Minimum Role");

/* templates//manage/link_filters.tpl.html */
ev_gettext("Projects");

/* templates//manage/link_filters.tpl.html */
ev_gettext("update this entry");

/* templates//manage/link_filters.tpl.html */
ev_gettext("No link filters could be found.");

/* templates//manage/link_filters.tpl.html */
ev_gettext("All");

/* templates//manage/link_filters.tpl.html */
ev_gettext("Delete");

/* templates//manage/news.tpl.html */
ev_gettext("Please enter the title of this news entry.");

/* templates//manage/news.tpl.html */
ev_gettext("Please assign the appropriate projects for this news entry.");

/* templates//manage/news.tpl.html */
ev_gettext("Manage News");

/* templates//manage/news.tpl.html */
ev_gettext("An error occurred while trying to add the news entry.");

/* templates//manage/news.tpl.html */
ev_gettext("Please enter the title for this news entry.");

/* templates//manage/news.tpl.html */
ev_gettext("Please enter the message for this news entry.");

/* templates//manage/news.tpl.html */
ev_gettext("Thank you, the news entry was added successfully.");

/* templates//manage/news.tpl.html */
ev_gettext("An error occurred while trying to update the news entry information.");

/* templates//manage/news.tpl.html */
ev_gettext("Please enter the title for this news entry.");

/* templates//manage/news.tpl.html */
ev_gettext("Please enter the message for this news entry.");

/* templates//manage/news.tpl.html */
ev_gettext("Thank you, the news entry was updated successfully.");

/* templates//manage/news.tpl.html */
ev_gettext("Assigned Projects");

/* templates//manage/news.tpl.html */
ev_gettext("Status");

/* templates//manage/news.tpl.html */
ev_gettext("Active");

/* templates//manage/news.tpl.html */
ev_gettext("Inactive");

/* templates//manage/news.tpl.html */
ev_gettext("Title");

/* templates//manage/news.tpl.html */
ev_gettext("Message");

/* templates//manage/news.tpl.html */
ev_gettext("Update News Entry");

/* templates//manage/news.tpl.html */
ev_gettext("Create News Entry");

/* templates//manage/news.tpl.html */
ev_gettext("Reset");

/* templates//manage/news.tpl.html */
ev_gettext("Existing News Entries");

/* templates//manage/news.tpl.html */
ev_gettext("Please select at least one of the news entries.");

/* templates//manage/news.tpl.html */
ev_gettext("This action will permanently remove the selected news entries.");

/* templates//manage/news.tpl.html */
ev_gettext("Title");

/* templates//manage/news.tpl.html */
ev_gettext("Projects");

/* templates//manage/news.tpl.html */
ev_gettext("Status");

/* templates//manage/news.tpl.html */
ev_gettext("update this entry");

/* templates//manage/news.tpl.html */
ev_gettext("No news entries could be found.");

/* templates//manage/news.tpl.html */
ev_gettext("All");

/* templates//manage/news.tpl.html */
ev_gettext("Delete");

/* templates//manage/manage.tpl.html */
ev_gettext("Sorry, but you do not have the required permission level to access this screen.");

/* templates//manage/manage.tpl.html */
ev_gettext("Go Back");

/* templates//manage/manage.tpl.html */
ev_gettext("Configuration");

/* templates//manage/manage.tpl.html */
ev_gettext("General Setup");

/* templates//manage/manage.tpl.html */
ev_gettext("Manage Email Accounts");

/* templates//manage/manage.tpl.html */
ev_gettext("Manage Custom Fields");

/* templates//manage/manage.tpl.html */
ev_gettext("Customize Issue Listing Screen");

/* templates//manage/manage.tpl.html */
ev_gettext("Areas");

/* templates//manage/manage.tpl.html */
ev_gettext("Manage Internal FAQ");

/* templates//manage/manage.tpl.html */
ev_gettext("Manage Round Robin Assignments");

/* templates//manage/manage.tpl.html */
ev_gettext("Manage News");

/* templates//manage/manage.tpl.html */
ev_gettext("Manage Issue Reminders");

/* templates//manage/manage.tpl.html */
ev_gettext("Manage Customer Account Managers");

/* templates//manage/manage.tpl.html */
ev_gettext("Manage Customer Quick Notes");

/* templates//manage/manage.tpl.html */
ev_gettext("Manage Statuses");

/* templates//manage/manage.tpl.html */
ev_gettext("Manage Projects");

/* templates//manage/manage.tpl.html */
ev_gettext("Add / Edit Releases");

/* templates//manage/manage.tpl.html */
ev_gettext("Add / Edit Categories");

/* templates//manage/manage.tpl.html */
ev_gettext("Add / Edit Priorities");

/* templates//manage/manage.tpl.html */
ev_gettext("Add / Edit Phone Support Categories");

/* templates//manage/manage.tpl.html */
ev_gettext("Anonymous Reporting Options");

/* templates//manage/manage.tpl.html */
ev_gettext("Edit Fields to Display");

/* templates//manage/manage.tpl.html */
ev_gettext("Edit Columns to Display");

/* templates//manage/manage.tpl.html */
ev_gettext("Manage Users");

/* templates//manage/manage.tpl.html */
ev_gettext("Manage Groups");

/* templates//manage/manage.tpl.html */
ev_gettext("Manage Time Tracking Categories");

/* templates//manage/manage.tpl.html */
ev_gettext("Manage Issue Resolutions");

/* templates//manage/manage.tpl.html */
ev_gettext("Manage Canned Email Responses");

/* templates//manage/manage.tpl.html */
ev_gettext("Manage Link Filters");

/* templates//manage/projects.tpl.html */
ev_gettext("Please enter the title of this project.");

/* templates//manage/projects.tpl.html */
ev_gettext("Please assign the users for this project.");

/* templates//manage/projects.tpl.html */
ev_gettext("Please assign the statuses for this project.");

/* templates//manage/projects.tpl.html */
ev_gettext("Please choose the initial status from one of the assigned statuses of this project.");

/* templates//manage/projects.tpl.html */
ev_gettext("Please enter a valid outgoing sender address for this project.");

/* templates//manage/projects.tpl.html */
ev_gettext("Manage Projects");

/* templates//manage/projects.tpl.html */
ev_gettext("An error occurred while trying to add the new project.");

/* templates//manage/projects.tpl.html */
ev_gettext("Please enter the title for this new project.");

/* templates//manage/projects.tpl.html */
ev_gettext("Thank you, the project was added successfully.");

/* templates//manage/projects.tpl.html */
ev_gettext("An error occurred while trying to update the project information.");

/* templates//manage/projects.tpl.html */
ev_gettext("Please enter the title for this project.");

/* templates//manage/projects.tpl.html */
ev_gettext("Thank you, the project was updated successfully.");

/* templates//manage/projects.tpl.html */
ev_gettext("Title");

/* templates//manage/projects.tpl.html */
ev_gettext("Status");

/* templates//manage/projects.tpl.html */
ev_gettext("Active");

/* templates//manage/projects.tpl.html */
ev_gettext("Archived");

/* templates//manage/projects.tpl.html */
ev_gettext("Customer Integration Backend");

/* templates//manage/projects.tpl.html */
ev_gettext("No Customer Integration");

/* templates//manage/projects.tpl.html */
ev_gettext("Workflow Backend");

/* templates//manage/projects.tpl.html */
ev_gettext("No Workflow Management");

/* templates//manage/projects.tpl.html */
ev_gettext("Project Lead");

/* templates//manage/projects.tpl.html */
ev_gettext("Users");

/* templates//manage/projects.tpl.html */
ev_gettext("Statuses");

/* templates//manage/projects.tpl.html */
ev_gettext("Initial Status for New Issues");

/* templates//manage/projects.tpl.html */
ev_gettext("Outgoing Email Sender Name");

/* templates//manage/projects.tpl.html */
ev_gettext("Outgoing Email Sender Address");

/* templates//manage/projects.tpl.html */
ev_gettext("Remote Invocation");

/* templates//manage/projects.tpl.html */
ev_gettext("Enabled");

/* templates//manage/projects.tpl.html */
ev_gettext("Disabled");

/* templates//manage/projects.tpl.html */
ev_gettext("Segregate Reporters");

/* templates//manage/projects.tpl.html */
ev_gettext("Yes");

/* templates//manage/projects.tpl.html */
ev_gettext("No");

/* templates//manage/projects.tpl.html */
ev_gettext("Update Project");

/* templates//manage/projects.tpl.html */
ev_gettext("Create Project");

/* templates//manage/projects.tpl.html */
ev_gettext("Reset");

/* templates//manage/projects.tpl.html */
ev_gettext("Existing Projects");

/* templates//manage/projects.tpl.html */
ev_gettext("You cannot remove all of the projects in the system.");

/* templates//manage/projects.tpl.html */
ev_gettext("Please select at least one of the projects.");

/* templates//manage/projects.tpl.html */
ev_gettext("WARNING: This action will remove the selected projects permanently.");

/* templates//manage/projects.tpl.html */
ev_gettext("It will remove all of its associated entries as well (issues, notes, attachments,netc), so please click OK to confirm.");

/* templates//manage/projects.tpl.html */
ev_gettext("All");

/* templates//manage/projects.tpl.html */
ev_gettext("Title");

/* templates//manage/projects.tpl.html */
ev_gettext("Project Lead");

/* templates//manage/projects.tpl.html */
ev_gettext("Status");

/* templates//manage/projects.tpl.html */
ev_gettext("Actions");

/* templates//manage/projects.tpl.html */
ev_gettext("Edit Releases");

/* templates//manage/projects.tpl.html */
ev_gettext("Edit Categories");

/* templates//manage/projects.tpl.html */
ev_gettext("Edit Priorities");

/* templates//manage/projects.tpl.html */
ev_gettext("Edit Phone Support Categories");

/* templates//manage/projects.tpl.html */
ev_gettext("Anonymous Reporting");

/* templates//manage/projects.tpl.html */
ev_gettext("Edit Fields to Display");

/* templates//manage/projects.tpl.html */
ev_gettext("Edit Columns to Display");

/* templates//manage/projects.tpl.html */
ev_gettext("No projects could be found.");

/* templates//manage/projects.tpl.html */
ev_gettext("All");

/* templates//manage/projects.tpl.html */
ev_gettext("Delete");

/* templates//manage/phone_categories.tpl.html */
ev_gettext("Please enter the title of this category");

/* templates//manage/phone_categories.tpl.html */
ev_gettext("Manage Phone Support Categories");

/* templates//manage/phone_categories.tpl.html */
ev_gettext("Current Project");

/* templates//manage/phone_categories.tpl.html */
ev_gettext("An error occurred while trying to add the new category.");

/* templates//manage/phone_categories.tpl.html */
ev_gettext("Please enter the title for this new category.");

/* templates//manage/phone_categories.tpl.html */
ev_gettext("Thank you, the category was added successfully.");

/* templates//manage/phone_categories.tpl.html */
ev_gettext("An error occurred while trying to update the category information.");

/* templates//manage/phone_categories.tpl.html */
ev_gettext("Please enter the title for this category.");

/* templates//manage/phone_categories.tpl.html */
ev_gettext("Thank you, the category was updated successfully.");

/* templates//manage/phone_categories.tpl.html */
ev_gettext("Title");

/* templates//manage/phone_categories.tpl.html */
ev_gettext("Update Category");

/* templates//manage/phone_categories.tpl.html */
ev_gettext("Create Category");

/* templates//manage/phone_categories.tpl.html */
ev_gettext("Reset");

/* templates//manage/phone_categories.tpl.html */
ev_gettext("Existing Phone Support Categories");

/* templates//manage/phone_categories.tpl.html */
ev_gettext("Please select at least one of the categories.");

/* templates//manage/phone_categories.tpl.html */
ev_gettext("This action will remove the selected entries.");

/* templates//manage/phone_categories.tpl.html */
ev_gettext("All");

/* templates//manage/phone_categories.tpl.html */
ev_gettext("Title");

/* templates//manage/phone_categories.tpl.html */
ev_gettext("update this entry");

/* templates//manage/phone_categories.tpl.html */
ev_gettext("No phone support categories could be found.");

/* templates//manage/phone_categories.tpl.html */
ev_gettext("All");

/* templates//manage/phone_categories.tpl.html */
ev_gettext("Delete");

/* templates//attached_emails.tpl.html */
ev_gettext("Please choose which entries need to be removed.");

/* templates//attached_emails.tpl.html */
ev_gettext("Attached Emails");

/* templates//attached_emails.tpl.html */
ev_gettext("Remove?");

/* templates//attached_emails.tpl.html */
ev_gettext("Sender");

/* templates//attached_emails.tpl.html */
ev_gettext("Subject");

/* templates//attached_emails.tpl.html */
ev_gettext("Remove Selected");

/* templates//preferences.tpl.html */
ev_gettext("Please enter your full name.");

/* templates//preferences.tpl.html */
ev_gettext("Please enter a valid email address.");

/* templates//preferences.tpl.html */
ev_gettext("Please enter your new password with at least 6 characters.");

/* templates//preferences.tpl.html */
ev_gettext("The two passwords do not match. Please review your information and try again.");

/* templates//preferences.tpl.html */
ev_gettext("User Details");

/* templates//preferences.tpl.html */
ev_gettext("An error occurred while trying to run your query.");

/* templates//preferences.tpl.html */
ev_gettext("Thank you, your full name was updated successfully.");

/* templates//preferences.tpl.html */
ev_gettext("Full Name");

/* templates//preferences.tpl.html */
ev_gettext("Update Full Name");

/* templates//preferences.tpl.html */
ev_gettext("Reset");

/* templates//preferences.tpl.html */
ev_gettext("An error occurred while trying to run your query.");

/* templates//preferences.tpl.html */
ev_gettext("Thank you, your email address was updated successfully.");

/* templates//preferences.tpl.html */
ev_gettext("Login");

/* templates//preferences.tpl.html */
ev_gettext("Email Address");

/* templates//preferences.tpl.html */
ev_gettext("Update Email Address");

/* templates//preferences.tpl.html */
ev_gettext("Reset");

/* templates//preferences.tpl.html */
ev_gettext("An error occurred while trying to run your query.");

/* templates//preferences.tpl.html */
ev_gettext("Thank you, your password was updated successfully.");

/* templates//preferences.tpl.html */
ev_gettext("Change Password");

/* templates//preferences.tpl.html */
ev_gettext("New Password");

/* templates//preferences.tpl.html */
ev_gettext("Confirm New Password");

/* templates//preferences.tpl.html */
ev_gettext("Update Password");

/* templates//preferences.tpl.html */
ev_gettext("Reset");

/* templates//preferences.tpl.html */
ev_gettext("Account Preferences");

/* templates//preferences.tpl.html */
ev_gettext("An error occurred while trying to run your query.");

/* templates//preferences.tpl.html */
ev_gettext("Thank you, your account preferences were updated successfully.");

/* templates//preferences.tpl.html */
ev_gettext("Available Languages");

/* templates//preferences.tpl.html */
ev_gettext("Timezone");

/* templates//preferences.tpl.html */
ev_gettext("Automatically close confirmation popup windows ?");

/* templates//preferences.tpl.html */
ev_gettext("Yes");

/* templates//preferences.tpl.html */
ev_gettext("No");

/* templates//preferences.tpl.html */
ev_gettext("Receive emails when all issues are created ?");

/* templates//preferences.tpl.html */
ev_gettext("Yes");

/* templates//preferences.tpl.html */
ev_gettext("No");

/* templates//preferences.tpl.html */
ev_gettext("Receive emails when new issues are assigned to you ?");

/* templates//preferences.tpl.html */
ev_gettext("Yes");

/* templates//preferences.tpl.html */
ev_gettext("No");

/* templates//preferences.tpl.html */
ev_gettext("Refresh Rate for Issue Listing Page");

/* templates//preferences.tpl.html */
ev_gettext("in minutes");

/* templates//preferences.tpl.html */
ev_gettext("Refresh Rate for Email Listing Page");

/* templates//preferences.tpl.html */
ev_gettext("in minutes");

/* templates//preferences.tpl.html */
ev_gettext("Email Signature");

/* templates//preferences.tpl.html */
ev_gettext("Edit Signature");

/* templates//preferences.tpl.html */
ev_gettext("Upload New Signature");

/* templates//preferences.tpl.html */
ev_gettext("Automatically append email signature when composing web based emails");

/* templates//preferences.tpl.html */
ev_gettext("Automatically append email signature when composing internal notes");

/* templates//preferences.tpl.html */
ev_gettext("SMS Email Address");

/* templates//preferences.tpl.html */
ev_gettext("only used for automatic issue reminders");

/* templates//preferences.tpl.html */
ev_gettext("Update Preferences");

/* templates//preferences.tpl.html */
ev_gettext("Reset");

/* templates//resize_textarea.tpl.html */
ev_gettext("Widen the field");

/* templates//resize_textarea.tpl.html */
ev_gettext("Shorten the field");

/* templates//notes.tpl.html */
ev_gettext("This action will permanently delete the specified note.");

/* templates//notes.tpl.html */
ev_gettext("This note will be deleted & converted to an email, one either sent immediately or saved as a draft.");

/* templates//notes.tpl.html */
ev_gettext("Internal Notes");

/* templates//notes.tpl.html */
ev_gettext("Back to Top");

/* templates//notes.tpl.html */
ev_gettext("Reply");

/* templates//notes.tpl.html */
ev_gettext("Posted Date");

/* templates//notes.tpl.html */
ev_gettext("User");

/* templates//notes.tpl.html */
ev_gettext("Title");

/* templates//notes.tpl.html */
ev_gettext("reply to this note");

/* templates//notes.tpl.html */
ev_gettext("delete");

/* templates//notes.tpl.html */
ev_gettext("convert note");

/* templates//notes.tpl.html */
ev_gettext("No internal notes could be found.");

/* templates//notes.tpl.html */
ev_gettext("Post Internal Note");

/* templates//add_time_tracking.tpl.html */
ev_gettext("An error occurred while trying to run your query");

/* templates//add_time_tracking.tpl.html */
ev_gettext("Thank you, the time tracking entry was added successfully.");

/* templates//add_time_tracking.tpl.html */
ev_gettext("Continue");

/* templates//add_time_tracking.tpl.html */
ev_gettext("Please enter the summary for this new time tracking entry.");

/* templates//add_time_tracking.tpl.html */
ev_gettext("Please choose the time tracking category for this new entry.");

/* templates//add_time_tracking.tpl.html */
ev_gettext("Please enter integers (or floating point numbers) on the time spent field.");

/* templates//add_time_tracking.tpl.html */
ev_gettext("Please select a valid date of work.");

/* templates//add_time_tracking.tpl.html */
ev_gettext("Record Time Worked");

/* templates//add_time_tracking.tpl.html */
ev_gettext("Summary");

/* templates//add_time_tracking.tpl.html */
ev_gettext("Category");

/* templates//add_time_tracking.tpl.html */
ev_gettext("Please choose a category");

/* templates//add_time_tracking.tpl.html */
ev_gettext("Time Spent");

/* templates//add_time_tracking.tpl.html */
ev_gettext("in minutes");

/* templates//add_time_tracking.tpl.html */
ev_gettext("Date of Work");

/* templates//add_time_tracking.tpl.html */
ev_gettext("Add Time Entry");

/* templates//top_link.tpl.html */
ev_gettext("Back to Top");

/* templates//view_email.tpl.html */
ev_gettext("Re-directing the parent window to the issue report page. This window will be closed automatically.");

/* templates//view_email.tpl.html */
ev_gettext("This message already belongs to that account");

/* templates//view_email.tpl.html */
ev_gettext("An error occurred while trying to run your query");

/* templates//view_email.tpl.html */
ev_gettext("Thank you, the email was successfully moved.");

/* templates//view_email.tpl.html */
ev_gettext("Continue");

/* templates//view_email.tpl.html */
ev_gettext("View Email Details");

/* templates//view_email.tpl.html */
ev_gettext("Associated with Issue");

/* templates//view_email.tpl.html */
ev_gettext("Previous Message");

/* templates//view_email.tpl.html */
ev_gettext("Next Message");

/* templates//view_email.tpl.html */
ev_gettext("Received");

/* templates//view_email.tpl.html */
ev_gettext("From");

/* templates//view_email.tpl.html */
ev_gettext("To");

/* templates//view_email.tpl.html */
ev_gettext("sent to notification list");

/* templates//view_email.tpl.html */
ev_gettext("Cc");

/* templates//view_email.tpl.html */
ev_gettext("Subject");

/* templates//view_email.tpl.html */
ev_gettext("Attachments");

/* templates//view_email.tpl.html */
ev_gettext("Message");

/* templates//view_email.tpl.html */
ev_gettext("display in fixed width font");

/* templates//view_email.tpl.html */
ev_gettext("Raw Headers");

/* templates//view_email.tpl.html */
ev_gettext("Reply");

/* templates//view_email.tpl.html */
ev_gettext("Close");

/* templates//view_email.tpl.html */
ev_gettext("Previous Message");

/* templates//view_email.tpl.html */
ev_gettext("Next Message");

/* templates//view_email.tpl.html */
ev_gettext("Move Message To");

/* templates//authorized_replier.tpl.html */
ev_gettext("Please enter a valid email address.");

/* templates//authorized_replier.tpl.html */
ev_gettext("Authorized Repliers");

/* templates//authorized_replier.tpl.html */
ev_gettext("An error occurred while trying to insert the authorized replier.");

/* templates//authorized_replier.tpl.html */
ev_gettext("Users with a role of \"customer\" or below are not allowed to be added to the authorized repliers list.");

/* templates//authorized_replier.tpl.html */
ev_gettext("Thank you, the authorized replier was inserted successfully.");

/* templates//authorized_replier.tpl.html */
ev_gettext("Email");

/* templates//authorized_replier.tpl.html */
ev_gettext("Add Authorized Replier");

/* templates//authorized_replier.tpl.html */
ev_gettext("Reset");

/* templates//authorized_replier.tpl.html */
ev_gettext("Existing Authorized Repliers for this Issue");

/* templates//authorized_replier.tpl.html */
ev_gettext("Please select at least one of the authorized repliers.");

/* templates//authorized_replier.tpl.html */
ev_gettext("This action will remove the selected entries.");

/* templates//authorized_replier.tpl.html */
ev_gettext("Email");

/* templates//authorized_replier.tpl.html */
ev_gettext("No authorized repliers could be found.");

/* templates//authorized_replier.tpl.html */
ev_gettext("Remove Selected");

/* templates//authorized_replier.tpl.html */
ev_gettext("Close");

/* templates//faq.tpl.html */
ev_gettext("Error: You are not allowed to view the requested FAQ entry.");

/* templates//faq.tpl.html */
ev_gettext("Last updated");

/* templates//faq.tpl.html */
ev_gettext("Close Window");

/* templates//faq.tpl.html */
ev_gettext("Article Entries");

/* templates//faq.tpl.html */
ev_gettext("Title");

/* templates//faq.tpl.html */
ev_gettext("Last Updated Date");

/* templates//faq.tpl.html */
ev_gettext("read faq entry");

/* templates//navigation.tpl.html */
ev_gettext("logout from");

/* templates//navigation.tpl.html */
ev_gettext("Logout");

/* templates//navigation.tpl.html */
ev_gettext("manage the application settings, users, projects, etc");

/* templates//navigation.tpl.html */
ev_gettext("Administration");

/* templates//navigation.tpl.html */
ev_gettext("create a new issue");

/* templates//navigation.tpl.html */
ev_gettext("Create Issue");

/* templates//navigation.tpl.html */
ev_gettext("list the issues stored in the system");

/* templates//navigation.tpl.html */
ev_gettext("List Issues");

/* templates//navigation.tpl.html */
ev_gettext("get access to advanced search parameters");

/* templates//navigation.tpl.html */
ev_gettext("Advanced Search");

/* templates//navigation.tpl.html */
ev_gettext("list available emails");

/* templates//navigation.tpl.html */
ev_gettext("Associate Emails");

/* templates//navigation.tpl.html */
ev_gettext("list all issues assigned to you");

/* templates//navigation.tpl.html */
ev_gettext("My Assignments");

/* templates//navigation.tpl.html */
ev_gettext("general statistics");

/* templates//navigation.tpl.html */
ev_gettext("Stats");

/* templates//navigation.tpl.html */
ev_gettext("reporting system");

/* templates//navigation.tpl.html */
ev_gettext("Reports");

/* templates//navigation.tpl.html */
ev_gettext("internal faq");

/* templates//navigation.tpl.html */
ev_gettext("Internal FAQ");

/* templates//navigation.tpl.html */
ev_gettext("help documentation");

/* templates//navigation.tpl.html */
ev_gettext("Help");

/* templates//navigation.tpl.html */
ev_gettext("Project");

/* templates//navigation.tpl.html */
ev_gettext("Please enter a valid issue ID.");

/* templates//navigation.tpl.html */
ev_gettext("Switch");

/* templates//navigation.tpl.html */
ev_gettext("CLOCKED");

/* templates//navigation.tpl.html */
ev_gettext("IN");

/* templates//navigation.tpl.html */
ev_gettext("OUT");

/* templates//navigation.tpl.html */
ev_gettext("modify your account details and preferences");

/* templates//navigation.tpl.html */
ev_gettext("Preferences");

/* templates//navigation.tpl.html */
ev_gettext("change your account clocked-in status");

/* templates//navigation.tpl.html */
ev_gettext("Clock");

/* templates//navigation.tpl.html */
ev_gettext("Out");

/* templates//navigation.tpl.html */
ev_gettext("In");

/* templates//navigation.tpl.html */
ev_gettext("Search");

/* templates//navigation.tpl.html */
ev_gettext("Go");

/* templates//expandable_cell/buttons.tpl.html */
ev_gettext("Expand all collapsed cells");

/* templates//expandable_cell/buttons.tpl.html */
ev_gettext("Expand all collapsed cells");

/* templates//expandable_cell/buttons.tpl.html */
ev_gettext("Expand collapsed cell");

/* templates//expandable_cell/buttons.tpl.html */
ev_gettext("Collapse expanded cell");

/* templates//main.tpl.html */
ev_gettext("Overall Stats");

/* templates//main.tpl.html */
ev_gettext("Issues by Status");

/* templates//main.tpl.html */
ev_gettext("No issues could be found.");

/* templates//main.tpl.html */
ev_gettext("Issues by Release");

/* templates//main.tpl.html */
ev_gettext("No issues could be found.");

/* templates//main.tpl.html */
ev_gettext("Issues by Priority");

/* templates//main.tpl.html */
ev_gettext("No issues could be found.");

/* templates//main.tpl.html */
ev_gettext("Issues by Category");

/* templates//main.tpl.html */
ev_gettext("No issues could be found.");

/* templates//main.tpl.html */
ev_gettext("Assigned Issues");

/* templates//main.tpl.html */
ev_gettext("No issues could be found.");

/* templates//main.tpl.html */
ev_gettext("Emails");

/* templates//main.tpl.html */
ev_gettext("Associated");

/* templates//main.tpl.html */
ev_gettext("Pending");

/* templates//main.tpl.html */
ev_gettext("Removed");

/* templates//main.tpl.html */
ev_gettext("Did you Know?");

/* templates//main.tpl.html */
ev_gettext("Graphical Stats (All Issues)");

/* templates//confirm.tpl.html */
ev_gettext("Password Confirmation");

/* templates//confirm.tpl.html */
ev_gettext("Account Creation");

/* templates//confirm.tpl.html */
ev_gettext("Error");

/* templates//confirm.tpl.html */
ev_gettext("Password Confirmation Success");

/* templates//confirm.tpl.html */
ev_gettext("The provided trial account email address could not be\nconfirmed. Please contact the local Technical Support staff for\nfurther assistance.");

/* templates//confirm.tpl.html */
ev_gettext("The provided trial account email address could not be\n            found. Please contact the local Technical Support staff for\n            further assistance.");

/* templates//confirm.tpl.html */
ev_gettext("The provided trial account encrypted hash could not be\n            authenticated. Please contact the local Technical\n            Support staff for further assistance.");

/* templates//confirm.tpl.html */
ev_gettext("Thank you, your request for a new password was confirmed successfully. You should receive an email with your new password shortly.");

/* templates//confirm.tpl.html */
ev_gettext("Back to Login Form");

/* templates//custom_fields_form.tpl.html */
ev_gettext("An error occurred while trying to run your query");

/* templates//custom_fields_form.tpl.html */
ev_gettext("Thank you, the custom field values were updated successfully.");

/* templates//custom_fields_form.tpl.html */
ev_gettext("Continue");

/* templates//custom_fields_form.tpl.html */
ev_gettext("Update Issue Details");

/* templates//custom_fields_form.tpl.html */
ev_gettext("Please choose an option");

/* templates//custom_fields_form.tpl.html */
ev_gettext("No custom field could be found.");

/* templates//custom_fields_form.tpl.html */
ev_gettext("Update Values");

/* templates//custom_fields_form.tpl.html */
ev_gettext("Close");

/* templates//reminders/alert_no_recipients.tpl.text */
ev_gettext("The following %1 reminder could not be sent out because no recipients could be found");

/* templates//reminders/alert_no_recipients.tpl.text */
ev_gettext("Automated Issue");

/* templates//reminders/alert_no_recipients.tpl.text */
ev_gettext("Reminder Alert");

/* templates//reminders/alert_no_recipients.tpl.text */
ev_gettext("URL");

/* templates//reminders/alert_no_recipients.tpl.text */
ev_gettext("Summary");

/* templates//reminders/alert_no_recipients.tpl.text */
ev_gettext("Assignment");

/* templates//reminders/alert_no_recipients.tpl.text */
ev_gettext("Customer");

/* templates//reminders/alert_no_recipients.tpl.text */
ev_gettext("Support Level");

/* templates//reminders/alert_no_recipients.tpl.text */
ev_gettext("Alert Reason");

/* templates//reminders/alert_no_recipients.tpl.text */
ev_gettext("Triggered Reminder");

/* templates//reminders/alert_no_recipients.tpl.text */
ev_gettext("Action");

/* templates//reminders/alert_no_recipients.tpl.text */
ev_gettext("Alert Query");

/* templates//reminders/email_alert.tpl.text */
ev_gettext("Automated Issue # %1 Reminder Alert");

/* templates//reminders/email_alert.tpl.text */
ev_gettext("URL");

/* templates//reminders/email_alert.tpl.text */
ev_gettext("Summary");

/* templates//reminders/email_alert.tpl.text */
ev_gettext("Assignment");

/* templates//reminders/email_alert.tpl.text */
ev_gettext("Customer");

/* templates//reminders/email_alert.tpl.text */
ev_gettext("Support Level");

/* templates//reminders/email_alert.tpl.text */
ev_gettext("Alert Reason");

/* templates//reminders/email_alert.tpl.text */
ev_gettext("Triggered Reminder");

/* templates//reminders/email_alert.tpl.text */
ev_gettext("Action");

/* templates//reminders/email_alert.tpl.text */
ev_gettext("Alert Query");

/* templates//reminders/sms_alert.tpl.text */
ev_gettext("This is a SMS reminder alert regarding issue # %1. Certain conditions triggered this action, and this issue may require immediate action in your part.");

/* templates//self_assign.tpl.html */
ev_gettext("An error occurred while trying to run your query");

/* templates//self_assign.tpl.html */
ev_gettext("Thank you, you are now assigned to the issue");

/* templates//self_assign.tpl.html */
ev_gettext("Continue");

/* templates//self_assign.tpl.html */
ev_gettext("WARNING");

/* templates//self_assign.tpl.html */
ngettext("The following user is already assigned to this issue","The following users are already assigned to this issue",x);

/* templates//self_assign.tpl.html */
ngettext("Replace current assignee with Myself.","Replace current assignees with Myself.",x);

/* templates//self_assign.tpl.html */
ev_gettext("Add Myself to list of assignees.");

/* templates//self_assign.tpl.html */
ev_gettext("Continue");

/* templates//edit_custom_fields.tpl.html */
ev_gettext("Please choose an option");

/* templates//post.tpl.html */
ev_gettext("Sorry, but there are no projects currently setup as allowing anonymous posting.");

/* templates//post.tpl.html */
ev_gettext("Thank you, the new issue was created successfully. For your records, the new issue ID is <font color=\"red\">%1</font>");

/* templates//post.tpl.html */
ev_gettext("You may <a class=\"link\" href=\"%1\">%2</a> if you so wish.");

/* templates//post.tpl.html */
ev_gettext("Please choose the project that this new issue will apply to.");

/* templates//post.tpl.html */
ev_gettext("Report New Issue");

/* templates//post.tpl.html */
ev_gettext("Project");

/* templates//post.tpl.html */
ev_gettext("Please choose a project");

/* templates//post.tpl.html */
ev_gettext("Next");

/* templates//post.tpl.html */
ev_gettext("Summary");

/* templates//post.tpl.html */
ev_gettext("Description");

/* templates//post.tpl.html */
ev_gettext("Report New Issue");

/* templates//post.tpl.html */
ev_gettext("Project");

/* templates//post.tpl.html */
ev_gettext("Summary");

/* templates//post.tpl.html */
ev_gettext("Description");

/* templates//post.tpl.html */
ev_gettext("Attach Files");

/* templates//post.tpl.html */
ev_gettext("Keep Form Open");

/* templates//post.tpl.html */
ev_gettext("Submit");

/* templates//post.tpl.html */
ev_gettext("Reset");

/* templates//post.tpl.html */
ev_gettext("Required fields");

/* templates//send.tpl.html */
ev_gettext("An error occurred while trying to run your query");

/* templates//send.tpl.html */
ev_gettext("Sorry, but the email could not be queued. This might be related to problems with your SMTP account settings.\n  Please contact the administrator of this application for further assistance.");

/* templates//send.tpl.html */
ev_gettext("Thank you, the email was queued to be sent successfully.");

/* templates//send.tpl.html */
ev_gettext("Continue");

/* templates//send.tpl.html */
ev_gettext("An error occurred while trying to run your query");

/* templates//send.tpl.html */
ev_gettext("Thank you, the email message was saved as a draft successfully.");

/* templates//send.tpl.html */
ev_gettext("Continue");

/* templates//send.tpl.html */
ev_gettext("If you close this window, you will lose your message");

/* templates//send.tpl.html */
ev_gettext("Please enter the recipient of this email.");

/* templates//send.tpl.html */
ev_gettext("Please enter the subject of this email.");

/* templates//send.tpl.html */
ev_gettext("Please enter the message body of this email.");

/* templates//send.tpl.html */
ev_gettext("WARNING: You are not assigned to this issue so your email will be blocked.\nYour blocked email will be converted to a note that can be recovered later.\nFor more information, please see the topic 'email blocking' in help.");

/* templates//send.tpl.html */
ev_gettext("WARNING: This email will be sent to all names on this issue's Notification List, including CUSTOMERS.\nIf you want the CUSTOMER to receive your message now, press OK.\nOtherwise, to return to your editing window, press CANCEL.");

/* templates//send.tpl.html */
ev_gettext("WARNING: This email will be sent to all names on this issue's Notification List.\nIf you want all users to receive your message now, press OK.\nOtherwise, to return to your editing window, press CANCEL.");

/* templates//send.tpl.html */
ev_gettext("Warning: This draft has already been sent. You cannot resend it.");

/* templates//send.tpl.html */
ev_gettext("Warning: This draft has already been edited. You cannot send or edit it.");

/* templates//send.tpl.html */
ev_gettext("Create Draft");

/* templates//send.tpl.html */
ev_gettext("Send Email");

/* templates//send.tpl.html */
ev_gettext("An error occurred while trying to run your query");

/* templates//send.tpl.html */
ev_gettext("Sorry, but the email could not be sent. This might be related to problems with your SMTP account settings.\n              Please contact the administrator of this application for assistance.");

/* templates//send.tpl.html */
ev_gettext("Thank you, the email was sent successfully.");

/* templates//send.tpl.html */
ev_gettext("From");

/* templates//send.tpl.html */
ev_gettext("To");

/* templates//send.tpl.html */
ev_gettext("Issue");

/* templates//send.tpl.html */
ev_gettext("Notification List");

/* templates//send.tpl.html */
ev_gettext("Members");

/* templates//send.tpl.html */
ev_gettext("Cc");

/* templates//send.tpl.html */
ev_gettext("Add Unknown Recipients to Issue Notification List");

/* templates//send.tpl.html */
ev_gettext("Subject");

/* templates//send.tpl.html */
ev_gettext("Canned Responses");

/* templates//send.tpl.html */
ev_gettext("Use Canned Response");

/* templates//send.tpl.html */
ev_gettext("New Status for Issue");

/* templates//send.tpl.html */
ev_gettext("Time Spent");

/* templates//send.tpl.html */
ev_gettext("in minutes");

/* templates//send.tpl.html */
ev_gettext("Send Email");

/* templates//send.tpl.html */
ev_gettext("Reset");

/* templates//send.tpl.html */
ev_gettext("Cancel");

/* templates//send.tpl.html */
ev_gettext("Check Spelling");

/* templates//send.tpl.html */
ev_gettext("Add Email Signature");

/* templates//send.tpl.html */
ev_gettext("Save Draft Changes");

/* templates//send.tpl.html */
ev_gettext("Save as Draft");

/* templates//send.tpl.html */
ev_gettext("Required fields");

/* templates//app_info.tpl.html */
ev_gettext("Page generated in %1 seconds");

/* templates//app_info.tpl.html */
ev_gettext("queries");

/* templates//app_info.tpl.html */
ev_gettext("Benchmark Statistics");

/* templates//login_form.tpl.html */
ev_gettext("Email Address");

/* templates//login_form.tpl.html */
ev_gettext("Password");

/* templates//login_form.tpl.html */
ev_gettext("Login");

/* templates//login_form.tpl.html */
ev_gettext("Error: Please provide your email address.");

/* templates//login_form.tpl.html */
ev_gettext("Error: Please provide your password.");

/* templates//login_form.tpl.html */
ev_gettext("Error: The email address / password combination could not be found in the system.");

/* templates//login_form.tpl.html */
ev_gettext("Your session has expired. Please login again to continue.");

/* templates//login_form.tpl.html */
ev_gettext("Thank you, you are now logged out of %1");

/* templates//login_form.tpl.html */
ev_gettext("Error: Your user status is currently set as inactive. Please\n              contact your local system administrator for further information.");

/* templates//login_form.tpl.html */
ev_gettext("Thank you, your account is now active and ready to be\n              used. Use the form below to login.");

/* templates//login_form.tpl.html */
ev_gettext("Error: Your user status is currently set as pending. This\n              means that you still need to confirm your account\n              creation request. Please contact your local system\n              administrator for further information.");

/* templates//login_form.tpl.html */
ev_gettext("Error: Cookies support seem to be disabled in your browser. Please enable this feature and try again.");

/* templates//login_form.tpl.html */
ev_gettext("Error: In order for %1 to work properly, you must enable cookie support in your browser. Please login\n              again and accept all cookies coming from it.");

/* templates//login_form.tpl.html */
ev_gettext("Email Address");

/* templates//login_form.tpl.html */
ev_gettext("Password");

/* templates//login_form.tpl.html */
ev_gettext("Login");

/* templates//login_form.tpl.html */
ev_gettext("I Forgot My Password");

/* templates//login_form.tpl.html */
ev_gettext("Signup for an Account");

/* templates//login_form.tpl.html */
ev_gettext("Requires support for cookies and javascript in your browser");

/* templates//login_form.tpl.html */
ev_gettext("NOTE: You may report issues without the need to login by using the following URL");

/* templates//notification.tpl.html */
ev_gettext("Please enter a valid email address.");

/* templates//notification.tpl.html */
ev_gettext("The given email address");

/* templates//notification.tpl.html */
ev_gettext("is neither a known staff member or customer technical contact.");

/* templates//notification.tpl.html */
ev_gettext("Are you sure you want to add this address to the notification list?");

/* templates//notification.tpl.html */
ev_gettext("Notification Options");

/* templates//notification.tpl.html */
ev_gettext("An error occurred while trying to update the notification entry.");

/* templates//notification.tpl.html */
ev_gettext("Error: the given email address is not allowed to be added to the notification list.");

/* templates//notification.tpl.html */
ev_gettext("Thank you, the notification entry was updated successfully.");

/* templates//notification.tpl.html */
ev_gettext("Email");

/* templates//notification.tpl.html */
ev_gettext("Get a Notification When");

/* templates//notification.tpl.html */
ev_gettext("Emails are Received or Sent");

/* templates//notification.tpl.html */
ev_gettext("Overview or Details are Changed");

/* templates//notification.tpl.html */
ev_gettext("Issue is Closed");

/* templates//notification.tpl.html */
ev_gettext("Files are Attached");

/* templates//notification.tpl.html */
ev_gettext("Update Subscription");

/* templates//notification.tpl.html */
ev_gettext("Add Subscription");

/* templates//notification.tpl.html */
ev_gettext("Reset");

/* templates//notification.tpl.html */
ev_gettext("Existing Subscribers for this Issue");

/* templates//notification.tpl.html */
ev_gettext("Please select at least one of the subscribers.");

/* templates//notification.tpl.html */
ev_gettext("This action will remove the selected entries.");

/* templates//notification.tpl.html */
ev_gettext("Email");

/* templates//notification.tpl.html */
ev_gettext("click to edit");

/* templates//notification.tpl.html */
ev_gettext("Actions");

/* templates//notification.tpl.html */
ev_gettext("update this entry");

/* templates//notification.tpl.html */
ev_gettext("No subscribers could be found.");

/* templates//notification.tpl.html */
ev_gettext("Remove Selected");

/* templates//notification.tpl.html */
ev_gettext("Close");

/* templates//file_upload.tpl.html */
ev_gettext("An error occurred while trying to process the uploaded file.");

/* templates//file_upload.tpl.html */
ev_gettext("The uploaded file is already attached to the current issue. Please rename the file and try again.");

/* templates//file_upload.tpl.html */
ev_gettext("Thank you, the uploaded file was associated with the issue below.");

/* templates//file_upload.tpl.html */
ev_gettext("Continue");

/* templates//file_upload.tpl.html */
ev_gettext("Add New Files");

/* templates//file_upload.tpl.html */
ev_gettext("Status");

/* templates//file_upload.tpl.html */
ev_gettext("Public");

/* templates//file_upload.tpl.html */
ev_gettext("visible to all");

/* templates//file_upload.tpl.html */
ev_gettext("Private");

/* templates//file_upload.tpl.html */
ev_gettext("standard user and above only");

/* templates//file_upload.tpl.html */
ev_gettext("Filenames");

/* templates//file_upload.tpl.html */
ev_gettext("ote: The current maximum allowed upload file size is");

/* templates//file_upload.tpl.html */
ev_gettext("Description");

/* templates//file_upload.tpl.html */
ev_gettext("Upload File");

/* templates//file_upload.tpl.html */
ev_gettext("You do not have the correct role to access this page");

/* templates//blah.tpl.html */
ev_gettext("Go");

/* templates//blah.tpl.html */
ev_gettext("Return to Issue #%1 Details Page");

/* templates//update.tpl.html */
ev_gettext("Error: The issue could not be found.");

/* templates//update.tpl.html */
ev_gettext("Go Back");

/* templates//update.tpl.html */
ev_gettext("Sorry, you do not have the required privileges to view this issue.");

/* templates//update.tpl.html */
ev_gettext("Go Back");

/* templates//update.tpl.html */
ev_gettext("Sorry, but you do not have the required permission level to access this screen.");

/* templates//update.tpl.html */
ev_gettext("Go Back");

/* templates//requirement.tpl.html */
ev_gettext("An error occurred while trying to run your query");

/* templates//requirement.tpl.html */
ev_gettext("Thank you, the impact analysis was updated successfully.");

/* templates//requirement.tpl.html */
ev_gettext("Please use only floating point numbers on the estimated development time field.");

/* templates//requirement.tpl.html */
ev_gettext("Please enter the impact analysis for this new requirement.");

/* templates//requirement.tpl.html */
ev_gettext("Enter Impact Analysis");

/* templates//requirement.tpl.html */
ev_gettext("Estimated Dev. Time");

/* templates//requirement.tpl.html */
ev_gettext("in hours");

/* templates//requirement.tpl.html */
ev_gettext("Impact <br />Analysis");

/* templates//notifications/new.tpl.text */
ev_gettext("This is an automated message sent at your request from %1");

/* templates//notifications/new.tpl.text */
ev_gettext("A new issue was just created and assigned to you.");

/* templates//notifications/new.tpl.text */
ev_gettext("To view more details of this issue, or to update it, please visit the following URL");

/* templates//notifications/new.tpl.text */
ev_gettext("ID");

/* templates//notifications/new.tpl.text */
ev_gettext("Summary");

/* templates//notifications/new.tpl.text */
ev_gettext("Project");

/* templates//notifications/new.tpl.text */
ev_gettext("Reported By");

/* templates//notifications/new.tpl.text */
ev_gettext("Priority");

/* templates//notifications/new.tpl.text */
ev_gettext("Description");

/* templates//notifications/new.tpl.text */
ev_gettext("Please Note: If you do not wish to receive any future email\nnotifications from %1, please change your account preferences by\nvisiting the URL below");

/* templates//notifications/new_user.tpl.text */
ev_gettext("A new user was just created for you in the system.");

/* templates//notifications/new_user.tpl.text */
ev_gettext("To start using the system, please load the URL below");

/* templates//notifications/new_user.tpl.text */
ev_gettext("Full Name");

/* templates//notifications/new_user.tpl.text */
ev_gettext("Email Address");

/* templates//notifications/new_user.tpl.text */
ev_gettext("Password");

/* templates//notifications/new_user.tpl.text */
ev_gettext("Assigned Projects");

/* templates//notifications/updated_password.tpl.text */
ev_gettext("Your user account password has been updated in %1");

/* templates//notifications/updated_password.tpl.text */
ev_gettext("Your account information as it now exists appears below.");

/* templates//notifications/updated_password.tpl.text */
ev_gettext("Full Name");

/* templates//notifications/updated_password.tpl.text */
ev_gettext("Email Address");

/* templates//notifications/updated_password.tpl.text */
ev_gettext("Password");

/* templates//notifications/updated_password.tpl.text */
ev_gettext("Assigned Projects");

/* templates//notifications/closed.tpl.text */
ev_gettext("This is an automated message sent at your request from %1.");

/* templates//notifications/closed.tpl.text */
ev_gettext("This issue was just closed by");

/* templates//notifications/closed.tpl.text */
ev_gettext(" with the message");

/* templates//notifications/closed.tpl.text */
ev_gettext("To view more details of this issue, or to update it, please visit the following URL");

/* templates//notifications/closed.tpl.text */
ev_gettext("ID");

/* templates//notifications/closed.tpl.text */
ev_gettext("Summary");

/* templates//notifications/closed.tpl.text */
ev_gettext("Status");

/* templates//notifications/closed.tpl.text */
ev_gettext("Project");

/* templates//notifications/closed.tpl.text */
ev_gettext("Reported By");

/* templates//notifications/closed.tpl.text */
ev_gettext("Priority");

/* templates//notifications/closed.tpl.text */
ev_gettext("Description");

/* templates//notifications/closed.tpl.text */
ev_gettext("Please Note: If you do not wish to receive any future email notifications from %1, please change your account preferences by visiting the URL below");

/* templates//notifications/visitor_account.tpl.text */
ev_gettext("Hello,\n\nWe just received a request to create a new account in %1\nFor security reasons we need you to confirm this request so we can finish the account creation process.\n\nIf this is not a real request from you, or you are not interested in creating a new account anymore, please disregard this email. In a week the request will be erased automatically. However, if you would like to confirm the new account, please do so by visiting the URL below:\n");

/* templates//notifications/new_auto_created_issue.tpl.text */
ev_gettext("Dear");

/* templates//notifications/new_auto_created_issue.tpl.text */
ev_gettext("This is an automated message sent from %1");

/* templates//notifications/new_auto_created_issue.tpl.text */
ev_gettext("We received a message from you and for your convenience, we created an issue that will be used by our staff to handle your message.");

/* templates//notifications/new_auto_created_issue.tpl.text */
ev_gettext("To view more details of this issue, or to update it, please visit the\nfollowing URL");

/* templates//notifications/new_auto_created_issue.tpl.text */
ev_gettext("To add more information to this issue, simply reply to this email.");

/* templates//notifications/new_auto_created_issue.tpl.text */
ev_gettext("Issue");

/* templates//notifications/new_auto_created_issue.tpl.text */
ev_gettext("Summary");

/* templates//notifications/new_auto_created_issue.tpl.text */
ev_gettext("Priority");

/* templates//notifications/new_auto_created_issue.tpl.text */
ev_gettext("Submitted");

/* templates//notifications/notes.tpl.text */
ev_gettext("These are the current issue details");

/* templates//notifications/notes.tpl.text */
ev_gettext("ID");

/* templates//notifications/notes.tpl.text */
ev_gettext("Summary");

/* templates//notifications/notes.tpl.text */
ev_gettext("Status");

/* templates//notifications/notes.tpl.text */
ev_gettext("Project");

/* templates//notifications/notes.tpl.text */
ev_gettext("Reported By");

/* templates//notifications/notes.tpl.text */
ev_gettext("Priority");

/* templates//notifications/notes.tpl.text */
ev_gettext("Description");

/* templates//notifications/notes.tpl.text */
ev_gettext("To view more details of this issue, or to update it, please visit the following URL");

/* templates//notifications/notes.tpl.text */
ev_gettext("Please Note: If you do not wish to receive any future email notifications from %1, please change your account preferences by visiting the URL below");

/* templates//notifications/account_details.tpl.text */
ev_gettext("This is an automated message sent at your request from %1.");

/* templates//notifications/account_details.tpl.text */
ev_gettext("Your full account information is available below.");

/* templates//notifications/account_details.tpl.text */
ev_gettext("Full Name");

/* templates//notifications/account_details.tpl.text */
ev_gettext("Email Address");

/* templates//notifications/account_details.tpl.text */
ev_gettext("Assigned Projects");

/* templates//notifications/updated_account.tpl.text */
ev_gettext("Your user account has been updated in %1");

/* templates//notifications/updated_account.tpl.text */
ev_gettext("Your account information as it now exists appears below.");

/* templates//notifications/updated_account.tpl.text */
ev_gettext("Full Name");

/* templates//notifications/updated_account.tpl.text */
ev_gettext("Email Address");

/* templates//notifications/updated_account.tpl.text */
ev_gettext("Assigned Projects");

/* templates//notifications/password_confirmation.tpl.text */
ev_gettext("Hello,\n\nWe just received a request to create a new random password for your account in %1. For security reasons we need you to confirm this request so we can finish the password creation process.\n\nIf this is not a real request from you, or if you don't need a new password anymore, please disregard this email.\n\nHowever, if you would like to confirm this request, please do so by visiting the URL below:\n");

/* templates//notifications/assigned.tpl.text */
ev_gettext("This is an automated message sent at your request from %1");

/* templates//notifications/assigned.tpl.text */
ev_gettext("An issue was assigned to you by %1");

/* templates//notifications/assigned.tpl.text */
ev_gettext("To view more details of this issue, or to update it, please visit the following URL");

/* templates//notifications/assigned.tpl.text */
ev_gettext("ID");

/* templates//notifications/assigned.tpl.text */
ev_gettext("Summary");

/* templates//notifications/assigned.tpl.text */
ev_gettext("Project");

/* templates//notifications/assigned.tpl.text */
ev_gettext("Reported By");

/* templates//notifications/assigned.tpl.text */
ev_gettext("Assignment");

/* templates//notifications/assigned.tpl.text */
ev_gettext("Priority");

/* templates//notifications/assigned.tpl.text */
ev_gettext("Description");

/* templates//notifications/assigned.tpl.text */
ev_gettext("Please Note: If you do not wish to receive any future email notifications from %1, please change your account preferences by visiting the URL below");

/* templates//notifications/files.tpl.text */
ev_gettext("This is an automated message sent at your request from");

/* templates//notifications/files.tpl.text */
ev_gettext("To view more details of this issue, or to update it, please visit the following URL");

/* templates//notifications/files.tpl.text */
ev_gettext("New Attachment");

/* templates//notifications/files.tpl.text */
ev_gettext("Owner");

/* templates//notifications/files.tpl.text */
ev_gettext("Date");

/* templates//notifications/files.tpl.text */
ev_gettext("Files");

/* templates//notifications/files.tpl.text */
ev_gettext("Description");

/* templates//notifications/files.tpl.text */
ev_gettext("These are the current issue details");

/* templates//notifications/files.tpl.text */
ev_gettext("ID");

/* templates//notifications/files.tpl.text */
ev_gettext("Summary");

/* templates//notifications/files.tpl.text */
ev_gettext("Status");

/* templates//notifications/files.tpl.text */
ev_gettext("Project");

/* templates//notifications/files.tpl.text */
ev_gettext("Reported By");

/* templates//notifications/files.tpl.text */
ev_gettext("Priority");

/* templates//notifications/files.tpl.text */
ev_gettext("Description");

/* templates//notifications/files.tpl.text */
ev_gettext("Please Note: If you do not wish to receive any future email notifications from %1, please change your account preferences by visiting the URL below");

/* templates//notifications/new_issue.tpl.text */
ev_gettext("This is an automated message sent at your request from");

/* templates//notifications/new_issue.tpl.text */
ev_gettext("A new issue was just created in the system.");

/* templates//notifications/new_issue.tpl.text */
ev_gettext("To view more details of this issue, or to update it, please visit the following URL");

/* templates//notifications/new_issue.tpl.text */
ev_gettext("ID");

/* templates//notifications/new_issue.tpl.text */
ev_gettext("Summary");

/* templates//notifications/new_issue.tpl.text */
ev_gettext("Project");

/* templates//notifications/new_issue.tpl.text */
ev_gettext("Reported");

/* templates//notifications/new_issue.tpl.text */
ev_gettext("Assignment");

/* templates//notifications/new_issue.tpl.text */
ev_gettext("Priority");

/* templates//notifications/new_issue.tpl.text */
ev_gettext("Description");

/* templates//notifications/new_issue.tpl.text */
ev_gettext("Issue Details");

/* templates//notifications/new_issue.tpl.text */
ev_gettext("Attachments");

/* templates//notifications/new_issue.tpl.text */
ev_gettext("Files");

/* templates//notifications/new_issue.tpl.text */
ev_gettext("Description");

/* templates//notifications/new_issue.tpl.text */
ev_gettext("Please Note: If you do not wish to receive any future email notifications from %1, please change your account preferences by visiting the URL below");

/* templates//notifications/updated.tpl.text */
ev_gettext("This is an automated message sent at your request from %1");

/* templates//notifications/updated.tpl.text */
ev_gettext("To view more details of this issue, or to update it, please visit the following URL");

/* templates//notifications/updated.tpl.text */
ev_gettext("Issue #");

/* templates//notifications/updated.tpl.text */
ev_gettext("Summary");

/* templates//notifications/updated.tpl.text */
ev_gettext("Changed Fields");

/* templates//notifications/updated.tpl.text */
ev_gettext("Please Note: If you do not wish to receive any future email notifications from %1, please change your account preferences by visiting the URL below");

/* templates//view_form.tpl.html */
ev_gettext("Please select the new status for this issue.");

/* templates//view_form.tpl.html */
ev_gettext("NOTE: If you need to send new information regarding this issue, please use the EMAIL related buttons available at the bottom of the screen.");

/* templates//view_form.tpl.html */
ev_gettext("Previous Issue");

/* templates//view_form.tpl.html */
ev_gettext("Next Issue");

/* templates//view_form.tpl.html */
ev_gettext("This Issue is Currently Quarantined");

/* templates//view_form.tpl.html */
ev_gettext("Quarantine expires in %1");

/* templates//view_form.tpl.html */
ev_gettext("Please see the <a class=\"link\" href=\"faq.php\">FAQ</a> for information regarding quarantined issues.");

/* templates//view_form.tpl.html */
ev_gettext("Remove Quarantine");

/* templates//view_form.tpl.html */
ev_gettext("Note: ");

/* templates//view_form.tpl.html */
ev_gettext("This issue is marked private. Only Managers, the reporter and users assigned to the issue can view it.");

/* templates//view_form.tpl.html */
ev_gettext("Issue Overview");

/* templates//view_form.tpl.html */
ev_gettext("Edit Authorized Replier List");

/* templates//view_form.tpl.html */
ev_gettext("Edit Notification List");

/* templates//view_form.tpl.html */
ev_gettext("History of Changes");

/* templates//view_form.tpl.html */
ev_gettext("Customer");

/* templates//view_form.tpl.html */
ev_gettext("Complete Details");

/* templates//view_form.tpl.html */
ev_gettext("Customer Contract");

/* templates//view_form.tpl.html */
ev_gettext("Support Level");

/* templates//view_form.tpl.html */
ev_gettext("Support Options");

/* templates//view_form.tpl.html */
ev_gettext("Redeemed Incident Types");

/* templates//view_form.tpl.html */
ev_gettext("None");

/* templates//view_form.tpl.html */
ev_gettext("Category");

/* templates//view_form.tpl.html */
ev_gettext("Status");

/* templates//view_form.tpl.html */
ev_gettext("Notification List");

/* templates//view_form.tpl.html */
ev_gettext("Staff");

/* templates//view_form.tpl.html */
ev_gettext("Other");

/* templates//view_form.tpl.html */
ev_gettext("Status");

/* templates//view_form.tpl.html */
ev_gettext("Submitted Date");

/* templates//view_form.tpl.html */
ev_gettext("Priority");

/* templates//view_form.tpl.html */
ev_gettext("Last Updated Date");

/* templates//view_form.tpl.html */
ev_gettext("Scheduled Release");

/* templates//view_form.tpl.html */
ev_gettext("Associated Issues");

/* templates//view_form.tpl.html */
ev_gettext("No issues associated");

/* templates//view_form.tpl.html */
ev_gettext("Resolution");

/* templates//view_form.tpl.html */
ev_gettext("Expected Resolution Date");

/* templates//view_form.tpl.html */
ev_gettext("No resolution date given");

/* templates//view_form.tpl.html */
ev_gettext("Percentage Complete");

/* templates//view_form.tpl.html */
ev_gettext("Estimated Dev. Time");

/* templates//view_form.tpl.html */
ev_gettext("hours");

/* templates//view_form.tpl.html */
ev_gettext("Reporter");

/* templates//view_form.tpl.html */
ev_gettext("Duplicates");

/* templates//view_form.tpl.html */
ev_gettext("Duplicate of");

/* templates//view_form.tpl.html */
ev_gettext("Duplicated by");

/* templates//view_form.tpl.html */
ev_gettext("Assignment");

/* templates//view_form.tpl.html */
ev_gettext("Authorized Repliers");

/* templates//view_form.tpl.html */
ev_gettext("Staff");

/* templates//view_form.tpl.html */
ev_gettext("Other");

/* templates//view_form.tpl.html */
ev_gettext("Group");

/* templates//view_form.tpl.html */
ev_gettext("Summary");

/* templates//view_form.tpl.html */
ev_gettext("Initial Description");

/* templates//view_form.tpl.html */
ev_gettext("fixed width font");

/* templates//view_form.tpl.html */
ev_gettext("Description is currently collapsed");

/* templates//view_form.tpl.html */
ev_gettext("Click to expand.");

/* templates//view_form.tpl.html */
ev_gettext("Unassign Issue");

/* templates//view_form.tpl.html */
ev_gettext("Assign Issue To Myself");

/* templates//view_form.tpl.html */
ev_gettext("Update Issue");

/* templates//view_form.tpl.html */
ev_gettext("Reply");

/* templates//view_form.tpl.html */
ev_gettext("Clear Duplicate Status");

/* templates//view_form.tpl.html */
ev_gettext("Mark as Duplicate");

/* templates//view_form.tpl.html */
ev_gettext("Close Issue");

/* templates//view_form.tpl.html */
ev_gettext("Signup as Authorized Replier");

/* templates//view_form.tpl.html */
ev_gettext("Edit Incident Redemption");

/* templates//view_form.tpl.html */
ev_gettext("Change Status To");

/* templates//email_drafts.tpl.html */
ev_gettext("Drafts");

/* templates//email_drafts.tpl.html */
ev_gettext("Back to Top");

/* templates//email_drafts.tpl.html */
ev_gettext("Status");

/* templates//email_drafts.tpl.html */
ev_gettext("From");

/* templates//email_drafts.tpl.html */
ev_gettext("To");

/* templates//email_drafts.tpl.html */
ev_gettext("Last Updated Date");

/* templates//email_drafts.tpl.html */
ev_gettext("Subject");

/* templates//email_drafts.tpl.html */
ev_gettext("No email drafts could be found.");

/* templates//email_drafts.tpl.html */
ev_gettext("Create Draft");

/* templates//email_drafts.tpl.html */
ev_gettext("Show All Drafts");

/* templates//redeem_incident.tpl.html */
ev_gettext("There was an error marking this issue as redeemed");

/* templates//redeem_incident.tpl.html */
ev_gettext("This issue already has been marked as redeemed");

/* templates//redeem_incident.tpl.html */
ev_gettext("Thank you, the issue was successfully marked.");

/* templates//redeem_incident.tpl.html */
ev_gettext("Please choose the incident types to redeem for this issue.");

/* templates//redeem_incident.tpl.html */
ev_gettext("Total");

/* templates//redeem_incident.tpl.html */
ev_gettext("Left");

/* templates//redeem_incident.tpl.html */
ev_gettext("Redeem Incidents");

/* templates//redeem_incident.tpl.html */
ev_gettext("Continue");

/* templates//custom_fields.tpl.html */
ev_gettext("Custom Fields");

/* templates//custom_fields.tpl.html */
ev_gettext("Back to Top");

/* templates//custom_fields.tpl.html */
ev_gettext("No custom fields could be found.");

/* templates//custom_fields.tpl.html */
ev_gettext("Update");

/* templates//switch.tpl.html */
ev_gettext("Thank you, your current selected project was changed successfully.");

/* templates//switch.tpl.html */
ev_gettext("Continue");

/* templates//news.tpl.html */
ev_gettext("Important Notices");

/* templates//impact_analysis.tpl.html */
ev_gettext("Please enter the estimated development time for this task.");

/* templates//impact_analysis.tpl.html */
ev_gettext("Please use only floating point numbers (or integers) on the estimated development time field.");

/* templates//impact_analysis.tpl.html */
ev_gettext("Please enter the analysis for the changes required by this issue.");

/* templates//impact_analysis.tpl.html */
ev_gettext("Impact Analysis");

/* templates//impact_analysis.tpl.html */
ev_gettext("Total Estimated Dev. Time");

/* templates//impact_analysis.tpl.html */
ev_gettext("in hours");

/* templates//impact_analysis.tpl.html */
ev_gettext("hours");

/* templates//impact_analysis.tpl.html */
ev_gettext("Initial Impact Analysis");

/* templates//impact_analysis.tpl.html */
ev_gettext("Update");

/* templates//impact_analysis.tpl.html */
ev_gettext("Please choose which entries need to be removed.");

/* templates//impact_analysis.tpl.html */
ev_gettext("This action will permanently delete the selected entries.");

/* templates//impact_analysis.tpl.html */
ev_gettext("Further Requirements");

/* templates//impact_analysis.tpl.html */
ev_gettext("All");

/* templates//impact_analysis.tpl.html */
ev_gettext("Handler");

/* templates//impact_analysis.tpl.html */
ev_gettext("Requirement");

/* templates//impact_analysis.tpl.html */
ev_gettext("Estimated Dev. Time");

/* templates//impact_analysis.tpl.html */
ev_gettext("Impact Analysis");

/* templates//impact_analysis.tpl.html */
ev_gettext("update entry");

/* templates//impact_analysis.tpl.html */
ev_gettext("update entry");

/* templates//impact_analysis.tpl.html */
ev_gettext("All");

/* templates//impact_analysis.tpl.html */
ev_gettext("Remove Selected");

/* templates//impact_analysis.tpl.html */
ev_gettext("No entries could be found.");

/* templates//impact_analysis.tpl.html */
ev_gettext("Please enter the new requirement for this issue.");

/* templates//impact_analysis.tpl.html */
ev_gettext("Add New Requirement");

/* templates//attachments.tpl.html */
ev_gettext("This action will permanently delete the selected attachment.");

/* templates//attachments.tpl.html */
ev_gettext("This action will permanently delete the selected file.");

/* templates//attachments.tpl.html */
ev_gettext("Attached Files");

/* templates//attachments.tpl.html */
ev_gettext("Back to Top");

/* templates//attachments.tpl.html */
ev_gettext("Files");

/* templates//attachments.tpl.html */
ev_gettext("Owner");

/* templates//attachments.tpl.html */
ev_gettext("Status");

/* templates//attachments.tpl.html */
ev_gettext("Date");

/* templates//attachments.tpl.html */
ev_gettext("Description");

/* templates//attachments.tpl.html */
ev_gettext("delete");

/* templates//attachments.tpl.html */
ev_gettext("delete");

/* templates//attachments.tpl.html */
ev_gettext("No attachments could be found.");

/* templates//attachments.tpl.html */
ev_gettext("Upload File");

/* templates//select_project.tpl.html */
ev_gettext("Please choose the project.");

/* templates//select_project.tpl.html */
ev_gettext("Select Project");

/* templates//select_project.tpl.html */
ev_gettext("You are not allowed to use the selected project.");

/* templates//select_project.tpl.html */
ev_gettext("Project");

/* templates//select_project.tpl.html */
ev_gettext("Remember Selection");

/* templates//select_project.tpl.html */
ev_gettext("Continue");

/* templates//removed_emails.tpl.html */
ev_gettext("An error occurred while trying to run your query");

/* templates//removed_emails.tpl.html */
ev_gettext("Removed Emails");

/* templates//removed_emails.tpl.html */
ev_gettext("Please choose which emails need to be restored.");

/* templates//removed_emails.tpl.html */
ev_gettext("Please choose which emails need to be permanently removed.");

/* templates//removed_emails.tpl.html */
ev_gettext("WARNING: This action will permanently remove the selected emails from your email account.");

/* templates//removed_emails.tpl.html */
ev_gettext("All");

/* templates//removed_emails.tpl.html */
ev_gettext("Date");

/* templates//removed_emails.tpl.html */
ev_gettext("From");

/* templates//removed_emails.tpl.html */
ev_gettext("Subject");

/* templates//removed_emails.tpl.html */
ev_gettext("No emails could be found.");

/* templates//removed_emails.tpl.html */
ev_gettext("All");

/* templates//removed_emails.tpl.html */
ev_gettext("Restore Emails");

/* templates//removed_emails.tpl.html */
ev_gettext("Close");

/* templates//removed_emails.tpl.html */
ev_gettext("Permanently Remove");

