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

require_once dirname(__FILE__) . '/../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate("post_note.tpl.html");

Auth::checkAuthentication(APP_COOKIE, 'index.php?err=5', true);

$prj_id = Auth::getCurrentProject();
$usr_id = Auth::getUserID();

@$issue_id = $_GET["issue_id"] ? $_GET["issue_id"] : $_POST["issue_id"];
$details = Issue::getDetails($issue_id);
$tpl->assign("issue_id", $issue_id);
$tpl->assign("issue", $details);

if ((!Issue::canAccess($issue_id, $usr_id)) || (Auth::getCurrentRole() <= User::getRoleID("Customer"))) {
    $tpl->setTemplate("permission_denied.tpl.html");
    $tpl->displayTemplate();
    exit;
}

Workflow::prePage($prj_id, 'post_note');

if (@$_POST["cat"] == "post_note") {
    // change status
    if (!@empty($_POST['new_status'])) {
        $res = Issue::setStatus($issue_id, $_POST['new_status']);
        if ($res != -1) {
            $new_status = Status::getStatusTitle($_POST['new_status']);
            History::add($issue_id, $usr_id, History::getTypeID('status_changed'), "Status changed to '$new_status' by " . User::getFullName($usr_id));
        }
    }

    $res = Note::insert($usr_id, $issue_id);
    Issue_Field::updateValues($issue_id, 'post_note', @$_REQUEST['issue_field']);
    $tpl->assign("post_result", $res);
    // enter the time tracking entry about this phone support entry
    if (!empty($_POST['time_spent'])) {
        $_POST['issue_id'] = $issue_id;
        $_POST['category'] = $_POST['time_category'];
        $_POST['summary'] = 'Time entry inserted when sending an internal note.';
        Time_Tracking::insertEntry();
    }
} elseif (@$_GET["cat"] == "reply") {
    if (!@empty($_GET["id"])) {
        $note = Note::getDetails($_GET["id"]);
        $date = Misc::formatReplyDate($note["timestamp"]);
        $header = "\n\n\nOn $date, " . $note["not_from"] . " wrote:\n>\n";
        $note["not_body"] = $header . Misc::formatReply($note["not_note"]);
        $tpl->bulkAssign(array(
            "note"           => $note,
            "parent_note_id" => $_GET["id"]
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
    'users'              => Project::getUserAssocList($prj_id, 'active', User::getRoleID('Customer')),
    'current_user_prefs' => Prefs::get($usr_id),
    'subscribers'        => Notification::getSubscribers($issue_id, false, User::getRoleID("Standard User")),
    'statuses'           => Status::getAssocStatusList($prj_id, false),
    'current_issue_status'  =>  Issue::getStatusID($issue_id),
    'time_categories'    => Time_Tracking::getAssocCategories($prj_id),
    'note_category_id'   => Time_Tracking::getCategoryID($prj_id, 'Note Discussion'),
    'reply_subject'      => $reply_subject,
    'issue_fields'       => Issue_Field::getDisplayData($issue_id, 'post_note'),
));

$tpl->displayTemplate();
