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

class Access
{
    /**
     * Method to determine if user can access a particular issue
     *
     * @param   int $issue_id the ID of the issue
     * @param   int $usr_id The ID of the user
     * @param   bool $log If the check should be logged. Default true
     * @return  bool If the user can access the issue
     */
    public static function canAccessIssue($issue_id, $usr_id, $log = true)
    {
        static $access;

        if (empty($issue_id)) {
            return false;
        }

        if (isset($access[$issue_id . '-' . $usr_id])) {
            return $access[$issue_id . '-' . $usr_id];
        }

        $details = Issue::getDetails($issue_id);
        if (empty($details)) {
            return true;
        }
        $usr_details = User::getDetails($usr_id);
        $usr_role = User::getRoleByUser($usr_id, $details['iss_prj_id']);
        $prj_id = $details['iss_prj_id'];

        $can_access_contract = false;
        if (CRM::hasCustomerIntegration($prj_id)) {
            $crm = CRM::getInstance($prj_id);
            try {
                if (!empty($usr_details['usr_customer_contact_id']) && !empty($details['iss_customer_contract_id'])) {
                    $contact = $crm->getContact($usr_details['usr_customer_contact_id']);
                    $can_access_contract = $contact->canAccessContract($crm->getContract($details['iss_customer_contract_id']));
                }
            } catch (CRMException $e) {
                // TODOCRM: Log exception?
            }
        }

        if (empty($usr_role)) {
            // check if they are even allowed to access the project
            $return = false;
        } elseif ((CRM::hasCustomerIntegration($details['iss_prj_id'])) && ($usr_role == User::ROLE_CUSTOMER)) {
            // check customer permissions
            $return = $can_access_contract;
        } elseif (!empty($usr_details['usr_par_code']) &&
                        !Partner::isPartnerEnabledForIssue($usr_details['usr_par_code'], $issue_id)) {
            // check if the user is a partner
            $return = false;
        } elseif ($details['iss_access_level'] !== 'normal') {
            $is_assignee_or_access_list = (Issue::isAssignedToUser($issue_id, $usr_id) or self::isOnAccessList($issue_id, $usr_id));
            if ($usr_role >= User::ROLE_MANAGER || $is_assignee_or_access_list) {
                $return = true;
            } elseif (substr($details['iss_access_level'], 0, 6) === 'group_' &&
                in_array(substr($details['iss_access_level'], 6), User::getGroupIDs($usr_id))) {
                $return = true;
            } else {
                $return = false;
            }
        } elseif ((Auth::getCurrentRole() == User::ROLE_REPORTER) && (Project::getSegregateReporters($prj_id)) &&
                ($details['iss_usr_id'] != $usr_id) && (!Authorized_Replier::isUserAuthorizedReplier($issue_id, $usr_id))) {
            $return = false;
        } else {
            $return = true;
        }

        $workflow = Workflow::canAccessIssue($prj_id, $issue_id, $usr_id);
        if ($workflow !== null) {
            $return = $workflow;
        }

        $access[$issue_id . '-' . $usr_id] = $return;

        if ($log) {
            self::log($return, $issue_id, $usr_id);
        }

        return $return;
    }

    public static function canViewIssuePartners($issue_id, $usr_id)
    {
        if (!self::canAccessIssue($issue_id, $usr_id)) {
            return false;
        }
        $prj_id = Auth::getCurrentProject();
        if (User::isPartner($usr_id)) {
            $partner = Partner::canUserAccessIssueSection($usr_id, 'partners');
            if (is_bool($partner)) {
                return $partner;
            }
        }
        if (User::getRoleByUser($usr_id, $prj_id) > User::ROLE_CUSTOMER) {
            return true;
        }

        return false;
    }

    public static function canViewAttachedFiles($issue_id, $usr_id)
    {
        if (!self::canAccessIssue($issue_id, $usr_id)) {
            return false;
        }
        $prj_id = Auth::getCurrentProject();
        if (User::isPartner($usr_id)) {
            $partner = Partner::canUserAccessIssueSection($usr_id, 'files');
            if (is_bool($partner)) {
                return $partner;
            }
        }
        if (User::getRoleByUser($usr_id, $prj_id) >= User::ROLE_VIEWER) {
            return true;
        }

        return false;
    }

