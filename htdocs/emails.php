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
$tpl->setTemplate('emails.tpl.html');

Auth::checkAuthentication();

if (!Access::canAccessAssociateEmails(Auth::getUserID())) {
    $tpl->assign('no_access', 1);
    $tpl->displayTemplate();
    exit;
}

// accept prj_id from GET to ease administration (link bookmarking)
$prj_id = isset($_GET['prj_id']) ? (int) $_GET['prj_id'] : null;
if ($prj_id && $prj_id != Auth::getCurrentProject()) {
    AuthCookie::setProjectCookie($prj_id);
}

$pagerRow = Support::getParam('pagerRow');
if (empty($pagerRow)) {
    $pagerRow = 0;
}
$rows = Support::getParam('rows');
if (empty($rows)) {
    $rows = APP_DEFAULT_PAGER_SIZE;
}

$options = Support::saveSearchParams();
$tpl->assign('options', $options);
$tpl->assign('sorting', Support::getSortingInfo($options));

$list = Support::getEmailListing($options, $pagerRow, $rows);
$tpl->assign('list', $list['list']);
$tpl->assign('list_info', $list['info']);
$tpl->assign('issues', Issue::getColList());
$tpl->assign('accounts', Email_Account::getAssocList($prj_id));

$prefs = Prefs::get(Auth::getUserID());
$tpl->assign('refresh_rate', $prefs['email_refresh_rate'] * 60);
$tpl->assign('refresh_page', 'emails.php');

$tpl->displayTemplate();
