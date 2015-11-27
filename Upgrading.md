Upgrade Process
===============

One of our objectives is to make upgrading from an earlier release as
painless as possible, and we provide scripts that should bring your
existing Eventum installation up-to-date.

IMPORTANT
---------

When upgrading to a new version of Eventum, please follow these instructions:

1.  Backup your copy of Eventum - files and data.
2.  Extract your new Eventum copy over your existing folder structure
3.  Run the upgrade scripts described in below

Upgrading from version 2.2 and from versions upwards
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

Upgrading from versions before 2.2
----------------------------------

Upgrading from these versions not supported, you have to go back and upgrade to 2.2 version first.

If you find any problems while upgrading, please email us in the mailing lists
described in the [README.md](https://github.com/eventum/eventum) file.