    public static function canViewTimeTracking($issue_id, $usr_id)
    {
        if (!self::canAccessIssue($issue_id, $usr_id)) {
            return false;
        }
        $prj_id = Auth::getCurrentProject();
        if (User::isPartner($usr_id)) {
            $partner = Partner::canUserAccessIssueSection($usr_id, 'time');
            if (is_bool($partner)) {
                return $partner;
            }
        }
        if (User::getRoleByUser($usr_id, $prj_id) > User::ROLE_CUSTOMER) {
            return true;
        }

        return false;
    }

    public static function canViewCheckins($issue_id, $usr_id)
    {
        if (!self::canAccessIssue($issue_id, $usr_id)) {
            return false;
        }
        $prj_id = Auth::getCurrentProject();

        if (User::getRoleByUser($usr_id, $prj_id) > User::ROLE_CUSTOMER) {
            return true;
        }

        return false;
    }

    public static function canViewInternalNotes($issue_id, $usr_id)
    {
        if (!self::canAccessIssue($issue_id, $usr_id, false)) {
            return false;
        }
        $prj_id = Auth::getCurrentProject();
        if (User::isPartner($usr_id)) {
            $partner = Partner::canUserAccessIssueSection($usr_id, 'notes');
            if (is_bool($partner)) {
                return $partner;
            }
        }
        if (User::getRoleByUser($usr_id, $prj_id) > User::ROLE_CUSTOMER) {
            return true;
        }

        return false;
    }

    public static function canViewPhoneCalls($issue_id, $usr_id)
    {
        if (!self::canAccessIssue($issue_id, $usr_id)) {
            return false;
        }
        $prj_id = Auth::getCurrentProject();
        if (User::isPartner($usr_id)) {
            $partner = Partner::canUserAccessIssueSection($usr_id, 'phone');
            if (is_bool($partner)) {
                return $partner;
            }
        }
        if (User::getRoleByUser($usr_id, $prj_id) > User::ROLE_CUSTOMER) {
            return true;
        }

        return false;
    }

    public static function canViewDrafts($issue_id, $usr_id)
    {
        if (!self::canAccessIssue($issue_id, $usr_id)) {
            return false;
        }
        $prj_id = Auth::getCurrentProject();
        if (User::isPartner($usr_id)) {
            $partner = Partner::canUserAccessIssueSection($usr_id, 'drafts');
            if (is_bool($partner)) {
                return $partner;
            }
        }
        if (User::getRoleByUser($usr_id, $prj_id) > User::ROLE_CUSTOMER) {
            return true;
        }

        return false;
    }

    public static function canViewHistory($issue_id, $usr_id)
    {
        if (!self::canAccessIssue($issue_id, $usr_id)) {
            return false;
        }

        if (User::isPartner($usr_id)) {
            $partner = Partner::canUserAccessIssueSection($usr_id, 'history');
            if (is_bool($partner)) {
                return $partner;
            }
        }

        return true;
    }

    public static function canViewNotificationList($issue_id, $usr_id)
    {
        if (!self::canAccessIssue($issue_id, $usr_id)) {
            return false;
        }
        $prj_id = Auth::getCurrentProject();
        if (User::isPartner($usr_id)) {
            $partner = Partner::canUserAccessIssueSection($usr_id, 'notification_list');
            if (is_bool($partner)) {
                return $partner;
            }
        }
        if (User::getRoleByUser($usr_id, $prj_id) > User::ROLE_CUSTOMER) {
            return true;
        }

        return false;
    }

    public static function canViewAuthorizedRepliers($issue_id, $usr_id)
    {
        if (!self::canAccessIssue($issue_id, $usr_id)) {
            return false;
        }
        $prj_id = Auth::getCurrentProject();
        if (User::isPartner($usr_id)) {
            $partner = Partner::canUserAccessIssueSection($usr_id, 'authorized_repliers');
            if (is_bool($partner)) {
                return $partner;
            }
        }
        if (User::getRoleByUser($usr_id, $prj_id) > User::ROLE_CUSTOMER) {
            return true;
        }

        return false;
    }

