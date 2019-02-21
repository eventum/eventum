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

require_once __DIR__ . '/../../../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate('customer/customer_lookup.tpl.html');

Auth::checkAuthentication();
$usr_id = Auth::getUserID();
$prj_id = Auth::getCurrentProject();

// only customers should be able to use this page
$role_id = Auth::getCurrentRole();
if ($role_id < User::ROLE_DEVELOPER) {
    Auth::redirect('list.php');
}

if (@$_POST['cat'] === 'lookup') {
    $tpl->assign('results', Customer_OLD::lookup($prj_id, $_POST['field'], $_POST['value']));
}

$tpl->displayTemplate();
