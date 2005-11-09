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
// @(#) $Id: s.general.php 1.16 04/01/22 16:21:32-00:00 jpradomaia $
//
include_once("../config.inc.php");
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.project.php");
include_once(APP_INC_PATH . "class.user.php");
include_once(APP_INC_PATH . "class.setup.php");
include_once(APP_INC_PATH . "db_access.php");

$tpl = new Template_API();
$tpl->setTemplate("manage/index.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

$tpl->assign("type", "general");

$role_id = Auth::getCurrentRole();
if ($role_id == User::getRoleID('administrator')) {
    $tpl->assign("show_setup_links", true);

    $tpl->assign("project_list", Project::getAll());

    if (@$HTTP_POST_VARS["cat"] == "update") {
        $setup = array();
        $setup["tool_caption"] = $HTTP_POST_VARS["tool_caption"];
        $setup["support_email"] = $HTTP_POST_VARS["support_email"];
        $setup["daily_tips"] = $HTTP_POST_VARS["daily_tips"];
        $setup["spell_checker"] = $HTTP_POST_VARS["spell_checker"];
        $setup["irc_notification"] = $HTTP_POST_VARS["irc_notification"];
        $setup["allow_unassigned_issues"] = $HTTP_POST_VARS["allow_unassigned_issues"];
        @$setup["update"] = $HTTP_POST_VARS["update"];
        @$setup["closed"] = $HTTP_POST_VARS["closed"];
        @$setup["notes"] = $HTTP_POST_VARS["notes"];
        @$setup["emails"] = $HTTP_POST_VARS["emails"];
        @$setup["files"] = $HTTP_POST_VARS["files"];
        @$setup["smtp"] = $HTTP_POST_VARS["smtp"];
        @$setup["scm_integration"] = $HTTP_POST_VARS["scm_integration"];
        @$setup["checkout_url"] = $HTTP_POST_VARS["checkout_url"];
        @$setup["diff_url"] = $HTTP_POST_VARS["diff_url"];
        @$setup["open_signup"] = $HTTP_POST_VARS["open_signup"];
        @$setup["accounts_projects"] = $HTTP_POST_VARS["accounts_projects"];
        @$setup["accounts_role"] = $HTTP_POST_VARS["accounts_role"];
        @$setup['subject_based_routing'] = $HTTP_POST_VARS['subject_based_routing'];
        @$setup['email_routing'] = $HTTP_POST_VARS['email_routing'];
        @$setup['note_routing'] = $HTTP_POST_VARS['note_routing'];
        @$setup['draft_routing'] = $HTTP_POST_VARS['draft_routing'];
        @$setup['email_error'] = $HTTP_POST_VARS['email_error'];
        @$setup['email_reminder'] = $HTTP_POST_VARS['email_reminder'];
        $options = Setup::load();
        @$setup['downloading_emails'] = $options['downloading_emails'];
        $res = Setup::save($setup);
        $tpl->assign("result", $res);
    }
    $options = Setup::load(true);
    $tpl->assign("setup", $options);
    $tpl->assign("user_roles", User::getRoles(array('Customer')));
} else {
    $tpl->assign("show_not_allowed_msg", true);
}

$tpl->displayTemplate();
?>