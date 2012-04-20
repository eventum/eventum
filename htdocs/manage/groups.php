<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2012 Eventum Team.                              |
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
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// +----------------------------------------------------------------------+

require_once dirname(__FILE__) . '/../../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate("manage/groups.tpl.html");

Auth::checkAuthentication(APP_COOKIE);


$role_id = Auth::getCurrentRole();
if ($role_id < User::getRoleID('manager')) {
    Misc::setMessage("Sorry, you are not allowed to access this page.", Misc::MSG_ERROR);
    $tpl->displayTemplate();exit;
}

if (@$_POST["cat"] == "new") {
    $res = Group::insert();
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the group was added successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to add the new group.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST["cat"] == "update") {
    $res = Group::update();
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the group was updated successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to update the group.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST["cat"] == "delete") {
    Group::remove();
}

if (@$_GET["cat"] == "edit") {
    $info = Group::getDetails($_GET["id"]);
    $tpl->assign("info", $info);
    $user_options = User::getActiveAssocList(Auth::getCurrentProject(), User::getRoleID('customer'), false, $_GET["id"]);
} else {
    $user_options = User::getActiveAssocList(Auth::getCurrentProject(), User::getRoleID('customer'), true);
}

if (@$_GET['show_customers'] == 1) {
    $show_customer = true;
} else {
    $show_customer = false;
}


$tpl->assign("user_options", $user_options);
$tpl->assign("list", Group::getList());
$tpl->assign("project_list", Project::getAll());

$tpl->displayTemplate();
