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
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+

require_once __DIR__ . '/../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate('view.tpl.html');

Auth::checkAuthentication();

$prj_id = Auth::getCurrentProject();
$usr_id = Auth::getUserID();
$role_id = Auth::getCurrentRole();

$associated_projects = @array_keys(Project::getAssocList($usr_id));

@$issue_id = $_POST['issue_id'] ? $_POST['issue_id'] : $_GET['id'];
$tpl->assign('issue_id', $issue_id);

// check if the requested issue is a part of the 'current' project. If it doesn't
// check if issue exists in another project and if it does, switch projects
$iss_prj_id = Issue::getProjectID($issue_id);
$auto_switched_from = false;
if ((!empty($iss_prj_id)) && ($iss_prj_id != $prj_id) && (in_array($iss_prj_id, $associated_projects))) {
    AuthCookie::setProjectCookie($iss_prj_id);
    $auto_switched_from = $prj_id;
    $prj_id = $iss_prj_id;
}

$details = Issue::getDetails($issue_id);
if ($details == '') {
    Misc::displayErrorMessage(ev_gettext('Error: The issue #%1$s could not be found.', $issue_id));
}

// TRANSLATORS: %1 = issue id
$tpl->assign('issue', $details);

// in the case of a customer user, also need to check if that customer has access to this issue
if (!Issue::canAccess($issue_id, $usr_id)) {
    Misc::displayErrorMessage(ev_gettext('Sorry, you do not have the required privileges to view this issue.'));
} else {

    // if the issue has a different customer then the currently selected one, switch customers
    if (Auth::getCurrentRole() == User::ROLE_CUSTOMER && Auth::getCurrentCustomerID() != $details['iss_customer_id']) {
        Auth::setCurrentCustomerID($details['iss_customer_id']);
        Misc::setMessage("Active customer changed to '" . $details['customer']->getName() . '"');
        Auth::redirect(APP_RELATIVE_URL . 'view.php?id=' . $issue_id);
    }

    $associated_projects = @array_keys(Project::getAssocList($usr_id));
    if ((empty($details)) || ($details['iss_prj_id'] != $prj_id)) {
        Misc::displayErrorMessage(ev_gettext('Error: The issue #%1$s could not be found.', $issue_id));
    } else {
        // now that we can access to the issue, add more verbose HTML <title>
        // TRANSLATORS: Page HTML title: %1 = issue id, %2 = issue summary
        $tpl->assign('extra_title', ev_gettext('#%1$s - %2$s', $issue_id, $details['iss_summary']));

        // check if the requested issue is a part of one of the projects
        // associated with this user
        if (!@in_array($details['iss_prj_id'], $associated_projects)) {
            Misc::displayErrorMessage(ev_gettext('Sorry, you do not have the required privileges to view this issue.'));
        } else {
            $options = Search::saveSearchParams();
            $sides = Issue::getSides($issue_id, $options);

            // FIXME: this $cookie seems unused
            $cookie = AuthCookie::getProjectCookie();
            if (!empty($auto_switched_from)) {
                $tpl->assign(array(
                    'project_auto_switched' =>  1,
                    'old_project'   =>  Project::getName($auto_switched_from),
                ));
            }
            $issue_fields_display = Issue_Field::getFieldsToDisplay($issue_id, 'view_issue');

            // figure out what data to show in each column
            $columns = array(0 => array(), 1 => array());
            if (CRM::hasCustomerIntegration($prj_id) and !empty($details['iss_customer_id'])) {
                $columns[0][] = array(
                    'title' =>  'Customer',
                    'field' =>  'customer_0'
                );
                $columns[1][] = array(
                    'title' =>  'Customer Contract',
                    'field' =>  'customer_1'
                );
            }
            $cats = Category::getList($prj_id);
            if (count($cats) > 0) {
                $columns[0][] = array(
                    'title' =>  ev_gettext('Category'),
                    'data'  =>  $details['prc_title'],
                    'field' =>  'category',
                );
            }
            $columns[0][] = array(
                'title' =>  ev_gettext('Status'),
                'data'  =>  $details['sta_title'],
                'data_bgcolor'  =>  $details['status_color'],
                'field' =>  'status',
            );

            $severities = Severity::getList($prj_id);
            if (count($severities) > 0) {
                $columns[0][] = array(
                    'title' =>  ev_gettext('Severity'),
                    'data'  =>  $details['sev_title'],
                    'field' =>  'severity'
                );
            }

            if ((!isset($issue_fields_display['priority'])) ||
                ($issue_fields_display['priority'] != false)) {
                if ((isset($issue_fields_display['priority']['min_role'])) &&
                    ($issue_fields_display['priority']['min_role'] > User::ROLE_CUSTOMER)) {
                    $bgcolor = APP_INTERNAL_COLOR;
                } else {
                    $bgcolor = '';
                }
                $columns[0][] = array(
                    'title' =>  ev_gettext('Priority'),
                    'data'  =>  $details['pri_title'],
                    'title_bgcolor'  =>  $bgcolor,
                    'field' =>  'priority',
                );
            }
            $releases = Release::getAssocList($prj_id);
            if ((count($releases) > 0) && ($role_id != User::ROLE_CUSTOMER)) {
                $columns[0][] = array(
                    'title' =>  ev_gettext('Scheduled Release'),
                    'data'  =>  $details['pre_title'],
                    'title_bgcolor' =>  APP_INTERNAL_COLOR,
                );
            }
            $columns[0][] = array(
                'title' =>  ev_gettext('Resolution'),
                'data'  =>  $details['iss_resolution'],
                'field' =>  'resolution',
            );

            if ((!isset($issue_fields_display['percent_complete'])) ||
                ($issue_fields_display['percent_complete'] != false)) {
                $columns[0][] = array(
                    'title' =>  ev_gettext('Percentage Complete'),
                    'data'  =>  (empty($details['iss_percent_complete']) ? 0 : $details['iss_percent_complete']) . '%',
                    'field' =>  'percentage_complete',
                );
            }
            $columns[0][] = array(
                'title' =>  ev_gettext('Reporter'),
                'field' =>  'reporter',
            );
            $products = Product::getAssocList(false);
            if (count($products) > 0) {
                $columns[0][] = array(
                    'title' =>  ev_gettext('Product'),
                    'field' =>  'product',
                );
                $columns[0][] = array(
                    'title' =>  ev_gettext('Product Version'),
                    'field' =>  'product_version',
                );
            }
            $columns[0][] = array(
                'title' =>  ev_gettext('Assignment'),
                'data'  =>  $details['assignments'],
                'field' =>  'assignment',
            );

            $columns[1][] = array(
                'title' =>  ev_gettext('Notification List'),
                'field' =>  'notification_list',
            );
            $columns[1][] = array(
                'title' =>  ev_gettext('Submitted Date'),
                'data'  =>  $details['iss_created_date'],
            );
            $columns[1][] = array(
                'title' =>  ev_gettext('Last Updated Date'),
                'data'  =>  $details['iss_updated_date'],
            );
            $columns[1][] = array(
                'title' =>  ev_gettext('Associated Issues'),
                'field' =>  'associated_issues',
            );
            if ((!isset($issue_fields_display['expected_resolution'])) ||
                ($issue_fields_display['expected_resolution'] != false)) {
                $columns[1][] = array(
                    'title' =>  ev_gettext('Expected Resolution Date'),
                    'field' =>  'expected_resolution',
                );
            }
            if ((!isset($issue_fields_display['estimated_dev_time'])) ||
                ($issue_fields_display['estimated_dev_time'] != false)) {
                $columns[1][] = array(
                    'title' =>  ev_gettext('Estimated Dev. Time'),
                    'data'  =>  empty($details['iss_dev_time']) ? '' : $details['iss_dev_time'] . ' hours',
                    'field' =>  'estimated_dev_time',
                );
            }
            if ($role_id > User::ROLE_CUSTOMER) {
                $columns[1][] = array(
                    'title' =>  ev_gettext('Duplicates'),
                    'field' =>  'duplicates',
                    'title_bgcolor' =>  APP_INTERNAL_COLOR,
                );
                $columns[1][] = array(
                    'title' =>  ev_gettext('Authorized Repliers'),
                    'field' =>  'authorized_repliers',
                    'title_bgcolor' =>  APP_INTERNAL_COLOR,
                );
            }
            $groups = Group::getAssocList($prj_id);
            if (($role_id > User::ROLE_CUSTOMER) && (count($groups) > 0)) {
                $columns[1][] = array(
                    'title' =>  ev_gettext('Group'),
                    'data' =>  isset($details['group']) ? $details['group']['grp_name'] : '',
                    'title_bgcolor' =>  APP_INTERNAL_COLOR,
                );
            }

            $tpl->assign(array(
                'next_issue'          => @$sides['next'],
                'previous_issue'      => @$sides['previous'],
                'subscribers'         => Notification::getSubscribers($issue_id),
                'custom_fields'       => Custom_Field::getListByIssue($prj_id, $issue_id),
                'files'               => Attachment::getList($issue_id),
                'emails'              => Support::getEmailsByIssue($issue_id),
                'zones'               => Date_Helper::getTimezoneList(),
                'users'               => Project::getUserAssocList($prj_id, 'active', User::ROLE_CUSTOMER),
                'ema_id'              => Email_Account::getEmailAccount(),
                'max_attachment_size' => Attachment::getMaxAttachmentSize(),
                'quarantine'          => Issue::getQuarantineInfo($issue_id),
                'grid'                => $columns,
                'can_update'          => Issue::canUpdate($issue_id, $usr_id),
                'enabled_partners'    => Partner::getPartnersByProject($prj_id),
                'partners'            => Partner::getPartnersByIssue($issue_id),
                'issue_access'        => Access::getIssueAccessArray($issue_id, $usr_id),
                'is_user_notified'   => Notification::isUserNotified($issue_id, $usr_id),
            ));

            if ($role_id != User::ROLE_CUSTOMER) {
                if (@$_COOKIE['show_all_drafts'] == 1) {
                    $show_all_drafts = true;
                } else {
                    $show_all_drafts = false;
                }

                if (Workflow::hasWorkflowIntegration($prj_id)) {
                    $statuses = Workflow::getAllowedStatuses($prj_id, $issue_id);
                    // if currently selected release is not on list, go ahead and add it.
                } else {
                    $statuses = Status::getAssocStatusList($prj_id, false);
                }
                if ((!empty($details['iss_sta_id'])) && (empty($statuses[$details['iss_sta_id']]))) {
                    $statuses[$details['iss_sta_id']] = Status::getStatusTitle($details['iss_sta_id']);
                }

                $time_entries = Time_Tracking::getTimeEntryListing($issue_id);
                $tpl->assign(array(
                    'notes'              => Note::getListing($issue_id),
                    'is_user_assigned'   => Issue::isAssignedToUser($issue_id, $usr_id),
                    'is_user_authorized' => Authorized_Replier::isUserAuthorizedReplier($issue_id, $usr_id),
                    'phone_entries'      => Phone_Support::getListing($issue_id),
                    'phone_categories'   => Phone_Support::getCategoryAssocList($prj_id),
                    'checkins'           => SCM::getCheckinList($issue_id),
                    'time_categories'    => Time_Tracking::getAssocCategories($prj_id),
                    'time_entries'       => $time_entries['list'],
                    'total_time_by_user' => $time_entries['total_time_by_user'],
                    'total_time_spent'   => $time_entries['total_time_spent'],
                    'impacts'            => Impact_Analysis::getListing($issue_id),
                    'statuses'           => $statuses,
                    'drafts'             => Draft::getList($issue_id, $show_all_drafts),
                    'groups'             => Group::getAssocList($prj_id),
                ));
            }
        }
    }
}

$tpl->displayTemplate();