    public static function canChangeReporter($issue_id, $usr_id)
    {
        if (!self::canAccessIssue($issue_id, $usr_id)) {
            return false;
        }
        $prj_id = Auth::getCurrentProject();
        if (User::isPartner($usr_id)) {
            $partner = Partner::canUserAccessIssueSection($usr_id, 'change_reporter');
            if (is_bool($partner)) {
                return $partner;
            }
        }
        if (User::getRoleByUser($usr_id, $prj_id) > User::ROLE_CUSTOMER) {
            return true;
        }

        return false;
    }

    public static function canChangeStatus($issue_id, $usr_id)
    {
        if (!self::canAccessIssue($issue_id, $usr_id)) {
            return false;
        }

        if (User::isPartner($usr_id)) {
            $partner = Partner::canUserAccessIssueSection($usr_id, 'change_status');
            if (is_bool($partner)) {
                return $partner;
            }
        }

        return self::canUpdateIssue($issue_id, $usr_id);
    }

    public static function canConvertNote($issue_id, $usr_id)
    {
        if (!self::canAccessIssue($issue_id, $usr_id)) {
            return false;
        }

        if (User::isPartner($usr_id)) {
            $partner = Partner::canUserAccessIssueSection($usr_id, 'convert_note');
            if (is_bool($partner)) {
                return $partner;
            }
        }

        return self::canUpdateIssue($issue_id, $usr_id);
    }

    public static function canCloneIssue($issue_id, $usr_id)
    {
        if (!self::canAccessIssue($issue_id, $usr_id)) {
            return false;
        }

        $prj_id = Issue::getProjectID($issue_id);
        $workflow = Workflow::canCloneIssue($prj_id, $issue_id, $usr_id);
        if ($workflow !== null) {
            return $workflow;
        }

        if (User::isPartner($usr_id)) {
            $partner = Partner::canUserAccessIssueSection($usr_id, 'clone_issue');
            if (is_bool($partner)) {
                return $partner;
            }
        }

        if (User::getRoleByUser($usr_id, $prj_id) >= User::ROLE_USER) {
            return true;
        }

        return false;
    }

    public static function canChangeAccessLevel($issue_id, $usr_id)
    {
        if (!self::canAccessIssue($issue_id, $usr_id)) {
            return false;
        }

        $prj_id = Issue::getProjectID($issue_id);
        $workflow = Workflow::canChangeAccessLevel($prj_id, $issue_id, $usr_id);
        if ($workflow !== null) {
            return $workflow;
        }

        if (User::getRoleByUser($usr_id, $prj_id) >= User::ROLE_MANAGER) {
            return true;
        }

        return false;
    }

    /**
     * @param int $issue_id
     * @param int $usr_id
     */
    public static function getIssueAccessArray($issue_id, $usr_id)
    {
        return [
            'files' => self::canViewAttachedFiles($issue_id, $usr_id),
            'drafts' => self::canViewDrafts($issue_id, $usr_id),
            'notes' => self::canViewInternalNotes($issue_id, $usr_id),
            'partners' => self::canViewIssuePartners($issue_id, $usr_id),
            'phone' => self::canViewPhoneCalls($issue_id, $usr_id),
            'time' => self::canViewTimeTracking($issue_id, $usr_id),
            'checkins' => self::canViewCheckins($issue_id, $usr_id),
            'history' => self::canViewHistory($issue_id, $usr_id),
            'notification_list' => self::canViewNotificationList($issue_id, $usr_id),
            'authorized_repliers' => self::canViewAuthorizedRepliers($issue_id, $usr_id),
            'change_reporter' => self::canChangeReporter($issue_id, $usr_id),
            'change_status' => self::canChangeStatus($issue_id, $usr_id),
            'convert_note' => self::canConvertNote($issue_id, $usr_id),
            'update' => self::canUpdateIssue($issue_id, $usr_id),
            'clone_issue' => self::canCloneIssue($issue_id, $usr_id),
            'change_access' => self::canChangeAccessLevel($issue_id, $usr_id),
            'change_assignee' => self::canChangeAssignee($issue_id, $usr_id),
        ];
    }

