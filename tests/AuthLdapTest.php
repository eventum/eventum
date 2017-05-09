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

namespace Eventum\Test;

use LDAP_Auth_Backend;

/**
 * @group ldap
 */
class AuthLdapTest extends TestCase
{
    /** @var LDAP_Auth_Backend */
    public static $ldap;

    public static function setupBeforeClass()
    {
        self::$ldap = new LDAP_Auth_Backend();
    }

    public function testLdapSearch()
    {
        $search = self::$ldap->getUserListing();
        while ($entry = $search->shiftEntry()) {
            $uid = $entry->getValue('uid');
            $dn = $entry->dn();

            // if no email, skip completely
            $emails = $entry->get_value('mail', 'all');
            if (!$emails) {
                echo "skip (no email): $uid, $dn\n";
                continue;
            }
            echo "user: $uid, $dn\n";
        }
    }
}
