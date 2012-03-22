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

Auth::checkAuthentication(APP_COOKIE);

if (Auth::getCurrentRole() <= User::getRoleID("Customer")) {
    echo "Invalid role";
    exit;
}

/**
 * Generates a graph for the selected custom field
 */

if ((!empty($_REQUEST['start']['Year'])) && (!empty($_REQUEST['start']['Month'])) &&(!empty($_REQUEST['start']['Day']))) {
    $start = join('-', $_REQUEST['start']);
} else {
    $start = false;
}
if ((!empty($_REQUEST['end']['Year'])) && (!empty($_REQUEST['end']['Month'])) &&(!empty($_REQUEST['end']['Day']))) {
    $end = join('-', $_REQUEST['end']);
} else {
    $end = false;
}

$data = Report::getCustomFieldReport(@$_GET["custom_field"], @$_GET["custom_options"], @$_GET["group_by"], $start, $end, false, @$_REQUEST['interval']);
$field_details = Custom_Field::getDetails(@$_GET["custom_field"]);

if (count($data) < 2) {
    header("Location: " . APP_RELATIVE_URL . "/images/no_data.gif");
}

if (@$_GET["type"] == "pie") {

    if (empty($data["All Others"])) {
        unset($data["All Others"]);
    }

    // A new graph
    $graph = new PieGraph(500,300,"auto");

    // The pie plot
    $plot = new PiePlot(array_values($data));
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
    $plot->SetLegends(array_keys($data));
    $graph->legend->SetFont(FF_FONT1);
    $graph->legend->Pos(0.06,0.27);

} else {
    // bar chart

    unset($data["All Others"]);

    // figure out the best size for this graph.
    $width = 75;
    if (count($data) > 3) {
        foreach ($data as $label => $value) {
            $label_width = imagefontwidth(FF_FONT1) * strlen($label) + 15;
            if ($label_width < 50) {
                $label_width = 50;
            }
            $width += $label_width;

            unset($data[$label]);
            $label = str_replace(array( '-', '/'), array("-\n", "/\n"), $label);
            $data[$label] = $value;
        }
    }
    if ($width < 500) {
        $width = 500;
    }

    // Create a bar pot
    $plot = new BarPlot(array_values($data));
    $plot->showValue(true);
    $plot->SetFillColor("#0000ff");

    $graph = new Graph($width,350);
    $graph->SetScale("textlin");
    $graph->img->SetMargin(60,30,40,60);
    $graph->yaxis->SetTitleMargin(45);
    $graph->SetShadow();

    // Turn the tickmarks
    $graph->xaxis->SetTickDirection(SIDE_DOWN);
    $graph->yaxis->SetTickDirection(SIDE_LEFT);
    $graph->xaxis->SetTickLabels(array_keys($data));

    $graph->xaxis->title->Set($field_details["fld_title"]);
    $graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);
    $graph->xaxis->SetTitleMargin(18);
    $graph->title->SetFont(FF_FONT1,FS_BOLD);
    $graph->yaxis->title->Set("Issue Count");
    $graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
}

if (@$_GET["group_by"] == "customers") {
    "Customers by " . $field_details["fld_title"];
} else {
    $title = "Issues by " . $field_details["fld_title"];
}

$graph->title->Set($title);
$graph->title->SetFont(FF_FONT1,FS_BOLD);

$graph->Add($plot);
$graph->Stroke();