    public static function canUpdateIssue($issue_id, $usr_id)
    {
        if (!self::canAccessIssue($issue_id, $usr_id)) {
            return false;
        }

        $prj_id = Issue::getProjectID($issue_id);
        $workflow = Workflow::canUpdateIssue($prj_id, $issue_id, $usr_id);
        if ($workflow !== null) {
            return $workflow;
        }

        if (User::isPartner($usr_id)) {
            $partner = Partner::canUpdateIssue($issue_id, $usr_id);
            if (is_bool($partner)) {
                return $partner;
            }
        }

        if (User::getRoleByUser($usr_id, $prj_id) >= User::ROLE_CUSTOMER) {
            return true;
        }

        return false;
    }

    public static function canChangeAssignee($issue_id, $usr_id)
    {
        if (!self::canAccessIssue($issue_id, $usr_id)) {
            return false;
        }

        $prj_id = Issue::getProjectID($issue_id);
        $workflow = Workflow::canChangeAssignee($prj_id, $issue_id, $usr_id);
        if ($workflow !== null) {
            return $workflow;
        }

        if (User::isPartner($usr_id)) {
            return false;
        }

        if (User::getRoleByUser($usr_id, $prj_id) >= User::ROLE_CUSTOMER) {
            return true;
        }

        return false;
    }

    public static function canCreateIssue($usr_id)
    {
        $prj_id = Auth::getCurrentProject();
        if (User::getRoleByUser($usr_id, $prj_id) < User::ROLE_REPORTER) {
            return false;
        }
        if (User::isPartner($usr_id)) {
            $partner = Partner::canUserAccessFeature($usr_id, 'create_issue');
            if (is_bool($partner)) {
                return $partner;
            }
        }

        return true;
    }

    public static function canAccessAssociateEmails($usr_id)
    {
        $prj_id = Auth::getCurrentProject();
        if (User::isPartner($usr_id)) {
            $partner = Partner::canUserAccessFeature($usr_id, 'associate_emails');
            if (is_bool($partner)) {
                return $partner;
            }
        }
        if (User::getRoleByUser($usr_id, $prj_id) > User::ROLE_CUSTOMER) {
            return true;
        }

        return false;
    }

    public static function canAccessReports($usr_id)
    {
        $prj_id = Auth::getCurrentProject();
        if (User::isPartner($usr_id)) {
            $partner = Partner::canUserAccessFeature($usr_id, 'reports');
            if (is_bool($partner)) {
                return $partner;
            }
        }
        if (User::getRoleByUser($usr_id, $prj_id) > User::ROLE_CUSTOMER) {
            return true;
        }

        return false;
    }

    public static function getFeatureAccessArray($usr_id)
    {
        return [
            'create_issue' => self::canCreateIssue($usr_id),
            'associate_emails' => self::canAccessAssociateEmails($usr_id),
            'reports' => self::canAccessReports($usr_id),
            'export' => self::canExportData($usr_id),
        ];
    }

    public static function canExportData($usr_id)
    {
        if (User::isPartner($usr_id)) {
            $partner = Partner::canUserAccessFeature($usr_id, 'reports');
            if (is_bool($partner)) {
                return $partner;
            }
        }

        return true;
    }

    public static function getAccessLevels()
    {
        $prj_id = Auth::getCurrentProject();

        $levels = [
            'normal' => 'Normal',
            'assignees_only' => 'Assignees Only',
        ];

        foreach (Group::getAssocList($prj_id) as $grp_id => $group) {
            $levels['group_' . $grp_id] = 'Group: ' . $group . ' only';
        }

        $workflow = Workflow::getAccessLevels($prj_id);
        if (is_array($workflow)) {
            $levels += $workflow;
        }

        return $levels;
    }

    /**
     * @return string
     */
    public static function getAccessLevelName($level)
    {
        $access_levels = self::getAccessLevels();
        if (isset($access_levels[$level])) {
            return $access_levels[$level];
        }

        return null;
    }

    public static function getAccessList($issue_id)
    {
        $sql = 'SELECT
                    ial_usr_id
                FROM
                    `issue_access_list`
                WHERE
                    ial_iss_id = ?';
        try {
            return DB_Helper::getInstance()->getColumn($sql, [$issue_id]);
        } catch (DatabaseException $e) {
            return [];
        }
    }

