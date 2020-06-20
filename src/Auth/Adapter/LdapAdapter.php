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

namespace Eventum\Auth\Adapter;

use Auth;
use Eventum\Auth\AuthException;
use Eventum\Auth\Ldap\LdapConnection;
use Eventum\Auth\Ldap\UserEntry;
use Eventum\Monolog\Logger;
use Eventum\ServiceContainer;
use Generator;
use Symfony\Component\Ldap\Exception\ConnectionException;
use User;

/**
 * This auth backend integrates with an LDAP server and if set to, will create
 * a local user with the specified name and email. The user will be
 * authenticated against the LDAP server on each login.
 */
class LdapAdapter implements AdapterInterface
{
    public const displayName = 'LDAP authentication adapter';

    /** @var bool */
    public $create_users;

    /** @var \Monolog\Logger */
    private $logger;

    /** @var LdapConnection */
    private $ldap;

    /**
     * DN under what Active users are stored
     *
     * @var string
     */
    public $active_dn;

    /**
     * DN under what Inactive users are stored
     *
     * @var string
     */
    public $inactive_dn;

    /** @var array */
    private $default_role;

    /**
     * configures LDAP
     *
     * @throws AuthException if failed LDAP bind failed
     */
    public function __construct()
    {
        $config = ServiceContainer::getConfig()['ldap'];
        $this->logger = Logger::auth();
        $this->ldap = new LdapConnection($config);
        $this->active_dn = $config['active_dn'];
        $this->inactive_dn = $config['inactive_dn'];
        $this->default_role = $config['default_role'];
        $this->create_users = (bool)$config['create_users'];

        if (!$this->active_dn || !$this->inactive_dn) {
            throw new AuthException('LDAP Adapter not configured');
        }
    }

    /**
     * Get all users from LDAP server.
     *
     * @param string $dn
     * @return Generator|UserEntry[]
     * @internal Public for use by LdapSyncCommand
     */
    public function getUserListing(string $dn): Generator
    {
        yield from $this->ldap->listUsers($dn);
    }

    private function validatePassword(string $uid, string $password): bool
    {
        $errors = $this->ldap->checkAuthentication($uid, $password);
        if ($errors === true) {
            return $errors;
        }

        foreach ($errors as $e) {
            Auth::saveLoginAttempt($uid, 'failure', $e->getMessage());
        }

        return false;
    }

    /**
     * Retrieve information from LDAP
     *
     * @param string $uid login or email
     * @return UserEntry|null
     * @internal Public for use by LdapSyncCommand
     */
    public function getLdapUser(string $uid): ?UserEntry
    {
        return $this->ldap->findUser($uid);
    }

    /**
     * Get local user by login or by emails
     *
     * @param string $login
     * @param string[] $emails
     * @return int|null
     * @internal Public for use by LdapSyncCommand
     */
    public function getLocalUserId(string $login, array $emails): ?int
    {
        // try by login name
        $usr_id = User::getUserIDByExternalID($login);
        if ($usr_id) {
            return $usr_id;
        }

        // find local user by email by ALL aliases from remote system
        foreach ($emails as $email) {
            $usr_id = User::getUserIDByEmail($email, true);
            if ($usr_id) {
                return $usr_id;
            }
        }

        return null;
    }

    /**
     * Disable account by external id.
     *
     * @param string $uid
     * @throws AuthException if the account was not active
     * @return bool
     * @internal Public for use by LdapSyncCommand
     */
    public function disableAccount(string $uid): bool
    {
        $usr_id = User::getUserIDByExternalID($uid);
        if ($usr_id <= 0) {
            return false;
        }

        if ($this->accountActive($uid) !== true) {
            throw new AuthException("User[usr_id=$usr_id; external_id=$uid] status is not active");
        }

        return User::changeStatus($usr_id, User::USER_STATUS_INACTIVE);
    }

