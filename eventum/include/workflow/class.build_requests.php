<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005 MySQL AB                              |
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
//

include_once(APP_INC_PATH . "workflow/class.abstract_workflow_backend.php");

/**
 * Workflow for build requests project.
 * 
 * @author  Bryan Alsdorf <bryan@mysql.com>
 */
class Build_Requests_Workflow_Backend extends Abstract_Workflow_Backend
{

    /**
     * Called when a new issue is created. If the issue was created from an email,
     * this will add the sender of the email (if an eventum user) to the
     * authorized repliers list.
     *
     * @param   integer $prj_id The projectID
     * @param   integer $issue_id The ID of the issue.
     * @param   boolean $has_TAM If this issue has a technical account manager.
     * @param   boolean $has_RR If Round Robin was used to assign this issue.
     */
    function handleNewIssue($prj_id, $issue_id, $has_TAM, $has_RR)
    {
        // assign to correct person, depending on product
        $product = Custom_Field::getDisplayValue($issue_id, Custom_Field::getIDByTitle("Product"));
        
        $current_assignments = Issue::getAssignedUserIDs($issue_id);

        if (count($current_assignments) < 1) {
            $assignments = array();
            if ($product == "Connector/J") {
                $assignments[] = User::getUserIDByEmail("mark@mysql.com");
            } elseif ($product == "Connector/ODBC") {
                $assignments[] = User::getUserIDByEmail("sinisa@mysql.com");
                $assignments[] = User::getUserIDByEmail("bogdan@mysql.com");
            }
            foreach ($assignments as $assignee) {
                Issue::addUserAssociation(Auth::getUserID(), $issue_id, $assignee);
            }
        }
    }
}
?>