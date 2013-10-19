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

if (Validation::isWhitespace($_POST["email"])) {
    Auth::redirect("index.php?err=1");
}
if (Validation::isWhitespace($_POST["passwd"])) {
    Auth::saveLoginAttempt($_POST["email"], 'failure', 'empty password');
    Auth::redirect("index.php?err=2&email=" . $_POST["email"]);
}

// check if user exists
if (!Auth::userExists($_POST["email"])) {
    Auth::saveLoginAttempt($_POST["email"], 'failure', 'unknown user');
    Auth::redirect("index.php?err=3");
}

// check if user is locked
if (Auth::isUserBackOffLocked(Auth::getUserIDByLogin($_POST['email']))){
    Auth::saveLoginAttempt($_POST["email"], 'failure', 'account back-off locked');
    Auth::redirect("index.php?err=13");
}

// check if the password matches
if (!Auth::isCorrectPassword($_POST["email"], $_POST["passwd"])) {
    Auth::saveLoginAttempt($_POST["email"], 'failure', 'wrong password');
    Auth::redirect("index.php?err=3&email=" . $_POST["email"]);
}

// handle aliases since the user is now authenticated
$_POST['email'] = User::getEmail(Auth::getUserIDByLogin($_POST['email']));

// check if this user did already confirm his account
if (Auth::isPendingUser($_POST["email"])) {
    Auth::saveLoginAttempt($_POST["email"], 'failure', 'pending user');
    Auth::redirect("index.php?err=9");
}
// check if this user is really an active one
if (!Auth::isActiveUser($_POST["email"])) {
    Auth::saveLoginAttempt($_POST["email"], 'failure', 'inactive user');
    Auth::redirect("index.php?err=7");
}

Auth::saveLoginAttempt($_POST["email"], 'success');

$remember = !empty($_POST['remember']);
Auth::createLoginCookie(APP_COOKIE, $_POST["email"], $remember);

Session::init(User::getUserIDByEmail($_POST['email']));
if (!empty($_POST["url"])) {
    $extra = '?url=' . urlencode($_POST["url"]);
} else {
    $extra = '';
}
Auth::redirect("select_project.php" . $extra);
