<?php

/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

require_once __DIR__ . '/../init.php';

// since this is all hacked up anyway, let's hardcode the values
// TODO: Actually use values from config
$_SERVER['argv'][1] = '1';

$full_message = stream_get_contents(STDIN);

$return = Routing::route($full_message);
if (is_array($return)) {
    echo $return[1];
    exit($return[0]);
} elseif ($return === false) {
    // message was not able to be routed
    echo 'no route';
    exit(Routing::EX_NOUSER);
}

/*
 * TODO: Save other emails
// save this message in a special directory
$path = "/home/eventum/bounced_emails/";
list($usec,) = explode(" ", microtime());
$filename = date('d-m-Y.H-i-s.') . $usec . '.email.txt';
$fp = fopen($path . $filename, 'a+');
fwrite($fp, $full_message);
fclose($fp);
chmod($path . $filename, 0777);
*/

// this indicates the script ran successfully to postfix
exit(0);
