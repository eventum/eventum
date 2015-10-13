#!/usr/bin/php
<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2014-2015 Eventum Team.                                |
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
// | Authors: Bryan Alsdorf <bryan@mysql.com>                             |
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

require_once __DIR__ . '/../init.php';

if (APP_AUTH_BACKEND != 'ldap_auth_backend') {
    error_log('You should enable and configure LDAP backend first');
    exit(1);
}

/**
 * Get the new user information from the LDAP servers
 */

$active_dn = 'ou=People,dc=example,dc=net';
$inactive_dn = 'ou=Inactive Accounts,dc=example,dc=net';

$backend = new LDAP_Auth_Backend();
$search = $backend->getUserListing();

while ($entry = $search->shiftEntry()) {
    $uid = $entry->getValue('uid');
    $dn = $entry->dn();

    // if no email, skip completely
    $emails = $entry->get_value('mail', 'all');
    if (!$emails) {
        echo "skip (no email): $uid, $dn\n";
        continue;
    }

//    if ($uid != 'telvislightuploader') {
//        continue;
//    }

    $suffix = substr($dn, -strlen($inactive_dn));
    if ($suffix == $inactive_dn) {
        echo "disabling: $uid, $dn\n";
        $backend->disableAccount($uid);
    }
    $suffix = substr($dn, -strlen($active_dn));
    if ($suffix == $active_dn) {
        $active = true;
        echo "updating: $uid, $dn\n";
        $backend->updateLocalUserFromBackend($uid);
    }
}
