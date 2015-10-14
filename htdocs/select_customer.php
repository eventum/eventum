<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2013-2015 Eventum Team.                                |
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
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// +----------------------------------------------------------------------+

require_once __DIR__ . '/../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate('select_customer.tpl.html');

session_start();

// check if cookies are enabled, first of all
if (!AuthCookie::hasCookieSupport()) {
    Auth::redirect('index.php?err=11');
}

if (!AuthCookie::hasAuthCookie()) {
    Auth::redirect('index.php?err=5');
}

$prj_id = Auth::getCurrentProject();
$usr_id = Auth::getUserID();
$contact_id = User::getCustomerContactID($usr_id);
if (!CRM::hasCustomerIntegration($prj_id) || empty($contact_id)) {
    Auth::redirect('main.php');
}
$crm = CRM::getInstance($prj_id);
$contact = $crm->getContact($contact_id);
$customers = $contact->getCustomers();

if (isset($_REQUEST['customer_id'])) {
    $customer_id = $_REQUEST['customer_id'];
    if (in_array($customer_id, array_keys($customers))) {
        Auth::setCurrentCustomerID($customer_id);
        if (!empty($_POST['url'])) {
            Auth::redirect($_REQUEST['url']);
        } else {
            Auth::redirect('main.php');
        }
    }
}

$tpl->assign('customers', $customers);

$tpl->displayTemplate();
