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
// @(#) $Id$
//
include_once("../config.inc.php");
include_once(APP_INC_PATH . "db_access.php");
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.user.php");

$tpl = new Template_API();
$tpl->setTemplate("manage/index.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

$tpl->assign("type", "account_managers");

$role_id = Auth::getCurrentRole();
if (($role_id == User::getRoleID('administrator')) || ($role_id == User::getRoleID('manager'))) {
    if ($role_id == User::getRoleID('administrator')) {
        $tpl->assign("show_setup_links", true);
    }

    if (@$HTTP_POST_VARS["cat"] == "new") {
        $tpl->assign("result", Customer::insertAccountManager());
    } elseif (@$HTTP_POST_VARS["cat"] == "update") {
        $tpl->assign("result", Customer::updateAccountManager());
    } elseif (@$HTTP_POST_VARS["cat"] == "delete") {
        Customer::removeAccountManager();
    } elseif (!empty($HTTP_GET_VARS['prj_id'])) {
        $tpl->assign("info", array('cam_prj_id' => $HTTP_GET_VARS['prj_id']));
        $tpl->assign('customers', Customer::getAssocList($HTTP_GET_VARS['prj_id']));
    }

    if (@$HTTP_GET_VARS["cat"] == "edit") {
        $info = Customer::getAccountManagerDetails($HTTP_GET_VARS["id"]);
        if (!empty($HTTP_GET_VARS['prj_id'])) {
            $info['cam_prj_id'] = $HTTP_GET_VARS['prj_id'];
        }
        $tpl->assign('customers', Customer::getAssocList($info['cam_prj_id']));
        $tpl->assign("info", $info);
    }

    $tpl->assign("list", Customer::getAccountManagerList());
    if (!empty($_REQUEST['prj_id'])) {
        $tpl->assign("user_options", User::getActiveAssocList($_REQUEST['prj_id'], User::getRoleID('Customer')));
    }
    $tpl->assign("project_list", Project::getAll(false));
} else {
    $tpl->assign("show_not_allowed_msg", true);
}

$tpl->displayTemplate();
?>