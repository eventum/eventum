<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004 MySQL AB                                    |
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
// @(#) $Id: s.send.php 1.23 03/12/05 18:32:39-00:00 jpradomaia $
//
include_once("config.inc.php");
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.user.php");
include_once(APP_INC_PATH . "class.support.php");
include_once(APP_INC_PATH . "class.email_response.php");
include_once(APP_INC_PATH . "db_access.php");

$tpl = new Template_API();
$tpl->setTemplate("send.tpl.html");

Auth::checkAuthentication(APP_COOKIE, 'index.php?err=5', true);

$prj_id = Auth::getCurrentProject();
$usr_id = Auth::getUserID();

@$issue_id = $HTTP_GET_VARS["issue_id"] ? $HTTP_GET_VARS["issue_id"] : $HTTP_POST_VARS["issue_id"];
$tpl->assign("issue_id", $issue_id);

if (@$HTTP_POST_VARS["cat"] == "send_email") {
    $res = Support::sendEmail();
    $tpl->assign("send_result", $res);
    if (!@empty($HTTP_POST_VARS['new_status'])) {
        Issue::setStatus($issue_id, $HTTP_POST_VARS['new_status']);
    }
} elseif (@$HTTP_POST_VARS["cat"] == "save_draft") {
    $res = Support::saveDraft();
    $tpl->assign("draft_result", $res);
}

if (!@empty($HTTP_GET_VARS["id"])) {
    $email = Support::getEmailDetails($HTTP_GET_VARS["ema_id"], $HTTP_GET_VARS["id"]);
    $date = Misc::formatReplyDate($email["timestamp"]);
    $header = "\n\n\nOn $date, " . $email["sup_from"] . " wrote:\n>\n";
    $email["sup_body"] = $header . Misc::formatReply($email["message"]);
    $tpl->bulkAssign(array(
        "email"           => $email,
        "parent_email_id" => $HTTP_GET_VARS["id"]
    ));
}

// special handling when someone tries to 'reply' to an issue
if (@$HTTP_GET_VARS["cat"] == 'reply') {
    $details = Issue::getReplyDetails($HTTP_GET_VARS['issue_id']);
    if ($details != '') {
        $date = Misc::formatReplyDate($details['created_date_ts']);
        $header = "\n\n\nOn $date, " . $details['reporter'] . " wrote:\n>\n";
        $details['sup_body'] = $header . Misc::formatReply($details['description']);
        $details['sup_from'] = Mail_API::getFormattedName($details['reporter'], $details['reporter_email']);
        $tpl->bulkAssign(array(
            "email"           => $details,
            "parent_email_id" => 0
        ));
    }
}

// show who is locking the issue, if appropriate
if (!empty($issue_id)) {
    $lock_usr_id = Issue::getLockedUserID($issue_id);
    if (!empty($lock_usr_id)) {
        $tpl->assign(array(
            "issue_lock_usr_id"  => $lock_usr_id,
            'lock_usr_full_name' => User::getFullName($lock_usr_id)
        ));
    }
    // list the available statuses
    $tpl->assign("statuses", Status::getAssocStatusList($prj_id));
    $tpl->assign("current_issue_status", Issue::getStatusID($issue_id));
}
if ((!@empty($HTTP_GET_VARS["ema_id"])) || (!@empty($HTTP_POST_VARS["ema_id"]))) {
    @$tpl->assign("ema_id", $HTTP_GET_VARS["ema_id"] ? $HTTP_GET_VARS["ema_id"] : $HTTP_POST_VARS["ema_id"]);
}
$tpl->assign("from", User::getFromHeader($usr_id));

// list of users to display in the lookup field in the To: and Cc: fields
$t = Project::getAddressBook($prj_id, $issue_id);
$tpl->assign("assoc_users", $t);
$tpl->assign("assoc_emails", array_keys($t));

$tpl->assign("canned_responses", Email_Response::getAssocList());
$tpl->assign("js_canned_responses", Email_Response::getAssocListBodies());

$tpl->assign("current_user_prefs", Prefs::get($usr_id));

$tpl->displayTemplate();
?>