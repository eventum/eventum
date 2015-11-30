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

if (!Access::canExportData(Auth::getUserID())) {
    exit;
}

$csv = base64_decode($_POST['csv_data']);

header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
header('Pragma: no-cache');
header('Cache-Control: must-revalidate, post-check=0,pre-check=0');

$filename = uniqid('csv') . '.xls';
$mimetype = 'application/vnd.ms-excel';
$filesize = Misc::countBytes($csv);
Attachment::outputDownload($csv, $filename, $filesize, $mimetype);
