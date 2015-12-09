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
$tpl->setTemplate('manage/email_alias.tpl.html');

Auth::checkAuthentication(null, true);

$role_id = Auth::getCurrentRole();
if ($role_id < User::ROLE_MANAGER) {
    $tpl->setTemplate('permission_denied.tpl.html');
    $tpl->displayTemplate();
    exit;
}

$usr_id = $_REQUEST['id'];

if (@$_POST['cat'] == 'save') {
    $res = User::addAlias($usr_id, trim($_POST['alias']));
    Misc::mapMessages($res, array(
            true   =>  array(ev_gettext('Thank you, the alias was added successfully.'), Misc::MSG_INFO),
            false  =>  array(ev_gettext('An error occurred while trying to add the alias.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST['cat'] == 'remove') {
    foreach ($_POST['item'] as $aliastmp) {
        $res = User::removeAlias($usr_id, $aliastmp);
    }
    Misc::mapMessages($res, array(
            true   =>  array(ev_gettext('Thank you, the alias was removed successfully.'), Misc::MSG_INFO),
            false  =>  array(ev_gettext('An error occurred while trying to remove the alias.'), Misc::MSG_ERROR),
    ));
}

$tpl->assign('list', User::getAliases($usr_id));
$tpl->assign('username', User::getFullName($usr_id));
$tpl->assign('id', $usr_id);

$tpl->displayTemplate();
