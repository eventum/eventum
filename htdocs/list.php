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
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// +----------------------------------------------------------------------+

require_once dirname(__FILE__) . '/../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate("list.tpl.html");

Auth::checkAuthentication(APP_COOKIE);
$usr_id = Auth::getUserID();
$prj_id = Auth::getCurrentProject();

$pagerRow = Misc::escapeInteger(Search::getParam('pagerRow'));
if (empty($pagerRow)) {
    $pagerRow = 0;
}

$rows = Search::getParam('rows');
$rows = ($rows == 'ALL') ? $rows : Misc::escapeInteger($rows);
if (empty($rows)) {
    $rows = APP_DEFAULT_PAGER_SIZE;
}

if (isset($_REQUEST['view'])) {
    if ($_REQUEST['view'] == 'my_assignments') {
        $profile = Search_Profile::getProfile($usr_id, $prj_id, 'issue');
        Search_Profile::remove($usr_id, $prj_id, 'issue');
        Auth::redirect("list.php?users=$usr_id&hide_closed=1&rows=$rows&sort_by=" .
                $profile['sort_by'] . "&sort_order=" . $profile['sort_order']);
    } elseif (($_REQUEST['view'] == 'customer') && (isset($_REQUEST['customer_id']))) {
        $profile = Search_Profile::getProfile($usr_id, $prj_id, 'issue');
        Search_Profile::remove($usr_id, $prj_id, 'issue');
        Auth::redirect("list.php?customer_id=" . Misc::escapeString($_REQUEST['customer_id']) .
                "&hide_closed=1&rows=$rows&sort_by=" . $profile['sort_by'] .
                "&sort_order=" . $profile['sort_order']);
    } elseif (($_REQUEST['view'] == 'reporter') && (isset($_REQUEST['reporter_id']))) {
        $profile = Search_Profile::getProfile($usr_id, $prj_id, 'issue');
        Search_Profile::remove($usr_id, $prj_id, 'issue');
        Auth::redirect("list.php?reporter=" . Misc::escapeInteger($_REQUEST['reporter_id']) .
                "&hide_closed=1&rows=$rows&sort_by=" . $profile['sort_by'] .
                "&sort_order=" . $profile['sort_order']);
    } elseif ($_REQUEST['view'] == 'clear') {
        Search_Profile::remove($usr_id, $prj_id, 'issue');
        Auth::redirect("list.php");
    }
}

if (!empty($_REQUEST['nosave'])) {
	$options = Search::saveSearchParams(false);
} else {
	$options = Search::saveSearchParams();
}
$tpl->assign("options", $options);
$tpl->assign("sorting", Search::getSortingInfo($options));

// generate options for assign list. If there are groups and user is above a customer, include groups
$groups = Group::getAssocList($prj_id);
$users = Project::getUserAssocList($prj_id, 'active', User::getRoleID('Customer'));
$assign_options = array(
    ""      =>  ev_gettext("Any"),
    "-1"    =>  ev_gettext("un-assigned"),
    "-2"    =>  ev_gettext("myself and un-assigned")
);
if (Auth::isAnonUser()) {
    unset($assign_options["-2"]);
} elseif (User::getGroupID($usr_id)) {
    $assign_options['-3'] = ev_gettext('myself and my group');
    $assign_options['-4'] = ev_gettext('myself, un-assigned and my group');
}
if ((count($groups) > 0) && (Auth::getCurrentRole() > User::getRoleID("Customer"))) {
    foreach ($groups as $grp_id => $grp_name) {
        $assign_options["grp:$grp_id"] = ev_gettext("Group") . ": " . $grp_name;
    }
}
$assign_options += $users;

$list = Search::getListing($prj_id, $options, $pagerRow, $rows);
$tpl->assign("list", $list["list"]);
$tpl->assign("list_info", $list["info"]);
$tpl->assign("csv_data", base64_encode(@$list["csv"]));
$tpl->assign("match_modes", Search::getMatchModes());
$tpl->assign("supports_excerpts", Search::doesBackendSupportExcerpts());

$tpl->assign("columns", Display_Column::getColumnsToDisplay($prj_id, 'list_issues'));
$tpl->assign("priorities", Priority::getAssocList($prj_id));
$tpl->assign("status", Status::getAssocStatusList($prj_id));
$tpl->assign("assign_options", $assign_options);
$tpl->assign("custom", Filter::getAssocList($prj_id));
$tpl->assign("csts", Filter::getListing(true));
$tpl->assign("active_filters", Filter::getActiveFilters($options));
$tpl->assign("categories", Category::getAssocList($prj_id));
$tpl->assign("releases", Release::getAssocList($prj_id, true));
$tpl->assign("reporters", Project::getReporters($prj_id));

$prefs = Prefs::get($usr_id);
$tpl->assign("refresh_rate", $prefs['list_refresh_rate'] * 60);
$tpl->assign("refresh_page", "list.php");

// items needed for bulk update tool
if (Auth::getCurrentRole() > User::getRoleID("Developer")) {
    $tpl->assign("users", $users);

    if (Workflow::hasWorkflowIntegration($prj_id)) {
        $open_statuses = Workflow::getAllowedStatuses($prj_id);
    } else {
        $open_statuses = Status::getAssocStatusList($prj_id, false);
    }

    $tpl->assign("open_status", $open_statuses);
    $tpl->assign("closed_status", Status::getClosedAssocList($prj_id));
    $tpl->assign("available_releases", Release::getAssocList($prj_id));
}

$tpl->displayTemplate();
