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
// |          Adam Ratcliffe <adam.ratcliffe@geosmart.co.nz>              |
// |          Frederik M. Kraus <f.kraus@pangora.com>                     |
// |          Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+
//
// @(#) $Id: process_svn_commits.php 3255 2007-02-14 23:15:24Z glen $

// See http://eventum.mysql.org/wiki/index.php/Subversion_integration about SVN integration.

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

if (!isset($svnlook)) {
    $svnlook = '/usr/bin/svnlook';
}

if (!is_executable($svnlook)) {
    die('svnlook is not executable, edit $svnlook');
}

if ($argc < 3) {
    printf("Missing arguments, got %d, expected 2\n", $argc - 1);
    exit(1);
}

$repos = $argv[1];
$rev = $argv[2];
$oldRev = $rev - 1;

$scm_module = rawurlencode(basename($repos));

$results = array();
exec($svnlook . ' info ' . $repos . ' -r ' . $rev, $results);

$username = array_shift($results);
$date = array_shift($results);
array_shift($results); // ignore file length

$commit_msg = join("\n", $results);
// now we have to strip html-tags from the commit message
$commit_msg = strip_tags($commit_msg);

$files = array();
exec($svnlook . ' changed ' . $repos . ' -r ' . $rev, $files);
foreach ($files as $file_info) {
    $pieces = explode('   ', $file_info);
    $filename = $pieces[1];
    $modified_files[] = array(
        'filename'     => $filename,
        'old_revision' => $oldRev,
        'new_revision' => $rev
    );
}

// parse the commit message and get all issue numbers we can find
$pattern = "/(?:issue|bug) ?:? ?#?(\d+)/i";
preg_match_all($pattern, $commit_msg, $matches);

if (count($matches) > 1) {
    // need to encode all of the url arguments
    $commit_msg = rawurlencode($commit_msg);
    $scm_module = rawurlencode($scm_module);
    $username = rawurlencode($username);

    // build the GET url to use
    $ping_url = $data['path']. "scm_ping.php?module=$scm_module&username=$username&commit_msg=$commit_msg";
    foreach ($matches[1] as $issue_id) {
        echo 'Matched Issue #', $issue_id, "\n";
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
