<?php
include_once("../../config.inc.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.lock.php");
include_once(APP_INC_PATH . "db_access.php");

Auth::checkAuthentication(APP_COOKIE);

if (Auth::getCurrentRole() < User::getRoleID("Developer")) {
    echo "Invalid role";exit;
}

$process_id = Lock::getProcessID('irc_bot');
echo "Existing process ID: $process_id<br />\n";
if (!empty($process_id)) {
    // kill current process
    $kill = `kill $process_id`;
    if (!empty($kill)) {
        echo "Killed: $kill<br />\n";
    }
}

Lock::release('irc_bot');
$start = `cd /var/www/html/eventum/misc/irc/;php -q bot.php > /dev/null &`;
if (!empty($start)) {
    echo "Error: $start<br />\n";
}

?>
<hr>
If there are no error messages above, the bot should have been successfully restarted.