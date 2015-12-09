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
$tpl->setTemplate('select_partners.tpl.html');

Auth::checkAuthentication('index.php?err=5', true);

$issue_id = @$_POST['issue_id'] ? $_POST['issue_id'] : $_GET['iss_id'];

if ((!Access::canViewDrafts($issue_id, Auth::getUserID())) || (Auth::getCurrentRole() <= User::ROLE_USER)) {
    $tpl = new Template_Helper();
    $tpl->setTemplate('permission_denied.tpl.html');
    $tpl->displayTemplate();
    exit;
}
$prj_id = Issue::getProjectID($issue_id);

if (@$_POST['cat'] == 'update') {
    $res = Partner::selectPartnersForIssue($_POST['issue_id'], @$_POST['partners']);
    $tpl->assign('update_result', $res);
}

$tpl->assign(array(
    'issue_id'           => $issue_id,
    'enabled_partners'   => Partner::getPartnersByProject($prj_id),
    'partners'           => Partner::getPartnersByIssue($issue_id),
    'current_user_prefs' => Prefs::get(Auth::getUserID()),
));

$tpl->displayTemplate();
