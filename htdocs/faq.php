<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2013 Eventum Team.                              |
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

require_once dirname(__FILE__) . '/../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate("faq.tpl.html");

Auth::checkAuthentication(APP_COOKIE, 'index.php?err=5', true);

$usr_id = Auth::getUserID();
$prj_id = Auth::getCurrentProject();

if (!CRM::hasCustomerIntegration($prj_id)) {
    // show all FAQ entries
    $support_level_ids = array();
} else {
    $crm = CRM::getInstance($prj_id);
    if (Auth::getCurrentRole() != User::getRoleID('Customer')) {
        // show all FAQ entries
        $support_level_ids = array();
    } else {
        $customer_id = User::getCustomerID(Auth::getUserID());
        $contact = Auth::getCurrentContact();
        $support_level_ids = array();
        // TODOCRM: only active contracts?
        foreach ($contact->getContracts() as $contract) {
            $support_level_ids[] = $contract->getSupportLevel()->getLevelID();
        }
    }
}
$tpl->assign("faqs", FAQ::getListBySupportLevel($support_level_ids));

if (!empty($_GET["id"])) {
    $t = FAQ::getDetails($_GET['id']);
    // check if this customer should have access to this FAQ entry or not
    if ((count($support_level_ids) > 0) && (count(array_intersect($support_level_ids, $t['support_levels'])) < 1)) {
        $tpl->assign('faq', -1);
    } else {
        $t['faq_created_date'] = Date_Helper::getFormattedDate($t["faq_created_date"]);
        $tpl->assign("faq", $t);
    }
}

$tpl->displayTemplate();
