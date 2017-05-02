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

$ldap = new LDAP_Auth_Backend();

$findUsers = function ($dn) use ($ldap) {
    $search = $ldap->getUserListing($dn);

    while ($entry = $search->shiftEntry()) {
        // skip entries with no email
        $emails = $entry->get_value('mail', 'all');
        if (!$emails) {
            $uid = $entry->getValue('uid');
            echo "skip (no email): $uid, $dn\n";
            continue;
        }

        yield $entry;
    }
};

// process active users from ldap
foreach ($findUsers($active_dn) as $entry) {
    $uid = $entry->getValue('uid');
    $dn = $entry->dn();

    // FIXME: where's adding new users part?
    echo "checking: $uid, $dn\n";
    try {
        $ldap->updateLocalUserFromBackend($uid);
    } catch (Exception $e) {
        error_log("$uid: " . $e->getMessage());
    }
}

// process inactive users from ldap
foreach ($findUsers($inactive_dn) as $entry) {
    $uid = $entry->getValue('uid');
    $dn = $entry->dn();

    if ($ldap->accountActive($uid) === true) {
        echo "disabling: $uid, $dn\n";
        $ldap->disableAccount($uid);
    }
}
