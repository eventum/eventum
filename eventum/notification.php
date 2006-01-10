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
// @(#) $Id: s.notification.php 1.10 03/08/20 17:49:55-00:00 jpradomaia $
//
include_once("config.inc.php");
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.project.php");
include_once(APP_INC_PATH . "class.notification.php");
include_once(APP_INC_PATH . "class.prefs.php");
include_once(APP_INC_PATH . "db_access.php");

$tpl = new Template_API();
$tpl->setTemplate("notification.tpl.html");

Auth::checkAuthentication(APP_COOKIE, 'index.php?err=5', true);

$usr_id = Auth::getUserID();
$prj_id = Auth::getCurrentProject();
$issue_id = @$HTTP_POST_VARS["issue_id"] ? $HTTP_POST_VARS["issue_id"] : $HTTP_GET_VARS["iss_id"];
$tpl->assign("issue_id", $issue_id);

// format default actions properly
$default = Notification::getDefaultActions();
$res = array();
foreach ($default as $action) {
    $res[$action] = 1;
}
$tpl->assign("default_actions", $res);

if (@$HTTP_POST_VARS["cat"] == "insert") {
    $res = Notification::subscribeEmail($usr_id, $issue_id, $HTTP_POST_VARS['email'], $HTTP_POST_VARS['actions']);
    $tpl->assign("insert_result", $res);
} elseif (@$HTTP_GET_VARS["cat"] == "edit") {
    $res = Notification::getDetails($HTTP_GET_VARS["id"]);
    $tpl->assign("info", $res);
} elseif (@$HTTP_POST_VARS["cat"] == "update") {
    $res = Notification::update($HTTP_POST_VARS["id"]);
    $tpl->assign("update_result", $res);
} elseif (@$HTTP_POST_VARS["cat"] == "delete") {
    $res = Notification::remove($HTTP_POST_VARS["items"]);
    $tpl->assign("delete_result", $res);
}

$tpl->assign("list", Notification::getSubscriberListing($issue_id));
$t = Project::getAddressBook($prj_id, $issue_id);
$tpl->assign("assoc_users", $t);
$tpl->assign("allowed_emails", Project::getAddressBookEmails($prj_id, $issue_id));

$tpl->displayTemplate();
?>