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
$tpl->setTemplate("reports/workload_time_period.tpl.html");

Auth::checkAuthentication(APP_COOKIE);
$usr_id = Auth::getUserID();

if (!Access::canAccessReports(Auth::getUserID())) {
    echo "Invalid role";
    exit;
}

$prj_id = Auth::getCurrentProject();

// get timezone of current user
$user_prefs = Prefs::get($usr_id);

if (@$_GET["type"] == "email") {
    $data = Report::getEmailWorkloadByTimePeriod(@$user_prefs["timezone"]);
} else {
    $data = Report::getWorkloadByTimePeriod(@$user_prefs["timezone"]);
}

$tpl->assign(array(
    "data"    => $data,
    "type"    => @$_GET["type"],
    "user_tz" => Date_Helper::getTimezoneShortNameByUser($usr_id)
));
$tpl->displayTemplate();
