<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
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

$tpl = new Template_Helper();
$tpl->setTemplate('redeem_incident.tpl.html');

if (!empty($_REQUEST['submit'])) {
    // update counts
    $res = Customer::updateRedeemedIncidents($prj_id, $issue_id, @$_REQUEST['redeem']);
    $tpl->assign('res', $res);
}
$details = Customer::getDetails($prj_id, Issue::getCustomerID($issue_id), true);

$tpl->assign(array(
    'issue_id'  =>  $issue_id,
    'redeemed'  =>  Customer::getRedeemedIncidentDetails($prj_id, $issue_id),
    'incident_details'  =>  $details['incident_details']
));
$tpl->displayTemplate();
