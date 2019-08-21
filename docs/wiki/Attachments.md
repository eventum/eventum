Since Eventum 3.3.0 attachments are abstracted using [Flysystem](https://flysystem.thephpleague.com/). Users upgrading from previous versions do not need to configure anything as a legacy adapter is provided for existing attachments. New attachments will be stored in the `attachment_path` and `attachment_chunk` tables using the [Flysystem-pdo](https://github.com/IntegralSoftware/flysystem-pdo-adapter) adapter.

# Advanced Use

Three adapters are included:

-   legacy: Read only adapter for accessing pre 3.3.0 attachments
-   pdo: flysystem-pdo adapter used by default for new attachments
-   local: local filesystem adapter if you wish to store attachments on disk instead of the database

Advanced users can change to a different adapter by editing setup.php in your configuration file and adding/changing the default adapter.

```php
    'attachments' => [
        'default_adapter' => 'local',
    ],
```

You can define additional adapters and pass options to adapters as well.

```php
    'attachments' => [
        'default_adapter' => 'local',
        'adapters' => [
            'local' => [
                'class' => '\\League\\Flysystem\\Adapter\\Local',
                'options' => ['/path/to/my/system/'],
            ],
        ],
    ],
```

# Migrating existing attachments

If you want to migrate your existing attachments to a new backend, use `migrate_storage_adapter.php` tool.

Changing storage adapters has a risk of data loss and may require large amounts
of disk space during the process. Make sure you fully understand the process
and backup all data before proceeding.

Verify that the attachments are reachable.

```
$ ./bin/migrate_storage_adapter.php --verify pdo
Verifying data in 'pdo://' Adapter
Preparing temporary table. Please wait...
Verifying 1 file(s)

$ ./bin/migrate_storage_adapter.php --verify legacy
Verifying data in 'legacy://' Adapter
Preparing temporary table. Please wait...
Verifying 1516 file(s)
```

Perform actual migration

```
$ ./bin/migrate_storage_adapter.php --yes pdo local --limit=100
Migrating data from 'pdo://' to 'local://'
Preparing temporary table. Please wait...
Moving 1 file(s)
 1/1 [============================] 100% < 1 sec/< 1 sec 6.8 MiB
You might need to run 'OPTIMIZE TABLE attachment_chunk' to reclaim space from the database

$ mysql -s eventum -e 'OPTIMIZE TABLE attachment_chunk'
Table   Op      Msg_type        Msg_text
eventum.attachment_chunk        optimize        status  OK
```

### Additional Reading

See [PR#254](https://github.com/eventum/eventum/pull/254)
