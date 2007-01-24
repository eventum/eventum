<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005, 2006, 2007 MySQL AB                              |
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
// @(#) $Id: custom_fields.php 3206 2007-01-24 20:24:35Z glen $
//
require_once(dirname(__FILE__) . "/../init.php");
require_once(APP_INC_PATH . "class.template.php");
require_once(APP_INC_PATH . "class.auth.php");
require_once(APP_INC_PATH . "class.report.php");
require_once(APP_INC_PATH . "class.custom_field.php");
require_once(APP_INC_PATH . "db_access.php");

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
    echo ev_gettext("No custom fields for this project");
    exit;
}

if (count(@$_GET['custom_field']) > 0) {
    $data = Report::getCustomFieldReport(@$_GET["custom_field"], @$_GET["custom_options"], @$_GET["group_by"], true);
}

$tpl->assign(array(
    "custom_fields" =>  $custom_fields,
    "custom_field"  =>  @$_GET["custom_field"],
    "options"   =>  $options,
    "custom_options"    =>  @$_GET["custom_options"],
    "group_by"      =>  @$_GET["group_by"],
    "selected_options"  => @$_REQUEST['custom_options'],
    "data"  =>  @$data
));

$tpl->displayTemplate();
