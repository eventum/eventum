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
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// +----------------------------------------------------------------------+

require_once __DIR__ . '/../../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate('reports/stalled_issues.tpl.html');

Auth::checkAuthentication();

if (!Access::canAccessReports(Auth::getUserID())) {
    echo 'Invalid role';
    exit;
}

$prj_id = Auth::getCurrentProject();

if (count(@$_REQUEST['before']) < 1) {
    $before = date('Y-m-d', (time() - Date_Helper::MONTH));
} else {
    $before = implode('-', $_REQUEST['before']);
}
if (count(@$_REQUEST['after']) < 1) {
    $after = date('Y-m-d', (time() - Date_Helper::YEAR));
} else {
    $after = implode('-', $_REQUEST['after']);
}
if (empty($_REQUEST['sort_order'])) {
    $_REQUEST['sort_order'] = 'ASC';
}

$data = Report::getStalledIssuesByUser($prj_id, @$_REQUEST['developers'], @$_REQUEST['status'], $before, $after, $_REQUEST['sort_order']);

$groups = Group::getAssocList($prj_id);
$assign_options = array();
if ((count($groups) > 0) && (Auth::getCurrentRole() > User::ROLE_CUSTOMER)) {
    foreach ($groups as $grp_id => $grp_name) {
        $assign_options["grp:$grp_id"] = 'Group: ' . $grp_name;
    }
}
$assign_options += Project::getUserAssocList($prj_id, 'active', User::ROLE_USER);

$tpl->assign(array(
    'users' =>  $assign_options,
    'before_date'   =>  $before,
    'after_date'   =>  $after,
    'data'  =>  $data,
    'developers'    => @$_REQUEST['developers'],
    'status_list'   =>  Status::getAssocStatusList($prj_id),
    'status'        =>  @$_REQUEST['status'],
    'sort_order'    =>  $_REQUEST['sort_order'],
));

$tpl->displayTemplate();
