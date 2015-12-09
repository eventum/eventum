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
$tpl->setTemplate('add_time_tracking.tpl.html');

Auth::checkAuthentication('index.php?err=5', true);

$issue_id = @$_POST['issue_id'] ? $_POST['issue_id'] : $_GET['iss_id'];

if ((!Issue::canAccess($issue_id, Auth::getUserID())) || (Auth::getCurrentRole() <= User::ROLE_CUSTOMER)) {
    $tpl = new Template_Helper();
    $tpl->setTemplate('permission_denied.tpl.html');
    $tpl->displayTemplate();
    exit;
}

if (@$_POST['cat'] == 'add_time') {
    $date = (array) $_POST['date'];
    $ttc_id = (int) $_POST['category'];
    $iss_id = (int) $_POST['issue_id'];
    $time_spent = (int) $_POST['time_spent'];
    $summary = (string) $_POST['summary'];
    $res = Time_Tracking::addTimeEntry($iss_id, $ttc_id, $time_spent, $date, $summary);
    $tpl->assign('time_add_result', $res);
}

$prj_id = Auth::getCurrentProject();
$tpl->assign(array(
    'issue_id'           => $issue_id,
    'time_categories'    => Time_Tracking::getAssocCategories($prj_id),
    'current_user_prefs' => Prefs::get(Auth::getUserID()),
));

$tpl->displayTemplate();
