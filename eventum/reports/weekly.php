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
$tpl->setTemplate("reports/weekly.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

if (Auth::getCurrentRole() <= User::getRoleID("Customer")) {
    echo "Invalid role";
    exit;
}

$prj_id = Auth::getCurrentProject();

if (count(@$HTTP_POST_VARS["start"]) > 0 &&
        (@$HTTP_POST_VARS["start"]["Year"] != 0) &&
        (@$HTTP_POST_VARS["start"]["Month"] != 0) &&
        (@$HTTP_POST_VARS["start"]["Day"] != 0)) {
    $start_date = join("-", $HTTP_POST_VARS["start"]);
}
if (count(@$HTTP_POST_VARS["end"]) > 0 &&
        (@$HTTP_POST_VARS["end"]["Year"] != 0) &&
        (@$HTTP_POST_VARS["end"]["Month"] != 0) &&
        (@$HTTP_POST_VARS["end"]["Day"] != 0)) {
    $end_date = join("-", $HTTP_POST_VARS["end"]);
}

$tpl->assign(array(
    "weeks" => Date_API::getWeekOptions(3,0),
    "users" => Project::getUserAssocList($prj_id, 'active', User::getRoleID('Customer')),
    "start_date"    =>  @$start_date,
    "end_date"      =>  @$end_date,
    "report_type"   =>  @$HTTP_POST_VARS["report_type"]
));

if (!empty($HTTP_POST_VARS["developer"])) {

    //split date up
    if (@$HTTP_POST_VARS["report_type"] == "weekly") {
        $dates = explode("_", $HTTP_POST_VARS["week"]);
    } else {
        $dates = array($start_date, $end_date);
    }

    // print out emails
    $data = Report::getWeeklyReport($HTTP_POST_VARS["developer"], $dates[0], $dates[1], @$_REQUEST['separate_closed']);
    $tpl->assign("data", $data);
}

if (empty($HTTP_POST_VARS["week"])) {
    $tpl->assign("week", Date_API::getCurrentWeek());
} else {
    $tpl->assign("week", $HTTP_POST_VARS["week"]);
}
if (empty($HTTP_POST_VARS["developer"])) {
    $tpl->assign("developer", Auth::getUserID());
} else {
    $tpl->assign("developer", $HTTP_POST_VARS["developer"]);
}

$tpl->displayTemplate();
?>