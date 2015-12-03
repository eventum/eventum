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
$tpl->setTemplate('reports/open_issues.tpl.html');

Auth::checkAuthentication();

if (!Access::canAccessReports(Auth::getUserID())) {
    echo 'Invalid role';
    exit;
}

$prj_id = Auth::getCurrentProject();

if (!isset($_GET['cutoff_days'])) {
    $cutoff_days = 7;
} else {
    $cutoff_days = $_GET['cutoff_days'];
}

if (empty($_GET['group_by_reporter'])) {
    $group_by_reporter = false;
} else {
    $group_by_reporter = true;
}
$tpl->assign('cutoff_days', $cutoff_days);
$res = Report::getOpenIssuesByUser($prj_id, $cutoff_days, $group_by_reporter);
$tpl->assign('users', $res);

$tpl->displayTemplate();