    /**
     * Return true if external uid is locally active user,
     * returns NULL if local user not found.
     *
     * @param string $uid external_id
     * @return null|bool
     * @internal Public for use by LdapSyncCommand
     */
    public function accountActive(string $uid): ?bool
    {
        $usr_id = User::getUserIDByExternalID($uid);
        if ($usr_id <= 0) {
            return null;
        }

        $details = User::getDetails($usr_id);
        $status = $details['usr_status'];

        return $status === User::USER_STATUS_ACTIVE;
    }

    /**
     * Sort user emails so that primary is what Eventum has as primary
     * Perspective what is main address and what is alias may be different in ldap and in Eventum.
     *
     * @param int $usr_id
     * @param array $emails
     * @return string[]
     */
    private function sortEmails(int $usr_id, array $emails): array
    {
        $email = User::getEmail($usr_id);

        if (($key = array_search($email, $emails, true)) !== false) {
            // email was found, ensure it's first item
            unset($emails[$key]);
            array_unshift($emails, $email);
        }

        return $emails;
    }

    /**
     * Creates or updates local user entry for the specified ID.
     *
     * @param string $login The login or email of the user to create or update
     * @return int the user id that was created or updated, null otherwise
     */
    public function updateLocalUserFromBackend(string $login): ?int
    {
        $remote = $this->getLdapUser($login);
        if (!$remote) {
            return null;
        }

        $usr_id = $this->getLocalUserId($login, $remote->getEmails());

        $data = [
            // do not add 'password' field here.
            // it maybe be set locally before ldap
            // and we don't want to store it in mysql at all
            'full_name' => $remote->getFullName(),
            'external_id' => $remote->getUid(),
            'customer_id' => $remote->getCustomerId(),
            'contact_id' => $remote->getContactId(),
        ];

        // if local user found, update it and return usr id
        if ($usr_id) {
            $emails = $this->sortEmails($usr_id, $remote->getEmails());
            if (!$emails) {
                throw new AuthException('E-mail is required');
            }
            // use first email as primary from sorted list
            $data['email'] = array_shift($emails);

            // do not clear full name if for some reason it is empty
            if (empty($data['full_name'])) {
                unset($data['full_name']);
            }

            // read in details, and make modification only if data has changed
            $user_details = User::getDetails($usr_id);
            $aliases = User::getAliases($usr_id);
            $stored_data = [
                'full_name' => $user_details['usr_full_name'],
                'external_id' => $user_details['usr_external_id'],
                'customer_id' => $user_details['usr_customer_id'],
                'contact_id' => $user_details['usr_customer_contact_id'],
                'email' => $user_details['usr_email'],
            ];
            $remove_aliases = [];

            $diff = array_diff_assoc($data, $stored_data);
            if ($diff) {
                $diff = array_diff_assoc($data, $stored_data);
                // if email is about to be updated, move current one to aliases
                if (isset($diff['email'], $stored_data['email'])) {
                    $this->logger->debug("add alias:{$stored_data['email']}");

                    $emails[] = $stored_data['email'];

                    // if new email is present in aliases remove it from there
                    if (($key = array_search($data['email'], $aliases, true)) !== false) {
                        $this->logger->debug("remove alias:{$aliases[$key]}");
                        $remove_aliases[] = $aliases[$key];
                    }
                }

                $fdiff = json_encode($diff, JSON_UNESCAPED_UNICODE);
                $this->logger->debug("update data: $usr_id: $fdiff");

                User::update($usr_id, $data, false);
            }

            // as we are only adding aliases (never removing)
            // check only one way
            if (array_diff($emails, $aliases)) {
                $diff = implode(',', array_diff($aliases, $emails));
                $this->logger->debug("update aliases: $usr_id: $diff");

                $res = $this->updateAliases($usr_id, $emails);
                if (!$res) {
                    $this->logger->error('aliases update failed');
                }
            }

            if ($remove_aliases) {
                $this->logger->debug('remove aliases: ' . implode(',', $remove_aliases));
                foreach ($remove_aliases as $email) {
                    User::removeAlias($usr_id, $email);
                }
            }

            return $usr_id;
        }

        if (!$this->create_users) {
            return null;
        }

        return $this->createUser($remote);
    }

