### Creating Issues

There are four ways to create issues.

## Create Issue from Anonymous Reporting Link

Has to be enabled from the administrator menu.

Projects that have the allow anonymous reporting set, show up hyperlinks to Report Issue Anonymous Form on the login page.

When issue is created from Anonymous Form, the default initial status is defined from each project configuration; default category and priority are defined from Anonymous Reporting configuration.

## Automatically Create Issue using Email Integration

Enable [Email Integration](../System-Admin/Email-integration.md) and set the download_mail function as a [cron job](../System-Admin/Adding-a-cron-entry.md) or [windows schedule](../System-Admin/Installation-notes-for-Windows.md). Eventum is able to see relations between issues and email based upon a reference to the issue number spelled like `[#1234]` in the title of an e-mail.

When issue is auto-created from Email, the default initial status is defined from each project configuration; default category and priority are defined from Manage Email, Auto-Creation of Issues Configuration.

## Create an Issue Manually

As easy as dialing 911. Log in to Eventum. Select the required project. Press Create Issue from the menu on the top of the page.

When issue is created from Create issue Form, the default initial status is defined from each project configuration; the reporter selects the Category and Priority for each issue.

---

Create an Issue Manually

Enter all mandatory fields Select Category of the project. The categories will be displayed automatically when you select the project (left side of the screen you have a selection box and switch button. Select your project and click on switch button) Select the Priority of which the issue belongs to. To assign the issue you need to select the user in Assignment. Where you can select multiple user at once Select the group. Enter the Summary (What is the issue) Enter Description (In Detail description of the issue) click on submit button to post this issue.

---

## Create an Issue using Email Integration by Associating Email

Enable email integration and set the download_mail function as a cron job or windows schedule. Email that is not directly recognized by Eventum piles up in a mail queue per project (accessible from the menu on the top of every page for Standard Users or higher role users). From this mail queue you can manually assign e-mail to issues or let Eventum generate a new issue from the e-mail.

When issue is created by the user from Email manually, the default initial status is defined from each project configuration; the user selects the Category and Priority (uses same form as create issue, with pre-filled fields).
