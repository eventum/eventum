<?php

/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

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
