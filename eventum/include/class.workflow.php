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
// | Authors: Bryan Alsdorf <bryan@mysql.com>                             |
// +----------------------------------------------------------------------+
//

class Workflow
{

    /**
     * Returns a list of backends available
     * 
     * @access  public
     * @return  array An array of workflow backends
     */
    function getBackendList()
    {
        $files = Misc::getFileList(APP_INC_PATH . "workflow");
        $list = array();
        for ($i = 0; $i < count($files); $i++) {
            // make sure we only list the customer backends
            if (preg_match('/^class\./', $files[$i])) {
                // display a prettyfied backend name in the admin section
                preg_match('/class\.(.*)\.php/', $files[$i], $matches);
                if ($matches[1] == 'abstract_workflow_backend') {
                    continue;
                }
                $name = ucwords(str_replace('_', ' ', $matches[1]));
                $list[$files[$i]] = $name;
            }
        }
        return $list;
    }


    /**
     * Returns the name of the workflow backend for the specified project.
     *
     * @access  public
     * @param   integer $prj_id The id of the project to lookup.
     * @return  string The name of the customer backend.
     */
    function _getBackendNameByProject($prj_id)
    {
        static $backends;
        
        if (isset($backends[$prj_id])) {
            return $backends[$prj_id];
        }
        
        $stmt = "SELECT
                    prj_id,
                    prj_workflow_backend
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project
                 ORDER BY
                    prj_id";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return '';
        } else {
            $backends = $res;
            return @$backends[$prj_id];
        }
    }


    /**
     * Includes the appropriate workflow backend class associated with the
     * given project ID, instantiates it and returns the class.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  boolean
     */
    function &_getBackend($prj_id)
    {
        static $setup_backends;

        if (empty($setup_backends[$prj_id])) {
            $backend_class = Workflow::_getBackendNameByProject($prj_id);
            if (empty($backend_class)) {
                return false;
            }
            $file_name_chunks = explode(".", $backend_class);
            $class_name = $file_name_chunks[1] . "_Workflow_Backend";
            
            include_once(APP_INC_PATH . "workflow/$backend_class");
            
            $setup_backends[$prj_id] = new $class_name;
        }
        return $setup_backends[$prj_id];
    }


    /**
     * Checks whether the given project ID is setup to use workflow integration
     * or not.
     *
     * @access  public
     * @param   integer integer $prj_id The project ID
     * @return  boolean
     */
    function hasWorkflowIntegration($prj_id)
    {
        $backend = Workflow::_getBackendNameByProject($prj_id);
        if (empty($backend)) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * Is called when an issue is updated.
     *
     * @param   integer $prj_id The project ID.
     * @param   integer $issue_id The ID of the issue.
     * @param   integer $usr_id The ID of the user.
     * @param   array $old_details The old details of the issues.
     * @param   array $changes The changes that were applied to this issue (the $HTTP_POST_VARS)
     */
    function handleIssueUpdated($prj_id, $issue_id, $usr_id, $old_details, $changes)
    {
        if (!Workflow::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& Workflow::_getBackend($prj_id);
        return $backend->handleIssueUpdated($prj_id, $issue_id, $usr_id, $old_details, $changes);
    }


    /**
     * Called when an issue is locked.
     *
     * @param   integer $prj_id The projectID
     * @param   integer $issue_id The ID of the issue.
     * @param   integer $usr_id The id of the user who locked the issue.
     */
    function handleLock($prj_id, $issue_id, $usr_id)
    {
        if (!Workflow::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& Workflow::_getBackend($prj_id);
        return $backend->handleLock($prj_id, $issue_id, $usr_id);
    }


    /**
     * Called when a file is attached to an issue..
     *
     * @param   integer $prj_id The projectID
     * @param   integer $issue_id The ID of the issue.
     * @param   integer $usr_id The id of the user who locked the issue.
     */
    function handleAttachment($prj_id, $issue_id, $usr_id)
    {
        if (!Workflow::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& Workflow::_getBackend($prj_id);
        return $backend->handleAttachment($prj_id, $issue_id, $usr_id);
    }


    /**
     * Called when the priority of an issue changes.
     *
     * @param   integer $prj_id The projectID
     * @param   integer $issue_id The ID of the issue.
     * @param   integer $usr_id The id of the user who locked the issue.
     * @param   array $old_details The old details of the issue.
     * @param   array $changes The changes that were applied to this issue (the $HTTP_POST_VARS)
     */
    function handlePriorityChange($prj_id, $issue_id, $usr_id, $old_details, $changes)
    {
        if (!Workflow::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& Workflow::_getBackend($prj_id);
        return $backend->handlePriorityChange($prj_id, $issue_id, $usr_id, $old_details, $changes);
    }


    /**
     * Called when an email is blocked.
     *
     * @param   integer $prj_id The projectID
     * @param   integer $issue_id The ID of the issue.
     * @param   array $email_details Details of the issue
     * @param   string $type What type of blocked email this is.
     */
    function handleBlockedEmail($prj_id, $issue_id, $email_details, $type)
    {
        if (!Workflow::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& Workflow::_getBackend($prj_id);
        return $backend->handleBlockedEmail($prj_id, $issue_id, $email_details, $type);
    }


    /**
     * Called when the assignment on an issue changes.
     *
     * @param   integer $prj_id The projectID
     * @param   integer $issue_id The ID of the issue.
     * @param   integer $usr_id The id of the user who locked the issue.
     * @param   array $issue_details The old details of the issue.
     * @param   array $new_assignees The new assignees of this issue.
     * @param   boolean $remote_assignment If this issue was remotely assigned.
     */
    function handleAssignmentChange($prj_id, $issue_id, $usr_id, $issue_details, $new_assignees, $remote_assignment = false)
    {
        if (!Workflow::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& Workflow::_getBackend($prj_id);
        return $backend->handleAssignmentChange($prj_id, $issue_id, $usr_id, $issue_details, $new_assignees, $remote_assignment);
    }


    /**
     * Called when a new issue is created.
     *
     * @param   integer $prj_id The projectID
     * @param   integer $issue_id The ID of the issue.
     * @param   boolean $has_TAM If this issue has a technical account manager.
     * @param   boolean $has_RR If Round Robin was used to assign this issue.
     */
    function handleNewIssue($prj_id, $issue_id, $has_TAM, $has_RR)
    {
        if (!Workflow::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& Workflow::_getBackend($prj_id);
        return $backend->handleNewIssue($prj_id, $issue_id, $has_TAM, $has_RR);
    }


    /**
     * Called when an email is recieved.
     *
     * @param   integer $prj_id The projectID
     * @param   integer $issue_id The ID of the issue.
     * @param   array $message An array containing the new email
     */
    function handleNewEmail($prj_id, $issue_id, $message)
    {
        if (!Workflow::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& Workflow::_getBackend($prj_id);
        return $backend->handleNewEmail($prj_id, $issue_id, $message);
    }


    /**
     * Called when an email is manually associated with an existing issue.
     *
     * @param   integer $prj_id The projectID
     * @param   integer $issue_id The ID of the issue.
     */
    function handleManualEmailAssociation($prj_id, $issue_id)
    {
        if (!Workflow::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& Workflow::_getBackend($prj_id);
        return $backend->handleManualEmailAssociation($prj_id, $issue_id);
    }


    /**
     * Called when a note is routed.
     *
     * @param   integer $prj_id The projectID
     * @param   integer $issue_id The ID of the issue.
     * @param   boolean $closing If the issue is being closed
     */
    function handleNewNote($prj_id, $issue_id, $closing = false)
    {
        if (!Workflow::hasWorkflowIntegration($prj_id)) {
            return;
        }
        $backend =& Workflow::_getBackend($prj_id);
        return $backend->handleNewNote($prj_id, $issue_id, $closing);
    }
}


?>