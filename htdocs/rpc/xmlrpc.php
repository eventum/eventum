<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2014 Eventum Team.                              |
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
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

require_once dirname(__FILE__) . '/../../init.php';

// close session
session_write_close();

$api = new RemoteApi();
$handler = new XmlRpcServer($api);

/**
* Class RemoteApi
*
* All public non-static methods are exposed for XMLRPC
*/
class RemoteApi {
protected static function userError($fstr = '') {
    global $XML_RPC_erruser;
    return new XML_RPC_Response(0, $XML_RPC_erruser+1, $fstr);
}

protected static function authenticate($email, $password)
{
    // XXX: The role check shouldn't be hardcoded for project 1
    if (!Auth::isCorrectPassword($email, $password) ||
        (User::getRoleByUser(User::getUserIDByEmail($email), 1) <= User::getRoleID("Customer"))) {
        return self::userError("Authentication failed for $email.\nYour email/password is invalid or you do not have the proper role");
    }

    self::createFakeCookie($email);

    return true;
}

/**
 * Fakes the creation of the login cookie
 */
protected static function createFakeCookie($email, $project = false)
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

/**
 * @param struct $email
 * @param string $password
 * @param int $prj_id
 * @return struct
 */
function getDeveloperList($email, $password, $prj_id)
{
    $auth = self::authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }

    $res = Project::getRemoteAssocList();
    if (empty($res)) {
        return self::userError("There are currently no projects setup for remote invocation");
    }
    // check if this project allows remote invocation
    if (!in_array($prj_id, array_keys($res))) {
        return self::userError("This project does not allow remote invocation");
    }

    $res = Project::getAddressBookAssocList($prj_id);
    if (empty($res)) {
        return self::userError("There are currently no users associated with the given project");
    }

    return new XML_RPC_Response(XML_RPC_Encode($res));
}

/**
 * @param string $email
 * @param string $password
 * @param int $issue_id
 * @return struct
 */
function getSimpleIssueDetails($email, $password, $issue_id)
{
    $auth = self::authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }

    self::createFakeCookie($email, Issue::getProjectID($issue_id));

    $details = Issue::getDetails($issue_id);
    if (empty($details)) {
        return self::userError("Issue #$issue_id could not be found");
    }

    return new XML_RPC_Response(new XML_RPC_Value(array(
                "summary"     => new XML_RPC_Value($details['iss_summary']),
                "customer"    => new XML_RPC_Value(@$details['customer_info']['customer_name']),
                "status"      => new XML_RPC_Value(@$details['sta_title']),
                "assignments" => new XML_RPC_Value(@$details["assignments"]),
                "authorized_names"  =>  new XML_RPC_Value(@implode(', ', $details['authorized_names']))
            ), "struct"));
}

/**
 * @param string $email
 * @param string $password
 * @param int $prj_id
 * @param boolean $show_all_issues
 * @param string $status
 * @return array
 */
