If you are getting blank pages in Eventum, this could be caused by a PHP error. By default Eventum is configured to hide all PHP errors to prevent information from being exposed to users. To changes this, edit the file "config/config.php" (Versions before 2.0 edit the file "config.inc.php") located in your eventum directory.

Change the following lines (near the top) from:

```php
ini_set("display_errors", 0);
error_reporting(0);
```

to:

```php
ini_set("display_errors", 1);
error_reporting(E_ALL);
```

and save the file. PHP errors should now be displayed.