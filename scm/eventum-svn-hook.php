#!/usr/bin/php
<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2014 Eventum Team.                              |
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
// | 51 Franklin Street, Suite 330                                          |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// |          Adam Ratcliffe <adam.ratcliffe@geosmart.co.nz>              |
// |          Frederik M. Kraus <f.kraus@pangora.com>                     |
// |          Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

/**
 * @see http://forge.mysql.com/wiki/Eventum:Subversion_integration about SVN integration.
 *
 * Setup in your svn server hooks/post-commit:
 *
 * #!/bin/sh
 * REPOS="$1"
 * REV="$2"
 * /path/toeventum-svn-hook.php "$REPOS" "$REV"
 */

// URL to your Eventum installation.
// https is supported transparently by PHP 5 if you have openssl module enabled.
$eventum_url = 'http://eventum.example.com/';
// SCM repository name. Needed if multiple repositories configured
$scm_name = 'svn';

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

// get commit date and username and commit message
$command = $svnlook . ' info ' . $repos . ' -r ' . $new_revision;
exec($command, $results);
$username = array_shift($results);
$date = array_shift($results);
// ignore commit message length value
array_shift($results);

// get the full commit message
$commit_msg = join("\n", $results);

// now parse the list of modified files
$modified_files = array();
$command = $svnlook . ' changed ' . $repos . ' -r ' . $new_revision;
exec($command, $files);

foreach ($files as $file_info) {
    // http://svnbook.red-bean.com/en/1.7/svn.ref.svnlook.c.changed.html
    // flags:
    // - 'A ' Item added to repository
    // - 'D ' Item deleted from repository
    // - 'U ' File contents changed
    // - '_U' Properties of item changed; note the leading underscore
    // - 'UU' File contents and properties changed
    list($flags, $filename) = preg_split('/\s+/', $file_info, 2);
    $modified_files[] = array(
        'flags'        => preg_split('//', $flags, -1, PREG_SPLIT_NO_EMPTY),
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
    $username = rawurlencode($username);
    $scm_name = rawurlencode($scm_name);

    // build the GET url to use
    $ping_url = $eventum_url. "scm_ping.php?scm_name=$scm_name&username=$username&commit_msg=$commit_msg";
    foreach ($matches[1] as $issue_id) {
        $ping_url .= "&issue[]=$issue_id";
    }

    foreach ($modified_files as $i => &$file) {
        list($scm_module, $filename) = fileparts($file['filename']);

        $ping_url .= "&module[$i]=" . rawurlencode($scm_module);
        $ping_url .= "&files[$i]=" . rawurlencode($filename);

        // add old revision if content was changed
        if (array_search('A', $file['flags']) === false) {
            $ping_url .= "&old_versions[$i]=" . rawurlencode($file['old_revision']);
        }
        // add new revision if it was not removed
        if (array_search('D', $file['flags']) === false) {
            $ping_url .= "&new_versions[$i]=" . rawurlencode($file['new_revision']);
        }
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

function fileparts($filename) {
    // special for "dirname/" case, pathinfo would set dir to '.' and filename to 'dirname'
    $length = strlen($filename);
    if ($filename[$length - 1] == '/') {
        return array(rtrim($filename, '/'), '');
    }

    $fi = pathinfo($filename);

    return array($fi['dirname'], $fi['basename']);
}

/**
  * Fetch $url, return response and optionaly unparsed headers array.
  *
  * @author Elan Ruusamäe <glen@delfi.ee>
  * @param  string  $url
  * @param  boolean $headers = false
  * @return mixed
  */
function wget($url, $headers = false)
{
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
