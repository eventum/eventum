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
$tpl->setTemplate('manage/anonymous.tpl.html');

Auth::checkAuthentication();

@$prj_id = $_POST['prj_id'] ? $_POST['prj_id'] : $_GET['prj_id'];

$role_id = Auth::getCurrentRole();
if ($role_id < User::ROLE_MANAGER) {
    Misc::setMessage(ev_gettext('Sorry, you are not allowed to access this page.'), Misc::MSG_ERROR);
    $tpl->displayTemplate();
    exit;
}

if (@$_POST['cat'] == 'update') {
    $res = Project::updateAnonymousPost($prj_id);
    $tpl->assign('result', $res);
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the information was updated successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to update the information.'), Misc::MSG_ERROR),
    ));
}
// load the form fields
$tpl->assign('project', Project::getDetails($prj_id));
$tpl->assign('cats', Category::getAssocList($prj_id));
$tpl->assign('priorities', Priority::getList($prj_id));
$tpl->assign('users', Project::getUserAssocList($prj_id, 'active'));
$tpl->assign('options', Project::getAnonymousPostOptions($prj_id));
$tpl->assign('prj_id', $prj_id);

$tpl->displayTemplate();
