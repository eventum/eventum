<?php

/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

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
