<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005 MySQL AB                              |
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
// @(#) $Id: s.reminders.php 1.3 04/01/19 15:15:25-00:00 jpradomaia $
//
include_once("../config.inc.php");
include_once(APP_INC_PATH . "db_access.php");
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.user.php");
include_once(APP_INC_PATH . "class.project.php");
include_once(APP_INC_PATH . "class.priority.php");
include_once(APP_INC_PATH . "class.reminder.php");
include_once(APP_INC_PATH . "class.issue.php");

$tpl = new Template_API();
$tpl->setTemplate("manage/index.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

$tpl->assign("type", "reminders");

$role_id = Auth::getCurrentRole();
if (($role_id == User::getRoleID('administrator')) || ($role_id == User::getRoleID('manager'))) {
    if ($role_id == User::getRoleID('administrator')) {
        $tpl->assign("show_setup_links", true);
    }

    if (@$HTTP_POST_VARS["cat"] == "new") {
        $tpl->assign("result", Reminder::insert());
    } elseif (@$HTTP_POST_VARS["cat"] == "update") {
        $tpl->assign("result", Reminder::update());
    } elseif (@$HTTP_POST_VARS["cat"] == "delete") {
        Reminder::remove();
    }

    if (@$HTTP_GET_VARS["cat"] == "edit") {
        $info = Reminder::getDetails($HTTP_GET_VARS["id"]);
        if (!empty($HTTP_GET_VARS['prj_id'])) {
            $info['rem_prj_id'] = $HTTP_GET_VARS['prj_id'];
        }
        // only show customers and support levels if the selected project really needs it
        $project_has_customer_integration = Customer::hasCustomerIntegration($info['rem_prj_id']);
        $tpl->assign("project_has_customer_integration", $project_has_customer_integration);
        if ($project_has_customer_integration) {
            $tpl->assign("customers", Customer::getAssocList($info['rem_prj_id']));
            $backend_uses_support_levels = Customer::doesBackendUseSupportLevels($info['rem_prj_id']);
            if ($backend_uses_support_levels) {
                $tpl->assign("support_levels", Customer::getSupportLevelAssocList($info['rem_prj_id']));
            }
            $tpl->assign("backend_uses_support_levels", $backend_uses_support_levels);
        }
        $tpl->assign('issues', Reminder::getIssueAssocListByProject($info['rem_prj_id']));
        $tpl->assign("info", $info);
        // wouldn't make much sense to create a reminder for a 'Not Prioritized' 
        // issue, so let's remove that as an option
        $priorities = array_flip(Priority::getAssocList($info['rem_prj_id']));
        unset($priorities['Not Prioritized']);
        $tpl->assign("priorities", array_flip($priorities));
    } elseif (@$HTTP_GET_VARS["cat"] == "change_rank") {
        Reminder::changeRank($HTTP_GET_VARS['id'], $HTTP_GET_VARS['rank']);
    } elseif (!empty($HTTP_GET_VARS['prj_id'])) {
        $tpl->assign("info", array('rem_prj_id' => $HTTP_GET_VARS['prj_id']));
        $tpl->assign('issues', Reminder::getIssueAssocListByProject($HTTP_GET_VARS['prj_id']));
        // wouldn't make much sense to create a reminder for a 'Not Prioritized' 
        // issue, so let's remove that as an option
        $priorities = array_flip(Priority::getAssocList($HTTP_GET_VARS['prj_id']));
        unset($priorities['Not Prioritized']);
        $tpl->assign("priorities", array_flip($priorities));
        // only show customers and support levels if the selected project really needs it
        $project_has_customer_integration = Customer::hasCustomerIntegration($HTTP_GET_VARS['prj_id']);
        $tpl->assign("project_has_customer_integration", $project_has_customer_integration);
        if ($project_has_customer_integration) {
            $tpl->assign("customers", Customer::getAssocList($HTTP_GET_VARS['prj_id']));
            $backend_uses_support_levels = Customer::doesBackendUseSupportLevels($HTTP_GET_VARS['prj_id']);
            if ($backend_uses_support_levels) {
                $tpl->assign("support_levels", Customer::getSupportLevelAssocList($HTTP_GET_VARS['prj_id']));
            }
            $tpl->assign("backend_uses_support_levels", $backend_uses_support_levels);
        }
    }

    $tpl->assign("project_list", Project::getAll());
    $tpl->assign("list", Reminder::getAdminList());
} else {
    $tpl->assign("show_not_allowed_msg", true);
}

$tpl->displayTemplate();
?>