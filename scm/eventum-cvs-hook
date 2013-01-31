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
// |          Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

// URL to your Eventum installation.
// https is supported transparently by PHP 5 if you have openssl module enabled.
$eventum_url = 'http://eventum.example.com/';

//
// DO NOT CHANGE ANYTHING AFTER THIS LINE
//

// load eventum-cvs-hook.conf.php from dir of this script if it exists
$configfile = dirname(__FILE__) . DIRECTORY_SEPARATOR . basename(__FILE__, '.php') . '.conf.php';
if (file_exists($configfile)) {
    require_once $configfile;
}

// save name of this script
$PROGRAM = basename(array_shift($argv));
// save who is committing these changes
$username = array_shift($argv);

// grab fileinfo
if ($argc == 3) {
    // assume the old way ("PATH {FILE,rev1,rev2 }+")
    // CVSROOT/loginfo: ALL eventum-cvs-hook $USER %{sVv}
    $args = explode(' ', array_shift($argv));
    // save what the name of the module is
    $cvs_module = array_shift($args);

    // skip if we're importing or adding new dirrectory
    // TODO: checked old way with CVS 1.11, but not checked the new way
    $msg = implode(' ', array_slice($args, -3));
    if (in_array($msg, array('- Imported sources', '- New directory'))) {
        exit(0);
    }

    // now parse the list of modified files
    $modified_files = array();
    foreach ($args as $file_info) {
        list($filename, $old_revision, $new_revision) = explode(',', $file_info);
        $modified_files[] = array(
            'filename'     => $filename,
            'old_revision' => $old_revision,
            'new_revision' => $new_revision
        );
    }

} else {
    // assume the new way ("PATH" {"FILE" "rev1" "rev2"}+)
    // CVSROOT/loginfo: ALL eventum-cvs-hook $USER "%p" %{sVv}
    $args = $argv;
    // save what the name of the module is
    $cvs_module = array_shift($args);

    // skip if we're importing or adding new dirrectory
    // TODO: checked old way with CVS 1.11, but not checked the new way
    $msg = implode(' ', array_slice($args, -3));
    if (in_array($msg, array('- Imported sources', '- New directory'))) {
        exit(0);
    }

    // now parse the list of modified files
    $modified_files = array();
    while ($file_info = array_splice($args, 0, 3)) {
        list($filename, $old_revision, $new_revision) = $file_info;
        $modified_files[] = array(
            'filename'     => $filename,
            'old_revision' => $old_revision,
            'new_revision' => $new_revision
        );
    }
}

// get the full commit message
$input = stream_get_contents(STDIN);
$commit_msg = rtrim(substr($input, strpos($input, 'Log Message:') + strlen('Log Message:') + 1));

// parse the commit message and get all issue numbers we can find
preg_match_all('/(?:issue|bug) ?:? ?#?(\d+)/i', $commit_msg, $matches);

if (count($matches[1]) > 0) {
    // need to encode all of the url arguments
    $commit_msg = rawurlencode($commit_msg);
    $cvs_module = rawurlencode($cvs_module);
    $username = rawurlencode($username);

    // build the GET url to use
    $ping_url = $eventum_url. "scm_ping.php?module=$cvs_module&username=$username&commit_msg=$commit_msg";
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
