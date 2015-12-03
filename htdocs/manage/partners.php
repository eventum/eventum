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
$tpl->setTemplate('manage/partners.tpl.html');

Auth::checkAuthentication();
$tpl->assign('type', 'partners');

$role_id = Auth::getCurrentRole();
if ($role_id < User::ROLE_MANAGER) {
    Misc::setMessage(ev_gettext('Sorry, you are not allowed to access this page.'), Misc::MSG_ERROR);
    $tpl->displayTemplate();
    exit;
}

if (@$_POST['cat'] == 'update') {
    $res = Partner::update($_POST['code'], @$_POST['projects']);
    $tpl->assign('result', $res);
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the partner was updated successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to update the partner information.'), Misc::MSG_ERROR),
    ));
}

if (@$_GET['cat'] == 'edit') {
    $info = Partner::getDetails($_GET['code']);
    $tpl->assign('info', $info);
}

$tpl->assign('list', Partner::getList());
$tpl->assign('project_list', Project::getAll());

$tpl->displayTemplate();
