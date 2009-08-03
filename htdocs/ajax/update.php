<?
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2009 Sun Microsystem Inc.                       |
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
//
// @(#) $Id: update.php 3868 2009-03-30 00:22:35Z raul $

require_once(dirname(__FILE__) . '/../../init.php');
require_once(APP_INC_PATH . "/class.auth.php");
require_once(APP_INC_PATH . "/class.issue.php");

// check login
Auth::checkAuthentication(APP_COOKIE);

// check if correct issue id was sent
if (!is_numeric($_POST['issue_id']) || !Issue::exists($_POST['issue_id'])) {
    exit;
}

$usr_id = Auth::getUserID();

// check if user role is above "Standard User"
if (User::getRoleByUser($usr_id, Issue::getProjectID($issue_id)) < User::getRoleID("Standard User")) {
	exit;
}

// check if user can acess the issue
if (!Issue::canAccess($_POST['issue_id'], $usr_id)) {
	exit;
}

switch ($_POST['fieldName']) {
    case 'expected_resolution_date':
        $day = (int)$_POST['day'];
        $month = (int)$_POST['month'];
        $year = (int)$_POST['year'];

        if ($day == 0 && $month == 1 && $year == 0) {
            // clear button
            $date = null;
        } else {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
        }

        if (Issue::updateField($_POST['issue_id'], $_POST['fieldName'], $date) !== -1) {
            if (!is_null($date)) {
                echo Date_Helper::getSimpleDate(sprintf('%04d-%02d-%02d', $year, $month, $day), false);
            }
        } else {
            echo 'update failed';
        }

        exit;
    break;

    default:
        die('object type not supported');
    break;
}
