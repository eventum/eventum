#!/usr/bin/php
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

require_once dirname(__FILE__).'/../init.php';

// if requested, clear the lock
if (in_array('--fix-lock', $argv)) {
    if (Lock::release('check_reminders')) {
        echo "The lock file was removed successfully.\n";
    }
    exit;
}

// acquire a lock to prevent multiple scripts from
// running at the same time
if (!Lock::acquire('check_reminders')) {
    echo "Error: Another instance of the script is still running. " .
                "If this is not accurate, you may fix it by running this script with '--fix-lock' " .
                "as the only parameter.\n";
    exit;
}

/*
1 - Get list of reminders with all of its actions
2 - Loop through each reminder level and build the SQL query
3 - If query returns TRUE, then run the appropriate action
4 - Get the list of actions
5 - Calculate which action need to be performed, if any
6 - Avoid repeating reminder actions, so first check if the last triggered action is the same one as "now"
7 - Perform action
8 - Continue to next reminder level
**/
$triggered_issues = array();

$reminders = Reminder::getList();
$weekday = date('w');
foreach ($reminders as $reminder) {
    // if this is the weekend and this reminder isn't supposed to run on weekends skip
    if ($reminder['rem_skip_weekend'] == 1 && in_array($weekday, array(0, 6))) {
        if (Reminder::isDebug()) {
            echo "Skipping Reminder '" . $reminder['rem_title'] . "' due to weekend exclusion\n";
        }
        continue;
    }

    // for each action, get the conditions and see if it triggered any issues
    $found = 0;
    foreach ($reminder['actions'] as $action) {
        if (Reminder::isDebug()) {
            echo "Processing Reminder Action '" . $action['rma_title'] . "'\n";
        }
        $conditions = Reminder_Condition::getList($action['rma_id']);
        if (count($conditions) == 0) {
            if (Reminder::isDebug()) {
                echo "  - Skipping Reminder because there were no reminder conditions found\n";
            }
            continue;
        }
        $issues = Reminder::getTriggeredIssues($reminder, $conditions);
        // avoid repeating reminder actions, so get the list of issues
        // that were last triggered with this reminder action ID
        $repeat_issues = Reminder_Action::getRepeatActions($issues, $action['rma_id']);
        if (count($repeat_issues) > 0) {
            // add the repeated issues to the list of already triggered
            // issues, so they get ignored for the next reminder actions
            foreach ($repeat_issues as $issue) {
                if (Reminder::isDebug()) {
                    echo "  - Adding repeated issue '" . $issue . "' to the list of already triggered issues\n";
                }
                $triggered_issues[] = $issue;
            }
        }
        if (count($issues) > 0) {
            foreach ($issues as $issue) {
                if (Reminder::isDebug()) {
                    echo "  - Processing issue '" . $issue . "'\n";
                }
                // only perform one action per issue id
                if (in_array($issue, $triggered_issues)) {
                    if (Reminder::isDebug()) {
                        echo "  - Ignoring issue '" . $issue . "' because it was found in the list of already triggered issues\n";
                    }
                    continue;
                }
                $triggered_issues[] = $issue;
                if (Reminder::isDebug()) {
                    echo "  - Triggered Action '" . $action['rma_title'] . "' for issue #" . $issue . "\n";
                }
                Reminder_Action::perform($issue, $reminder, $action);
            }
        } else {
            if (Reminder::isDebug()) {
                echo "  - No triggered issues for action '" . $action['rma_title'] . "'\n";
            }
        }
    }
}

// release the lock
Lock::release('check_reminders');
