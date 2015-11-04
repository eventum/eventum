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
$tpl->setTemplate('manage/issue_auto_creation.tpl.html');

Auth::checkAuthentication();

@$ema_id = $_POST['ema_id'] ? $_POST['ema_id'] : $_GET['ema_id'];

$role_id = Auth::getCurrentRole();
if ($role_id < User::ROLE_REPORTER) {
    Misc::setMessage('Sorry, you are not allowed to access this page.', Misc::MSG_ERROR);
    $tpl->displayTemplate();
    exit;
}

$prj_id = Email_Account::getProjectID($ema_id);

if (@$_POST['cat'] == 'update') {
    @Email_Account::updateIssueAutoCreation($ema_id, $_POST['issue_auto_creation'], $_POST['options']);
}
// load the form fields
$tpl->assign('info', Email_Account::getDetails($ema_id));
$tpl->assign('cats', Category::getAssocList($prj_id));
$tpl->assign('priorities', Priority::getList($prj_id));
$tpl->assign('users', Project::getUserAssocList($prj_id, 'active'));
$tpl->assign('options', Email_Account::getIssueAutoCreationOptions($ema_id));
$tpl->assign('ema_id', $ema_id);
$tpl->assign('prj_title', Project::getName($prj_id));
$tpl->assign('uses_customer_integration', CRM::hasCustomerIntegration($prj_id));
$tpl->displayTemplate();
