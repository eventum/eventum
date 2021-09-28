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

namespace Eventum\Console\Command;

use Eventum\Auth\Adapter\LdapAdapter;
use Eventum\Auth\AuthException;
use Eventum\Auth\Ldap\UserEntry;
use Generator;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LdapSyncCommand extends BaseCommand
{
    public const DEFAULT_COMMAND = 'ldap:sync';

    protected static $defaultName = 'eventum:' . self::DEFAULT_COMMAND;

    /** @var LdapAdapter */
    private $ldap;

    /** @var bool */
    private $dryrun;

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE)
            ->addOption('create-users', null, InputOption::VALUE_NONE)
            ->addOption('no-update', null, InputOption::VALUE_NONE)
            ->addOption('no-disable', null, InputOption::VALUE_NONE)
            ->addOption('no-notify', null, InputOption::VALUE_NONE);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $dryrun = $input->getOption('dry-run');
        $createUsers = $input->getOption('create-users');
        $noUpdate = $input->getOption('no-update');
        $noDisable = $input->getOption('no-disable');
        $noNotify = $input->getOption('no-notify');

        $this->dryrun = $dryrun;

        $this->ldap = new LdapAdapter();
        $this->ldap->create_users = $createUsers;

        if ($noNotify) {
            $this->ldap->notify_users = false;
        }

        $this->updateUsers(!$noUpdate);
        $this->disableUsers(!$noDisable);

        return 0;
    }

    /**
     * add new users and update existing users
     *
     * @param bool $enabled
     */
    private function updateUsers($enabled): void
    {
        if (!$enabled || !$this->ldap->active_dn) {
            $this->writeln('Skipping update users');

            return;
        }

        $users = $this->findUsers($this->ldap->active_dn);
        $this->writeln('Checking active LDAP users');
        foreach ($users as $user) {
            $uid = $user->getUid();
            $dn = $user->getDn();

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
    private function disableUsers($enabled): void
    {
        if (!$enabled || !$this->ldap->inactive_dn) {
            $this->writeln('Skipping disable users');

            return;
        }

        $users = $this->findUsers($this->ldap->inactive_dn);
        $this->writeln('Checking inactive LDAP users');
        foreach ($users as $user) {
            $uid = $user->getUid();
            $dn = $user->getDn();

            $this->writeln("checking: $uid, $dn", self::VERY_VERBOSE);

            $active = $this->ldap->accountActive($uid);

            // handle unmapped users
            if ($active === null) {
                // try to find user
                $remote = $this->ldap->getLdapUser($uid);
                if ($remote === null) {
                    throw new InvalidArgumentException('Unexpected null');
                }
                $usr_id = $this->ldap->getLocalUserId($uid, $remote->getEmails());
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
     * @return Generator|UserEntry[]
     */
    private function findUsers($dn): Generator
    {
        $users = $this->ldap->getUserListing($dn);
        foreach ($users as $user) {
            $emails = $user->getEmails();
            // skip entries with no email
            if (!$this->hasValidEmail($emails)) {
                $uid = $user->getUid();
                $this->writeln("skip (no email): $uid", self::VERBOSE);
                continue;
            }

            yield $user;
        }
    }

    /**
     * Return true if at least one valid email found
     *
     * @param string[] $emails
     * @return bool
     */
    private function hasValidEmail(array $emails): bool
    {
        foreach ($emails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return true;
            }
        }

        return false;
    }

    /**
     * proxy to do nothing when dry-run mode active
     *
     * @param string $uid
     */
    private function updateLocalUserFromBackend($uid): void
    {
        if ($this->dryrun) {
            $this->writeln("<info>would run</info> updateLocalUserFromBackend($uid)");

            return;
        }
        $this->ldap->updateLocalUserFromBackend($uid);
    }

    private function disableAccount($dn, $uid): void
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
}
