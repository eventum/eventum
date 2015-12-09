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
$tpl->setTemplate('manage/resolution.tpl.html');

Auth::checkAuthentication();

$role_id = Auth::getCurrentRole();
if ($role_id < User::ROLE_MANAGER) {
    Misc::setMessage(ev_gettext('Sorry, you are not allowed to access this page.'), Misc::MSG_ERROR);
    $tpl->displayTemplate();
    exit;
}

if (@$_POST['cat'] == 'new') {
    $res = Resolution::insert();
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the issue resolution was added successfully.'), Misc::MSG_INFO),
            -1   =>  array(ev_gettext('An error occurred while trying to add the new issue resolution.'), Misc::MSG_INFO),
            -2  =>  array(ev_gettext('Please enter the title for this new issue resolution.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST['cat'] == 'update') {
    $res = Resolution::update();
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the issue resolution was updated successfully.'), Misc::MSG_INFO),
            -1   =>  array(ev_gettext('An error occurred while trying to update the new issue resolution.'), Misc::MSG_INFO),
            -2  =>  array(ev_gettext('Please enter the title for this new issue resolution.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST['cat'] == 'delete') {
    Resolution::remove();
}

if (@$_GET['cat'] == 'edit') {
    $tpl->assign('info', Resolution::getDetails($_GET['id']));
}

$tpl->assign('list', Resolution::getList());

$tpl->displayTemplate();
