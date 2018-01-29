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

use Access;
use APIAuthToken;
use Auth;
use AuthCookie;
use Authorized_Replier;
use CRM;
use CRMException;
use Custom_Field;
use Date_Helper;
use DateTime;
use DB_Helper;
use Draft;
use Eventum\Attachment\Attachment;
use Eventum\Attachment\AttachmentManager;
use Eventum\Attachment\Exceptions\AttachmentException;
use History;
use InvalidArgumentException;
use Issue;
use Misc;
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
 *
 * @group api
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
     * @param bool $show_all_issues
     * @param string $status
     * @return array
     * @access protected
     */
    public function getOpenIssues($prj_id, $show_all_issues, $status)
    {
        AuthCookie::setProjectCookie($prj_id);
        $status_id = Status::getStatusID($status);
        $usr_id = Auth::getUserID();

        $results = self::getOpenIssuesList($prj_id, $usr_id, $show_all_issues, $status_id);

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
                return AttachmentManager::getMaxAttachmentSize(true);
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

        $res = self::getRemoteAssocListByUser($usr_id, $only_customer_projects);
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
        if (isset($res['contact'])) {
            $res['contact'] = $res['contact']->getDetails();
        }
        // Remove any objects as this can break XMLRPC
        $res = Misc::removeNestedObjects($res);

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
        if (!Access::canUpdateIssue($issue_id, $usr_id)) {
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
     * @since 3.2.2 checks access via Access::canChangeStatus
     */
    public function setIssueStatus($issue_id, $new_status)
    {
        $this->checkIssuePermissions($issue_id);
        $this->checkIssueAssignment($issue_id);

        $usr_id = Auth::getUserID();

        self::updateIssueStatus($issue_id, $usr_id, $new_status);

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
        $projects = self::getRemoteAssocListByUser($assignee);
        if (!array_key_exists($project_id, $projects)) {
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
        $projects = self::getRemoteAssocListByUser($usr_id);
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

        self::updateIssueStatus($issue_id, $usr_id, 'Assigned');

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
            $projects = self::getRemoteAssocListByUser($replier_usr_id);
            if (!array_key_exists($project_id, $projects)) {
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

        $res = AttachmentManager::getList($issue_id);
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
        $res = AttachmentManager::getAttachment($file_id);
        if (empty($res)) {
            throw new RemoteApiException('The requested file could not be found');
        }

        $details = $res->getDetails();
        $details['contents'] = base64_encode($details['contents']);

        return $details;
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

        $this->checkIssuePermissions($issue_id);
        $this->checkIssueAssignment($issue_id);

        try {
            $iaf_id = Attachment::create($filename, $mimetype, $contents)->id;
        } catch (AttachmentException $e) {
            throw new RemoteApiException('File not uploaded', $e->getCode(), $e);
        }

        $iaf_ids = [$iaf_id];
        // TODO: Implement min role properly
        if ($internal_only) {
            $min_role = User::ROLE_USER;
        } else {
            $min_role = User::ROLE_VIEWER;
        }
        AttachmentManager::attachFiles($issue_id, $usr_id, $iaf_ids, $min_role, $file_description);

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
     * @since 3.3.0 checks user access and issue close state
     */
    public function closeIssue($issue_id, $new_status, $resolution_id, $send_notification, $note)
    {
        $this->checkIssuePermissions($issue_id);
        $this->checkIssueAssignment($issue_id);

        $usr_id = Auth::getUserID();

        if (!Access::canChangeStatus($issue_id, $usr_id)) {
            throw new RemoteApiException("User has no access to update issue #$issue_id");
        }

        if (Issue::isClosed($issue_id)) {
            throw new RemoteApiException("Issue #$issue_id already closed");
        }

        $status_id = Status::getStatusID($new_status);
        $prj_id = Issue::getProjectID($issue_id);
        if (!$status_id || !in_array($prj_id, Status::getAssociatedProjects($status_id))) {
            throw new RemoteApiException("Invalid status: $new_status");
        }

        AuthCookie::setProjectCookie($prj_id);

        $res = Issue::close($usr_id, $issue_id, $send_notification, $resolution_id, $status_id, $note);
        if ($res == -1) {
            throw new RemoteApiException("Could not close issue #$issue_id");
        }

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
            unset($email);
        }

        $setup = Setup::get();

        if (isset($setup['description_email_0']) && $setup['description_email_0'] === 'enabled') {
            $issue = Issue::getDetails($issue_id);
            $email = [
                'id' => 0,
                'sup_date' => $issue['iss_created_date'],
                'sup_from' => $issue['reporter'],
                'sup_to' => '',
                'sup_cc' => '',
                'sup_subject' => $issue['iss_summary'],
            ];
            if ($real_emails !== '') {
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
            }

            return 'no';
        }

        return 'yes';
    }

    /**
     * Get data for weekly report.
     *
     * @param int $prj_id The project id
     * @param DateTime $start
     * @param DateTime $end
     * @param struct $options
     * @return struct
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
     * @deprecated since 3.3.0 this method does nothing
     */
    public function logCommand($command)
    {
        return 'OK';
    }

    /**
     * Method used to remotely set the status of a given issue.
     *
     * @param   int $issue_id The issue ID
     * @param   int $usr_id The user ID of the person performing this change
     * @param   int $new_status The new status ID
     * @throws RemoteApiException on errors
     * @since 3.2.2 moved to RemoteApi class
     */
    private static function updateIssueStatus($issue_id, $usr_id, $new_status)
    {
        if (!Access::canChangeStatus($issue_id, $usr_id)) {
            throw new RemoteApiException("User has no access to update issue #$issue_id");
        }

        // check if the given status is a valid option
        $prj_id = Issue::getProjectID($issue_id);
        $statuses = Status::getAbbreviationAssocList($prj_id, false);

        $titles = Misc::lowercase(array_values($statuses));
        $abbreviations = Misc::lowercase(array_keys($statuses));
        if ((!in_array(strtolower($new_status), $titles))
            && (!in_array(strtolower($new_status), $abbreviations))) {
            $message = "Status '$new_status' could not be matched against the list of available statuses";
            throw new RemoteApiException($message);
        }

        // if the user is passing an abbreviation, use the real title instead
        if (in_array(strtolower($new_status), $abbreviations)) {
            $index = array_search(strtolower($new_status), $abbreviations);
            $new_status = $titles[$index];
        }

        $sta_id = Status::getStatusID($new_status);
        if ($sta_id === null) {
            // should not really happen as status fetched from above code
            throw new RemoteApiException("Unable to find status named '$new_status'");
        }

        $res = Issue::setStatus($issue_id, $sta_id);
        if ($res == -2) {
            throw new RemoteApiException("Issue status is already set to '$new_status'");
        }
        if ($res != 1) {
            throw new RemoteApiException("Could not set status for issue #$issue_id");
        }

        // record history entry
        History::add($issue_id, $usr_id, 'remote_status_change',
            "Status remotely changed to '{status}' by {user}", [
            'status' => $new_status,
            'user' => User::getFullName($usr_id),
        ]);
    }

    /**
     * Method used to get all issues associated with a status that doesn't have
     * the 'closed' context.
     *
     * @param   int $prj_id The project ID to list issues from
     * @param   int $usr_id The user ID of the user requesting this information
     * @param   bool $show_all_issues Whether to show all open issues, or just the ones assigned to the given email address
     * @param   int $status_id The status ID to be used to restrict results
     * @return  array The list of open issues
     * @since 3.2.2 moved to RemoteApi class
     */
    private static function getOpenIssuesList($prj_id, $usr_id, $show_all_issues, $status_id)
    {
        $projects = self::getRemoteAssocListByUser($usr_id);
        if (count($projects) == 0) {
            return [];
        }

        $stmt
            = 'SELECT
                    iss_id,
                    iss_summary,
                    sta_title
                 FROM
                    (
                    `issue`,
                    `STATUS`
                    )
                 LEFT JOIN
                    `issue_user`
                 ON
                    isu_iss_id=iss_id
                 WHERE ';
        $params = [];

        if (!empty($status_id)) {
            $stmt .= ' sta_id=? AND ';
            $params[] = $status_id;
        }

        $stmt
            .= '
                    iss_prj_id=? AND
                    sta_id=iss_sta_id AND
                    sta_is_closed=0';
        $params[] = $prj_id;
        if ($show_all_issues == false) {
            $stmt
                .= ' AND
                    isu_usr_id=?';
            $params[] = $usr_id;
        }
        $stmt
            .= "\nGROUP BY
                        iss_id";
        $res = DB_Helper::getInstance()->getAll($stmt, $params);

        if (count($res) > 0) {
            Issue::getAssignedUsersByIssues($res);
        }

        return $res;
    }

    /**
     * Method used to get the list of projects assigned to a given user that
     * allow remote invocation of issues.
     *
     * @param   int $usr_id The user ID
     * @param   bool $only_customer_projects Whether to only include projects with customer integration or not
     * @return  array The list of projects
     * @since 3.2.2 moved to RemoteApi class
     */
    private static function getRemoteAssocListByUser($usr_id, $only_customer_projects = false)
    {
        static $returns;

        if (!$only_customer_projects && !empty($returns[$usr_id])) {
            return $returns[$usr_id];
        }

        $stmt
            = "SELECT
                    prj_id,
                    prj_title
                 FROM
                    `project`,
                    `project_user`
                 WHERE
                    prj_id=pru_prj_id AND
                    pru_usr_id=? AND
                    pru_role > ? AND
                    prj_remote_invocation='enabled'";
        if ($only_customer_projects) {
            $stmt .= " AND prj_customer_backend <> '' AND prj_customer_backend IS NOT NULL ";
        }
        $stmt
            .= '
                 ORDER BY
                    prj_title';
        $res = DB_Helper::getInstance()->getPair($stmt, [$usr_id, User::ROLE_CUSTOMER]);

        // don't cache the results when the optional argument is used to avoid getting bogus results
        if (!$only_customer_projects) {
            $returns[$usr_id] = $res;
        }

        return $res;
    }

    /**
     * @param int $issue_id
     * @see \Command_Line::checkIssuePermissions()
     * @since 3.2.2
     */
    private function checkIssuePermissions($issue_id)
    {
        $projects = $this->getUserAssignedProjects(false);
        $details = $this->getIssueDetails($issue_id);
        $iss_prj_id = (int)$details['iss_prj_id'];

        // check if the issue the user is trying to change is inside a project viewable to him
        $found = 0;
        foreach ($projects as $i => $project) {
            if ($iss_prj_id == $project['id']) {
                $found = 1;
                break;
            }
        }
        if (!$found) {
            throw new RemoteApiException("The assigned project for issue #$issue_id doesn't match any in the list of projects assigned to you");
        }
    }

    /**
     * Checks whether the given user email address is assigned to the given
     * issue ID.
     *
     * @param   int $issue_id The issue ID
     * @see \Command_Line::checkIssueAssignment()
     * @since 3.2.2
     */
    private function checkIssueAssignment($issue_id)
    {
        // check if the current user is allowed to change the given issue
        $may_change_issue = $this->mayChangeIssue($issue_id);

        // if not, show confirmation message
        if ($may_change_issue !== 'yes') {
            throw new RemoteApiException("You are not currently assigned to issue #$issue_id.");
        }
    }
}
