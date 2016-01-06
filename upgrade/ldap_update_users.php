#!/usr/bin/php
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

if (strtolower(APP_AUTH_BACKEND) != 'ldap_auth_backend') {
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
