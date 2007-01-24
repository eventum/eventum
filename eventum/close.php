<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005, 2006, 2007 MySQL AB                  |
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
// @(#) $Id: close.php 3206 2007-01-24 20:24:35Z glen $
//
require_once(dirname(__FILE__) . "/init.php");
require_once(APP_INC_PATH . "class.template.php");
require_once(APP_INC_PATH . "class.auth.php");
require_once(APP_INC_PATH . "class.issue.php");
require_once(APP_INC_PATH . "class.misc.php");
require_once(APP_INC_PATH . "class.resolution.php");
require_once(APP_INC_PATH . "class.time_tracking.php");
require_once(APP_INC_PATH . "class.status.php");
require_once(APP_INC_PATH . "class.notification.php");
require_once(APP_INC_PATH . "db_access.php");

$tpl = new Template_API();
$tpl->setTemplate("close.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

$prj_id = Auth::getCurrentProject();
$issue_id = @$_POST["issue_id"] ? $_POST["issue_id"] : @$_GET["id"];
$tpl->assign("extra_title", "Close Issue #$issue_id");

if (!Issue::exists($issue_id, false)) {
    $tpl->assign("no_issue", true);
    $tpl->displayTemplate();
    exit;
}

$notification_list = Notification::getSubscribers($issue_id, 'closed');
$tpl->assign("notification_list_all", $notification_list['all']);

$notification_list_internal = Notification::getSubscribers($issue_id, 'closed', User::getRoleID("Standard User"));
$tpl->assign("notification_list_internal", $notification_list_internal['all']);

if (@$_POST["cat"] == "close") {
    $res = Issue::close(Auth::getUserID(), $_POST["issue_id"], $_POST["send_notification"], $_POST["resolution"], $_POST["status"], $_POST["reason"], @$_REQUEST['notification_list']);

    if (!empty($_POST['time_spent'])) {
        $_POST['summary'] = 'Time entry inserted when closing issue.';
        Time_Tracking::insertEntry();
    }

    if ((Customer::hasCustomerIntegration($prj_id)) && (Customer::hasPerIncidentContract($prj_id, Issue::getCustomerID($issue_id)))) {
        Customer::updateRedeemedIncidents($prj_id, $issue_id, @$_REQUEST['redeem']);
    }

    $tpl->assign("close_result", $res);
}

$tpl->assign("statuses", Status::getClosedAssocList($prj_id));
$tpl->assign("resolutions", Resolution::getAssocList());
$tpl->assign("time_categories", Time_Tracking::getAssocCategories());

if ((Customer::hasCustomerIntegration($prj_id)) && (Customer::hasPerIncidentContract($prj_id, Issue::getCustomerID($issue_id)))) {
    $details = Issue::getDetails($issue_id);
    $tpl->assign(array(
            'redeemed'  =>  Customer::getRedeemedIncidentDetails($prj_id, $issue_id),
            'incident_details'  =>  $details['customer_info']['incident_details']
    ));
}

$usr_id = Auth::getUserID();
$user_prefs = Prefs::get($usr_id);
$tpl->assign("current_user_prefs", $user_prefs);

$tpl->displayTemplate();
