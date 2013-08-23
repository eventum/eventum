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
// | Authors: Bryan Alsdorf <bryan@mysql.com>                             |
// +----------------------------------------------------------------------+

require_once dirname(__FILE__) . '/../init.php';

// This page handles marking an issue as 'redeeming' an incident.
Auth::checkAuthentication(APP_COOKIE, 'index.php?err=5', true);

$prj_id = Auth::getCurrentProject();
$issue_id = $_REQUEST['iss_id'];
$usr_id = Auth::getUserID();

if ((!Issue::canAccess($issue_id, $usr_id)) || (Auth::getCurrentRole() <= User::getRoleID("Customer"))) {
    $tpl->setTemplate("permission_denied.tpl.html");
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
    'incident_details'  =>  $details['incident_details']
));
$tpl->displayTemplate();
