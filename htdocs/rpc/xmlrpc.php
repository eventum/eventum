<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2013 Eventum Team.                              |
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

require_once dirname(__FILE__) . '/../../init.php';
require_once 'XML/RPC/Server.php';

function authenticate($email, $password)
{
    global $XML_RPC_erruser;

    // XXX: The role check shouldn't be hardcoded for project 1
    if ((!Auth::isCorrectPassword($email, $password)) || (User::getRoleByUser(User::getUserIDByEmail($email), 1) <= User::getRoleID("Customer"))) {
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Authentication failed for $email.\nYour email/password is invalid or you do not have the proper role");
    } else {
        createFakeCookie($email);
        return true;
    }
}


$getDeveloperList_sig = array(array($XML_RPC_Struct, $XML_RPC_String, $XML_RPC_String, $XML_RPC_Int));
function getDeveloperList($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $password = XML_RPC_decode($p->getParam(1));
    $auth = authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $prj_id = XML_RPC_decode($p->getParam(2));

    $res = Project::getRemoteAssocList();
    if (empty($res)) {
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "There are currently no projects setup for remote invocation");
    }
    // check if this project allows remote invocation
    if (!in_array($prj_id, array_keys($res))) {
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "This project does not allow remote invocation");
    }

    $res = Project::getAddressBookAssocList($prj_id);
    if (empty($res)) {
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "There are currently no users associated with the given project");
    }

    return new XML_RPC_Response(XML_RPC_Encode($res));
}

$getSimpleIssueDetails_sig = array(array($XML_RPC_Struct, $XML_RPC_String, $XML_RPC_String, $XML_RPC_Int));
function getSimpleIssueDetails($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $password = XML_RPC_decode($p->getParam(1));
    $auth = authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $issue_id = XML_RPC_decode($p->getParam(2));
    createFakeCookie($email, Issue::getProjectID($issue_id));

    $details = Issue::getDetails($issue_id);
    if (empty($details)) {
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Issue #$issue_id could not be found");
    }

    return new XML_RPC_Response(new XML_RPC_Value(array(
                "summary"     => new XML_RPC_Value($details['iss_summary']),
                "customer"    => new XML_RPC_Value(@$details['customer_info']['customer_name']),
                "status"      => new XML_RPC_Value(@$details['sta_title']),
                "assignments" => new XML_RPC_Value(@$details["assignments"]),
                "authorized_names"  =>  new XML_RPC_Value(@implode(', ', $details['authorized_names']))
            ), "struct"));
}

$getOpenIssues_sig = array(array($XML_RPC_Array, $XML_RPC_String, $XML_RPC_String, $XML_RPC_Int, $XML_RPC_Boolean, $XML_RPC_String));
function getOpenIssues($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $password = XML_RPC_decode($p->getParam(1));
    $auth = authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $prj_id = XML_RPC_decode($p->getParam(2));
    createFakeCookie($prj_id);
    $show_all_issues = XML_RPC_decode($p->getParam(3));
    $status = XML_RPC_decode($p->getParam(4));
    $status_id = Status::getStatusID($status);
    $usr_id = User::getUserIDByEmail($email);

    $results = Issue::getOpenIssues($prj_id, $usr_id, $show_all_issues, $status_id);

    if (empty($results)) {
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "There are currently no open issues");
    }

    $structs = array();
    foreach ($results as $res) {
        $structs[] = new XML_RPC_Value(array(
            "issue_id"   => new XML_RPC_Value($res['iss_id'], "int"),
            "summary"    => new XML_RPC_Value($res['iss_summary']),
            'assigned_users'    => new XML_RPC_Value($res['assigned_users']),
            'status'     => new XML_RPC_Value($res['sta_title'])
        ), "struct");
    }
    return new XML_RPC_Response(new XML_RPC_Value($structs, "array"));
}

$isValidLogin_sig = array(array($XML_RPC_String, $XML_RPC_String, $XML_RPC_String));
function isValidLogin($p)
{
    global $XML_RPC_String;

    $email = XML_RPC_decode($p->getParam(0));
    $password = XML_RPC_decode($p->getParam(1));

    if (!Auth::isCorrectPassword($email, $password)) {
        $is_valid = 'no';
    } else {
        $is_valid = 'yes';
    }

    return new XML_RPC_Response(new XML_RPC_Value($is_valid, $XML_RPC_String));
}

