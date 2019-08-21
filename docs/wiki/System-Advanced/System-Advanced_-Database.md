## Database

Eventum has it's own Db Abstraction layer, so that [PEAR::DB](https://github.com/eventum/eventum/blob/v3.0.3/lib/eventum/db/DbPear.php) backend could be later replaced with something else. Currently for tesing [Yii2](https://github.com/eventum/eventum/blob/v3.0.3/lib/eventum/db/DbYii.php) backend was written.

Both classes implement [DbInterface](https://github.com/eventum/eventum/blob/v3.0.3/lib/eventum/db/DbInterface.php) which is where you can see what methods exist.

## Getting connection

You can get connection via [DB_Helper::getInstance()](https://github.com/eventum/eventum/blob/v3.0.3/lib/eventum/class.db_helper.php#L45) and then call any of the `DbInterface` methods.

```php
DB_Helper::getInstance()->query("update foo set a=1");
$updated = DB_Helper::getInstance()->affectedRows();

DB_Helper::getInstance()->fetchAssoc();
DB_Helper::getInstance()->getAll();
DB_Helper::getInstance()->getColumn();
DB_Helper::getInstance()->getOne();
DB_Helper::getInstance()->getPair();
DB_Helper::getInstance()->getRow();

```
