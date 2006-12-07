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
// @(#) $Id: s.popup.php 1.25 04/01/23 03:42:02-00:00 jpradomaia $
//
include_once("config.inc.php");
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.note.php");
include_once(APP_INC_PATH . "class.time_tracking.php");
include_once(APP_INC_PATH . "class.issue.php");
include_once(APP_INC_PATH . "class.impact_analysis.php");
include_once(APP_INC_PATH . "class.filter.php");
include_once(APP_INC_PATH . "class.support.php");
include_once(APP_INC_PATH . "class.attachment.php");
include_once(APP_INC_PATH . "class.scm.php");
include_once(APP_INC_PATH . "class.notification.php");
include_once(APP_INC_PATH . "class.phone_support.php");
include_once(APP_INC_PATH . "class.status.php");
include_once(APP_INC_PATH . "class.history.php");
include_once(APP_INC_PATH . "class.user.php");
include_once(APP_INC_PATH . "class.authorized_replier.php");
include_once(APP_INC_PATH . "db_access.php");

$tpl = new Template_API();
$tpl->setTemplate("popup.tpl.html");

Auth::checkAuthentication(APP_COOKIE, 'index.php?err=5', true);
$usr_id = Auth::getUserID();
$prj_id = Auth::getCurrentProject();

if (@$_GET["cat"] == "delete_note") {
    $res = Note::remove($_GET["id"]);
    $tpl->assign("note_delete_result", $res);
} elseif (@$_GET["cat"] == "delete_time") {
    $res = Time_Tracking::removeEntry($_GET["id"], $usr_id);
    $tpl->assign("time_delete_result", $res);
} elseif (@$_POST["cat"] == "bulk_update") {
    $res = Issue::bulkUpdate();
    $tpl->assign("bulk_update_result", $res);
} elseif (@$_POST["cat"] == "set_initial_impact") {
    $res = Issue::setImpactAnalysis($_POST["issue_id"]);
    $tpl->assign("set_initial_impact_result", $res);
} elseif (@$_POST["cat"] == "add_requirement") {
    $res = Impact_Analysis::insert($_POST["issue_id"]);
    $tpl->assign("add_requirement_result", $res);
} elseif (@$_POST["cat"] == "set_impact_requirement") {
    $res = Impact_Analysis::update($_POST["isr_id"]);
    $tpl->assign("set_impact_requirement_result", $res);
} elseif (@$_POST["cat"] == "delete_requirement") {
    $res = Impact_Analysis::remove();
    $tpl->assign("requirement_delete_result", $res);
} elseif (@$_POST["cat"] == "save_filter") {
    $res = Filter::save();
    $tpl->assign("save_filter_result", $res);
} elseif (@$_POST["cat"] == "delete_filter") {
    $res = Filter::remove();
    $tpl->assign("delete_filter_result", $res);
} elseif (@$_POST["cat"] == "remove_support_email") {
    $res = Support::removeAssociation();
    $tpl->assign("remove_association_result", $res);
} elseif (@$_GET["cat"] == "delete_attachment") {
    $res = Attachment::remove($_GET["id"]);
    $tpl->assign("remove_attachment_result", $res);
} elseif (@$_GET["cat"] == "delete_file") {
    $res = Attachment::removeIndividualFile($_GET["id"]);
    $tpl->assign("remove_file_result", $res);
} elseif (@$_POST["cat"] == "remove_checkin") {
    $res = SCM::remove();
    $tpl->assign("remove_checkin_result", $res);
} elseif (@$_GET['cat'] == 'unassign') {
    $res = Issue::deleteUserAssociation($_GET["iss_id"], $usr_id);
    Workflow::handleAssignmentChange($prj_id, $_GET["iss_id"], Auth::getUserID(), Issue::getDetails($_GET["iss_id"]), Issue::getAssignedUserIDs($_GET["iss_id"]));
    $tpl->assign('unassign_result', $res);
} elseif (@$_POST["cat"] == "remove_email") {
    $res = Support::removeEmails();
    $tpl->assign("remove_email_result", $res);
} elseif (@$_GET["cat"] == "clear_duplicate") {
    $res = Issue::clearDuplicateStatus($_GET["iss_id"]);
    $tpl->assign("clear_duplicate_result", $res);
} elseif (@$_GET["cat"] == "delete_phone") {
    $res = Phone_Support::remove($_GET["id"]);
    $tpl->assign("delete_phone_result", $res);
} elseif (@$_GET["cat"] == "new_status") {
    // XXX: need to call the workflow api in the following function?
    $res = Issue::setStatus($_GET["iss_id"], $_GET["new_sta_id"], true);
    if ($res == 1) {
        History::add($_GET["iss_id"], $usr_id, History::getTypeID('status_changed'), 
                "Issue manually set to status '" . Status::getStatusTitle($_GET["new_sta_id"]) . "' by " . User::getFullName($usr_id));
    }
    $tpl->assign("new_status_result", $res);
} elseif (@$_GET['cat'] == 'authorize_reply') {
    $res = Authorized_Replier::addUser($_GET["iss_id"], $usr_id);
    $tpl->assign('authorize_reply_result', $res);
} elseif (@$_GET['cat'] == 'remove_quarantine') {
    if (Auth::getCurrentRole() > User::getRoleID('Developer')) {
        $res = Issue::setQuarantine($_GET['iss_id'], 0);
        $tpl->assign('remove_quarantine_result', $res);
    }
}

$tpl->assign("current_user_prefs", Prefs::get($usr_id));

$tpl->displayTemplate();
?>