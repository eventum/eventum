<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2012 Eventum Team.                              |
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
require_once APP_JPGRAPH_PATH . '/jpgraph.php';
require_once APP_JPGRAPH_PATH . '/jpgraph_bar.php';

Auth::checkAuthentication(APP_COOKIE);
$usr_id = Auth::getUserID();

if (!Access::canAccessReports(Auth::getUserID())) {
    echo "Invalid role";
    exit;
}

/**
 * Generates the workload by time period graph.
 */

// get timezone of current user
$user_prefs = Prefs::get($usr_id);

if (@$_GET["type"] == "email") {
    $data = Report::getEmailWorkloadByTimePeriod(@$user_prefs["timezone"], true);
    $graph_title = ev_gettext("Email by Time Period");
    $event_type = ev_gettext("emails");
} else {
    $data = Report::getWorkloadByTimePeriod(@$user_prefs["timezone"], true);
    $graph_title = ev_gettext("Workload by Time Period");
    $event_type = ev_gettext("actions");
}

$plots = array();
foreach ($data as $performer => $values) {
    ksort($values);
    ksort($data[$performer]);

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
$graph->xaxis->title->Set("Hours (" . Date_Helper::getTimezoneShortNameByUser($usr_id) . ")");
$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->yaxis->title->Set(ucfirst($event_type) . " (%)");
$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->legend->Pos(0.01,0.09,'left','bottom');
$graph->legend->SetLayout(LEGEND_HOR);
$graph->Stroke();
