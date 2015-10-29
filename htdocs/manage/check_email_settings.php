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

require_once __DIR__ . '/../../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate('get_emails.tpl.html');

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
            'ema_folder'   => $_POST['folder'],
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
