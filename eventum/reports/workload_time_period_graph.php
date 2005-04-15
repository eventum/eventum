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
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.report.php");
include_once(APP_INC_PATH . "class.prefs.php");
include_once(APP_INC_PATH . "class.date.php");
include_once(APP_INC_PATH . "db_access.php");
include_once(APP_JPGRAPH_PATH . "jpgraph.php");
include_once(APP_JPGRAPH_PATH . "jpgraph_bar.php");

Auth::checkAuthentication(APP_COOKIE);
$usr_id = Auth::getUserID();

if (Auth::getCurrentRole() <= User::getRoleID("Customer")) {
    echo "Invalid role";
    exit;
}

/**
 * Generates the workload by time period graph.
 */

// get timezone of current user
$user_prefs = Prefs::get($usr_id);

if (@$HTTP_GET_VARS["type"] == "email") {
    $data = Report::getEmailWorkloadByTimePeriod(@$user_prefs["timezone"], true);
    $graph_title = "Email by Time Period";
    $event_type = "emails";
} else {
    $data = Report::getWorkloadByTimePeriod(@$user_prefs["timezone"], true);
    $graph_title = "Workload by Time Period";
    $event_type = "actions";
}

$plots = array();
foreach ($data as $performer => $values) {
    ksort($values);ksort($data[$performer]);
    
    // Create a bar pot 
    $bplot = new BarPlot(array_values($values));
    
    if ($performer == "customer") {
        $color = "#99ccff";
    } else {
        $color = "#ffcc00";
    }
    $bplot->SetFillColor($color);
    $bplot->setLegend(ucfirst($performer) . " " . $event_type);
    
    $plots[] = $bplot;
}

$graph = new Graph(800,350);
$graph->SetScale("textlin");
$graph->img->SetMargin(60,30,40,40);
$graph->yaxis->SetTitleMargin(45);
$graph->SetShadow();

// Turn the tickmarks 
$graph->xaxis->SetTickDirection(SIDE_DOWN);
$graph->yaxis->SetTickDirection(SIDE_LEFT);
$graph->xaxis->SetTickLabels(array_keys($data["developer"] + $data["customer"]));

// group plots together
$grouped = new GroupBarPlot($plots);
$graph->Add($grouped);

$graph->title->Set($graph_title);
$graph->xaxis->title->Set("Hours (" . Date_API::getTimezoneShortNameByUser($usr_id) . ")");
$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->yaxis->title->Set(ucfirst($event_type) . " (%)");
$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->legend->Pos(0.01,0.09,'left','bottom');
$graph->legend->SetLayout(LEGEND_HOR); 
$graph->Stroke();
?>