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
include_once(APP_INC_PATH . "class.date.php");
include_once(APP_INC_PATH . "db_access.php");

$tpl = new Template_API();
$tpl->setTemplate("reports/weekly.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

$prj_id = Auth::getCurrentProject();

$tpl->assign(array(
    "weeks" => Date_API::getWeekOptions(3,0),
    "users" => Project::getUserAssocList($prj_id, 'active', User::getRoleID('Customer'))
));

if (!empty($HTTP_POST_VARS["week"]) && !empty($HTTP_POST_VARS["developer"])) {
    
    //split date up
    $dates = explode("_", $HTTP_POST_VARS["week"]);
    
    
    // print out emails
    $data = Report::getWeeklyReport($HTTP_POST_VARS["developer"], $dates[0], $dates[1]);
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