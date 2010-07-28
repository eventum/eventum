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
    $issues = @explode(',', $_REQUEST['values']);
    if ($_REQUEST['check_project'] == 0) {
        $check_project = false;
    } else {
        $check_project = true;
    }

    $bad_issues = array();
    if (count($issues) > 0) {
        for ($i = 0; $i < count($issues); $i++) {
            $issue_id = $issues[$i];
            if ((($issue_id != '') && (!Issue::exists($issue_id, $check_project))) ||
                ((isset($_REQUEST['exclude_issue'])) && ($_REQUEST['exclude_issue'] == $issue_id)) ||
                ((isset($_REQUEST['exclude_duplicates'])) && ($_REQUEST['exclude_duplicates'] == 1) && (Issue::isDuplicate($issue_id) ))) {
                $bad_issues[] = $issues[$i];
            }
        }
    }
    if (count($bad_issues) > 0) {
        return $_REQUEST['form_name'] . ':' . $_REQUEST['field_name'] . ':' . join(', ', $bad_issues);
    } else {
        return $_REQUEST['form_name'] . ':' . $_REQUEST['field_name'] . ':' . 'ok';
    }
}
