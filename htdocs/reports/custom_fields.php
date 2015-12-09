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
$tpl->setTemplate('reports/custom_fields.tpl.html');

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

if ((!empty($_REQUEST['start']['Year'])) && (!empty($_REQUEST['start']['Month'])) && (!empty($_REQUEST['start']['Day']))) {
    $start = implode('-', $_REQUEST['start']);
} else {
    $start = false;
}
if ((!empty($_REQUEST['end']['Year'])) && (!empty($_REQUEST['end']['Month'])) && (!empty($_REQUEST['end']['Day']))) {
    $end = implode('-', $_REQUEST['end']);
} else {
    $end = false;
}

if (count(@$_GET['custom_field']) > 0) {
    $data = Report::getCustomFieldReport(@$_GET['custom_field'], @$_GET['custom_options'], @$_GET['group_by'], $start, $end, true, @$_REQUEST['interval'],
                        @$_REQUEST['assignee']);
}

if (($start == false) || ($end = false)) {
    $start = '--';
    $end = '--';
}

$tpl->assign(array(
    'custom_fields' =>  $custom_fields,
    'custom_field'  =>  @$_GET['custom_field'],
    'options'   =>  $options,
    'custom_options'    =>  @$_GET['custom_options'],
    'group_by'      =>  @$_GET['group_by'],
    'selected_options'  => @$_REQUEST['custom_options'],
    'data'  =>  @$data,
    'start_date' =>  $start,
    'end_date'  =>  $end,
    'assignees' =>  Project::getUserAssocList($prj_id, 'active', User::ROLE_CUSTOMER),
    'assignee'  =>  @$_REQUEST['assignee'],
));

if (isset($_GET['custom_field'])) {
    $tpl->assign(array(
        'field_info'  =>  Custom_Field::getDetails($_GET['custom_field']),
    ));
}

$tpl->displayTemplate();
