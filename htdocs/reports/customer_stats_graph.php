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

if (Auth::getCurrentRole() <= User::getRoleID("Customer")) {
    echo "Invalid role";
    exit;
}

/**
 * Customer Statistics Graphs. Will Graph different items, depending on what is passed to this page.
 */

$data = Session::get("customer_stats_data");
if (empty($data)) {
    echo "Unable to load data";
    exit;
}

$colors = array(
            "#c0c0c0",
            "#0033ff",
            "#99ccff",
            "#00ff66",
            "#33ffcc",
            "#ffff66",
            "#ffffcc",
            "#ff3333",
            "#ff9191"
);
$color_index = 0;

$graph_types = Customer_Stats_Report::getGraphTypes();

$graph_id = $_GET["graph_id"];

$plots = array();
$max_title_len = 0;
foreach ($data as $index => $info) {

    if (strlen($info["title"]) > $max_title_len) {
        $max_title_len = strlen($info["title"]);
    }

    // go through data and convert into something plottable
    $plottable = array();
    switch ($graph_id) {
        case 1:
            $plottable["Customer Count"] = $info["customer_counts"]["customer_count"];
            $plottable["Issues"] = $info["issue_counts"]["total"];
            $plottable["Emails by Staff"] = $info["email_counts"]["developer"]["total"];
            $plottable["Emails by Customers"] = $info["email_counts"]["customer"]["total"];
            break;
        case 2:
            $plottable["Issues"] = $info["issue_counts"]["avg"];
            $plottable["Emails by Staff"] = $info["email_counts"]["developer"]["avg"];
            $plottable["Emails by Customers"] = $info["email_counts"]["customer"]["avg"];
            break;
        case 3:
            $plottable["Avg Time to Close"] = $info["time_stats"]["time_to_close"]["avg"] / (60 * 24);
            $plottable["Median Time to Close"] = $info["time_stats"]["time_to_close"]["median"] / (60 * 24);
            break;
        case 4:
            $plottable["Avg Time to First Response"] = $info["time_stats"]["time_to_first_response"]["avg"] / 60;
            $plottable["Median Time to First Response"] = $info["time_stats"]["time_to_first_response"]["median"] / 60;
            break;
    }

    // Create a bar pot
    $bplot = new BarPlot(array_values($plottable));
    $bplot->showValue(true);
    $bplot->SetValueFont(FF_FONT2, FS_NORMAL, 9);
    if (!empty($graph_types[$graph_id]["value_format"])) {
        $value_format = $graph_types[$graph_id]["value_format"];
    } else {
        $value_format = '%d';
    }
    $bplot->SetValueFormat($value_format, 90);

    $bplot->setLegend($info["title"]);
    if (isset($colors[$color_index])) {
        $color = $colors[$color_index];
    } else {
        $color_index = 0;
        $color = $colors[$color_index];
    }
    $color_index++;
    $bplot->SetFillColor($color);
    $plots[] = $bplot;
    $labels = array_keys($plottable);
}

// figure out width of legend to propery set margin.
$legend_width = (imagefontwidth(FF_FONT1) * $max_title_len) + 30;

if (!empty($graph_types[$graph_id]["size"]["group"])) {
    $width = ($graph_types[$graph_id]["size"]["group"] * count($data)) + 200;
} else {
    $width = $graph_types[$graph_id]["size"]["x"];
}

if (!empty($graph_types[$graph_id]["y_label"])) {
    $y_label = $graph_types[$graph_id]["y_label"];
} else {
    $y_label = "Count";
}

$graph = new Graph($width, $graph_types[$graph_id]["size"]["y"]);
$graph->SetScale("textlin");
$graph->img->setMargin(60,($legend_width + 20),25,25);
$graph->yaxis->SetTitleMargin(45);
$graph->yaxis->scale->setGrace(15,0);
$graph->SetShadow();

// Turn the tickmarks
$graph->xaxis->SetTickDirection(SIDE_DOWN);
$graph->yaxis->SetTickDirection(SIDE_LEFT);
$graph->xaxis->SetTickLabels($labels);

// group plots together
$grouped = new GroupBarPlot($plots);
$graph->Add($grouped);

$graph->title->Set($graph_types[$graph_id]["title"]);
//$graph->xaxis->title->Set("Support Level");
//$graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->yaxis->title->Set($y_label);
$graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
$graph->legend->Pos(.015,.5,'right','center');
$graph->Stroke();
