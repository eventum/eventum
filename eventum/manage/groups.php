<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004 MySQL AB                                    |
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
//
// @(#) $Id$
//
include_once("../config.inc.php");
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.user.php");
include_once(APP_INC_PATH . "class.group.php");
include_once(APP_INC_PATH . "class.project.php");
include_once(APP_INC_PATH . "db_access.php");

$tpl = new Template_API();
$tpl->setTemplate("manage/index.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

$tpl->assign("type", "groups");

$role_id = Auth::getCurrentRole();
if (($role_id == User::getRoleID('administrator')) || ($role_id == User::getRoleID('manager'))) {
    if ($role_id == User::getRoleID('administrator')) {
        $tpl->assign("show_setup_links", true);
    }

    if (@$HTTP_POST_VARS["cat"] == "new") {
        $tpl->assign("result", Group::insert());
    } elseif (@$HTTP_POST_VARS["cat"] == "update") {
        $tpl->assign("result", Group::update());
    } elseif (@$HTTP_POST_VARS["cat"] == "delete") {
        Group::remove();
    }

    if (@$HTTP_GET_VARS["cat"] == "edit") {
        $info = Group::getDetails($HTTP_GET_VARS["id"]);
        $tpl->assign("info", $info);
        $user_options = User::getActiveAssocList(Auth::getCurrentProject(), User::getRoleID('customer'), false, $HTTP_GET_VARS["id"]);
    } else {
        $user_options = User::getActiveAssocList(Auth::getCurrentProject(), User::getRoleID('customer'), true);
    }

    if (@$HTTP_GET_VARS['show_customers'] == 1) {
        $show_customer = true;
    } else {
        $show_customer = false;
    }
    
    
    $tpl->assign("user_options", $user_options);
    $tpl->assign("list", Group::getList());
    $tpl->assign("project_list", Project::getAll());
} else {
    $tpl->assign("show_not_allowed_msg", true);
}

$tpl->displayTemplate();
?>