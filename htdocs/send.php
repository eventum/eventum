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
$tpl->setTemplate('send.tpl.html');

Auth::checkAuthentication('index.php?err=5', true);

$prj_id = Auth::getCurrentProject();
$usr_id = Auth::getUserID();

$issue_id = isset($_GET['issue_id']) ? (int) $_GET['issue_id'] : (isset($_POST['issue_id']) ? (int) $_POST['issue_id'] : null);
$cat = isset($_POST['cat']) ? (string) $_POST['cat'] : (isset($_GET['cat']) ? (string) $_GET['cat'] : null);

$tpl->assign('issue_id', $issue_id);

if (!Issue::canAccess($issue_id, $usr_id)) {
    $tpl->setTemplate('permission_denied.tpl.html');
    $tpl->displayTemplate();
    exit;
}

Workflow::prePage($prj_id, 'send_email');

// since emails associated with issues are sent to the notification list, not the to: field, set the to field to be blank
// this field should already be blank, but may also be unset.
if ($issue_id) {
    $_POST['to'] = '';
}

if ($cat == 'send_email') {
    $res = Support::sendEmailFromPost($_POST['parent_id']);
    $tpl->assign('send_result', $res);
    if (Access::canChangeStatus($issue_id, $usr_id) && isset($_POST['new_status']) &&
        !empty($_POST['new_status'])) {
        $res = Issue::setStatus($issue_id, $_POST['new_status']);
        if ($res != -1) {
            $new_status = Status::getStatusTitle($_POST['new_status']);
            History::add($issue_id, $usr_id, 'status_changed', "Status changed to '{status}' by {user} when sending an email", array(
                'status' => $new_status,
                'user' => User::getFullName($usr_id),
            ));
        }
    }
    // remove the existing email draft, if appropriate
    if (!empty($_POST['draft_id'])) {
        Draft::remove($_POST['draft_id']);
    }
    // enter the time tracking entry about this new email
    if (!empty($_POST['time_spent'])) {
        if (isset($_POST['time_summary']) && !empty($_POST['time_summary'])) {
            $summary = (string) $_POST['time_summary'];
        } else {
            $summary = 'Time entry inserted when sending outgoing email.';
        }
        $ttc_id = (int) $_POST['time_category'];
        $time_spent = (int) $_POST['time_spent'];
        Time_Tracking::addTimeEntry($issue_id, $ttc_id, $time_spent, false, $summary);
    }
} elseif ($cat == 'save_draft') {
    $res = Draft::saveEmail($issue_id, $_POST['to'], $_POST['cc'], $_POST['subject'], $_POST['message'], $_POST['parent_id']);
    $tpl->assign('draft_result', $res);
} elseif ($cat == 'update_draft') {
    $res = Draft::update($issue_id, $_POST['draft_id'], $_POST['to'], $_POST['cc'], $_POST['subject'], $_POST['message'], $_POST['parent_id']);
    $tpl->assign('draft_result', $res);
}

// enter the time tracking entry about this new email
if ($cat == 'save_draft' || $cat == 'update_draft') {
    if (!empty($_POST['time_spent'])) {
        if (isset($_POST['time_summary']) && !empty($_POST['time_summary'])) {
            $summary = (string) $_POST['time_summary'];
        } else {
            $summary = 'Time entry inserted when saving an email draft.';
        }
        $ttc_id = (int) $_POST['time_category'];
        $time_spent = (int) $_POST['time_spent'];
        Time_Tracking::addTimeEntry($issue_id, $ttc_id, $time_spent, false, $summary);
    }
}

