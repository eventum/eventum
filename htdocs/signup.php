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
$tpl->setTemplate('signup.tpl.html');

// log anonymous users out so they can use the signup form
if (AuthCookie::hasAuthCookie() && Auth::isAnonUser()) {
    Auth::logout();
}

if (@$_POST['cat'] == 'signup') {
    $setup = Setup::get();
    $res = User::createVisitorAccount($setup['accounts_role'], $setup['accounts_projects']);
    $tpl->assign('signup_result', $res);
    Misc::mapMessages($res, array(
            1   =>  array('Thank you, your account creation request was processed successfully. For security reasons a confirmation email was sent to the provided email address with instructions on how to confirm your request and activate your account.', Misc::MSG_INFO),
            -1  =>  array('Error: An error occurred while trying to run your query.', Misc::MSG_ERROR),
            -2  =>  array('Error: The email address specified is already associated with an user in the system.', Misc::MSG_ERROR),
    ));
}

$tpl->displayTemplate();
