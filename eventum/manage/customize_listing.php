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
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.user.php");
include_once(APP_INC_PATH . "class.project.php");
include_once(APP_INC_PATH . "class.status.php");
include_once(APP_INC_PATH . "db_access.php");

$tpl = new Template_API();
$tpl->setTemplate("manage/index.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

$tpl->assign("type", "customize_listing");

$role_id = Auth::getCurrentRole();
if ($role_id == User::getRoleID('administrator')) {
    $tpl->assign("show_setup_links", true);

    if (@$HTTP_POST_VARS["cat"] == "new") {
        $tpl->assign("result", Status::insertCustomization($HTTP_POST_VARS['project'], $HTTP_POST_VARS['status'], $HTTP_POST_VARS['date_field'], $HTTP_POST_VARS['label']));
    } elseif (@$HTTP_POST_VARS["cat"] == "update") {
        $tpl->assign("result", Status::updateCustomization($HTTP_POST_VARS['id'], $HTTP_POST_VARS['project'], $HTTP_POST_VARS['status'], $HTTP_POST_VARS['date_field'], $HTTP_POST_VARS['label']));
    } elseif (@$HTTP_POST_VARS["cat"] == "delete") {
        Status::removeCustomization($HTTP_POST_VARS['items']);
    }

    if (@$HTTP_GET_VARS["cat"] == "edit") {
        $details = Status::getCustomizationDetails($HTTP_GET_VARS["id"]);
        $tpl->assign(array(
            "info"        => $details,
            'project_id'  => $details['psd_prj_id'],
            'status_list' => Status::getAssocStatusList($details['psd_prj_id'], TRUE)
        ));
    }

    $display_customer_fields = false;
    @$prj_id = $HTTP_POST_VARS["prj_id"] ? $HTTP_POST_VARS["prj_id"] : $HTTP_GET_VARS["prj_id"];
    if (!empty($prj_id)) {
        $tpl->assign("status_list", Status::getAssocStatusList($prj_id, TRUE));
        $tpl->assign('project_id', $prj_id);
        $display_customer_fields = Customer::hasCustomerIntegration($prj_id);
    }

    $tpl->assign("date_fields", Issue::getDateFieldsAssocList($display_customer_fields));
    $tpl->assign("project_list", Project::getAll());
    $tpl->assign("list", Status::getCustomizationList());
} else {
    $tpl->assign("show_not_allowed_msg", true);
}

$tpl->displayTemplate();
?>