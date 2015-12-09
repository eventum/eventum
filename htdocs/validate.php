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

$valid_functions = array('validateIssueNumbers');
$action = Misc::escapeString($_REQUEST['action']);
if (in_array($action, $valid_functions)) {
    echo $action();
} else {
    echo "ERROR: Unable to call function $action";
}
exit;

function validateIssueNumbers()
{
    $issues = explode(',', $_REQUEST['values']);
    $check_project = $_REQUEST['check_project'] != 0;
    $exclude_issue = isset($_REQUEST['exclude_issue']) ? $_REQUEST['exclude_issue'] : null;
    $exclude_duplicates = isset($_REQUEST['exclude_duplicates']) ? $_REQUEST['exclude_duplicates'] == 1 : false;
    $bad_issues = array();

    foreach ($issues as $issue_id) {
        if (
            ($issue_id != '' && !Issue::exists($issue_id, $check_project)) ||
            ($exclude_issue == $issue_id) ||
            ($exclude_duplicates && Issue::isDuplicate($issue_id))
            ) {
            $bad_issues[] = $issue_id;
        }
    }

    if (count($bad_issues)) {
        return implode(', ', $bad_issues);
    } else {
        return 'ok';
    }
}
