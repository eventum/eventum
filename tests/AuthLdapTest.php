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

use Eventum\Auth\Ldap\LdapConnection;
use LDAP_Auth_Backend;
use Setup;
use Zend\Config\Config;

/**
 * @group ldap
 */
class AuthLdapTest extends TestCase
{
    /** @var LDAP_Auth_Backend */
    private $ldap;

    /** @var \Eventum\Auth\Ldap\LdapConnection */
    private $connection;

    /** @var Config */
    private $config;

    public function setUp()
    {
        $this->config = Setup::get()['ldap'];
        $this->connection = new LdapConnection($this->config);
    }

    public function testLdapAdapter()
    {
        $users = $this->connection->listUsers();
        $expanded = iterator_to_array($users);
    }

    public function test2()
    {
        $uid = 'glen';
        $entry = $this->connection->findUser($uid);
        dump($entry);
    }

    public function testLdapSearch()
    {
        $ldap = new LDAP_Auth_Backend();

        $search = $ldap->getUserListing();
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
