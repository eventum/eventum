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
// @(#) $Id: s.xmlrpc.php 1.10 03/11/17 16:04:48-00:00 jpradomaia $
//
include_once("../config.inc.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.issue.php");
include_once(APP_INC_PATH . "class.note.php");
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.project.php");
include_once(APP_INC_PATH . "class.status.php");
include_once(APP_INC_PATH . "db_access.php");
error_reporting(0);
include_once(APP_PEAR_PATH . "XML_RPC/Server.php");

$addIssue_sig = array(array($XML_RPC_String, $XML_RPC_Int, $XML_RPC_String, $XML_RPC_String));
$addIssue_doc = "Creates a new issue in the database. Returns a boolean value indicating whether it worked or not.";
function addIssue($p)
{
    // parameters being passed to this service
    $prj_id = XML_RPC_decode($p->getParam(0));
    $summary = XML_RPC_decode($p->getParam(1));
    $description = XML_RPC_decode($p->getParam(2));
    // get the preferences
    if (!Project::exists($prj_id)) {
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Project ID $prj_id does not exist");
    } else {
        $project = Project::getDetails($prj_id);
        $project_prefs = Project::getRemoteInvocationOptions($prj_id);
        if ($project["remote_invocation"] != "enabled") {
            return new XML_RPC_Response(0, $XML_RPC_erruser+2, "Project '" . $project["prj_title"] . "' has remote invocation disabled");
        } else {
            $category = $project_prefs["category"];
            $priority = $project_prefs["priority"];
            $users = $project_prefs["users"];
            $reporter = $project_prefs["reporter"];
            // check if there is already one issue with the same summary
            $issue_id = Issue::getIssueID($summary);
            if ($issue_id == 0) {
                $res = Issue::addRemote($prj_id, $category, $priority, $reporter, $users, $summary, $description);
            } else {
                // add a note to the existing issue
                $res = Note::addRemote($issue_id, $reporter, $description);
            }
            if (PEAR::isError($res)) {
                return new XML_RPC_Response(0, $XML_RPC_erruser+3, "An error occurred while trying to create the new issue");
            } else {
                return new XML_RPC_Response(new XML_RPC_Value("The new issue (ID: $res) was created successfully", "string"));
            }
        }
    }
}

function getProjectList()
{
    include_once(APP_INC_PATH . "class.project.php");
    $res = Project::getRemoteAssocList();
    if (empty($res)) {
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "There are currently no projects setup for remote invocation");
    } else {
        $structs = array();
        foreach ($res as $key => $value) {
            $structs[] = new XML_RPC_Value(array(
                "id"    => new XML_RPC_Value($key, "int"),
                "title" => new XML_RPC_Value($value)
            ), "struct");
        }
        return new XML_RPC_Response(new XML_RPC_Value($structs, "array"));
    }
}

$getDeveloperList_sig = array(array($XML_RPC_Array, $XML_RPC_Int));
function getDeveloperList($p)
{
    $prj_id = XML_RPC_decode($p->getParam(0));

    $res = Project::getRemoteAssocList();
    if (empty($res)) {
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "There are currently no projects setup for remote invocation");
    }
    // check if this project allows remote invocation
    if (!in_array($prj_id, array_keys($res))) {
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "This project does not allow remote invocation");
    }

    $res = Project::getUserEmailAssocList($prj_id, 'active', User::getRoleID('Reporter'));
    if (empty($res)) {
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "There are currently no users associated with the given project");
    } else {
        return new XML_RPC_Response(XML_RPC_Encode(array_values($res)));
    }
}

$getSimpleIssueDetails_sig = array(array($XML_RPC_Struct, $XML_RPC_Int));
function getSimpleIssueDetails($p)
{
    $issue_id = XML_RPC_decode($p->getParam(0));

    $details = Issue::getDetails($issue_id);
    if (empty($details)) {
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Issue #$issue_id could not be found");
    }

    return new XML_RPC_Response(new XML_RPC_Value(array(
                "summary"  => new XML_RPC_Value($details['iss_summary'])
            ), "struct"));
}

