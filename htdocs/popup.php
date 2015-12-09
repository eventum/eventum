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
