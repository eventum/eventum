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
use Symfony\Component\Console\Output\OutputInterface;

class LdapSyncCommand extends BaseCommand
{
    const DEFAULT_COMMAND = 'ldap:sync';
    const USAGE = self::DEFAULT_COMMAND . ' [--dry-run] [--create-users] [--no-update] [--no-disable]';

    /** @var LDAP_Auth_Backend */
    private $ldap;

    /** @var bool */
    private $dryrun;

    public function execute(OutputInterface $output, $dryrun = false, $createUsers, $noUpdate, $noDisable)
    {
        $this->assertLdapAuthEnabled();
        $this->output = $output;
        $this->dryrun = $dryrun;

        $this->ldap = new LDAP_Auth_Backend();
        $this->ldap->create_users = $createUsers;
        $this->updateUsers(!$noUpdate);
        $this->disableUsers(!$noDisable);
    }

    /**
     * add new users and update existing users
     *
     * @param bool $enabled
     */
    private function updateUsers($enabled)
    {
        if (!$enabled || !$this->ldap->active_dn) {
            $this->writeln('Skipping update users');

            return;
        }

        $users = $this->findUsers($this->ldap->active_dn);
        $this->writeln('Checking active LDAP users');
        foreach ($users as $entry) {
            $uid = $entry->getValue('uid');
            $dn = $entry->dn();

            // FIXME: where's adding new users part?
            // TODO: check if ldap enabled and eventum disabled activates accounts in eventum
            $this->writeln("checking: $uid, $dn", self::VERY_VERBOSE);
            try {
                $this->updateLocalUserFromBackend($uid);
            } catch (AuthException $e) {
                $this->writeln("<error>ERROR</error>: <info>$uid</info>: {$e->getMessage()}");
            }
        }
    }

    /**
     * Process inactive users from ldap
     *
     * @param bool $enabled
     */
    private function disableUsers($enabled)
    {
        if (!$enabled || !$this->ldap->inactive_dn) {
            $this->writeln('Skipping disable users');

            return;
        }

        $users = $this->findUsers($this->ldap->inactive_dn);
        $this->writeln('Checking inactive LDAP users');
        foreach ($users as $entry) {
            $uid = $entry->getValue('uid');
            $dn = $entry->dn();

            $this->writeln("checking: $uid, $dn", self::VERY_VERBOSE);

            $active = $this->ldap->accountActive($uid);

            // handle unmapped users
            if ($active === null) {
                // try to find user
                $remote = $this->ldap->getRemoteUserInfo($uid);
                $usr_id = $this->ldap->getLocalUserId($uid, $remote['emails']);
                // first update user to setup "external_id" mapping
                if ($usr_id) {
                    $this->updateLocalUserFromBackend($uid);
                    // fetch user again
                    $active = $this->ldap->accountActive($uid);
                }
            }

            if ($active === true) {
                $this->disableAccount($dn, $uid);
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
                $this->writeln("skip (no email): $uid, $dn", self::VERBOSE);
                continue;
            }

            yield $entry;
        }
    }

    /**
     * proxy to do nothing when dry-run mode active
     *
     * @param string $uid
     */
    private function updateLocalUserFromBackend($uid)
    {
        if ($this->dryrun) {
            $this->writeln("<info>would run</info> updateLocalUserFromBackend($uid)");

            return;
        }
        $this->ldap->updateLocalUserFromBackend($uid);
    }

    private function disableAccount($dn, $uid)
    {
        if ($this->dryrun) {
            $this->writeln("<info>would run</info> disableAccount($uid)");

            return;
        }

        $this->writeln("disabling: $uid, $dn");
        $res = $this->ldap->disableAccount($uid);
        if ($res !== true) {
            throw new RuntimeException("Account disable for $uid ($dn) failed");
        }
    }

    private function assertLdapAuthEnabled()
    {
        if (strtolower(APP_AUTH_BACKEND) !== 'ldap_auth_backend') {
            throw new RuntimeException('You should enable and configure LDAP backend first');
        }
    }
}