function getOpenIssues($email, $password, $prj_id, $show_all_issues, $status)
{
    $auth = self::authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }

    self::createFakeCookie($prj_id);
    $status_id = Status::getStatusID($status);
    $usr_id = User::getUserIDByEmail($email);

    $results = Issue::getOpenIssues($prj_id, $usr_id, $show_all_issues, $status_id);

    if (empty($results)) {
        return self::userError("There are currently no open issues");
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

/**
 * @param string $email
 * @param string $password
 * @return string
 */
function isValidLogin($email, $password)
{
    if (!Auth::isCorrectPassword($email, $password)) {
        $is_valid = 'no';
    } else {
        $is_valid = 'yes';
    }

    return $is_valid;
}

/**
 * @param string $email
 * @param string $password
 * @param boolean $only_customer_projects
 * @return array
 */
function getUserAssignedProjects($email, $password, $only_customer_projects)
{
    $auth = self::authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }

    $usr_id = User::getUserIDByEmail($email);
    $res = Project::getRemoteAssocListByUser($usr_id, $only_customer_projects);
    if (empty($res)) {
        return self::userError("You are not assigned to any projects at this moment");
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

/**
 * @param string $email
 * @param string $password
 * @param int $p
 * @return struct
 */
function getIssueDetails($email, $password, $issue_id)
{
    $auth = self::authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }

    self::createFakeCookie($email, Issue::getProjectID($issue_id));

    $res = Issue::getDetails($issue_id);

    // flatten some fields
    if (isset($res['customer'])) {
        $details = $res['customer']->getDetails();
        $res['customer'] = $details;
    }
    if (isset($res['contract'])) {
        $res['contract'] = $res['contract']->getDetails();
    }

    $res = Misc::base64_encode($res);
    if (empty($res)) {
        return self::userError("Issue #$issue_id could not be found");
    }

    // remove some naughty fields
    unset($res['iss_original_description']);

    return new XML_RPC_Response(XML_RPC_Encode($res));
}

/**
 * @param string $email
 * @param string $password
 * @param int $issue_id
 * @return struct
 */
function getTimeTrackingCategories($email, $password, $issue_id)
{
    $auth = self::authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $prj_id = Issue::getProjectID($issue_id);
    $res = Time_Tracking::getAssocCategories($prj_id);
    if (empty($res)) {
        return self::userError("No time tracking categories could be found");
    }

    return new XML_RPC_Response(XML_RPC_Encode($res));
}

/**
 * @param string $email
 * @param string $password
 * @param int $issue_id
 * @param int $cat_id
 * @param string $summary
 * @param int $time_spent
 * @return string
 */
function recordTimeWorked($email, $password, $issue_id, $cat_id, $summary, $time_spent)
{
    $auth = self::authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }

    $usr_id = User::getUserIDByEmail($email);
    $res = Time_Tracking::recordRemoteEntry($issue_id, $usr_id, $cat_id, $summary, $time_spent);
    if ($res == -1) {
        return self::userError("Could not record the time tracking entry");
    }

    return new XML_RPC_Response(XML_RPC_Encode('OK'));
}

/**
 * @param string $email
 * @param string $password
 * @param int $issue_id
 * @param string $new_status
 * @return string
 */
function setIssueStatus($email, $password, $issue_id, $new_status)
{
    $auth = self::authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }

    $usr_id = User::getUserIDByEmail($email);
    $res = Issue::setRemoteStatus($issue_id, $usr_id, $new_status);
    if ($res == -1) {
        return self::userError("Could not set the status to issue #$issue_id");
    }

    return new XML_RPC_Response(XML_RPC_Encode('OK'));
}

/**
 * @param string $p
 * @param string $email
 * @param string $password
 * @param int $issue_id
 * @param int $project_id
 * @param string $developer
 * @return XML_RPC_Response
 */
function assignIssue($email, $password, $issue_id, $project_id, $developer)
{
    $auth = self::authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }

    self::createFakeCookie($email, Issue::getProjectID($issue_id));

    $usr_id = User::getUserIDByEmail($email);
    $assignee = User::getUserIDByEmail($developer);
    if (empty($assignee)) {
        return self::userError("Could not find a user with email '$developer'");
    }

    // check if the assignee is even allowed to be in the given project
    $projects = Project::getRemoteAssocListByUser($assignee);
    if (!in_array($project_id, array_keys($projects))) {
        return self::userError("The selected developer is not permitted in the project associated with issue #$issue_id");
    }

    $res = Issue::remoteAssign($issue_id, $usr_id, $assignee);
    if ($res == -1) {
        return self::userError("Could not assign issue #$issue_id to $developer");
    }

    return new XML_RPC_Response(XML_RPC_Encode('OK'));
}

/**
 * @param string $email
 * @param string $password
 * @param int $issue_id
 * @param int $project_id
 * @return string
 */
function takeIssue($email, $password, $issue_id, $project_id)
{
    $auth = self::authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }

    self::createFakeCookie($email, Issue::getProjectID($issue_id));

    // check if issue currently is un-assigned
    $current_assignees = Issue::getAssignedUsers($issue_id);
    if (count($current_assignees) > 0) {
        return self::userError("Issue is currently assigned to " . join(',', $current_assignees));
    }

    $usr_id = User::getUserIDByEmail($email);

    // check if the assignee is even allowed to be in the given project
    $projects = Project::getRemoteAssocListByUser($usr_id);
    if (!in_array($project_id, array_keys($projects))) {
        return self::userError("The selected developer is not permitted in the project associated with issue #$issue_id");
    }

    $res = Issue::remoteAssign($issue_id, $usr_id, $usr_id);
    if ($res == -1) {
        return self::userError("Could not assign issue #$issue_id to $email");
    }

    $res = Issue::setRemoteStatus($issue_id, $usr_id, "Assigned");
    if ($res == -1) {
        return self::userError("Could not set status for issue #$issue_id");
    }

    return new XML_RPC_Response(XML_RPC_Encode('OK'));
}