    /**
     * Create new local user.
     *
     * @param UserEntry $remote
     * @throws AuthException
     * @return int usr_id
     */
    private function createUser(UserEntry $remote): int
    {
        $emails = $remote->getEmails();
        if (!$emails) {
            throw new AuthException('E-mail is required');
        }

        $data = [
            'full_name' => $remote->getFullName(),
            'external_id' => $remote->getUid(),
            'customer_id' => $remote->getCustomerId(),
            'contact_id' => $remote->getContactId(),
        ];

        // set first email as default
        $data['email'] = array_shift($emails);

        $data['role'] = $this->default_role;

        if (!empty($data['customer_id']) && !empty($data['contact_id'])) {
            foreach ($data['role'] as $prj_id => $role) {
                if ($role > 0) {
                    $data['role'][$prj_id] = User::ROLE_CUSTOMER;
                }
            }
        }

        $usr_id = User::insert($data);
        if ($usr_id > 0 && $emails) {
            $this->updateAliases($usr_id, $emails);
        }

        return $usr_id;
    }

    /**
     * @param int $usr_id
     * @return bool returns true if all aliases were added
     */
    private function updateAliases($usr_id, $aliases)
    {
        $updated = 0;
        foreach ($aliases as $alias) {
            $res = User::addAlias($usr_id, $alias);
            if (!$res) {
                $this->logger->error("updating $alias failed");
            } else {
                $updated++;
            }
        }

        return $updated === count($aliases);
    }

    /**
     * {@inheritdoc}
     */
    public function getUserId(string $login): ?int
    {
        $usr_id = User::getUserIDByEmail($login, true);
        if (!$usr_id) {
            // the login is not a local email address, try external id
            $usr_id = User::getUserIDByExternalID($login);
        }

        if ($usr_id) {
            $local_user_info = User::getDetails($usr_id);
        }

        if (!empty($local_user_info) && empty($local_user_info['usr_external_id'])) {
            // local user exists and is not associated with LDAP, don't try to update.
            return $usr_id;
        }

        // try to create or update local user from ldap info
        return $this->updateLocalUserFromBackend($login);
    }

    /**
     * {@inheritdoc}
     */
    public function userExists(string $login): bool
    {
        try {
            $usr_id = $this->getUserId($login);
        } catch (ConnectionException $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);

            return false;
        }

        return $usr_id > 0;
    }

    private function hasExternalId(int $usr_id): bool
    {
        $external_id = User::getExternalID($usr_id) ?? null;

        return $external_id !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function verifyPassword(string $login, string $password): bool
    {
        // check if this is an ldap or internal
        $usr_id = $this->getUserId($login);
        $external_id = User::getExternalID($usr_id) ?? null;

        if (!$external_id) {
            return false;
        }

        return $this->validatePassword($external_id, $password);
    }

    /**
     * {@inheritdoc}
     */
    public function canUserUpdateName(int $usr_id): bool
    {
        return $this->hasExternalId($usr_id);
    }

    /**
     * {@inheritdoc}
     */
    public function canUserUpdateEmail(int $usr_id): bool
    {
        return $this->hasExternalId($usr_id);
    }

    /**
     * {@inheritdoc}
     */
    public function canUserUpdatePassword(int $usr_id): bool
    {
        return $this->hasExternalId($usr_id);
    }

    /**
     * {@inheritdoc}
     */
    public function updatePassword(int $usr_id, string $password): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getExternalLoginURL(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function checkAuthentication(): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function logout(): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function autoRedirectToExternalLogin(): bool
    {
        return false;
    }
}
