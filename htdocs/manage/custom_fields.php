<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2013 Eventum Team.                              |
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
$tpl->setTemplate("manage/custom_fields.tpl.html");

Auth::checkAuthentication(APP_COOKIE);


$role_id = Auth::getCurrentRole();
if ($role_id < User::getRoleID('administrator')) {
    Misc::setMessage("Sorry, you are not allowed to access this page.", Misc::MSG_ERROR);
    $tpl->displayTemplate();exit;
}
$tpl->assign("project_list", Project::getAll());

if (@$_POST["cat"] == "new") {
    $res = Custom_Field::insert();
    Misc::mapMessages($res, array(
            1   =>  array('Thank you, the custom field was added successfully.', Misc::MSG_INFO),
            -1  =>  array("An error occurred while trying to add the new custom field.", Misc::MSG_ERROR),
    ));
} elseif (@$_POST["cat"] == "update") {
    $res = Custom_Field::update();
    Misc::mapMessages($res, array(
            1   =>  array('Thank you, the custom field was updated successfully.', Misc::MSG_INFO),
            -1  =>  array("An error occurred while trying to update the custom field information.", Misc::MSG_ERROR),
    ));
} elseif (@$_POST["cat"] == "delete") {
    $res = Custom_Field::remove();
    Misc::mapMessages($res, array(
            true   =>  array('Thank you, the custom field was removed successfully.', Misc::MSG_INFO),
            false  =>  array("An error occurred while trying to remove the custom field information.", Misc::MSG_ERROR),
    ));
}elseif (@$_REQUEST["cat"] == "change_rank") {
    Custom_Field::changeRank();
}

if (@$_GET["cat"] == "edit") {
    $tpl->assign("info", Custom_Field::getDetails($_GET["id"]));
}

$excluded_roles = array();
if (!Customer::hasCustomerIntegration(Auth::getCurrentProject())) {
    $excluded_roles[] = "customer";
}
$user_roles = User::getRoles($excluded_roles);
$user_roles[9] = "Never Display";

$tpl->assign("list", Custom_Field::getList());
$tpl->assign("project_list", Project::getAll());
$tpl->assign("user_roles", $user_roles);
$tpl->assign("backend_list", Custom_Field::getBackendList());

$tpl->displayTemplate();
