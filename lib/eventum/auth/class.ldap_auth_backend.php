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
    /** @var string */
    protected $basedn;
    /** @var string */
    protected $user_dn_string;
    /** @var string */
    protected $customer_id_attribute;
    /** @var string */
    protected $contact_id_attribute;

    /**
     * configures LDAP
     *
     * @throws AuthException if failed LDAP bind failed
     */
    public function __construct()
    {
        $setup = Setup::get()->ldap;

        $this->basedn = $setup['basedn'];
        $this->user_dn_string = $setup['userdn'];
        $this->user_filter_string = $setup['user_filter'];
        $this->customer_id_attribute = $setup['customer_id_attribute'];
        $this->contact_id_attribute = $setup['contact_id_attribute'];
    }

    /**
     * Create LDAP connection.
     *
     * @return Net_LDAP2
     */
    protected function connect()
    {
        static $conn;
        if (!$conn) {
            $setup = Setup::get()->ldap;

            $options = [
                'host' => $setup['host'],
                'port' => $setup['port'],
                'binddn' => $setup['binddn'],
                'bindpw' => $setup['bindpw'],
                'basedn' => $this->basedn,
            ];

            $conn = Net_LDAP2::connect($options);
            if (Misc::isError($conn)) {
                throw new AuthException($conn->getMessage(), $conn->getCode());
            }
        }

        return $conn;
    }

    /**
     * Get all users from LDAP server
     *
     * @return Net_LDAP2_Search|Net_LDAP2_Error Net_LDAP2_Search object or Net_LDAP2_Error object
     */
    public function getUserListing()
    {
        $filter = Net_LDAP2_Filter::create('uid', 'equals', '*', false);
        if (!empty($this->user_filter_string)) {
            $user_filter = Net_LDAP2_Filter::parse($this->user_filter_string);
            $filter = Net_LDAP2_Filter::combine('and', [$filter, $user_filter]);
        }

        $search = $this->connect()->search($this->basedn, $filter);

        if (Misc::isError($search)) {
            throw new AuthException($search->getMessage(), $search->getCode());
        }

        return $search;
    }

    private function validatePassword($uid, $password)
    {
        $errors = [];

        foreach (explode('|', $this->getUserDNstring($uid)) as $userDNstring) {
            // Connecting using the configuration
            try {
                $res = $this->connect()->bind($userDNstring, $password);
                if (Misc::isError($res)) {
                    throw new AuthException($res->getMessage(), $res->getCode());
                }

                return $res;
            } catch (AuthException $e) {
                $errors[] = $e;
            }
        }

        foreach ($errors as $e) {
            /** @var Exception $e */
            Auth::saveLoginAttempt($uid, 'failure', $e->getMessage());
        }

        return false;
    }

    /**
     * Retrieve information from LDAP
     *
     * @param string $uid login or email
     * @return array
     */
    public function getRemoteUserInfo($uid)
    {
        if (strpos($uid, '@') === false) {
            $filter = Net_LDAP2_Filter::create('uid', 'equals', $uid);
        } else {
            $filter = Net_LDAP2_Filter::create('mail', 'equals', $uid);
        }
        if (!empty($this->user_filter_string)) {
            $user_filter = Net_LDAP2_Filter::parse($this->user_filter_string);
            $filter = Net_LDAP2_Filter::combine('and', [$filter, $user_filter]);
        }
        $search = $this->connect()->search($this->basedn, $filter, ['sizelimit' => 1]);
        $entry = $search->shiftEntry();

        if (!$entry || Misc::isError($entry)) {
            return null;
        }

        $details = [
            'uid' => $entry->get_value('uid'),
            'full_name' => Misc::trim($entry->get_value('cn')),
            'emails' => Misc::trim(Misc::lowercase($entry->get_value('mail', 'all'))),
            'customer_id' => Misc::trim($entry->get_value($this->customer_id_attribute)) ?: null,
            'contact_id' => Misc::trim($entry->get_value($this->contact_id_attribute)) ?: null,
        ];

        return $details;
    }

    protected function getUserDNstring($uid)
    {
        return str_replace('%UID%', $uid, $this->user_dn_string);
    }

    /**
     * Get local user by login or by emails
     *
     * @param string $login
     * @param string[] $emails
     * @return int|null
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

    public function disableAccount($uid)
    {
        $usr_id = User::getUserIDByExternalID($uid);
        if ($usr_id <= 0) {
            return false;
        }

        return User::changeStatus($usr_id, User::USER_STATUS_INACTIVE);
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

        if (($key = array_search($email, $emails)) !== false) {
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
        $remote = $this->getRemoteUserInfo($login);
        if (!$remote) {
            return false;
        }

        $usr_id = $this->getLocalUserId($login, $remote['emails']);

        $data = [
            // do not add 'password' field here.
            // it maybe be set locally before ldap
            // and we don't want to store it in mysql at all
            'full_name' => $remote['full_name'],
            'external_id' => $remote['uid'],
            'customer_id' => $remote['customer_id'],
            'contact_id' => $remote['contact_id'],
        ];

        // if local user found, update it and return usr id
        if ($usr_id) {
            $emails = $this->sortEmails($usr_id, $remote['emails']);
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
                if (isset($diff['email']) && isset($stored_data['email'])) {
                    $emails[] = $stored_data['email'];

                    // if new email is present in aliases remove it from there
                    if (($key = array_search($data['email'], $aliases)) !== false) {
                        $remove_aliases[] = $aliases[$key];
                    }
                }

                User::update($usr_id, $data, false);
            }

            // as we are only adding aliases (never removing)
            // check only one way
            if (array_diff($emails, $aliases)) {
                $res = $this->updateAliases($usr_id, $emails);
                if (!$res) {
                    error_log('aliases update failed');
                }
            }

            if ($remove_aliases) {
                foreach ($remove_aliases as $email) {
                    User::removeAlias($usr_id, $email);
                }
            }

            return $usr_id;
        }

        return $this->createUser($remote);
    }

    /**
     * Create new local user.
     *
     * @param array $remote
     * @return int usr_id
     */
    private function createUser($remote)
    {
        $emails = $remote['emails'];
        if (!$emails) {
            throw new AuthException('E-mail is required');
        }

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
     * @return true if all aliases were added
     */
    private function updateAliases($usr_id, $aliases)
    {
        $updated = 0;
        foreach ($aliases as $alias) {
            $res = User::addAlias($usr_id, $alias);
            if (!$res) {
                error_log("updating $alias failed");
            } else {
                $updated++;
            }
        }

        return $updated === count($aliases);
    }

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
        $created = $this->updateLocalUserFromBackend($login);

        return $created;
    }

    public function userExists($login)
    {
        $usr_id = $this->getUserIDByLogin($login);

        return $usr_id > 0;
    }

    private function isLDAPuser($usr_id)
    {
        $local_user_info = User::getDetails($usr_id);
        if (empty($local_user_info['usr_external_id'])) {
            return false;
        } else {
            return true;
        }
    }

    public function verifyPassword($login, $password)
    {
        // check if this is an ldap or internal
        $usr_id = self::getUserIDByLogin($login);
        $local_user_info = User::getDetails($usr_id);
        if (empty($local_user_info['usr_external_id'])) {
            return Auth::getFallBackAuthBackend()->verifyPassword($login, $password);
        }

        $user_info = $this->validatePassword($local_user_info['usr_external_id'], $password);

        return $user_info != null;
    }

    public function canUserUpdateName($usr_id)
    {
        $external_id = User::getExternalID($usr_id);
        if (empty($external_id)) {
            return Auth::getFallBackAuthBackend()->canUserUpdateName($usr_id);
        } else {
            return false;
        }
    }

    public function canUserUpdateEmail($usr_id)
    {
        $external_id = User::getExternalID($usr_id);
        if (empty($external_id)) {
            return Auth::getFallBackAuthBackend()->canUserUpdateEmail($usr_id);
        } else {
            return false;
        }
    }

    public function canUserUpdatePassword($usr_id)
    {
        $external_id = User::getExternalID($usr_id);
        if (empty($external_id)) {
            return Auth::getFallBackAuthBackend()->canUserUpdatePassword($usr_id);
        } else {
            return false;
        }
    }

    /**
     * Method used to get the system-wide defaults.
     *
     * @return  string array of the default parameters
     */
    public static function getDefaults()
    {
        // don't do anything complex here that would load setup
        return [
            'host' => 'localhost',
            'port' => '389',
            'binddn' => '',
            'bindpw' => '',
            'basedn' => 'dc=example,dc=org',
            'userdn' => 'uid=%UID%,ou=People,dc=example,dc=org',
            'customer_id_attribute' => '',
            'contact_id_attribute' => '',
            'user_filter' => '',
            'create_users' => null,
            'default_role' => [],
        ];
    }

    /**
     * Method used to update the account password for a specific user.
     *
     * @param   integer $usr_id The user ID
     * @param   string $password The password.
     * @return  boolean true if update worked, false otherwise
     */
    public function updatePassword($usr_id, $password)
    {
        if (!$this->isLDAPuser($usr_id)) {
            return Auth::getFallBackAuthBackend()->updatePassword($usr_id, $password);
        } else {
            return false;
        }
    }

    public function incrementFailedLogins($usr_id)
    {
        return true;
    }

    public function resetFailedLogins($usr_id)
    {
        return true;
    }

    public function isUserBackOffLocked($usr_id)
    {
        return false;
    }

    /**
     * Returns a URL to redirect the user to when they attempt to login or null if the native login pages
     * should be used.
     *
     * @return  string The login url or null
     */
    public function getExternalLoginURL()
    {
        return null;
    }

    /**
     * Called on every page load and can be used to process external authentication checks before the rest of the
     * authentication process happens.
     *
     * @return null
     */
    public function checkAuthentication()
    {
        return null;
    }

    /**
     * Called when a user logs out.
     *
     * @return mixed
     */
    public function logout()
    {
        return null;
    }

    /**
     * Returns true if the user should automatically be redirected to the external login URL, false otherwise
     *
     * @return  boolean
     */
    public function autoRedirectToExternalLogin()
    {
        return false;
    }
}
