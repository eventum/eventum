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

use Eventum\Auth\Ldap\LdapConnection;
use Eventum\Auth\Ldap\UserEntry;
use Eventum\Monolog\Logger;
use Symfony\Component\Ldap\Entry;

/**
 * This auth backend integrates with an LDAP server and if set to, will create
 * a local user with the specified name and email. The user will be
 * authenticated against the LDAP server on each login.
 *
 * This backend will look for users in the default mysql backend if no LDAP
 * user is found. This behaviour may be configurable in the future.
 *
 * Set define('APP_AUTH_BACKEND', 'LDAP_Auth_Backend') in the config file and
 * then fill in the LDAP server details in manage
 */
class LDAP_Auth_Backend implements Auth_Backend_Interface
{
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

    /**
     * configures LDAP
     *
     * @throws AuthException if failed LDAP bind failed
     */
    public function __construct()
    {
        $this->logger = Logger::auth();
        $this->ldap = new LdapConnection(Setup::get()['ldap']);

        $setup = Setup::get()['ldap'];

        $this->active_dn = $setup['active_dn'];
        $this->inactive_dn = $setup['inactive_dn'];
        $this->create_users = (bool)$setup['create_users'];
    }

    /**
     * Get all users from LDAP server.
     *
     * @param string $dn
     * @return UserEntry[]
     * @internal Public for use by LdapSyncCommand
     */
    public function getUserListing($dn)
    {
        return $this->ldap->listUsers($dn);
    }

    /**
     * @param string $uid
     * @param string $password
     * @return bool
     */
    private function validatePassword($uid, $password)
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
    public function getLdapUser($uid)
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
    public function getLocalUserId($login, $emails)
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
    public function disableAccount($uid)
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
    public function accountActive($uid)
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
    private function sortEmails($usr_id, $emails)
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
     * @return  bool True if the user was created or updated, false otherwise
     */
    public function updateLocalUserFromBackend($login)
    {
        $remote = $this->getLdapUser($login);
        if (!$remote) {
            return false;
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
            return false;
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
    private function createUser(UserEntry $remote)
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

        $data['role'] = Setup::get()->ldap->default_role;

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
    public function getUserIDByLogin($login)
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
    public function userExists($login)
    {
        $usr_id = $this->getUserIDByLogin($login);

        return $usr_id > 0;
    }

    /**
     * @param int $usr_id
     * @return bool
     */
    private function isLDAPUser($usr_id)
    {
        $local_user_info = User::getDetails($usr_id);

        return !empty($local_user_info['usr_external_id']);
    }

    /**
     * {@inheritdoc}
     */
    public function verifyPassword($login, $password)
    {
        // check if this is an ldap or internal
        $usr_id = $this->getUserIDByLogin($login);
        $local_user_info = User::getDetails($usr_id);
        if (empty($local_user_info['usr_external_id'])) {
            return Auth::getFallBackAuthBackend()->verifyPassword($login, $password);
        }

        $user_info = $this->validatePassword($local_user_info['usr_external_id'], $password);

        return $user_info != null;
    }

    /**
     * {@inheritdoc}
     */
    public function canUserUpdateName($usr_id)
    {
        $external_id = User::getExternalID($usr_id);
        if (empty($external_id)) {
            return Auth::getFallBackAuthBackend()->canUserUpdateName($usr_id);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function canUserUpdateEmail($usr_id)
    {
        $external_id = User::getExternalID($usr_id);
        if (empty($external_id)) {
            return Auth::getFallBackAuthBackend()->canUserUpdateEmail($usr_id);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function canUserUpdatePassword($usr_id)
    {
        $external_id = User::getExternalID($usr_id);
        if (empty($external_id)) {
            return Auth::getFallBackAuthBackend()->canUserUpdatePassword($usr_id);
        }

        return false;
    }

    /**
     * Method used to get the system-wide defaults.
     *
     * @return array of the default parameters
     */
    public static function getDefaults()
    {
        // to avoid dead-loop,
        // don't do anything complex here that would require loading setup
        return [
            'host' => 'localhost',
            'port' => '389',
            'binddn' => '',
            'bindpw' => '',
            'basedn' => 'dc=example,dc=org',
            'user_id_attribute' => '',
            'userdn' => 'uid=%UID%,ou=People,dc=example,dc=org',
            'customer_id_attribute' => '',
            'contact_id_attribute' => '',
            'user_filter' => '',
            'create_users' => null,
            'active_dn' => 'ou=People,dc=example,dc=org',
            'inactive_dn' => 'ou=Inactive Accounts,dc=example,dc=org',
            'default_role' => [],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function updatePassword($usr_id, $password)
    {
        if (!$this->isLDAPUser($usr_id)) {
            return Auth::getFallBackAuthBackend()->updatePassword($usr_id, $password);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function incrementFailedLogins($usr_id)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function resetFailedLogins($usr_id)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isUserBackOffLocked($usr_id)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getExternalLoginURL()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function checkAuthentication()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function logout()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function autoRedirectToExternalLogin()
    {
        return false;
    }
}
