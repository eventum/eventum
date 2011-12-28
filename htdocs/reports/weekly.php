<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
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
$tpl->setTemplate("reports/weekly.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

if (!Access::canAccessReports(Auth::getUserID())) {
    echo "Invalid role";
    exit;
}

$prj_id = Auth::getCurrentProject();

if (count(@$_POST["start"]) > 0 &&
        (@$_POST["start"]["Year"] != 0) &&
        (@$_POST["start"]["Month"] != 0) &&
        (@$_POST["start"]["Day"] != 0)) {
    $start_date = join("-", $_POST["start"]);
} else if (!empty($_GET['start_date'])) {
    $start_date = $_GET['start_date'];
}

if (count(@$_POST["end"]) > 0 &&
        (@$_POST["end"]["Year"] != 0) &&
        (@$_POST["end"]["Month"] != 0) &&
        (@$_POST["end"]["Day"] != 0)) {
    $end_date = join("-", $_POST["end"]);
} else if (!empty($_GET['end_date'])) {
    $end_date = $_GET['end_date'];
}

$tpl->assign(array(
    "weeks" => Date_Helper::getWeekOptions(3,0),
    "users" => Project::getUserAssocList($prj_id, 'active', User::getRoleID('Customer')),
    "start_date"    =>  @$start_date,
    "end_date"      =>  @$end_date,
    "report_type"   =>  @$_REQUEST["report_type"]
));

if (!empty($_REQUEST["developer"])) {

    //split date up
    if (@$_REQUEST["report_type"] == "weekly") {
        $dates = explode("_", $_REQUEST["week"]);
    } else {
        $dates = array($start_date, $end_date);
    }

    // print out emails
    $data = Report::getWeeklyReport($_REQUEST["developer"], $dates[0], $dates[1], @$_REQUEST['separate_closed'], @$_REQUEST['ignore_statuses']);
    // order issues by time spent on them
    if (isset($_REQUEST['show_per_issue'])) {
        $sort_function = create_function('$a,$b', 'if ($a["it_spent"] == $b["it_spent"]) {return 0;} return ($a["it_spent"] < $b["it_spent"]) ? 1 : -1;');
        @usort($data['issues']['closed'], $sort_function);
        @usort($data['issues']['other'], $sort_function);
    }
    $tpl->assign("data", $data);
}

if (empty($_REQUEST["week"])) {
    $tpl->assign("week", Date_Helper::getCurrentWeek());
} else {
    $tpl->assign("week", $_REQUEST["week"]);
}
if (empty($_REQUEST["developer"])) {
    $tpl->assign("developer", Auth::getUserID());
} else {
    $tpl->assign("developer", $_REQUEST["developer"]);
}

$tpl->displayTemplate();
