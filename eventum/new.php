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
// @(#) $Id: s.new.php 1.14 03/07/11 05:04:05-00:00 jpm $
//
include_once("config.inc.php");
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.category.php");
include_once(APP_INC_PATH . "class.release.php");
include_once(APP_INC_PATH . "class.issue.php");
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.support.php");
include_once(APP_INC_PATH . "class.custom_field.php");
include_once(APP_INC_PATH . "class.setup.php");
include_once(APP_INC_PATH . "db_access.php");

$tpl = new Template_API();
$tpl->setTemplate("new.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

if (@$HTTP_POST_VARS["cat"] == "report") {
    $res = Issue::insert();
    if ($res != -1) {
        // show direct links to the issue page, issue listing page and 
        // email listing page
        $tpl->assign("new_issue_id", $res);
    } else {
        // need to show everything again
        $tpl->assign("error_msg", "1");
    }
}

if (@$HTTP_GET_VARS["cat"] == "associate") {
    $res = Support::getListDetails($HTTP_GET_VARS["item"]);
    $tpl->assign("emails", $res);
    $tpl->assign("attached_emails", @implode(",", $HTTP_GET_VARS["item"]));
}

$prj_id = Auth::getCurrentProject();
$tpl->assign("cats", Category::getAssocList($prj_id));
$tpl->assign("priorities", Misc::getPriorities());
$tpl->assign("users", Project::getUserAssocList($prj_id, 'active'));
$tpl->assign("releases", Release::getAssocList($prj_id));
$tpl->assign("custom_fields", Custom_Field::getListByProject($prj_id, 'report_form'));

$setup = Setup::load();
$tpl->assign("allow_unassigned_issues", $setup["allow_unassigned_issues"]);

$prefs = Prefs::get(Auth::getUserID());
$tpl->assign("user_prefs", $prefs);

$tpl->displayTemplate();
?>