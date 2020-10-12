From Rich Stevenson (email withheld to prevent spam)

I now have Eventum running on a NetWare 6.5 box. More extensive testing needs to be done but here's what I had to do to get it installed and working.

-   NetWare 6.5 Service Pack 4a
-   Apache 2.0.54
-   PHP 5.0.4
-   MySQL 4.0.24a

1.  NetWare 6.5 with Service Pack4a installs PHP 5.0.4. I downgraded it to 4.2.4 from <http://developer.novell.com/wiki/index.php/Php>.
2.  Installed the GD extension from <http://developer.novell.com/wiki/index.php/Php_gd>
3.  Added the line, extension=php_gd.nlm, to the NetWare Extensions section of PHP.INI.
4.  In the PHP.INI file, commented out the, Open basedir=".;sys:\\tmp", line.
5.  Changed the following code in the setup/config.inc.php...

<!-- -->

    if (stristr(PHP_OS, 'darwin')) {
        ini_set("include_path", ".:" . APP_PEAR_PATH);
    } elseif (stristr(PHP_OS, 'win')) {
        ini_set("include_path", ".;" . APP_PEAR_PATH);
    } else {
        ini_set("include_path", ".:" . APP_PEAR_PATH);
    }

...to include a check for the NetWare OS.

    if (stristr(PHP_OS, 'darwin')) {
        ini_set("include_path", ".:" . APP_PEAR_PATH);
    } elseif (stristr(PHP_OS, 'win')) {
        ini_set("include_path", ".;" . APP_PEAR_PATH);
    } elseif (stristr(PHP_OS, 'netware')) {
        ini_set("include_path", ".;" . APP_PEAR_PATH);
    } else {
        ini_set("include_path", ".:" . APP_PEAR_PATH);
    }

NOTE: I also could have changed the : to a ; in the else statement instead of adding the OS check.

6. Changed the following code in the setup/index.php....

    if (stristr(PHP_OS, 'darwin')) {
    ini_set("include_path", ".:./../include/pear/");
    } elseif (stristr(PHP_OS, 'win')) {
    ini_set("include_path", ".;./../include/pear/");
    } else {
    ini_set("include_path", ".:./../include/pear/");
    }

...to include a check for the NetWare OS.

    if (stristr(PHP_OS, 'darwin')) {
        ini_set("include_path", ".:./../include/pear/");
    } elseif (stristr(PHP_OS, 'win')) {
        ini_set("include_path", ".;./../include/pear/");
    } elseif (stristr(PHP_OS, 'netware')) {
        ini_set("include_path", ".;./../include/pear/");
    } else {
        ini_set("include_path", ".:./../include/pear/");
    }

**\*\* Upgrading from 1.6.1 to 1.7**

Follow the standard upgrade procedure and then modify the config.inc.php as follows: Change this block....

    if (stristr(PHP_OS, 'darwin')) {
        ini_set("include_path", ".:" . APP_PEAR_PATH);
    } elseif (stristr(PHP_OS, 'win')) {
        ini_set("include_path", ".;" . APP_PEAR_PATH);
    } else {
        ini_set("include_path", ".:" . APP_PEAR_PATH);
    }

...to include a check for the NetWare OS.

    if (stristr(PHP_OS, 'darwin')) {
        ini_set("include_path", ".:" . APP_PEAR_PATH);
    } elseif (stristr(PHP_OS, 'win')) {
        ini_set("include_path", ".;" . APP_PEAR_PATH);
    } elseif (stristr(PHP_OS, 'netware')) {
        ini_set("include_path", ".;" . APP_PEAR_PATH);
    } else {
        ini_set("include_path", ".:" . APP_PEAR_PATH);
    }
