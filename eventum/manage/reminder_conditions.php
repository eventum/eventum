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
// @(#) $Id: s.reminder_conditions.php 1.2 04/01/19 15:15:25-00:00 jpradomaia $
//
include_once("../config.inc.php");
include_once(APP_INC_PATH . "db_access.php");
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.user.php");
include_once(APP_INC_PATH . "class.status.php");
include_once(APP_INC_PATH . "class.reminder.php");
include_once(APP_INC_PATH . "class.reminder_action.php");

$tpl = new Template_API();
$tpl->setTemplate("manage/index.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

$tpl->assign("type", "reminder_conditions");

$rem_id = @$HTTP_POST_VARS['rem_id'] ? $HTTP_POST_VARS['rem_id'] : $HTTP_GET_VARS['rem_id'];
$rma_id = @$HTTP_POST_VARS['rma_id'] ? $HTTP_POST_VARS['rma_id'] : $HTTP_GET_VARS['rma_id'];

$role_id = Auth::getCurrentRole();
if (($role_id == User::getRoleID('administrator')) || ($role_id == User::getRoleID('manager'))) {
    if ($role_id == User::getRoleID('administrator')) {
        $tpl->assign("show_setup_links", true);
    }

    if (@$HTTP_POST_VARS["cat"] == "new") {
        $tpl->assign("result", Reminder_Condition::insert());
    } elseif (@$HTTP_POST_VARS["cat"] == "update") {
        $tpl->assign("result", Reminder_Condition::update());
    } elseif (@$HTTP_POST_VARS["cat"] == "delete") {
        Reminder_Condition::remove();
    }

    if (@$HTTP_GET_VARS["cat"] == "edit") {
        $info = Reminder_Condition::getDetails($HTTP_GET_VARS["id"]);
        if (!empty($HTTP_GET_VARS['field'])) {
            $info['rlc_rmf_id'] = $HTTP_GET_VARS['field'];
        } else {
            $HTTP_GET_VARS['field'] = $info['rlc_rmf_id'];
        }
        $tpl->assign("info", $info);
        
    }

    if (!empty($HTTP_GET_VARS['field'])) {
        $field_title = Reminder_Condition::getFieldTitle($HTTP_GET_VARS['field']);
        if (Reminder_Condition::canFieldBeCompared($HTTP_GET_VARS['field'])) {
            $tpl->assign(array(
                'show_field_options'    =>  'yes',
                'comparable_fields'     =>  Reminder_Condition::getFieldAdminList(true)
            ));
        } elseif (strtolower($field_title) == 'status') {
            $prj_id = Reminder::getProjectID($rem_id);
            $tpl->assign(array(
                'show_status_options' => 'yes',
                'statuses'            => Status::getAssocStatusList($prj_id)
            ));
        } elseif (strtolower($field_title) == 'category') {
            $prj_id = Reminder::getProjectID($rem_id);
            $tpl->assign(array(
                'show_category_options' => 'yes',
                'categories'            => Category::getAssocList($prj_id)
            ));
        } else {
            $tpl->assign('show_status_options', 'no');
        }
        if (@$HTTP_GET_VARS["cat"] != "edit") {
            $tpl->assign('info', array(
                'rlc_rmf_id' => $HTTP_GET_VARS['field'],
                'rlc_rmo_id' => '',
                'rlc_value'  => ''
            ));
        }
    }

    $tpl->assign("rem_id", $rem_id);
    $tpl->assign("rma_id", $rma_id);
    $tpl->assign("rem_title", Reminder::getTitle($rem_id));
    $tpl->assign("rma_title", Reminder_Action::getTitle($rma_id));
    $tpl->assign("fields", Reminder_Condition::getFieldAdminList());
    $tpl->assign("operators", Reminder_Condition::getOperatorAdminList());
    $tpl->assign("list", Reminder_Condition::getAdminList($rma_id));
} else {
    $tpl->assign("show_not_allowed_msg", true);
}

$tpl->displayTemplate();
?>