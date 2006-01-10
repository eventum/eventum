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
// @(#) $Id: s.emails.php 1.10 03/10/14 15:38:03-00:00 jpradomaia $
//
include_once("config.inc.php");
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.issue.php");
include_once(APP_INC_PATH . "class.support.php");
include_once(APP_INC_PATH . "db_access.php");

$tpl = new Template_API();
$tpl->setTemplate("emails.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

if (Auth::getCurrentRole() <= User::getRoleID("Customer")) {
    $tpl->assign("no_access", 1);
    $tpl->displayTemplate();
    exit;
}

$pagerRow = Support::getParam('pagerRow');
if (empty($pagerRow)) {
    $pagerRow = 0;
}
$rows = Support::getParam('rows');
if (empty($rows)) {
    $rows = APP_DEFAULT_PAGER_SIZE;
}

$options = Support::saveSearchParams();
$tpl->assign("options", $options);
$tpl->assign("sorting", Support::getSortingInfo($options));

$list = Support::getEmailListing($options, $pagerRow, $rows);
$tpl->assign("list", $list["list"]);
$tpl->assign("list_info", $list["info"]);
$tpl->assign("issues", Issue::getColList());
$tpl->assign("accounts", Email_Account::getAssocList(Auth::getCurrentProject()));
$tpl->assign("assoc_issues", Issue::getAssocList());

$prefs = Prefs::get(Auth::getUserID());
$tpl->assign("refresh_rate", $prefs['emails_refresh_rate'] * 60);
$tpl->assign("refresh_page", "emails.php");

$tpl->displayTemplate();
?>