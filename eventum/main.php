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
// @(#) $Id: s.main.php 1.11 04/01/13 20:02:51-00:00 jpradomaia $
//
require_once("config.inc.php");
require_once(APP_INC_PATH . "class.template.php");
require_once(APP_INC_PATH . "class.auth.php");
require_once(APP_INC_PATH . "class.user.php");
require_once(APP_INC_PATH . "class.stats.php");
require_once(APP_INC_PATH . "class.misc.php");
require_once(APP_INC_PATH . "class.news.php");
require_once(APP_INC_PATH . "db_access.php");

$tpl = new Template_API();
$tpl->setTemplate("main.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

$prj_id = Auth::getCurrentProject();
$role_id = Auth::getCurrentRole();
if ($role_id == User::getRoleID('customer')) {
    // need the activity dashboard here
    $usr_id = Auth::getUserID();
    $customer_id = User::getCustomerID($usr_id);
    $tpl->assign("customer_stats", Customer::getOverallStats($prj_id, $customer_id));
    $tpl->assign("profile", Customer::getProfile($prj_id, $usr_id));
} else {
    $tpl->assign("status", Stats::getStatus());
    $tpl->assign("releases", Stats::getRelease());
    $tpl->assign("categories", Stats::getCategory());
    $tpl->assign("priorities", Stats::getPriority());
    $tpl->assign("users", Stats::getUser());
    $tpl->assign("emails", Stats::getEmailStatus());
    $tpl->assign("pie_chart", Stats::getPieChart());
    $tpl->assign("random_tip", Misc::getRandomTip($tpl));
}

$tpl->assign("news", News::getListByProject($prj_id));

$tpl->displayTemplate();
?>