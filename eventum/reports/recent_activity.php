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
// This report shows a list of activity performed in recent history.
include_once("../config.inc.php");
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "db_access.php");

$tpl = new Template_API();
$tpl->setTemplate("reports/recent_activity.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

if (Auth::getCurrentRole() <= User::getRoleID("Customer")) {
    echo "Invalid role";
    exit;
}

$units = array(
    "hour"  =>  "Hours",
    "day"   =>  "Days"
);

$prj_id = Auth::getCurrentProject();
$usr_id = Auth::getUserID();

$tpl->assign(array(
    "units" =>  $units,
    "users" => Project::getUserAssocList($prj_id, 'active', User::getRoleID('Customer'))
));


if (((!empty($_REQUEST['unit'])) && (!empty($_REQUEST['amount']))) || (@count($_REQUEST['start']) == 3)) {
    
    if (count(@$_REQUEST["start"]) > 0 &&
            (@$_REQUEST["start"]["Year"] != 0) &&
            (@$_REQUEST["start"]["Month"] != 0) &&
            (@$_REQUEST["start"]["Day"] != 0)) {
        $start_date = join("-", $HTTP_POST_VARS["start"]);
    }
    if (count(@$_REQUEST["end"]) > 0 &&
            (@$_REQUEST["end"]["Year"] != 0) &&
            (@$_REQUEST["end"]["Month"] != 0) &&
            (@$_REQUEST["end"]["Day"] != 0)) {
        $end_date = join("-", $HTTP_POST_VARS["end"]);
    }
        
    $sql = "SELECT
                " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "phone_support.*,
                phc_title,
                usr_full_name,
                iss_summary
            FROM
                " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "phone_support,
                " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_phone_category,
                " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
            WHERE
                phs_phc_id = phc_id AND
                phs_iss_id = iss_id AND
                phs_usr_id = usr_id AND
                iss_prj_id = $prj_id AND\n";
    if ($_REQUEST['report_type'] == 'recent') {
        $sql .= "phs_created_date >= DATE_SUB('" . Date_API::getCurrentDateGMT() . "', INTERVAL " . $_REQUEST['amount'] . " " . $_REQUEST['unit'] . ")";
    } else {
        $sql .= "phs_created_date BETWEEN '$start_date' AND '$end_date'";
    }
    if (!empty($_REQUEST['developer'])) {
        $sql .= " AND phs_usr_id = " . $_REQUEST['developer'];
    }
    $res = $GLOBALS["db_api"]->dbh->getAll($sql, DB_FETCHMODE_ASSOC);
    if (PEAR::isError($res)) {
        Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
    } else {
        for ($i = 0; $i < count($res); $i++) {
            if (Customer::hasCustomerIntegration($prj_id)) {
                $details = Customer::getDetails($prj_id, Issue::getCustomerID($res[$i]['phs_iss_id']));
                $res[$i]["customer"] = $details['customer_name'];
            }
            $res[$i]["date"] = Date_API::getFormattedDate($res[$i]['phs_created_date'], Date_API::getPreferredTimezone($usr_id));
        }
        
        $tpl->assign("data", $res);
    }
    $tpl->assign(array(
        "unit"  =>  $_REQUEST['unit'],
        "amount"    =>  $_REQUEST['amount'],
        "developer" =>  $_REQUEST['developer'],
        "start_date"    =>  @$start_date,
        "end_date"      =>  @$end_date,
    ));
}

$tpl->displayTemplate();
?>