$getUserAssignedProjects_sig = array(array($XML_RPC_Array, $XML_RPC_String, $XML_RPC_String, $XML_RPC_Boolean));
function getUserAssignedProjects($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $password = XML_RPC_decode($p->getParam(1));
    $auth = authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $only_customer_projects = XML_RPC_decode($p->getParam(2));

    $usr_id = User::getUserIDByEmail($email);
    $res = Project::getRemoteAssocListByUser($usr_id, $only_customer_projects);
    if (empty($res)) {
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "You are not assigned to any projects at this moment");
    }

    $structs = array();
    foreach ($res as $prj_id => $prj_title) {
        $structs[] = new XML_RPC_Value(array(
            "id"   => new XML_RPC_Value($prj_id, "int"),
            "title"    => new XML_RPC_Value($prj_title)
        ), "struct");
    }
    return new XML_RPC_Response(new XML_RPC_Value($structs, "array"));
}

$getIssueDetails_sig = array(array($XML_RPC_Struct, $XML_RPC_String, $XML_RPC_String, $XML_RPC_Int));
function getIssueDetails($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $password = XML_RPC_decode($p->getParam(1));
    $auth = authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $issue_id = XML_RPC_decode($p->getParam(2));
    createFakeCookie($email, Issue::getProjectID($issue_id));

    $res = Issue::getDetails($issue_id);
    foreach ($res as $k => $v) {
        if (is_array($v)) {
            // XXX: shouldn't go recursive instead?
            unset($res[$k]);
        } else {
            $res[$k] = base64_encode($v);
        }
    }
    if (empty($res)) {
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Issue #$issue_id could not be found");
    }

    // remove some naughty fields
    unset($res['iss_original_description']);
    return new XML_RPC_Response(XML_RPC_Encode($res));
}

$getTimeTrackingCategories_sig = array(array($XML_RPC_Struct, $XML_RPC_String, $XML_RPC_String, $XML_RPC_Int));
function getTimeTrackingCategories($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $password = XML_RPC_decode($p->getParam(1));
    $auth = authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $issue_id = XML_RPC_decode($p->getParam(2));
    $prj_id = Issue::getProjectID($issue_id);
    $res = Time_Tracking::getAssocCategories($prj_id);
    if (empty($res)) {
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "No time tracking categories could be found");
    }
    return new XML_RPC_Response(XML_RPC_Encode($res));
}

$recordTimeWorked_sig = array(array($XML_RPC_String, $XML_RPC_String, $XML_RPC_String, $XML_RPC_Int, $XML_RPC_Int, $XML_RPC_String, $XML_RPC_Int));
function recordTimeWorked($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $password = XML_RPC_decode($p->getParam(1));
    $auth = authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $issue_id = XML_RPC_decode($p->getParam(2));
    $cat_id = XML_RPC_decode($p->getParam(3));
    $summary = XML_RPC_decode($p->getParam(4));
    $time_spent = XML_RPC_decode($p->getParam(5));

    $usr_id = User::getUserIDByEmail($email);
    $res = Time_Tracking::recordRemoteEntry($issue_id, $usr_id, $cat_id, $summary, $time_spent);
    if ($res == -1) {
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Could not record the time tracking entry");
    }

    return new XML_RPC_Response(XML_RPC_Encode('OK'));
}

$setIssueStatus_sig = array(array($XML_RPC_String, $XML_RPC_String, $XML_RPC_String, $XML_RPC_Int, $XML_RPC_String));
function setIssueStatus($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $password = XML_RPC_decode($p->getParam(1));
    $auth = authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $issue_id = XML_RPC_decode($p->getParam(2));
    $new_status = XML_RPC_decode($p->getParam(3));

    $usr_id = User::getUserIDByEmail($email);
    $res = Issue::setRemoteStatus($issue_id, $usr_id, $new_status);
    if ($res == -1) {
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Could not set the status to issue #$issue_id");
    }

    return new XML_RPC_Response(XML_RPC_Encode('OK'));
}

