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
// | Authors: Bryan Alsdorf <bryan@mysql.com>                             |
// +----------------------------------------------------------------------+

require_once dirname(__FILE__) . '/../init.php';

Auth::checkAuthentication(APP_COOKIE);

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
            $bad_issues[] = $issues_id;
        }
    }

    if (count($bad_issues)) {
        return $_REQUEST['form_name'] . ':' . $_REQUEST['field_name'] . ':' . join(', ', $bad_issues);
    } else {
        return $_REQUEST['form_name'] . ':' . $_REQUEST['field_name'] . ':' . 'ok';
    }
}
