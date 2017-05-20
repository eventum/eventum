Upgrade Process
===============

One of our objectives is to make upgrading from an earlier release as
painless as possible, and we provide scripts that should bring your
existing Eventum installation up-to-date.

Please note that if your database encoding is not UTF-8, you may encounter various bugs:
* PDO Driver is [unusable](https://github.com/eventum/eventum/pull/167) 
* Issue History entries [get corrupted](https://gitter.im/eventum/eventum?at=58225f1d45c9e3eb4314b58c) (JSON requires UTF-8 encoding)

See 2.2 upgrade instructions how to convert database to UTF-8.

IMPORTANT
---------

When upgrading to a new version of Eventum, please follow these instructions:

1.  Backup your copy of Eventum - files and data.
2.  Extract your new Eventum copy over your existing folder structure
3.  Run the upgrade scripts described in below

Upgrading from version 3.0 and from versions upwards
----------------------------------------------------

* Rename your current Eventum dir to `eventum.old`
* Extract Eventum release tarball and rename it to `eventum` directory.
* Copy all config files from old version to new version: `eventum.old/config` to `eventum/config`
* If your workflow API, customer API or custom field files to were in `lib/eventum` copy them to `config/`:
	 - `eventum.old/lib/eventum/workflow/` -> `eventum/config/workflow/`
	 - `eventum.old/lib/eventum/customer/` -> `eventum/config/customer/`
	 - `eventum.old/lib/eventum/custom_field/` -> `eventum/config/custom_field/`
* Ensure your database database partition has enough disk space and run upgrade script: `php bin/upgrade.php` (`upgrade/update-database.php` in older versions)
* Modify your workflow/customer classes not to require any Eventum core classes, they are autoloaded now. So you can just remove such lines:
```php
require_once(APP_INC_PATH."workflow/class.abstract_workflow_backend.php");
require_once(APP_INC_PATH."customer/class.abstract_customer_backend.php");
```
* Update your cron jobs to point to the scripts in the new location (see [INSTALL](System-Admin%3A-Doing-a-fresh-install)).
	Previously the scripts were in 'crons', now in 'bin', eg:
```
	0 * * * * <PATH-TO-EVENTUM>/bin/download_emails.php username_here mail.domain.com INBOX
```
* Since 3.0.4 directory for writable data [was moved](https://github.com/eventum/eventum/pull/81):

Old Value  | New Value | Description
------------- | ------------- | -------------
`templates_c`  | `var/cache` | templates cache
`locks`  | `var/lock` | various lock and pid files
`logs`  | `var/log` | directory for logs
* Since 3.2.0 MySQL extension was changed from mysql/mysqli to [PDO_MySQL](https://github.com/eventum/eventum/pull/252):

Upgrading from versions before 3.2
----------------------------------

You need to upgrade to 3.2.0 first before you can upgrade to 3.2.x versions. [#270](https://github.com/eventum/eventum/pull/270)

Upgrading from versions before 3.0
----------------------------------

Upgrading directly to 3.1/3.2 from versions before 3.0 does not work, you have to upgrade to 3.0 series first.

Upgrading from versions before 2.2
----------------------------------

Upgrading from these versions not supported, you have to go back and upgrade to 2.2 version first.

Since version 2.2 the database is assumed to be in UTF-8 encoding, it includes [scripts](https://github.com/eventum/eventum/tree/v2.4.0-pre1/upgrade/v2.1.1_to_v2.2) to convert.

The charset convert scripts exists up to 2.4.0 version and are removed in 3.x series.

While it may work to use other encodings than UTF-8,
then be aware that such configuration is not tested and you may encounter various problems.

- use `convert-utf8.php` script to update database to utf8 if the former encoding was proper
- use `fix-charset.php` script to update database to utf8 if the former encoding was improper.

you may also find this tool useful: https://packagist.org/packages/mremi/database-encoder

See scripts contents for inline comments and customization