$assignIssue_sig = array(array($XML_RPC_String, $XML_RPC_String, $XML_RPC_String, $XML_RPC_Int, $XML_RPC_Int, $XML_RPC_String));
function assignIssue($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $password = XML_RPC_decode($p->getParam(1));
    $auth = authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $issue_id = XML_RPC_decode($p->getParam(2));
    $project_id = XML_RPC_decode($p->getParam(3));
    $developer = XML_RPC_decode($p->getParam(4));

    createFakeCookie($email, Issue::getProjectID($issue_id));

    $usr_id = User::getUserIDByEmail($email);
    $assignee = User::getUserIDByEmail($developer);
    if (empty($assignee)) {
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Could not find a user with email '$developer'");
    }

    // check if the assignee is even allowed to be in the given project
    $projects = Project::getRemoteAssocListByUser($assignee);
    if (!in_array($project_id, array_keys($projects))) {
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "The selected developer is not permitted in the project associated with issue #$issue_id");
    }

    $res = Issue::remoteAssign($issue_id, $usr_id, $assignee);
    if ($res == -1) {
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Could not assign issue #$issue_id to $developer");
    }

    return new XML_RPC_Response(XML_RPC_Encode('OK'));
}

$takeIssue_sig = array(array($XML_RPC_String, $XML_RPC_String, $XML_RPC_String, $XML_RPC_Int, $XML_RPC_Int));
function takeIssue($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $password = XML_RPC_decode($p->getParam(1));
    $auth = authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $issue_id = XML_RPC_decode($p->getParam(2));
    $project_id = XML_RPC_decode($p->getParam(3));

    createFakeCookie($email, Issue::getProjectID($issue_id));

    // check if issue currently is un-assigned
    $current_assignees = Issue::getAssignedUsers($issue_id);
    if (count($current_assignees) > 0) {
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Issue is currently assigned to " . join(',', $current_assignees));
    }

    $usr_id = User::getUserIDByEmail($email);

    // check if the assignee is even allowed to be in the given project
    $projects = Project::getRemoteAssocListByUser($usr_id);
    if (!in_array($project_id, array_keys($projects))) {
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "The selected developer is not permitted in the project associated with issue #$issue_id");
    }

    $res = Issue::remoteAssign($issue_id, $usr_id, $usr_id);
    if ($res == -1) {
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Could not assign issue #$issue_id to $email");

    }

    $res = Issue::setRemoteStatus($issue_id, $usr_id, "Assigned");
    if ($res == -1) {
       return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Could not set status for issue #$issue_id");
    }
    return new XML_RPC_Response(XML_RPC_Encode('OK'));
}

$addAuthorizedReplier_sig = array(array($XML_RPC_String, $XML_RPC_String, $XML_RPC_String, $XML_RPC_Int, $XML_RPC_Int, $XML_RPC_String));
function addAuthorizedReplier($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $password = XML_RPC_decode($p->getParam(1));
    $auth = authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $issue_id = XML_RPC_decode($p->getParam(2));
    $project_id = XML_RPC_decode($p->getParam(3));
    $new_replier = XML_RPC_decode($p->getParam(4));

    $usr_id = User::getUserIDByEmail($email);
    $replier_usr_id = User::getUserIDByEmail($new_replier);

    // if this is an actual user, not just an email address check permissions
    if (!empty($replier_usr_id)) {
        // check if the assignee is even allowed to be in the given project
        $projects = Project::getRemoteAssocListByUser($replier_usr_id);
        if (!in_array($project_id, array_keys($projects))) {
            global $XML_RPC_erruser;
            return new XML_RPC_Response(0, $XML_RPC_erruser+1, "The given user is not permitted in the project associated with issue #$issue_id");
        }
    }

    // check if user is already authorized
    if (Authorized_Replier::isAuthorizedReplier($issue_id, $new_replier)) {
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "The given user is already an authorized replier on issue #$issue_id");
    }

    $res = Authorized_Replier::remoteAddAuthorizedReplier($issue_id, $usr_id, $new_replier);
    if ($res == -1) {
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Could not add '$new_replier' as an authorized replier to issue #$issue_id");
    }

    return new XML_RPC_Response(XML_RPC_Encode('OK'));
}

$getFileList_sig = array(array($XML_RPC_String, $XML_RPC_String, $XML_RPC_String, $XML_RPC_Int));
function getFileList($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $password = XML_RPC_decode($p->getParam(1));
    $auth = authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $issue_id = XML_RPC_decode($p->getParam(2));
    createFakeCookie($email, Issue::getProjectID($issue_id));

    $res = Attachment::getList($issue_id);
    if (empty($res)) {
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "No files could be found");
    }

    return new XML_RPC_Response(XML_RPC_Encode($res));
}

