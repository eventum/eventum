<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005 MySQL AB                              |
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
//
// @(#) $Id$
//
include_once("../config.inc.php");
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.report.php");
include_once(APP_INC_PATH . "class.custom_field.php");
include_once(APP_INC_PATH . "db_access.php");

$tpl = new Template_API();
$tpl->setTemplate("reports/custom_fields.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

if (Auth::getCurrentRole() <= User::getRoleID("Customer")) {
    echo "Invalid role";
    exit;
}

$prj_id = Auth::getCurrentProject();

// get list of fields and convert info useful arrays
$fields = Custom_Field::getListByProject($prj_id, '', "combo");
$custom_fields = array();
$options = array();
if (is_array($fields) && count($fields) > 0) {
    foreach ($fields as $field) {
        $custom_fields[$field["fld_id"]] = $field["fld_title"];
        $options[$field["fld_id"]] = Custom_Field::getOptions($field["fld_id"]);
    }
} else {
    echo "No custom fields for this project";
    exit;
}

if (count(@$_GET['custom_field']) > 0) {
    $data = Report::getCustomFieldReport(@$HTTP_GET_VARS["custom_field"], @$HTTP_GET_VARS["custom_options"], @$HTTP_GET_VARS["group_by"], true);
}

$tpl->assign(array(
    "custom_fields" =>  $custom_fields,
    "custom_field"  =>  @$HTTP_GET_VARS["custom_field"],
    "options"   =>  $options,
    "custom_options"    =>  @$HTTP_GET_VARS["custom_options"],
    "group_by"      =>  @$HTTP_GET_VARS["group_by"],
    "selected_options"  => @$_REQUEST['custom_options'],
    "data"  =>  @$data
));

$tpl->displayTemplate();
?>