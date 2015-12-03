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
$tpl->setTemplate('history.tpl.html');

Auth::checkAuthentication('index.php?err=5', true);

$iss_id = $_GET['iss_id'];
if (!Access::canViewHistory($iss_id, Auth::getUserID())) {
    $tpl->setTemplate('permission_denied.tpl.html');
    $tpl->displayTemplate();
    exit;
}

$tpl->assign('changes', History::getListing($iss_id));
$tpl->assign('issue_id', $iss_id);

$role_id = Auth::getCurrentRole();
if ($role_id > User::ROLE_CUSTOMER) {
    $tpl->assign('reminders', Reminder::getHistoryList($_GET['iss_id']));
}

$tpl->displayTemplate();
