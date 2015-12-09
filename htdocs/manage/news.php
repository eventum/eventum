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
$tpl->setTemplate('manage/news.tpl.html');

Auth::checkAuthentication();

$role_id = Auth::getCurrentRole();
if ($role_id < User::ROLE_MANAGER) {
    Misc::setMessage(ev_gettext('Sorry, you are not allowed to access this page.'), Misc::MSG_ERROR);
    $tpl->displayTemplate();
    exit;
}

if (@$_POST['cat'] == 'new') {
    $res = News::insert();
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the news entry was added successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to add the news entry.'), Misc::MSG_ERROR),
            -2  =>  array(ev_gettext('Please enter the title for this news entry.'), Misc::MSG_ERROR),
            -3  =>  array(ev_gettext('Please enter the message for this news entry.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST['cat'] == 'update') {
    $res = News::update();
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the news entry was updated successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to update the news entry.'), Misc::MSG_ERROR),
            -2  =>  array(ev_gettext('Please enter the title for this news entry.'), Misc::MSG_ERROR),
            -3  =>  array(ev_gettext('Please enter the message for this news entry.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST['cat'] == 'delete') {
    News::remove();
}

if (@$_GET['cat'] == 'edit') {
    $tpl->assign('info', News::getAdminDetails($_GET['id']));
}

$tpl->assign('list', News::getList());
$tpl->assign('project_list', Project::getAll());

$tpl->displayTemplate();
