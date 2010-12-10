<?php
// changes the setup file from just containing the setup string, to be a php file with an array.

require_once dirname(__FILE__) . '/../init.php';

$contents = file_get_contents(APP_SETUP_FILE);

$array = unserialize(base64_decode($contents));

$res = Setup::save($array);

if ($res != 1) {
    echo "Unable to write to file '" . APP_SETUP_FILE . "'.<br />\nPlease verify this file is writeable and try again.";
    exit(1);
}
?>
Done.
