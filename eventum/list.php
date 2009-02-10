<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2009 Sun Microsystem Inc.                       |
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
// @(#) $Id: list.php 3828 2009-02-10 07:01:48Z glen $

require_once(dirname(__FILE__) . "/init.php");
require_once(APP_INC_PATH . "db_access.php");
require_once(APP_INC_PATH . "class.template.php");
require_once(APP_INC_PATH . "class.auth.php");
require_once(APP_INC_PATH . "class.category.php");
require_once(APP_INC_PATH . "class.priority.php");
require_once(APP_INC_PATH . "class.misc.php");
require_once(APP_INC_PATH . "class.release.php");
require_once(APP_INC_PATH . "class.issue.php");
require_once(APP_INC_PATH . "class.project.php");
require_once(APP_INC_PATH . "class.filter.php");
require_once(APP_INC_PATH . "class.status.php");
require_once(APP_INC_PATH . "class.user.php");
require_once(APP_INC_PATH . "class.group.php");
require_once(APP_INC_PATH . "class.display_column.php");
require_once(APP_INC_PATH . "class.search_profile.php");

$tpl = new Template_Helper();
$tpl->setTemplate("list.tpl.html");

Auth::checkAuthentication(APP_COOKIE);
$usr_id = Auth::getUserID();
$prj_id = Auth::getCurrentProject();

$pagerRow = Issue::getParam('pagerRow');
if (empty($pagerRow)) {
    $pagerRow = 0;
}
$rows = Issue::getParam('rows');
if (empty($rows)) {
    $rows = APP_DEFAULT_PAGER_SIZE;
}

if (@$_REQUEST['view'] == 'my_assignments') {
    $profile = Search_Profile::getProfile($usr_id, $prj_id, 'issue');
    Search_Profile::remove($usr_id, $prj_id, 'issue');
    Auth::redirect(APP_RELATIVE_URL . "list.php?users=$usr_id&hide_closed=1&rows=$rows&sort_by=" .
            $profile['sort_by'] . "&sort_order=" . $profile['sort_order']);
}

$options = Issue::saveSearchParams();
$tpl->assign("options", $options);
$tpl->assign("sorting", Issue::getSortingInfo($options));

// generate options for assign list. If there are groups and user is above a customer, include groups
$groups = Group::getAssocList($prj_id);
$users = Project::getUserAssocList($prj_id, 'active', User::getRoleID('Customer'));
$assign_options = array(
    ""      =>  ev_gettext("Any"),
    "-1"    =>  ev_gettext("un-assigned"),
    "-2"    =>  ev_gettext("myself and un-assigned")
);
if (User::getGroupID($usr_id) != '') {
    $assign_options['-3'] = ev_gettext('myself and my group');
    $assign_options['-4'] = ev_gettext('myself, un-assigned and my group');
}
if ((count($groups) > 0) && (Auth::getCurrentRole() > User::getRoleID("Customer"))) {
    foreach ($groups as $grp_id => $grp_name) {
        $assign_options["grp:$grp_id"] = ev_gettext("Group") . ": " . $grp_name;
    }
}
$assign_options += $users;

$list = Issue::getListing($prj_id, $options, $pagerRow, $rows);
$tpl->assign("list", $list["list"]);
$tpl->assign("list_info", $list["info"]);
$tpl->assign("csv_data", base64_encode(@$list["csv"]));

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
    $tpl->assign("open_status", Status::getAssocStatusList($prj_id, false));
    $tpl->assign("available_releases", Release::getAssocList($prj_id));
}

$tpl->displayTemplate();
