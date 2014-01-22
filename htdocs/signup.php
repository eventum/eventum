<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2013 Eventum Team.                              |
// |                                                                      |
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License as published by |
// | the Free Software Foundation; either version 2 of the License, or    |
// | (at your option) any later version.                                  |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to:                           |
// |                                                                      |
// | Free Software Foundation, Inc.                                       |
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// +----------------------------------------------------------------------+

require_once dirname(__FILE__) . '/../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate("signup.tpl.html");

// log anonymous users out so they can use the signup form
if (Auth::hasValidCookie(APP_COOKIE) && Auth::isAnonUser()) {
    Auth::logout();
}

if (@$_POST['cat'] == 'signup') {
    $setup = Setup::load();
    $res = User::createVisitorAccount($setup['accounts_role'], $setup['accounts_projects']);
    $tpl->assign('signup_result', $res);
    Misc::mapMessages($res, array(
            1   =>  array('Thank you, your account creation request was processed successfully. For security reasons a confirmation email was sent to the provided email address with instructions on how to confirm your request and activate your account.', Misc::MSG_INFO),
            -1  =>  array('Error: An error occurred while trying to run your query.', Misc::MSG_ERROR),
            -2  =>  array('Error: The email address specified is already associated with an user in the system.', Misc::MSG_ERROR),
    ));
}

$tpl->displayTemplate();
