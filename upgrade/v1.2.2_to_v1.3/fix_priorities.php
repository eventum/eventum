<?php
/*
 * Since priorities were change to be project specific, this script adds priorities to all
 * projects and updates existing issues.
 */
require_once dirname(__FILE__) . '/../init.php';

// get current priorities
$stmt = "SELECT
            pri_id,
            pri_title
         FROM
            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_priority";
$res = DB_Helper::getInstance()->getAssoc($stmt);
if (PEAR::isError($res)) {
	echo 'ERROR: ', $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
    exit(1);
}

$priorities = $res;
if (count($priorities) < 1) {
    echo "Error getting priorities or no priorities defined.\n";
    print_r($priorities);
    exit(1);
}

$stmt = "SELECT
            prj_id,
            prj_title
         FROM
            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project";
$res = DB_Helper::getInstance()->getAssoc($stmt);
if (PEAR::isError($res)) {
	echo 'ERROR: ', $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
    exit(1);
}
$projects = $res;
if (count($projects) < 1) {
    echo "Error getting projects or no projects defined.<pre>";
    print_r($priorities);
    exit(1);
}
$first = true;
foreach ($projects as $project_id => $project_name) {
    echo "Project: $project_name<br />\n";
    if ($first) {
        echo "Updating priorities<br />\n";
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_priority
                 SET
                    pri_prj_id = $project_id";
        $res = DB_Helper::getInstance()->query($stmt);
        if (DB::isError($res)) {
			echo 'ERROR: ', $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
			exit(1);
        }
        echo DB_Helper::getInstance()->affectedRows() . " priorities updated<br />";
    } else {
        foreach ($priorities as $pri_id => $pri_title) {
            echo "Inserting new priority '$pri_title'<br />\n";
            $stmt = "INSERT INTO
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_priority
                     SET
                        pri_title = '" . $pri_title . "',
                        pri_prj_id = $project_id";
            $res = DB_Helper::getInstance()->query($stmt);
            if (DB::isError($res)) {
				echo 'ERROR: ', $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
				exit(1);
            }
            $new_pri_id = DB_Helper::get_last_insert_id();
            $stmt = "UPDATE
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                     SET
                        iss_pri_id = $new_pri_id
                     WHERE
                        iss_pri_Id = $pri_id AND
                        iss_prj_id = $project_id";
            $res = DB_Helper::getInstance()->query($stmt);
            if (DB::isError($res)) {
				echo 'ERROR: ', $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
				exit(1);
            }
            echo DB_Helper::getInstance()->affectedRows() . " issues updated to correct priority for project.<br />";
        }
    }
    $first = false;
    echo "<hr>";
}

?>
done
