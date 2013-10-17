<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2013 Eventum Team.                              |
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

require_once dirname(__FILE__) . '/../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate("send.tpl.html");

Auth::checkAuthentication(APP_COOKIE, 'index.php?err=5', true);

$prj_id = Auth::getCurrentProject();
$usr_id = Auth::getUserID();

@$issue_id = $_GET["issue_id"] ? $_GET["issue_id"] : $_POST["issue_id"];
$tpl->assign("issue_id", $issue_id);

if (!Issue::canAccess($issue_id, $usr_id)) {
    $tpl->setTemplate("permission_denied.tpl.html");
    $tpl->displayTemplate();
    exit;
}

Workflow::prePage($prj_id, 'send_email');

// since emails associated with issues are sent to the notification list, not the to: field, set the to field to be blank
// this field should already be blank, but may also be unset.
if (!empty($issue_id)) {
    $_POST['to'] = '';
}

if (@$_POST["cat"] == "send_email") {
    $res = Support::sendEmail($_POST['parent_id']);
    $tpl->assign("send_result", $res);
    if (!@empty($_POST['new_status'])) {
        $res = Issue::setStatus($issue_id, $_POST['new_status']);
        if ($res != -1) {
            $new_status = Status::getStatusTitle($_POST['new_status']);
            History::add($issue_id, $usr_id, History::getTypeID('status_changed'), "Status changed to '$new_status' by " . User::getFullName($usr_id) . " when sending an email");
        }
    }
    // remove the existing email draft, if appropriate
    if (!empty($_POST['draft_id'])) {
        Draft::remove($_POST['draft_id']);
    }
    // enter the time tracking entry about this new email
    if (!empty($_POST['time_spent'])) {
        $_POST['issue_id'] = $issue_id;
        $_POST['category'] = Time_Tracking::getCategoryID($prj_id, 'Email Discussion');
        $_POST['summary'] = 'Time entry inserted when sending outgoing email.';
        Time_Tracking::insertEntry();
    }
} elseif (@$_POST["cat"] == "save_draft") {
    $res = Draft::saveEmail($issue_id, $_POST["to"], $_POST["cc"], $_POST["subject"], $_POST["message"], $_POST["parent_id"]);
    $tpl->assign("draft_result", $res);
} elseif (@$_POST["cat"] == "update_draft") {
    $res = Draft::update($issue_id, $_POST["draft_id"], $_POST["to"], $_POST["cc"], $_POST["subject"], $_POST["message"], $_POST["parent_id"]);
    $tpl->assign("draft_result", $res);
}

// enter the time tracking entry about this new email
if ((@$_POST["cat"] == "save_draft") || (@$_POST["cat"] == "update_draft")) {
    if (!empty($_POST['time_spent'])) {
        $_POST['issue_id'] = $issue_id;
        $_POST['category'] = Time_Tracking::getCategoryID($prj_id, 'Email Discussion');
        $_POST['summary'] = 'Time entry inserted when saving an email draft.';
        Time_Tracking::insertEntry();
    }
}

if (@$_GET['cat'] == 'view_draft') {
    $draft = Draft::getDetails($_GET['id']);
    $email = array(
        'sup_subject' => $draft['emd_subject'],
        'seb_body'    => $draft['emd_body'],
        'sup_from'    => $draft['to'],
        'cc'          => implode('; ', $draft['cc'])
    );
    // try to guess the correct email account to be associated with this email
    if (!empty($draft['emd_sup_id'])) {
        $_GET['ema_id'] = Email_Account::getAccountByEmail($draft['emd_sup_id']);
    } else {
        // if we are not replying to an existing message, just get the first email account you can find...
        $_GET['ema_id'] = Email_Account::getEmailAccount();
    }
    $tpl->bulkAssign(array(
        "draft_id"        => $_GET['id'],
        "email"           => $email,
        "parent_email_id" => $draft['emd_sup_id'],
        "draft_status"    => $draft['emd_status']
    ));
    if ($draft['emd_status'] != 'pending') {
        $tpl->assign("read_only", 1);
    }
} elseif (@$_GET['cat'] == 'create_draft') {
    $tpl->assign("hide_email_buttons", "yes");
} else {
    if (!@empty($_GET["id"])) {
        $email = Support::getEmailDetails($_GET["ema_id"], $_GET["id"]);
        $date = Misc::formatReplyDate($email["timestamp"]);
        $header = "\n\n\nOn $date, " . $email["sup_from"] . " wrote:\n>\n";
        $email['seb_body'] = $header . Misc::formatReply($email['seb_body']);
        $tpl->bulkAssign(array(
            "email"           => $email,
            "parent_email_id" => $_GET["id"]
        ));
    }
}

// special handling when someone tries to 'reply' to an issue
if (@$_GET["cat"] == 'reply') {
    $details = Issue::getReplyDetails($_GET['issue_id']);
    if ($details != '') {
        $date = Misc::formatReplyDate($details['created_date_ts']);
        $header = "\n\n\nOn $date, " . $details['reporter'] . " wrote:\n>\n";
        $details['seb_body'] = $header . Misc::formatReply($details['description']);
        $details['sup_from'] = Mail_Helper::getFormattedName($details['reporter'], $details['reporter_email']);
        $tpl->bulkAssign(array(
            "email"           => $details,
            "parent_email_id" => 0,
            "extra_title"     => "Issue #" . $_GET['issue_id'] . ": Reply"
        ));
    }
}

if (!empty($issue_id)) {
    // list the available statuses
    $tpl->assign("statuses", Status::getAssocStatusList($prj_id, false));
    $tpl->assign("current_issue_status", Issue::getStatusID($issue_id));
    // set if the current user is allowed to send emails on this issue or not
    $sender_details = User::getDetails($usr_id);
    $tpl->assign("can_send_email", Support::isAllowedToEmail($issue_id, $sender_details["usr_email"]));
    $tpl->assign('subscribers', Notification::getSubscribers($issue_id, 'emails'));
}
if ((!@empty($_GET["ema_id"])) || (!@empty($_POST["ema_id"]))) {
    @$tpl->assign("ema_id", $_GET["ema_id"] ? $_GET["ema_id"] : $_POST["ema_id"]);
}
$tpl->assign("from", User::getFromHeader($usr_id));

// list of users to display in the lookup field in the To: and Cc: fields
$t = Project::getAddressBook($prj_id, $issue_id);
$tpl->assign("assoc_users", $t);
$tpl->assign("assoc_emails", array_keys($t));

$tpl->assign("canned_responses", Email_Response::getAssocList($prj_id));
$tpl->assign("js_canned_responses", Email_Response::getAssocListBodies($prj_id));

$user_prefs = Prefs::get($usr_id);
$tpl->assign("current_user_prefs", $user_prefs);

// don't add signature if it already exists. Note: This won't handle multiple user duplicate sigs.
if ((@!empty($draft['emd_body'])) && ($user_prefs["auto_append_email_sig"] == 1) &&
        (strpos($draft['emd_body'], $user_prefs["email_signature"]) !== false)) {
    $tpl->assign('body_has_sig_already', 1);
}

$tpl->displayTemplate();
