#!/usr/bin/php
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
    echo 'Error: Another instance of the script is still running. ' .
                "If this is not accurate, you may fix it by running this script with '--fix-lock' " .
                "as the only parameter.\n";
    exit;
}

if (in_array('--debug', $argv)) {
    Reminder::$debug = true;
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
