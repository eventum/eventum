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
$tpl->setTemplate('add_phone_entry.tpl.html');

Auth::checkAuthentication('index.php?err=5', true);

$issue_id = @$_POST['issue_id'] ? $_POST['issue_id'] : $_GET['iss_id'];

if ((!Issue::canAccess($issue_id, Auth::getUserID())) || (Auth::getCurrentRole() <= User::ROLE_CUSTOMER)) {
    $tpl = new Template_Helper();
    $tpl->setTemplate('permission_denied.tpl.html');
    $tpl->displayTemplate();
    exit;
}

if (@$_POST['cat'] == 'add_phone') {
    $res = Phone_Support::insert();
    $tpl->assign('add_phone_result', $res);
}

$prj_id = Issue::getProjectID($issue_id);
$usr_id = Auth::getUserID();

$tpl->assign(array(
    'issue_id'           => $issue_id,
    'phone_categories'   => Phone_Support::getCategoryAssocList($prj_id),
    'current_user_prefs' => Prefs::get($usr_id),
));

$tpl->displayTemplate();
