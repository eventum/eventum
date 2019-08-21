### Deleting Issues

It is impossible to delete issues from Eventum through the user interface.

There exists [contrib/delete_issues.php] script that produces SQL statements
to permanently delete issue data.

Run the script, review output and execute it with MySQL CLI.

```
$ php contrib/delete_issues.php 1 2 3 62 666
```

The above command would try to delete issues 1, 2, 3, 62 and 666

[contrib/delete_issues.php]: https://github.com/eventum/eventum/blob/master/contrib/delete_issues.php
