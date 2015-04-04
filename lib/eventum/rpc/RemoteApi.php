<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2015 Eventum Team.                              |
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
// | 51 Franklin Street, Suite 330                                          |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

class RemoteApiException extends RuntimeException
{
}

/**
 * Class RemoteApi
 *
 * All public non-static methods are exposed for XMLRPC
 */
class RemoteApi
{
    /**
     * Fakes the creation of the login cookie
     */
    public static function createFakeCookie($email, $project = false)
    {
        if ($email) {
            $cookie = array(
                "email" => $email
            );
            $_COOKIE[APP_COOKIE] = base64_encode(serialize($cookie));
        }

        if ($project) {
            $cookie = array(
                "prj_id"   => $project,
                "remember" => false
            );
            $_COOKIE[APP_PROJECT_COOKIE] = base64_encode(serialize($cookie));
        }
    }

    /**
     * @param int $prj_id
     * @return struct
     * @access protected
     */
    public function getDeveloperList($prj_id)
    {
        $res = Project::getRemoteAssocList();
        if (empty($res)) {
            throw new RemoteApiException("There are currently no projects setup for remote invocation");
        }
        // check if this project allows remote invocation
        if (!in_array($prj_id, array_keys($res))) {
            throw new RemoteApiException("This project does not allow remote invocation");
        }

        $res = Project::getAddressBookAssocList($prj_id);
        if (empty($res)) {
            throw new RemoteApiException("There are currently no users associated with the given project");
        }

        return $res;
    }

    /**
     * @param int $issue_id
     * @return struct
     * @access protected
     */
    public function getSimpleIssueDetails($issue_id)
    {
        self::createFakeCookie(null, Issue::getProjectID($issue_id));

        $details = Issue::getDetails($issue_id);
        if (empty($details)) {
            throw new RemoteApiException("Issue #$issue_id could not be found");
        }

        return array(
            "summary"          => $details['iss_summary'],
            "customer"         => @$details['customer_info']['customer_name'],
            "status"           => @$details['sta_title'],
            "assignments"      => @$details["assignments"],
            "authorized_names" => @implode(', ', $details['authorized_names']),
        );
    }

    /**
     * @param int $prj_id
     * @param boolean $show_all_issues
     * @param string $status
     * @return array
     * @access protected
     */
    public function getOpenIssues($prj_id, $show_all_issues, $status)
    {
        self::createFakeCookie(false, $prj_id);
        $status_id = Status::getStatusID($status);
        $usr_id = Auth::getUserID();

        $results = Issue::getOpenIssues($prj_id, $usr_id, $show_all_issues, $status_id);

        if (empty($results)) {
            throw new RemoteApiException("There are currently no open issues");
        }

        $structs = array();
        foreach ($results as $res) {
            $structs[] = array(
                'issue_id'       => $res['iss_id'],
                'summary'        => $res['iss_summary'],
                'assigned_users' => $res['assigned_users'],
                'status'         => $res['sta_title'],
            );
        }

        return $structs;
    }

    /**
     * FIXME: this should return bool
     *
     * @param string $email
     * @param string $password
     * @return string
     */
    public function isValidLogin($email, $password)
    {
        if (!Auth::isCorrectPassword($email, $password)) {
            $is_valid = 'no';
        } else {
            $is_valid = 'yes';
        }

        return $is_valid;
    }

    /**
     * @param bool $only_customer_projects
     * @return array
     * @access protected
     */
    public function getUserAssignedProjects($only_customer_projects)
    {
        $usr_id = Auth::getUserID();

        $res = Project::getRemoteAssocListByUser($usr_id, $only_customer_projects);
        if (empty($res)) {
            throw new RemoteApiException("You are not assigned to any projects at this moment or you lack the proper role");
        }

        $structs = array();
        foreach ($res as $prj_id => $prj_title) {
            $structs[] = array(
                "id"    => $prj_id,
                "title" => $prj_title,
            );
        }

        return $structs;
    }

