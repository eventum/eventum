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

// This page handles marking an issue as 'redeeming' an incident.
Auth::checkAuthentication('index.php?err=5', true);

$prj_id = Auth::getCurrentProject();
$issue_id = $_REQUEST['iss_id'];
$usr_id = Auth::getUserID();

if ((!Issue::canAccess($issue_id, $usr_id)) || (Auth::getCurrentRole() <= User::ROLE_CUSTOMER)) {
    $tpl->setTemplate('permission_denied.tpl.html');
    $tpl->displayTemplate();
    exit;
}

$tpl = new Template_Helper();
$tpl->setTemplate('redeem_incident.tpl.html');
$crm = CRM::getInstance($prj_id);
$contract = $crm->getContract(Issue::getContractID($issue_id));

if (!empty($_REQUEST['submit'])) {
    // update counts
    $res = $contract->updateRedeemedIncidents($issue_id, @$_REQUEST['redeem']);
    $tpl->assign('res', $res);
    Misc::mapMessages($res, array(
            1   =>  array('Thank you, the issue was successfully marked.', Misc::MSG_INFO),
            -1  =>  array('There was an error marking this issue as redeemed', Misc::MSG_ERROR),
            -2  =>  array('This issue already has been marked as redeemed', Misc::MSG_ERROR),
    ));
}
$details = $contract->getDetails();

$tpl->assign(array(
    'issue_id'  =>  $issue_id,
    'redeemed'  =>  $contract->getRedeemedIncidentDetails($issue_id),
    'incident_details'  =>  $details['incident_details'],
));
$tpl->displayTemplate();
