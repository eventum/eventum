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
$tpl->setTemplate('reports/custom_fields_weekly.tpl.html');

Auth::checkAuthentication();

if (!Access::canAccessReports(Auth::getUserID())) {
    echo 'Invalid role';
    exit;
}

$prj_id = Auth::getCurrentProject();

// get list of fields and convert info useful arrays
$fields = Custom_Field::getListByProject($prj_id, '');
$custom_fields = array();
$options = array();
if (is_array($fields) && count($fields) > 0) {
    foreach ($fields as $field) {
        $custom_fields[$field['fld_id']] = $field['fld_title'];
        $options[$field['fld_id']] = Custom_Field::getOptions($field['fld_id']);
    }
} else {
    echo ev_gettext('No custom fields for this project');
    exit;
}

if (count(@$_REQUEST['start']) > 0 &&
        (@$_REQUEST['start']['Year'] != 0) &&
        (@$_REQUEST['start']['Month'] != 0) &&
        (@$_REQUEST['start']['Day'] != 0)) {
    $start_date = implode('-', $_REQUEST['start']);
}

if (count(@$_REQUEST['end']) > 0 &&
        (@$_REQUEST['end']['Year'] != 0) &&
        (@$_REQUEST['end']['Month'] != 0) &&
        (@$_REQUEST['end']['Day'] != 0)) {
    $end_date = implode('-', $_REQUEST['end']);
}
$per_user = empty($_REQUEST['time_per_user']) ? false : true;

$tpl->assign(array(
    'custom_fields' =>  $custom_fields,
    'custom_field'  =>  @$_REQUEST['custom_field'],
    'options'   =>  $options,
    'custom_options'    =>  @$_REQUEST['custom_options'],
    'selected_options'  => @$_REQUEST['custom_options'],
    'start_date'    =>  @$start_date,
    'end_date'      =>  @$end_date,
    'report_type'   =>  @$_REQUEST['report_type'],
    'per_user'   =>  $per_user,
    'weeks' => Date_Helper::getWeekOptions(3, 0),
));

if (empty($_REQUEST['week'])) {
    $tpl->assign('week', Date_Helper::getCurrentWeek());
} else {
    $tpl->assign('week', $_REQUEST['week']);
}

if (isset($_REQUEST['custom_field'])) {
    $tpl->assign(array(
        'field_info'  =>  Custom_Field::getDetails($_REQUEST['custom_field']),
    ));
}

// split date up
if (@$_REQUEST['report_type'] == 'weekly') {
    $dates = explode('_', $_REQUEST['week']);
} else {
    $dates = array(@$start_date, @$end_date);
}

if (count(@$_REQUEST['custom_field']) > 0) {
    $data = Report::getCustomFieldWeeklyReport(@$_REQUEST['custom_field'], @$_REQUEST['custom_options'], $dates[0], $dates[1], $per_user);
    $tpl->assign(array(
        'data'  =>  $data,
    ));
}

$tpl->displayTemplate();
