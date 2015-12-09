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
$tpl->setTemplate('reports/weekly.tpl.html');

Auth::checkAuthentication();

if (!Access::canAccessReports(Auth::getUserID())) {
    echo 'Invalid role';
    exit;
}

$prj_id = Auth::getCurrentProject();

if (count(@$_REQUEST['start']) > 0 &&
        (@$_REQUEST['start']['Year'] != 0) &&
        (@$_REQUEST['start']['Month'] != 0) &&
        (@$_REQUEST['start']['Day'] != 0)) {
    $start_date = implode('-', $_REQUEST['start']);
} elseif (!empty($_GET['start_date'])) {
    $start_date = $_GET['start_date'];
}

if (count(@$_REQUEST['end']) > 0 &&
        (@$_REQUEST['end']['Year'] != 0) &&
        (@$_REQUEST['end']['Month'] != 0) &&
        (@$_REQUEST['end']['Day'] != 0)) {
    $end_date = implode('-', $_REQUEST['end']);
} elseif (!empty($_GET['end_date'])) {
    $end_date = $_GET['end_date'];
}

$tpl->assign(array(
    'weeks' => Date_Helper::getWeekOptions(3, 0),
    'users' => Project::getUserAssocList($prj_id, 'active', User::ROLE_CUSTOMER),
    'start_date'    =>  @$start_date,
    'end_date'      =>  @$end_date,
    'report_type'   =>  @$_REQUEST['report_type'],
));

if (!empty($_REQUEST['developer'])) {

    //split date up
    if (@$_REQUEST['report_type'] == 'weekly') {
        $dates = explode('_', $_REQUEST['week']);
    } else {
        $dates = array($start_date, $end_date);
    }

    // print out emails
    $developer = $_REQUEST['developer'];
    $prj_id = Auth::getCurrentProject();
    $options = array(
        'separate_closed' => @$_REQUEST['separate_closed'],
        'separate_not_assigned_to_user' => @$_REQUEST['separate_not_assigned_to_user'],
        'ignore_statuses' => @$_REQUEST['ignore_statuses'],
        'show_per_issue' => !empty($_REQUEST['show_per_issue']),
        'separate_no_time' => !empty($_REQUEST['separate_no_time']),
    );
    $data = Report::getWeeklyReport($developer, $prj_id, $dates[0], $dates[1], $options);

    // order issues by time spent on them
    if (isset($_REQUEST['show_per_issue'])) {
        $sort_function = function ($a, $b) {
            if ($a['it_spent'] == $b['it_spent']) {
                return 0;
            }

            return ($a['it_spent'] < $b['it_spent']) ? 1 : -1;
        };
        usort($data['issues']['closed'], $sort_function);
        usort($data['issues']['other'], $sort_function);
        usort($data['issues']['not_mine'], $sort_function);
    }
    $tpl->assign('data', $data);
}

if (empty($_REQUEST['week'])) {
    $tpl->assign('week', Date_Helper::getCurrentWeek());
} else {
    $tpl->assign('week', $_REQUEST['week']);
}
if (empty($_REQUEST['developer'])) {
    $tpl->assign('developer', Auth::getUserID());
} else {
    $tpl->assign('developer', $_REQUEST['developer']);
}

$tpl->displayTemplate();