/**
 * @param string $email
 * @param string $password
 * @param int $issue_id
 * @param int $project_id
 * @param string $new_replier
 * @return string
 */
function addAuthorizedReplier($email, $password, $issue_id, $project_id, $new_replier)
{
    $auth = self::authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }

    $usr_id = User::getUserIDByEmail($email);
    $replier_usr_id = User::getUserIDByEmail($new_replier);

    // if this is an actual user, not just an email address check permissions
    if (!empty($replier_usr_id)) {
        // check if the assignee is even allowed to be in the given project
        $projects = Project::getRemoteAssocListByUser($replier_usr_id);
        if (!in_array($project_id, array_keys($projects))) {
            return self::userError("The given user is not permitted in the project associated with issue #$issue_id");
        }
    }

    // check if user is already authorized
    if (Authorized_Replier::isAuthorizedReplier($issue_id, $new_replier)) {
        return self::userError("The given user is already an authorized replier on issue #$issue_id");
    }

    $res = Authorized_Replier::remoteAddAuthorizedReplier($issue_id, $usr_id, $new_replier);
    if ($res == -1) {
        return self::userError("Could not add '$new_replier' as an authorized replier to issue #$issue_id");
    }

    return new XML_RPC_Response(XML_RPC_Encode('OK'));
}

/**
 * @param string $email
 * @param string $password
 * @param int $issue_id
 * @return string
 */
function getFileList($email, $password, $issue_id)
{
    $auth = self::authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }

    self::createFakeCookie($email, Issue::getProjectID($issue_id));

    $res = Attachment::getList($issue_id);
    if (empty($res)) {
        return self::userError("No files could be found");
    }

    return new XML_RPC_Response(XML_RPC_Encode($res));
}

/**
 * @param string $email
 * @param string $password
 * @param int $file_id
 * @return string
 */
function getFile($email, $password, $file_id)
{
    $auth = self::authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }

    $res = Attachment::getDetails($file_id);
    if (empty($res)) {
        return self::userError("The requested file could not be found");
    }

    return new XML_RPC_Response(XML_RPC_Encode(Misc::base64_encode($res)));
}

/**
 * @param string $email
 * @param string $password
 * @param string $prj_id
 * @param string $field
 * @param string $value
 * @return string
 */
function lookupCustomer($email, $password, $prj_id, $field, $value)
{
    $auth = self::authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }

    $possible_fields = array('email', 'support', 'customer');
    if (!in_array($field, $possible_fields)) {
        return self::userError("Unknown field type '$field'");
    }

    $usr_id = User::getUserIDByEmail($email);
    // only customers should be able to use this page
    $role_id = User::getRoleByUser($usr_id, $prj_id);
    if ($role_id < User::getRoleID('Developer')) {
        return self::userError("You don't have the appropriate permissions to lookup customer information");
    }

    $crm = CRM::getInstance($prj_id);
    $res = $crm->lookup($field, $value, array());

    return new XML_RPC_Response(XML_RPC_Encode($res));
}

/**
 * @param string $email
 * @param string $password
 * @param int $p
 * @param string $p
 * @param int $p
 * @param bool $p
 * @param string $p
 * @return string
 */
function closeIssue($email, $password, $issue_id, $new_status, $resolution_id, $send_notification, $note)
{
    $auth = self::authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $usr_id = User::getUserIDByEmail($email);
    $status_id = Status::getStatusID($new_status);

    self::createFakeCookie($email, Issue::getProjectID($issue_id));

    $res = Issue::close($usr_id, $issue_id, $send_notification, $resolution_id, $status_id, $note);
    if ($res == -1) {
        return self::userError("Could not close issue #$issue_id");
    }

    $prj_id = Issue::getProjectID($issue_id);
    if (CRM::hasCustomerIntegration($prj_id)) {
        $crm = CRM::getInstance($prj_id);
        try {
            $contract = $crm->getContract(Issue::getContractID($issue_id));
            if ($contract->hasPerIncident()) {
                return new XML_RPC_Response(XML_RPC_Encode('INCIDENT'));
            }
        } catch (CRMException $e) {}
    }

    return new XML_RPC_Response(XML_RPC_Encode('OK'));
}

