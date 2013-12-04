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
$tpl->setTemplate("reports/customer_stats.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

if (!Access::canAccessReports(Auth::getUserID())) {
    echo "Invalid role";
    exit;
}

// check if this project has customer integration
$prj_id = Auth::getCurrentProject();
if (!CRM::hasCustomerIntegration($prj_id)) {
    $tpl->assign("no_customer_integration", 1);
    $tpl->displayTemplate();
    exit;
}
$crm = CRM::getInstance($prj_id);

if (count(@$_POST["start"]) > 0 &&
        (@$_POST["start"]["Year"] != 0) &&
        (@$_POST["start"]["Month"] != 0) &&
        (@$_POST["start"]["Day"] != 0)) {
    $start_date = join("-", $_POST["start"]);
} else {
    $start_date = "0000-00-00";
}
if (count(@$_POST["end"]) > 0 &&
        (@$_POST["end"]["Year"] != 0) &&
        (@$_POST["end"]["Month"] != 0) &&
        (@$_POST["end"]["Day"] != 0)) {
    $end_date = join("-", $_POST["end"]);
} else {
    $end_date = "0000-00-00";
}

if (count(@$_POST["display_sections"]) < 1) {
    $_POST["display_sections"] = array_keys(Customer_Stats_Report::getDisplaySections());
    unset($_POST["display_sections"][4]);
}

$support_levels = array('Aggregate' => 'Aggregate');
$grouped_levels = $crm->getGroupedSupportLevels();
foreach ($grouped_levels as $level_name => $level_ids) {
    $support_levels[$level_name] = $level_name;
}

if (count(@$_POST["support_level"]) < 1) {
    $_POST["support_level"] = array('Aggregate');
}

$prj_id = Auth::getCurrentProject();
$tpl->assign(array(
    "project_name"      =>  Auth::getCurrentProjectName(),
    "support_levels"    =>  $support_levels,
    "support_level"     =>  @$_POST["support_level"],
    "start_date"        =>  $start_date,
    "end_date"          =>  $end_date,
    "sections"          =>  Customer_Stats_Report::getDisplaySections(),
    "display_sections"  =>  $_POST["display_sections"],
    "include_expired"   =>  @$_POST["include_expired"],
    "graphs"            =>  Customer_Stats_Report::getGraphTypes()
));

// only set customers if user has role of manager or above
if (Auth::getCurrentRole() >= User::getRoleID('manager')) {
    $tpl->assign(array(
    "customers"         =>  $crm->getCustomerAssocList(),
    "customer"          =>  @$_POST["customer"]
    ));
}

// loop through display sections
$display = array();
foreach ($_POST["display_sections"] as $section) {
    $display[$section] = 1;
}
$tpl->assign("display", $display);


if (@$_POST["cat"] == "Generate") {

    if ($start_date == "0000-00-00") {
        $start_date = '';
    }
    if ($end_date == "0000-00-00") {
        $end_date = '';
    }

    // set the date range msg
    if ((!empty($start_date)) && (!empty($end_date))) {
        $date_msg_text = "Date Range: $start_date - $end_date";
        $tpl->assign(array(
            "date_msg_text" =>  $date_msg_text,
            "date_msg"      =>  "<div align=\"center\" style=\"font-family: Verdana, Arial, Helvetica, sans-serif;font-style: normal;font-weight: bold; margin: 3px\">
                                    $date_msg_text
                                </div>"
        ));
    }

    $csr = new Customer_Stats_Report(
                    $prj_id,
                    @$_POST["support_level"],
                    @$_POST["customer"],
                    $start_date,
                    $end_date);
    if (@$_POST["split_innoDB"] == 1) {
        $csr->splitByInnoDB(true);
    }
    if (@$_POST["include_expired"] == 1) {
        $csr->excludeExpired(false);
    } else {
        $csr->excludeExpired(true);
    }

    $data = $csr->getData();
    $tpl->assign("data", $data);
    $tpl->assign("time_tracking_categories", $csr->getTimeTrackingCategories());
    $tpl->assign("row_label", $csr->getRowLabel());

    Session::set("customer_stats_data", $data);
}

function formatValue($value, $all_value, $round_places = false, $alternate_value = false)
{
    if ($alternate_value === false) {
        $compare_value = $value;
    } else {
        $compare_value = $alternate_value;
    }

    if ($all_value < $compare_value) {
        $color = "red";
    } else if ($all_value > $compare_value)  {
        $color = "blue";
    } else {
        if (is_int($round_places)) {
            $value = round($value, $round_places);
        }
        return $value;
    }
    if (is_int($round_places)) {
        $value = round($value, $round_places);
    }
    return "<span style=\"color: $color\">$value</span>";
}

$tpl->displayTemplate();
   // echo "<pre>";print_r(@$data);echo "</pre>";
