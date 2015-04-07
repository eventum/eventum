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
// | 51 Franklin Street, Suite 330                                          |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// +----------------------------------------------------------------------+

require_once dirname(__FILE__) . '/../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate('close.tpl.html');

Auth::checkAuthentication(APP_COOKIE);

$usr_id = Auth::getUserID();
$prj_id = Auth::getCurrentProject();
$role_id = Auth::getCurrentRole();
$issue_id = @$_POST['issue_id'] ? $_POST['issue_id'] : @$_GET['id'];
$tpl->assign('extra_title', "Close Issue #$issue_id");
$tpl->assign('user_prefs', Prefs::get($usr_id));

if (!Issue::exists($issue_id, false)) {
    $tpl->assign('no_issue', true);
    $tpl->displayTemplate();
    exit;
} elseif (($role_id == User::getRoleID('customer')) || (!Issue::canAccess($issue_id, $usr_id))) {
    $tpl->assign('auth_customer', 'denied');
    $tpl->displayTemplate();
    exit;
}
$details = Issue::getDetails($issue_id);

$notification_list = Notification::getSubscribers($issue_id, 'closed');
$tpl->assign('notification_list_all', $notification_list['all']);

$notification_list_internal = Notification::getSubscribers($issue_id, 'closed', User::getRoleID('Standard User'));
$tpl->assign('notification_list_internal', $notification_list_internal['all']);

if (@$_REQUEST['cat'] == 'close') {
    Custom_Field::updateValues();
    $res = Issue::close(Auth::getUserID(), $issue_id, $_REQUEST['send_notification'], $_REQUEST['resolution'], $_REQUEST['status'], $_REQUEST['reason'], @$_REQUEST['notification_list']);

    if (!empty($_POST['time_spent'])) {
        $_POST['summary'] = 'Time entry inserted when closing issue.';
        Time_Tracking::insertEntry();
    }

    if (CRM::hasCustomerIntegration($prj_id) && isset($details['contract'])) {
        $crm = CRM::getInstance($prj_id);
        $contract = $details['contract'];
        if ($contract->hasPerIncident()) {
            $contract->updateRedeemedIncidents($issue_id, @$_REQUEST['redeem']);
        }
    }

    $tpl->assign('close_result', $res);
    if ($res == 1) {
        Misc::setMessage(ev_gettext('Thank you, the issue was closed successfully'));
        Misc::displayNotifiedUsers(Notification::getLastNotifiedAddresses($issue_id));
        Auth::redirect(APP_RELATIVE_URL . 'view.php?id=' . $issue_id);
    }
}

$tpl->assign(array(
    'statuses'      => Status::getClosedAssocList($prj_id),
    'resolutions'   => Resolution::getAssocList(),
    'time_categories'   => Time_Tracking::getAssocCategories($prj_id),
    'notify_list'       => Notification::getLastNotifiedAddresses($issue_id),
    'custom_fields'     => Custom_Field::getListByIssue($prj_id, $issue_id, $usr_id, 'close_form'),
    'issue_id'          => $issue_id,
));

if (CRM::hasCustomerIntegration($prj_id) && isset($details['contract'])) {
    $crm = CRM::getInstance($prj_id);
    $contract = $details['contract'];
    if ($contract->hasPerIncident()) {
        $details = Issue::getDetails($issue_id);
        $tpl->assign(array(
                'redeemed'  =>  $contract->getRedeemedIncidentDetails($issue_id),
                'incident_details'  =>  $details['customer']['incident_details'],
        ));
    }
}

$usr_id = Auth::getUserID();
$user_prefs = Prefs::get($usr_id);
$tpl->assign('current_user_prefs', $user_prefs);

$tpl->displayTemplate();
