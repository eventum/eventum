The first user created during Eventum setup has Administrator access to all Eventum features. However, it is possible for administrators to accidently lose administrative access. If this happens use the following instructions to restore administrative access.

If you want to have an access in "Administration" section with the "Configuration" section as this :

`Configuration:`
`   * General Setup`
`   * Manage Email Accounts`
`   * Manage Custom Fields`
`   * Customize Issue Listing Screen`

You need to change some information in the database.

First step get the user's ID (will have the Administrator access)

`SELECT usr_id FROM eventum_user WHERE usr_email='your_email_here@example.com';`

Second step : update the user level in table *project_user*

`` UPDATE `eventum_project_user` SET `pru_role` = '7' WHERE `pru_usr_id` = '2' LIMIT 1 ; ``

-   7 is the Administrator level
-   2 is the user's ID

In my example all the eventum tables have the prefix: eventum_

* * * * *

The MySQL line above didn't work for me, I had to use:

`UPDATE eventum_project_user SET pru_role = 7 WHERE pru_usr_id = 2;`

* * * * *

if you're having recent MySQL server you can use subquery:

`update eventum_project_user set pru_role=7 where pru_usr_id in`
`(select usr_id from eventum_user where usr_email='your_email_here@example.com');`

* * * * *

another solution from Eventum User mailing list [<http://lists.mysql.com/eventum-users/1415>](http://lists.mysql.com/eventum-users/1415 "wikilink") from Joao to update the level access to one user and one project (but administrator have a full access in all projects)
- Update the role

` REPLACE project_user VALUES (USER_ID_HERE, PROJECT_ID_HERE, 7);`

The PROJECT_ID_HERE is the ID of the project in which this user is
supposed to be an administrator under. Please let me know if that works.
 Be aware that Eventum 1.5.2 has a bug that can potentially break your administrator user privileges again. I recommend upgrading to 1.5.3