$getOpenIssues_sig = array(array($XML_RPC_Array, $XML_RPC_String, $XML_RPC_Boolean));
function getOpenIssues($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $show_all_issues = XML_RPC_decode($p->getParam(1));

    $res = Issue::getOpenIssues($email, $show_all_issues);
    if (empty($res)) {
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "There are currently no open issues");
    } else {
        $structs = array();
        for ($i = 0; $i < count($res); $i++) {
            $structs[] = new XML_RPC_Value(array(
                "issue_id"   => new XML_RPC_Value($res[$i]['iss_id'], "int"),
                "summary"    => new XML_RPC_Value($res[$i]['iss_summary']),
                'assignment' => new XML_RPC_Value($res[$i]['usr_full_name']),
                'status'     => new XML_RPC_Value($res[$i]['sta_title'])
            ), "struct");
        }
        return new XML_RPC_Response(new XML_RPC_Value($structs, "array"));
    }
}

$isValidLogin_sig = array(array($XML_RPC_String, $XML_RPC_String, $XML_RPC_String));
function isValidLogin($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $password = XML_RPC_decode($p->getParam(1));

    if (!Auth::isCorrectPassword($email, $password)) {
        $is_valid = 'no';
    } else {
        $is_valid = 'yes';
    }

    return new XML_RPC_Response(new XML_RPC_Value($is_valid, $XML_RPC_String));
}

$getUserAssignedProjects_sig = array(array($XML_RPC_Array, $XML_RPC_String));
function getUserAssignedProjects($p)
{
    $email = XML_RPC_decode($p->getParam(0));

    $usr_id = User::getUserIDByEmail($email);
    $res = Project::getRemoteAssocListByUser($usr_id);
    if (empty($res)) {
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "You are not assigned to any projects at this moment");
    } else {
        $structs = array();
        foreach ($res as $prj_id => $prj_title) {
            $structs[] = new XML_RPC_Value(array(
                "id"   => new XML_RPC_Value($prj_id, "int"),
                "title"    => new XML_RPC_Value($prj_title)
            ), "struct");
        }
        return new XML_RPC_Response(new XML_RPC_Value($structs, "array"));
    }
}

$getStatusList_sig = array(array($XML_RPC_Array, $XML_RPC_Int));
function getStatusList($p)
{
    $prj_id = XML_RPC_decode($p->getParam(0));

    $res = Status::getAssocStatusList($prj_id);
    if (empty($res)) {
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "No statuses could be found at this moment");
    } else {
        $values = array();
        foreach ($res as $key => $value) {
            $values[] = new XML_RPC_Value($value, "string");
        }
        return new XML_RPC_Response(new XML_RPC_Value($values, "array"));
    }
}

$getIssueDetails_sig = array(array($XML_RPC_Struct, $XML_RPC_Int));
function getIssueDetails($p)
{
    $issue_id = XML_RPC_decode($p->getParam(0));

    $res = Issue::getDetails($issue_id);
    if (empty($res)) {
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Issue #$issue_id could not be found");
    } else {
        return new XML_RPC_Response(XML_RPC_Encode($res));
    }
}

$getTimeTrackingCategories_sig = array(array($XML_RPC_Struct));
function getTimeTrackingCategories()
{
    $res = Time_Tracking::getAssocCategories();
    if (empty($res)) {
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "No time tracking categories could be found");
    } else {
        return new XML_RPC_Response(XML_RPC_Encode($res));
    }
}

$recordTimeWorked_sig = array(array($XML_RPC_String, $XML_RPC_Int, $XML_RPC_String, $XML_RPC_Int, $XML_RPC_String, $XML_RPC_Int));
function recordTimeWorked($p)
{
    $issue_id = XML_RPC_decode($p->getParam(0));
    $email = XML_RPC_decode($p->getParam(1));
    $cat_id = XML_RPC_decode($p->getParam(2));
    $summary = XML_RPC_decode($p->getParam(3));
    $time_spent = XML_RPC_decode($p->getParam(4));

    $usr_id = User::getUserIDByEmail($email);
    $res = Time_Tracking::recordRemoteEntry($issue_id, $usr_id, $cat_id, $summary, $time_spent);
    if ($res == -1) {
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Could not record the time tracking entry");
    } else {
        return new XML_RPC_Response(XML_RPC_Encode('OK'));
    }
}

