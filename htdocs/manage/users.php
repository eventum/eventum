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
$tpl->setTemplate('manage/users.tpl.html');

Auth::checkAuthentication();

$role_id = Auth::getCurrentRole();

if ($role_id < User::ROLE_MANAGER) {
    Misc::setMessage(ev_gettext('Sorry, you are not allowed to access this page.'), Misc::MSG_ERROR);
    $tpl->displayTemplate();
    exit;
}

if (@$_POST['cat'] == 'new') {
    $res = User::insertFromPost();
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the user was added successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to add the new user.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST['cat'] == 'update') {
    $res = User::updateFromPost();
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the user was updated successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to update the user information.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST['cat'] == 'change_status') {
    User::changeStatus($_POST['items'], $_POST['status']);
}

$project_roles = array();
$project_list = Project::getAll();
if (@$_GET['cat'] == 'edit') {
    $info = User::getDetails($_GET['id']);
    $tpl->assign('info', $info);
}
foreach ($project_list as $prj_id => $prj_title) {
    $excluded_roles = array('Customer');
    if (@$info['roles'][$prj_id]['pru_role'] == User::ROLE_CUSTOMER) {
        if (count($excluded_roles) == 1) {
            $excluded_roles = false;
        } else {
            $excluded_roles = array('administrator');
        }
        if (@$info['roles'][$prj_id]['pru_role'] == User::ROLE_REPORTER) {
            $excluded_roles = false;
        }
    }
    $project_roles[$prj_id] = $user_roles = array(0 => 'No Access') + User::getRoles($excluded_roles);
}

$show_customer = !empty($_GET['show_customers']);
$show_inactive = !empty($_GET['show_inactive']);
$tpl->assign('list', User::getList($show_customer, $show_inactive));
$tpl->assign('project_list', $project_list);
$tpl->assign('project_roles', $project_roles);
$tpl->assign('group_list', Group::getAssocListAllProjects());
$tpl->assign('partners', Partner::getAssocList());

$tpl->displayTemplate();
