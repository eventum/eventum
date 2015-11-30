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
$tpl->setTemplate('manage/severities.tpl.html');

Auth::checkAuthentication();

$role_id = Auth::getCurrentRole();
if ($role_id < User::ROLE_MANAGER) {
    Misc::setMessage('Sorry, you are not allowed to access this page.', Misc::MSG_ERROR);
    $tpl->displayTemplate();
    exit;
}

@$prj_id = $_POST['prj_id'] ? $_POST['prj_id'] : $_GET['prj_id'];
$tpl->assign('project', Project::getDetails($prj_id));

if (@$_POST['cat'] == 'new') {
    $res = Severity::insert($prj_id, $_POST['title'], $_POST['description'], $_POST['rank']);
    Misc::mapMessages($res, array(
            1   =>  array('Thank you, the severity was added successfully.', Misc::MSG_INFO),
            -1  =>  array('An error occurred while trying to add the severity.', Misc::MSG_ERROR),
            -2  =>  array('Please enter the title for this new severity.', Misc::MSG_ERROR),
    ));
} elseif (@$_POST['cat'] == 'update') {
    $res = Severity::update($_POST['id'], $_POST['title'], $_POST['description'], $_POST['rank']);
    Misc::mapMessages($res, array(
            1   =>  array('Thank you, the severity was added successfully.', Misc::MSG_INFO),
            -1  =>  array('An error occurred while trying to add the severity.', Misc::MSG_ERROR),
            -2  =>  array('Please enter the title for this new severity.', Misc::MSG_ERROR),
    ));
} elseif (@$_POST['cat'] == 'delete') {
    Severity::remove($_POST['items']);
}

if (@$_GET['cat'] == 'edit') {
    $tpl->assign('info', Severity::getDetails($_GET['id']));
} elseif (@$_GET['cat'] == 'change_rank') {
    Severity::changeRank($prj_id, $_GET['id'], $_GET['rank']);
}
$tpl->assign('list', Severity::getList($prj_id));

$tpl->displayTemplate();
