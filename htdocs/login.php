<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2015 Eventum Team.                              |
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
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+

require_once __DIR__ . '/../init.php';

$login = isset($_POST['email']) ? (string) $_POST['email'] : null;
if (Validation::isWhitespace($login)) {
    Auth::redirect('index.php?err=1');
}
$passwd = isset($_POST['passwd']) ? (string) $_POST['passwd'] : null;
if (Validation::isWhitespace($passwd)) {
    Auth::saveLoginAttempt($login, 'failure', 'empty password');
    Auth::redirect('index.php?err=2&email=' . rawurlencode($login));
}

// check if user exists
if (!Auth::userExists($login)) {
    Auth::saveLoginAttempt($login, 'failure', 'unknown user');
    Auth::redirect('index.php?err=3');
}

// check if user is locked
if (Auth::isUserBackOffLocked(Auth::getUserIDByLogin($login))) {
    Auth::saveLoginAttempt($login, 'failure', 'account back-off locked');
    Auth::redirect('index.php?err=13');
}

// check if the password matches
if (!Auth::isCorrectPassword($login, $passwd)) {
    Auth::saveLoginAttempt($login, 'failure', 'wrong password');
    Auth::redirect('index.php?err=3&email=' . rawurlencode($login));
}

Auth::login($login);
if (!empty($_POST['url'])) {
    $extra = '?url=' . urlencode($_POST['url']);
} else {
    $extra = '';
}
Auth::redirect('select_project.php' . $extra);
