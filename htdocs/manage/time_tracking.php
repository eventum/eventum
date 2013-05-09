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
$tpl->setTemplate("manage/index.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

$tpl->assign("type", "time_tracking");

$role_id = Auth::getCurrentRole();
if (($role_id == User::getRoleID('administrator')) || ($role_id == User::getRoleID('manager'))) {
    if ($role_id == User::getRoleID('administrator')) {
        $tpl->assign("show_setup_links", true);
    }

    @$prj_id = $_POST["prj_id"] ? $_POST["prj_id"] : $_GET["prj_id"];
    $tpl->assign("project", Project::getDetails($prj_id));

    if (@$_POST["cat"] == "new") {
        $prj_id = $_POST["prj_id"];
        $title = $_POST["title"];
        $tpl->assign("result", Time_Tracking::insert($prj_id, $title));
    } elseif (@$_POST["cat"] == "update") {
        $tpl->assign("result", Time_Tracking::update());
    } elseif (@$_POST["cat"] == "delete") {
        $tpl->assign("result", Time_Tracking::remove());
    }

    if (@$_GET["cat"] == "edit") {
        $tpl->assign("info", Time_Tracking::getDetails($_GET["id"]));
    }

    $tpl->assign("list", Time_Tracking::getList($prj_id));
} else {
    $tpl->assign("show_not_allowed_msg", true);
}

$tpl->displayTemplate();
