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

namespace Eventum\RPC;

use APIAuthToken;
use Attachment;
use Auth;
use AuthCookie;
use Authorized_Replier;
use CRM;
use CRMException;
use Custom_Field;
use Date_Helper;
use DateTime;
use Draft;
use Eventum\Monolog\Logger;
use InvalidArgumentException;
use Issue;
use Note;
use Project;
use Report;
use Resolution;
use Setup;
use Status;
use Support;
use Template_Helper;
use Time_Tracking;
use User;

/**
 * Class Eventum\RPC\RemoteApi
 *
 * All public non-static methods are exposed for XMLRPC
 */
class RemoteApi
{
    /**
     * @param int $prj_id
     * @return struct
     * @access protected
     */
    public function getDeveloperList($prj_id)
    {
        $res = Project::getRemoteAssocList();
        if (empty($res)) {
            throw new RemoteApiException('There are currently no projects setup for remote invocation');
        }
        // check if this project allows remote invocation
        if (!in_array($prj_id, array_keys($res))) {
            throw new RemoteApiException('This project does not allow remote invocation');
        }

        $res = Project::getAddressBookAssocList($prj_id);
        if (empty($res)) {
            throw new RemoteApiException('There are currently no users associated with the given project');
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
        AuthCookie::setProjectCookie(Issue::getProjectID($issue_id));

        $details = Issue::getDetails($issue_id);
        if (empty($details)) {
            throw new RemoteApiException("Issue #$issue_id could not be found");
        }

        return [
            'summary' => $details['iss_summary'],
            'customer' => isset($details['customer_info']['customer_name']) ? $details['customer_info']['customer_name']
                : null,
            'status' => $details['sta_title'],
            'is_closed' => $details['sta_is_closed'],
            'assignments' => $details['assignments'],
            'authorized_names' => implode(', ', $details['authorized_names']),
        ];
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
        AuthCookie::setProjectCookie($prj_id);
        $status_id = Status::getStatusID($status);
        $usr_id = Auth::getUserID();

        $results = Issue::getOpenIssues($prj_id, $usr_id, $show_all_issues, $status_id);

        if (empty($results)) {
            throw new RemoteApiException('There are currently no open issues');
        }

        $structs = [];
        foreach ($results as $res) {
            $structs[] = [
                'issue_id' => $res['iss_id'],
                'summary' => $res['iss_summary'],
                'assigned_users' => $res['assigned_users'],
                'status' => $res['sta_title'],
            ];
        }

        return $structs;
    }

    /**
     * @param string $email
     * @param string $password
     * @return bool
     * @access public
     */
    public function isValidLogin($email, $password)
    {
        if (!Auth::isCorrectPassword($email, $password) && !APIAuthToken::isTokenValidForEmail($password, $email)) {
            $is_valid = false;
        } else {
            $is_valid = true;
        }

        return $is_valid;
    }

    /**
     * Method used to check if Eventum RPC can be reached
     *
     * @return bool
     * @access protected
     * @since 3.0.2
     */
    public function checkAuthentication()
    {
        $usr_id = Auth::getUserID();
        if (!$usr_id) {
            throw new RemoteApiException('Not authenticated');
        }

        return true;
    }

    /**
     * Method used to retrieve server parameters
     *
     * @param string $parameter
     * @return string
     * @access protected
     * @since 3.0.2
     */
    public function getServerParameter($parameter)
    {
        switch ($parameter) {
            case 'upload_max_filesize':
                return Attachment::getMaxAttachmentSize(true);
        }
        throw new InvalidArgumentException("Invalid parameter: $parameter");
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
            throw new RemoteApiException(
                'You are not assigned to any projects at this moment or you lack the proper role'
            );
        }

        $structs = [];
        foreach ($res as $prj_id => $prj_title) {
            $structs[] = [
                'id' => $prj_id,
                'title' => $prj_title,
            ];
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
        AuthCookie::setProjectCookie(Issue::getProjectID($issue_id));

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

        // returns custom fields in an array
        $res['custom_fields'] = Custom_Field::getListByIssue($res['iss_prj_id'], $res['iss_id']);

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
            throw new RemoteApiException('No time tracking categories could be found');
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
     * @since 3.0.2 checks access via Issue::canUpdate
     */
    public function recordTimeWorked($issue_id, $cat_id, $summary, $time_spent)
    {
        $usr_id = Auth::getUserID();
        if (!Issue::canUpdate($issue_id, $usr_id)) {
            throw new RemoteApiException("No access to issue #{$issue_id}");
        }

        $res = Time_Tracking::recordRemoteTimeEntry($issue_id, $usr_id, $cat_id, $summary, $time_spent);
        if ($res == -1) {
            throw new RemoteApiException('Could not record the time tracking entry');
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
        AuthCookie::setProjectCookie(Issue::getProjectID($issue_id));

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
        AuthCookie::setProjectCookie(Issue::getProjectID($issue_id));

        // check if issue currently is un-assigned
        $current_assignees = Issue::getAssignedUsers($issue_id);
        if (count($current_assignees) > 0) {
            throw new RemoteApiException('Issue is currently assigned to ' . implode(',', $current_assignees));
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

        $res = Issue::setRemoteStatus($issue_id, $usr_id, 'Assigned');
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
        AuthCookie::setProjectCookie(Issue::getProjectID($issue_id));

        $res = Attachment::getList($issue_id);
        if (empty($res)) {
            throw new RemoteApiException('No files could be found');
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
            throw new RemoteApiException('The requested file could not be found');
        }

        return $res;
    }

    /**
     * Upload single file to an issue.
     *
     * @param int $issue_id
     * @param string $filename
     * @param string $mimetype
     * @param base64 $contents
     * @param string $file_description
     * @param bool $internal_only
     * @return struct
     * @access protected
     * @since 3.0.2
     */
    public function addFile($issue_id, $filename, $mimetype, $contents, $file_description, $internal_only)
    {
        $filesize = strlen($contents);
        if (!$filesize) {
            throw new RemoteApiException('Empty file uploaded');
        }

        $usr_id = Auth::getUserID();
        if (!$usr_id) {
            throw new RemoteApiException('Not authenticated');
        }

        $iaf_id = Attachment::addFile(0, $filename, $mimetype, $contents);
        if (!$iaf_id) {
            throw new RemoteApiException('File not uploaded');
        }

        $iaf_ids = [$iaf_id];
        Attachment::attachFiles($issue_id, $usr_id, $iaf_ids, $internal_only, $file_description);

        $res = [
            'usr_id' => $usr_id,
            'iaf_id' => $iaf_id,
            'filesize' => $filesize,
        ];

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
        $possible_fields = ['email', 'support', 'customer'];
        if (!in_array($field, $possible_fields)) {
            throw new RemoteApiException("Unknown field type '$field'");
        }

        $usr_id = Auth::getUserID();

        // only customers should be able to use this page
        $role_id = User::getRoleByUser($usr_id, $prj_id);
        if ($role_id < User::ROLE_DEVELOPER) {
            throw new RemoteApiException("You don't have the appropriate permissions to lookup customer information");
        }

        $crm = CRM::getInstance($prj_id);
        if (!$crm) {
            throw new RemoteApiException("Customer Integration not enabled for project $prj_id");
        }
        $res = $crm->lookup($field, $value, []);

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

        AuthCookie::setProjectCookie(Issue::getProjectID($issue_id));

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
        if (is_array($real_emails)) {
            foreach ($real_emails as $i => &$email) {
                $email['id'] = $i + 1;
                unset($email['seb_body']);
            }
        }

        $setup = Setup::get();

        if (isset($setup['description_email_0']) && $setup['description_email_0'] == 'enabled') {
            $issue = Issue::getDetails($issue_id);
            $email = [
                'id' => 0,
                'sup_date' => $issue['iss_created_date'],
                'sup_from' => $issue['reporter'],
                'sup_to' => '',
                'sup_cc' => '',
                'sup_subject' => $issue['iss_summary'],
            ];
            if ($real_emails != '') {
                $emails = array_merge([$email], $real_emails);
            } else {
                $emails[] = $email;
            }
        } else {
            $emails = $real_emails;
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
            $email = [
                'sup_date' => $issue['iss_created_date'],
                'sup_from' => $issue['reporter'],
                'sup_to' => '',
                'recipients' => '',
                'sup_cc' => '',
                'sup_has_attachment' => 0,
                'sup_subject' => $issue['iss_summary'],
                'message' => $issue['iss_original_description'],
                'seb_full_email' => $issue['iss_original_description'],
            ];
        } else {
            $email = Support::getEmailBySequence($issue_id, $email_id);
        }

        // get requested email
        if (count($email) < 1 || !is_array($email)) {
            throw new RemoteApiException('Email #' . $email_id . " does not exist for issue #$issue_id");
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
        AuthCookie::setProjectCookie(Issue::getProjectID($issue_id));
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
            throw new RemoteApiException('Note #' . $note_id . " does not exist for issue #$issue_id");
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
        AuthCookie::setProjectCookie(Issue::getProjectID($issue_id));

        $res = Note::convertNote($note_id, $target, $authorize_sender);
        if (empty($res)) {
            throw new RemoteApiException('Error converting note');
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
                return 'yes';
            } else {
                return 'no';
            }
        } else {
            return 'yes';
        }
    }

    /**
     * Get data for weekly report.
     *
     * @param int $prj_id The project id
     * @param DateTime $start
     * @param DateTime $end
     * @param struct $options
     * @return string
     * @access protected
     * @since 3.0.2
     */
    public function getWeeklyReportData($prj_id, $start, $end, $options)
    {
        $usr_id = Auth::getUserID();

        return Report::getWeeklyReport($usr_id, $prj_id, $start, $end, $options);
    }

    /**
     * @param int $week
     * @param string $start
     * @param string $end
     * @param bool $separate_closed
     * @param int $prj_id
     * @return string
     * @access protected
     * @deprecated use getWeeklyReportData() and format data yourself
     */
    public function getWeeklyReport($week, $start, $end, $separate_closed, $prj_id)
    {
        $usr_id = Auth::getUserID();
        $week = abs($week);
        AuthCookie::setProjectCookie($prj_id);
        $prj_id = Auth::getCurrentProject();

        // figure out the correct week
        if ((empty($start)) || (empty($end))) {
            $start = date('U') - (Date_Helper::DAY * (date('w') - 1));
            if ($week > 0) {
                $start = ($start - (Date_Helper::WEEK * $week));
            }
            $end = date('Y-m-d', ($start + (Date_Helper::DAY * 6)));
            $start = date('Y-m-d', $start);
        }

        if ($separate_closed) {
            // emulate smarty value for reports/weekly_data.tpl.tmpl:
            // {if $smarty.post.separate_closed == 1}
            $_POST['separate_closed'] = true;
        }
        $options = [
            'separate_closed' => $separate_closed,
        ];
        $tpl = new Template_Helper();
        $tpl->setTemplate('reports/weekly_data.tpl.html');
        $tpl->assign(
            [
                'report_type' => 'weekly',
                'data' => Report::getWeeklyReport($usr_id, $prj_id, $start, $end, $options),
            ]
        );

        $ret = strip_tags($tpl->getTemplateContents()) . "\n";

        return $ret;
    }

    /**
     * @return array
     * @access public
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
        // TODO: is the email printing necessary?
        $email = User::getEmail($usr_id);

        if ($action == 'in') {
            $res = User::clockIn($usr_id);
        } elseif ($action == 'out') {
            $res = User::clockOut($usr_id);
        } else {
            if (User::isClockedIn($usr_id)) {
                $msg = 'is clocked in';
            } else {
                $msg = 'is clocked out';
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
            throw new RemoteApiException('Draft #' . $draft_id . " does not exist for issue #$issue_id");
        }
        if (empty($draft['to'])) {
            $draft['to'] = 'Notification List';
        }
        $draft['cc'] = @implode(', ', $draft['cc']);

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
        AuthCookie::setProjectCookie(Issue::getProjectID($issue_id));

        if (count($draft) < 1 || !is_array($draft)) {
            throw new RemoteApiException('Draft #' . $draft_id . " does not exist for issue #$issue_id");
        }

        $res = Draft::send($draft['emd_id']);
        if ($res == 1) {
            return "Draft #{$draft_id} sent successfully.\n";
        }

        throw new RemoteApiException('Error sending Draft #' . $draft_id . "\n");
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
        AuthCookie::setProjectCookie($prj_id);
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
                throw new RemoteApiException('An error occurred trying to mark issue as redeemed.');
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
        AuthCookie::setProjectCookie($prj_id);

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
                throw new RemoteApiException('An error occurred trying to mark issue as unredeemed.');
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
        AuthCookie::setProjectCookie($prj_id);
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

    // FIXME: this method should be used by SERVER, not by CLIENT

    /**
     * @param string $command
     * @return string
     * @access protected
     */
    public function logCommand($command)
    {
        $usr_id = Auth::getUserID();
        $email = User::getEmail($usr_id);

        Logger::cli()->info($command, ['usr_id' => $usr_id, 'email' => $email]);

        return 'OK';
    }
}