$getFile_sig = array(array($XML_RPC_String, $XML_RPC_String, $XML_RPC_String, $XML_RPC_Int));
function getFile($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $password = XML_RPC_decode($p->getParam(1));
    $auth = authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $file_id = XML_RPC_decode($p->getParam(2));

    $res = Attachment::getDetails($file_id);
    if (empty($res)) {
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "The requested file could not be found");
    }

    $res['iaf_file'] = base64_encode($res['iaf_file']);
    return new XML_RPC_Response(XML_RPC_Encode($res));
}

$lookupCustomer_sig = array(array($XML_RPC_String, $XML_RPC_String, $XML_RPC_String, $XML_RPC_Int, $XML_RPC_String, $XML_RPC_String));
function lookupCustomer($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $password = XML_RPC_decode($p->getParam(1));
    $auth = authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $prj_id = XML_RPC_decode($p->getParam(2));
    $field = XML_RPC_decode($p->getParam(3));
    $value = XML_RPC_decode($p->getParam(4));

    $possible_fields = array('email', 'support', 'customer');
    if (!in_array($field, $possible_fields)) {
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Unknown field type '$field'");
    }

    $usr_id = User::getUserIDByEmail($email);
    // only customers should be able to use this page
    $role_id = User::getRoleByUser($usr_id, $prj_id);
    if ($role_id < User::getRoleID('Developer')) {
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "You don't have the appropriate permissions to lookup customer information");
    }

    $res = Customer::lookup($prj_id, $field, $value);
    return new XML_RPC_Response(XML_RPC_Encode($res));
}

$closeIssue_sig = array(array($XML_RPC_String, $XML_RPC_String, $XML_RPC_String, $XML_RPC_Int, $XML_RPC_String, $XML_RPC_Int, $XML_RPC_Boolean, $XML_RPC_String));
function closeIssue($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $password = XML_RPC_decode($p->getParam(1));
    $auth = authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $usr_id = User::getUserIDByEmail($email);
    $issue_id = XML_RPC_decode($p->getParam(2));
    $new_status = XML_RPC_decode($p->getParam(3));
    $status_id = Status::getStatusID($new_status);
    $resolution_id = XML_RPC_decode($p->getParam(4));
    $send_notification = XML_RPC_decode($p->getParam(5));
    $note = XML_RPC_decode($p->getParam(6));

    createFakeCookie($email, Issue::getProjectID($issue_id));

    $res = Issue::close($usr_id, $issue_id, $send_notification, $resolution_id, $status_id, $note);
    if ($res == -1) {
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Could not close issue #$issue_id");
    }

    $prj_id = Issue::getProjectID($issue_id);
    if (Customer::hasCustomerIntegration($prj_id) && Customer::hasPerIncidentContract($prj_id, Issue::getCustomerID($issue_id))) {
        return new XML_RPC_Response(XML_RPC_Encode('INCIDENT'));
    } else {
        return new XML_RPC_Response(XML_RPC_Encode('OK'));
    }
}

$getClosedAbbreviationAssocList_sig = array(array($XML_RPC_String, $XML_RPC_String, $XML_RPC_String, $XML_RPC_Int));
function getClosedAbbreviationAssocList($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $password = XML_RPC_decode($p->getParam(1));
    $auth = authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $prj_id = XML_RPC_decode($p->getParam(2));

    $res = Status::getClosedAbbreviationAssocList($prj_id);
    return new XML_RPC_Response(XML_RPC_Encode($res));
}

$getAbbreviationAssocList_sig = array(array($XML_RPC_String, $XML_RPC_String, $XML_RPC_String, $XML_RPC_Int, $XML_RPC_Boolean));
function getAbbreviationAssocList($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $password = XML_RPC_decode($p->getParam(1));
    $auth = authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $prj_id = XML_RPC_decode($p->getParam(2));
    $show_closed = XML_RPC_decode($p->getParam(3));

    $res = Status::getAbbreviationAssocList($prj_id, $show_closed);
    return new XML_RPC_Response(XML_RPC_Encode($res));
}

$getEmailListing_sig = array(array($XML_RPC_Array, $XML_RPC_String, $XML_RPC_String, $XML_RPC_Int));
function getEmailListing($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $password = XML_RPC_decode($p->getParam(1));
    $auth = authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $issue_id = XML_RPC_decode($p->getParam(2));
    $emails = Support::getEmailsByIssue($issue_id);

    // since xml-rpc has issues, lets base64 encode everything
    if (is_array($emails)) {
        foreach ($emails as &$email) {
            unset($email["seb_body"]);
            foreach ($email as $key => $val) {
                $email[$key] = base64_encode($val);
            }
        }
    }
    return new XML_RPC_Response(XML_RPC_Encode($emails));
}

