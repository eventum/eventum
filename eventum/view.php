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
// @(#) $Id: s.view.php 1.27 04/01/23 03:42:02-00:00 jpradomaia $
//
include_once("config.inc.php");
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.issue.php");
include_once(APP_INC_PATH . "class.time_tracking.php");
include_once(APP_INC_PATH . "class.note.php");
include_once(APP_INC_PATH . "class.impact_analysis.php");
include_once(APP_INC_PATH . "class.support.php");
include_once(APP_INC_PATH . "class.notification.php");
include_once(APP_INC_PATH . "class.attachment.php");
include_once(APP_INC_PATH . "class.custom_field.php");
include_once(APP_INC_PATH . "class.phone_support.php");
include_once(APP_INC_PATH . "class.scm.php");
include_once(APP_INC_PATH . "db_access.php");

$tpl = new Template_API();
$tpl->setTemplate("view.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

$prj_id = Auth::getCurrentProject();
$usr_id = Auth::getUserID();
$role_id = User::getRoleByUser($usr_id);

$issue_id = @$HTTP_POST_VARS["issue_id"] ? $HTTP_POST_VARS["issue_id"] : $HTTP_GET_VARS["id"];
$tpl->assign("extra_title", "Issue #$issue_id Details");

if (@$HTTP_GET_VARS['cat'] == 'lock') {
    Issue::lock($issue_id, $usr_id);
} elseif (@$HTTP_GET_VARS['cat'] == 'unlock') {
    Issue::unlock($issue_id);
}

$details = Issue::getDetails($issue_id);
$tpl->assign("issue", $details);

// XXX: need to check if the current user has access to the project associated with this issue id

$options = Issue::saveSearchParams();
$sides = Issue::getSides($issue_id, $options);
$tpl->assign(array(
    "next_issue"     => @$sides["next"],
    "previous_issue" => @$sides["previous"],
    "subscribers"    => Notification::getSubscribers($issue_id),
    "custom_fields"  => Custom_Field::getListByIssue($prj_id, $issue_id),
    "files"          => Attachment::getList($issue_id),
    "notes"          => Note::getListing($issue_id),
    "emails"         => Support::getEmailsByIssue($issue_id),
    "phone_entries"  => Phone_Support::getListing($issue_id),
    "zones"          => Date_API::getTimezoneList(),
    'users'          => Project::getUserAssocList($prj_id, 'active', User::getRoleID('Reporter'))
));

$tpl->assign("ema_id", Support::getEmailAccount());

$time_entries = Time_Tracking::getListing($issue_id);
$tpl->assign(array(
    "checkins"         => SCM::getCheckinList($issue_id),
    "time_categories"  => Time_Tracking::getAssocCategories(),
    "time_entries"     => $time_entries['list'],
    "total_time_spent" => $time_entries['total_time_spent'],
    "impacts"          => Impact_Analysis::getListing($issue_id),
    "statuses"         => Status::getAssocStatusList($prj_id)
));

$tpl->displayTemplate();
?>