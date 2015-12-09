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
$tpl->setTemplate('manage/phone_categories.tpl.html');

Auth::checkAuthentication();

$role_id = Auth::getCurrentRole();
if ($role_id < User::ROLE_MANAGER) {
    Misc::setMessage(ev_gettext('Sorry, you are not allowed to access this page.'), Misc::MSG_ERROR);
    $tpl->displayTemplate();
    exit;
}

@$prj_id = $_POST['prj_id'] ? $_POST['prj_id'] : $_GET['prj_id'];
$tpl->assign('project', Project::getDetails($prj_id));

if (@$_POST['cat'] == 'new') {
    $res = Phone_Support::insertCategory();
    $tpl->assign('result', $res);
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the phone category was added successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to add the phone category.'), Misc::MSG_ERROR),
            -2  =>  array(ev_gettext('Please enter the title for this new phone category.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST['cat'] == 'update') {
    $res = Phone_Support::updateCategory();
    $tpl->assign('result', $res);
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the phone category was updated successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to uodate the phone category.'), Misc::MSG_ERROR),
            -2  =>  array(ev_gettext('Please enter the title for this phone category.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST['cat'] == 'delete') {
    Phone_Support::removeCategory();
}

if (@$_GET['cat'] == 'edit') {
    $tpl->assign('info', Phone_Support::getCategoryDetails($_GET['id']));
}
$tpl->assign('list', Phone_Support::getCategoryList($prj_id));

$tpl->displayTemplate();
