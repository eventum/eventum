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
include_once(APP_INC_PATH . "db_access.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.issue.php");
include_once(APP_INC_PATH . "class.note.php");
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.project.php");
include_once(APP_INC_PATH . "class.status.php");
include_once(APP_INC_PATH . "class.authorized_replier.php");
include_once(APP_INC_PATH . "class.report.php");
include_once(APP_INC_PATH . "class.template.php");
error_reporting(0);
include_once(APP_PEAR_PATH . "XML_RPC/Server.php");

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
                "summary"     => new XML_RPC_Value($details['iss_summary']),
                "status"      => new XML_RPC_Value(@$details['sta_title']),
                "assignments" => new XML_RPC_Value(@$details["assignments"]),
                "authorized_repliers"   =>  new XML_RPC_Value(@$details['authorized_repliers'])
            ), "struct"));
}

$getOpenIssues_sig = array(array($XML_RPC_Array, $XML_RPC_Int, $XML_RPC_String, $XML_RPC_Boolean, $XML_RPC_String));
function getOpenIssues($p)
{
    $prj_id = XML_RPC_decode($p->getParam(0));
    $email = XML_RPC_decode($p->getParam(1));
    $show_all_issues = XML_RPC_decode($p->getParam(2));
    $status = XML_RPC_decode($p->getParam(3));
    $status_id = Status::getStatusID($status);

    $res = Issue::getOpenIssues($prj_id, $email, $show_all_issues, $status_id);
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

$getIssueDetails_sig = array(array($XML_RPC_Struct, $XML_RPC_Int, $XML_RPC_String));
function getIssueDetails($p)
{
    $issue_id = XML_RPC_decode($p->getParam(0));
    
    createFakeCookie(XML_RPC_decode($p->getParam(1)));
    
    $res = Issue::getDetails($issue_id);
    if (empty($res)) {
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Issue #$issue_id could not be found");
    } else {
        // remove some naughty fields
        unset($res['iss_original_description']);
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
    if (empty($assignee)) {
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Could not find a user with email '$developer'");
    }
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

$addAuthorizedReplier_sig = array(array($XML_RPC_String, $XML_RPC_Int, $XML_RPC_Int, $XML_RPC_String, $XML_RPC_String));
function addAuthorizedReplier($p)
{
    $issue_id = XML_RPC_decode($p->getParam(0));
    $project_id = XML_RPC_decode($p->getParam(1));
    $email = XML_RPC_decode($p->getParam(2));
    $new_replier = XML_RPC_decode($p->getParam(3));
    
    $usr_id = User::getUserIDByEmail($email);
    $replier_usr_id = User::getUserIDByEmail($new_replier);
    
    // if this is an actual user, not just an email address check permissions
    if (!empty($replier_usr_id)) {
        // check if the assignee is even allowed to be in the given project
        $projects = Project::getRemoteAssocListByUser($replier_usr_id);
        if (!in_array($project_id, array_keys($projects))) {
            return new XML_RPC_Response(0, $XML_RPC_erruser+1, "The given user is not permitted in the project associated with issue #$issue_id");
        }
    }
    
    // check if user is already authorized
    if (Authorized_Replier::isAuthorizedReplier($issue_id, $new_replier)) {
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "The given user is already an authorized replier on issue #$issue_id");
    }
    
    $res = Authorized_Replier::remoteAddAuthorizedReplier($issue_id, $usr_id, $new_replier);
    if ($res == -1) {
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Could not add '$new_replier' as an authorized replier to issue #$issue_id");
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

$unlockIssue_sig = array(array($XML_RPC_String, $XML_RPC_Int, $XML_RPC_String));
function unlockIssue($p)
{
    $issue_id = XML_RPC_decode($p->getParam(0));
    $email = XML_RPC_decode($p->getParam(1));

    $usr_id = User::getUserIDByEmail($email);
    $res = Issue::remoteUnlock($issue_id, $usr_id);
    if ($res == -1) {
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Could not unlock issue #$issue_id");
    } elseif ($res == -2) {
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Issue #$issue_id is already unlocked");
    } else {
        return new XML_RPC_Response(XML_RPC_Encode('OK'));
    }
}

$getFileList_sig = array(array($XML_RPC_String, $XML_RPC_Int));
function getFileList($p)
{
    $issue_id = XML_RPC_decode($p->getParam(0));

    $res = Attachment::getList($issue_id);
    if (empty($res)) {
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "No files could be found");
    } else {
        return new XML_RPC_Response(XML_RPC_Encode($res));
    }
}

$getFile_sig = array(array($XML_RPC_String, $XML_RPC_Int));
function getFile($p)
{
    $file_id = XML_RPC_decode($p->getParam(0));

    $res = Attachment::getDetails($file_id);
    if (empty($res)) {
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "The requested file could not be found");
    } else {
        $res['iaf_file'] = base64_encode($res['iaf_file']);
        return new XML_RPC_Response(XML_RPC_Encode($res));
    }
}

$closeIssue_sig = array(array($XML_RPC_String, $XML_RPC_String, $XML_RPC_Int, $XML_RPC_String, $XML_RPC_Boolean, $XML_RPC_String));
function closeIssue($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $usr_id = User::getUserIDByEmail($email);
    $issue_id = XML_RPC_decode($p->getParam(1));
    $new_status = XML_RPC_decode($p->getParam(2));
    $status_id = Status::getStatusID($new_status);
    $notify_customer = XML_RPC_decode($p->getParam(3));
    $note = XML_RPC_decode($p->getParam(4));

    $res = Issue::close($usr_id, $issue_id, $notify_customer, $status_id, $note);
    if ($res == -1) {
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Could not close issue #$issue_id");
    } else {
        return new XML_RPC_Response(XML_RPC_Encode('OK'));
    }
}

$getClosedAbbreviationAssocList_sig = array(array($XML_RPC_String, $XML_RPC_Int));
function getClosedAbbreviationAssocList($p)
{
    $prj_id = XML_RPC_decode($p->getParam(0));

    $res = Status::getClosedAbbreviationAssocList($prj_id);
    return new XML_RPC_Response(XML_RPC_Encode($res));
}

$getAbbreviationAssocList_sig = array(array($XML_RPC_String, $XML_RPC_Int, $XML_RPC_Boolean));
function getAbbreviationAssocList($p)
{
    $prj_id = XML_RPC_decode($p->getParam(0));
    $show_closed = XML_RPC_decode($p->getParam(1));

    $res = Status::getAbbreviationAssocList($prj_id, $show_closed);
    return new XML_RPC_Response(XML_RPC_Encode($res));
}

$getEmailListing_sig = array(array($XML_RPC_Array, $XML_RPC_Int));
function getEmailListing($p)
{
    $issue_id = XML_RPC_decode($p->getParam(0));
    $emails = Support::getEmailsByIssue($issue_id);

    // since xml-rpc has issues, lets base64 encode everything
    if (is_array($emails)) {
        for ($i = 0; $i < count($emails); $i++) {
            foreach ($emails[$i] as $key => $val) {
                $emails[$i][$key] = base64_encode($val);
            }
        }
    }
    return new XML_RPC_Response(XML_RPC_Encode($emails));
}

$getEmail_sig = array(array($XML_RPC_Array, $XML_RPC_Int, $XML_RPC_Int));
function getEmail($p)
{
    $issue_id = XML_RPC_decode($p->getParam(0));
    $email_id = XML_RPC_decode($p->getParam(1));
    $email = Support::getEmailBySequence($issue_id, $email_id);

    // get requested email
    if ((count($email) < 1) || (!is_array($email))) {
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Email #" . $email_id . " does not exist for issue #$issue_id");
    }
    // since xml-rpc has issues, lets base64 encode everything
    foreach ($email as $key => $val) {
        $email[$key] = base64_encode($val);
    }
    return new XML_RPC_Response(XML_RPC_Encode($email));
}

$getNoteListing_sig = array(array($XML_RPC_Array, $XML_RPC_Int, $XML_RPC_String));
function getNoteListing($p)
{
    $issue_id = XML_RPC_decode($p->getParam(0));
    createFakeCookie(XML_RPC_decode($p->getParam(1)));
    $notes = Note::getListing($issue_id);

    // since xml-rpc has issues, lets base64 encode everything
    for ($i = 0; $i < count($notes); $i++) {
        foreach ($notes[$i] as $key => $val) {
            $notes[$i][$key] = base64_encode($val);
        }
    }
    return new XML_RPC_Response(XML_RPC_Encode($notes));
}

$getNote_sig = array(array($XML_RPC_Array, $XML_RPC_Int, $XML_RPC_Int));
function getNote($p)
{
    $issue_id = XML_RPC_decode($p->getParam(0));
    $note_id = XML_RPC_decode($p->getParam(1));
    $note = Note::getNoteBySequence($issue_id, $note_id);
    
    if ((count($note) < 1) || (!is_array($note))) {
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Note #" . $note_id . " does not exist for issue #$issue_id");
    }
    // since xml-rpc has issues, lets base64 encode everything
    foreach ($note as $key => $val) {
        $note[$key] = base64_encode($val);
    }
    return new XML_RPC_Response(XML_RPC_Encode($note));
}

$convertNote_sig = array(array($XML_RPC_String, $XML_RPC_Int, $XML_RPC_Int, $XML_RPC_String, $XML_RPC_String));
function convertNote($p)
{
    $issue_id = XML_RPC_decode($p->getParam(0));
    $note_id = XML_RPC_decode($p->getParam(1));
    $target = XML_RPC_decode($p->getParam(2));
    
    createFakeCookie(XML_RPC_decode($p->getParam(3)), Issue::getProjectID($issue_id));
    $res = Note::convertNote($note_id, $target);
    if ($res) {
        return new XML_RPC_Response(XML_RPC_Encode("OK"));
    } else {
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Error converting note");
    }
}

$mayChangeIssue_sig = array(array($XML_RPC_String, $XML_RPC_Int, $XML_RPC_String));
function mayChangeIssue($p)
{
    $issue_id = XML_RPC_decode($p->getParam(0));
    $email = XML_RPC_decode($p->getParam(1));
    $usr_id = User::getUserIDByEmail($email);

    $assignees = Issue::getAssignedUserIDs($issue_id);
    if (count($assignees) > 0) {
        if (in_array($usr_id, $assignees)) {
            return new XML_RPC_Response(XML_RPC_Encode("yes"));
        } else {
            return new XML_RPC_Response(XML_RPC_Encode("no"));
        }
    } else {
        return new XML_RPC_Response(XML_RPC_Encode("yes"));
    }
}

$getWeeklyReport_sig = array(array($XML_RPC_String, $XML_RPC_String, $XML_RPC_Int));
function getWeeklyReport($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $week = abs(XML_RPC_decode($p->getParam(1)));
    
    // figure out the correct week
    $start = date("U") - (DAY * (date("w") - 1));
    if ($week > 0) {
        $start = ($start - (WEEK * $week));
    }
    $end = date("Y-m-d", ($start + (DAY * 6)));
    $start = date("Y-m-d", $start);
    
    $tpl = new Template_API();
    $tpl->setTemplate("reports/weekly_data.tpl.html");
    $tpl->assign("data", Report::getWeeklyReport(User::getUserIDByEmail($email), $start, $end));
    
    return new XML_RPC_Response(XML_RPC_Encode($tpl->getTemplateContents() . "\n"));
}

/**
 * Fakes the creation of the login cookie
 */
function createFakeCookie($email, $project = false)
{
    global $HTTP_COOKIE_VARS;

    $cookie = array(
        "email" => $email
    );
    $HTTP_COOKIE_VARS[APP_COOKIE] = base64_encode(serialize($cookie));
    if ($project) {
        $cookie = array(
            "prj_id"   => $project,
            "remember" => false
        );
    }
    $HTTP_COOKIE_VARS[APP_PROJECT_COOKIE] = base64_encode(serialize($cookie));
}


$services = array(
    "mayChangeIssue" => array(
        'function'  => 'mayChangeIssue',
        'signature' => $mayChangeIssue_sig
    ),
    "getClosedAbbreviationAssocList" => array(
        'function'  => 'getClosedAbbreviationAssocList',
        'signature' => $getClosedAbbreviationAssocList_sig
    ),
    "getAbbreviationAssocList" => array(
        'function'  => 'getAbbreviationAssocList',
        'signature' => $getAbbreviationAssocList_sig
    ),
    "closeIssue" => array(
        'function'  => 'closeIssue',
        'signature' => $closeIssue_sig
    ),
    "getFile" => array(
        'function'  => "getFile",
        'signature' => $getFile_sig
    ),
    "getFileList" => array(
        'function'  => "getFileList",
        'signature' => $getFileList_sig
    ),
    "unlockIssue" => array(
        'function'  => "unlockIssue",
        'signature' => $unlockIssue_sig
    ),
    "lockIssue" => array(
        'function'  => "lockIssue",
        'signature' => $lockIssue_sig
    ),
    "assignIssue" => array(
        'function'  => "assignIssue",
        'signature' => $assignIssue_sig
    ),
    "addAuthorizedReplier" => array(
        'function'  => "addAuthorizedReplier",
        'signature' => $addAuthorizedReplier_sig
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
    "getEmailListing" => array(
        "function"  =>  "getEmailListing",
        "signature" =>  $getEmailListing_sig
    ),
    "getEmail" => array(
        "function"  =>  "getEmail",
        "signature" =>  $getEmail_sig
    ),
    "getNoteListing" => array(
        "function"  =>  "getNoteListing",
        "signature" =>  $getNoteListing_sig
    ),
    "getNote" => array(
        "function"  =>  "getNote",
        "signature" =>  $getNote_sig
    ),
    "convertNote" => array(
        "function"  =>  "convertNote",
        "signature" =>  $convertNote_sig
    ),
    "getWeeklyReport"   => array(
        "function"  =>  "getWeeklyReport",
        "signature" =>  $getWeeklyReport_sig
    )
);
$server = new XML_RPC_Server($services);
?>