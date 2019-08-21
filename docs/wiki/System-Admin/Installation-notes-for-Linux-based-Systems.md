## Slackware 12.0

Slackware 12.0 includes all necessary modules with the default installation of PHP.

Unlike many other Linux distributions, MySQL must be initialized before using on Slackware. Simply issue these commands as root:

```

mysql_install_db
chown -R mysql:mysql /var/lib/mysql
chmod +x /etc/rc.d/rc.mysqld
/etc/rc.d/rc.mysqld start
```

It's also a good idea to use the following script to harden MySQL:

```
mysql_secure_installation
```

Now that MySQL is running, complete the installation of Eventum.

## Ubuntu 5.10

If the installer complains that a file is not writable even though it clearly is, the problem is that the file is not owned by the webserver user. It is not enough to have the webserver group be able to write to the file or even to have it world writable, installation will not continue until the webserver user owns the file.

## Fedora Core 4

```
# yum install php-gd
# yum install php-mysql
```

/etc/php.d/eventum.ini

```ini
allow_call_time_pass_reference = true
memory_limit = 16M
```

/etc/php/errors.ini (good for troubleshooting during installation)

```ini
display_errors = On
display_startup_errors = On
log_errors = On
error_reporting = E_ALL
; error_log = filename
```

## Debian Linux

-   ensure these packages are installed: apache2 libapache2-mod-php5 php5-gd
-   [download](http://dev.mysql.com/downloads/other/eventum/)
-   extract to /usr/share/eventum
-   chown -R root:root /usr/share/eventum
-   chmod -R go-w /usr/share/eventum
-   mv /usr/share/eventum/logs /var/log/eventum
-   chmod 100 /var/log/eventum/login_attempts.log
-   mkdir /etc/eventum
-   mkdir -p /var/lib/eventum/locks /var/lib/eventum/templates_c
-   chown www-data -R /var/log/eventum /etc/eventum /var/lib/eventum/locks /var/lib/eventum/templates_c
-   rmdir /usr/share/eventum/config /usr/share/eventum/locks /usr/share/eventum/templates_c
-   ln -sf /var/log/eventum /usr/share/eventum/logs
-   ln -sf /etc/eventum /usr/share/eventum/config
-   ln -sf /var/lib/eventum/locks /usr/share/eventum/locks
-   ln -sf /var/lib/eventum/templates_c /usr/share/eventum/templates_c
-   setup apache with this (in /etc/apache2/conf.d/eventum):
    -   Alias /eventum /usr/share/eventum
-   in mysql, create an eventum DB, granting permissions to eventum@localhost user
-   visit <http://eventum.example.com/eventum>
    -   servername = eventum.example.com (tick SSL)
    -   relative url = /eventum/
    -   mysql server = localhost
    -   mysql database = eventum (do not tick)
    -   mysql table_prefix = (blank)
    -   username = eventum
    -   password = ....
    -   we did not set another user for normal use (which would probably only need select,update,insert,delete permissions)
    -   email sender = eventum@example.com
    -   email hostname = localhost
    -   email port = 25
-   click next
-   login using admin@example.com (no matter what you used earlier) with password of "admin"

## gettext and Translation

In order to have translation working on some systems, ensure you have the correct locale enabled, otherwise the gettext function does not get the translation.

Edit: `/etc/locale.gen`

Activate all the locales you need and then run:

```
locale-gen
```
