Since Eventum 3.3.0 attachments are abstracted using [Flysystem](https://flysystem.thephpleague.com/). Users upgrading from previous versions do not need to configure anything as a legacy adapter is provided for existing attachments. New attachments will be stored in the `attachment_path` and `attachment_chunk` tables using the [Flysystem-pdo](https://github.com/IntegralSoftware/flysystem-pdo-adapter) adapter.

# Advanced Use

Three adapters are included:
* legacy: Read only adapter for accessing pre 3.3.0 attachments
* pdo: flysystem-pdo adapter used by default for new attachments
* local: local filesystem adapter if you wish to store attachments on disk instead of the database

Advanced users can change to a different adapter by editing setup.php in your configuration file and adding/changing the default adapter.

```
 'attachments' =>
  array (
    'default_adapter' => 'local',
  ),
```

You can define additional adapters and pass options to adapters as well.
```
 'attachments' =>
  array (
    'default_adapter' => 'local',
    'adapters' => array(
        'local' =>  array(
            'class' =>  '\\League\\Flysystem\\Adapter\\Local',
            'options'   =>  array('/path/to/my/system/')
        ),
    ),
  ),
```

# Migrating existing attachments

If you want to migrate your existing attachments to a new backend, please see [contrib/migrate_storage_adapter.php](https://github.com/eventum/eventum/blob/75710aef672bb64d7a6624f91d703e7c4e0853b4/contrib/migrate_storage_adapter.php). Changing storage adapters has a risk of data loss and may require large amounts of disk space during the process. Make sure you fully understand the process and backup all data before proceeding.

### Additional Reading

See [PR#254](https://github.com/eventum/eventum/pull/254)