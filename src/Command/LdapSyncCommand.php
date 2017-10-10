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

namespace Eventum\Command;

use AuthException;
use LDAP_Auth_Backend;
use RuntimeException;
use Setup;
use Symfony\Component\Console\Output\OutputInterface;

class LdapSyncCommand
{
    const DEFAULT_COMMAND = 'ldap:sync';
    const USAGE = self::DEFAULT_COMMAND;

    /** @var OutputInterface */
    private $output;

    /** @var  LDAP_Auth_Backend */
    private $ldap;

    public function execute(OutputInterface $output)
    {
        $this->assertLdapAuthEnabled();
        $this->output = $output;

        $this->ldap = new LDAP_Auth_Backend();
        $this->ldapSync();
    }

    private function ldapSync()
    {
        if ($dn = $this->ldap->active_dn) {
            $this->updateUsers($this->findUsers($dn));
        }

        if ($dn = $this->ldap->inactive_dn) {
            $this->disableUsers($this->findUsers($dn));
        }
    }

    /**
     * add new users and update existing users
     *
     * @param \Net_LDAP2_Entry[] $users
     */
    private function updateUsers($users)
    {
        foreach ($users as $entry) {
            $uid = $entry->getValue('uid');
            $dn = $entry->dn();

            // FIXME: where's adding new users part?
            // TODO: check if ldap enabled and eventum disabled activates accounts in eventum
            echo "checking: $uid, $dn\n";
            try {
                $this->ldap->updateLocalUserFromBackend($uid);
            } catch (AuthException $e) {
                // this likely logs that user doesn't exist and will not be created
                error_log("XX: $uid: " . $e->getMessage());
            }
        }
    }

    /**
     * Process inactive users from ldap
     *
     * @param \Net_LDAP2_Entry[] $users
     */
    private function disableUsers($users)
    {
        foreach ($users as $entry) {
            $uid = $entry->getValue('uid');
            $dn = $entry->dn();
            $active = $this->ldap->accountActive($uid);

            // handle unmapped users
            if ($active === null) {
                // try to find user
                $remote = $this->ldap->getRemoteUserInfo($uid);
                $usr_id = $this->ldap->getLocalUserId($uid, $remote['emails']);
                // first update user to setup "external_id" mapping
                if ($usr_id) {
                    $this->ldap->updateLocalUserFromBackend($uid);
                    // fetch user again
                    $active = $this->ldap->accountActive($uid);
                }
            }

            if ($active === true) {
                echo "disabling: $uid, $dn\n";
                $res = $this->ldap->disableAccount($uid);
                if ($res !== true) {
                    throw new RuntimeException("Account disable for $uid ($dn) failed");
                }
            }
        }
    }

    /**
     * Find users under specified DN.
     *
     * @param string $dn
     * @return \Generator|\Net_LDAP2_Entry[]
     */
    private function findUsers($dn)
    {
        $search = $this->ldap->getUserListing($dn);

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
    }

    private function assertLdapAuthEnabled()
    {
        if (strtolower(APP_AUTH_BACKEND) !== 'ldap_auth_backend') {
            throw new RuntimeException('You should enable and configure LDAP backend first');
        }
    }
}
