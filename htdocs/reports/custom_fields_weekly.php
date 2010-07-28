<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2010 Sun Microsystem Inc.                              |
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
// | Authors: Raul Raat <raul.raat@delfi.ee>                              |
// +----------------------------------------------------------------------+

require_once dirname(__FILE__) . '/../../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate("reports/custom_fields_weekly.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

if (Auth::getCurrentRole() <= User::getRoleID("Customer")) {
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

if (count(@$_POST["start"]) > 0 &&
        (@$_POST["start"]["Year"] != 0) &&
        (@$_POST["start"]["Month"] != 0) &&
        (@$_POST["start"]["Day"] != 0)) {
    $start_date = join("-", $_POST["start"]);
}

if (count(@$_POST["end"]) > 0 &&
        (@$_POST["end"]["Year"] != 0) &&
        (@$_POST["end"]["Month"] != 0) &&
        (@$_POST["end"]["Day"] != 0)) {
    $end_date = join("-", $_POST["end"]);
}
$per_user = empty($_POST['time_per_user']) ? false : true;

$tpl->assign(array(
    "custom_fields" =>  $custom_fields,
    "custom_field"  =>  @$_POST["custom_field"],
    "options"   =>  $options,
    "custom_options"    =>  @$_POST["custom_options"],
    "selected_options"  => @$_REQUEST['custom_options'],
    "start_date"    =>  @$start_date,
    "end_date"      =>  @$end_date,
    "report_type"   =>  @$_POST["report_type"],
    "per_user"   =>  $per_user,
    "weeks" => Date_Helper::getWeekOptions(3,0),
));

if (empty($_POST["week"])) {
    $tpl->assign("week", Date_Helper::getCurrentWeek());
} else {
    $tpl->assign("week", $_POST["week"]);
}

if (isset($_POST["custom_field"])) {
    $tpl->assign(array(
        "field_info"  =>  Custom_Field::getDetails($_POST['custom_field'])
    ));
}

// split date up
if (@$_POST["report_type"] == "weekly") {
    $dates = explode("_", $_POST["week"]);
} else {
    $dates = array($start_date, $end_date);
}

if (count(@$_POST['custom_field']) > 0) {
	$data = Report::getCustomFieldWeeklyReport($_POST["custom_field"], $_POST["custom_options"], $dates[0], $dates[1], $per_user);
    $tpl->assign(array(
        "data"  =>  $data
    ));
}

$tpl->displayTemplate();
