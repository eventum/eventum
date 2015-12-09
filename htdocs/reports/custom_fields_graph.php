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

if (!empty($_REQUEST['start']['Year']) && !empty($_REQUEST['start']['Month']) && !empty($_REQUEST['start']['Day'])) {
    $start = implode('-', $_REQUEST['start']);
} else {
    $start = false;
}

if (!empty($_REQUEST['end']['Year']) && !empty($_REQUEST['end']['Month']) && !empty($_REQUEST['end']['Day'])) {
    $end = implode('-', $_REQUEST['end']);
} else {
    $end = false;
}

$custom_field = isset($_GET['custom_field']) ? $_GET['custom_field'] : null;
$custom_options = isset($_GET['custom_options']) ? $_GET['custom_options'] : null;
$group_by = isset($_GET['group_by']) ? $_GET['group_by'] : null;
$interval = isset($_REQUEST['interval']) ? $_REQUEST['interval'] : null;
$type = isset($_GET['type']) ? $_GET['type'] : null;

$plot = new PlotHelper();
$res = $plot->CustomFieldGraph($type, $custom_field, $custom_options, $group_by, $start, $end, $interval);
if (!$res) {
    header('Location: ' . APP_RELATIVE_URL . '/images/no_data.gif');
    exit;
}
