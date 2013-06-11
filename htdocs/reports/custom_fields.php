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
$tpl->setTemplate("reports/custom_fields.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

if (!Access::canAccessReports(Auth::getUserID())) {
    echo "Invalid role";
    exit;
}

$prj_id = Auth::getCurrentProject();

// get list of fields and convert info useful arrays
$fields = Custom_Field::getListByProject($prj_id, '');
$custom_fields = array();
$options = array();
if (is_array($fields) && count($fields) > 0) {
    foreach ($fields as $field) {
        $custom_fields[$field["fld_id"]] = $field["fld_title"];
        $options[$field["fld_id"]] = Custom_Field::getOptions($field["fld_id"]);
    }
} else {
    echo ev_gettext("No custom fields for this project");
    exit;
}

if ((!empty($_REQUEST['start']['Year'])) && (!empty($_REQUEST['start']['Month'])) &&(!empty($_REQUEST['start']['Day']))) {
    $start = join('-', $_REQUEST['start']);
} else {
    $start = false;
}
if ((!empty($_REQUEST['end']['Year'])) && (!empty($_REQUEST['end']['Month'])) &&(!empty($_REQUEST['end']['Day']))) {
    $end = join('-', $_REQUEST['end']);
} else {
    $end = false;
}

if (count(@$_GET['custom_field']) > 0) {
    $data = Report::getCustomFieldReport(@$_GET["custom_field"], @$_GET["custom_options"], @$_GET["group_by"], $start, $end, true, @$_REQUEST['interval'],
                        @$_REQUEST['assignee']);
}

if (($start == false) || ($end = false)) {
    $start = '--';
    $end = '--';
}

$tpl->assign(array(
    "custom_fields" =>  $custom_fields,
    "custom_field"  =>  @$_GET["custom_field"],
    "options"   =>  $options,
    "custom_options"    =>  @$_GET["custom_options"],
    "group_by"      =>  @$_GET["group_by"],
    "selected_options"  => @$_REQUEST['custom_options'],
    "data"  =>  @$data,
    "start_date"=>  $start,
    "end_date"  =>  $end,
    "assignees" =>  Project::getUserAssocList($prj_id, 'active', User::getRoleID("Customer")),
    "assignee"  =>  @$_REQUEST['assignee'],
));

if (isset($_GET["custom_field"])) {
    $tpl->assign(array(
        "field_info"  =>  Custom_Field::getDetails($_GET['custom_field'])
    ));
}

$tpl->displayTemplate();
