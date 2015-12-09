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
$tpl->setTemplate('removed_emails.tpl.html');

Auth::checkAuthentication(null, true);

if (@$_POST['cat'] == 'restore') {
    $res = Support::restoreEmails();
    $tpl->assign('result_msg', $res);
} elseif (@$_POST['cat'] == 'remove') {
    $res = Support::expungeEmails($_POST['item']);
    $tpl->assign('result_msg', $res);
}

$tpl->assign('list', Support::getRemovedList());

$tpl->displayTemplate();
