<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005, 2006 MySQL AB                        |
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
//
// @(#) $Id: s.login.php 1.21 03/10/08 17:06:06-00:00 jpradomaia $
//
include_once("config.inc.php");
include_once(APP_INC_PATH . "db_access.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.user.php");
include_once(APP_INC_PATH . "class.validation.php");

if (Validation::isWhitespace($HTTP_POST_VARS["email"])) {
    Auth::redirect(APP_RELATIVE_URL . "index.php?err=1");
}
if (Validation::isWhitespace($HTTP_POST_VARS["passwd"])) {
    Auth::saveLoginAttempt($HTTP_POST_VARS["email"], 'failure', 'empty password');
    Auth::redirect(APP_RELATIVE_URL . "index.php?err=2&email=" . $HTTP_POST_VARS["email"]);
}

// check if user exists
if (!Auth::userExists($HTTP_POST_VARS["email"])) {
    Auth::saveLoginAttempt($HTTP_POST_VARS["email"], 'failure', 'unknown user');
    Auth::redirect(APP_RELATIVE_URL . "index.php?err=3");
}
// check if the password matches
if (!Auth::isCorrectPassword($HTTP_POST_VARS["email"], $HTTP_POST_VARS["passwd"])) {
    Auth::saveLoginAttempt($HTTP_POST_VARS["email"], 'failure', 'wrong password');
    Auth::redirect(APP_RELATIVE_URL . "index.php?err=3&email=" . $HTTP_POST_VARS["email"]);
}
// check if this user did already confirm his account
if (Auth::isPendingUser($HTTP_POST_VARS["email"])) {
    Auth::saveLoginAttempt($HTTP_POST_VARS["email"], 'failure', 'pending user');
    Auth::redirect(APP_RELATIVE_URL . "index.php?err=9", $is_popup);
}
// check if this user is really an active one
if (!Auth::isActiveUser($HTTP_POST_VARS["email"])) {
    Auth::saveLoginAttempt($HTTP_POST_VARS["email"], 'failure', 'inactive user');
    Auth::redirect(APP_RELATIVE_URL . "index.php?err=7", $is_popup);
}

Auth::saveLoginAttempt($HTTP_POST_VARS["email"], 'success');
// redirect to the initial page
@Auth::createLoginCookie(APP_COOKIE, $HTTP_POST_VARS["email"], $HTTP_POST_VARS["remember_login"]);
if (!empty($HTTP_POST_VARS["url"])) {
    $extra = '?url=' . urlencode($HTTP_POST_VARS["url"]);
} else {
    $extra = '';
}
Auth::redirect(APP_RELATIVE_URL . "select_project.php" . $extra);
?>