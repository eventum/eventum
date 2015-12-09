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

require_once __DIR__ . '/../../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate('reports/workload_time_period.tpl.html');

Auth::checkAuthentication();
$usr_id = Auth::getUserID();

if (!Access::canAccessReports(Auth::getUserID())) {
    echo 'Invalid role';
    exit;
}

$prj_id = Auth::getCurrentProject();

// get timezone of current user
$user_prefs = Prefs::get($usr_id);

if (@$_GET['type'] == 'email') {
    $data = Report::getEmailWorkloadByTimePeriod(@$user_prefs['timezone']);
} else {
    $data = Report::getWorkloadByTimePeriod(@$user_prefs['timezone']);
}

$tpl->assign(array(
    'data'    => $data,
    'type'    => @$_GET['type'],
    'user_tz' => Date_Helper::getTimezoneShortNameByUser($usr_id),
));
$tpl->displayTemplate();
