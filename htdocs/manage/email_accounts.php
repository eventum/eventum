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
$tpl->setTemplate('manage/email_accounts.tpl.html');

Auth::checkAuthentication();

$tpl->assign('all_projects', Project::getAll());

$role_id = Auth::getCurrentRole();
if ($role_id < User::ROLE_REPORTER) {
    Misc::setMessage(ev_gettext('Sorry, you are not allowed to access this page.'), Misc::MSG_ERROR);
    $tpl->displayTemplate();
    exit;
}

if (@$_POST['cat'] == 'new') {
    Misc::mapMessages(Email_Account::insert(), array(
            1   =>  array(ev_gettext('Thank you, the email account was added successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to add the new account.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST['cat'] == 'update') {
    Misc::mapMessages(Email_Account::update(), array(
            1   =>  array(ev_gettext('Thank you, the email account was updated successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to update the account information.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST['cat'] == 'delete') {
    Misc::mapMessages(Email_Account::remove(), array(
            1   =>  array(ev_gettext('Thank you, the email account was deleted successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to delete the account information.'), Misc::MSG_ERROR),
    ));
}

if (@$_GET['cat'] == 'edit') {
    $tpl->assign('info', Email_Account::getDetails($_GET['id']));
}
$tpl->assign('list', Email_Account::getList());

$tpl->displayTemplate();
