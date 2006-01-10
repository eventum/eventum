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
// @(#) $Id: s.preferences.php 1.9 04/01/07 15:50:18-00:00 jpradomaia $
//
include_once("config.inc.php");
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.prefs.php");
include_once(APP_INC_PATH . "class.setup.php");
include_once(APP_INC_PATH . "class.date.php");
include_once(APP_INC_PATH . "db_access.php");

$tpl = new Template_API();
$tpl->setTemplate("preferences.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

$usr_id = Auth::getUserID();

if (@$HTTP_POST_VARS["cat"] == "update_account") {
    $res = Prefs::set($usr_id);
    $tpl->assign('update_account_result', $res);
    User::updateSMS($usr_id, @$HTTP_POST_VARS['sms_email']);
} elseif (@$HTTP_POST_VARS["cat"] == "update_name") {
    $res = User::updateFullName($usr_id);
    $tpl->assign('update_name_result', $res);
} elseif (@$HTTP_POST_VARS["cat"] == "update_email") {
    $res = User::updateEmail($usr_id);
    $tpl->assign('update_email_result', $res);
} elseif (@$HTTP_POST_VARS["cat"] == "update_password") {
    $res = User::updatePassword($usr_id);
    $tpl->assign('update_password_result', $res);
}

$prefs = Prefs::get($usr_id);
$prefs['sms_email'] = User::getSMS($usr_id);
// if the user has no preferences set yet, get it from the system-wide options
if (empty($prefs)) {
    $prefs = Setup::load();
}
$tpl->assign("user_prefs", $prefs);
$tpl->assign("assigned_projects", Project::getAssocList($usr_id, false, true));
$tpl->assign("zones", Date_API::getTimezoneList());

$tpl->displayTemplate();
?>