    /**
     * @param int $issue_id
     * @return struct
     * @access protected
     */
    public function getIssueDetails($issue_id)
    {
        self::createFakeCookie(false, Issue::getProjectID($issue_id));

        $res = Issue::getDetails($issue_id);

        // flatten some fields
        if (isset($res['customer'])) {
            $details = $res['customer']->getDetails();
            $res['customer'] = $details;
        }
        if (isset($res['contract'])) {
            $res['contract'] = $res['contract']->getDetails();
        }

        if (empty($res)) {
            throw new RemoteApiException("Issue #$issue_id could not be found");
        }

        // remove some naughty fields
        unset($res['iss_original_description']);

        // returns custom fields in an array
        $res["custom_fields"] = Custom_Field::getListByIssue($res['iss_prj_id'], $res['iss_id']);

        return $res;
    }

    /**
     * @param int $issue_id
     * @return struct
     * @access protected
     */
    public function getTimeTrackingCategories($issue_id)
    {
        $prj_id = Issue::getProjectID($issue_id);
        $res = Time_Tracking::getAssocCategories($prj_id);
        if (empty($res)) {
            throw new RemoteApiException("No time tracking categories could be found");
        }

        return $res;
    }

    /**
     * @param int $issue_id
     * @param int $cat_id
     * @param string $summary
     * @param int $time_spent
     * @return string
     * @access protected
     */
    public function recordTimeWorked($issue_id, $cat_id, $summary, $time_spent)
    {
        $usr_id = Auth::getUserID();

        $res = Time_Tracking::recordRemoteEntry($issue_id, $usr_id, $cat_id, $summary, $time_spent);
        if ($res == -1) {
            throw new RemoteApiException("Could not record the time tracking entry");
        }

        return 'OK';
    }

    /**
     * @param int $issue_id
     * @param string $new_status
     * @return string
     * @access protected
     */
    public function setIssueStatus($issue_id, $new_status)
    {
        $usr_id = Auth::getUserID();

        $res = Issue::setRemoteStatus($issue_id, $usr_id, $new_status);
        if ($res == -1) {
            throw new RemoteApiException("Could not set the status to issue #$issue_id");
        }

        return 'OK';
    }

    /**
     * @param int $issue_id
     * @param int $project_id
     * @param string $developer
     * @return string
     * @access protected
     */
    public function assignIssue($issue_id, $project_id, $developer)
    {
        self::createFakeCookie(false, Issue::getProjectID($issue_id));

        $usr_id = Auth::getUserID();

        $assignee = User::getUserIDByEmail($developer);
        if (empty($assignee)) {
            throw new RemoteApiException("Could not find a user with email '$developer'");
        }

        // check if the assignee is even allowed to be in the given project
        $projects = Project::getRemoteAssocListByUser($assignee);
        if (!in_array($project_id, array_keys($projects))) {
            throw new RemoteApiException(
                "The selected developer is not permitted in the project associated with issue #$issue_id"
            );
        }

        $res = Issue::remoteAssign($issue_id, $usr_id, $assignee);
        if ($res == -1) {
            throw new RemoteApiException("Could not assign issue #$issue_id to $developer");
        }

        return 'OK';
    }

    /**
     * @param int $issue_id
     * @param int $project_id
     * @return string
     * @access protected
     */
    public function takeIssue($issue_id, $project_id)
    {
        self::createFakeCookie(false, Issue::getProjectID($issue_id));

        // check if issue currently is un-assigned
        $current_assignees = Issue::getAssignedUsers($issue_id);
        if (count($current_assignees) > 0) {
            throw new RemoteApiException("Issue is currently assigned to " . implode(',', $current_assignees));
        }

        $usr_id = Auth::getUserID();

        // check if the assignee is even allowed to be in the given project
        $projects = Project::getRemoteAssocListByUser($usr_id);
        if (!in_array($project_id, array_keys($projects))) {
            throw new RemoteApiException(
                "The selected developer is not permitted in the project associated with issue #$issue_id"
            );
        }

        $res = Issue::remoteAssign($issue_id, $usr_id, $usr_id);
        if ($res == -1) {
            $email = User::getEmail($usr_id);
            throw new RemoteApiException("Could not assign issue #$issue_id to $email");
        }

        $res = Issue::setRemoteStatus($issue_id, $usr_id, "Assigned");
        if ($res == -1) {
            throw new RemoteApiException("Could not set status for issue #$issue_id");
        }

        return 'OK';
    }