$getEmail_sig = array(array($XML_RPC_Array, $XML_RPC_String, $XML_RPC_String, $XML_RPC_Int, $XML_RPC_Int));
function getEmail($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $password = XML_RPC_decode($p->getParam(1));
    $auth = authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $issue_id = XML_RPC_decode($p->getParam(2));
    $email_id = XML_RPC_decode($p->getParam(3));
    $email = Support::getEmailBySequence($issue_id, $email_id);

    // get requested email
    if ((count($email) < 1) || (!is_array($email))) {
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Email #" . $email_id . " does not exist for issue #$issue_id");
    }
    // since xml-rpc has issues, lets base64 encode everything
    foreach ($email as $key => $val) {
        $email[$key] = base64_encode($val);
    }
    return new XML_RPC_Response(XML_RPC_Encode($email));
}

$getNoteListing_sig = array(array($XML_RPC_Array, $XML_RPC_String, $XML_RPC_String, $XML_RPC_Int));
function getNoteListing($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $password = XML_RPC_decode($p->getParam(1));
    $auth = authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $issue_id = XML_RPC_decode($p->getParam(2));
    createFakeCookie($email, Issue::getProjectID($issue_id));
    $notes = Note::getListing($issue_id);

    // since xml-rpc has issues, lets base64 encode everything
    foreach ($notes as &$note) {
        foreach ($note as $key => $val) {
            $note[$key] = base64_encode($val);
        }
    }
    return new XML_RPC_Response(XML_RPC_Encode($notes));
}

$getNote_sig = array(array($XML_RPC_Array, $XML_RPC_String, $XML_RPC_String, $XML_RPC_Int, $XML_RPC_Int));
function getNote($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $password = XML_RPC_decode($p->getParam(1));
    $auth = authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $issue_id = XML_RPC_decode($p->getParam(2));
    $note_id = XML_RPC_decode($p->getParam(3));
    $note = Note::getNoteBySequence($issue_id, $note_id);

    if (count($note) < 1 || !is_array($note)) {
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Note #" . $note_id . " does not exist for issue #$issue_id");
    }
    // since xml-rpc has issues, lets base64 encode everything
    foreach ($note as $key => $val) {
        $note[$key] = base64_encode($val);
    }
    return new XML_RPC_Response(XML_RPC_Encode($note));
}

$convertNote_sig = array(array($XML_RPC_String, $XML_RPC_String, $XML_RPC_String, $XML_RPC_Int, $XML_RPC_Int, $XML_RPC_String, $XML_RPC_Boolean));
function convertNote($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $password = XML_RPC_decode($p->getParam(1));
    $auth = authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $issue_id = XML_RPC_decode($p->getParam(2));
    $note_id = XML_RPC_decode($p->getParam(3));
    $target = XML_RPC_decode($p->getParam(4));
    $authorize_sender = XML_RPC_decode($p->getParam(5));

    createFakeCookie($email, Issue::getProjectID($issue_id));
    $res = Note::convertNote($note_id, $target, $authorize_sender);
    if (empty($res)) {
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Error converting note");
    }

    return new XML_RPC_Response(XML_RPC_Encode("OK"));
}

