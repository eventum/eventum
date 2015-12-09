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

require_once __DIR__ . '/../init.php';

Auth::checkAuthentication();

$type = isset($_GET['plot']) ? (string) $_GET['plot'] : null;
$hide_closed = isset($_REQUEST['hide_closed']) ? $_REQUEST['hide_closed'] : false;

$plot = new PlotHelper();
$res = $plot->StatsChart($type, $hide_closed);
if (!$res) {
    header('Content-type: image/gif');
    readfile(APP_PATH . '/htdocs/images/no_data.gif');
    exit;
}
