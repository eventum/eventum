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
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// +----------------------------------------------------------------------+

require_once dirname(__FILE__) . '/../init.php';
require_once APP_JPGRAPH_PATH . '/jpgraph.php';
require_once APP_JPGRAPH_PATH . '/jpgraph_pie.php';

Auth::checkAuthentication(APP_COOKIE);

// check to see if the TTF file is available or not
$ttf_font = TTF_DIR . "verdana.ttf";
if (!file_exists($ttf_font)) {
    $font = FF_FONT1;
} else {
    $font = FF_VERDANA;
}

if (isset($_REQUEST['hide_closed'])) {
    $hide_closed = $_REQUEST['hide_closed'];
} else {
    $hide_closed = false;
}

// Some data
$colors = array();
if ($_GET["plot"] == "status") {
    $fake = false;
    $rgb = new RGB($fake);
    $data = Stats::getAssocStatus($hide_closed);
    $graph_title = ev_gettext("Issues by Status");
    foreach ($data as $sta_title => $trash) {
        $sta_id = Status::getStatusID($sta_title);
        $status_details = Status::getDetails($sta_id);
        if (!isset($rgb->rgb_table[$status_details['sta_color']])) {
            $colors = array();
            break;
        }
        $colors[] = $status_details['sta_color'];
    }
} elseif ($_GET["plot"] == "release") {
    $data = Stats::getAssocRelease($hide_closed);
    $graph_title = ev_gettext("Issues by Release");
} elseif ($_GET["plot"] == "priority") {
    $data = Stats::getAssocPriority($hide_closed);
    $graph_title = ev_gettext("Issues by Priority");
} elseif ($_GET["plot"] == "user") {
    $data = Stats::getAssocUser($hide_closed);
    $graph_title = ev_gettext("Issues by Assignment");
} elseif ($_GET["plot"] == "category") {
    $data = Stats::getAssocCategory($hide_closed);
    $graph_title = ev_gettext("Issues by Category");
}
$labels = array();
foreach ($data as $label => $count) {
    $labels[] = $label . ' (' . $count . ')';
}
$data = array_values($data);

// check the values coming from the database and if they are all empty, then
// output a pre-generated 'No Data Available' picture
if ((!Stats::hasData($data)) || ((Auth::getCurrentRole() <= User::getRoleID("Reporter")) && (Project::getSegregateReporters(Auth::getCurrentProject())))) {
	header("Content-type: image/gif");
    readfile(APP_PATH . "/htdocs/images/no_data.gif");
    exit;
}

// A new graph
$graph = new PieGraph(360,200,"auto");

// Setup title
$graph->title->Set($graph_title);
$graph->title->SetFont($font, FS_BOLD, 12);

// The pie plot
$p1 = new PiePlot($data);
if (count($colors) > 0) {
    $p1->SetSliceColors($colors);
} else {
    $p1->SetTheme('pastel');
}

// Move center of pie to the left to make better room
// for the legend
$p1->SetCenter(0.26,0.55);

// Label font and color setup
$p1->SetFont($font, FS_BOLD);
$p1->SetFontColor("black");

// Use absolute values (type==1)
$p1->SetLabelType(1);

// Label format
$p1->SetLabelFormat("%d");

// Size of pie in fraction of the width of the graph
$p1->SetSize(0.3);

// Legends
$p1->SetLegends($labels);
$graph->legend->SetFont($font);
$graph->legend->Pos(0.06,0.27);

$graph->Add($p1);
$graph->Stroke();
