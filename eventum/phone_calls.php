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
// @(#) $Id$
//
include_once("config.inc.php");
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.phone_support.php");
include_once(APP_INC_PATH . "db_access.php");

$tpl = new Template_API();
$tpl->setTemplate("add_phone_entry.tpl.html");

Auth::checkAuthentication(APP_COOKIE, 'index.php?err=5', true);

$issue_id = @$HTTP_POST_VARS["issue_id"] ? $HTTP_POST_VARS["issue_id"] : $HTTP_GET_VARS["iss_id"];

if (!Issue::canAccess($issue_id, Auth::getUserID())) {
    $tpl = new Template_API();
    $tpl->setTemplate("permission_denied.tpl.html");
    $tpl->displayTemplate();
    exit;
}

if (@$HTTP_POST_VARS["cat"] == "add_phone") {
    $res = Phone_Support::insert();
    $tpl->assign("add_phone_result", $res);
}

$prj_id = Issue::getProjectID($issue_id);
$usr_id = Auth::getUserID();

$tpl->assign(array(
    "issue_id"           => $issue_id,
    "phone_categories"   => Phone_Support::getCategoryAssocList($prj_id),
    'current_user_prefs' => Prefs::get($usr_id)
));

$tpl->displayTemplate();
?>