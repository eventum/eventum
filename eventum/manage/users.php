<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005, 2006, 2007 MySQL AB                              |
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
// @(#) $Id: users.php 3189 2007-01-11 21:57:57Z glen $
//
require_once("../config.inc.php");
require_once(APP_INC_PATH . "class.template.php");
require_once(APP_INC_PATH . "class.auth.php");
require_once(APP_INC_PATH . "class.user.php");
require_once(APP_INC_PATH . "class.project.php");
require_once(APP_INC_PATH . "db_access.php");

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

    if (@$_POST["cat"] == "new") {
        $tpl->assign("result", User::insert());
    } elseif (@$_POST["cat"] == "update") {
        $tpl->assign("result", User::update());
    } elseif (@$_POST["cat"] == "change_status") {
        User::changeStatus();
    }

    $project_roles = array();
    $project_list = Project::getAll();
    if (@$_GET["cat"] == "edit") {
        $info = User::getDetails($_GET["id"]);
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
    if (@$_GET['show_customers'] == 1) {
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
