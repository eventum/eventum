<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005 MySQL AB                              |
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
// @(#) $Id: s.users.php 1.15 03/11/19 18:53:39-00:00 jpradomaia $
//
include_once("../config.inc.php");
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.user.php");
include_once(APP_INC_PATH . "class.project.php");
include_once(APP_INC_PATH . "db_access.php");

$tpl = new Template_API();
$tpl->setTemplate("manage/index.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

$tpl->assign("type", "users");

$role_id = Auth::getCurrentRole();
if (($role_id == User::getRoleID('administrator')) || ($role_id == User::getRoleID('manager'))) {
    if ($role_id == User::getRoleID('administrator')) {
        $tpl->assign("show_setup_links", true);
        $excluded_roles = array('customer');
    } else {
        $excluded_roles = array('customer', 'administrator');
    }

    if (@$HTTP_POST_VARS["cat"] == "new") {
        $tpl->assign("result", User::insert());
    } elseif (@$HTTP_POST_VARS["cat"] == "update") {
        $tpl->assign("result", User::update());
    } elseif (@$HTTP_POST_VARS["cat"] == "change_status") {
        User::changeStatus();
    }

    $project_roles = array();
    $project_list = Project::getAll();
    if (@$HTTP_GET_VARS["cat"] == "edit") {
        $info = User::getDetails($HTTP_GET_VARS["id"]);
        $tpl->assign("info", $info);
    }
    foreach ($project_list as $prj_id => $prj_title) {
        if (@$info['roles'][$prj_id]['pru_role'] == User::getRoleID('Customer')) {
            if (count($excluded_roles) == 1) {
                $excluded_roles = false;
            } else {
                $excluded_roles = array('administrator');
            }
        }
        if (@$info['roles'][$prj_id]['pru_role'] == User::getRoleID("administrator")) {
            $excluded_roles = false;
        }
        $project_roles[$prj_id] = $user_roles = array(0 => "No Access") + User::getRoles($excluded_roles);
    }
    if (@$HTTP_GET_VARS['show_customers'] == 1) {
        $show_customer = true;
    } else {
        $show_customer = false;
    }
    $tpl->assign("list", User::getList($show_customer));
    $tpl->assign("project_list", $project_list);
    $tpl->assign("project_roles", $project_roles);
} else {
    $tpl->assign("show_not_allowed_msg", true);
}

$tpl->displayTemplate();
?>