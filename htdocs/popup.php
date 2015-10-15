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
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

require_once __DIR__ . '/../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate('popup.tpl.html');

Auth::checkAuthentication('index.php?err=5', true);
$usr_id = Auth::getUserID();
$prj_id = Auth::getCurrentProject();

$iss_id = isset($_GET['iss_id']) ? (int) $_GET['iss_id'] : (isset($_POST['issue_id']) ? (int) $_POST['issue_id'] : null);
$cat = isset($_GET['cat']) ? (string) $_GET['cat'] : (isset($_POST['cat']) ? (string) $_POST['cat'] : null);
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$status_id = isset($_GET['new_sta_id']) ? (int) $_GET['new_sta_id'] : null;
$isr_id = isset($_POST['isr_id']) ? (int) $_POST['isr_id'] : null;
$items = isset($_POST['item']) ? (array) $_POST['item'] : null;

if ($cat == 'delete_note') {
    $res = Note::remove($id);
    $tpl->assign('note_delete_result', $res);
} elseif ($cat == 'delete_time') {
    $res = Time_Tracking::removeTimeEntry($id, $usr_id);
    $tpl->assign('time_delete_result', $res);
} elseif ($cat == 'bulk_update') {
    $res = Issue::bulkUpdate();
    $tpl->assign('bulk_update_result', $res);
} elseif ($cat == 'set_initial_impact') {
    $res = Issue::setImpactAnalysis($iss_id);
    $tpl->assign('set_initial_impact_result', $res);
} elseif ($cat == 'add_requirement') {
    $res = Impact_Analysis::insert($iss_id);
    $tpl->assign('add_requirement_result', $res);
} elseif ($cat == 'set_impact_requirement') {
    $res = Impact_Analysis::update($isr_id);
    $tpl->assign('set_impact_requirement_result', $res);
} elseif ($cat == 'delete_requirement') {
    $res = Impact_Analysis::remove();
    $tpl->assign('requirement_delete_result', $res);
} elseif ($cat == 'save_filter') {
    $res = Filter::save();
    $tpl->assign('save_filter_result', $res);
} elseif ($cat == 'delete_filter') {
    $res = Filter::remove();
    $tpl->assign('delete_filter_result', $res);
} elseif ($cat == 'remove_support_email') {
    $res = Support::removeAssociation();
    $tpl->assign('remove_association_result', $res);
} elseif ($cat == 'delete_attachment') {
    $res = Attachment::remove($id);
    $tpl->assign('remove_attachment_result', $res);
} elseif ($cat == 'delete_file') {
    $res = Attachment::removeIndividualFile($id);
    $tpl->assign('remove_file_result', $res);
} elseif ($cat == 'remove_checkin') {
    $res = SCM::remove($items);
    $tpl->assign('remove_checkin_result', $res);
} elseif ($cat == 'unassign') {
    $res = Issue::deleteUserAssociation($iss_id, $usr_id);
    Workflow::handleAssignmentChange(
        $prj_id, $iss_id, Auth::getUserID(), Issue::getDetails($iss_id), Issue::getAssignedUserIDs($iss_id)
    );
    $tpl->assign('unassign_result', $res);
} elseif ($cat == 'remove_email') {
    $res = Support::removeEmails();
    $tpl->assign('remove_email_result', $res);
} elseif ($cat == 'clear_duplicate') {
    $res = Issue::clearDuplicateStatus($iss_id);
    $tpl->assign('clear_duplicate_result', $res);
} elseif ($cat == 'delete_phone') {
    $res = Phone_Support::remove($id);
    $tpl->assign('delete_phone_result', $res);
} elseif ($cat == 'new_status') {
    $res = Issue::setStatus($iss_id, $status_id, true);
    if ($res == 1) {
        History::add($iss_id, $usr_id, 'status_changed', "Issue manually set to status '{status}' by {user}", array(
            'status' => Status::getStatusTitle($status_id),
            'user' => User::getFullName($usr_id),
        ));
    }
    $tpl->assign('new_status_result', $res);
} elseif ($cat == 'authorize_reply') {
    $res = Authorized_Replier::addUser($iss_id, $usr_id);
    $tpl->assign('authorize_reply_result', $res);
} elseif ($cat == 'remove_quarantine') {
    if (Auth::getCurrentRole() > User::ROLE_DEVELOPER) {
        $res = Issue::setQuarantine($iss_id, 0);
        $tpl->assign('remove_quarantine_result', $res);
    }
} elseif ($cat == 'selfnotify') {
    if (Issue::canAccess($iss_id, $usr_id)) {
        $res = Notification::subscribeUser($usr_id, $iss_id, $usr_id, Notification::getDefaultActions($iss_id));
        $tpl->assign('selfnotify_result', $res);
    }
}

$tpl->assign('current_user_prefs', Prefs::get($usr_id));
$tpl->assign('cat', $cat);

$tpl->displayTemplate();
