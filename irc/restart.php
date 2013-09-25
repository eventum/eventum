<?php
require_once dirname(__FILE__) . '/../init.php';

Auth::checkAuthentication(APP_COOKIE);

if (Auth::getCurrentRole() < User::getRoleID("Developer")) {
	echo "Invalid role";
	exit;
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
$start = `cd /srv/www/vhosts/eventum/irc;./run-bot > /dev/null &`;
if (!empty($start)) {
    echo "Error: $start<br />\n";
}

?>
<hr>
If there are no error messages above, the bot should have been successfully restarted.
