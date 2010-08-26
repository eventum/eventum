<?php
/*
 * Since canned email responses were changed to be project specific, this script
 * updates canned responses to be valid for all projects.
 */
require_once dirname(__FILE__) . '/../init.php';


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

// get current canned responses
$stmt = "SELECT
            ere_id,
            ere_title
         FROM
            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_response";
$res = DB_Helper::getInstance()->getAssoc($stmt);
if (PEAR::isError($res)) {
	echo 'ERROR: ', $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
    exit(1);
}
$responses = $res;
if (count($responses) < 1) {
    echo "Error getting canned responses or none defined.<pre>";
    print_r($responses);
    exit(1);
}
foreach ($responses as $ere_id => $ere_title) {
    echo "Response: $ere_title<br />\n";
    foreach ($projects as $prj_id => $prj_title) {
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_email_response
                 VALUES
                    (
                        $prj_id,
                        $ere_id
                    )";
        $res = DB_Helper::getInstance()->query($stmt);
        if (DB::isError($res)) {
			echo 'ERROR: ', $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
			exit(1);
        }
    }
}

?>
done
