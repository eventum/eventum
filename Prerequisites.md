# System Requirements

You need:

-   A Webserver that is capable of handling PHP scripts (i.e [Apache HTTPD Server](http://httpd.apache.org/))
-   PHP 5.1.0 or newer with the following extensions
    -   PCRE Extension
    -   Session handling enabled
    -   MySQL Extension
    -   GD Extension
    -   IMAP Extension (c-client imap library)
    -   gettext support if you want to use localization
-   An SMTP and POP Server for email support
-   MySQL Database Server (you can get it from the [MySQL Download page](http://dev.mysql.com/))

Eventum 2.0.1 may give an SQL syntax error for using 'DEFAULT CHARSET=utf8' with MySQL 4.0.18, The installation worked fine for me on a machine running MySQL 5.0.21

### Checking PHP Requirements

If a requirement is missing from your PHP installation, Eventum will output information about those missing modules. You can also check using one of the following methods:

#### Via the Command Line

Type this command as any user:

`php -m | grep -Ei '(gd|imap|mysql|pcre|session)'`

**NOTE:** If your system has multiple PHP installations, be sure to use the complete path to the same php binary that is used by your web server.

#### Via the Web

To see if your webserver handles PHP scripts and meets the requirements from above, just place a file with the extension '.php' somewhere in your webspace and put the following content into it:

```php
<?php
phpinfo();
```

Open that file in your browser. If PHP is installed on your webserver you will see information about configuration and extensions installed together with PHP.