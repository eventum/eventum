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
// | Authors: Bryan Alsdorf <bryan@mysql.com>                             |
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

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
