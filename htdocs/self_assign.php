<?php

/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

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
