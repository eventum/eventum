<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2015 Eventum Team.                              |
// |                                                                      |
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License as published by |
// | the Free Software Foundation; either version 2 of the License, or    |
// | (at your option) any later version.                                  |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to:                           |
// |                                                                      |
// | Free Software Foundation, Inc.                                       |
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+

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
