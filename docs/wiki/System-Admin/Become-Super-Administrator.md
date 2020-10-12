### Become Super Administrator

The first user created during Eventum setup has Administrator access to all Eventum features. However, it is possible for administrators to accidentally lose administrative access. If this happens use the following instructions to restore administrative access.

If you want to have an access in "Administration" section with the "Configuration" section as this :

```
Configuration:
   * General Setup
   * Manage Email Accounts
   * Manage Custom Fields
   * Customize Issue Listing Screen
```

You need to change some information in the database.

First step get the user's ID (will have the Administrator access)

```sql
SELECT usr_id FROM user WHERE usr_email='your_email_here@example.com';
```

Second step : update the user level in table _project_user_

```sql
UPDATE project_user SET pru_role = 7 WHERE pru_usr_id = 2 LIMIT 1;
```

-   7 is the Administrator level
-   2 is the user's ID

In this example all the eventum tables have the prefix: `eventum_`
