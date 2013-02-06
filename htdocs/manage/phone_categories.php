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
$tpl->setTemplate("manage/phone_categories.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

$role_id = Auth::getCurrentRole();
if ($role_id < User::getRoleID('manager')) {
    Misc::setMessage("Sorry, you are not allowed to access this page.", Misc::MSG_ERROR);
    $tpl->displayTemplate();exit;
}

@$prj_id = $_POST["prj_id"] ? $_POST["prj_id"] : $_GET["prj_id"];
$tpl->assign("project", Project::getDetails($prj_id));

if (@$_POST["cat"] == "new") {
    $res = Phone_Support::insertCategory();
    $tpl->assign("result", $res);
    Misc::mapMessages($res, array(
            1   =>  array('Thank you, the phone category was added successfully.', Misc::MSG_INFO),
            -1  =>  array('An error occurred while trying to add the phone category.', Misc::MSG_ERROR),
            -2  =>  array('Please enter the title for this new phone category.', Misc::MSG_ERROR),
    ));
} elseif (@$_POST["cat"] == "update") {
    $res = Phone_Support::updateCategory();
    $tpl->assign("result", $res);
    Misc::mapMessages($res, array(
            1   =>  array('Thank you, the phone category was updated successfully.', Misc::MSG_INFO),
            -1  =>  array('An error occurred while trying to uodate the phone category.', Misc::MSG_ERROR),
            -2  =>  array('Please enter the title for this phone category.', Misc::MSG_ERROR),
    ));
} elseif (@$_POST["cat"] == "delete") {
    Phone_Support::removeCategory();
}

if (@$_GET["cat"] == "edit") {
    $tpl->assign("info", Phone_Support::getCategoryDetails($_GET["id"]));
}
$tpl->assign("list", Phone_Support::getCategoryList($prj_id));

$tpl->displayTemplate();
