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
$tpl->setTemplate('manage/priorities.tpl.html');

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
    $res = Priority::insert();
    $tpl->assign('result', $res);
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the priority was added successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to add the priority.'), Misc::MSG_ERROR),
            -2  =>  array(ev_gettext('Please enter the title for this new priority.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST['cat'] == 'update') {
    $res = Priority::update();
    $tpl->assign('result', $res);
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the priority was updated successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to update the priority.'), Misc::MSG_ERROR),
            -2  =>  array(ev_gettext('Please enter the title for this priority.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST['cat'] == 'delete') {
    Priority::remove();
}

if (@$_GET['cat'] == 'edit') {
    $tpl->assign('info', Priority::getDetails($_GET['id']));
} elseif (@$_GET['cat'] == 'change_rank') {
    Priority::changeRank($prj_id, $_GET['id'], $_GET['rank']);
}
$tpl->assign('list', Priority::getList($prj_id));

$tpl->displayTemplate();
