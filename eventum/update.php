<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004 MySQL AB                                    |
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
// @(#) $Id: s.update.php 1.15 03/10/31 17:09:07-00:00 jpradomaia $
//
include_once("config.inc.php");
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.issue.php");
include_once(APP_INC_PATH . "class.category.php");
include_once(APP_INC_PATH . "class.project.php");
include_once(APP_INC_PATH . "class.release.php");
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.notification.php");
include_once(APP_INC_PATH . "class.status.php");
include_once(APP_INC_PATH . "db_access.php");

$tpl = new Template_API();
$tpl->setTemplate("update.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

$issue_id = @$HTTP_POST_VARS["issue_id"] ? $HTTP_POST_VARS["issue_id"] : $HTTP_GET_VARS["id"];
$tpl->assign("issue", Issue::getDetails($issue_id));
$tpl->assign("extra_title", "Update Issue #$issue_id");

if (@$HTTP_POST_VARS["cat"] == "update") {
    $res = Issue::update($HTTP_POST_VARS["issue_id"]);
    $tpl->assign("update_result", $res);
    if (Issue::hasDuplicates($HTTP_POST_VARS["issue_id"])) {
        $tpl->assign("has_duplicates", "yes");
    }
}

$prj_id = Auth::getCurrentProject();

$tpl->assign(array(
    "subscribers"  => Notification::getSubscribers($issue_id),
    "categories"   => Category::getAssocList($prj_id),
    "priorities"   => Misc::getAssocPriorities(),
    "status"       => Status::getAssocStatusList($prj_id),
    "releases"     => Release::getAssocList($prj_id),
    "resolutions"  => Resolution::getAssocList(),
    "users"        => Project::getUserAssocList($prj_id, 'active', User::getRoleID('Reporter')),
    "issues"       => Issue::getColList(),
    "assoc_issues" => Issue::getAssocList()
));

$tpl->displayTemplate();
?>