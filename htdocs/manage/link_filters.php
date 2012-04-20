<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2012 Eventum Team.                              |
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
$tpl->setTemplate("manage/link_filters.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

$role_id = Auth::getCurrentRole();
if ($role_id < User::getRoleID('manager')) {
    Misc::setMessage("Sorry, you are not allowed to access this page.", Misc::MSG_ERROR);
    $tpl->displayTemplate();exit;
}

if (@$_POST["cat"] == "new") {
    $res = Link_Filter::insert();
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the link filter was added successfully.'), Misc::MSG_INFO),
            -1   =>  array(ev_gettext('An error occurred while trying to add the new link filter.'), Misc::MSG_INFO),
    ));
} elseif (@$_POST["cat"] == "update") {
    $res = Link_Filter::update();
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the link filter was updated successfully.'), Misc::MSG_INFO),
            -1   =>  array(ev_gettext('An error occurred while trying to update the link filter.'), Misc::MSG_INFO),
    ));
} elseif (@$_POST["cat"] == "delete") {
    $res = Link_Filter::remove();
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the link filter was deleted successfully.'), Misc::MSG_INFO),
            -1   =>  array(ev_gettext('An error occurred while trying to delete the link filter.'), Misc::MSG_INFO),
    ));
}

if (@$_GET["cat"] == "edit") {
    $info = Link_Filter::getDetails($_GET["id"]);
    $tpl->assign("info", $info);
}

$user_roles = User::getRoles();

$tpl->assign("list", Link_Filter::getList());
$tpl->assign("project_list", Project::getAll());
$tpl->assign("user_roles", $user_roles);

$tpl->displayTemplate();
