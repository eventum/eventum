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
$tpl->setTemplate('manage/groups.tpl.html');

Auth::checkAuthentication();

$role_id = Auth::getCurrentRole();
if ($role_id < User::ROLE_MANAGER) {
    Misc::setMessage(ev_gettext('Sorry, you are not allowed to access this page.'), Misc::MSG_ERROR);
    $tpl->displayTemplate();
    exit;
}

if (@$_POST['cat'] == 'new') {
    $res = Group::insert();
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the group was added successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to add the new group.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST['cat'] == 'update') {
    $res = Group::update();
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the group was updated successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to update the group.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST['cat'] == 'delete') {
    Group::remove();
}

if (@$_GET['cat'] == 'edit') {
    $info = Group::getDetails($_GET['id']);
    $tpl->assign('info', $info);
    $user_options = User::getActiveAssocList(Auth::getCurrentProject(), User::ROLE_CUSTOMER, false, $_GET['id']);
} else {
    $user_options = User::getActiveAssocList(Auth::getCurrentProject(), User::ROLE_CUSTOMER, true);
}

if (@$_GET['show_customers'] == 1) {
    $show_customer = true;
} else {
    $show_customer = false;
}

$tpl->assign('user_options', $user_options);
$tpl->assign('list', Group::getList());
$tpl->assign('project_list', Project::getAll());

$tpl->displayTemplate();
