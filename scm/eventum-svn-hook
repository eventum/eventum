#!/usr/bin/php
<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2013 Eventum Team.                              |
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

// See http://forge.mysql.com/wiki/Eventum:Subversion_integration about SVN integration.

// URL to your Eventum installation.
// https is supported transparently by PHP 5 if you have openssl module enabled.
$eventum_url = 'http://eventum.example.com/';

//
// DO NOT CHANGE ANYTHING AFTER THIS LINE
//

// load eventum-svn-hook.conf.php from dir of this script if it exists
$configfile = dirname(__FILE__) . DIRECTORY_SEPARATOR . basename(__FILE__, '.php') . '.conf.php';
if (file_exists($configfile)) {
    require_once $configfile;
}

// save name of this script
$PROGRAM = basename(array_shift($argv));

if (!isset($svnlook)) {
    $svnlook = '/usr/bin/svnlook';
}

if (!is_executable($svnlook)) {
    fwrite(STDERR, "$PROGRAM: svnlook is not executable, edit \$svnlook\n");
    exit(1);
}

if ($argc < 3) {
    fwrite(STDERR, "$PROGRAM: Missing arguments, got ".($argc - 1).", expected 2\n");
    exit(1);
}

$repos = $argv[0];
$new_revision = $argv[1];
$old_revision = $new_revision - 1;

$scm_module = rawurlencode(basename($repos));

exec($svnlook . ' info ' . $repos . ' -r ' . $new_revision, $results);
$username = array_shift($results);
$date = array_shift($results);
// ignore file length
array_shift($results);

// get the full commit message
$commit_msg = join("\n", $results);

// now parse the list of modified files
$modified_files = array();
exec($svnlook . ' changed ' . $repos . ' -r ' . $new_revision, $files);
foreach ($files as $file_info) {
    $pieces = explode('   ', $file_info);
    $filename = $pieces[1];
    $modified_files[] = array(
        'filename'     => $filename,
        'old_revision' => $old_revision,
        'new_revision' => $new_revision
    );
}

// parse the commit message and get all issue numbers we can find
preg_match_all('/(?:issue|bug) ?:? ?#?(\d+)/i', $commit_msg, $matches);

if (count($matches[1]) > 0) {
    // need to encode all of the url arguments
    $commit_msg = rawurlencode($commit_msg);
    $scm_module = rawurlencode($scm_module);
    $username = rawurlencode($username);

    // build the GET url to use
    $ping_url = $eventum_url. "scm_ping.php?module=$scm_module&username=$username&commit_msg=$commit_msg";
    foreach ($matches[1] as $issue_id) {
        $ping_url .= "&issue[]=$issue_id";
    }

    for ($i = 0; $i < count($modified_files); $i++) {
        $ping_url .= "&files[$i]=" . rawurlencode($modified_files[$i]['filename']);
        $ping_url .= "&old_versions[$i]=" . rawurlencode($modified_files[$i]['old_revision']);
        $ping_url .= "&new_versions[$i]=" . rawurlencode($modified_files[$i]['new_revision']);
    }

    $res = wget($ping_url, true);
    if (!$res) {
        fwrite(STDERR, "Error: Couldn't read response from $ping_url\n");
        exit(1);
    }

    list($headers, $data) = $res;
    // status line is first header in response
    $status = array_shift($headers);
    list($proto, $status, $msg) = explode(' ', trim($status), 3);
    if ($status != '200') {
        fwrite(STDERR, "Error: Could not ping the Eventum SCM handler script: HTTP status code: $status $msg\n");
        exit(1);
    }

    // prefix response with our name
    foreach (explode("\n", trim($data)) as $line) {
        echo "$PROGRAM: $line\n";
    }
}

/**
  * Fetch $url, return response and optionaly unparsed headers array.
  *
  * @author Elan Ruusamäe <glen@delfi.ee>
  * @param  string  $url
  * @param  boolean $headers = false
  * @return mixed
  */
function wget($url, $headers = false) {
    // see if we can fopen
    $flag = ini_get('allow_url_fopen');
    if (!$flag) {
        fwrite(STDERR, "ERROR: allow_url_fopen is disabled\n");
        return false;
    }

    // see if https is supported
    $scheme = parse_url($url, PHP_URL_SCHEME);
    if (!in_array($scheme, stream_get_wrappers())) {
        fwrite(STDERR, "ERROR: $scheme:// scheme not supported. Load openssl php extension?\n");
        return false;
    }

    ini_set('track_errors', 'On');
    $fp = fopen($url, 'r');
    if (!$fp) {
        fwrite(STDERR, "ERROR: $php_errormsg\n");
        return false;
    }

    if ($headers) {
        $meta = stream_get_meta_data($fp);
    }

    $data = '';
    while (!feof($fp)) {
        $data .= fread($fp, 4096);
    }
    fclose($fp);

    if ($headers) {
        return array($meta['wrapper_data'], $data);
    } else {
        return $data;
    }
}
