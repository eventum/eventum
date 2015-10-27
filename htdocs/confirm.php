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

$tpl = new Template_Helper();
$tpl->setTemplate('confirm.tpl.html');

$cat = isset($_GET['cat']) ? (string) $_GET['cat'] : null;
$email = isset($_GET['email']) ? (string) $_GET['email'] : null;
$hash = isset($_GET['hash']) ? (string) $_GET['hash'] : null;
if ($cat == 'newuser') {
    $res = User::checkHash($email, $hash);
    if ($res == 1) {
        User::confirmVisitorAccount($email);
        // redirect user to login form with pretty message
        Auth::redirect('index.php?err=8&email=' . $email);
        exit;
    }
    $tpl->assign('confirm_result', $res);
} elseif ($cat == 'password') {
    $res = User::checkHash($email, $hash);
    if ($res == 1) {
        User::confirmNewPassword($email);
        $tpl->assign('email', $email);
    }
    $tpl->assign('confirm_result', $res);
}

$tpl->displayTemplate();
