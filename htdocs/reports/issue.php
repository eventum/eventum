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
$tpl->setTemplate('reports/issue_user.tpl.html');

Auth::checkAuthentication();

if (!Access::canAccessReports(Auth::getUserID())) {
    echo 'Invalid role';
    exit;
}

$prj_id = Auth::getCurrentProject();

if ($_GET['cat'] == 'user') {
    $res = Report::getIssuesByUser($prj_id);
    $tpl->assign('users', $res);
}

$tpl->displayTemplate();
