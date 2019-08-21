### Import Users

Example script to import users. Put in `bin/`. You will need to change the name of the project and the role.

```php
<?php

$file_path = @$_SERVER["argv"][1];
if (!$file_path) {
    die("Error: please specify the location of the tab delimited file as the first parameter to this script.\n");
}

require_once __DIR__ . '/../init.php';

$prj_id = Project::getID('MyProject');

// default values for these users
$role = User::ROLE_USER;
$prefs = Prefs::getDefaults(array($prj_id));

$lines = file($file_path);
$lines = array_map('trim', $lines);

$dbh = DB_Helper::getInstance();

foreach ($lines as $employee) {
    list($name, $email) = explode("\t", $employee);
    // checks if this email is not already in the table
    $stmt = "SELECT COUNT(*) FROM {{%user}} WHERE usr_email=?";
    $total = $dbh->getOne($stmt, array($email));

    if ($total > 0) {
        // email already registered, skip to next user
        echo "User $name already exists in database, skipping\n";
        continue;
    }

    $stmt = "INSERT INTO {{%user}} (
                            usr_created_date,
                            usr_status,
                            usr_password,
                            usr_full_name,
                            usr_email,
                            usr_preferences
                         ) VALUES (?, ?, ?, ?, ?, ?)";
    $params = array(
        Date_Helper::getCurrentDateGMT(),
        'active',
        AuthPassword::hash('testing'),
        $name,
        $email,
        $prefs,

    );

    $res = $dbh->query($stmt, $params);

    $usr_id = DB_Helper::get_last_insert_id();
    Project::associateUser($prj_id, $usr_id, $role);
    echo "User $name successfully added to the system.\n";
}
```