/**
 * @param string $email
 * @param string $password
 * @param int $prj_id
 * @return string
 */
function getClosedAbbreviationAssocList($email, $password, $prj_id)
{
    $auth = self::authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }

    $res = Status::getClosedAbbreviationAssocList($prj_id);

    return new XML_RPC_Response(XML_RPC_Encode($res));
}

/**
 * @param string $email
 * @param string $password
 * @param int $prj_id
 * @param bool $show_closed
 * @return string
 */
function getAbbreviationAssocList($email, $password, $prj_id, $show_closed)
{
    $auth = self::authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }

    $res = Status::getAbbreviationAssocList($prj_id, $show_closed);

    return new XML_RPC_Response(XML_RPC_Encode($res));
}

/**
 * @param string $email
 * @param string $password
 * @param int $issue_id
 * @return array
 */
function getEmailListing($email, $password, $issue_id)
{
    $auth = self::authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }

    $real_emails = Support::getEmailsByIssue($issue_id);

    $issue = Issue::getDetails($issue_id);
    $email = array(
        'sup_date'  =>  $issue['iss_created_date'],
        'sup_from'  =>  $issue['reporter'],
        'sup_to'    => '',
        'sup_cc'    =>  '',
        'sup_subject'   =>  $issue['iss_summary']
    );
    if ($real_emails != '') {
        $emails = array_merge(array($email), $real_emails);
    } else {
        $emails[] = $email;
    }

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

/**
 * @param string $email
 * @param string $password
 * @param int $issue_id
 * @param int $email_id
 * @return array
 */
function getEmail($email, $password, $issue_id, $email_id)
{
    $auth = self::authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }

    if ($email_id == 0) {
        // return issue description instead
        $issue = Issue::getDetails($issue_id);
        $email = array(
            'sup_date'  =>  $issue['iss_created_date'],
            'sup_from'  =>  $issue['reporter'],
            'sup_to'    => '',
            'recipients'=>  '',
            'sup_cc'    =>  '',
            'sup_has_attachment'    =>  0,
            'sup_subject'   =>  $issue['iss_summary'],
            'message'   =>  $issue['iss_original_description'],
            'seb_full_email'    =>  $issue['iss_original_description']
        );
    } else {
        $email = Support::getEmailBySequence($issue_id, $email_id);
    }

    // get requested email
    if ((count($email) < 1) || (!is_array($email))) {
        return self::userError("Email #" . $email_id . " does not exist for issue #$issue_id");
    }
    // since xml-rpc has issues, lets base64 encode everything
    $email = Misc::base64_encode($email);

    return new XML_RPC_Response(XML_RPC_Encode($email));
}

/**
 * @param string $email
 * @param string $password
 * @param int $issue_id
 * @return array
 */
function getNoteListing($email, $password, $issue_id)
{
    $auth = self::authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }

    self::createFakeCookie($email, Issue::getProjectID($issue_id));
    $notes = Note::getListing($issue_id);

    // since xml-rpc has issues, lets base64 encode everything
    foreach ($notes as &$note) {
        foreach ($note as $key => $val) {
            $note[$key] = base64_encode($val);
        }
    }

    return new XML_RPC_Response(XML_RPC_Encode($notes));
}

/**
 * @param string $email
 * @param string $password
 * @param int $issue_id
 * @param int $note_id
 * @return array
 */
function getNote($email, $password, $issue_id, $note_id)
{
    $auth = self::authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }

    $note = Note::getNoteBySequence($issue_id, $note_id);

    if (count($note) < 1 || !is_array($note)) {
        return self::userError("Note #" . $note_id . " does not exist for issue #$issue_id");
    }
    // since xml-rpc has issues, lets base64 encode everything
    foreach ($note as $key => $val) {
        $note[$key] = base64_encode($val);
    }

    return new XML_RPC_Response(XML_RPC_Encode($note));
}

/**
 * @param string $email
 * @param string $password
 * @param int $issue_id
 * @param int $note_id
 * @param string $target
 * @param bool $authorize_sender
 * @return string
 */
