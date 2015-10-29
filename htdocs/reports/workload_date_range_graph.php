<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2015 Eventum Team.                              |
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
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+

require_once __DIR__ . '/../../init.php';

Auth::checkAuthentication();

if (!Access::canAccessReports(Auth::getUserID())) {
    echo 'Invalid role';
    exit;
}

$interval = isset($_REQUEST['interval']) ? $_REQUEST['interval'] : null;
$graph = isset($_REQUEST['graph']) ? $_REQUEST['graph'] : null;
$start_date = isset($_REQUEST['start_date']) ? $_REQUEST['start_date'] : null;
$end_date = isset($_REQUEST['end_date']) ? $_REQUEST['end_date'] : null;
$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : null;

$plot = new PlotHelper();
$res = $plot->WorkloadDateRangeGraph($graph, $type, $start_date, $end_date, $interval);
if (!$res) {
    header('Location: ../images/no_data.gif');
    exit;
}
