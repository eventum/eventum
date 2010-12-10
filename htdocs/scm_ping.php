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
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// +----------------------------------------------------------------------+

// shortcut to exit out when no issue id-s are passed in request
// as this script is always called by CVS but we handle only ones which can be
// associated with issues.
if (empty($_GET['issue'])) {
    exit(0);
}

require_once dirname(__FILE__) . '/../init.php';

foreach ($_GET['issue'] as $issue_id) {
    $module = $_GET['module'];
    $username = $_GET['username'];
    $commit_msg = $_GET['commit_msg'];

    $files = array();
    $nfiles = count($_GET['files']);
    for ($y = 0; $y < $nfiles; $y++) {
        $file = array(
            'file' => $_GET['files'][$y],
            'old_version' => $_GET['old_versions'][$y],
            'new_version' => $_GET['new_versions'][$y],
        );

        SCM::logCheckin($issue_id, $module, $file, $username, $commit_msg);
        $files[] = $file;
    }

    // workflow needs to know project_id to find out which workflow class to use.
    $prj_id = Issue::getProjectID($issue_id);
    if (empty($prj_id)) {
        echo "issue #$issue_id not found\n";
        continue;
    }
    Workflow::handleSCMCheckins($prj_id, $issue_id, $module, $files, $username, $commit_msg);
}
