<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004 MySQL AB                                    |
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
//
// @(#) $Id: s.check_reminders.php 1.1 04/01/07 15:50:18-00:00 jpradomaia $
//

include_once("../config.inc.php");
include_once(APP_INC_PATH . "db_access.php");
include_once(APP_INC_PATH . "class.reminder.php");
include_once(APP_INC_PATH . "class.reminder_action.php");
include_once(APP_INC_PATH . "class.reminder_condition.php");

/*
1 - Get list of reminders with all of its actions
2 - Loop through each reminder level and build the SQL query
3 - If query returns TRUE, then run the appropriate action
4 - Get the list of actions
5 - Calculate which action need to be performed, if any
6 - Perform action
7 - Continue to next reminder level
**/
$triggered_issues = array();

$reminders = Reminder::getList();
for ($i = 0; $i < count($reminders); $i++) {
    // for each action, get the conditions and see if it triggered any issues
    $found = 0;
    for ($y = 0; $y < count($reminders[$i]['actions']); $y++) {
        $conditions = Reminder_Condition::getList($reminders[$i]['actions'][$y]['rma_id']);
        if (count($conditions) == 0) {
            continue;
        }
        $issues = Reminder::getTriggeredIssues($reminders[$i], $conditions);
        if (count($issues) > 0) {
            for ($z = 0; $z < count($issues); $z++) {
                // only perform one action per issue id
                if (in_array($issues[$z], $triggered_issues)) {
                    continue;
                }
                $triggered_issues[] = $issues[$z];
                if (Reminder::isDebug()) {
                    echo "Triggered Action '" . $reminders[$i]['actions'][$y]['rma_title'] . "' for issue #" . $issues[$z] . "\n";
                }
                Reminder_Action::perform($issues[$z], $reminders[$i], $reminders[$i]['actions'][$y]);
            }
            // perform just one action per reminder
            break;
        } else {
            if (Reminder::isDebug()) {
                echo "No triggered issues for action '" . $reminders[$i]['actions'][$y]['rma_title'] . "'\n";
            }
        }
    }
}
?>