### How issues are handled in Eventum

This Chapter is intended to be a basic guidline for new users. One of my problems when starting with Eventum was, to understand the 'Big Picture'. Maybe it's useful to place an example here. Maybe someone can write a short issue-story here. How it's handled from the beginning, and how it goes through different statuses until it is finally closed. ----

---

Somebody says: hmm I see this way:

1.  beta tester reports bug/requires enhancement, he/she creates new issue with status discovery
2.  moderator/admin/project leader get email about new bug report, he evaluates on what area/department bug is connected, assigns task to the people responsible for that area.
3.  developer gets email notification about issue, set bug status to "evaluating"
4.  when bug is confirmed set status to confirmed/working on it and estimates time and release in which bug will be solved
5.  when bug is terminated, issue is closed, reporter and all in notification list gets email about it (anyone can subscribe/unsubscribe from notification list in any time, except reporter/developer)

---

--**Eliotbq** 02:01, 27 Jul 2005 (CEST) Disclaimer: this is how I see it as a fairly new user:

## Issue creation

See [Creating Issues](../Basic-User/Creating-Issues.md) for more details. Either Click "Create issue" and fill in all the details

or from the "Associate emails" screen, select one or more unassociated emails, and click [Associate]-\>(New issue), then add the remaining details as for the first case.

or (if enabled) issues are created automatically from incoming email, or anonymously from the login screen.

## Issue followup

While viewing an issue, use the `[Reply]` button at the bottom of the first section.

OR click the reply icon next to an email in the notes or associated emails list.

OR click `[Send Email]` or `[Post Internal Note]` at the foot of the relevant section.

OR reply to an email that has originated from eventum

It is NOT recommended to carry on an email conversation and CC: support@mydomain. You want to ensure that all emails are handled through eventum, this means that replies more often get associated with the correct issue automatically.

## Issue closing

When an issue is closed its status can be set to a status that has "Closed context" (see Administration/Manage Statuses).

Default install has three:

-   Released (develop) = we changed something (code,docs,hardware) and released it.
-   Killed (develop) = we didn't change anything.
-   Resolved (support)= we told the customer something that made the problem go away.

"Issue Resolutions" are finer grained reasons that can be applied when closing an issue. The defaults apply to bugs, but I added "question answered" to that list for use with tech support.

## Issue Lifecycles

Here are some rudimentary state diagrams for issues: (Where the state is a closed state, the possible resolutions are listed in parentheses.)

_Feature Request:_

    discovery -> killed(won't fix,suspended,not fixable,duplicate)
    |
    requirements -> killed(as above)
    |
    implementation
    |
    testing
    |
    release(fixed)

_Bug report:_

    discovery - killed(not a bug, won't fix, cant fix, duplicate, can't reproduce)
    |
    requirements - killed(won't fix)
    |
    implementation
    |
    testing -> [maybe back to discovery again ]
    |
    release(fixed)

_Techsupport:_

    discovery
    |
    testing -> [convert to bug report if bug]
    |
    resolved(question answered,fixed)

## Receiving Notifications

Emails are sent (when process_mail_queue is properly set in CRON) by Eventum on issue events.

These messages are sent using templates stored at <eventum>/templates/notifications.

Notifications are sent to users in the Notification List when some of the following conditions are met:

-   The eventum-user has not reported the issue
-   The eventum-user has configured his preferences to receive notifications for the proper event (issue creation or issue assigned).
-   The user reported the issue by sendind email, with auto-creation of issues enabled or issue created from manually associated email (no setup option for disabling).
-   The user is CC in the email that creates an issue (no setup option for disabling).
-   The issue has been set for some user to receive notification on specific issue Action (Issues are Updated, Issues are Closed, Emails are Associated, Files are Attached), default Action values set at [General Setup](../System-Admin/General-Setup.md), specific issue Action values set at [Edit Notification List](Edit Notification List "wikilink").
