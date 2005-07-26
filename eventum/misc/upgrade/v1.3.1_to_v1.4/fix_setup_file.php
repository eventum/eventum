<?php
// changes the setup file from just containing the setup string, to be a php file with an array.

include_once("../../../config.inc.php");
include_once(APP_INC_PATH . "class.setup.php");
include_once(APP_INC_PATH . "db_access.php");

$contents = file_get_contents(APP_SETUP_FILE);

$array = unserialize(base64_decode($contents));

$res = Setup::save($array);

if ($res != 1) {
    echo "Unable to write to file '" . APP_SETUP_FILE . "'.<br />\nPlease verify this file is writeable and try again.";
    exit(1);
}
?>
Done. 