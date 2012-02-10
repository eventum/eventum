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

$tpl = new Template_Helper();
$tpl->setTemplate("manage/reminder_actions.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

$rem_id = @$_POST['rem_id'] ? $_POST['rem_id'] : $_GET['rem_id'];

$role_id = Auth::getCurrentRole();
if ($role_id < User::getRoleID('manager')) {
    Misc::setMessage("Sorry, you are not allowed to access this page.", Misc::MSG_ERROR);
    $tpl->displayTemplate();exit;
}

if (@$_POST["cat"] == "new") {
    $res = Reminder_Action::insert();
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the action was added successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to add the new action.'), Misc::MSG_ERROR),
            -2  =>  array(ev_gettext('Please enter the title for this new action.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST["cat"] == "update") {
    $res = Reminder_Action::update();
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the action was updated successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to update the action.'), Misc::MSG_ERROR),
            -2  =>  array(ev_gettext('Please enter the title for this action.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST["cat"] == "delete") {
    @Reminder_Action::remove($_POST['items']);
}

if (@$_GET["cat"] == "edit") {
    $tpl->assign("info", Reminder_Action::getDetails($_GET["id"]));
} elseif (@$_GET["cat"] == "change_rank") {
    Reminder_Action::changeRank($_GET['rem_id'], $_GET['id'], $_GET['rank']);
}

$tpl->assign("rem_id", $rem_id);
$tpl->assign("rem_title", Reminder::getTitle($rem_id));
$tpl->assign("action_types", Reminder_Action::getActionTypeList());
$tpl->assign("list", Reminder_Action::getAdminList($rem_id));
$tpl->assign("user_options", User::getActiveAssocList(Reminder::getProjectID($rem_id), User::getRoleID('Customer')));

$tpl->displayTemplate();
