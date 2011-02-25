<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
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
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: Bryan Alsdorf <bryan@mysql.com>                             |
// +----------------------------------------------------------------------+

require_once 'init.php';

$full_message = Misc::getInput();

$structure = Mime_Helper::decode($full_message, false, true);
print_r($structure->headers);
// TODO: Actually use values from config

// since this is all hacked up anyway, let's hardcode the values
if (Routing::getMatchingIssueIDs($structure->headers['to'], 'email') !== false) {
    $_SERVER['argv'][1] = '1';
    Routing::route_emails($full_message);
} elseif (Routing::getMatchingIssueIDs($structure->headers['to'], 'note') !== false) {
    Routing::route_notes($full_message);
} elseif (Routing::getMatchingIssueIDs($structure->headers['to'], 'draft') !== false) {
    Routing::route_drafts($full_message);
} else {
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
    // postfix uses exit code 67 to flag unknown users
    exit(67);
}