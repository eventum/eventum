<?php

/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

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