    /**
     * @param int $issue_id
     * @param int $project_id
     * @param string $new_replier
     * @return string
     * @access protected
     */
    public function addAuthorizedReplier($issue_id, $project_id, $new_replier)
    {
        $usr_id = Auth::getUserID();

        $replier_usr_id = User::getUserIDByEmail($new_replier);

        // if this is an actual user, not just an email address check permissions
        if (!empty($replier_usr_id)) {
            // check if the assignee is even allowed to be in the given project
            $projects = Project::getRemoteAssocListByUser($replier_usr_id);
            if (!in_array($project_id, array_keys($projects))) {
                throw new RemoteApiException(
                    "The given user is not permitted in the project associated with issue #$issue_id"
                );
            }
        }

        // check if user is already authorized
        if (Authorized_Replier::isAuthorizedReplier($issue_id, $new_replier)) {
            throw new RemoteApiException("The given user is already an authorized replier on issue #$issue_id");
        }

        $res = Authorized_Replier::remoteAddAuthorizedReplier($issue_id, $usr_id, $new_replier);
        if ($res == -1) {
            throw new RemoteApiException("Could not add '$new_replier' as an authorized replier to issue #$issue_id");
        }

        return 'OK';
    }

    /**
     * @param int $issue_id
     * @return array
     * @access protected
     */
    public function getFileList($issue_id)
    {
        self::createFakeCookie(false, Issue::getProjectID($issue_id));

        $res = Attachment::getList($issue_id);
        if (empty($res)) {
            throw new RemoteApiException("No files could be found");
        }

        return $res;
    }

    /**
     * @param int $file_id
     * @return struct
     * @access protected
     */
    public function getFile($file_id)
    {
        $res = Attachment::getDetails($file_id);
        if (empty($res)) {
            throw new RemoteApiException("The requested file could not be found");
        }

        return $res;
    }

    /**
     * @param int $prj_id
     * @param string $field
     * @param string $value
     * @access protected
     * @return string
     */
    public function lookupCustomer($prj_id, $field, $value)
    {
        $possible_fields = array('email', 'support', 'customer');
        if (!in_array($field, $possible_fields)) {
            throw new RemoteApiException("Unknown field type '$field'");
        }

        $usr_id = Auth::getUserID();

        // only customers should be able to use this page
        $role_id = User::getRoleByUser($usr_id, $prj_id);
        if ($role_id < User::getRoleID('Developer')) {
            throw new RemoteApiException("You don't have the appropriate permissions to lookup customer information");
        }

        $crm = CRM::getInstance($prj_id);
        if (!$crm) {
            throw new RemoteApiException("Customer Integration not enabled for project $prj_id");
        }
        $res = $crm->lookup($field, $value, array());

        return $res;
    }

    /**
     * @param int $issue_id
     * @param string $new_status
     * @param int $resolution_id
     * @param bool $send_notification
     * @param string $note
     * @return string
     * @access protected
     */
    public function closeIssue($issue_id, $new_status, $resolution_id, $send_notification, $note)
    {
        $usr_id = Auth::getUserID();
        $status_id = Status::getStatusID($new_status);

        self::createFakeCookie(false, Issue::getProjectID($issue_id));

        $res = Issue::close($usr_id, $issue_id, $send_notification, $resolution_id, $status_id, $note);
        if ($res == -1) {
            throw new RemoteApiException("Could not close issue #$issue_id");
        }

        $prj_id = Issue::getProjectID($issue_id);
        if (CRM::hasCustomerIntegration($prj_id)) {
            $crm = CRM::getInstance($prj_id);
            try {
                $contract = $crm->getContract(Issue::getContractID($issue_id));
                if ($contract->hasPerIncident()) {
                    return 'INCIDENT';
                }
            } catch (CRMException $e) {
            }
        }

        return 'OK';
    }

