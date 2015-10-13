<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2010 - 2015 Eventum Team.                              |
// |                                                                      |
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License as published by |
// | the Free Software Foundation; either version 2 of the License, or    |
// | (at your option) any later version.                                  |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to:                           |
// |                                                                      |
// | Free Software Foundation, Inc.                                       |
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: Bryan Alsdorf <bryan@mysql.com>                             |
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

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
