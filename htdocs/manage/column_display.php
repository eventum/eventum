<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
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

$prj_id = $_REQUEST['prj_id'];

$tpl = new Template_Helper();
$tpl->setTemplate("manage/index.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

$tpl->assign("type", "column_display");

$role_id = Auth::getCurrentRole();
if (($role_id == User::getRoleID('administrator')) || ($role_id == User::getRoleID('manager'))) {
    if ($role_id == User::getRoleID('administrator')) {
        $tpl->assign("show_setup_links", true);
    }

    if (@$_POST["cat"] == "save") {
        $tpl->assign("result", Display_Column::save());
    }

    $page = 'list_issues';
    $available = Display_Column::getAllColumns($page);
    $selected = Display_Column::getSelectedColumns($prj_id, $page);

    // re-order available array to match rank
    $available_ordered = array();
    foreach ($selected as $field_name => $field_info) {
        $available_ordered[$field_name] = $available[$field_name];
        unset($available[$field_name]);
    }
    if (count($available) > 0) {
        $available_ordered += $available;
    }

    $excluded_roles = array();
    if (!Customer::hasCustomerIntegration($prj_id)) {
        $excluded_roles[] = "customer";
    }
    $user_roles = User::getRoles($excluded_roles);
    $user_roles[9] = "Never Display";

    // generate ranks
    $ranks = array();
    for ($i = 1; $i <= count($available_ordered); $i++) {
        $ranks[$i] = $i;
    }

    $tpl->assign(array(
        "available" =>  $available_ordered,
        "selected"  =>  $selected,
        "user_roles"=>  $user_roles,
        "page"      =>  $page,
        "ranks"     =>  $ranks,
        "prj_id"    =>  $prj_id,
        "project_name"  =>  Project::getName($prj_id)
    ));
} else {
    $tpl->assign("show_not_allowed_msg", true);
}

$tpl->displayTemplate();
