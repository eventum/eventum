<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005, 2006 MySQL AB                        |
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
// @(#) $Id: s.cvs_ping.php 1.4 03/01/16 01:47:31-00:00 jpm $
//
include_once("config.inc.php");
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.scm.php");
include_once(APP_INC_PATH . "class.workflow.php");
include_once(APP_INC_PATH . "db_access.php");

$HTTP_GET_VARS = Misc::array_map_deep($HTTP_GET_VARS, 'base64_decode');

foreach ($HTTP_GET_VARS['issue'] as $issue_id) {
    $files = array();
    for ($y = 0; $y < count($HTTP_GET_VARS['files']); $y++) {
        SCM::logCheckin($issue_id, $y);
        $files[] = array(
            'file' => $HTTP_GET_VARS['files'][$y],
            'old_version' => $HTTP_GET_VARS['old_versions'][$y],
            'new_version' => $HTTP_GET_VARS['new_versions'][$y],
        );
    }

    $prj_id = Issue::getProjectID($issue_id);
    $module = $HTTP_GET_VARS['module'];
    $username = $HTTP_GET_VARS['username'];
    $commit_msg = $HTTP_GET_VARS['commit_msg'];

    Workflow::handleSCMCheckins($prj_id, $issue_id, $module, $files, $username, $commit_msg);
}
?>