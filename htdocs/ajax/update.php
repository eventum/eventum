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

// check login
Auth::checkAuthentication();

$field_name = filter_var(!empty($_POST['field_name']) ? $_POST['field_name'] : null, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$issue_id = !empty($_POST['issue_id']) ? (int) $_POST['issue_id'] : null;

// check if correct issue id was sent
if (!$issue_id || !Issue::exists($issue_id)) {
    die('Invalid issue_id');
}

$usr_id = Auth::getUserID();

// check if user role is above "Standard User"
if (User::getRoleByUser($usr_id, Issue::getProjectID($issue_id)) < User::ROLE_USER) {
    die('Forbidden');
}

// check if user can acess the issue
if (!Issue::canAccess($issue_id, $usr_id)) {
    die('Forbidden');
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
            die('Update failed');
        }

        if ($date !== null) {
            echo Date_Helper::getSimpleDate($date, false);
        }
    break;

    default:
        die("Object type '$field_name' not supported");
    break;
}
