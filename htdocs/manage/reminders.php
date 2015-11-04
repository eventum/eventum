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

require_once __DIR__ . '/../../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate('manage/reminders.tpl.html');

Auth::checkAuthentication();

$role_id = Auth::getCurrentRole();
if ($role_id < User::ROLE_MANAGER) {
    Misc::setMessage(ev_gettext('Sorry, you are not allowed to access this page.'), Misc::MSG_ERROR);
    $tpl->displayTemplate();
    exit;
}
$tpl->assign('backend_uses_support_levels', false);
$tpl->assign('project_has_customer_integration', false);

if (@$_POST['cat'] == 'new') {
    $res = Reminder::insert();
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the reminder was added successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to add the new reminder.'), Misc::MSG_ERROR),
            -2  =>  array(ev_gettext('Please enter the title for this new reminder.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST['cat'] == 'update') {
    $res = Reminder::update();
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the reminder was updated successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to update the reminder.'), Misc::MSG_ERROR),
            -2  =>  array(ev_gettext('Please enter the title for this reminder.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST['cat'] == 'delete') {
    Reminder::remove();
}

if (@$_GET['cat'] == 'edit') {
    $info = Reminder::getDetails($_GET['id']);
    if (!empty($_GET['prj_id'])) {
        $info['rem_prj_id'] = $_GET['prj_id'];
    }
    // only show customers and support levels if the selected project really needs it
    $project_has_customer_integration = CRM::hasCustomerIntegration($info['rem_prj_id']);
    $tpl->assign('project_has_customer_integration', $project_has_customer_integration);
    if ($project_has_customer_integration) {
        $crm = CRM::getInstance($info['rem_prj_id']);
        $tpl->assign('customers', $crm->getCustomerAssocList());
        $tpl->assign('support_levels', $crm->getSupportLevelAssocList());
    }
    $tpl->assign('issues', Reminder::getIssueAssocListByProject($info['rem_prj_id']));
    $tpl->assign('info', $info);
    // wouldn't make much sense to create a reminder for a 'Not Prioritized'
    // issue, so let's remove that as an option
    $priorities = array_flip(Priority::getAssocList($info['rem_prj_id']));
    unset($priorities['Not Prioritized']);
    $tpl->assign('priorities', array_flip($priorities));
    $tpl->assign('severities', Severity::getAssocList($info['rem_prj_id']));
    $tpl->assign('products', Product::getAssocList());
} elseif (@$_GET['cat'] == 'change_rank') {
    Reminder::changeRank($_GET['id'], $_GET['rank']);
} elseif (!empty($_GET['prj_id'])) {
    $tpl->assign('info', array('rem_prj_id' => $_GET['prj_id']));
    $tpl->assign('issues', Reminder::getIssueAssocListByProject($_GET['prj_id']));
    // wouldn't make much sense to create a reminder for a 'Not Prioritized'
    // issue, so let's remove that as an option
    $priorities = array_flip(Priority::getAssocList($_GET['prj_id']));
    unset($priorities['Not Prioritized']);
    $tpl->assign('priorities', array_flip($priorities));
    $tpl->assign('severities', Severity::getAssocList($_GET['prj_id']));
    $tpl->assign('products', Product::getAssocList());
    // only show customers and support levels if the selected project really needs it
    $project_has_customer_integration = CRM::hasCustomerIntegration($_GET['prj_id']);
    $tpl->assign('project_has_customer_integration', $project_has_customer_integration);
    if ($project_has_customer_integration) {
        $crm = CRM::getInstance($_GET['prj_id']);
        $tpl->assign('customers', $crm->getCustomerAssocList());
        $tpl->assign('support_levels', $crm->getSupportLevelAssocList());
    }
}

$tpl->assign('project_list', Project::getAll());
$tpl->assign('list', Reminder::getAdminList());

$tpl->displayTemplate();
