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
// | 51 Franklin Street, Suite 330                                          |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// +----------------------------------------------------------------------+

require_once dirname(__FILE__) . '/../init.php';

$prj_id = Auth::getCurrentProject();
$usr_id = Auth::getUserID();
$role_id = Auth::getCurrentRole();

$associated_projects = @array_keys(Project::getAssocList($usr_id));

$tpl = new Template_Helper();
$tpl->setTemplate('update.tpl.html');
$tpl->assign('user_prefs', Prefs::get($usr_id));

Auth::checkAuthentication(APP_COOKIE);

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
if ((!empty($iss_prj_id)) && ($iss_prj_id != $prj_id) && (in_array($iss_prj_id, $associated_projects))) {
    $cookie = Auth::getCookieInfo(APP_PROJECT_COOKIE);
    Auth::setCurrentProject($iss_prj_id, $cookie['remember'], true);
    $auto_switched_from = $iss_prj_id;
    $prj_id = $iss_prj_id;
    Misc::setMessage(ev_gettext('Note: Project automatically switched to "%1$s" from "%2$s".',
                                Auth::getCurrentProjectName(), Project::getName($iss_prj_id)));
}

$tpl->assign('issue', $details);
$tpl->assign('extra_title', ev_gettext('Update Issue #%1$s', $issue_id));

// in the case of a customer user, also need to check if that customer has access to this issue
if (($role_id == User::getRoleID('customer')) && ((empty($details)) || (User::getCustomerID($usr_id) != $details['iss_customer_id'])) ||
        !Issue::canAccess($issue_id, $usr_id) ||
        !($role_id > User::getRoleID('Reporter')) || !Issue::canUpdate($issue_id, $usr_id)) {
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

    $setup = Setup::load();
    $tpl->assign('allow_unassigned_issues', @$setup['allow_unassigned_issues']);

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
    $tpl->assign(array(
        'subscribers'  => Notification::getSubscribers($issue_id),
        'categories'   => Category::getAssocList($prj_id),
        'priorities'   => Priority::getAssocList($prj_id),
        'severities'   => Severity::getAssocList($prj_id),
        'status'       => $statuses,
        'releases'     => $releases,
        'resolutions'  => Resolution::getAssocList(),
        'users'        => Project::getUserAssocList($prj_id, 'active', User::getRoleID('Customer')),
        'one_week_ts'  => time() + (7 * Date_Helper::DAY),
        'allow_unassigned_issues'   =>  @$setup['allow_unassigned_issues'],
        'groups'       => Group::getAssocList($prj_id),
        'current_year' =>   date('Y'),
        'products'     => Product::getList(false),
    ));

$tpl->assign('usr_role_id', User::getRoleByUser($usr_id, $prj_id));
$tpl->displayTemplate();
