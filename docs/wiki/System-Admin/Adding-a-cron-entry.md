### Adding a cron entry

All email is sent using a cron script on Unix systems (including OS X) and needs to be added to the cron tables. An example is given in the INSTALL file.

```
* * * * * /path-to-eventum/bin/console.php eventum:mail-queue:process
```

Use `crontab -e` to edit cron job definition.
