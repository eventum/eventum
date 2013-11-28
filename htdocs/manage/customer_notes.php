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
// | Authors: Bryan Alsdorf <bryan@mysql.com>                             |
// +----------------------------------------------------------------------+

require_once dirname(__FILE__) . '/../../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate("manage/customer_notes.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

$role_id = Auth::getCurrentRole();
if ($role_id < User::getRoleID('manager')) {
    Misc::setMessage(ev_gettext('Sorry, you are not allowed to access this page.'), Misc::MSG_ERROR);
    $tpl->displayTemplate();exit;
}

if (@$_POST["cat"] == "new") {
    $res = Customer::insertNote($_POST["project"], $_POST["customer"], $_POST["note"]);
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the note was added successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to add the new note.'), Misc::MSG_ERROR),
    ));
} else if (@$_POST["cat"] == "update") {
    $res = Customer::updateNote($_POST["id"], $_POST["project"], $_POST["customer"], $_POST["note"]);
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the note was updated successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to update the note.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST["cat"] == "delete") {
    $res = Customer::removeNotes($_POST['items']);
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the note was deleted successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to delete the note.'), Misc::MSG_ERROR),
    ));
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

$tpl->displayTemplate();
