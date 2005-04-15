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
include_once(APP_INC_PATH . "class.date.php");
include_once(APP_INC_PATH . "db_access.php");

$tpl = new Template_API();
$tpl->setTemplate("reports/workload_time_period.tpl.html");

Auth::checkAuthentication(APP_COOKIE);
$usr_id = Auth::getUserID();

if (Auth::getCurrentRole() <= User::getRoleID("Customer")) {
    echo "Invalid role";
    exit;
}

$prj_id = Auth::getCurrentProject();

// get timezone of current user
$user_prefs = Prefs::get($usr_id);

if (@$HTTP_GET_VARS["type"] == "email") {
    $data = Report::getEmailWorkloadByTimePeriod(@$user_prefs["timezone"]);
} else {
    $data = Report::getWorkloadByTimePeriod(@$user_prefs["timezone"]);
}

$tpl->assign(array(
    "data"    => $data,
    "type"    => @$HTTP_GET_VARS["type"],
    "user_tz" => Date_API::getTimezoneShortNameByUser($usr_id)
));
$tpl->displayTemplate();
?>