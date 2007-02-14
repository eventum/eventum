#!/usr/bin/php
<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005, 2006, 2007 MySQL AB                  |
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
// @(#) $Id: process_cvs_commits.php 3259 2007-02-14 23:26:47Z glen $


// URL to your Eventum installation.
// https is supported transparently by PHP 5 if you have openssl module enabled.
$eventum_url = 'http://rabbit.impleo.net/';


//
// DO NOT CHANGE ANYTHING AFTER THIS LINE
//

if (isset($eventum_url)) {
    $data = parse_url($eventum_url);
} else {
    // legacy
    $data = array();
    $data['host'] = $eventum_domain;
    $data['path'] = $eventum_relative_url;
    $data['port'] = $eventum_port;
    $data['scheme'] = 'http';
}

if (!isset($data['port'])) {
    $data['port'] = $data['scheme'] == 'https' ? 443 : 80;
}

$input = getInput();

// remove the first element which is the name of this script
array_shift($_SERVER['argv']);

// save who is committing these changes
$username = array_shift($_SERVER['argv']);
// save what the name of the module is
$cvs_arguments = array_shift($_SERVER['argv']);
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
preg_match_all($pattern, $commit_msg, $matches);

if (count($matches) > 1) {
    // need to encode all of the url arguments
    $commit_msg = rawurlencode($commit_msg);
    $cvs_module = rawurlencode($cvs_module);
    $username = rawurlencode($username);

    // build the GET url to use
    $ping_url = $data['path']. "scm_ping.php?module=$cvs_module&username=$username&commit_msg=$commit_msg";
    foreach ($matches[1] as $issue_id) {
        $ping_url .= "&issue[]=$issue_id";
    }

    for ($i = 0; $i < count($modified_files); $i++) {
        $ping_url .= "&files[$i]=" . rawurlencode($modified_files[$i]['filename']);
        $ping_url .= "&old_versions[$i]=" . rawurlencode($modified_files[$i]['old_revision']);
        $ping_url .= "&new_versions[$i]=" . rawurlencode($modified_files[$i]['new_revision']);
    }

    $address = $data['host'];
    if ($data['scheme'] == 'https') {
        $address = "ssl://$address";
    }
    $fp = fsockopen($address, $data['port'], $errno, $errstr, 30);
    if (!$fp) {
        die("Error: Could not ping the Eventum SCM handler script.\n");
    } else {
        $msg = "GET $ping_url HTTP/1.1\r\n";
        $msg .= "Host: $data[host]\r\n";
        $msg .= "Connection: Close\r\n\r\n";
        fwrite($fp, $msg);
        $buf = fgets($fp, 4096);
        list($proto, $status, $msg) = explode(' ', trim($buf), 3);
        if ($status != '200') {
            echo "Error: Could not ping the Eventum SCM handler script: HTTP status code: $status $msg\n";
        }
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
