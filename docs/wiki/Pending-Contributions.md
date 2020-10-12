The recommended way of adding contributions is to submit [Pull Request in GitHub](https://github.com/eventum/eventum/blob/master/CONTRIBUTING.md).

## Patches for Eventum 1.7.0

-   [Compact issue display](Compact issue display "wikilink") - **Implemented in 2.0** This patch removes line breaks between each subsection, moved "Back to top" link into header of each subsection.
-   [Associate new issues](System-Advanced/Associate-new-issues.md) - on the associate emails page, select only from open issues.
-   [Subject AND header based routing](System-Advanced/Subject-AND-header-based-routing.md) - fall back to header based routing if no issue \# found in subject
-   [Hide_closed_issues_from_stats](<System-Advanced/Hide-Closed-Issues-on-Stats-(Main)-Page.md>) - Pie charts only show issues with open status. <small>link may not be correct</small>
-   [Add a timeout for outgoing smtp connections 171](System-Admin/Add-a-timeout-for-outgoing-smtp-connections-171.md) - This adds an admin configurable timeout for smtp connections. It helps preventing the php script that processes the outgoing mail queue from getting stalled and never releasing its lock
-   [Defaulting Assigned Emails to Yes](System-Advanced/Defaulting-Assigned-Emails-to-Yes.md) - This fixes the fact users assigned to projects after being created do not get the proper defaults in reference to receiving emails when it is assigned to them

## Patches for Eventum 2.0.1

-   [Hide_closed_issues_from_stats](<System-Advanced/Hide-Closed-Issues-on-Stats-(Main)-Page.md>) - Pie charts only show issues with open status. <small>link may not be correct</small>
-   [Add a timeout for outgoing smtp connections](System-Admin/Add-a-timeout-for-outgoing-smtp-connections.md) - This adds an admin configurable timeout for smtp connections. It helps preventing the php script that processes the outgoing mail queue from getting stalled and never releasing its lock
-   [Open Source Project Mod](System-Advanced/Open-Source-Project-Mod.md) - Allows anonymous (non-registered) user access to tracker issues just like a regular user with configurable access.
-   [Custom reports from Tim Uckun](http://eventum.mysql.org/downloads/customreports.tgz) - Extra reports from Tim as described [here](http://lists.mysql.com/eventum-devel/611)

## Patches for Eventum 2.1.1

-   [Eventum in Spanish](http://translate.unixlan.com.ar/es/eventum/eventum.po) - Eventum 100% translated into Spanish, thanks to the contributors of the [list](http://www.unixlan.com.ar/list/)
-   [Patch Reminder Repetition](System-Advanced/Patch-Reminder-Repetition.md) - This patch adds the possibility to specify a reminder repetition period. The reminder is repeated after the specified time.
-   [Limit Project Managers to Their Projects](docs/wiki/System-Advanced/Limit-Project-Managers-to-Only-Their-Projects.md) - This mod will limit what a project manager can do in the Administration section.
    -   Managers can add / edit /update users only on the projects they manage.
    -   Managers can modify project parameters only on the projects they manage.
    -   Administrators can still do anything on any project.

## Patches for Eventum 2.2

-   [Patch Reminder Repetition](System-Advanced/Patch-Reminder-Repetition.md) - This patch adds the possibility to specify a reminder repetition period. The reminder is repeated after the specified time.

## Patches against Eventum daily build

-   [Enhanced FAQ part 1](System-Advanced/Enhanced-FAQ-part-1.md) - Gives us two lists of FAQ-items in manage. One with not published FAQ-items, and one with published FAQ-items.
-   [Enhanced FAQ part 2](System-Advanced/Enhanced-FAQ-part-2.md) - You MUST choose a project before editing FAQ-items. Remembers which project you choose.
