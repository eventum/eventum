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
$tpl->setTemplate('post_note.tpl.html');

Auth::checkAuthentication('index.php?err=5', true);

$prj_id = Auth::getCurrentProject();
$usr_id = Auth::getUserID();

$issue_id = isset($_GET['issue_id']) ? (int) $_GET['issue_id'] : (isset($_POST['issue_id']) ? (int) $_POST['issue_id'] : null);
$cat = isset($_POST['cat']) ? (string) $_POST['cat'] : (isset($_GET['cat']) ? (string) $_GET['cat'] : null);

$details = Issue::getDetails($issue_id);
$tpl->assign('issue_id', $issue_id);
$tpl->assign('issue', $details);

if ((!Issue::canAccess($issue_id, $usr_id)) || (Auth::getCurrentRole() <= User::ROLE_CUSTOMER)) {
    $tpl->setTemplate('permission_denied.tpl.html');
    $tpl->displayTemplate();
    exit;
}

Workflow::prePage($prj_id, 'post_note');

if ($cat == 'post_result' && !empty($_GET['post_result'])) {
    $res = (int) $_GET['post_result'];
    $tpl->assign('post_result', $res);
} elseif ($cat == 'post_note') {
    // change status
    $status = isset($_POST['new_status']) ? (int) $_POST['new_status'] : null;
    if ($status) {
        $res = Issue::setStatus($issue_id, $status);
        if ($res != -1) {
            $new_status = Status::getStatusTitle($status);
            History::add($issue_id, $usr_id, 'status_changed', "Status changed to '{status}' by {user} when sending a note", array(
                'status' => $new_status,
                'user' => User::getFullName($usr_id)
            ));
        }
    }

    $res = Note::insertFromPost($usr_id, $issue_id);
    Issue_Field::updateValues($issue_id, 'post_note', @$_REQUEST['issue_field']);

    if ($res == -1) {
        Misc::setMessage(ev_gettext('An error occurred while trying to run your query'), Misc::MSG_ERROR);
    } else {
        Misc::setMessage(ev_gettext('Thank you, the internal note was posted successfully.'), Misc::MSG_INFO);
    }
    $tpl->assign('post_result', $res);

    // enter the time tracking entry about this phone support entry
    if (!empty($_POST['time_spent'])) {
        if (isset($_POST['time_summary']) && !empty($_POST['time_summary'])) {
            $summary = (string) $_POST['time_summary'];
        } else {
            $summary = 'Time entry inserted when sending an internal note.';
        }
        $date = (array) $_POST['date'];
        $ttc_id = (int) $_POST['time_category'];
        $time_spent = (int) $_POST['time_spent'];
        Time_Tracking::addTimeEntry($issue_id, $ttc_id, $time_spent, $date, $summary);
    }

    Auth::redirect("post_note.php?cat=post_result&issue_id=$issue_id&post_result={$res}");
} elseif ($cat == 'reply') {
    if (!empty($_GET['id'])) {
        $note = Note::getDetails($_GET['id']);
        $header = Misc::formatReplyPreamble($note['timestamp'], $note['not_from']);
        $note['not_body'] = $header . Misc::formatReply($note['not_note']);
        $tpl->assign(array(
            'note'           => $note,
            'parent_note_id' => $_GET['id'],
        ));
        $reply_subject = Mail_Helper::removeExcessRe($note['not_title']);
    }
}

if (empty($reply_subject)) {
    // TRANSLATORS: %1 = issue summary
    $reply_subject = ev_gettext('Re: %1$s', $details['iss_summary']);
}

$tpl->assign(array(
    'from'               => User::getFromHeader($usr_id),
    'users'              => Project::getUserAssocList($prj_id, 'active', User::ROLE_CUSTOMER),
    'current_user_prefs' => Prefs::get($usr_id),
    'subscribers'        => Notification::getSubscribers($issue_id, false, User::ROLE_USER),
    'statuses'           => Status::getAssocStatusList($prj_id, false),
    'current_issue_status'  =>  Issue::getStatusID($issue_id),
    'time_categories'    => Time_Tracking::getAssocCategories($prj_id),
    'note_category_id'   => Time_Tracking::getCategoryId($prj_id, 'Note Discussion'),
    'reply_subject'      => $reply_subject,
    'issue_fields'       => Issue_Field::getDisplayData($issue_id, 'post_note'),
));

$tpl->displayTemplate();
