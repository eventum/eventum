# Updating Eventum

One of our objectives is to make upgrading from an earlier release as
painless as possible, and we provide scripts that should bring your
existing Eventum installation up-to-date.

Eventum supports sequential upgrade path using released minor versions.

To explain more clearly: you may skip all patch versions and upgrade to the
next minor version.

This table lists upgrade path from SOURCE to TARGET version, the third column
shows PHP version required for the update.

| SOURCE | TARGET | PHP |
|--------|--------|-----|
| 2.2   | 2.3   | 5.1 |
| 2.3.x | 2.4.0 | 5.1 |
| 2.4.x | 3.0.0 | 5.1 |
| 3.0.x | 3.1.0 | 5.3 |
| 3.1.x | 3.2.0 | 5.5 |
| 3.2.x | 3.3.0 | 5.6 |
| 3.3.x | 3.4.0 | 5.6 |
| 3.4.x | 3.5.0 | 5.6 |
| 3.5.x | 3.6.0 | 7.1 |
| 3.6.x | 3.7.0 | 7.1 |
| 3.7.x | 3.8.0 | 7.1 |
| 3.8.x | 3.9.0 | 7.2 |

## UTF-8 is required

Please note that if your database encoding is not UTF-8, you may encounter various bugs:

-   PDO Driver is [unusable](https://github.com/eventum/eventum/pull/167)
-   Issue History entries [get corrupted](https://gitter.im/eventum/eventum?at=58225f1d45c9e3eb4314b58c) (JSON requires UTF-8 encoding)

See 2.2 upgrade instructions how to convert database to UTF-8.

## Upgrade overview

When upgrading to a new version of Eventum, please follow these instructions:

1.  Check the [requirements](Prerequisites.md) for the version
1.  Backup your copy of Eventum - files and data
1.  Rename the current Eventum directory out of the way (`eventum.old`)
1.  Extract new Eventum version to existing installation direcory (`eventum`)
1.  Restore config and workflow files from the previous version
1.  Run the upgrade script

This way of installing will get rid of files that got removed from newer Eventum version.

NOTE: If you change the installation direcory, you need to change config files to the new direcory value.

## Step by step instructions

-   Rename your current Eventum directory to `eventum.old`
-   Extract Eventum release tarball and rename it to `eventum` directory.
-   Copy all config files from the old version to the new version: `eventum.old/config` to `eventum/config`
-   Restore `var` directory: `eventum.old/var` to `eventum/var`
-   If your workflow API, customer API, or custom field files were in `lib/eventum` copy them to `config/`:
    - `eventum.old/lib/eventum/workflow/` -> `eventum/config/workflow/`
    - `eventum.old/lib/eventum/customer/` -> `eventum/config/customer/`
    - `eventum.old/lib/eventum/custom_field/` -> `eventum/config/custom_field/`
-   Ensure your database partition has enough disk space and run upgrade script: `php bin/upgrade.php` (`upgrade/update-database.php` in older versions)
-   Modify your workflow/customer classes not to require any Eventum core classes, they are autoloaded now. So you can just remove such lines:

```php
require_once(APP_INC_PATH."workflow/class.abstract_workflow_backend.php");
require_once(APP_INC_PATH."customer/class.abstract_customer_backend.php");
```

-   Update your cron jobs to point to the scripts in the new location (see [INSTALL](System-Admin/Doing-a-fresh-install.md)).
    Previously the scripts were in 'crons', now in 'bin', eg:

```
	0 * * * * <PATH-TO-EVENTUM>/bin/download_emails.php username_here mail.domain.com INBOX
```

-   Since 3.0.4 directory for writable data [was moved](https://github.com/eventum/eventum/pull/81):

| Old Value     | New Value   | Description                |
| ------------- | ----------- | -------------------------- |
| `templates_c` | `var/cache` | templates cache            |
| `locks`       | `var/lock`  | various lock and pid files |
| `logs`        | `var/log`   | directory for logs         |

-   Since 3.2.0 MySQL extension was changed from mysql/mysqli to [PDO_MySQL](https://github.com/eventum/eventum/pull/252):

## Upgrading from versions before 2.2

Upgrading from these versions not supported, you have to go back and upgrade to the 2.2 version first.

Since version 2.2 the database is assumed to be in UTF-8 encoding, it includes [scripts](https://github.com/eventum/eventum/tree/v2.4.0-pre1/upgrade/v2.1.1_to_v2.2) to convert.

The charset convert scripts exist up to 2.4.0 version and are removed in 3.x series.

While it may work to use other encodings than UTF-8,
then be aware that such configuration is not tested and you may encounter various problems.

-   use `convert-utf8.php` script to update the database to utf8 if the former encoding was proper
-   use `fix-charset.php` script to update the database to utf8 if the former encoding was improper.

you may also find this tool useful: https://packagist.org/packages/mremi/database-encoder

See scripts contents for inline comments and customization