$mayChangeIssue_sig = array(array($XML_RPC_String, $XML_RPC_String, $XML_RPC_String, $XML_RPC_Int));
function mayChangeIssue($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $password = XML_RPC_decode($p->getParam(1));
    $auth = authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $issue_id = XML_RPC_decode($p->getParam(2));
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

$getWeeklyReport_sig = array(array($XML_RPC_String, $XML_RPC_String, $XML_RPC_String, $XML_RPC_Int, $XML_RPC_String, $XML_RPC_String, $XML_RPC_Int));
function getWeeklyReport($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $password = XML_RPC_decode($p->getParam(1));
    $auth = authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $week = abs(XML_RPC_decode($p->getParam(2)));
    $start = XML_RPC_decode($p->getParam(3));
    $end = XML_RPC_decode($p->getParam(4));
    $separate_closed = XML_RPC_decode($p->getParam(5));

    // we have to set a project so the template class works, even though the weekly report doesn't actually need it
    $projects = Project::getAssocList(Auth::getUserID());
    createFakeCookie($email, current(array_keys($projects)));

    // figure out the correct week
    if ((empty($start)) || (empty($end))) {
        $start = date("U") - (DAY * (date("w") - 1));
        if ($week > 0) {
            $start = ($start - (WEEK * $week));
        }
        $end = date("Y-m-d", ($start + (DAY * 6)));
        $start = date("Y-m-d", $start);
    }

    if ($separate_closed) {
        // emulate smarty value for reports/weekly_data.tpl.tmpl:
        // {if $smarty.post.separate_closed == 1}
        $_POST['separate_closed'] = true;
    }
    $tpl = new Template_Helper();
    $tpl->setTemplate("reports/weekly_data.tpl.html");
    $tpl->assign("data", Report::getWeeklyReport(User::getUserIDByEmail($email), $start, $end, $separate_closed));

    $ret = $tpl->getTemplateContents(). "\n";
    return new XML_RPC_Response(XML_RPC_Encode(base64_encode($ret)));
}

$getResolutionAssocList_sig = array(array($XML_RPC_String));
function getResolutionAssocList($p)
{

    $res = Resolution::getAssocList();
    return new XML_RPC_Response(XML_RPC_Encode($res));
}

$timeClock_sig = array(array($XML_RPC_String, $XML_RPC_String, $XML_RPC_String, $XML_RPC_String));
function timeClock($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $password = XML_RPC_decode($p->getParam(1));
    $auth = authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $action = XML_RPC_decode($p->getParam(2));

    if ($action == "in") {
        $res = User::clockIn(User::getUserIDByEmail($email));
    } elseif ($action == "out") {
        $res = User::clockOut(User::getUserIDByEmail($email));
    } else {
        if (User::isClockedIn(User::getUserIDByEmail($email))) {
            $msg = "is clocked in";
        } else {
            $msg = "is clocked out";
        }
        return new XML_RPC_Response(XML_RPC_Encode("$email " . $msg . ".\n"));
    }

    if ($res == 1) {
        return new XML_RPC_Response(XML_RPC_Encode("$email successfully clocked " . $action . ".\n"));
    } else {
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Error clocking " . $action . ".\n");
    }
}


$getDraftListing_sig = array(array($XML_RPC_Array, $XML_RPC_String, $XML_RPC_String, $XML_RPC_Int));
function getDraftListing($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $password = XML_RPC_decode($p->getParam(1));
    $auth = authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $issue_id = XML_RPC_decode($p->getParam(2));

    $drafts = Draft::getList($issue_id);

    // since xml-rpc has issues, lets base64 encode everything
    foreach ($drafts as &$draft) {
        foreach ($draft as $key => $val) {
            $draft[$key] = base64_encode($val);
        }
    }
    return new XML_RPC_Response(XML_RPC_Encode($drafts));
}

$getDraft_sig = array(array($XML_RPC_Array, $XML_RPC_String, $XML_RPC_String, $XML_RPC_Int, $XML_RPC_Int));
function getDraft($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $password = XML_RPC_decode($p->getParam(1));
    $auth = authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $issue_id = XML_RPC_decode($p->getParam(2));
    $draft_id = XML_RPC_decode($p->getParam(3));
    $draft = Draft::getDraftBySequence($issue_id, $draft_id);

    if ((count($draft) < 1) || (!is_array($draft))) {
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Draft #" . $draft_id . " does not exist for issue #$issue_id");
    }
    if (empty($draft['to'])) {
        $draft['to'] = "Notification List";
    }
    $draft['cc'] = @join(", ", $draft['cc']);
    // since xml-rpc has issues, lets base64 encode everything
    foreach ($draft as $key => $val) {
        $draft[$key] = base64_encode($val);
    }
    return new XML_RPC_Response(XML_RPC_Encode($draft));
}

$sendDraft_sig = array(array($XML_RPC_String, $XML_RPC_String, $XML_RPC_String, $XML_RPC_Int, $XML_RPC_Int));
function sendDraft($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $password = XML_RPC_decode($p->getParam(1));
    $auth = authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $issue_id = XML_RPC_decode($p->getParam(2));
    $draft_id = XML_RPC_decode($p->getParam(3));
    $draft = Draft::getDraftBySequence($issue_id, $draft_id);
    createFakeCookie($email, Issue::getProjectID($issue_id));

    if ((count($draft) < 1) || (!is_array($draft))) {
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Draft #" . $draft_id . " does not exist for issue #$issue_id");
    }
    $res = Draft::send($draft["emd_id"]);
    if ($res == 1) {
        return new XML_RPC_Response(XML_RPC_Encode("Draft #" . $draft_id . " sent successfully.\n"));
    } else {
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Error sending Draft #" . $draft_id . "\n");
    }
}

$redeemIssue_sig = array(array($XML_RPC_String, $XML_RPC_String, $XML_RPC_String, $XML_RPC_Int, $XML_RPC_Struct));
function redeemIssue($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $password = XML_RPC_decode($p->getParam(1));
    $auth = authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $issue_id = XML_RPC_decode($p->getParam(2));
    $types = XML_RPC_decode($p->getParam(3));

    $prj_id = Issue::getProjectID($issue_id);
    createFakeCookie($email, $prj_id);
    $customer_id = Issue::getCustomerID($issue_id);

    $all_types = Customer::getIncidentTypes($prj_id);

    if (!Customer::hasCustomerIntegration($prj_id)) {
        // no customer integration
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "No customer integration for issue #$issue_id");
    } elseif (!Customer::hasPerIncidentContract($prj_id, $customer_id)) {
        // check if is per incident contract
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Customer for issue #$issue_id does not have a per-incident contract");
    } else {
        // check if incidents are remaining
        global $XML_RPC_erruser;
        foreach ($types as $type_id) {
            if (Customer::isRedeemedIncident($prj_id, $issue_id, $type_id)) {
                return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Issue #$issue_id is already marked as redeemed incident of type " . $all_types[$type_id]);
            } elseif (!Customer::hasIncidentsLeft($prj_id, $customer_id, $type_id)) {
                return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Customer for issue #$issue_id has no remaining incidents of type " . $all_types[$type_id]);
            }
        }
    }

    foreach ($types as $type_id) {
        $res = Customer::flagIncident($prj_id, $issue_id, $type_id);
        if ($res == -1) {
            global $XML_RPC_erruser;
            return new XML_RPC_Response(0, $XML_RPC_erruser+1, "An error occured trying to mark issue as redeemed.");
        }
    }
    return new XML_RPC_Response(XML_RPC_Encode('OK'));
}

