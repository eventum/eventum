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
$tpl->setTemplate('get_emails_ajax.tpl.html');

Auth::checkAuthentication(null, true);

// we need the IMAP extension for this to work
if (!function_exists('imap_open')) {
    $tpl->assign('error', 'imap_extension_missing');
} else {
    // check if the hostname is just an IP based one
    if ((!preg_match("/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/", $_POST['hostname'])) &&
            (gethostbyname($_POST['hostname']) == $_POST['hostname'])) {
        $tpl->assign('error', 'hostname_resolv_error');
    } else {
        $account = array(
            'ema_hostname' => $_POST['hostname'],
            'ema_port'     => $_POST['port'],
            'ema_type'     => $_POST['type'],
            'ema_folder'   => Misc::ifSet($_POST, 'folder'),
            'ema_username' => $_POST['username'],
            'ema_password' => $_POST['password'],
        );
        $mbox = Support::connectEmailServer($account);
        if (!$mbox) {
            $tpl->assign('error', 'could_not_connect');
        } else {
            $tpl->assign('error', 'no_error');
        }
    }
}

$tpl->displayTemplate();