function convertNote($email, $password, $issue_id, $note_id, $target, $authorize_sender)
{
    $auth = self::authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }


    self::createFakeCookie($email, Issue::getProjectID($issue_id));
    $res = Note::convertNote($note_id, $target, $authorize_sender);
    if (empty($res)) {
        return self::userError("Error converting note");
    }

    return new XML_RPC_Response(XML_RPC_Encode("OK"));
}

/**
 * @param string $email
 * @param string $password
 * @param int $issue_id
 * @return string
 */
function mayChangeIssue($email, $password, $issue_id)
{
    $auth = self::authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }

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

/**
 * @param string $email
 * @param string $password
 * @param int $week
 * @param string $start
 * @param string $end
 * @param int $separate_closed
 * @return string
 */
function getWeeklyReport($email, $password, $week, $start, $end, $separate_closed)
{
    $auth = self::authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $week = abs($week);

    // we have to set a project so the template class works, even though the weekly report doesn't actually need it
    $projects = Project::getAssocList(Auth::getUserID());
    self::createFakeCookie($email, current(array_keys($projects)));

    // figure out the correct week
    if ((empty($start)) || (empty($end))) {
        $start = date("U") - (Date_Helper::DAY * (date("w") - 1));
        if ($week > 0) {
            $start = ($start - (Date_Helper::WEEK * $week));
        }
        $end = date("Y-m-d", ($start + (Date_Helper::DAY * 6)));
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

/**
 * @return string
 */
function getResolutionAssocList()
{
    $res = Resolution::getAssocList();

    return new XML_RPC_Response(XML_RPC_Encode($res));
}

/**
 * @param string $email
 * @param string $password
 * @param string $action
 * @return string
 */
function timeClock($email, $password, $action)
{
    $auth = self::authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }

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
        return self::userError("Error clocking " . $action . ".\n");
    }
}

/**
 * @param array $email
 * @param array $password
 * @param int $issue_id
 * @return array
 */
function getDraftListing($email, $password, $issue_id)
{
    $auth = self::authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }

    $drafts = Draft::getList($issue_id);

    // since xml-rpc has issues, lets base64 encode everything
    foreach ($drafts as &$draft) {
        foreach ($draft as $key => $val) {
            $draft[$key] = base64_encode($val);
        }
    }

    return new XML_RPC_Response(XML_RPC_Encode($drafts));
}

/**
 * @param string $email
 * @param string $password
 * @param int $issue_id
 * @param int $draft_id
 * @return array
 */
function getDraft($email, $password, $issue_id, $draft_id)
{
    $auth = self::authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }

    $draft = Draft::getDraftBySequence($issue_id, $draft_id);

    if ((count($draft) < 1) || (!is_array($draft))) {
        return self::userError("Draft #" . $draft_id . " does not exist for issue #$issue_id");
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

/**
 * @param string $email
 * @param string $password
 * @param int $issue_id
 * @param int $draft_id
 * @return string
 */
function sendDraft($email, $password, $issue_id, $draft_id)
{
    $auth = self::authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }

    $draft = Draft::getDraftBySequence($issue_id, $draft_id);
    self::createFakeCookie($email, Issue::getProjectID($issue_id));

    if ((count($draft) < 1) || (!is_array($draft))) {
        return self::userError("Draft #" . $draft_id . " does not exist for issue #$issue_id");
    }
    $res = Draft::send($draft["emd_id"]);
    if ($res == 1) {
        return new XML_RPC_Response(XML_RPC_Encode("Draft #" . $draft_id . " sent successfully.\n"));
    } else {
        return self::userError("Error sending Draft #" . $draft_id . "\n");
    }
}

/**
 * @param string $email
 * @param string $password
 * @param int $issue_id
 * @param struct $types
 * @return string
 */
