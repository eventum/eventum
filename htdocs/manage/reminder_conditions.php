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
$tpl->setTemplate("manage/reminder_conditions.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

$rem_id = @$_POST['rem_id'] ? $_POST['rem_id'] : $_GET['rem_id'];
$rma_id = @$_POST['rma_id'] ? $_POST['rma_id'] : $_GET['rma_id'];

$role_id = Auth::getCurrentRole();
if ($role_id < User::getRoleID('manager')) {
    Misc::setMessage("Sorry, you are not allowed to access this page.", Misc::MSG_ERROR);
    $tpl->displayTemplate();exit;
}

if (@$_POST["cat"] == "new") {
    $res = Reminder_Condition::insert();
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the condition was added successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to add the new condition.'), Misc::MSG_ERROR),
            -2  =>  array(ev_gettext('Please enter the title for this new condition.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST["cat"] == "update") {
    $res = Reminder_Condition::update();
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the condition was updated successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to update the condition.'), Misc::MSG_ERROR),
            -2  =>  array(ev_gettext('Please enter the title for this condition.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST["cat"] == "delete") {
    Reminder_Condition::remove();
}

if (@$_GET["cat"] == "edit") {
    $info = Reminder_Condition::getDetails($_GET["id"]);
    if (!empty($_GET['field'])) {
        $info['rlc_rmf_id'] = $_GET['field'];
    } else {
        $_GET['field'] = $info['rlc_rmf_id'];
    }
    $tpl->assign("info", $info);

}

if (!empty($_GET['field'])) {
    $field_title = Reminder_Condition::getFieldTitle($_GET['field']);
    if (Reminder_Condition::canFieldBeCompared($_GET['field'])) {
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
    } elseif ((strtolower($field_title) == 'group') || (strtolower($field_title) == 'active group')) {
            $prj_id = Reminder::getProjectID($rem_id);
            $tpl->assign(array(
                'show_group_options' => 'yes',
                'groups'             => Group::getAssocList($prj_id)
            ));
    } else {
        $tpl->assign('show_status_options', 'no');
    }
    if (@$_GET["cat"] != "edit") {
        $tpl->assign('info', array(
            'rlc_rmf_id' => $_GET['field'],
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

$tpl->displayTemplate();
