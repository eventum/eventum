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
$tpl->setTemplate('forgot_password.tpl.html');

if (@$_POST['cat'] == 'reset_password') {
    if (empty($_POST['email'])) {
        $tpl->assign('result', 4);
    }
    $usr_id = User::getUserIDByEmail($_POST['email'], true);
    if (empty($usr_id)) {
        $tpl->assign('result', 5);
    } else {
        $info = User::getDetails($usr_id);
        if (!User::isActiveStatus($info['usr_status'])) {
            $tpl->assign('result', 3);
        } else {
            User::sendPasswordConfirmationEmail($usr_id);
            $tpl->assign('result', 1);
        }
    }
}

$tpl->displayTemplate();