function redeemIssue($email, $password, $issue_id, $types)
{
    $auth = self::authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }

    $prj_id = Issue::getProjectID($issue_id);
    self::createFakeCookie($email, $prj_id);
    $customer_id = Issue::getCustomerID($issue_id);

    if (!CRM::hasCustomerIntegration($prj_id)) {
        // no customer integration
        return self::userError("No customer integration for issue #$issue_id");
    }

    $crm = CRM::getInstance($prj_id);
    $all_types = $crm->getIncidentTypes();
    $contract = $crm->getContract(Issue::getContractID($issue_id));

    if (!$contract->hasPerIncident()) {
        // check if is per incident contract
        return self::userError("Customer for issue #$issue_id does not have a per-incident contract");
    }

    // check if incidents are remaining
    foreach ($types as $type_id) {
        if ($contract->isRedeemedIncident($issue_id, $type_id)) {
            return self::userError("Issue #$issue_id is already marked as redeemed incident of type " . $all_types[$type_id]);
        } elseif (!$contract->hasIncidentsLeft($customer_id, $type_id)) {
            return self::userError("Customer for issue #$issue_id has no remaining incidents of type " . $all_types[$type_id]);
        }
    }

    foreach ($types as $type_id) {
        $res = $contract->redeemIncident($issue_id, $type_id);
        if ($res == -1) {
            return self::userError("An error occured trying to mark issue as redeemed.");
        }
    }

    return new XML_RPC_Response(XML_RPC_Encode('OK'));
}

/**
 * @param string $email
 * @param string $password
 * @param int $issue_id
 * @param struct $types
 * @return string
 */
function unredeemIssue($email, $password, $issue_id, $types)
{
    $auth = self::authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }

    $prj_id = Issue::getProjectID($issue_id);
    self::createFakeCookie($email, $prj_id);

    // FIXME: $customer_id unused
    $customer_id = Issue::getCustomerID($issue_id);

    if (!CRM::hasCustomerIntegration($prj_id)) {
        // no customer integration
        return self::userError("No customer integration for issue #$issue_id");
    }

    $crm = CRM::getInstance($prj_id);
    $all_types = $crm->getIncidentTypes();
    $contract = $crm->getContract(Issue::getContractID($issue_id));

    if (!$contract->hasPerIncident()) {
        // check if is per incident contract
        return self::userError("Customer for issue #$issue_id does not have a per-incident contract");
    }

    // check if incidents are remaining
    foreach ($types as $type_id) {
        if (!$contract->isRedeemedIncident($issue_id, $type_id)) {
            return self::userError("Issue #$issue_id is not marked as redeemed incident of type " . $all_types[$type_id]);
        }
    }

    foreach ($types as $type_id) {
        $res = $contract->unRedeemIncident($issue_id, $type_id);
        if ($res == -1) {
            return self::userError("An error occured trying to mark issue as unredeemed.");
        }
    }

    return new XML_RPC_Response(XML_RPC_Encode('OK'));
}

/**
 * @param string $email
 * @param string $password
 * @param int $issue_id
 * @param bool $redeemed_only
 * @return string
 */
function getIncidentTypes($email, $password, $issue_id, $redeemed_only)
{
    $auth = self::authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }

    $prj_id = Issue::getProjectID($issue_id);
    self::createFakeCookie($email, $prj_id);
    // FIXME: $customer_id unused
    $customer_id = Issue::getCustomerID($issue_id);

    if (!CRM::hasCustomerIntegration($prj_id)) {
        // no customer integration
        return self::userError("No customer integration for issue #$issue_id");
    }

    $crm = CRM::getInstance($prj_id);
    // FIXME: $all_types unused
    $all_types = $crm->getIncidentTypes();
    $contract = $crm->getContract(Issue::getContractID($issue_id));

    if (!$contract->hasPerIncident()) {
        // check if is per incident contract
        return self::userError("Customer for issue #$issue_id does not have a per-incident contract");
    }

    $incidents = $contract->getIncidents();
    foreach ($incidents as $type_id => $type_details) {
        $is_redeemed = $contract->isRedeemedIncident($issue_id, $type_id);
        if ((($redeemed_only) && (!$is_redeemed)) || ((!$redeemed_only) && ($is_redeemed))) {
            unset($incidents[$type_id]);
        }
    }

    return new XML_RPC_Response(XML_RPC_Encode($incidents));
}

/**
 * @param string $email
 * @param string $password
 * @param string $command
 * @return string
 */
function logCommand($email, $password, $command)
{
    $auth = self::authenticate($email, $password);
    if (is_object($auth)) {
        return $auth;
    }
    $command = base64_decode($command);

    $msg = $email . "\t" . $command . "\n";

    $fp = @fopen(APP_CLI_LOG, "a");
    @fwrite($fp, $msg);
    @fclose($fp);

    return new XML_RPC_Response(XML_RPC_Encode('OK'));
}
}
