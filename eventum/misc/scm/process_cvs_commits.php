<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005 MySQL AB                              |
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
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// +----------------------------------------------------------------------+
//
// @(#) $Id$
//

$eventum_domain = 'rabbit.impleo.net';
$eventum_relative_url = '/';
$eventum_port = 80;


//
// DO NOT CHANGE ANYTHING AFTER THIS LINE
//

if (isset($_SERVER)) {
    $HTTP_SERVER_VARS = $_SERVER;
}

$input = getInput();

// remove the first element which is the name of this script
array_shift($HTTP_SERVER_VARS['argv']);

// save who is committing these changes
$username = array_shift($HTTP_SERVER_VARS['argv']);
// save what the name of the module is
$cvs_arguments = array_shift($HTTP_SERVER_VARS['argv']);
$pieces = explode(' ', $cvs_arguments);
$cvs_module = array_shift($pieces);

// now parse the list of modified files
$modified_files = array();
foreach ($pieces as $file_info) {
    @list($filename, $old_revision, $new_revision) = explode(',', $file_info);
    $modified_files[] = array(
        'filename'     => $filename,
        'old_revision' => $old_revision,
        'new_revision' => $new_revision
    );
}

// get the full commit message
$commit_msg = substr($input, strpos($input, 'Log Message:')+strlen('Log Message:')+1);

// parse the commit message and get the first issue number we can find
$pattern = "/(?:issue|bug) ?:? ?#?(\d+)/i";
preg_match($pattern, $commit_msg, $matches);

if (count($matches) > 1) {
    // need to encode all of the url arguments
    $issue_id = base64_encode($matches[1]);
    $commit_msg = base64_encode($commit_msg);
    $cvs_module = base64_encode($cvs_module);
    $username = base64_encode($username);

    // build the GET url to use
    $ping_url = $eventum_relative_url . "scm_ping.php?module=$cvs_module&username=$username&commit_msg=$commit_msg";
    $ping_url .= "&issue[]=$issue_id";
    for ($i = 0; $i < count($modified_files); $i++) {
        $ping_url .= "&files[$i]=" . base64_encode($modified_files[$i]['filename']);
        $ping_url .= "&old_versions[$i]=" . base64_encode($modified_files[$i]['old_revision']);
        $ping_url .= "&new_versions[$i]=" . base64_encode($modified_files[$i]['new_revision']);
    }

    $fp = fsockopen($eventum_domain, $eventum_port, $errno, $errstr, 30);
    if (!$fp) {
        echo "Error: Could not ping the Eventum SCM handler script.\n";
        exit();
    } else {
        $msg = "GET $ping_url HTTP/1.1\r\n";
        $msg .= "Host: $eventum_domain\r\n";
        $msg .= "Connection: Close\r\n\r\n";
        fwrite($fp, $msg);
        fclose($fp);
    }
}

function getInput()
{
    $terminator = "\n";

    $stdin = fopen("php://stdin", "r");
    $input = '';
    while (!feof($stdin)) {
        $buffer = fgets($stdin, 256);
        $input .= $buffer;
    }
    fclose($stdin);
    return $input;
}
?>