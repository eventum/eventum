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
$tpl->setTemplate('manage/account_managers.tpl.html');

Auth::checkAuthentication();

$role_id = Auth::getCurrentRole();
if ($role_id < User::ROLE_MANAGER) {
    Misc::setMessage(ev_gettext('Sorry, you are not allowed to access this page.'), Misc::MSG_ERROR);
    $tpl->displayTemplate();
    exit;
}

if (@$_POST['cat'] == 'new') {
    $res = CRM::insertAccountManager();
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the account manager was added successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to add the the account manager.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST['cat'] == 'update') {
    $res = CRM::updateAccountManager();
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the account manager was updated successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to update the the account manager.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST['cat'] == 'delete') {
    CRM::removeAccountManager();
} elseif (!empty($_GET['prj_id'])) {
    $tpl->assign('info', array('cam_prj_id' => $_GET['prj_id']));
    $crm = CRM::getInstance($_GET['prj_id']);
    $tpl->assign('customers', $crm->getCustomerAssocList());
}

if (@$_GET['cat'] == 'edit') {
    $info = CRM::getAccountManagerDetails($_GET['id']);
    if (!empty($_GET['prj_id'])) {
        $info['cam_prj_id'] = $_GET['prj_id'];
    }
    $tpl->assign('customers', CRM::getInstance($info['cam_prj_id'])->getCustomerAssocList());
    $tpl->assign('user_options', User::getActiveAssocList($info['cam_prj_id'], User::ROLE_CUSTOMER));
    $tpl->assign('info', $info);
}

$tpl->assign('list', CRM::getAccountManagerList());
if (!empty($_REQUEST['prj_id'])) {
    $tpl->assign('user_options', User::getActiveAssocList($_REQUEST['prj_id'], User::ROLE_CUSTOMER));
}
$tpl->assign('project_list', Project::getAll(false));

$tpl->displayTemplate();
