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
// @(#) $Id$
//
include_once("config.inc.php");
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.user.php");
include_once(APP_INC_PATH . "class.note.php");
include_once(APP_INC_PATH . "db_access.php");

$tpl = new Template_API();
$tpl->setTemplate("post_note.tpl.html");

Auth::checkAuthentication(APP_COOKIE, 'index.php?err=5', true);

$prj_id = Auth::getCurrentProject();
$usr_id = Auth::getUserID();

@$issue_id = $HTTP_GET_VARS["issue_id"] ? $HTTP_GET_VARS["issue_id"] : $HTTP_POST_VARS["issue_id"];
$details = Issue::getDetails($issue_id);
$tpl->assign("issue_id", $issue_id);
$tpl->assign("issue", $details);

if (!Issue::canAccess($issue_id, $usr_id)) {
    $tpl->setTemplate("permission_denied.tpl.html");
    $tpl->displayTemplate();
    exit;
}

if (@$HTTP_POST_VARS["cat"] == "post_note") {
    // change status
    if (!@empty($HTTP_POST_VARS['new_status'])) {
        $res = Issue::setStatus($issue_id, $HTTP_POST_VARS['new_status']);
        if ($res != -1) {
            $new_status = Status::getStatusTitle($HTTP_POST_VARS['new_status']);
            History::add($issue_id, $usr_id, History::getTypeID('status_changed'), "Status changed to '$new_status' by " . User::getFullName($usr_id));
        }
    }

    $res = Note::insert($usr_id, $issue_id);
    $tpl->assign("post_result", $res);
    // enter the time tracking entry about this phone support entry
    if (!empty($HTTP_POST_VARS['time_spent'])) {
        $HTTP_POST_VARS['issue_id'] = $issue_id;
        $HTTP_POST_VARS['category'] = $HTTP_POST_VARS['time_category'];
        $HTTP_POST_VARS['summary'] = 'Time entry inserted when sending an internal note.';
        Time_Tracking::insertEntry();
    }
} elseif (@$HTTP_GET_VARS["cat"] == "reply") {
    if (!@empty($HTTP_GET_VARS["id"])) {
        $note = Note::getDetails($HTTP_GET_VARS["id"]);
        $date = Misc::formatReplyDate($note["timestamp"]);
        $header = "\n\n\nOn $date, " . $note["not_from"] . " wrote:\n>\n";
        $note["not_body"] = $header . Misc::formatReply($note["not_note"]);
        $tpl->bulkAssign(array(
            "note"           => $note,
            "parent_note_id" => $HTTP_GET_VARS["id"]
        ));
        $reply_subject = Mail_API::removeExcessRe($note['not_title']);
    }
}
if (empty($reply_subject)) {
    $reply_subject = 'Re: ' . $details['iss_summary'];
}


$tpl->assign(array(
    'from'               => User::getFromHeader($usr_id),
    'users'              => Project::getUserAssocList($prj_id, 'active', User::getRoleID('Customer')),
    'current_user_prefs' => Prefs::get($usr_id),
    'subscribers'        => Notification::getSubscribers($issue_id, false, User::getRoleID("Standard User")),
    'statuses'           => Status::getAssocStatusList($prj_id, false),
    'current_issue_status'  =>  Issue::getStatusID($issue_id),
    'time_categories'    => Time_Tracking::getAssocCategories(),
    'note_category_id'   => Time_Tracking::getCategoryID('Note Discussion'),
    'reply_subject'      => $reply_subject
));

$tpl->displayTemplate();
?>