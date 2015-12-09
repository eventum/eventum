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
$tpl->setTemplate('manage/round_robin.tpl.html');

Auth::checkAuthentication();

$role_id = Auth::getCurrentRole();
if ($role_id < User::ROLE_MANAGER) {
    Misc::setMessage(ev_gettext('Sorry, you are not allowed to access this page.'), Misc::MSG_ERROR);
    $tpl->displayTemplate();
    exit;
}

if (@$_POST['cat'] == 'new') {
    $res = Round_Robin::insert();
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the round robin entry was added successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to add the round robin entry.'), Misc::MSG_ERROR),
            -2  =>  array(ev_gettext('Please enter the title for this round robin entry.'), Misc::MSG_ERROR),
            -3  =>  array(ev_gettext('Please enter the message for this round robin entry.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST['cat'] == 'update') {
    $res = Round_Robin::update();
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the round robin entry was updated successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to update the round robin entry information.'), Misc::MSG_ERROR),
            -2  =>  array(ev_gettext('Please enter the title for this round robin entry.'), Misc::MSG_ERROR),
            -3  =>  array(ev_gettext('Please enter the message for this round robin entry.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST['cat'] == 'delete') {
    Round_Robin::remove();
}

if (@$_GET['cat'] == 'edit') {
    $info = Round_Robin::getDetails($_GET['id']);
    $tpl->assign('info', $info);
    $_REQUEST['prj_id'] = $info['prr_prj_id'];
}

$tpl->assign('list', Round_Robin::getList());
if (!empty($_REQUEST['prj_id'])) {
    $tpl->assign('user_options', User::getActiveAssocList($_REQUEST['prj_id'], User::ROLE_CUSTOMER));
}
$tpl->assign('project_list', Project::getAll());
$tpl->displayTemplate();
