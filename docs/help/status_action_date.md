# Customize Status Action Date Screen

This page allows you to dynamically configure the values displayed in the
`Status Action Date` column in the issue listing screen, for a particular
project.

This column is useful to display the amount of time since the last change in
status for each issue. For example, if issue #1234 is set to status `Closed`,
you could configure Eventum to display the difference in time between `now` and
the date value stored in the closed date field.

Since the list of statuses available per project is dynamic and database driven,
this manual process is needed to associate a status to a date field coming from
the database.
