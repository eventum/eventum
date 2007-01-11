<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005, 2006, 2007 MySQL AB                              |
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
// @(#) $Id: s.anonymous.php 1.4 03/03/01 23:27:37-00:00 jpm $
//
require_once("../config.inc.php");
require_once(APP_INC_PATH . "class.template.php");
require_once(APP_INC_PATH . "class.auth.php");
require_once(APP_INC_PATH . "class.user.php");
require_once(APP_INC_PATH . "class.category.php");
require_once(APP_INC_PATH . "class.priority.php");
require_once(APP_INC_PATH . "class.misc.php");
require_once(APP_INC_PATH . "class.project.php");
require_once(APP_INC_PATH . "class.setup.php");
require_once(APP_INC_PATH . "db_access.php");

$tpl = new Template_API();
$tpl->setTemplate("manage/index.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

$tpl->assign("type", "anonymous");

@$prj_id = $_POST["prj_id"] ? $_POST["prj_id"] : $_GET["prj_id"];

$role_id = Auth::getCurrentRole();
if (($role_id == User::getRoleID('administrator')) || ($role_id == User::getRoleID('manager'))) {
    if ($role_id == User::getRoleID('administrator')) {
        $tpl->assign("show_setup_links", true);
    }

    if (@$_POST["cat"] == "update") {
        $tpl->assign("result", Project::updateAnonymousPost($prj_id));
    }
    // load the form fields
    $tpl->assign("project", Project::getDetails($prj_id));
    $tpl->assign("cats", Category::getAssocList($prj_id));
    $tpl->assign("priorities", Priority::getList($prj_id));
    $tpl->assign("users", Project::getUserAssocList($prj_id, 'active'));
    $tpl->assign("options", Project::getAnonymousPostOptions($prj_id));
    $tpl->assign("prj_id", $prj_id);
    $setup = Setup::load();
    $tpl->assign("allow_unassigned_issues", @$setup["allow_unassigned_issues"]);
} else {
    $tpl->assign("show_not_allowed_msg", true);
}

$tpl->displayTemplate();
