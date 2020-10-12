### Adding a cron entry

All email is sent using a cron script on Unix systems (including OS X) and needs to be added to the cron tables. An example is given in the INSTALL file, but the mechanics are not.

**The PHP switch on this page was incorrect. It should be "`/usr/bin/php -f file`"**

**Please check your crontab entries!!**

`* * * * * cd /path-to-eventum/misc; /usr/bin/php -f process_mail_queue.php`

If you already have cron scripts running you don't need this - just add another entry. If you do not, then do the following:

1. open a command line window and become root (using the su command)
2. # cd /path-to-eventum/
3. create a file called email.cron in the eventum directory (under the web server)
4. copy the line above into that file
5. edit the path-to-eventum AND path to php if needed
6. save the file
7. # crontab email.cron
8. # crontab -l

you should see your crontab entry listed (and only that one). Email should now be sent.

In f.ex. Debian GNU/Linux when running PHP5, /usr/bin/php is a (soft) link to /etc/alternatives/php which in turn is a link to /usr/bin/php5.

```
 $ l /usr/bin/|grep php
lrwxrwxrwx 1 root root 21 2006-02-19 13:01 php -> /etc/alternatives/php*
-rwxr-xr-x 1 root root 5,4M 2006-05-04 10:52 php5*
 $ file /etc/alternatives/php
/etc/alternatives/php: symbolic link to `/usr/bin/php5'
```

CLI (Command Line Interface) PHP have it's own php.ini
