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
$tpl->setTemplate('manage/link_filters.tpl.html');

Auth::checkAuthentication();

$role_id = Auth::getCurrentRole();
if ($role_id < User::ROLE_MANAGER) {
    Misc::setMessage(ev_gettext('Sorry, you are not allowed to access this page.'), Misc::MSG_ERROR);
    $tpl->displayTemplate();
    exit;
}

if (@$_POST['cat'] == 'new') {
    $res = Link_Filter::insert();
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the link filter was added successfully.'), Misc::MSG_INFO),
            -1   =>  array(ev_gettext('An error occurred while trying to add the new link filter.'), Misc::MSG_INFO),
    ));
} elseif (@$_POST['cat'] == 'update') {
    $res = Link_Filter::update();
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the link filter was updated successfully.'), Misc::MSG_INFO),
            -1   =>  array(ev_gettext('An error occurred while trying to update the link filter.'), Misc::MSG_INFO),
    ));
} elseif (@$_POST['cat'] == 'delete') {
    $res = Link_Filter::remove();
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the link filter was deleted successfully.'), Misc::MSG_INFO),
            -1   =>  array(ev_gettext('An error occurred while trying to delete the link filter.'), Misc::MSG_INFO),
    ));
}

if (@$_GET['cat'] == 'edit') {
    $info = Link_Filter::getDetails($_GET['id']);
    $tpl->assign('info', $info);
}

$user_roles = User::getRoles();

$tpl->assign('list', Link_Filter::getList());
$tpl->assign('project_list', Project::getAll());
$tpl->assign('user_roles', $user_roles);

$tpl->displayTemplate();
