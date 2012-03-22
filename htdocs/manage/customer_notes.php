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
// | Authors: Bryan Alsdorf <bryan@mysql.com>                             |
// +----------------------------------------------------------------------+

require_once dirname(__FILE__) . '/../../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate("manage/index.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

$tpl->assign("type", "customer_notes");

$role_id = Auth::getCurrentRole();
if (($role_id == User::getRoleID('administrator')) || ($role_id == User::getRoleID('manager'))) {
    if ($role_id == User::getRoleID('administrator')) {
        $tpl->assign("show_setup_links", true);
    }

    if (@$_POST["cat"] == "new") {
        $tpl->assign("result", Customer::insertNote($_POST["project"], $_POST["customer"], $_POST["note"]));
    } else if (@$_POST["cat"] == "update") {
        $tpl->assign("result", Customer::updateNote($_POST["id"], $_POST["project"], $_POST["customer"], $_POST["note"]));
    } elseif (@$_POST["cat"] == "delete") {
        $tpl->assign("result", Customer::removeNotes($_POST['items']));
    } elseif (!empty($_GET['prj_id'])) {
        $tpl->assign("info", array('cno_prj_id' => $_GET['prj_id']));
        $tpl->assign('customers', Customer::getAssocList($_GET['prj_id']));
    }

    if (@$_GET["cat"] == "edit") {
        $info = Customer::getNoteDetailsByID($_GET["id"]);
        if (!empty($_GET['prj_id'])) {
            $info['cno_prj_id'] = $_GET['prj_id'];
        }
        $tpl->assign('customers', Customer::getAssocList($info['cno_prj_id']));
        $tpl->assign("info", $info);
    }

    $tpl->assign("list", Customer::getNoteList());
    $tpl->assign("project_list", Project::getAll(false));
} else {
    $tpl->assign("show_not_allowed_msg", true);
}

$tpl->displayTemplate();
