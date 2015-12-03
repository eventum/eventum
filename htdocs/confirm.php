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