$unredeemIssue_sig = array(array($XML_RPC_String, $XML_RPC_String, $XML_RPC_String, $XML_RPC_Int, $XML_RPC_Struct));
function unredeemIssue($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $password = XML_RPC_decode($p->getParam(1));
    $auth = authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $issue_id = XML_RPC_decode($p->getParam(2));
    $types = XML_RPC_decode($p->getParam(3));

    $prj_id = Issue::getProjectID($issue_id);
    createFakeCookie($email, $prj_id);

    $customer_id = Issue::getCustomerID($issue_id);

    if (!Customer::hasCustomerIntegration($prj_id)) {
        // no customer integration
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "No customer integration for issue #$issue_id");
    } elseif (!Customer::hasPerIncidentContract($prj_id, $customer_id)) {
        // check if is per incident contract
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Customer for issue #$issue_id does not have a per-incident contract");
    } else {
        // check if incidents are remaining
        global $XML_RPC_erruser;
        foreach ($types as $type_id) {
            if (!Customer::isRedeemedIncident($prj_id, $issue_id, $type_id)) {
                return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Issue #$issue_id is not marked as redeemed incident of type " . $all_types[$type_id]);
            }
        }
    }

    foreach ($types as $type_id) {
        $res = Customer::unflagIncident($prj_id, $issue_id, $type_id);
        if ($res == -1) {
            global $XML_RPC_erruser;
            return new XML_RPC_Response(0, $XML_RPC_erruser+1, "An error occured trying to mark issue as unredeemed.");
        }
    }
    return new XML_RPC_Response(XML_RPC_Encode('OK'));
}

