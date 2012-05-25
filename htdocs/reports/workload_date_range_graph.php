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
require_once APP_JPGRAPH_PATH . '/jpgraph_pie.php';
require_once APP_JPGRAPH_PATH . '/jpgraph_line.php';

Auth::checkAuthentication(APP_COOKIE);

if (Auth::getCurrentRole() <= User::getRoleID("Customer")) {
    echo "Invalid role";
    exit;
}

/**
 * Generates a graph for workload by date range report
 */
$data = Session::get("workload_date_range_data");
if (empty($data)) {
    echo "Unable to load data";
    exit;
}

switch ($_REQUEST["interval"]) {
    case "dow":
        $x_title = "Day of Week";
        break;
    case "week":
        $x_title = "Week";
        break;
    case "dom":
        $x_title = "Day of Month";
        break;
    case "day":
        $x_title = "Day";
        break;
    case "month":
        $x_title = "Month";
        break;
}

if ($_REQUEST["graph"] == "issue") {
    $plots = array_values($data["issues"]["points"]);
    $graph_title = "Issues by created date";
    $labels = array_keys($data["issues"]["points"]);
    $y_label = "Issues";
} elseif ($_REQUEST["graph"] == "email") {
    $plots = array_values($data["emails"]["points"]);
    $graph_title = "Emails by sent date";
    $labels = array_keys($data["emails"]["points"]);
    $y_label = "Emails";
}elseif ($_REQUEST["graph"] == "note") {
    $plots = array_values($data["notes"]["points"]);
    $graph_title = "Notes by sent date";
    $labels = array_keys($data["notes"]["points"]);
    $y_label = "Notes";
} elseif ($_REQUEST["graph"] == "phone") {
    $plots = array_values($data["phone"]["points"]);
    $graph_title = "Phone calls by date";
    $labels = array_keys($data["phone"]["points"]);
    $y_label = "Phone Calls";
} elseif ($_REQUEST["graph"] == "time_spent") {
    $plots = array_values($data["time_spent"]["points"]);
    $graph_title = "Time spent (hrs)";
    $labels = array_keys($data["time_spent"]["points"]);
    $y_label = "Hours";
} elseif ($_REQUEST["graph"] == "avg_time_per_issue") {
    $plots = array_values($data["avg_time_per_issue"]["points"]);
    $graph_title = "Avg. Time spent per issue (min)";
    $labels = array_keys($data["avg_time_per_issue"]["points"]);
    $y_label = "Minutes";
}
$graph_title .= " " . $_REQUEST["start_date"] . " through " . $_REQUEST["end_date"];

if (count($plots) < 1) {
    Header("Location: ../images/no_data.gif");
    exit;
}

if (@$_REQUEST["type"] == "pie") {

    // A new graph
    $graph = new PieGraph(500,300,"auto");

    // The pie plot
    $plot = new PiePlot($plots);
    $plot->SetTheme('pastel');

    // Move center of pie to the left to make better room
    // for the legend
    $plot->SetCenter(0.26,0.55);

    // Label font and color setup
    $plot->SetFont(FF_FONT1, FS_BOLD);
    $plot->SetFontColor("black");

    // Use percentages
    $plot->SetLabelType(0);

    // Size of pie in fraction of the width of the graph
    $plot->SetSize(0.3);

    // Legends
    $plot->SetLegends($labels);
    $graph->legend->SetFont(FF_FONT1);
    $graph->legend->Pos(0.06,0.27);

} else {

    // bar chart
    $plot = new BarPlot($plots);
    $plot->showValue(true);
    $plot->SetValueFont(FF_FONT2, FS_NORMAL, 9);

    //$plot->setLegend("Issues");

    // figure out the best size for this graph.
    $width = 75;
    if (count($labels) > 3) {
        foreach ($labels as $label) {
            $label_width = imagefontwidth(FF_FONT1) * strlen($label) + 15;
            if ($label_width < 50) {
                $label_width = 50;
            }
            $width += $label_width;
        }
    }
    if ($width < 500) {
        $width = 500;
    }

    $plot->showValue(true);
    $plot->SetFillColor("#0000ff");

    $graph = new Graph($width,350);
    $graph->SetScale("textlin");
    $graph->img->SetMargin(50,30,40,40);
    $graph->yaxis->SetTitleMargin(30);
    $graph->SetShadow();

    // Turn the tickmarks
    $graph->xaxis->SetTickDirection(SIDE_DOWN);
    $graph->yaxis->SetTickDirection(SIDE_LEFT);
    $graph->xaxis->SetTickLabels($labels);

    $graph->xaxis->title->Set($x_title);
    $graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);
    $graph->title->SetFont(FF_FONT1,FS_BOLD);
    $graph->yaxis->scale->setGrace(15,0);
    $graph->yaxis->title->Set($y_label);
    $graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);

}

$graph->title->Set($graph_title);
$graph->title->SetFont(FF_FONT1,FS_BOLD);

$graph->Add($plot);
$graph->Stroke();
