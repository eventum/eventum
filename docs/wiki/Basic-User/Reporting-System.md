### Reporting System

Eventum has a set of built-in reports. Each report is automatically generated and available in a dtree menu so you can browse them. Some of them need customization or some minor user input to generate the Report. Overview of available reports:

## Issues

### Issues by User

One listing for each user with the issues thay have been assigned to, displaying Summary, Status, Time Spent and Creation Date for each record. No customization available.

### Open Issues By Assignee

Same as Issues by User report, but no closed issues, and two new columns: Days-and-Hours-Since Last-Update and Last-Outgoing-Msg. A filter-input is available to show all open issues older than X days.

### Open Issues By Reporter

Same as Issues by Assignee report but for issues the user has reported.

## Weekly Report

Displays a form to filter Weekly or by Date Range, and select 1 developer, generating a simple text report with the amount of issues, emails, notes and other items, for the selected period.

## Workload by time period

It displays a bar graphics and a table with Workload by Time of day, based on all issues recorded in Eventum since start to present. Actions are any event that shows up in the history of an issue, such as a user or a developer updating an issue, uploading a file, sending an email, etc. No customization available.

## Email by time period

It displays a bar graphics and a table with Email Workload by Time of day, based on all issues recorded in Eventum since start to present. No customization available.

## Custom Fields

Displays a form to select the custom field to "graph", an Interval type (Day, Month, Month, Year) and a Date Range (Start Date, End Date), to genenerate a table with the issues and the custom field values.

## Customer Profile Stats

For the actual project, it shows a form where you select Support Levels, Date Range and Sections to Display, to generate Tables and Bar-graphics with Total Workload by Support Level, Avg Workload per Customer by Support Level, Avg and Median Time to Close by Support Level and Avg and Median Time to First Response by Support Level.

This report will be available only if the current project has customer integration.

## Recent Activity

Displays a form to filter by Activity Type (Phone Calls, Notes, Email, Drafts, Time Tracking, Reminders), date and developer, to generate a table for each Selected Activity for which exists available data.

## Workload By Date Range

Since some type and interval options, combined with large date ranges can produce extremely large graphs, the report is not directly displayed. Instead, a search-form allows to select a Category and a date range (start-end dates), to display table and graphics with Avg/Med/Max Issues/Emails per day for that range.

## Stalled Issues

One list for each User, with the Time Spent, Last Response and Last Update of issues. Includes a Search Form to filter the issues with no response.

## Estimated Development Time

A two-columns table with category vs Estimated time (Hours), based on all open issues for each project. Eventum Users must fill the Estimated Dev. Time field in the issues for this report to be useful. The Estimated Dev. Time field is especially important as a metrics tool to get a simple estimate of how much time each issue will take from discovery, going through implementation and testing up until release time.

## Categories and Statuses

The link might appear in some cases, but it is broken since the /reports/category_statuses.php page does not exists. It was not included in 2.1.1.