$setIssueStatus_sig = array(array($XML_RPC_String, $XML_RPC_Int, $XML_RPC_String, $XML_RPC_String));
function setIssueStatus($p)
{
    $issue_id = XML_RPC_decode($p->getParam(0));
    $email = XML_RPC_decode($p->getParam(1));
    $new_status = XML_RPC_decode($p->getParam(2));

    $usr_id = User::getUserIDByEmail($email);
    $res = Issue::setRemoteStatus($issue_id, $usr_id, $new_status);
    if ($res == -1) {
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Could not set the status to issue #$issue_id");
    } else {
        return new XML_RPC_Response(XML_RPC_Encode('OK'));
    }
}

$assignIssue_sig = array(array($XML_RPC_String, $XML_RPC_Int, $XML_RPC_Int, $XML_RPC_String, $XML_RPC_String));
function assignIssue($p)
{
    $issue_id = XML_RPC_decode($p->getParam(0));
    $project_id = XML_RPC_decode($p->getParam(1));
    $email = XML_RPC_decode($p->getParam(2));
    $developer = XML_RPC_decode($p->getParam(3));

    $usr_id = User::getUserIDByEmail($email);
    $assignee = User::getUserIDByEmail($developer);
    // check if the assignee is even allowed to be in the given project
    $projects = Project::getRemoteAssocListByUser($assignee);
    if (!in_array($project_id, array_keys($projects))) {
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "The selected developer is not permitted in the project associated with issue #$issue_id");
    }

    $res = Issue::remoteAssign($issue_id, $usr_id, $assignee);
    if ($res == -1) {
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Could not assign issue #$issue_id to $developer");
    } else {
        return new XML_RPC_Response(XML_RPC_Encode('OK'));
    }
}

$lockIssue_sig = array(array($XML_RPC_String, $XML_RPC_Int, $XML_RPC_String, $XML_RPC_String));
function lockIssue($p)
{
    $issue_id = XML_RPC_decode($p->getParam(0));
    $email = XML_RPC_decode($p->getParam(1));
    $force_lock = XML_RPC_decode($p->getParam(2));

    $usr_id = User::getUserIDByEmail($email);
    $res = Issue::remoteLock($issue_id, $usr_id, $force_lock);
    if ($res == -1) {
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Could not lock and assign issue #$issue_id");
    } elseif ($res == -2) {
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Issue #$issue_id is already locked by you");
    } elseif ($res == -3) {
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Issue #$issue_id is already locked by another developer. Run with --force to override this security check");
    } else {
        return new XML_RPC_Response(XML_RPC_Encode('OK'));
    }
}

$services = array(
    "lockIssue" => array(
        'function'  => "lockIssue",
        'signature' => $lockIssue_sig
    ),
    "assignIssue" => array(
        'function'  => "assignIssue",
        'signature' => $assignIssue_sig
    ),
    "setIssueStatus" => array(
        'function'  => "setIssueStatus",
        'signature' => $setIssueStatus_sig
    ),
    "recordTimeWorked" => array(
        'function'  => "recordTimeWorked",
        'signature' => $recordTimeWorked_sig
    ),
    "getTimeTrackingCategories" => array(
        'function'  => "getTimeTrackingCategories",
        'signature' => $getTimeTrackingCategories_sig
    ),
    "getIssueDetails" => array(
        'function'  => "getIssueDetails",
        'signature' => $getIssueDetails_sig
    ),
    "getStatusList" => array(
        'function'  => "getStatusList",
        'signature' => $getStatusList_sig
    ),
    "getUserAssignedProjects" => array(
        'function'  => "getUserAssignedProjects",
        'signature' => $getUserAssignedProjects_sig
    ),
    "isValidLogin" => array(
        'function'  => "isValidLogin",
        'signature' => $isValidLogin_sig
    ),
    "getOpenIssues" => array(
        'function'  => "getOpenIssues",
        'signature' => $getOpenIssues_sig
    ),
    "getSimpleIssueDetails" => array(
        'function'  => "getSimpleIssueDetails",
        'signature' => $getSimpleIssueDetails_sig
    ),
    "getDeveloperList" => array(
        "function"  => "getDeveloperList",
        'signature' => $getDeveloperList_sig
    ),
    "getProjectList" => array(
        "function" => "getProjectList"
    ),
    "addIssue" => array(
        "function"  => "addIssue",
        "signature" => $addIssue_sig,
        "docstring" => $addIssue_doc
    )
);
$server = new XML_RPC_Server($services);
?>