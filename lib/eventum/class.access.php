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

class Access
{
    /**
     * Method to determine if user can access a particular issue
     *
     * @param   integer $issue_id The ID of the issue.
     * @param   integer $usr_id The ID of the user
     * @return  boolean If the user can access the issue
     */
    public static function canAccessIssue($issue_id, $usr_id)
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
        } elseif ((CRM::hasCustomerIntegration($details['iss_prj_id'])) && ($usr_role == User::ROLE_CUSTOMER) &&
                ($can_access_contract === false)) {
            // check customer permissions
            $return = false;
        } elseif (!empty($usr_details['usr_par_code']) &&
                        !Partner::isPartnerEnabledForIssue($usr_details['usr_par_code'], $issue_id)) {
            // check if the user is a partner
            $return = false;
        } elseif ($details['iss_private'] == 1) {
            // check if the issue is even private

            // check role, reporter, assignment and group
            if ($usr_role > User::ROLE_DEVELOPER) {
                $return = true;
            } elseif ($details['iss_usr_id'] == $usr_id) {
                $return = true;
            } elseif (Issue::isAssignedToUser($issue_id, $usr_id)) {
                $return = true;
            } elseif ((!empty($details['iss_grp_id'])) && (in_array($details['iss_grp_id'], $usr_details['group_ids']))) {
                $return = true;
            } elseif (Authorized_Replier::isUserAuthorizedReplier($issue_id, $usr_id)) {
                $return = true;
            } else {
                $return = false;
            }
        } elseif ((Auth::getCurrentRole() == User::ROLE_REPORTER) && (Project::getSegregateReporters($prj_id)) &&
                ($details['iss_usr_id'] != $usr_id) && (!Authorized_Replier::isUserAuthorizedReplier($issue_id, $usr_id))) {
            return false;
        } else {
            $return = true;
        }

        $access[$issue_id . '-' . $usr_id] = $return;

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
        if (User::getRoleByUser($usr_id, $prj_id) >= User::ROLE_CUSTOMER) {
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

    public static function canViewInternalNotes($issue_id, $usr_id)
    {
        if (!self::canAccessIssue($issue_id, $usr_id)) {
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

    public static function getIssueAccessArray($issue_id, $usr_id)
    {
        return array(
            'files'     =>  self::canViewAttachedFiles($issue_id, $usr_id),
            'drafts'    =>  self::canViewDrafts($issue_id, $usr_id),
            'notes'     =>  self::canViewInternalNotes($issue_id, $usr_id),
            'partners'  =>  self::canViewIssuePartners($issue_id, $usr_id),
            'phone'     =>  self::canViewPhoneCalls($issue_id, $usr_id),
            'time'      =>  self::canViewTimeTracking($issue_id, $usr_id),
            'history'   =>  self::canViewHistory($issue_id, $usr_id),
            'notification_list' =>  self::canViewNotificationList($issue_id, $usr_id),
            'authorized_repliers'   =>  self::canViewAuthorizedRepliers($issue_id, $usr_id),
            'change_reporter'   =>  self::canChangeReporter($issue_id, $usr_id),
            'change_status' =>  self::canChangeStatus($issue_id, $usr_id),
            'convert_note'  =>  self::canConvertNote($issue_id, $usr_id),
            'update'    =>  self::canUpdateIssue($issue_id, $usr_id),
            'clone_issue'   =>  self::canCloneIssue($issue_id, $usr_id),
        );
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
        return array(
            'create_issue'  =>  self::canCreateIssue($usr_id),
            'associate_emails'  =>  self::canAccessAssociateEmails($usr_id),
            'reports'       =>  self::canAccessReports($usr_id),
            'export'        =>  self::canExportData($usr_id),
        );
    }

    public static function canExportData($usr_id)
    {
        $prj_id = Auth::getCurrentProject();
        if (User::isPartner($usr_id)) {
            $partner = Partner::canUserAccessFeature($usr_id, 'reports');
            if (is_bool($partner)) {
                return $partner;
            }
        }

        return true;
    }
}
