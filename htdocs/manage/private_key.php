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
$tpl->setTemplate('manage/private_key.tpl.html');

Auth::checkAuthentication();

$role_id = Auth::getCurrentRole();
if ($role_id < User::ROLE_ADMINISTRATOR) {
    Misc::setMessage(ev_gettext('Sorry, you are not allowed to access this page.'), Misc::MSG_ERROR);
    $tpl->displayTemplate();
    exit;
}

$cat = isset($_POST['cat']) ? (string) $_POST['cat'] : null;

if ($cat == 'update') {
    // regenerate key
    try {
        Auth::generatePrivateKey();
        Misc::setMessage(ev_gettext('Thank you, the private key was regenerated.'));
    } catch (Exception $e) {
        Misc::setMessage(ev_gettext('Private key regeneration error. Check server error logs.'), Misc::MSG_ERROR);
    }
}

$tpl->displayTemplate();
