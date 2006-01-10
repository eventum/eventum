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
include_once(APP_INC_PATH . "db_access.php");
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.faq.php");
include_once(APP_INC_PATH . "class.customer.php");

$tpl = new Template_API();
$tpl->setTemplate("faq.tpl.html");

Auth::checkAuthentication(APP_COOKIE, 'index.php?err=5', true);

$usr_id = Auth::getUserID();
$prj_id = Auth::getCurrentProject();

if (!Customer::hasCustomerIntegration($prj_id)) {
    // show all FAQ entries
    $support_level_id = -1;
} else {
    if (!Customer::doesBackendUseSupportLevels($prj_id)) {
        // show all FAQ entries
        $support_level_id = -1;
    } else {
        if (Auth::getCurrentRole() != User::getRoleID('Customer')) {
            // show all FAQ entries
            $support_level_id = -1;
        } else {
            $customer_id = User::getCustomerID(Auth::getUserID());
            $support_level_id = Customer::getSupportLevelID($prj_id, $customer_id);
        }
    }
}
$tpl->assign("faqs", FAQ::getListBySupportLevel($support_level_id));

if (!empty($HTTP_GET_VARS["id"])) {
    $t = FAQ::getDetails($HTTP_GET_VARS['id']);
    // check if this customer should have access to this FAQ entry or not
    if (($support_level_id != -1) && (!in_array($support_level_id, $t['support_levels']))) {
        $tpl->assign('faq', -1);
    } else {
        $t['faq_created_date'] = Date_API::getFormattedDate($t["faq_created_date"]);
        $tpl->assign("faq", $t);
    }
}

$tpl->displayTemplate();
?>