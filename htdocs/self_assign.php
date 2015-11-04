<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2015 Eventum Team.                              |
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
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+

require_once __DIR__ . '/../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate('self_assign.tpl.html');

Auth::checkAuthentication('index.php?err=5', true);
$usr_id = Auth::getUserID();
$prj_id = Auth::getCurrentProject();
$issue_id = $_REQUEST['iss_id'];
$tpl->assign('issue_id', $issue_id);

// check if issue is assigned to someone else and if so, confirm change.
$assigned_user_ids = Issue::getAssignedUserIDs($issue_id);
if ((count($assigned_user_ids) > 0) && (empty($_REQUEST['target']))) {
    $tpl->assign(array(
        'prompt_override'   =>  1,
        'assigned_users'    =>  Issue::getAssignedUsers($issue_id),
    ));
} else {
    $issue_details = Issue::getDetails($issue_id);
    // force assignment change
    if (@$_REQUEST['target'] == 'replace') {
        // remove current user(s) first
        Issue::deleteUserAssociations($issue_id, $usr_id);
    }
    $res = Issue::addUserAssociation($usr_id, $issue_id, $usr_id);
    $tpl->assign('self_assign_result', $res);
    Notification::subscribeUser($usr_id, $issue_id, $usr_id, Notification::getDefaultActions($issue_id, User::getEmail($usr_id), 'self_assign'));
    Workflow::handleAssignmentChange($prj_id, $issue_id, $usr_id, $issue_details, Issue::getAssignedUserIDs($issue_id), false);
}

$tpl->assign('current_user_prefs', Prefs::get($usr_id));

$tpl->displayTemplate();
