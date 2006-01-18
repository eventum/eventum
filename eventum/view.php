<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005, 2006 MySQL AB                        |
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
// +----------------------------------------------------------------------+
//
// @(#) $Id: s.view.php 1.27 04/01/23 03:42:02-00:00 jpradomaia $
//
include_once("config.inc.php");
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.issue.php");
include_once(APP_INC_PATH . "class.time_tracking.php");
include_once(APP_INC_PATH . "class.note.php");
include_once(APP_INC_PATH . "class.impact_analysis.php");
include_once(APP_INC_PATH . "class.support.php");
include_once(APP_INC_PATH . "class.notification.php");
include_once(APP_INC_PATH . "class.attachment.php");
include_once(APP_INC_PATH . "class.custom_field.php");
include_once(APP_INC_PATH . "class.phone_support.php");
include_once(APP_INC_PATH . "class.scm.php");
include_once(APP_INC_PATH . "class.draft.php");
include_once(APP_INC_PATH . "db_access.php");

$tpl = new Template_API();
$tpl->setTemplate("view.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

$prj_id = Auth::getCurrentProject();
$usr_id = Auth::getUserID();
$role_id = Auth::getCurrentRole();

$associated_projects = @array_keys(Project::getAssocList($usr_id));

@$issue_id = $HTTP_POST_VARS["issue_id"] ? $HTTP_POST_VARS["issue_id"] : $HTTP_GET_VARS["id"];
$tpl->assign("extra_title", "Issue #$issue_id Details");

// check if the requested issue is a part of the 'current' project. If it doesn't
// check if issue exists in another project and if it does, switch projects
$iss_prj_id = Issue::getProjectID($issue_id);
$auto_switched_from = false;
if ((!empty($iss_prj_id)) && ($iss_prj_id != $prj_id) && (in_array($iss_prj_id, $associated_projects))) {
    $cookie = Auth::getCookieInfo(APP_PROJECT_COOKIE);
    Auth::setCurrentProject($iss_prj_id, $cookie["remember"], true);
    $auto_switched_from = $iss_prj_id;
    $prj_id = $iss_prj_id;
}

$details = Issue::getDetails($issue_id);
$tpl->assign("issue", $details);

// in the case of a customer user, also need to check if that customer has access to this issue
if (($role_id == User::getRoleID('customer')) && (User::getCustomerID($usr_id) != $details['iss_customer_id'])) {
    $tpl->assign("auth_customer", 'denied');
} elseif (!Issue::canAccess($issue_id, $usr_id)) {
    $tpl->assign("auth_user", 'denied');
} else {
    $associated_projects = @array_keys(Project::getAssocList($usr_id));
    if ((empty($details)) || ($details['iss_prj_id'] != $prj_id)) {
        $tpl->assign('issue', '');
    } else {
        // check if the requested issue is a part of one of the projects
        // associated with this user
        if (!@in_array($details['iss_prj_id'], $associated_projects)) {
            $tpl->assign("auth_customer", 'denied');
        } else {
            $options = Issue::saveSearchParams();
            $sides = Issue::getSides($issue_id, $options);

            // check if scheduled release should be displayed
            $releases = Release::getAssocList($prj_id);
            if (count($releases) > 0) {
                $show_releases = 1;
            } else {
                $show_releases = 0;
            }

            // get if categories should be displayed
            $cats = Category::getList($prj_id);
            if (count($cats) > 0) {
                $show_category = 1;
            } else {
                $show_category = 0;
            }

            $cookie = Auth::getCookieInfo(APP_PROJECT_COOKIE);
            if (!empty($auto_switched_from)) {
                $tpl->assign(array(
                    "project_auto_switched" =>  1,
                    "old_project"   =>  Project::getName($auto_switched_from)
                ));
            }
            $setup = Setup::load();
            $tpl->assign("allow_unassigned_issues", @$setup["allow_unassigned_issues"]);

            $tpl->assign(array(
                'next_issue'          => @$sides['next'],
                'previous_issue'      => @$sides['previous'],
                'subscribers'         => Notification::getSubscribers($issue_id),
                'custom_fields'       => Custom_Field::getListByIssue($prj_id, $issue_id),
                'files'               => Attachment::getList($issue_id),
                'emails'              => Support::getEmailsByIssue($issue_id),
                'zones'               => Date_API::getTimezoneList(),
                'users'               => Project::getUserAssocList($prj_id, 'active', User::getRoleID('Customer')),
                'ema_id'              => Email_Account::getEmailAccount(),
                'max_attachment_size' => Attachment::getMaxAttachmentSize(),
                'show_releases'       => $show_releases,
                'show_category'       => $show_category,
                'quarantine'          => Issue::getQuarantineInfo($issue_id)
            ));

            if ($role_id != User::getRoleID('customer')) {
                if (@$_REQUEST['show_all_drafts'] == 1) {
                    $show_all_drafts = true;
                } else {
                    $show_all_drafts = false;
                }
                $time_entries = Time_Tracking::getListing($issue_id);
                $tpl->assign(array(
                    'notes'              => Note::getListing($issue_id),
                    'is_user_assigned'   => Issue::isAssignedToUser($issue_id, $usr_id),
                    'is_user_authorized' => Authorized_Replier::isUserAuthorizedReplier($issue_id, $usr_id),
                    'phone_entries'      => Phone_Support::getListing($issue_id),
                    'phone_categories'   => Phone_Support::getCategoryAssocList($prj_id),
                    'checkins'           => SCM::getCheckinList($issue_id),
                    'time_categories'    => Time_Tracking::getAssocCategories(),
                    'time_entries'       => $time_entries['list'],
                    'total_time_spent'   => $time_entries['total_time_spent'],
                    'impacts'            => Impact_Analysis::getListing($issue_id),
                    'statuses'           => Status::getAssocStatusList($prj_id, false),
                    'drafts'             => Draft::getList($issue_id, $show_all_drafts),
                    'groups'             => Group::getAssocList($prj_id)
                ));
            }
        }
    }
}

$tpl->displayTemplate();
?>