    /**
     * @param int $prj_id
     * @return struct
     * @access protected
     */
    public function getClosedAbbreviationAssocList($prj_id)
    {
        $res = Status::getClosedAbbreviationAssocList($prj_id);

        return $res;
    }

    /**
     * @param int $prj_id
     * @param bool $show_closed
     * @return struct
     * @access protected
     */
    public function getAbbreviationAssocList($prj_id, $show_closed)
    {
        $res = Status::getAbbreviationAssocList($prj_id, $show_closed);

        return $res;
    }

    /**
     * @param int $issue_id
     * @return array
     * @access protected
     */
    public function getEmailListing($issue_id)
    {
        $real_emails = Support::getEmailsByIssue($issue_id);

        $issue = Issue::getDetails($issue_id);
        $email = array(
            'sup_date'    => $issue['iss_created_date'],
            'sup_from'    => $issue['reporter'],
            'sup_to'      => '',
            'sup_cc'      => '',
            'sup_subject' => $issue['iss_summary']
        );
        if ($real_emails != '') {
            $emails = array_merge(array($email), $real_emails);
        } else {
            $emails[] = $email;
        }

        if (is_array($emails)) {
            foreach ($emails as &$email) {
                unset($email["seb_body"]);
            }
        }

        return $emails;
    }

    /**
     * @param int $issue_id
     * @param int $email_id
     * @return array
     * @access protected
     */
    public function getEmail($issue_id, $email_id)
    {
        if ($email_id == 0) {
            // return issue description instead
            $issue = Issue::getDetails($issue_id);
            $email = array(
                'sup_date'           => $issue['iss_created_date'],
                'sup_from'           => $issue['reporter'],
                'sup_to'             => '',
                'recipients'         => '',
                'sup_cc'             => '',
                'sup_has_attachment' => 0,
                'sup_subject'        => $issue['iss_summary'],
                'message'            => $issue['iss_original_description'],
                'seb_full_email'     => $issue['iss_original_description']
            );
        } else {
            $email = Support::getEmailBySequence($issue_id, $email_id);
        }

        // get requested email
        if (count($email) < 1 || !is_array($email)) {
            throw new RemoteApiException("Email #" . $email_id . " does not exist for issue #$issue_id");
        }

        return $email;
    }

    /**
     * @param int $issue_id
     * @return array
     * @access protected
     */
    public function getNoteListing($issue_id)
    {
        self::createFakeCookie(false, Issue::getProjectID($issue_id));
        $notes = Note::getListing($issue_id);

        return $notes;
    }

    /**
     * @param int $issue_id
     * @param int $note_id
     * @return array
     * @access protected
     */
    public function getNote($issue_id, $note_id)
    {
        $note = Note::getNoteBySequence($issue_id, $note_id);

        if (count($note) < 1 || !is_array($note)) {
            throw new RemoteApiException("Note #" . $note_id . " does not exist for issue #$issue_id");
        }

        return $note;
    }

    /**
     * @param int $issue_id
     * @param int $note_id
     * @param string $target
     * @param bool $authorize_sender
     * @return string
     * @access protected
     */
    public function convertNote($issue_id, $note_id, $target, $authorize_sender)
    {
        self::createFakeCookie(false, Issue::getProjectID($issue_id));

        $res = Note::convertNote($note_id, $target, $authorize_sender);
        if (empty($res)) {
            throw new RemoteApiException("Error converting note");
        }

        return 'OK';
    }

    /**
     * TODO: use boolean return
     *
     * @param int $issue_id
     * @return string
     * @access protected
     */
    public function mayChangeIssue($issue_id)
    {
        $usr_id = Auth::getUserID();

        $assignees = Issue::getAssignedUserIDs($issue_id);
        if (count($assignees) > 0) {
            if (in_array($usr_id, $assignees)) {
                return "yes";
            } else {
                return "no";
            }
        } else {
            return "yes";
        }
    }

