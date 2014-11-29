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
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

// shortcut to exit out when no issue id-s are passed in request
// as this script is always called by CVS but we handle only ones which can be
// associated with issues.
if (empty($_GET['issue'])) {
    exit(0);
}

require_once dirname(__FILE__) . '/../init.php';

$module = $_GET['module'];
$username = $_GET['username'];
$commit_msg = $_GET['commit_msg'];
$scm_name = isset($_GET['scm_name']) ? $_GET['scm_name'] : null;

// module is per file (svn hook)
if (is_array($module)) {
    $module = null;
}

// process checkins for each issue
foreach ($_GET['issue'] as $issue_id) {
    // check early if issue exists to report proper message back
    // workflow needs to know project_id to find out which workflow class to use.
    $prj_id = Issue::getProjectID($issue_id);
    if (empty($prj_id)) {
        echo "issue #$issue_id not found\n";
        continue;
    }

    $files = array();
    $nfiles = count($_GET['files']);
    for ($y = 0; $y < $nfiles; $y++) {
        $file = array(
            'file' => $_GET['files'][$y],
            // version may be missing to indicate 'added' or ''removed'' state
            'old_version' => isset($_GET['old_versions'][$y]) ? $_GET['old_versions'][$y] : null,
            'new_version' => isset($_GET['new_versions'][$y]) ? $_GET['new_versions'][$y] : null,
            // there may be per file global (cvs) or module (svn)
            'module' => isset($module) ? $module : $_GET['module'][$y],
        );

        $files[] = $file;
    }

    $commit_time = Date_Helper::getCurrentDateGMT();
    try {
        SCM::addCheckins($issue_id, $commit_time, $scm_name, $username, $commit_msg, $files);

        // print report to stdout of commits so hook could report status back to commiter
        $details = Issue::getDetails($issue_id);
        echo "#$issue_id - {$details['iss_summary']} ({$details['sta_title']})\n";

    } catch (Exception $e) {
        // echo, to give some indication to user about error
        echo "ERROR: ", $e->getMessage(), "\n";
        error_log($e->getMessage());
        error_log($e->getTraceAsString());
    }
}
