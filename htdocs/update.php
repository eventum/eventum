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
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// +----------------------------------------------------------------------+

require_once __DIR__ . '/../init.php';

$prj_id = Auth::getCurrentProject();
$usr_id = Auth::getUserID();
$role_id = Auth::getCurrentRole();

$associated_projects = @array_keys(Project::getAssocList($usr_id));

$tpl = new Template_Helper();
$tpl->setTemplate('update.tpl.html');
$tpl->assign('user_prefs', Prefs::get($usr_id));

Auth::checkAuthentication();

$issue_id = @$_POST['issue_id'] ? $_POST['issue_id'] : @$_GET['id'];
$tpl->assign('issue_id', $issue_id);
$details = Issue::getDetails($issue_id);
if ($details == '') {
    Misc::setMessage(ev_gettext('Error: The issue #%1$s could not be found.', $issue_id), Misc::MSG_ERROR);
    $tpl->displayTemplate();
    exit;
}

Workflow::prePage($prj_id, 'update');

// check if the requested issue is a part of the 'current' project. If it doesn't
// check if issue exists in another project and if it does, switch projects
$iss_prj_id = Issue::getProjectID($issue_id);
$auto_switched_from = false;
if (!empty($iss_prj_id) && $iss_prj_id != $prj_id && in_array($iss_prj_id, $associated_projects)) {
    AuthCookie::setProjectCookie($iss_prj_id);
    $auto_switched_from = $iss_prj_id;
    $prj_id = $iss_prj_id;
    Misc::setMessage(ev_gettext('Note: Project automatically switched to "%1$s" from "%2$s".',
                                Auth::getCurrentProjectName(), Project::getName($iss_prj_id)));
}

$tpl->assign('issue', $details);
$tpl->assign('extra_title', ev_gettext('Update Issue #%1$s', $issue_id));

// in the case of a customer user, also need to check if that customer has access to this issue
if (($role_id == User::ROLE_CUSTOMER) && ((empty($details)) || (User::getCustomerID($usr_id) != $details['iss_customer_id'])) ||
        !Issue::canAccess($issue_id, $usr_id) ||
        !($role_id > User::ROLE_REPORTER) || !Issue::canUpdate($issue_id, $usr_id)) {
    $tpl->setTemplate('base_full.tpl.html');
    Misc::setMessage(ev_gettext('Sorry, you do not have the required privileges to update this issue.'), Misc::MSG_ERROR);
    $tpl->displayTemplate();
    exit;
}

if (Issue_Lock::acquire($issue_id, $usr_id)) {
    $issue_lock = false;
} else {
    $issue_lock = Issue_Lock::getInfo($issue_id);
    $issue_lock['locker'] = User::getDetails($issue_lock['usr_id']);
    $issue_lock['expires_formatted_time'] = Date_Helper::getFormattedDate($issue_lock['expires']);
}
$tpl->assign('issue_lock', $issue_lock);

    $new_prj_id = Issue::getProjectID($issue_id);
    $cancel_update = isset($_POST['cancel']);

    if ($cancel_update) {
        // be sure not to unlock somebody else's lock
        if (!$issue_lock) {
            Issue_Lock::release($issue_id);
            Misc::setMessage(ev_gettext('Cancelled Issue #%1$s update.', $issue_id), Misc::MSG_INFO);
        }

        Auth::redirect(APP_RELATIVE_URL . 'view.php?id=' . $issue_id);
        exit;
    } elseif (@$_POST['cat'] == 'update') {
        if ($issue_lock) {
            Misc::setMessage(ev_gettext("Sorry, you can't update issue if it's locked by another user"), Misc::MSG_ERROR);
            $tpl->displayTemplate();
            exit;
        }

        $res = Issue::update($issue_id);
        Issue_Lock::release($issue_id);

        if ($res == -1) {
            Misc::setMessage(ev_gettext('Sorry, an error happened while trying to update this issue.'), Misc::MSG_ERROR);
            $tpl->displayTemplate();
            exit;
        } elseif ($res == 1) {
            Misc::setMessage(ev_gettext('Thank you, issue #%1$s was updated successfully.', $issue_id), Misc::MSG_INFO);
        }

        $notify_list = Notification::getLastNotifiedAddresses($issue_id);
        $has_duplicates = Issue::hasDuplicates($_POST['issue_id']);
        if ($has_duplicates || count($errors) > 0 || count($notify_list) > 0) {
            $update_tpl = new Template_Helper();
            $update_tpl->setTemplate('include/update_msg.tpl.html');
            $update_tpl->assign('update_result', $res);
            $update_tpl->assign('errors', $errors);
            $update_tpl->assign('notify_list', $notify_list);
            if ($has_duplicates) {
                $update_tpl->assign('has_duplicates', 'yes');
            }
            Misc::setMessage($update_tpl->getTemplateContents(false), Misc::MSG_HTML_BOX);
        }
        Auth::redirect(APP_RELATIVE_URL . 'view.php?id=' . $issue_id);
        exit;
    }

    $prj_id = Auth::getCurrentProject();

    // if currently selected release is in the past, manually add it to list
    $releases = Release::getAssocList($prj_id);
    if ($details['iss_pre_id'] != 0 && empty($releases[$details['iss_pre_id']])) {
        $releases = array($details['iss_pre_id'] => $details['pre_title']) + $releases;
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
    $categories = Category::getAssocList($prj_id);
    if (count($categories) > 0) {
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

    $severities = Severity::getAssocList($prj_id);
    if (count($severities) > 0) {
        $columns[0][] = array(
            'title' =>  ev_gettext('Severity'),
            'data'  =>  $details['sev_title'],
            'field' =>  'severity'
        );
    }

    $priorities = Priority::getAssocList($prj_id);
    if (count($priorities) > 0 && ((!isset($issue_fields_display['priority'])) ||
        ($issue_fields_display['priority'] != false))) {
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
    if ($role_id > User::ROLE_CUSTOMER) {
        $columns[0][] = array(
            'title' =>  ev_gettext('Resolution'),
            'data'  =>  $details['iss_resolution'],
            'field' =>  'resolution',
        );
    }

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
            'data'  =>  $details['iss_dev_time'] . empty($details['iss_dev_time']) ? '' : ' hours',
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
        'subscribers'  => Notification::getSubscribers($issue_id),
        'categories'   => $categories,
        'priorities'   => $priorities,
        'severities'   => $severities,
        'status'       => $statuses,
        'releases'     => $releases,
        'resolutions'  => Resolution::getAssocList(),
        'users'        => Project::getUserAssocList($prj_id, 'active', User::ROLE_CUSTOMER),
        'one_week_ts'  => time() + (7 * Date_Helper::DAY),
        'groups'       => Group::getAssocList($prj_id),
        'current_year' =>   date('Y'),
        'products'     => Product::getList(false),
        'grid'         => $columns,
    ));

$tpl->assign('usr_role_id', User::getRoleByUser($usr_id, $prj_id));
$tpl->displayTemplate();