    /**
     * @param int $week
     * @param string $start
     * @param string $end
     * @param bool $separate_closed
     * @return string
     * @access protected
     */
    public function getWeeklyReport($week, $start, $end, $separate_closed)
    {
        $usr_id = Auth::getUserID();
        $week = abs($week);

        // we have to set a project so the template class works, even though the weekly report doesn't actually need it
        $projects = Project::getAssocList(Auth::getUserID());
        self::createFakeCookie(false, current(array_keys($projects)));

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
        $tpl->assign(array(
            'report_type' => 'weekly',
            'data' => Report::getWeeklyReport($usr_id, $start, $end, $separate_closed)
        ));

        $ret = $tpl->getTemplateContents() . "\n";

        return $ret;
    }

    /**
     * @return array
     */
    public function getResolutionAssocList()
    {
        $res = Resolution::getAssocList();

        return $res;
    }

    /**
     * @param string $action
     * @return string
     * @access protected
     */
    public function timeClock($action)
    {
        $usr_id = Auth::getUserID();
        // TODO: is the email printing neccessary?
        $email = User::getEmail($usr_id);

        if ($action == "in") {
            $res = User::clockIn($usr_id);
        } elseif ($action == "out") {
            $res = User::clockOut($usr_id);
        } else {
            if (User::isClockedIn($usr_id)) {
                $msg = "is clocked in";
            } else {
                $msg = "is clocked out";
            }

            return "$email $msg.\n";
        }

        if ($res == 1) {
            return "$email successfully clocked $action.\n";
        }

        throw new RemoteApiException("Error clocking $action.\n");
    }

    /**
     * @param int $issue_id
     * @return array
     * @access protected
     */
    public function getDraftListing($issue_id)
    {
        $drafts = Draft::getList($issue_id);

        return $drafts;
    }

    /**
     * @param int $issue_id
     * @param int $draft_id
     * @return struct
     * @access protected
     */
    public function getDraft($issue_id, $draft_id)
    {
        $draft = Draft::getDraftBySequence($issue_id, $draft_id);

        if ((count($draft) < 1) || (!is_array($draft))) {
            throw new RemoteApiException("Draft #" . $draft_id . " does not exist for issue #$issue_id");
        }
        if (empty($draft['to'])) {
            $draft['to'] = "Notification List";
        }
        $draft['cc'] = @implode(", ", $draft['cc']);

        return $draft;
    }

    /**
     * @param int $issue_id
     * @param int $draft_id
     * @return string
     * @access protected
     */
    public function sendDraft($issue_id, $draft_id)
    {
        $draft = Draft::getDraftBySequence($issue_id, $draft_id);
        self::createFakeCookie(false, Issue::getProjectID($issue_id));

        if (count($draft) < 1 || !is_array($draft)) {
            throw new RemoteApiException("Draft #" . $draft_id . " does not exist for issue #$issue_id");
        }

        $res = Draft::send($draft["emd_id"]);
        if ($res == 1) {
            return "Draft #{$draft_id} sent successfully.\n";
        }

        throw new RemoteApiException("Error sending Draft #" . $draft_id . "\n");
    }

