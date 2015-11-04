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

require_once __DIR__ . '/../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate('add_phone_entry.tpl.html');

Auth::checkAuthentication('index.php?err=5', true);

$issue_id = @$_POST['issue_id'] ? $_POST['issue_id'] : $_GET['iss_id'];

if ((!Issue::canAccess($issue_id, Auth::getUserID())) || (Auth::getCurrentRole() <= User::ROLE_CUSTOMER)) {
    $tpl = new Template_Helper();
    $tpl->setTemplate('permission_denied.tpl.html');
    $tpl->displayTemplate();
    exit;
}

if (@$_POST['cat'] == 'add_phone') {
    $res = Phone_Support::insert();
    $tpl->assign('add_phone_result', $res);
}

$prj_id = Issue::getProjectID($issue_id);
$usr_id = Auth::getUserID();

$tpl->assign(array(
    'issue_id'           => $issue_id,
    'phone_categories'   => Phone_Support::getCategoryAssocList($prj_id),
    'current_user_prefs' => Prefs::get($usr_id),
));

$tpl->displayTemplate();
