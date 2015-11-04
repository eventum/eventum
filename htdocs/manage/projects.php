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
$tpl->setTemplate('manage/projects.tpl.html');

Auth::checkAuthentication();

$role_id = Auth::getCurrentRole();
if ($role_id < User::ROLE_MANAGER) {
    Misc::setMessage(ev_gettext('Sorry, you are not allowed to access this page.'), Misc::MSG_ERROR);
    $tpl->displayTemplate();
    exit;
}

if (@$_POST['cat'] == 'new') {
    Misc::mapMessages(Project::insert(), array(
            1   =>  array(ev_gettext('Thank you, the project was added successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to add the new project.'), Misc::MSG_ERROR),
            -2  =>  array(ev_gettext('Please enter the title for this new project.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST['cat'] == 'update') {
    Misc::mapMessages(Project::update(), array(
            1   =>  array(ev_gettext('Thank you, the project was updated successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to update the project information.'), Misc::MSG_ERROR),
            -2  =>  array(ev_gettext('Please enter the title for this project.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST['cat'] == 'delete') {
    Misc::mapMessages(Project::remove(), array(
            1   =>  array(ev_gettext('Thank you, the project was deleted successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to delete the project.'), Misc::MSG_ERROR),
    ));
}
$tpl->assign('active_projects', Project::getAssocList(Auth::getUserID(), true));

if (@$_GET['cat'] == 'edit') {
    $tpl->assign('info', Project::getDetails($_GET['id']));
}

$tpl->assign('list', Project::getList());
$tpl->assign('user_options', User::getActiveAssocList());
$tpl->assign('status_options', Status::getAssocList());
$tpl->assign('customer_backends', CRM::getBackendList());
$tpl->assign('workflow_backends', Workflow::getBackendList());

$tpl->displayTemplate();
