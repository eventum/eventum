<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2015 Eventum Team.                              |
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
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+

require_once __DIR__ . '/../../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate('manage/time_tracking.tpl.html');

Auth::checkAuthentication();

$role_id = Auth::getCurrentRole();
if ($role_id < User::ROLE_MANAGER) {
    Misc::setMessage(ev_gettext('Sorry, you are not allowed to access this page.'), Misc::MSG_ERROR);
    $tpl->displayTemplate();
    exit;
}

$prj_id = isset($_POST['prj_id']) ? (int) $_POST['prj_id'] : (int) $_GET['prj_id'];
$cat = isset($_POST['cat']) ? (string) $_POST['cat'] : null;

$tpl->assign('project', Project::getDetails($prj_id));

if ($cat == 'new') {
    $title = $_POST['title'];
    $res = Time_Tracking::insertCategory($prj_id, $title);
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the time tracking category was added successfully.'), Misc::MSG_INFO),
            -1   =>  array(ev_gettext('An error occurred while trying to add the new time tracking category.'), Misc::MSG_INFO),
            -2  =>  array(ev_gettext('Please enter the title for this new time tracking category.'), Misc::MSG_ERROR),
    ));
} elseif ($cat == 'update') {
    $title = (string) $_POST['title'];
    $prj_id = (int) $_POST['prj_id'];
    $id = (int) $_POST['id'];
    $res = Time_Tracking::updateCategory($prj_id, $id, $title);
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the time tracking category was updated successfully.'), Misc::MSG_INFO),
            -1   =>  array(ev_gettext('An error occurred while trying to update the time tracking category information.'), Misc::MSG_INFO),
            -2  =>  array(ev_gettext('Please enter the title for this time tracking category.'), Misc::MSG_ERROR),
    ));
} elseif ($cat == 'delete') {
    $items = (array) $_POST['items'];
    Time_Tracking::removeCategory($items);
}

if ($cat == 'edit') {
    $tpl->assign('info', Time_Tracking::getCategoryDetails($_GET['id']));
}

$tpl->assign('list', Time_Tracking::getCategoryList($prj_id));
$tpl->displayTemplate();
