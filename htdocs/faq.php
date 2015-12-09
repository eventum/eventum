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
$tpl->setTemplate('faq.tpl.html');

Auth::checkAuthentication('index.php?err=5', true);

$usr_id = Auth::getUserID();
$prj_id = Auth::getCurrentProject();

if (!CRM::hasCustomerIntegration($prj_id)) {
    // show all FAQ entries
    $support_level_ids = array();
} else {
    $crm = CRM::getInstance($prj_id);
    if (Auth::getCurrentRole() != User::ROLE_CUSTOMER) {
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
$tpl->assign('faqs', FAQ::getListBySupportLevel($support_level_ids));

if (!empty($_GET['id'])) {
    $t = FAQ::getDetails($_GET['id']);
    // check if this customer should have access to this FAQ entry or not
    if ((count($support_level_ids) > 0) && (count(array_intersect($support_level_ids, $t['support_levels'])) < 1)) {
        $tpl->assign('faq', -1);
    } else {
        $tpl->assign('faq', $t);
    }
}

$tpl->displayTemplate();
