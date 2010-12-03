<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
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
// | Authors: Raul Raat <raul.raat@delfi.ee>                              |
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

require_once dirname(__FILE__) . '/../../init.php';

// check login
Auth::checkAuthentication(APP_COOKIE);

$field_name = !empty($_POST['field_name']) ? $_POST['field_name'] : null;
$issue_id = !empty($_POST['issue_id']) ? (int )$_POST['issue_id'] : null;

// check if correct issue id was sent
if (!$issue_id || !Issue::exists($issue_id)) {
    die("Invalid issue_id");
}

$usr_id = Auth::getUserID();

// check if user role is above "Standard User"
if (User::getRoleByUser($usr_id, Issue::getProjectID($issue_id)) < User::getRoleID("Standard User")) {
    die("Forbidden");
}

// check if user can acess the issue
if (!Issue::canAccess($issue_id, $usr_id)) {
    die("Forbidden");
}

switch ($field_name) {
    case 'expected_resolution_date':
        $day = Misc::escapeInteger($_POST['day']);
        $month = Misc::escapeInteger($_POST['month']);
        $year = Misc::escapeInteger($_POST['year']);

        if ($day == 0 && $month == 1 && $year == 0) {
            // clear button
            $date = null;
        } else {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
        }

        $res = Issue::setExpectedResolutionDate($issue_id, $date);
        if ($res == -1) {
            die("Update failed");
        }

        if (!is_null($date)) {
            echo Date_Helper::getSimpleDate($date, false);
        }
    break;

    default:
        die("Object type '$field_name' not supported");
    break;
}