if ($cat == 'view_draft') {
    $draft = Draft::getDetails($_GET['id']);
    $email = array(
        'sup_subject' => $draft['emd_subject'],
        'seb_body'    => $draft['emd_body'],
        'sup_from'    => $draft['to'],
        'cc'          => implode('; ', $draft['cc']),
    );
    // try to guess the correct email account to be associated with this email
    if (!empty($draft['emd_sup_id'])) {
        $_GET['ema_id'] = Email_Account::getAccountByEmail($draft['emd_sup_id']);
    } else {
        // if we are not replying to an existing message, just get the first email account you can find...
        $_GET['ema_id'] = Email_Account::getEmailAccount();
    }
    $tpl->assign(array(
        'draft_id'        => $_GET['id'],
        'email'           => $email,
        'parent_email_id' => $draft['emd_sup_id'],
        'draft_status'    => $draft['emd_status'],
    ));
    if ($draft['emd_status'] != 'pending') {
        $tpl->assign('read_only', 1);
    }
} elseif ($cat == 'create_draft') {
    $tpl->assign('hide_email_buttons', 'yes');
} else {
    if (!empty($_GET['id'])) {
        $email = Support::getEmailDetails($_GET['ema_id'], $_GET['id']);
        $header = Misc::formatReplyPreamble($email['timestamp'], $email['sup_from']);
        $email['seb_body'] = $header . Misc::formatReply($email['seb_body']);
        $tpl->assign(array(
            'email'           => $email,
            'parent_email_id' => $_GET['id'],
        ));
    }
}

// special handling when someone tries to 'reply' to an issue
if ($cat == 'reply') {
    $details = Issue::getReplyDetails($_GET['issue_id']);
    if ($details != '') {
        $header = Misc::formatReplyPreamble($details['created_date_ts'], $details['reporter']);
        $details['seb_body'] = $header . Misc::formatReply($details['description']);
        $details['sup_from'] = Mail_Helper::getFormattedName($details['reporter'], $details['reporter_email']);
        $tpl->assign(array(
            'email'           => $details,
            'parent_email_id' => 0,
            'extra_title'     => 'Issue #' . $_GET['issue_id'] . ': Reply',
        ));
    }
}

if (!empty($issue_id)) {
    // list the available statuses
    $tpl->assign('statuses', Status::getAssocStatusList($prj_id, false));
    $tpl->assign('current_issue_status', Issue::getStatusID($issue_id));
    // set if the current user is allowed to send emails on this issue or not
    $sender_details = User::getDetails($usr_id);
    $tpl->assign('can_send_email', Support::isAllowedToEmail($issue_id, $sender_details['usr_email']));
    $tpl->assign('subscribers', Notification::getSubscribers($issue_id, 'emails'));
}
if ((!empty($_GET['ema_id'])) || (!empty($_POST['ema_id']))) {
    $ema_id = isset($_GET['ema_id']) ? (int) $_GET['ema_id'] : (isset($_POST['ema_id']) ? (int) $_POST['ema_id'] : null);
    $tpl->assign('ema_id', $ema_id);
}

$user_prefs = Prefs::get($usr_id);
// list of users to display in the lookup field in the To: and Cc: fields
$t = Project::getAddressBook($prj_id, $issue_id);

$tpl->assign(array(
    'from' => User::getFromHeader($usr_id),
    'assoc_users' => $t,
    'assoc_emails' => array_keys($t),
    'canned_responses' => Email_Response::getAssocList($prj_id),
    'js_canned_responses' => Email_Response::getAssocListBodies($prj_id),
    'current_user_prefs' => $user_prefs,
    'issue_access' => Access::getIssueAccessArray($issue_id, $usr_id),
    'max_attachment_size' => Attachment::getMaxAttachmentSize(),
    'max_attachment_bytes' => Attachment::getMaxAttachmentSize(true),
    'time_categories'    => Time_Tracking::getAssocCategories($prj_id),
    'email_category_id'   => Time_Tracking::getCategoryId($prj_id, 'Email Discussion'),
));

// don't add signature if it already exists. Note: This won't handle multiple user duplicate sigs.
if ((@!empty($draft['emd_body'])) && ($user_prefs['auto_append_email_sig'] == 1) &&
        (strpos($draft['emd_body'], $user_prefs['email_signature']) !== false)) {
    $tpl->assign('body_has_sig_already', 1);
}

$tpl->displayTemplate();
