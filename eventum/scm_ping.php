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
// @(#) $Id: scm_ping.php 3206 2007-01-24 20:24:35Z glen $

// shortcut to exit out when no issue id-s are passed in request
// as this script is always called by CVS but we handle only ones which can be
// associated with issues.
if (empty($_GET['issue'])) {
    exit;
}

require_once(dirname(__FILE__) . "/init.php");
require_once(APP_INC_PATH . "class.misc.php");
require_once(APP_INC_PATH . "class.scm.php");
require_once(APP_INC_PATH . "class.workflow.php");
require_once(APP_INC_PATH . "db_access.php");

foreach ($_GET['issue'] as $issue_id) {
    $files = array();
    for ($y = 0; $y < count($_GET['files']); $y++) {
        SCM::logCheckin($issue_id, $y);
        $files[] = array(
            'file' => $_GET['files'][$y],
            'old_version' => $_GET['old_versions'][$y],
            'new_version' => $_GET['new_versions'][$y],
        );
    }

    $prj_id = Issue::getProjectID($issue_id);
    $module = $_GET['module'];
    $username = $_GET['username'];
    $commit_msg = $_GET['commit_msg'];

    Workflow::handleSCMCheckins($prj_id, $issue_id, $module, $files, $username, $commit_msg);
}