    /**
     * @param int $issue_id
     */
    public static function addUserToIssue($issue_id, $usr_id)
    {
        $sql = 'INSERT INTO
                    `issue_access_list`
                SET
                    ial_iss_id = ?,
                    ial_usr_id = ?,
                    ial_created = ?';
        try {
            DB_Helper::getInstance()->query($sql, [$issue_id, $usr_id, Date_Helper::getCurrentDateGMT()]);
            History::add($issue_id, Auth::getUserID(), 'access_list_added', 'Access list entry ({target_user}) added by {user}', [
                'target_user' => User::getFullName($usr_id),
                'user' => User::getFullName(Auth::getUserID()),
            ]);
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * @param int $issue_id
     */
    public static function removeUserFromIssue($issue_id, $usr_id)
    {
        $sql = 'DELETE FROM
                    `issue_access_list`
                WHERE
                    ial_iss_id = ? AND
                    ial_usr_id = ?';
        try {
            DB_Helper::getInstance()->query($sql, [$issue_id, $usr_id]);
            History::add($issue_id, Auth::getUserID(), 'access_list_removed', 'Access list entry ({target_user}) removed by {user}', [
                'target_user' => User::getFullName($usr_id),
                'user' => User::getFullName(Auth::getUserID()),
            ]);
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * @param int $issue_id
     * @param int $usr_id
     */
    public static function isOnAccessList($issue_id, $usr_id)
    {
        if (in_array($usr_id, self::getAccessList($issue_id))) {
            return true;
        }

        return false;
    }

    /**
     * @param int $prj_id
     */
    public static function getListingSQL($prj_id)
    {
        $sql = '';
        if (Auth::getCurrentRole() < User::ROLE_MANAGER) {
            $sql .= " AND
                        (
                            iss_access_level = 'normal' OR
                            ial_id IS NOT NULL OR
                            (
                                SUBSTR(iss_access_level, 1, 6) = 'group_' AND ugr_grp_id = SUBSTR(iss_access_level, 7)
                            ) OR
                            (
                                isu_usr_id = " . Auth::getUserID() . '
                            )';

            $workflow = Workflow::getAdditionalAccessSQL($prj_id, Auth::getUserID());
            if ($workflow !== null) {
                $sql .= $workflow;
            }

            $sql .= ')';
        }

        return $sql;
    }

    /**
     * @param int $issue_id
     * @param int $usr_id
     */
    public static function log($return, $issue_id, $usr_id, $item = null, $item_id = null)
    {
        if (Setup::get()->get('audit_trail') !== 'enabled') {
            return $return;
        }

        if (is_null($item) && is_null($item_id) && isset($_SERVER['REQUEST_URI'])) {
            list($item, $item_id) = self::extractInfoFromURL($_SERVER['REQUEST_URI']);
        }
        $sql = 'INSERT INTO
                    `issue_access_log`
                SET
                    alg_iss_id = ?,
                    alg_usr_id = ?,
                    alg_created = ?,
                    alg_ip_address = ?,
                    alg_failed = ?,
                    alg_item = ?,
                    alg_item_id = ?,
                    alg_url = ?';
        $params = [
            $issue_id,
            $usr_id,
            Date_Helper::getCurrentDateGMT(),
            $_SERVER['REMOTE_ADDR'] ?? null,
            (int) !$return,
            $item,
            $item_id,
            $_SERVER['REQUEST_URI'] ?? null,
        ];
        try {
            DB_Helper::getInstance()->query($sql, $params);
        } catch (DatabaseException $e) {
            // do nothing besides log it
        }

        return $return;
    }

    private static function extractInfoFromURL($url)
    {
        if (preg_match("/view_note\.php\?id=(?P<item_id>\d+)/", $url, $matches)) {
            return ['note', $matches[1]];
        } elseif (preg_match("/view_email\.php\?ema_id=\d+&id=(?P<item_id>\d+)/", $url, $matches)) {
            return ['email', $matches[1]];
        } elseif (preg_match("/download\.php\?cat=attachment&id=(?P<item_id>\d+)/", $url, $matches)) {
            return ['file', $matches[1]];
        } elseif (preg_match("/update\.php/", $url, $matches)) {
            return ['update', null];
        }

        return [null, null];
    }
}
