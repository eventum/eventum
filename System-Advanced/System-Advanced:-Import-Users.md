### Import Users

Example script to import users. Put in misc/ You will need to change the name of the project and the role.

    <?php
    include('../init.php'); // try: include("../config.inc.php"); // for older Eventum versions
    include_once(APP_INC_PATH . "class.date.php");
    include_once(APP_INC_PATH . "class.prefs.php");
    include_once(APP_INC_PATH . "class.user.php");
    include_once(APP_INC_PATH . "class.misc.php");
    include_once(APP_INC_PATH . "class.project.php");
    include_once(APP_INC_PATH . "db_access.php");

    $prj_id = Project::getID('MyProject');

    $file_path = @$_SERVER["argv"][1];
    if (empty($file_path)) {
        die("Error: please specify the location of the tab delimited file as the first parameter to this script.\n");
    }

    $lines = file($file_path);
    $lines = array_map('trim', $lines);

    foreach ($lines as $employee) {
        list($name, $email) = explode("\t", $employee);
        // checks if this email is not already in the table
        $stmt = "SELECT
                    COUNT(*)
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    usr_email='$email'";
        $total = $db_api->dbh->getOne($stmt);
        if (PEAR::isError($total)) {
            die("broken select query\n");
        } else {
            if ($total > 0) {
                // email already registered, skip to next user
                echo "User $name already exists in database, skipping\n";
                flush();
                continue;
            } else {
                // default values for these users
                $role = User::getRoleID('Standard User');
                $prefs = Prefs::getDefaults(array(Project::getID(1)));

                $stmt = "INSERT INTO
                            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                         (
                            usr_created_date,
                            usr_status,
                            usr_password,
                            usr_full_name,
                            usr_email,
                            usr_preferences
                         ) VALUES (
                            '" . Date_API::getCurrentDateGMT() . "',
                            'active',
                            '" . md5('testing') . "',
                            '" . Misc::escapeString($name) . "',
                            '" . Misc::escapeString($email) . "',
                            '" . Misc::escapeString($prefs) . "'
                         )";
                $res = $db_api->dbh->query($stmt);
                if (PEAR::isError($res)) {
                    die("broken insert query\n");
                } else {
                    $new_usr_id = $db_api->get_last_insert_id();
                    Project::associateUser($prj_id, $new_usr_id, $role);
                    echo "User $name successfully added to the system.\n";
                    flush();
                }
            }
        }
    }
    ?>