$getIncidentTypes_sig = array(array($XML_RPC_String, $XML_RPC_String, $XML_RPC_String, $XML_RPC_Int, $XML_RPC_Boolean));
function getIncidentTypes($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $password = XML_RPC_decode($p->getParam(1));
    $auth = authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $issue_id = XML_RPC_decode($p->getParam(2));
    $redeemed_only = XML_RPC_decode($p->getParam(3));

    $prj_id = Issue::getProjectID($issue_id);
    createFakeCookie($email, $prj_id);
    $customer_id = Issue::getCustomerID($issue_id);

    if (!Customer::hasCustomerIntegration($prj_id)) {
        // no customer integration
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "No customer integration for issue #$issue_id");
    } elseif (!Customer::hasPerIncidentContract($prj_id, $customer_id)) {
        // check if is per incident contract
        global $XML_RPC_erruser;
        return new XML_RPC_Response(0, $XML_RPC_erruser+1, "Customer for issue #$issue_id does not have a per-incident contract");
    }

    $details = Customer::getDetails($prj_id, $customer_id);

    foreach ($details['incident_details'] as $type_id => $type_details) {
        $is_redeemed = Customer::isRedeemedIncident($prj_id, $issue_id, $type_id);
        if ((($redeemed_only) && (!$is_redeemed)) || ((!$redeemed_only) && ($is_redeemed))) {
            unset($details['incident_details'][$type_id]);
        }
    }

    return new XML_RPC_Response(XML_RPC_Encode($details['incident_details']));
}

$logCommand_sig = array(array($XML_RPC_String, $XML_RPC_String, $XML_RPC_String, $XML_RPC_String));
function logCommand($p)
{
    $email = XML_RPC_decode($p->getParam(0));
    $password = XML_RPC_decode($p->getParam(1));
    $auth = authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $command = base64_decode(XML_RPC_decode($p->getParam(2)));

    $msg = $email . "\t" . $command . "\n";

    $fp = @fopen(APP_CLI_LOG, "a");
    @fwrite($fp, $msg);
    @fclose($fp);

    return new XML_RPC_Response(XML_RPC_Encode('OK'));
}

/**
 * Fakes the creation of the login cookie
 */
function createFakeCookie($email, $project = false)
{
    $cookie = array(
        "email" => $email
    );
    $_COOKIE[APP_COOKIE] = base64_encode(serialize($cookie));
    if ($project) {
        $cookie = array(
            "prj_id"   => $project,
            "remember" => false
        );
    }
    $_COOKIE[APP_PROJECT_COOKIE] = base64_encode(serialize($cookie));
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
    "lookupCustomer" => array(
        'function'  => "lookupCustomer",
        'signature' => $lookupCustomer_sig
    ),
    "getFile" => array(
        'function'  => "getFile",
        'signature' => $getFile_sig
    ),
    "getFileList" => array(
        'function'  => "getFileList",
        'signature' => $getFileList_sig
    ),
    "assignIssue" => array(
        'function'  => "assignIssue",
        'signature' => $assignIssue_sig
    ),
    "takeIssue" => array(
        'function'  => "takeIssue",
        'signature' => $takeIssue_sig
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
        "function"  => "getEmailListing",
        "signature" => $getEmailListing_sig
    ),
    "getEmail" => array(
        "function"  => "getEmail",
        "signature" => $getEmail_sig
    ),
    "getNoteListing" => array(
        "function"  => "getNoteListing",
        "signature" => $getNoteListing_sig
    ),
    "getNote" => array(
        "function"  => "getNote",
        "signature" => $getNote_sig
    ),
    "convertNote" => array(
        "function"  => "convertNote",
        "signature" => $convertNote_sig
    ),
    "getWeeklyReport" => array(
        "function"  => "getWeeklyReport",
        "signature" => $getWeeklyReport_sig
    ),
    "getResolutionAssocList" => array(
        "function"  => "getResolutionAssocList",
        "signature" => $getResolutionAssocList_sig
    ),
    "timeClock"     => array(
        "function"  => "timeClock",
        "signature" => $timeClock_sig
    ),
    "getDraftListing" => array(
        "function"  => "getDraftListing",
        "signature" => $getDraftListing_sig
    ),
    "getDraft" => array(
        "function"  => "getDraft",
        "signature" => $getDraft_sig
    ),
    "sendDraft" => array(
        "function"  => "sendDraft",
        "signature" => $sendDraft_sig
    ),
    "redeemIssue" =>  array(
        "function"  =>  "redeemIssue",
        "signature" =>  $redeemIssue_sig
    ),
    "unredeemIssue" =>  array(
        "function"  =>  "unredeemIssue",
        "signature" =>  $unredeemIssue_sig
    ),
    "getIncidentTypes"  =>  array(
        "function"  =>  "getIncidentTypes",
        "signature" =>  $getIncidentTypes_sig
    ),
    "logCommand"    => array(
        "function"  => "logCommand",
        "signature" => $logCommand_sig
    )
);
$server = new XML_RPC_Server($services);
