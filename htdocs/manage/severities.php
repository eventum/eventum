<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2010 Bryan Alsdorf                                     |
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
// | Authors: Bryan Alsdorf <balsdorf@gmail.com>                          |
// +----------------------------------------------------------------------+

require_once dirname(__FILE__) . '/../../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate("manage/severities.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

$role_id = Auth::getCurrentRole();
if ($role_id < User::getRoleID('manager')) {
    Misc::setMessage("Sorry, you are not allowed to access this page.", Misc::MSG_ERROR);
    $tpl->displayTemplate();exit;
}

@$prj_id = $_POST["prj_id"] ? $_POST["prj_id"] : $_GET["prj_id"];
$tpl->assign("project", Project::getDetails($prj_id));

if (@$_POST["cat"] == "new") {
    $res = Severity::insert($prj_id, $_POST['title'], $_POST['description'], $_POST['rank']);
    Misc::mapMessages($res, array(
            1   =>  array('Thank you, the severity was added successfully.', Misc::MSG_INFO),
            -1  =>  array('An error occurred while trying to add the severity.', Misc::MSG_ERROR),
            -2  =>  array('Please enter the title for this new severity.', Misc::MSG_ERROR),
    ));
} elseif (@$_POST["cat"] == "update") {
    $res = Severity::update($_POST['id'], $_POST['title'], $_POST['description'], $_POST['rank']);
    Misc::mapMessages($res, array(
            1   =>  array('Thank you, the severity was added successfully.', Misc::MSG_INFO),
            -1  =>  array('An error occurred while trying to add the severity.', Misc::MSG_ERROR),
            -2  =>  array('Please enter the title for this new severity.', Misc::MSG_ERROR),
    ));
} elseif (@$_POST["cat"] == "delete") {
    Severity::remove($_POST['items']);
}

if (@$_GET["cat"] == "edit") {
    $tpl->assign("info", Severity::getDetails($_GET["id"]));
} elseif (@$_GET["cat"] == "change_rank") {
    Severity::changeRank($prj_id, $_GET['id'], $_GET['rank']);
}
$tpl->assign("list", Severity::getList($prj_id));

$tpl->displayTemplate();
