<?php

/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

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
