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
$tpl->setTemplate('reports/workload_date_range.tpl.html');

Auth::checkAuthentication();

if (!Access::canAccessReports(Auth::getUserID())) {
    echo 'Invalid role';
    exit;
}

$prj_id = Auth::getCurrentProject();

$types = array(
    'individual'    =>  'Individual',
    'aggregate'     =>  'Aggregate',
);

if (count(@$_REQUEST['start']) > 0 &&
        (@$_REQUEST['start']['Year'] != 0) &&
        (@$_REQUEST['start']['Month'] != 0) &&
        (@$_REQUEST['start']['Day'] != 0)) {
    $start_date = implode('-', $_REQUEST['start']);
} else {
    // if empty start date, set to be a month ago
    $start_date = date('Y-m-d', time() - Date_Helper::MONTH);
}
if (count(@$_REQUEST['end']) > 0 &&
        (@$_REQUEST['end']['Year'] != 0) &&
        (@$_REQUEST['end']['Month'] != 0) &&
        (@$_REQUEST['end']['Day'] != 0)) {
    $end_date = implode('-', $_REQUEST['end']);
} else {
    $end_date = date('Y-m-d');
}

if (!empty($_REQUEST['interval'])) {
    $data = Report::getWorkloadByDateRange($_REQUEST['interval'], $_REQUEST['type'], $start_date, date('Y-m-d', (strtotime($end_date) + Date_Helper::DAY)), @$_REQUEST['category']);
    Session::set('workload_date_range_data', $data);
    $tpl->assign('data', $data);
  //  echo "<pre>";print_r($data);echo "</pre>";
}

$tpl->assign(array(
    'interval'  =>  @$_REQUEST['interval'],
    'types' =>  $types,
    'type'  =>  @$_REQUEST['type'],
    'start_date'    =>  $start_date,
    'end_date'  =>  $end_date,
    'categories'    =>  Category::getAssocList($prj_id),
    'category'  =>  @$_REQUEST['category'],
));
$tpl->displayTemplate();
