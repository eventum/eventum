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

use Eventum\Db\DatabaseException;
use Eventum\Model\Repository\IssueAssociationRepository;

/**
 * Class designed to handle all business logic related to the issues in the
 * system, such as adding or updating them or listing them in the grid mode.
 */
class Issue
{
    /**
     * Method used to check whether a given issue ID exists or not.
     *
     * @param   int $issue_id The issue ID
     * @param   bool $check_project If we should check that this issue is in the current project
     * @return  bool
     */
    public static function exists($issue_id, $check_project = true)
    {
        $stmt = 'SELECT
                    COUNT(*)
                 FROM
                    {{%issue}}
                 WHERE
                    iss_id=?';
        $params = [$issue_id];
        if ($check_project) {
            $stmt .= ' AND
                    iss_prj_id = ?';
            $params[] = Auth::getCurrentProject();
        }
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, $params);
        } catch (DatabaseException $e) {
            return false;
        }

        return $res != 0;
    }

    /**
     * Method used to get the full list of date fields available to issues, to
     * be used when customizing the issue listing screen in the 'last status
     * change date' column.
     *
     * @param   bool $display_customer_fields Whether to include any customer related fields or not
     * @return  array The list of available date fields
     */
    public static function getDateFieldsAssocList($display_customer_fields = false)
    {
        $fields = [
            'iss_created_date' => ev_gettext('Created Date'),
            'iss_updated_date' => ev_gettext('Last Updated Date'),
            'iss_last_response_date' => ev_gettext('Last Response Date'),
            'iss_closed_date' => ev_gettext('Closed Date'),
        ];
        if ($display_customer_fields) {
            $fields['iss_last_customer_action_date'] = 'Customer Action Date';
        }
        asort($fields);

        return $fields;
    }

    /**
     * Method used to get the full list of issue IDs and their respective
     * titles associated to a given project.
     *
     * @param   int $prj_id The project ID
     * @return  array The list of issues
     */
    public static function getAssocListByProject($prj_id)
    {
        $stmt = 'SELECT
                    iss_id,
                    iss_summary
                 FROM
                    {{%issue}}
                 WHERE
                    iss_prj_id=?
                 ORDER BY
                    iss_id ASC';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, [$prj_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the status of a given issue.
     *
     * @param   int $issue_id The issue ID
     * @return  int The status ID
     */
    public static function getStatusID($issue_id)
    {
        static $returns;

        if (!empty($returns[$issue_id])) {
            return $returns[$issue_id];
        }

        $stmt = 'SELECT
                    iss_sta_id
                 FROM
                    {{%issue}}
                 WHERE
                    iss_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$issue_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        $returns[$issue_id] = $res;

        return $res;
    }

    /**
     * Records the last customer action date for a given issue ID.
     *
     * @param   int $issue_id The issue ID
     * @return  int 1 if the update worked, -1 otherwise
     */
    public static function recordLastCustomerAction($issue_id)
    {
        $stmt = "UPDATE
                    {{%issue}}
                 SET
                    iss_last_customer_action_date=?,
                    iss_last_public_action_date=?,
                    iss_last_public_action_type='customer action'
                 WHERE
                    iss_id=?";
        $params = [Date_Helper::getCurrentDateGMT(), Date_Helper::getCurrentDateGMT(), $issue_id];
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Returns the customer ID associated with the given issue ID.
     *
     * @param   int $issue_id The issue ID
     * @return  int The customer ID associated with the issue
     */
    public static function getCustomerID($issue_id)
    {
        static $returns;

        if (!empty($returns[$issue_id])) {
            return $returns[$issue_id];
        }

        $stmt = 'SELECT
                    iss_customer_id
                 FROM
                    {{%issue}}
                 WHERE
                    iss_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$issue_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        $returns[$issue_id] = $res;

        return $res;
    }

    /**
     * Returns the contract ID associated with the given issue ID.
     *
     * @param   int $issue_id The issue ID
     * @return  int The contract ID associated with the issue
     */
    public static function getContractID($issue_id)
    {
        static $returns;

        if (!empty($returns[$issue_id])) {
            return $returns[$issue_id];
        }

        $stmt = 'SELECT
                    iss_customer_contract_id
                 FROM
                    {{%issue}}
                 WHERE
                    iss_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$issue_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        $returns[$issue_id] = $res;

        return $res;
    }

    /**
     * Sets the contract ID for a specific issue.
     *
     * @param   int $issue_id The issue ID
     * @param   int $contract_id The contract ID
     * @return  int 1 if the update worked, -1 otherwise
     * @deprecated method not used?
     */
    public static function setContractID($issue_id, $contract_id)
    {
        $old_contract_id = self::getContractID($issue_id);

        $stmt = 'UPDATE
                    {{%issue}}
                SET
                    iss_customer_contract_id = ?
                 WHERE
                    iss_id=?';
        try {
            DB_Helper::getInstance()->query($stmt, [$contract_id, $issue_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        $usr_id = Auth::getUserID();
        History::add($issue_id, $usr_id, 'contract_changed', 'Contract changed from {old_contract_id} to {contract_id} by {user}', [
            'old_contract_id' => $old_contract_id,
            'contract_id' => $contract_id,
            'user' => User::getFullName($usr_id),
        ]);

        return 1;
    }

    /**
     * Returns the customer ID associated with the given issue ID.
     *
     * @param   int $issue_id The issue ID
     * @return  int The customer ID associated with the issue
     * @deprecated method not used?
     */
    public static function getContactID($issue_id)
    {
        static $returns;

        if (!empty($returns[$issue_id])) {
            return $returns[$issue_id];
        }

        $stmt = 'SELECT
                    iss_customer_contact_id
                 FROM
                    {{%issue}}
                 WHERE
                    iss_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$issue_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        $returns[$issue_id] = $res;

        return $res;
    }

    /**
     * Method used to get the project associated to a given issue.
     *
     * @param   int $issue_id The issue ID
     * @param   bool $force_refresh if the cache should not be used
     * @return  int The project ID
     */
    public static function getProjectID($issue_id, $force_refresh = false)
    {
        static $returns;

        if ((!empty($returns[$issue_id])) && ($force_refresh != true)) {
            return $returns[$issue_id];
        }

        $stmt = 'SELECT
                    iss_prj_id
                 FROM
                    {{%issue}}
                 WHERE
                    iss_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$issue_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        $returns[$issue_id] = $res;

        return $res;
    }

    /**
     * Method used to remotely assign a given issue to an user.
     *
     * @param   int $issue_id The issue ID
     * @param   int $usr_id The user ID of the person performing the change
     * @param   bool $assignee The user ID of the assignee
     * @return  int The status ID
     */
    public static function remoteAssign($issue_id, $usr_id, $assignee)
    {
        Workflow::handleAssignmentChange(self::getProjectID($issue_id), $issue_id, $usr_id, self::getDetails($issue_id), [$assignee], true);
        // clear up the assignments for this issue, and then assign it to the current user
        self::deleteUserAssociations($issue_id, $usr_id);
        $res = self::addUserAssociation($usr_id, $issue_id, $assignee, false);
        if ($res != -1) {
            History::add($issue_id, $usr_id, 'remote_assigned', 'Issue remotely assigned to {assignee} by {user}', [
                'assignee' => User::getFullName($assignee),
                'user' => User::getFullName($usr_id),
            ]);
            Notification::subscribeUser($usr_id, $issue_id, $assignee, Notification::getDefaultActions($issue_id, User::getEmail($assignee), 'remote_assign'), false);
            if ($assignee != $usr_id) {
                Notification::notifyNewAssignment([$assignee], $issue_id);
            }
        }

        return $res;
    }

    /**
     * Method used to set the status of a given issue.
     *
     * @param   int $issue_id The issue ID
     * @param   int $status_id The new status ID
     * @param   bool $notify if a notification should be sent about this change
     * @return  int 1 if the update worked, -1 otherwise
     */
    public static function setStatus($issue_id, $status_id, $notify = false)
    {
        $workflow = Workflow::preStatusChange(self::getProjectID($issue_id), $issue_id, $status_id, $notify);
        if ($workflow !== true) {
            return $workflow;
        }

        // check if the status is already set to the 'new' one
        if (self::getStatusID($issue_id) == $status_id) {
            return -1;
        }

        $old_status = self::getStatusID($issue_id);
        $old_details = Status::getDetails($old_status);

        $stmt = "UPDATE
                    {{%issue}}
                 SET
                    iss_sta_id=?,
                    iss_updated_date=?,
                    iss_last_public_action_date=?,
                    iss_last_public_action_type='update',
                    iss_status_change_date=?
                 WHERE
                    iss_id=?";

        $params = [$status_id, Date_Helper::getCurrentDateGMT(), Date_Helper::getCurrentDateGMT(), Date_Helper::getCurrentDateGMT(), $issue_id];
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return -1;
        }

        // clear out the last-triggered-reminder flag when changing the status of an issue
        Reminder_Action::clearLastTriggered($issue_id);

        // if old status was closed and new status is not, clear closed data from issue.
        if (@$old_details['sta_is_closed'] == 1) {
            $new_details = Status::getDetails($status_id);
            if ($new_details['sta_is_closed'] != 1) {
                self::clearClosed($issue_id);
            }
        }

        if ($notify) {
            Notification::notifyStatusChange($issue_id, $old_status, $status_id);
        }

        return 1;
    }

    /**
     * Method used to remotely set the status of a given issue.
     *
     * @param   int $issue_id The issue ID
     * @param   int $usr_id The user ID of the person performing this change
     * @param   int $new_status The new status ID
     * @return  int 1 if the update worked, -1 otherwise
     */
    public static function setRemoteStatus($issue_id, $usr_id, $new_status)
    {
        $sta_id = Status::getStatusID($new_status);

        $res = self::setStatus($issue_id, $sta_id);
        if ($res == 1) {
            // record history entry
            History::add($issue_id, $usr_id, 'remote_status_change', "Status remotely changed to '{status}' by {user}", [
                'status' => $new_status,
                'user' => User::getFullName($usr_id),
            ]);
        }

        return $res;
    }

    /**
     * Method used to set the release of an issue
     *
     * @param   int $issue_id The ID of the issue
     * @param   int $pre_id The ID of the release to set this issue too
     * @return  int 1 if the update worked, -1 otherwise
     */
    public static function setRelease($issue_id, $pre_id)
    {
        if ($pre_id != self::getRelease($issue_id)) {
            $sql = 'UPDATE
                        {{%issue}}
                    SET
                        iss_pre_id = ?
                    WHERE
                        iss_id = ?';
            try {
                DB_Helper::getInstance()->query($sql, [$pre_id, $issue_id]);
            } catch (DatabaseException $e) {
                return -1;
            }
        }

        return 1;
    }

    /**
     * Returns the current release of an issue
     *
     * @param   int $issue_id The ID of the issue
     * @return  int The release ID
     */
    public static function getRelease($issue_id)
    {
        $sql = 'SELECT
                    iss_pre_id
                FROM
                    {{%issue}}
                WHERE
                    iss_id = ?';
        try {
            $res = DB_Helper::getInstance()->getOne($sql, [$issue_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        return $res;
    }

    /**
     * Method used to set the priority of an issue
     *
     * @param   int $issue_id The ID of the issue
     * @param   int $pri_id The ID of the priority to set this issue too
     * @return  int 1 if the update worked, -1 otherwise
     */
    public static function setPriority($issue_id, $pri_id)
    {
        if ($pri_id != self::getPriority($issue_id)) {
            $sql = 'UPDATE
                        {{%issue}}
                    SET
                        iss_pri_id = ?
                    WHERE
                        iss_id = ?';
            try {
                DB_Helper::getInstance()->query($sql, [$pri_id, $issue_id]);
            } catch (DatabaseException $e) {
                return -1;
            }
        }

        return 1;
    }

    /**
     * Returns the current issue priority
     *
     * @param   int $issue_id The ID of the issue
     * @return  int The priority
     */
    public static function getPriority($issue_id)
    {
        $sql = 'SELECT
                    iss_pri_id
                FROM
                    {{%issue}}
                WHERE
                    iss_id = ?';
        try {
            $res = DB_Helper::getInstance()->getOne($sql, [$issue_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        return $res;
    }

    /**
     * Method used to set the severity of an issue
     *
     * @param   int $issue_id The ID of the issue
     * @param   int $sev_id The ID of the severity to set this issue to
     * @return  int 1 if the update worked, -1 otherwise
     */
    public static function setSeverity($issue_id, $sev_id)
    {
        if ($sev_id != self::getSeverity($issue_id)) {
            $sql = 'UPDATE
                        {{%issue}}
                    SET
                        iss_sev_id = ?
                    WHERE
                        iss_id = ?';
            try {
                DB_Helper::getInstance()->query($sql, [$sev_id, $issue_id]);
            } catch (DatabaseException $e) {
                return -1;
            }
        }

        return 1;
    }

    /**
     * Returns the current issue severity
     *
     * @param   int $issue_id The ID of the issue
     * @return  int The severity
     */
    public static function getSeverity($issue_id)
    {
        $sql = 'SELECT
                    iss_sev_id
                FROM
                    {{%issue}}
                WHERE
                    iss_id = ?';
        try {
            $res = DB_Helper::getInstance()->getOne($sql, [$issue_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        return $res;
    }

    /**
     * Method used to set the expected resolution date of an issue
     *
     * @param   int $issue_id The ID of the issue
     * @param   string $expected_resolution_date The Expected Resolution Date to set this issue too
     * @return  int 1 if the update worked, -1 otherwise
     */
    public static function setExpectedResolutionDate($issue_id, $expected_resolution_date)
    {
        $current = self::getExpectedResolutionDate($issue_id);
        if ($expected_resolution_date != $current) {
            $expected_resolution_date = $expected_resolution_date ?: null;
            $sql = 'UPDATE
                        {{%issue}}
                    SET
                        iss_expected_resolution_date = ?
                    WHERE
                        iss_id = ?';
            try {
                DB_Helper::getInstance()->query($sql, [$expected_resolution_date, $issue_id]);
            } catch (DatabaseException $e) {
                return -1;
            }

            $usr_id = Auth::getUserID();
            Notification::notifyIssueUpdated($issue_id, ['iss_expected_resolution_date' => $current], ['expected_resolution_date' => $expected_resolution_date], []);
            History::add($issue_id, $usr_id, 'issue_updated', 'Issue updated (Expected Resolution Date: {changes}) by {user}', [
                'changes' => History::formatChanges($current, $expected_resolution_date),
                'user' => User::getFullName($usr_id),
            ]);

            return 1;
        }

        return -1;
    }

    /**
     * Returns the current issue expected resolution date
     *
     * @param   int $issue_id The ID of the issue
     * @return  string The Expected Resolution Date
     */
    public static function getExpectedResolutionDate($issue_id)
    {
        $sql = 'SELECT
                    iss_expected_resolution_date
                FROM
                    {{%issue}}
                WHERE
                    iss_id = ?';
        try {
            $res = DB_Helper::getInstance()->getOne($sql, [$issue_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        return $res;
    }

    /**
     * Method used to set the category of an issue
     *
     * @param   int $issue_id The ID of the issue
     * @param   int $prc_id The ID of the category to set this issue too
     * @return  int 1 if the update worked, -1 otherwise
     */
    public static function setCategory($issue_id, $prc_id)
    {
        if ($prc_id != self::getPriority($issue_id)) {
            $sql = 'UPDATE
                        {{%issue}}
                    SET
                        iss_prc_id = ?
                    WHERE
                        iss_id = ?';
            try {
                DB_Helper::getInstance()->query($sql, [$prc_id, $issue_id]);
            } catch (DatabaseException $e) {
                return -1;
            }
        }

        return 1;
    }

    /**
     * Returns the current issue category
     *
     * @param   int $issue_id The ID of the issue
     * @return  int The category
     */
    public static function getCategory($issue_id)
    {
        $sql = 'SELECT
                    iss_prc_id
                FROM
                    {{%issue}}
                WHERE
                    iss_id = ?';
        try {
            $res = DB_Helper::getInstance()->getOne($sql, [$issue_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        return $res;
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
     */
    public static function getOpenIssues($prj_id, $usr_id, $show_all_issues, $status_id)
    {
        $projects = Project::getRemoteAssocListByUser($usr_id);
        if (count($projects) == 0) {
            return '';
        }

        $stmt = 'SELECT
                    iss_id,
                    iss_summary,
                    sta_title
                 FROM
                    (
                    {{%issue}},
                    {{%status}}
                    )
                 LEFT JOIN
                    {{%issue_user}}
                 ON
                    isu_iss_id=iss_id
                 WHERE ';
        $params = [];

        if (!empty($status_id)) {
            $stmt .= ' sta_id=? AND ';
            $params[] = $status_id;
        }

        $stmt .= '
                    iss_prj_id=? AND
                    sta_id=iss_sta_id AND
                    sta_is_closed=0';
        $params[] = $prj_id;
        if ($show_all_issues == false) {
            $stmt .= ' AND
                    isu_usr_id=?';
            $params[] = $usr_id;
        }
        $stmt .= "\nGROUP BY
                        iss_id";
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, $params);
        } catch (DatabaseException $e) {
            return '';
        }

        if (count($res) > 0) {
            self::getAssignedUsersByIssues($res);
        }

        return $res;
    }

    /**
     * Method used to build the required parameters to simulate an email reply
     * to the user who reported the issue, using the issue details like summary
     * and description as email fields.
     *
     * @param   int $issue_id The issue ID
     * @return  array The email parameters
     */
    public static function getReplyDetails($issue_id)
    {
        $stmt = 'SELECT
                    iss_created_date,
                    usr_full_name AS reporter,
                    usr_email AS reporter_email,
                    iss_description AS description,
                    iss_summary AS sup_subject
                 FROM
                    {{%issue}},
                    {{%user}}
                 WHERE
                    iss_usr_id=usr_id AND
                    iss_id=?';
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, [$issue_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        // TRANSLATORS: %1 = issue_id, %2 = issue summary
        $res['reply_subject'] = ev_gettext('Re: [#%1$s] %2$s', (int)$issue_id, $res['sup_subject']);
        $res['created_date_ts'] = Date_Helper::getUnixTimestamp($res['iss_created_date'], 'GMT');

        return $res;
    }

    /**
     * Method used to record the last updated timestamp for a given
     * issue ID.
     *
     * @param   int $issue_id The issue ID
     * @param   string $type The type of update that was made (optional)
     * @return  bool
     */
    public static function markAsUpdated($issue_id, $type = null)
    {
        $public = ['staff response', 'customer action', 'file uploaded', 'user response'];
        $stmt = "UPDATE
                    {{%issue}}
                 SET
                    iss_updated_date=?\n";
        $params = [
            Date_Helper::getCurrentDateGMT(),
        ];

        if ($type) {
            if (in_array($type, $public)) {
                $field = 'iss_last_public_action_';
            } else {
                $field = 'iss_last_internal_action_';
            }
            $stmt .= ",\n " . $field . "date = ?,\n" .
                $field . "type  = ?\n";
            $params[] = Date_Helper::getCurrentDateGMT();
            $params[] = $type;
        }
        $stmt .= 'WHERE
                    iss_id=?';
        $params[] = $issue_id;

        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return false;
        }

        // update last response dates if this is a staff response
        if ($type == 'staff response') {
            $stmt = 'UPDATE
                        {{%issue}}
                     SET
                        iss_last_response_date=?
                     WHERE
                        iss_id = ?';
            DB_Helper::getInstance()->query($stmt, [Date_Helper::getCurrentDateGMT(), $issue_id]);

            $stmt = 'UPDATE
                        {{%issue}}
                     SET
                        iss_first_response_date=?
                     WHERE
                        iss_first_response_date IS NULL AND
                        iss_id = ?';
            DB_Helper::getInstance()->query($stmt, [Date_Helper::getCurrentDateGMT(), $issue_id]);
        }

        return true;
    }

    /**
     * Method used to check whether a given issue has duplicates
     * or not.
     *
     * @param   int $issue_id The issue ID
     * @return  bool
     */
    public static function hasDuplicates($issue_id)
    {
        $stmt = 'SELECT
                    COUNT(iss_id)
                 FROM
                    {{%issue}}
                 WHERE
                    iss_duplicated_iss_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$issue_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        return $res != 0;
    }

    /**
     * Method used to update the duplicated issues for a given
     * issue ID.
     *
     * @param   int $issue_id The issue ID
     * @return  int 1 if the update worked, -1 otherwise
     */
    public static function updateDuplicates($issue_id)
    {
        $ids = self::getDuplicateList($issue_id);
        if ($ids == '') {
            return -1;
        }
        $ids = array_keys($ids);
        $stmt = "UPDATE
                    {{%issue}}
                 SET
                    iss_updated_date=?,
                    iss_last_internal_action_date=?,
                    iss_last_internal_action_type='updated',
                    iss_prc_id=?,";
        $params = [
            Date_Helper::getCurrentDateGMT(),
            Date_Helper::getCurrentDateGMT(),
            $_POST['category'],
        ];

        if (@$_POST['keep'] == 'no') {
            $stmt .= 'iss_pre_id=?,';
            $params[] = $_POST['release'];
        }
        $stmt .= '
                    iss_pri_id=?,
                    iss_sta_id=?,
                    iss_res_id=?
                 WHERE
                    iss_id IN (' . DB_Helper::buildList($ids) . ')';
        $params[] = $_POST['priority'];
        $params[] = $_POST['status'];
        $params[] = $_POST['resolution'];
        $params = array_merge($params, $ids);
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return -1;
        }

        // record the change
        $issue_id = (int)$issue_id;
        $usr_id = Auth::getUserID();
        $full_name = User::getFullName($usr_id);
        $htt_id = History::getTypeID('duplicate_update');
        foreach ($ids as $iss_id) {
            History::add($iss_id, $usr_id, $htt_id, 'The details for issue #{issue_id} were updated by {user} and the changes propagated to the duplicated issues', [
                'issue_id' => $issue_id,
                'user' => $full_name,
            ]);
        }

        return 1;
    }

    /**
     * Method used to get a list of the duplicate issues for a given
     * issue ID.
     *
     * @param   int $issue_id The issue ID
     * @return  array The list of duplicates
     */
    public static function getDuplicateList($issue_id)
    {
        $res = self::getDuplicateDetailsList($issue_id);
        if (count($res) == 0) {
            return '';
        }

        $list = [];
        foreach ($res as $row) {
            $list[$row['issue_id']] = $row['title'];
        }

        return $list;
    }

    /**
     * Method used to get a list of the duplicate issues (and their details)
     * for a given issue ID.
     *
     * @param   int $issue_id The issue ID
     * @return  array The list of duplicates
     */
    public static function getDuplicateDetailsList($issue_id)
    {
        static $returns;

        if (!empty($returns[$issue_id])) {
            return $returns[$issue_id];
        }

        $stmt = 'SELECT
                    iss_id issue_id,
                    iss_summary title,
                    sta_title current_status,
                    sta_is_closed is_closed
                 FROM
                    {{%issue}},
                    {{%status}}
                 WHERE
                    iss_sta_id=sta_id AND
                    iss_duplicated_iss_id=?';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, [$issue_id]);
        } catch (DatabaseException $e) {
            return [];
        }

        $returns[$issue_id] = $res;

        return $res;
    }

    /**
     * Method used to clear the duplicate status of an issue.
     *
     * @param   int $issue_id The issue ID
     * @return  int 1 if the update worked, -1 otherwise
     */
    public static function clearDuplicateStatus($issue_id)
    {
        $stmt = "UPDATE
                    {{%issue}}
                 SET
                    iss_updated_date=?,
                    iss_last_internal_action_date=?,
                    iss_last_internal_action_type='updated',
                    iss_duplicated_iss_id=NULL
                 WHERE
                    iss_id=?";
        $params = [Date_Helper::getCurrentDateGMT(), Date_Helper::getCurrentDateGMT(), $issue_id];
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return -1;
        }

        // record the change
        $usr_id = Auth::getUserID();
        History::add($issue_id, $usr_id, 'duplicate_removed', 'Duplicate flag was reset by {user}', [
            'user' => User::getFullName($usr_id),
        ]);

        return 1;
    }

    /**
     * Method used to mark an issue as a duplicate of an existing one.
     *
     * @param int $issue_id The issue ID
     * @param int $dup_iss_id
     * @return int 1 if the update worked, -1 otherwise
     */
    public static function markAsDuplicate($issue_id, $dup_iss_id)
    {
        if (!self::exists($issue_id) || !self::exists($dup_iss_id)) {
            return -1;
        }

        $stmt = "UPDATE
                    {{%issue}}
                 SET
                    iss_updated_date=?,
                    iss_last_internal_action_date=?,
                    iss_last_internal_action_type='updated',
                    iss_duplicated_iss_id=?
                 WHERE
                    iss_id=?";
        $params = [Date_Helper::getCurrentDateGMT(), Date_Helper::getCurrentDateGMT(), $dup_iss_id, $issue_id];
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return -1;
        }

        $usr_id = Auth::getUserID();

        if (!empty($_POST['comments'])) {
            // add note with the comments of marking an issue as a duplicate of another one
            $_POST['title'] = 'Issue duplication comments';
            $_POST['note'] = $_POST['comments'];
            Note::insertFromPost($usr_id, $issue_id);
        }
        // record the change
        History::add($issue_id, $usr_id, 'duplicate_added', 'Issue marked as a duplicate of issue #{issue_id} by {user}', [
            'issue_id' => $dup_iss_id,
            'user' => User::getFullName($usr_id),
        ]);

        return 1;
    }

    public static function isDuplicate($issue_id)
    {
        $sql = 'SELECT
                    count(iss_id)
                FROM
                    {{%issue}}
                WHERE
                    iss_id = ? AND
                    iss_duplicated_iss_id IS NULL';
        try {
            $res = DB_Helper::getInstance()->getOne($sql, [$issue_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        return !($res > 0);
    }

    /**
     * Method used to get an associative array of user ID => user
     * status associated with a given issue ID.
     *
     * @param   int $issue_id The issue ID
     * @return  array The list of users
     */
    public static function getAssignedUsersStatus($issue_id)
    {
        $stmt = 'SELECT
                    usr_id,
                    usr_status
                 FROM
                    {{%issue_user}},
                    {{%user}}
                 WHERE
                    isu_iss_id=? AND
                    isu_usr_id=usr_id';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, [$issue_id]);
        } catch (DatabaseException $e) {
            return [];
        }

        return $res;
    }

    /**
     * Method used to get the summary associated with a given issue ID.
     *
     * @param   int $issue_id The issue ID
     * @return  string The issue summary
     */
    public static function getTitle($issue_id)
    {
        $stmt = 'SELECT
                    iss_summary
                 FROM
                    {{%issue}}
                 WHERE
                    iss_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$issue_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the issue ID associated with a specific summary.
     *
     * @param   string $summary The summary to look for
     * @return  int The issue ID
     * @deprecated method not used
     */
    public static function getIssueID($summary)
    {
        $stmt = 'SELECT
                    iss_id
                 FROM
                    {{%issue}}
                 WHERE
                    iss_summary=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$summary]);
        } catch (DatabaseException $e) {
            return 0;
        }

        return !empty($res) ? $res : 0;
    }

    /**
     * Method used to add a new anonymous based issue in the system.
     *
     * @return  int The new issue ID
     */
    public static function addAnonymousReport()
    {
        $prj_id = (int) $_POST['project'];
        $options = Project::getAnonymousPostOptions($prj_id);
        $initial_status = Project::getInitialStatus($prj_id);
        $usr_id = (int) $options['reporter'];

        $params = [
            'iss_prj_id' => $prj_id,
            'iss_prc_id' => $options['category'],
            'iss_pre_id' => 0,
            'iss_pri_id' => $options['priority'],
            'iss_usr_id' => $usr_id,
            'iss_created_date' => Date_Helper::getCurrentDateGMT(),
            'iss_last_public_action_date' => Date_Helper::getCurrentDateGMT(),
            'iss_last_public_action_type' => 'created',
            'iss_summary' => $_POST['summary'],
            'iss_description' => $_POST['description'],
            'iss_root_message_id' => Mail_Helper::generateMessageID(),
        ];

        if ($initial_status) {
            $params['iss_sta_id'] = $initial_status;
        }

        $stmt = 'INSERT INTO {{%issue}} SET ' . DB_Helper::buildSet($params);

        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return -1;
        }

        $issue_id = DB_Helper::get_last_insert_id();
        // log the creation of the issue
        History::add($issue_id, APP_SYSTEM_USER_ID, 'issue_opened_anon', 'Issue opened anonymously');

        // process any files being uploaded
        // TODO: handle ajax uploads
        if (isset($_FILES['file'])) {
            $iaf_ids = Attachment::addFiles($_FILES['file']);

            if ($iaf_ids) {
                Attachment::attachFiles($issue_id, $usr_id, $iaf_ids, false, 'Files uploaded anonymously');
            }
        }

        // need to process any custom fields ?
        if (@count($_POST['custom_fields']) > 0) {
            foreach ($_POST['custom_fields'] as $fld_id => $value) {
                Custom_Field::associateIssue($issue_id, $fld_id, $value);
            }
        }

        // now add the user/issue association
        $assign = [];
        $users = @$options['users'];
        $actions = Notification::getDefaultActions($issue_id, false, 'anon_issue');
        foreach ($users as $user) {
            Notification::subscribeUser(APP_SYSTEM_USER_ID, $issue_id, $user, $actions);
            self::addUserAssociation(APP_SYSTEM_USER_ID, $issue_id, $user);
            $assign[] = $user;
        }

        Workflow::handleNewIssue($prj_id, $issue_id, false, false);

        // also notify any users that want to receive emails anytime a new issue is created
        Notification::notifyNewIssue($prj_id, $issue_id);

        return $issue_id;
    }

    /**
     * Method used to close off an issue.
     *
     * @param   int $usr_id The user ID
     * @param   int $issue_id The issue ID
     * @param   bool $send_notification Whether to send a notification about this action or not
     * @param   int $resolution_id The resolution ID
     * @param   int $status_id The status ID
     * @param   string $reason The reason for closing this issue
     * @param   string $send_notification_to Who this notification should be sent too
     * @return  int 1 if the update worked, -1 otherwise
     */
    public static function close($usr_id, $issue_id, $send_notification, $resolution_id, $status_id, $reason,
                                 $send_notification_to = 'internal')
    {
        $usr_id = (int) $usr_id;
        $issue_id = (int) $issue_id;
        $resolution_id = (int) $resolution_id;
        $status_id = (int) $status_id;

        $params = [
            'iss_updated_date' => Date_Helper::getCurrentDateGMT(),
            'iss_last_public_action_date' => Date_Helper::getCurrentDateGMT(),
            'iss_last_public_action_type' => 'closed',
            'iss_closed_date' => Date_Helper::getCurrentDateGMT(),
            'iss_sta_id' => $status_id,
        ];

        if (!empty($resolution_id)) {
            $params['iss_res_id'] = $resolution_id;
        }

        $stmt = 'UPDATE {{%issue}} SET ' . DB_Helper::buildSet($params) . ' WHERE iss_id=?';
        $params[] = $issue_id;

        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return -1;
        }

        $prj_id = self::getProjectID($issue_id);

        // record the change
        History::add($issue_id, $usr_id, 'issue_closed', "Issue updated to status '{status}' by {user}", [
            'status' => Status::getStatusTitle($status_id),
            'user' => User::getFullName($usr_id),
        ]);

        if ($send_notification_to == 'all') {
            $from = User::getFromHeader($usr_id);
            $message_id = Mail_Helper::generateMessageID();
            $full_email = Support::buildFullHeaders($issue_id, $message_id, $from,
                '', '', ev_gettext('Issue closed comments'), $reason, '');

            $structure = Mime_Helper::decode($full_email, true, false);

            $email = [
                'ema_id' => Email_Account::getEmailAccount(self::getProjectID($issue_id)),
                'issue_id' => $issue_id,
                'message_id' => $message_id,
                'date' => Date_Helper::getCurrentDateGMT(),
                'subject' => ev_gettext('Issue closed comments'),
                'from' => $from,
                'has_attachment' => 0,
                'body' => $reason,
                'full_email' => $full_email,
                'headers' => $structure->headers,
            ];
            $sup_id = null;
            Support::insertEmail($email, $structure, $sup_id, true);
            $ids = $sup_id;
        } else {
            // add note with the reason to close the issue
            $options = [
                'send_notification' => false,
                'closing' => true,
            ];
            Note::insertNote($usr_id, $issue_id, ev_gettext('Issue closed comments'), $reason, $options);
            $ids = false;
        }

        if ($send_notification) {
            if (CRM::hasCustomerIntegration($prj_id)) {
                $crm = CRM::getInstance($prj_id);
                // send a special confirmation email when customer issues are closed
                $stmt = 'SELECT
                            iss_customer_contact_id
                         FROM
                            {{%issue}}
                         WHERE
                            iss_id=?';
                $customer_contact_id = DB_Helper::getInstance()->getOne($stmt, [$issue_id]);
                if (!empty($customer_contact_id)) {
                    try {
                        $contact = $crm->getContact($customer_contact_id);
                        $contact->notifyIssueClosed($issue_id, $reason);
                    } catch (CRMException $e) {
                    }
                }
            }
            // send notifications for the issue being closed
            Notification::notify($issue_id, 'closed', $ids, ($send_notification_to != 'all'));
        }
        Workflow::handleIssueClosed($prj_id, $issue_id, $send_notification, $resolution_id, $status_id, $reason, $usr_id);

        return 1;
    }

    /**
     * Update the issue associations
     *
     * @param int $usr_id User Id performing the operation
     * @param int $issue_id issue to associate
     * @param array $associated_issues issue_id's to associate with
     * @deprecated use IssueAssociationRepository
     */
    private static function updateAssociatedIssuesRelations($usr_id, $issue_id, $associated_issues)
    {
        $repo = IssueAssociationRepository::create();
        $res = $repo->updateAssociations($usr_id, $issue_id, $associated_issues);

        global $errors;
        foreach ($res as $error) {
            $errors['Associated Issues'][] = $error;
        }
    }

    /**
     * Method to update the details of a specific issue.
     *
     * @param   int $issue_id The issue ID
     * @return  int 1 if the update worked, -1 or -2 otherwise
     */
    public static function update($issue_id)
    {
        $issue_id = (int) $issue_id;

        $usr_id = Auth::getUserID();
        $prj_id = self::getProjectID($issue_id);

        $workflow = Workflow::preIssueUpdated($prj_id, $issue_id, $usr_id, $_POST);
        if ($workflow !== true) {
            return $workflow;
        }

        // get all of the 'current' information of this issue
        $current = self::getDetails($issue_id);

        $associated_issues = isset($_POST['associated_issues']) ? explode(',', $_POST['associated_issues']) : [];
        self::updateAssociatedIssuesRelations($usr_id, $issue_id, $associated_issues);

        $assignments_changed = false;
        if (@$_POST['keep_assignments'] == 'no' && Access::canChangeAssignee($issue_id, $usr_id)) {
            // only change the issue-user associations if there really were any changes
            $old_assignees = array_merge($current['assigned_users'], $current['assigned_inactive_users']);
            if (!empty($_POST['assignments'])) {
                $new_assignees = @$_POST['assignments'];
            } else {
                $new_assignees = [];
            }
            $assignment_notifications = [];

            // remove people from the assignment list, if appropriate
            foreach ($old_assignees as $assignee) {
                if (!in_array($assignee, $new_assignees)) {
                    self::deleteUserAssociation($issue_id, $assignee);
                    $assignments_changed = true;
                }
            }
            // add people to the assignment list, if appropriate
            foreach ($new_assignees as $assignee) {
                if (!in_array($assignee, $old_assignees)) {
                    self::addUserAssociation($usr_id, $issue_id, $assignee);
                    Notification::subscribeUser($usr_id, $issue_id, $assignee, Notification::getDefaultActions($issue_id, User::getEmail($assignee), 'issue_update'), true);
                    $assignment_notifications[] = $assignee;
                    $assignments_changed = true;
                }
            }
            if (count($assignment_notifications) > 0) {
                Notification::notifyNewAssignment($assignment_notifications, $issue_id);
            }
        }

        if (empty($_POST['estimated_dev_time'])) {
            $_POST['estimated_dev_time'] = 0;
        }

        $params = [
            'iss_updated_date' => Date_Helper::getCurrentDateGMT(),
            'iss_last_public_action_date' => Date_Helper::getCurrentDateGMT(),
            'iss_last_public_action_type' => 'updated',
            'iss_sta_id' => $_POST['status'],
            'iss_summary' => $_POST['summary'],
            'iss_description' => $_POST['description'],
        ];

        if (isset($_POST['status']) && $current['iss_sta_id'] != $_POST['status']) {
            $params['iss_status_change_date'] = Date_Helper::getCurrentDateGMT();
        }

        if (isset($_POST['release'])) {
            $params['iss_pre_id'] = $_POST['release'];
        }
        if (isset($_POST['percentage_complete'])) {
            $params['iss_percent_complete'] = $_POST['percentage_complete'];
        }
        if (isset($_POST['group'])) {
            $params['iss_grp_id'] = $_POST['group'];
        }
        if (isset($_POST['estimated_dev_time'])) {
            $params['iss_dev_time'] = $_POST['estimated_dev_time'];
        }
        if (isset($_POST['trigger_reminders'])) {
            $params['iss_trigger_reminders'] = $_POST['trigger_reminders'];
        }
        if (isset($_POST['resolution'])) {
            $params['iss_res_id'] = $_POST['resolution'];
        }

        if (!empty($_POST['category'])) {
            $params['iss_prc_id'] = $_POST['category'];
        }
        if (@$_POST['keep'] == 'no') {
            $params['iss_pre_id'] = $_POST['release'];
        }
        if (!empty($_POST['expected_resolution_date'])) {
            $params['iss_expected_resolution_date'] = $_POST['expected_resolution_date'];
        } else {
            $params['iss_expected_resolution_date'] = null;
        }
        if (isset($_POST['priority'])) {
            $params['iss_pri_id'] = $_POST['priority'];
        }
        if (isset($_POST['severity'])) {
            $params['iss_sev_id'] = $_POST['severity'];
        }
        if (isset($_POST['scheduled_release'])) {
            $params['iss_pre_id'] = $_POST['scheduled_release'];
        }

        $stmt = 'UPDATE {{%issue}} SET ' . DB_Helper::buildSet($params) . ' WHERE iss_id=?';
        $params[] = $issue_id;

        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return -1;
        }

        // change product
        if (isset($_POST['product'])) {
            $product_changes = Product::updateProductsByIssue($issue_id, $_POST['product'], $_POST['product_version']);
        }

        // add change to the history (only for changes on specific fields?)
        $updated_fields = [];
        if ($current['iss_expected_resolution_date'] != $_POST['expected_resolution_date']) {
            $updated_fields['Expected Resolution Date'] = History::formatChanges($current['iss_expected_resolution_date'], $_POST['expected_resolution_date']);
        }
        if (isset($_POST['category']) && $current['iss_prc_id'] != $_POST['category']) {
            $updated_fields['Category'] = History::formatChanges(Category::getTitle($current['iss_prc_id']), Category::getTitle($_POST['category']));
        }
        if (isset($_POST['release']) && $current['iss_pre_id'] != $_POST['release']) {
            $updated_fields['Release'] = History::formatChanges(Release::getTitle($current['iss_pre_id']), Release::getTitle($_POST['release']));
        }
        if (isset($_POST['priority']) && $current['iss_pri_id'] != $_POST['priority']) {
            $updated_fields['Priority'] = History::formatChanges(Priority::getTitle($current['iss_pri_id']), Priority::getTitle($_POST['priority']));
            Workflow::handlePriorityChange($prj_id, $issue_id, $usr_id, $current, $_POST);
        }
        if (isset($_POST['severity']) && $current['iss_sev_id'] != $_POST['severity']) {
            $updated_fields['Severity'] = History::formatChanges(Severity::getTitle($current['iss_sev_id']), Severity::getTitle($_POST['severity']));
            Workflow::handleSeverityChange($prj_id, $issue_id, $usr_id, $current, $_POST);
        }
        if (isset($_POST['scheduled_release']) && $current['iss_pre_id'] != $_POST['scheduled_release']) {
            $updated_fields['Scheduled Release'] = History::formatChanges(Release::getTitle($current['iss_pre_id']), Release::getTitle($_POST['scheduled_release']));
        }
        if (isset($_POST['status']) && $current['iss_sta_id'] != $_POST['status']) {
            // clear out the last-triggered-reminder flag when changing the status of an issue
            Reminder_Action::clearLastTriggered($issue_id);

            // if old status was closed and new status is not, clear closed data from issue.
            $old_status_details = Status::getDetails($current['iss_sta_id']);
            if ($old_status_details['sta_is_closed'] == 1) {
                $new_status_details = Status::getDetails($_POST['status']);
                if ($new_status_details['sta_is_closed'] != 1) {
                    self::clearClosed($issue_id);
                }
            }
            $updated_fields['Status'] = History::formatChanges(Status::getStatusTitle($current['iss_sta_id']), Status::getStatusTitle($_POST['status']));
        }
        if (isset($_POST['resolution']) && $current['iss_res_id'] != $_POST['resolution']) {
            $updated_fields['Resolution'] = History::formatChanges(Resolution::getTitle($current['iss_res_id']), Resolution::getTitle($_POST['resolution']));
        }
        if (isset($_POST['estimated_dev_time']) && $current['iss_dev_time'] != $_POST['estimated_dev_time']) {
            $updated_fields['Estimated Dev. Time'] = History::formatChanges(Misc::getFormattedTime(($current['iss_dev_time'] * 60)), Misc::getFormattedTime(($_POST['estimated_dev_time'] * 60)));
        }
        if ($current['iss_summary'] != $_POST['summary']) {
            $updated_fields['Summary'] = '';
        }

        if (isset($_POST['percentage_complete']) && $current['iss_original_percent_complete'] != $_POST['percentage_complete']) {
            $updated_fields['Percentage complete'] = History::formatChanges($current['iss_original_percent_complete'], $_POST['percentage_complete']);
        }

        if ($current['iss_original_description'] != $_POST['description']) {
            $updated_fields['Description'] = '';
        }

        if (isset($_POST['product']) && count($product_changes) > 0) {
            $updated_fields['Product'] = implode('; ', $product_changes);
        }

        if (isset($_POST['custom_fields']) && count($_POST['custom_fields']) > 0) {
            $updated_custom_fields = Custom_Field::updateValues($issue_id, $_POST['custom_fields']);
        } else {
            $updated_custom_fields = [];
        }

        if (count($updated_fields) > 0) {
            // log the changes
            $changes = '';
            $i = 0;
            foreach ($updated_fields as $key => $value) {
                if ($i > 0) {
                    $changes .= '; ';
                }
                if (($key != 'Summary') && ($key != 'Description')) {
                    $changes .= "$key: $value";
                } else {
                    $changes .= "$key";
                }
                $i++;
            }
            History::add($issue_id, $usr_id, 'issue_updated', 'Issue updated ({changes}) by {user}', [
                'changes' => $changes,
                'user' => User::getFullName($usr_id),
            ]);
        }

        if (count($updated_fields) > 0 || count($updated_custom_fields) > 0) {
            // send notifications for the issue being updated
            Notification::notifyIssueUpdated($issue_id, $current, $_POST, $updated_custom_fields);
        }

        // record group change as a separate change
        if (isset($_POST['group']) && $current['iss_grp_id'] != (int) $_POST['group']) {
            History::add($issue_id, $usr_id, 'group_changed', 'Group changed ({changes}) by {user}', [
                'changes' => History::formatChanges(Group::getName($current['iss_grp_id']), Group::getName($_POST['group'])),
                'user' => User::getFullName($usr_id),
            ]);
        }

        // now update any duplicates, if any
        $update_dupe = [
            'Category',
            'Release',
            'Priority',
            'Release',
            'Resolution',
        ];
        $intersect = array_intersect($update_dupe, array_keys($updated_fields));
        if (($current['duplicates'] != '') && (count($intersect) > 0)) {
            self::updateDuplicates($issue_id);
        }

        // if there is customer integration, mark last customer action
        if ((CRM::hasCustomerIntegration($prj_id)) && (User::getRoleByUser($usr_id, $prj_id) == User::ROLE_CUSTOMER)) {
            self::recordLastCustomerAction($issue_id);
        }

        if ($assignments_changed) {
            Workflow::handleAssignmentChange(self::getProjectID($issue_id), $issue_id, $usr_id, self::getDetails($issue_id), @$_POST['assignments'], false);
        }

        Workflow::handleIssueUpdated($prj_id, $issue_id, $usr_id, $current, $_POST);
        // Move issue to another project
        if (isset($_POST['move_issue']) and (User::getRoleByUser($usr_id, $prj_id) >= User::ROLE_DEVELOPER)) {
            $new_prj_id = (int) @$_POST['new_prj'];
            if (($prj_id != $new_prj_id) && (array_key_exists($new_prj_id, Project::getAssocList($usr_id)))) {
                if (User::getRoleByUser($usr_id, $new_prj_id) >= User::ROLE_REPORTER) {
                    $res = self::moveIssue($issue_id, $new_prj_id);
                    if ($res == -1) {
                        return $res;
                    }
                } else {
                    return -1;
                }
            }
        }

        return 1;
    }

    /**
     * Move the issue to a new project
     *
     * @param int $issue_id
     * @param int $new_prj_id
     * @return int 1 on success, -1 otherwise
     */
    public static function moveIssue($issue_id, $new_prj_id)
    {
        $current_prj_id = self::getProjectID($issue_id);
        $mapping = self::getMovedIssueMapping($issue_id, $new_prj_id);

        $values = [$new_prj_id];
        $stmt = 'UPDATE
              {{%issue}}
          SET
              iss_prj_id = ?';
        foreach ($mapping as $fld_name => $fld_value) {
            $stmt .= ",\n$fld_name = ?";
            $values[] = $fld_value;
        }
        $stmt .= '
          WHERE
              iss_id = ?';
        $values[] = $issue_id;
        try {
            DB_Helper::getInstance()->query($stmt, $values);
        } catch (DatabaseException $e) {
            return -1;
        }

        // clear project cache
        self::getProjectID($issue_id, true);

        History::add($issue_id, Auth::getUserID(), 'issue_moved', 'Issue moved from {old_project} to {new_project} by {user}', [
                'old_project' => Project::getName($current_prj_id),
                'new_project' => Project::getName($new_prj_id),
                'user' => User::getFullName(Auth::getUserID()),
        ]);

        Workflow::handleIssueMovedFromProject($current_prj_id, $issue_id, $new_prj_id);
        Workflow::handleIssueMovedToProject($new_prj_id, $issue_id, $current_prj_id);

        Notification::notifyNewIssue($new_prj_id, $issue_id);

        return 1;
    }

    /**
     * @param int $issue_id
     * @param int $new_prj_id
     */
    private static function getMovedIssueMapping($issue_id, $new_prj_id)
    {
        $mapping = [];
        $current_details = self::getDetails($issue_id);

        // set new category
        $new_iss_prc_list = Category::getAssocList($new_prj_id);
        $iss_prc_title = Category::getTitle($current_details['iss_prc_id']);
        $new_prc_id = array_search($iss_prc_title, $new_iss_prc_list);
        if ($new_prc_id === false) {
            // use the first category listed in the new project
            $mapping['iss_prc_id'] = key($new_iss_prc_list);
        }

        // set new priority
        $new_iss_pri_list = Priority::getAssocList($new_prj_id);
        $iss_pri_title = Priority::getTitle($current_details['iss_pri_id']);
        $new_pri_id = array_search($iss_pri_title, $new_iss_pri_list);
        if ($new_pri_id === false) {
            // use the first category listed in the new project
            $mapping['iss_pri_id'] = key($new_iss_pri_list);
        }

        return Workflow::getMovedIssueMapping($new_prj_id, $issue_id, $mapping, $current_details['iss_prj_id']);
    }

    /**
     * Method used to assign an issue with an user.
     *
     * @param   int $usr_id The user ID of the person performing this change
     * @param   int $issue_id The issue ID
     * @param   int $assignee_usr_id The user ID of the assignee
     * @param   bool $add_history Whether to add a history entry about this or not
     * @return  int 1 if the update worked, -1 otherwise
     */
    public static function addUserAssociation($usr_id, $issue_id, $assignee_usr_id, $add_history = true)
    {
        $stmt = 'INSERT INTO
                    {{%issue_user}}
                 (
                    isu_iss_id,
                    isu_usr_id,
                    isu_assigned_date
                 ) VALUES (
                    ?, ?, ?
                 )';
        $params = [$issue_id, $assignee_usr_id, Date_Helper::getCurrentDateGMT()];
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return -1;
        }

        if ($add_history) {
            History::add($issue_id, $usr_id, 'user_associated', 'Issue assigned to {assignee} by {user}', [
                'assignee' => User::getFullName($assignee_usr_id),
                'user' => User::getFullName($usr_id),
            ]);
        }

        return 1;
    }

    /**
     * Method used to delete all user assignments for a specific issue.
     *
     * @param   int|int[] $issue_id The issue ID(s)
     * @param   int $usr_id The user ID of the person performing the change
     * @return int
     */
    public static function deleteUserAssociations($issue_id, $usr_id = null)
    {
        $issues = (array) $issue_id;
        $list = DB_Helper::buildList($issues);

        $stmt = "DELETE FROM
                    {{%issue_user}}
                 WHERE
                    isu_iss_id IN ($list)";
        try {
            DB_Helper::getInstance()->query($stmt, $issues);
        } catch (DatabaseException $e) {
            return -1;
        }

        if ($usr_id) {
            History::add($issue_id, $usr_id, 'user_all_unassociated', 'Issue assignments removed by {user}', [
                'user' => User::getFullName($usr_id),
            ]);
        }

        return 1;
    }

    /**
     * Method used to delete a single user assignments for a specific issue.
     *
     * @param   int $issue_id The issue ID
     * @param   int $usr_id the user to remove
     * @param   bool $add_history Whether to add a history entry about this or not
     * @return int
     */
    public static function deleteUserAssociation($issue_id, $usr_id, $add_history = true)
    {
        $stmt = 'DELETE FROM
                    {{%issue_user}}
                 WHERE
                    isu_iss_id = ? AND
                    isu_usr_id = ?';
        try {
            DB_Helper::getInstance()->query($stmt, [$issue_id, $usr_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        if ($add_history) {
            $current_usr_id = Auth::getUserID();
            History::add($issue_id, $current_usr_id, 'user_unassociated', '{other_user} removed from issue by {user}', [
                'other_user' => User::getFullName($usr_id),
                'user' => User::getFullName($current_usr_id),
            ]);
        }

        return 1;
    }

    /**
     * Creates an issue with the given email information.
     *
     * @param   int $prj_id The project ID
     * @param   int $usr_id The user responsible for this action
     * @param   string $sender The original sender of this email
     * @param   string $summary The issue summary
     * @param   string $description The issue description
     * @param   int $category The category ID
     * @param   int $priority The priority ID
     * @param   array $assignment The list of users to assign this issue to
     * @param   string $date the date the email was originally sent
     * @param   string $msg_id the message ID of the email we are creating this issue from
     * @param   int $severity
     * @param   string $customer_id
     * @param   string $contact_id
     * @param   string $contract_id
     * @return int
     */
    public static function createFromEmail($prj_id, $usr_id, $sender, $summary, $description, $category, $priority, $assignment,
                             $date, $msg_id, $severity, $customer_id, $contact_id, $contract_id)
    {
        $exclude_list = [];
        $managers = [];

        $sender_email = Mail_Helper::getEmailAddress($sender);
        $sender_usr_id = User::getUserIDByEmail($sender_email, true);
        if (!empty($sender_usr_id)) {
            $reporter = $sender_usr_id;
            $exclude_list[] = $sender_usr_id;
        } else {
            $exclude_list[] = $sender_email;
        }

        $data = [
            'category' => $category,
            'priority' => $priority,
            'severity' => $severity,
            'description' => $description,
            'summary' => $summary,
            'msg_id' => $msg_id,
            'customer' => false,
            'contact' => false,
            'contract' => false,
            'contact_person_lname' => '',
            'contact_person_fname' => '',
            'contact_email' => '',
            'contact_phone' => '',
            'contact_timezone' => '',
        ];

        if (CRM::hasCustomerIntegration($prj_id)) {
            $crm = CRM::getInstance($prj_id);
            try {
                if ($contact_id != false) {
                    $contact = $crm->getContact($contact_id);
                } else {
                    $contact = $crm->getContactByEmail($sender_email);
                }
                // overwrite the reporter with the customer contact
                $reporter = User::getUserIDByContactID($contact->getContactID());
                $data['contact'] = $contact->getContactID();
                $data['contact_person_lname'] = $contact['last_name'];
                $data['contact_person_fname'] = $contact['first_name'];
                $data['contact_email'] = $sender_email;
                $data['contact_phone'] = $contact['phone'];
                $data['contact_timezone'] = Date_Helper::getPreferredTimezone($reporter);
            } catch (ContactNotFoundException $e) {
            }

            try {
                if ($contract_id != false) {
                    $contract = $crm->getContract($contract_id);
                    $data['contract'] = $contract->getContractID();
                } elseif (isset($contact)) {
                    // Just use first contract / customer for now.
                    $contracts = $contact->getContracts(['active' => true]);
                    $contract = $contracts[0];
                    $data['contract'] = $contract->getContractID();
                }
            } catch (ContractNotFoundException $e) {
            }

            try {
                if ($customer_id != false) {
                    $customer = $crm->getCustomer($customer_id);
                    $data['customer'] = $customer->getCustomerID();
                } elseif (isset($contract)) {
                    $customer = $contract->getCustomer();
                    $data['customer'] = $customer->getCustomerID();
                }
            } catch (CustomerNotFoundException $e) {
            }
        }

        if (empty($reporter)) {
            $reporter = APP_SYSTEM_USER_ID;
        }

        $data['reporter'] = $reporter;

        $issue_id = self::insertIssue($prj_id, $data);
        if ($issue_id == -1) {
            return -1;
        }

        $has_RR = false;
        // log the creation of the issue
        History::add($issue_id, $usr_id, 'issue_opened', 'Issue opened by {sender}', [
            'sender' => $sender,
        ]);

        $emails = [];
        // if there are any technical account managers associated with this customer, add these users to the notification list
        if ($data['customer']) {
            $managers = CRM::getAccountManagers($prj_id, $data['customer']);
            foreach ($managers as $manager) {
                $emails[] = $manager['usr_email'];
            }
        }
        // add the reporter to the notification list
        $emails[] = $sender;
        $emails = array_unique($emails);
        $actions = Notification::getDefaultActions($issue_id, false, 'issue_from_email');
        foreach ($emails as $address) {
            Notification::subscribeEmail($reporter, $issue_id, $address, $actions);
        }

        // only assign the issue to an user if the associated customer has any technical account managers
        $users = [];
        $has_TAM = false;
        if ((CRM::hasCustomerIntegration($prj_id)) && (count($managers) > 0)) {
            foreach ($managers as $manager) {
                if ($manager['cam_type'] == 'intpart') {
                    continue;
                }
                $users[] = $manager['cam_usr_id'];
                self::addUserAssociation($usr_id, $issue_id, $manager['cam_usr_id'], false);
                History::add($issue_id, $usr_id, 'issue_auto_assigned', 'Issue auto-assigned to {assignee} (TAM)', [
                    'assignee' => User::getFullName($manager['cam_usr_id']),
                ]);
            }
            $has_TAM = true;
        }
        // now add the user/issue association
        if (@count($assignment) > 0) {
            foreach ($assignment as $ass_usr_id) {
                Notification::subscribeUser($reporter, $issue_id, $ass_usr_id, $actions);
                self::addUserAssociation(APP_SYSTEM_USER_ID, $issue_id, $ass_usr_id);
                if ($ass_usr_id != $usr_id) {
                    $users[] = $ass_usr_id;
                }
            }
        } else {
            // only use the round-robin feature if this new issue was not
            // already assigned to a customer account manager
            if (count($managers) < 1) {
                $assignee = Round_Robin::getNextAssignee($prj_id);
                // assign the issue to the round robin person
                if (!empty($assignee)) {
                    self::addUserAssociation(APP_SYSTEM_USER_ID, $issue_id, $assignee, false);
                    History::add($issue_id, APP_SYSTEM_USER_ID, 'rr_issue_assigned', 'Issue auto-assigned to {assignee} (RR)', [
                        'assignee' => User::getFullName($assignee),
                    ]);
                    $users[] = $assignee;
                    $has_RR = true;
                }
            }
        }

        Workflow::handleNewIssue($prj_id, $issue_id, $has_TAM, $has_RR);

        // send special 'an issue was auto-created for you' notification back to the sender
        Notification::notifyAutoCreatedIssue($prj_id, $issue_id, $sender, $date, $summary);

        // also notify any users that want to receive emails anytime a new issue is created
        Notification::notifyNewIssue($prj_id, $issue_id, $exclude_list);

        return $issue_id;
    }

    /**
     * Return errors that happened when creating new issue from POST method.
     *
     * @return  array
     */
    private static $insert_errors = [];

    public static function getInsertErrors()
    {
        return self::$insert_errors;
    }

    /**
     * Method used to add a new issue using the normal report form.
     *
     * @return  int The new issue ID
     */
    public static function createFromPost()
    {
        $keys = [
            'add_primary_contact', 'attached_emails', 'category', 'contact', 'contact_email', 'contact_extra_emails', 'contact_person_fname',
            'contact_person_lname', 'contact_phone', 'contact_timezone', 'contract', 'customer', 'custom_fields', 'description',
            'estimated_dev_time', 'group', 'notify_customer', 'notify_senders', 'priority', 'private', 'release', 'severity', 'summary', 'users',
            'product', 'product_version', 'expected_resolution_date', 'associated_issues', 'access_level',
        ];
        $data = [];
        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                $data[$key] = $_POST[$key];
            }
        }

        $prj_id = Auth::getCurrentProject();
        $usr_id = Auth::getUserID();

        // if we are creating an issue for a customer, put the
        // main customer contact as the reporter for it
        if (CRM::hasCustomerIntegration($prj_id)) {
            $crm = CRM::getInstance($prj_id);
            $contact_usr_id = User::getUserIDByContactID($data['contact']);
            if (empty($contact_usr_id)) {
                $contact_usr_id = $usr_id;
            }
            $data['reporter'] = $contact_usr_id;
        } else {
            $data['reporter'] = $usr_id;
        }

        $data['msg_id'] = Mail_Helper::generateMessageID();

        $issue_id = self::insertIssue($prj_id, $data);
        if ($issue_id == -1) {
            return -1;
        }

        $has_RR = false;
        $info = User::getNameEmail($usr_id);
        // log the creation of the issue
        History::add($issue_id, $usr_id, 'issue_opened', 'Issue opened by {user}', [
            'user' => User::getFullName($usr_id),
        ]);

        $clone_iss_id = isset($_POST['clone_iss_id']) ? (int) $_POST['clone_iss_id'] : null;
        if ($clone_iss_id && Access::canCloneIssue($clone_iss_id, $usr_id)) {
            History::add($issue_id, $usr_id, 'issue_cloned_from', 'Issue cloned from issue #{issue_id}', [
                'issue_id' => $clone_iss_id,
            ]);
            History::add($clone_iss_id, $usr_id, 'issue_cloned_to', 'Issue cloned to issue #{issue_id}', [
                'issue_id' => $issue_id,
            ]);

            IssueAssociationRepository::create()
                ->addIssueAssociation($usr_id, $issue_id, $clone_iss_id);
        }

        $emails = [];
        if (CRM::hasCustomerIntegration($prj_id)) {
            $customer = $crm->getCustomer($data['customer']);
            $contract = $crm->getContract($data['contract']);
            if (!empty($data['contact_extra_emails']) && count($data['contact_extra_emails']) > 0) {
                $emails = $data['contact_extra_emails'];
            }
            // add the primary contact to the notification list
            if (isset($data['add_primary_contact']) && ($data['add_primary_contact'] == 'yes')) {
                $contact_email = User::getEmailByContactID($data['contact']);
                if (!empty($contact_email)) {
                    $emails[] = $contact_email;
                }
            }
            // if there are any technical account managers associated with this customer, add these users to the notification list
            $managers = $customer->getEventumAccountManagers();
            foreach ($managers as $manager) {
                $emails[] = $manager['usr_email'];
            }
        }
        // add the reporter to the notification list
        $emails[] = $info['usr_email'];
        $emails = array_unique($emails);
        foreach ($emails as $address) {
            Notification::subscribeEmail($usr_id, $issue_id, $address, Notification::getDefaultActions($issue_id, $address, 'new_issue'));
        }

        // only assign the issue to an user if the associated customer has any technical account managers
        $users = [];
        $has_TAM = false;
        if ((CRM::hasCustomerIntegration($prj_id)) && (count($managers) > 0)) {
            foreach ($managers as $manager) {
                if ($manager['cam_type'] == 'intpart') {
                    continue;
                }
                $users[] = $manager['cam_usr_id'];
                self::addUserAssociation($usr_id, $issue_id, $manager['cam_usr_id'], false);
                History::add($issue_id, $usr_id, 'issue_auto_assigned', 'Issue auto-assigned to {assignee} (TAM)', [
                    'assignee' => User::getFullName($manager['cam_usr_id']),
                ]);
            }
            $has_TAM = true;
        }
        // now add the user/issue association (aka assignments)
        if (!empty($data['users']) && count($data['users']) > 0) {
            foreach ($data['users'] as $user) {
                $actions = Notification::getDefaultActions($issue_id, User::getEmail($user), 'new_issue');
                Notification::subscribeUser($usr_id, $issue_id, $user, $actions);
                self::addUserAssociation($usr_id, $issue_id, $user);
                if ($user != $usr_id) {
                    $users[] = $user;
                }
            }
        } else {
            // only use the round-robin feature if this new issue was not
            // already assigned to a customer account manager
            if (@count($managers) < 1) {
                $assignee = Round_Robin::getNextAssignee($prj_id);
                // assign the issue to the round robin person
                if (!empty($assignee)) {
                    $users[] = $assignee;
                    self::addUserAssociation($usr_id, $issue_id, $assignee, false);
                    History::add($issue_id, APP_SYSTEM_USER_ID, 'rr_issue_assigned', 'Issue auto-assigned to {assignee} (RR)', [
                         'assignee' => User::getFullName($assignee),
                    ]);
                    $has_RR = true;
                }
            }
        }

        // set product and version
        if (isset($data['product']) && $data['product'] != '-1') {
            Product::addIssueProductVersion($issue_id, $data['product'], $data['product_version']);
        }

        // process any files being uploaded
        // from ajax upload, attachment file ids
        $iaf_ids = !empty($_POST['iaf_ids']) ? explode(',', $_POST['iaf_ids']) : null;
        // if no iaf_ids passed, perhaps it's old style upload
        // TODO: verify that the uploaded file(s) owner is same as attachment owner.
        if (!$iaf_ids && isset($_FILES['file'])) {
            $iaf_ids = Attachment::addFiles($_FILES['file']);
        }
        if ($iaf_ids) {
            Attachment::attachFiles($issue_id, $usr_id, $iaf_ids, false, 'Files uploaded at issue creation time');
        }

        // need to associate any emails ?
        if (!empty($data['attached_emails'])) {
            $items = explode(',', $data['attached_emails']);
            Support::associate($usr_id, $issue_id, $items);
        }
        // need to notify any emails being converted into issues ?
        if (@count($data['notify_senders']) > 0) {
            $recipients = Notification::notifyEmailConvertedIntoIssue($prj_id, $issue_id, $data['notify_senders'], @$data['customer']);
        } else {
            $recipients = [];
        }
        // need to process any custom fields ?
        if (@count($data['custom_fields']) > 0) {
            foreach ($data['custom_fields'] as $fld_id => $value) {
                Custom_Field::associateIssue($issue_id, $fld_id, $value);
            }
        }
        // also send a special confirmation email to the customer contact
        if ((@$data['notify_customer'] == 'yes') && (!empty($data['contact']))) {
            $contact = $contract->getContact($data['contact']);
            // also need to pass the list of sender emails already notified,
            // so we can avoid notifying the same person again
            $contact_email = User::getEmailByContactID($data['contact']);
            if (@!in_array($contact_email, $recipients)) {
                $contact->notifyNewIssue($issue_id);
            }
            // now check for additional emails in contact_extra_emails
            if (@count($data['contact_extra_emails']) > 0) {
                $notification_emails = $data['contact_extra_emails'];
                foreach ($notification_emails as $notification_email) {
                    if (@!in_array($notification_email, $recipients)) {
                        try {
                            $notification_contact = $crm->getContactByEmail($notification_email);
                            $notification_contact->notifyNewIssue($issue_id);
                        } catch (ContactNotFoundException $e) {
                        }
                    }
                }
            }
        }

        // handle associated issues
        if (isset($data['associated_issues'])) {
            $associated_issues = explode(',', $data['associated_issues']);
            if ($clone_iss_id) {
                $associated_issues[] = $clone_iss_id;
            }
            self::updateAssociatedIssuesRelations($usr_id, $issue_id, $associated_issues);
        }

        Workflow::handleNewIssue($prj_id, $issue_id, $has_TAM, $has_RR);

        // also notify any users that want to receive emails anytime a new issue is created
        Notification::notifyNewIssue($prj_id, $issue_id);

        return $issue_id;
    }

    /**
     * Insert issue to database.
     *
     * @param   int $prj_id The project ID
     * @param   array $data of issue to be inserted
     * @return  int The new issue ID
     */
    private static function insertIssue($prj_id, $data)
    {
        // if there is no reporter set, use the system user
        if (empty($data['reporter'])) {
            $data['reporter'] = APP_SYSTEM_USER_ID;
        }

        if ((!isset($data['estimated_dev_time'])) || ($data['estimated_dev_time'] == '')) {
            $data['estimated_dev_time'] = 0;
        }

        if (!isset($data['private'])) {
            $data['private'] = 0;
        }

        // add new issue
        $params = [
            'iss_usr_id' => $data['reporter'],
            'iss_created_date' => Date_Helper::getCurrentDateGMT(),
            'iss_last_public_action_date' => Date_Helper::getCurrentDateGMT(),
            'iss_last_public_action_type' => 'created',
            'iss_summary' => $data['summary'],
            'iss_description' => $data['description'],
            'iss_dev_time' => $data['estimated_dev_time'],
            'iss_root_message_id' => $data['msg_id'],
            'iss_prj_id' => $prj_id,
        ];

        if (!empty($data['group'])) {
            $params['iss_grp_id'] = $data['group'];
        }
        if (!empty($data['category'])) {
            $params['iss_prc_id'] = $data['category'];
        }
        if (!empty($data['release'])) {
            $params['iss_pre_id'] = $data['release'];
        }
        if (!empty($data['priority'])) {
            $params['iss_pri_id'] = $data['priority'];
        }
        if (!empty($data['severity'])) {
            $params['iss_sev_id'] = $data['severity'];
        }
        if (!empty($data['expected_resolution_date'])) {
            $params['iss_expected_resolution_date'] = $data['expected_resolution_date'];
        }
        if (!empty($data['access_level'])) {
            $params['iss_access_level'] = $data['access_level'];
        }

        $initial_status = Project::getInitialStatus($prj_id);
        if (!empty($initial_status)) {
            $params['iss_sta_id'] = $initial_status;
        }

        if (CRM::hasCustomerIntegration($prj_id)) {
            $params['iss_customer_id'] = $data['customer'];
            if (!empty($data['contract'])) {
                $params['iss_customer_contract_id'] = $data['contract'];
            }
            $params['iss_customer_contact_id'] = $data['contact'];
            $params['iss_contact_person_lname'] = $data['contact_person_lname'];
            $params['iss_contact_person_fname'] = $data['contact_person_fname'];
            $params['iss_contact_email'] = $data['contact_email'];
            $params['iss_contact_phone'] = $data['contact_phone'];
            $params['iss_contact_timezone'] = $data['contact_timezone'];
        }

        $stmt = 'INSERT INTO {{%issue}} SET ' . DB_Helper::buildSet($params);

        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return -1;
        }

        $issue_id = DB_Helper::get_last_insert_id();

        return $issue_id;
    }

    /**
     * Returns the list of action date fields appropriate for the
     * current user ID.
     *
     * @return  string The list of action date fields
     */
    public static function getLastActionFields()
    {
        $last_action_fields = [
            'iss_last_public_action_date',
        ];
        if (Auth::getCurrentRole() > User::ROLE_CUSTOMER) {
            $last_action_fields[] = 'iss_last_internal_action_date';
        }
        if (count($last_action_fields) > 1) {
            return 'GREATEST(' . implode(', IFNULL(', $last_action_fields) . ", '0000-00-00')) AS last_action_date";
        }

        return $last_action_fields[0] . ' AS last_action_date';
    }

    /**
     * Processes a result set to format the "Last Action Date" column.
     *
     * @param   array $result The result set
     */
    public static function formatLastActionDates(&$result)
    {
        $role_id = Auth::getCurrentRole();
        $customer_role_id = User::ROLE_CUSTOMER;
        foreach ($result as &$row) {
            if ($row['action_type'] == 'internal' && $role_id > $customer_role_id) {
                $label = $row['iss_last_internal_action_type'];
                $last_date = $row['iss_last_internal_action_date'];
            } else {
                $label = $row['iss_last_public_action_type'];
                $last_date = $row['iss_last_public_action_date'];
            }

            $dateDiff = Date_Helper::getFormattedDateDiff(time(), $last_date);
            $row['last_action_date_diff'] = $dateDiff;
            switch ($label) {
                case 'customer action':
                    $label = ev_gettext('Customer Action');
                    break;
                case 'update':
                    $label = ev_gettext('Update');
                    break;
                case 'updated':
                    $label = ev_gettext('Updated');
                    break;
                case 'created':
                    $label = ev_gettext('Created');
                    break;
                case 'closed':
                    $label = ev_gettext('Closed');
                    break;
                case 'time added':
                    $label = ev_gettext('Time Added');
                    break;
                case 'file uploaded':
                    $label = ev_gettext('File Uploaded');
                    break;
                case 'draft saved':
                    $label = ev_gettext('Draft Saved');
                    break;
                case 'note':
                    $label = ev_gettext('Note');
                    break;
                case 'staff response':
                    $label = ev_gettext('Staff Response');
                    break;
                case 'phone call':
                    $label = ev_gettext('Phone Call');
                    break;
                case 'user response':
                    $label = ev_gettext('User Response');
                    break;
                case 'email':
                    $label = ev_gettext('Email');
                    break;
                case 'scm checkin':
                    $label = ev_gettext('SCM Checkin');
                    break;
                default:
                    $label = ucwords($label);
            }
            $row['last_action_date_label'] = $label;
        }
    }

    /**
     * Retrieves the last status change date for the given issue.
     *
     * @param   int $prj_id The project ID
     * @param   array $result The associative array of data
     * @see     Search::getListing()
     */
    public static function getLastStatusChangeDates($prj_id, &$result)
    {
        $ids = [];
        foreach ($result as $res) {
            $ids[] = $res['iss_sta_id'];
        }
        if (!$ids) {
            return;
        }
        $customizations = Status::getProjectStatusCustomization($prj_id, $ids);
        foreach ($result as &$row) {
            if (empty($row['iss_sta_id'])) {
                $row['status_change_date'] = '';
                continue;
            }

            list($label, $date_field_name) = @$customizations[$row['iss_sta_id']];
            if ((empty($label)) || (empty($date_field_name))) {
                $row['status_change_date'] = '';
                continue;
            }

            // TRANSLATORS: %1 = label, %2 = date diff
            $desc = ev_gettext('%1$s: %2$s ago');
            $target_date = $row[$date_field_name];
            if (empty($target_date)) {
                $row['status_change_date'] = '';
                continue;
            }

            $dateDiff = Date_Helper::getFormattedDateDiff(time(), $target_date);
            $row['status_change_date'] = sprintf($desc, $label, $dateDiff);
        }
    }

    /**
     * Method used to get the previous and next issues that are available
     * according to the current search parameters.
     *
     * @param   int $issue_id The issue ID
     * @param   array $options The search parameters
     * @return  array The list of issues
     */
    public static function getSides($issue_id, $options)
    {
        $res = Search::getListing(Auth::getCurrentProject(), $options);
        $res = array_column($res['list'], 'iss_id');
        $index = array_search($issue_id, $res);
        if (!empty($res[$index + 1])) {
            $next = $res[$index + 1];
        }
        if (!empty($res[$index - 1])) {
            $previous = $res[$index - 1];
        }

        return [
            'next' => @$next,
            'previous' => @$previous,
        ];
    }

    /**
     * Method used to get the full list of user IDs assigned to a specific
     * issue.
     *
     * @param   int $issue_id The issue ID
     * @return  array The list of user IDs
     */
    public static function getAssignedUserIDs($issue_id)
    {
        $stmt = 'SELECT
                    usr_id
                 FROM
                    {{%issue_user}},
                    {{%user}}
                 WHERE
                    isu_iss_id=? AND
                    isu_usr_id=usr_id';
        try {
            $res = DB_Helper::getInstance()->getColumn($stmt, [$issue_id]);
        } catch (DatabaseException $e) {
            return [];
        }

        return $res;
    }

    /**
     * Method used to see if a user is assigned to an issue.
     *
     * @param   int $issue_id The issue ID
     * @param   int $usr_id an integer containg the ID of the user
     * @return  bool true if the user(s) are assigned to the issue
     */
    public static function isAssignedToUser($issue_id, $usr_id)
    {
        $assigned_users = self::getAssignedUserIDs($issue_id);
        if (in_array($usr_id, $assigned_users)) {
            return true;
        }

        return false;
    }

    /**
     * Method used to get the full list of reporters associated with a given
     * list of issues.
     *
     * @param   array $result The result set
     * @deprecated method not used
     */
    public static function getReportersByIssues(&$result)
    {
        $ids = [];
        foreach ($result as $res) {
            $ids[] = $res['iss_id'];
        }
        $ids = implode(', ', $ids);
        $stmt = "SELECT
                    iss_id,
                    CONCAT(usr_full_name, ' <', usr_email, '>') AS usr_full_name
                 FROM
                    {{%issue}},
                    {{%user}}
                 WHERE
                    iss_usr_id=usr_id AND
                    iss_id IN ($ids)";

        try {
            $res = DB_Helper::getInstance()->getPair($stmt);
        } catch (DatabaseException $e) {
            return;
        }

        // now populate the $result variable again
        foreach ($result as &$row) {
            $row['reporter'] = $res[$row['iss_id']];
        }
    }

    /**
     * Method used to get the full list of assigned users by a list
     * of issues. This was originally created to optimize the issue
     * listing page.
     *
     * @param   array $result The result set
     */
    public static function getAssignedUsersByIssues(&$result)
    {
        $ids = [];
        foreach ($result as $res) {
            $ids[] = $res['iss_id'];
        }
        if (count($ids) < 1) {
            return;
        }
        $ids = implode(', ', $ids);
        $stmt = "SELECT
                    isu_iss_id,
                    usr_full_name
                 FROM
                    {{%issue_user}},
                    {{%user}}
                 WHERE
                    isu_usr_id=usr_id AND
                    isu_iss_id IN ($ids)";
        try {
            $res = DB_Helper::getInstance()->getAll($stmt);
        } catch (DatabaseException $e) {
            return;
        }

        $t = [];
        foreach ($res as &$row) {
            if (!empty($t[$row['isu_iss_id']])) {
                $t[$row['isu_iss_id']] .= ', ' . $row['usr_full_name'];
            } else {
                $t[$row['isu_iss_id']] = $row['usr_full_name'];
            }
        }

        // now populate the $result variable again
        foreach ($result as &$res) {
            $res['assigned_users'] = isset($t[$res['iss_id']]) ? $t[$res['iss_id']] : '';
        }
    }

    /**
     * Method used to add the issue description to a list of issues.
     *
     * @param   array $result The result set
     */
    public static function getDescriptionByIssues(&$result)
    {
        if (count($result) == 0) {
            return;
        }

        $ids = [];
        foreach ($result as $res) {
            $ids[] = $res['iss_id'];
        }
        $ids = implode(', ', $ids);

        $stmt = "SELECT
                    iss_id,
                    iss_description
                 FROM
                    {{%issue}}
                 WHERE
                    iss_id in ($ids)";
        try {
            $res = DB_Helper::getInstance()->getPair($stmt);
        } catch (DatabaseException $e) {
            return;
        }

        foreach ($result as &$row) {
            $row['iss_description'] = $res[$row['iss_id']];
        }
    }

    /**
     * Method used to get the full list of users (the full names) assigned to a
     * specific issue.
     *
     * @param   int $issue_id The issue ID
     * @return  array The list of users
     */
    public static function getAssignedUsers($issue_id)
    {
        $stmt = 'SELECT
                    usr_full_name
                 FROM
                    {{%issue_user}},
                    {{%user}}
                 WHERE
                    isu_iss_id=? AND
                    isu_usr_id=usr_id';
        try {
            $res = DB_Helper::getInstance()->getColumn($stmt, [$issue_id]);
        } catch (DatabaseException $e) {
            return [];
        }

        return $res;
    }

    /**
     * Method used to get the full list of users (the email usernames) assigned to a
     * specific issue.
     *
     * @param   int $issue_id The issue ID
     * @return  array The list of users
     * @deprecated method not used
     */
    public static function getAssignedUserEmailHandles($issue_id)
    {
        $stmt = "SELECT
                    usr_id,
                    SUBSTRING(usr_email, 1, INSTR(usr_email, '@')-1) AS handle
                 FROM
                    {{%issue_user}},
                    {{%user}}
                 WHERE
                    isu_iss_id=? AND
                    isu_usr_id=usr_id";
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, [$issue_id]);
        } catch (DatabaseException $e) {
            return [];
        }

        return array_values($res);
    }

    /**
     * Method used to get the details for a specific issue.
     *
     * @param   int $issue_id The issue ID
     * @param   bool $force_refresh if the cache should not be used
     * @return  array The details for the specified issue
     */
    public static function getDetails($issue_id, $force_refresh = false)
    {
        static $returns;

        if (empty($issue_id)) {
            return '';
        }

        if (!empty($returns[$issue_id]) && $force_refresh != true) {
            return $returns[$issue_id];
        }

        $stmt = 'SELECT
                    {{%issue}}.*,
                    prj_title,
                    prc_title,
                    pre_title,
                    pri_title,
                    pri_icon,
                    sev_title,
                    sta_title,
                    sta_abbreviation,
                    sta_color status_color,
                    sta_is_closed
                 FROM
                    (
                    {{%issue}},
                    {{%project}}
                    )
                 LEFT JOIN
                    {{%project_priority}}
                 ON
                    iss_pri_id=pri_id
                 LEFT JOIN
                    {{%project_severity}}
                 ON
                    iss_sev_id=sev_id
                 LEFT JOIN
                    {{%status}}
                 ON
                    iss_sta_id=sta_id
                 LEFT JOIN
                    {{%project_category}}
                 ON
                    iss_prc_id=prc_id
                 LEFT JOIN
                    {{%project_release}}
                 ON
                    iss_pre_id=pre_id
                 WHERE
                    iss_id=? AND
                    iss_prj_id=prj_id';
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, [$issue_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        if (empty($res)) {
            return '';
        }

        $created_date_ts = Date_Helper::getUnixTimestamp($res['iss_created_date'], Date_Helper::getDefaultTimezone());
        // get customer information, if any
        if ((!empty($res['iss_customer_id'])) && (CRM::hasCustomerIntegration($res['iss_prj_id']))) {
            $crm = CRM::getInstance($res['iss_prj_id']);
            try {
                $customer = $crm->getCustomer($res['iss_customer_id']);
                $contract = $crm->getContract($res['iss_customer_contract_id']);
                $res['contact_local_time'] = Date_Helper::getFormattedDate(Date_Helper::getCurrentDateGMT(), $res['iss_contact_timezone']);
                $res['customer'] = $customer;
                $res['contract'] = $contract;
                $res['contact'] = $crm->getContact($res['iss_customer_contact_id']);
                // TODOCRM: Deal with incidents
//                    $res['redeemed_incidents'] = Customer::getRedeemedIncidentDetails($res['iss_prj_id'], $res['iss_id']);
                $max_first_response_time = $contract->getMaximumFirstResponseTime($issue_id);
                $res['max_first_response_time'] = Misc::getFormattedTime($max_first_response_time / 60);
                if (empty($res['iss_first_response_date'])) {
                    $first_response_deadline = $created_date_ts + $max_first_response_time;
                    if (time() <= $first_response_deadline) {
                        $res['max_first_response_time_left'] = Date_Helper::getFormattedDateDiff($first_response_deadline, time());
                    } else {
                        $res['overdue_first_response_time'] = Date_Helper::getFormattedDateDiff(time(), $first_response_deadline);
                    }
                }
            } catch (CRMException $e) {
                // TODOCRM: Log exception?
            }
        }

        $res['iss_original_description'] = $res['iss_description'];
        $res['iss_original_percent_complete'] = $res['iss_percent_complete'];
        $res['iss_description'] = nl2br(htmlspecialchars($res['iss_description']));
        $res['iss_resolution'] = Resolution::getTitle($res['iss_res_id']);
        $res['iss_created_date_ts'] = $created_date_ts;
        $res['assignments'] = @implode(', ', array_values(self::getAssignedUsers($res['iss_id'])));
        list($res['authorized_names'], $res['authorized_repliers']) = Authorized_Replier::getAuthorizedRepliers($res['iss_id']);
        $temp = self::getAssignedUsersStatus($res['iss_id']);
        $res['has_inactive_users'] = 0;
        $res['assigned_users'] = [];
        $res['assigned_inactive_users'] = [];
        foreach ($temp as $usr_id => $usr_status) {
            if (!User::isActiveStatus($usr_status)) {
                $res['assigned_inactive_users'][] = $usr_id;
                $res['has_inactive_users'] = 1;
            } else {
                $res['assigned_users'][] = $usr_id;
            }
        }
        if (@in_array(Auth::getUserID(), $res['assigned_users'])) {
            $res['is_current_user_assigned'] = 1;
        } else {
            $res['is_current_user_assigned'] = 0;
        }

        $repo = IssueAssociationRepository::create();
        $res['associated_issues'] = $repo->getAssociatedIssues($res['iss_id']);
        $res['associated_issues_details'] = $repo->getIssueDetails($res['associated_issues']);

        $res['reporter'] = User::getFullName($res['iss_usr_id']);
        if (empty($res['iss_updated_date'])) {
            $res['iss_updated_date'] = $res['iss_created_date'];
        }
        $res['estimated_formatted_time'] = Misc::getFormattedTime($res['iss_dev_time']);
        if (Release::isAssignable($res['iss_pre_id'])) {
            $release = Release::getDetails($res['iss_pre_id']);
            $res['pre_title'] = $release['pre_title'];
            $res['pre_status'] = $release['pre_status'];
        }
        // need to return the list of issues that are duplicates of this one
        $res['duplicates'] = self::getDuplicateList($res['iss_id']);
        $res['duplicates_details'] = self::getDuplicateDetailsList($res['iss_id']);
        // also get the issue title of the duplicated issue
        if (!empty($res['iss_duplicated_iss_id'])) {
            $res['duplicated_issue'] = self::getDuplicatedDetails($res['iss_duplicated_iss_id']);
        }

        // get group information
        if (!empty($res['iss_grp_id'])) {
            $res['group'] = Group::getDetails($res['iss_grp_id']);
        }

        // get quarantine issue
        $res['quarantine'] = self::getQuarantineInfo($res['iss_id']);

        $res['products'] = Product::getProductsByIssue($res['iss_id']);

        $res['access_level_name'] = Access::getAccessLevelName($res['iss_access_level']);

        $returns[$issue_id] = $res;

        return $res;
    }

    /**
     * Method used to get some simple details about the given duplicated issue.
     *
     * @param   int $issue_id The issue ID
     * @return  array The duplicated issue details
     */
    public static function getDuplicatedDetails($issue_id)
    {
        $stmt = 'SELECT
                    iss_summary title,
                    sta_title current_status,
                    sta_is_closed is_closed
                 FROM
                    {{%issue}},
                    {{%status}}
                 WHERE
                    iss_sta_id=sta_id AND
                    iss_id=?';
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, [$issue_id]);
        } catch (DatabaseException $e) {
            return [];
        }

        return $res;
    }

    /**
     * Method used to bulk update a list of issues
     *
     * @return  bool
     */
    public static function bulkUpdate()
    {
        // check if user performing this chance has the proper role
        if (Auth::getCurrentRole() < User::ROLE_MANAGER) {
            return -1;
        }

        $items = (array) $_POST['item'];
        $new_status_id = (int) $_POST['status'];
        $new_release_id = (int) $_POST['release'];
        $new_priority_id = (int) $_POST['priority'];
        $new_category_id = (int) $_POST['category'];

        foreach ($items as $issue_id) {
            $issue_id = (int) $issue_id;
            if (!self::canAccess($issue_id, Auth::getUserID())) {
                continue;
            }
            if (self::getProjectID($issue_id) != Auth::getCurrentProject()) {
                // make sure issue is not in another project
                continue;
            }

            $issue_details = self::getDetails($issue_id);

            $updated_fields = [];

            // update assignment
            if (count(@$_POST['users']) > 0) {
                $users = (array) $_POST['users'];
                // get who this issue is currently assigned too
                $stmt = 'SELECT
                            isu_usr_id,
                            usr_full_name
                         FROM
                            {{%issue_user}},
                            {{%user}}
                         WHERE
                            isu_usr_id = usr_id AND
                            isu_iss_id = ?';
                try {
                    $current_assignees = DB_Helper::getInstance()->getPair($stmt, [$issue_id]);
                } catch (DatabaseException $e) {
                    return -1;
                }

                foreach ($current_assignees as $usr_id => $usr_name) {
                    if (!in_array($usr_id, $users)) {
                        self::deleteUserAssociation($issue_id, $usr_id, false);
                    }
                }
                $new_user_names = [];
                $new_assignees = [];
                foreach ($users as $usr_id) {
                    $usr_id = (int) $usr_id;
                    $new_user_names[$usr_id] = User::getFullName($usr_id);

                    // check if the issue is already assigned to this person
                    $stmt = 'SELECT
                                COUNT(*) AS total
                             FROM
                                {{%issue_user}}
                             WHERE
                                isu_iss_id=? AND
                                isu_usr_id=?';
                    $total = DB_Helper::getInstance()->getOne($stmt, [$issue_id, $usr_id]);
                    if ($total > 0) {
                        continue;
                    }
                    $new_assignees[] = $usr_id;
                        // add the assignment
                        self::addUserAssociation(Auth::getUserID(), $issue_id, $usr_id, false);
                    Notification::subscribeUser(Auth::getUserID(), $issue_id, $usr_id, Notification::getAllActions());
                }

                $prj_id = Auth::getCurrentProject();
                $usr_ids = self::getAssignedUserIDs($issue_id);
                Workflow::handleAssignmentChange($prj_id, $issue_id, Auth::getUserID(), $issue_details, $usr_ids, false);
                Notification::notifyNewAssignment($new_assignees, $issue_id);
                $updated_fields['Assignment'] = History::formatChanges(implode(', ', $current_assignees), implode(', ', $new_user_names));
            }

            // update status
            if ($new_status_id) {
                $old_status_id = self::getStatusID($issue_id);
                $res = self::setStatus($issue_id, $new_status_id, false);
                if ($res == 1) {
                    $updated_fields['Status'] = History::formatChanges(Status::getStatusTitle($old_status_id), Status::getStatusTitle($new_status_id));
                }
            }

            // update release
            if ($new_release_id) {
                $old_release_id = self::getRelease($issue_id);
                $res = self::setRelease($issue_id, $new_release_id);
                if ($res == 1) {
                    $updated_fields['Release'] = History::formatChanges(Release::getTitle($old_release_id), Release::getTitle($new_release_id));
                }
            }

            // update priority
            if ($new_priority_id) {
                $old_priority_id = self::getPriority($issue_id);
                $res = self::setPriority($issue_id, $new_priority_id);
                if ($res == 1) {
                    $updated_fields['Priority'] = History::formatChanges(Priority::getTitle($old_priority_id), Priority::getTitle($new_priority_id));
                }
            }

            // update category
            if ($new_category_id) {
                $old_category_id = self::getCategory($issue_id);
                $res = self::setCategory($issue_id, $new_category_id);
                if ($res == 1) {
                    $updated_fields['Category'] = History::formatChanges(Category::getTitle($old_category_id), Category::getTitle($new_category_id));
                }
            }

            if (count($updated_fields) > 0) {
                // log the changes
                $changes = '';
                $k = 0;
                foreach ($updated_fields as $key => $value) {
                    if ($k > 0) {
                        $changes .= '; ';
                    }
                    $changes .= "$key: $value";
                    $k++;
                }
                $usr_id = Auth::getUserID();
                History::add($issue_id, $usr_id, 'issue_bulk_updated', 'Issue updated ({changes}) by {user}', [
                    'changes' => $changes,
                    'user' => User::getFullName(Auth::getUserID()),
                ]);
            }

            // close if request
            if ((isset($_REQUEST['closed_status'])) && (!empty($_REQUEST['closed_status']))) {
                self::close(Auth::getUserID(), $issue_id, true, 0, $_REQUEST['closed_status'], $_REQUEST['closed_message'], $_REQUEST['notification_list']);
            }
        }

        return true;
    }

    /**
     * Method used to get the full list of issue IDs that area available in the
     * system.
     *
     * @param   string $extra_condition An extra condition in the WHERE clause
     * @return  array The list of issue IDs
     */
    public static function getColList($extra_condition = null)
    {
        $stmt = 'SELECT
                    iss_id
                 FROM
                    {{%issue}}
                 WHERE
                    iss_prj_id=' . Auth::getCurrentProject();
        if (!empty($extra_condition)) {
            $stmt .= " AND $extra_condition ";
        }
        $stmt .= '
                 ORDER BY
                    iss_id DESC';
        try {
            $res = DB_Helper::getInstance()->getColumn($stmt);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the full list of issue IDs and their respective
     * titles.
     *
     * @param   string $extra_condition An extra condition in the WHERE clause
     * @return  array The list of issues
     * @deprecated method not used
     */
    public static function getAssocList($extra_condition = null)
    {
        $stmt = 'SELECT
                    iss_id,
                    iss_summary
                 FROM
                    {{%issue}}
                 WHERE
                    iss_prj_id=' . Auth::getCurrentProject();
        if (!empty($extra_condition)) {
            $stmt .= " AND $extra_condition ";
        }
        $stmt .= '
                 ORDER BY
                    iss_id ASC';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to check whether an issue was already closed or not.
     *
     * @param   int $issue_id The issue ID
     * @return  bool
     */
    public static function isClosed($issue_id)
    {
        $stmt = 'SELECT
                    COUNT(*)
                 FROM
                    {{%issue}},
                    {{%status}}
                 WHERE
                    iss_id=? AND
                    iss_sta_id=sta_id AND
                    sta_is_closed=1';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$issue_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        return $res != 0;
    }

    /**
     * Returns a simple list of issues that are currently set to some
     * form of quarantine. This is mainly used by the IRC interface.
     *
     * @return  array List of quarantined issues
     */
    public static function getQuarantinedIssueList()
    {
        // XXX: would be nice to restrict the result list to only one project
        $stmt = 'SELECT
                    iss_id,
                    iss_summary
                 FROM
                    {{%issue}},
                    {{%issue_quarantine}}
                 WHERE
                    iqu_iss_id=iss_id AND
                    iqu_expiration >= ? AND
                    iqu_expiration IS NOT NULL';
        $params = [Date_Helper::getCurrentDateGMT()];
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, $params);
        } catch (DatabaseException $e) {
            return [];
        }

        self::getAssignedUsersByIssues($res);

        return $res;
    }

    /**
     * Returns the status of a quarantine.
     *
     * @param   int $issue_id The issue ID
     * @return  int indicates what the current state of quarantine is
     */
    public static function getQuarantineInfo($issue_id)
    {
        $stmt = 'SELECT
                    iqu_status,
                    iqu_expiration
                 FROM
                    {{%issue_quarantine}}
                 WHERE
                    iqu_iss_id = ? AND
                        (iqu_expiration > ? OR
                        iqu_expiration IS NULL)';
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, [$issue_id, Date_Helper::getCurrentDateGMT()]);
        } catch (DatabaseException $e) {
            return [];
        }

        if (!empty($res['iqu_expiration'])) {
            $expiration_ts = Date_Helper::getUnixTimestamp($res['iqu_expiration'], Date_Helper::getDefaultTimezone());
            $res['time_till_expiration'] = Date_Helper::getFormattedDateDiff($expiration_ts, time());
        }

        return $res;
    }

    /**
     * Sets the quarantine status. Optionally an expiration date can be set
     * to indicate when the quarantine expires. A status > 0 indicates that quarantine is active.
     *
     * @param   int $issue_id The issue ID
     * @param   int $status The quarantine status
     * @param   string $expiration The expiration date of quarantine (default empty)
     * @return int
     */
    public static function setQuarantine($issue_id, $status, $expiration = '')
    {
        $issue_id = (int) $issue_id;
        $status = (int) $status;

        // see if there is an existing record
        $stmt = 'SELECT
                    COUNT(*)
                 FROM
                    {{%issue_quarantine}}
                 WHERE
                    iqu_iss_id = ?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$issue_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        if ($res > 0) {
            // update
            $stmt = 'UPDATE
                        {{%issue_quarantine}}
                     SET
                        iqu_status = ?';
            $params = [$status];
            if (!empty($expiration)) {
                $stmt .= ",\niqu_expiration = ?";
                $params[] = $expiration;
            }
            $stmt .= "\nWHERE
                        iqu_iss_id = ?";
            $params[] = $issue_id;
            try {
                DB_Helper::getInstance()->query($stmt, $params);
            } catch (DatabaseException $e) {
                return -1;
            }

            // add history entry about this change taking place
            if ($status == 0) {
                $usr_id = Auth::getUserID();
                History::add($issue_id, $usr_id, 'issue_quarantine_removed', 'Issue quarantine status cleared by {user}', [
                    'user' => User::getFullName(Auth::getUserID()),
                ]);
            }

            return 1;
        }

        // insert
        $params = [
            'iqu_iss_id' => $issue_id,
            'iqu_status' => $status,
        ];
        if (!empty($expiration)) {
            $params['iqu_expiration'] = $expiration;
        }
        $stmt = 'INSERT INTO {{%issue_quarantine}} SET ' . DB_Helper::buildSet($params);

        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Sets the group of the issue.
     *
     * @param   int $issue_id The ID of the issue
     * @param   int $group_id The ID of the group
     * @return  int 1 if successful, -1 or -2 otherwise
     * @deprecated method not used
     */
    public static function setGroup($issue_id, $group_id)
    {
        $issue_id = (int) $issue_id;
        $group_id = (int) $group_id;

        $current = self::getDetails($issue_id);
        if ($current['iss_grp_id'] == $group_id) {
            return -2;
        }
        $stmt = 'UPDATE
                    {{%issue}}
                 SET
                    iss_grp_id = ?
                 WHERE
                    iss_id = ?';
        try {
            DB_Helper::getInstance()->query($stmt, [$group_id, $issue_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        $usr_id = Auth::getUserID();
        if (!$usr_id) {
            $usr_id = APP_SYSTEM_USER_ID;
        }

        History::add($issue_id, $usr_id, 'group_changed', 'Group changed ({changes}) by {user}', [
            'changes' => History::formatChanges(Group::getName($current['iss_grp_id']), Group::getName($group_id)),
            'user' => User::getFullName($usr_id),
        ]);

        return 1;
    }

    /**
     * Returns the group ID associated with the given issue ID.
     *
     * @param   int $issue_id The issue ID
     * @return  int The associated group ID
     */
    public static function getGroupID($issue_id)
    {
        $stmt = 'SELECT
                    iss_grp_id
                 FROM
                    {{%issue}}
                 WHERE
                    iss_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$issue_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        return $res;
    }

    /**
     * Method to determine if user can access a particular issue
     *
     * @param   int $issue_id the ID of the issue
     * @param   int $usr_id The ID of the user
     * @return  bool If the user can access the issue
     */
    public static function canAccess($issue_id, $usr_id)
    {
        return Access::canAccessIssue($issue_id, $usr_id);
    }

    /**
     * Returns true if the user can update the issue
     *
     * @param   int $issue_id the ID of the issue
     * @param   int $usr_id The ID of the user
     * @return  bool If the user can update the issue
     */
    public static function canUpdate($issue_id, $usr_id)
    {
        return Access::canUpdateIssue($issue_id, $usr_id);
    }

    /**
     * Clears closed information from an issues.
     *
     * @param   int $issue_id The ID of the issue
     * @return int
     */
    public static function clearClosed($issue_id)
    {
        $stmt = 'UPDATE
                    {{%issue}}
                 SET
                    iss_closed_date = null,
                    iss_res_id = null
                 WHERE
                    iss_id=?';
        try {
            DB_Helper::getInstance()->query($stmt, [$issue_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Returns the message ID that should be used as the parent ID for all messages
     *
     * @param   int $issue_id The ID of the issue
     * @return string
     */
    public static function getRootMessageID($issue_id)
    {
        $sql = 'SELECT
                    iss_root_message_id
                FROM
                    {{%issue}}
                WHERE
                    iss_id=?';

        return DB_Helper::getInstance()->getOne($sql, [$issue_id]);
    }

    /**
     * Returns the issue ID of the issue with the specified root message ID, or false
     *
     * @param   string $msg_id The Message ID
     * @return  int The ID of the issue
     */
    public static function getIssueByRootMessageID($msg_id)
    {
        static $returns;

        if (!empty($returns[$msg_id])) {
            return $returns[$msg_id];
        }

        $sql = 'SELECT
                    iss_id
                FROM
                    {{%issue}}
                WHERE
                    iss_root_message_id = ?';
        try {
            $res = DB_Helper::getInstance()->getOne($sql, [$msg_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        if (empty($res)) {
            $returns[$msg_id] = false;
        } else {
            $returns[$msg_id] = $res;
        }

        return $returns[$msg_id];
    }

    /**
     * Sets the assignees for the issue
     *
     * @param   int $issue_id
     * @param   array $assignees
     * @return  int 1 if success, -1 if error, 0 if no change was needed
     */
    public static function setAssignees($issue_id, $assignees)
    {
        if (!is_array($assignees)) {
            $assignees = [];
        }

        // see if there is anything to change
        $old_assignees = self::getAssignedUserIDs($issue_id);
        if ((count(array_diff($old_assignees, $assignees)) == 0) && (count(array_diff($assignees, $old_assignees)) == 0)) {
            return 0;
        }

        $old_assignee_names = self::getAssignedUsers($issue_id);

        Workflow::handleAssignmentChange(self::getProjectID($issue_id), $issue_id, Auth::getUserID(), self::getDetails($issue_id), $assignees, true);
        // clear up the assignments for this issue, and then assign it to the current user
        self::deleteUserAssociations($issue_id);
        $assignee_names = [];
        foreach ($assignees as $assignee) {
            $res = self::addUserAssociation(Auth::getUserID(), $issue_id, $assignee, false);
            if ($res == -1) {
                return -1;
            }
            $assignee_names[] = User::getFullName($assignee);
            Notification::subscribeUser(Auth::getUserID(), $issue_id, $assignee, Notification::getDefaultActions($issue_id, User::getEmail($assignee), 'set_assignees'), false);
        }

        Notification::notifyNewAssignment($assignees, $issue_id);

        $usr_id = Auth::getUserID();
        History::add($issue_id, $usr_id, 'user_associated', 'Issue assignment to changed ({changes}) by {user}', [
            'changes' => History::formatChanges(implode(', ', $old_assignee_names), implode(', ', $assignee_names)),
            'user' => User::getFullName($usr_id),
        ]);

        return 1;
    }

    /**
     * Sets the access level of the issue.
     *
     * @param   int $issue_id The ID of the issue
     * @param   string $level The Access level
     * @return  int 1 if successful, -1 otherwise
     */
    public static function setAccessLevel($issue_id, $level)
    {
        $issue_id = (int) $issue_id;
        $usr_id = Auth::getUserID();

        $old_access_level = self::getAccessLevel($issue_id);
        if ($level == $old_access_level) {
            return 1;
        }

        $stmt = 'UPDATE
                    {{%issue}}
                 SET
                    iss_access_level = ?
                 WHERE
                    iss_id = ?';
        try {
            DB_Helper::getInstance()->query($stmt, [$level, $issue_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        History::add($issue_id, $usr_id, 'access_level_changed', 'Access level changed ({changes}) by {user}', [
            'changes' => History::formatChanges(Access::getAccessLevelName($old_access_level), Access::getAccessLevelName($level)),
            'user' => User::getFullName($usr_id),
        ]);

        return 1;
    }

    /**
     * Returns the access level associated with the given issue ID.
     *
     * @param   int $issue_id The issue ID
     * @return  string The Access Level
     */
    public static function getAccessLevel($issue_id)
    {
        $stmt = 'SELECT
                    iss_access_level
                 FROM
                    {{%issue}}
                 WHERE
                    iss_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$issue_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        return $res;
    }
}
