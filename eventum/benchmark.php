<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005, 2006 MySQL AB                        |
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
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// +----------------------------------------------------------------------+
//
// @(#) $Id: s.benchmark.php 1.2 03/03/24 00:33:26-00:00 jpm $
//
include_once("config.inc.php");
include_once(APP_INC_PATH . "db_access.php");

error_reporting(E_ALL);
include_once(APP_JPGRAPH_PATH . "jpgraph.php");
include_once(APP_JPGRAPH_PATH . "jpgraph_pie.php");

$font = FF_FONT1;

$stats = unserialize(base64_decode($HTTP_POST_VARS["encoded_stats"]));
$labels = array();
$data = array();
foreach ($stats as $point) {
    if ($point["name"] != "Start") {
        $labels[] = $point["name"] . " (" . $point["diff"] . ")";
        $data[] = (float) $point["diff"];
    }
}

// A new graph
$graph = new PieGraph(750, 500, "auto");

// Setup title
$graph->title->Set("Benchmark Results");
$graph->title->SetFont($font, FS_BOLD, 12);

// The pie plot
$p1 = new PiePlot($data);
$p1->SetTheme('pastel');

// Move center of pie to the left to make better room
// for the legend
$p1->SetCenter(0.26,0.55);

// Label font and color setup
$p1->SetFont($font, FS_BOLD);
$p1->SetFontColor("black");

// Use absolute values (type==1)
$p1->SetLabelType(1);

// Label format
$p1->SetLabelFormat("%.5f");

// Size of pie in fraction of the width of the graph
$p1->SetSize(0.3);

// Legends
$p1->SetLegends($labels);
$graph->legend->SetFont($font);
$graph->legend->Pos(0.06,0.10);

$graph->Add($p1);
$graph->Stroke();
?>