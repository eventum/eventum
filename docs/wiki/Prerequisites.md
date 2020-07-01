# System Requirements

You will need:

-   A Webserver that is capable of handling PHP scripts (i.e [Apache HTTPD Server](https://httpd.apache.org/))
-   [PHP](https://php.net) 7.2.x with the following extensions
    -   ctype
    -   date (builtin)
    -   pcre (builtin)
    -   filter
    -   intl
    -   fileinfo
    -   gd - GD Extension
    -   gettext - gettext support if you want to use localization
    -   iconv
    -   imap - IMAP Extension (c-client imap library)
    -   json
    -   mbstring
    -   pdo
    -   pdo_mysql - MySQL Extension
    -   pcre - PCRE Extension
    -   session (builtin) - Session handling enabled
    -   spl (builtin)
-   An SMTP and POP Server for email support
-   MySQL Database Server (you can get it from the [MySQL Download page](https://dev.mysql.com/downloads/mysql/))

### Checking PHP Requirements

If a requirement is missing from your PHP installation, Eventum will output information about those missing modules. You can also check using one of the following methods:

#### Via the Command Line

Type this command as any user:

```
php -m
```

**NOTE:** If your system has multiple PHP installations, be sure to use the complete path to the same php binary that is used by your web server.

#### Via the Web

To see if your webserver handles PHP scripts and meets the requirements from above, just place a file with the extension `.php` somewhere in your webspace and put the following content into it:

```php
<?php
phpinfo();
```

Open that file in your browser. If PHP is installed on your webserver you will see information about configuration and extensions installed together with PHP.