    /**
     * @param int $issue_id
     * @param array $types
     * @return string
     * @access protected
     */
    public function redeemIssue($issue_id, $types)
    {
        $prj_id = Issue::getProjectID($issue_id);
        self::createFakeCookie(false, $prj_id);
        $customer_id = Issue::getCustomerID($issue_id);

        if (!CRM::hasCustomerIntegration($prj_id)) {
            // no customer integration
            throw new RemoteApiException("No customer integration for issue #$issue_id");
        }

        $crm = CRM::getInstance($prj_id);
        $all_types = $crm->getIncidentTypes();
        $contract = $crm->getContract(Issue::getContractID($issue_id));

        if (!$contract->hasPerIncident()) {
            // check if is per incident contract
            throw new RemoteApiException("Customer for issue #$issue_id does not have a per-incident contract");
        }

        // check if incidents are remaining
        foreach ($types as $type_id) {
            if ($contract->isRedeemedIncident($issue_id, $type_id)) {
                throw new RemoteApiException(
                    "Issue #$issue_id is already marked as redeemed incident of type " . $all_types[$type_id]
                );
            } elseif (!$contract->hasIncidentsLeft($customer_id, $type_id)) {
                throw new RemoteApiException(
                    "Customer for issue #$issue_id has no remaining incidents of type " . $all_types[$type_id]
                );
            }
        }

        foreach ($types as $type_id) {
            $res = $contract->redeemIncident($issue_id, $type_id);
            if ($res == -1) {
                throw new RemoteApiException("An error occured trying to mark issue as redeemed.");
            }
        }

        return 'OK';
    }

    /**
     * @param int $issue_id
     * @param array $types
     * @return string
     * @access protected
     */
    public function unredeemIssue($issue_id, $types)
    {
        $prj_id = Issue::getProjectID($issue_id);
        self::createFakeCookie(false, $prj_id);

        // FIXME: $customer_id unused
        $customer_id = Issue::getCustomerID($issue_id);

        if (!CRM::hasCustomerIntegration($prj_id)) {
            // no customer integration
            throw new RemoteApiException("No customer integration for issue #$issue_id");
        }

        $crm = CRM::getInstance($prj_id);
        $all_types = $crm->getIncidentTypes();
        $contract = $crm->getContract(Issue::getContractID($issue_id));

        if (!$contract->hasPerIncident()) {
            // check if is per incident contract
            throw new RemoteApiException("Customer for issue #$issue_id does not have a per-incident contract");
        }

        // check if incidents are remaining
        foreach ($types as $type_id) {
            if (!$contract->isRedeemedIncident($issue_id, $type_id)) {
                throw new RemoteApiException(
                    "Issue #$issue_id is not marked as redeemed incident of type " . $all_types[$type_id]
                );
            }
        }

        foreach ($types as $type_id) {
            $res = $contract->unRedeemIncident($issue_id, $type_id);
            if ($res == -1) {
                throw new RemoteApiException("An error occured trying to mark issue as unredeemed.");
            }
        }

        return 'OK';
    }

    /**
     * @param int $issue_id
     * @param bool $redeemed_only
     * @return array
     * @access protected
     */
    public function getIncidentTypes($issue_id, $redeemed_only)
    {
        $prj_id = Issue::getProjectID($issue_id);
        self::createFakeCookie(false, $prj_id);
        // FIXME: $customer_id unused
        $customer_id = Issue::getCustomerID($issue_id);

        if (!CRM::hasCustomerIntegration($prj_id)) {
            // no customer integration
            throw new RemoteApiException("No customer integration for issue #$issue_id");
        }

        $crm = CRM::getInstance($prj_id);
        // FIXME: $all_types unused
        $all_types = $crm->getIncidentTypes();
        $contract = $crm->getContract(Issue::getContractID($issue_id));

        if (!$contract->hasPerIncident()) {
            // check if is per incident contract
            throw new RemoteApiException("Customer for issue #$issue_id does not have a per-incident contract");
        }

        $incidents = $contract->getIncidents();
        foreach ($incidents as $type_id => $type_details) {
            $is_redeemed = $contract->isRedeemedIncident($issue_id, $type_id);
            if (($redeemed_only && !$is_redeemed) || (!$redeemed_only && $is_redeemed)) {
                unset($incidents[$type_id]);
            }
        }

        return $incidents;
    }

    /**
     * @param string $command
     * @return string
     * @access protected
     */
    public function logCommand($command)
    {
        $usr_id = Auth::getUserID();
        $email = User::getEmail($usr_id);

        $msg = $email . "\t" . $command . "\n";

        $fp = @fopen(APP_CLI_LOG, "a");
        @fwrite($fp, $msg);
        @fclose($fp);

        return 'OK';
    }
}
