<?php
/*
 * Since canned email responses were changed to be project specific, this script
 * updates canned responses to be valid for all projects.
 */
include_once("../../../config.inc.php");
include_once(APP_INC_PATH . "db_access.php");


$stmt = "SELECT
            prj_id,
            prj_title
         FROM
            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project";
$projects = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
if (PEAR::isError($projects) || count($projects) < 1) {
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
$responses = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
if (PEAR::isError($responses) || count($responses) < 1) {
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
        $res = $GLOBALS['db_api']->dbh->query($stmt);
        if (DB::isError($res)) {
            echo "<pre>";var_dump($res);exit(1);
        }
    }
}

?>
done