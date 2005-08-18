<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004 MySQL AB                                    |
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
include_once(APP_INC_PATH . "class.session.php");
include_once(APP_INC_PATH . "db_access.php");

$tpl = new Template_API();
$tpl->setTemplate("reports/workload_date_range.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

if (Auth::getCurrentRole() <= User::getRoleID("Customer")) {
    echo "Invalid role";
    exit;
}

$types = array(
    "individual"    =>  "Individual",
    "aggregate"     =>  "Aggregate"
);


if (count(@$_REQUEST["start"]) > 0 &&
        (@$_REQUEST["start"]["Year"] != 0) &&
        (@$_REQUEST["start"]["Month"] != 0) &&
        (@$_REQUEST["start"]["Day"] != 0)) {
    $start_date = join("-", $_REQUEST["start"]);
} else {
    // if empty start date, set to be a month ago
    $start_date = date("Y-m-d", time() - MONTH);
}
if (count(@$_REQUEST["end"]) > 0 &&
        (@$_REQUEST["end"]["Year"] != 0) &&
        (@$_REQUEST["end"]["Month"] != 0) &&
        (@$_REQUEST["end"]["Day"] != 0)) {
    $end_date = join("-", $_REQUEST["end"]);
} else {
    $end_date = date("Y-m-d");
}


if (!empty($_REQUEST["interval"])) {
    $data = Report::getWorkloadByDateRange($_REQUEST["interval"], $_REQUEST["type"], $start_date, date('Y-m-d', (strtotime($end_date) + DAY)));
    Session::set("workload_date_range_data", $data);
    $tpl->assign("data", $data);
  //  echo "<pre>";print_r($data);echo "</pre>";
}

$tpl->assign(array(
    "interval"  =>  @$_REQUEST["interval"],
    "types" =>  $types,
    "type"  =>  @$_REQUEST["type"],
    "start_date"    =>  $start_date,
    "end_date"  =>  $end_date
));
$tpl->displayTemplate();
?>