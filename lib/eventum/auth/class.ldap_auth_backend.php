<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2012 - 2014 Eventum Team.                              |
// |                                                                      |
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License as published by |
// | the Free Software Foundation; either version 2 of the License, or    |
// | (at your option) any later version.                                  |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to:                           |
// |                                                                      |
// | Free Software Foundation, Inc.                                       |
// | 51 Franklin Street, Suite 330                                          |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: Bryan Alsdorf <balsdorf@gmail.com>                          |
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

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
    /** @var Net_LDAP2 $conn The admin LDAP connection */
    protected $conn;

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
        $setup = self::loadSetup();

        $this->basedn = $setup['basedn'];
        $this->user_dn_string = $setup['userdn'];
        $this->user_filter_string = $setup['user_filter'];
        $this->customer_id_attribute = $setup['customer_id_attribute'];
        $this->contact_id_attribute = $setup['contact_id_attribute'];

        $options = array(
            'host' => $setup['host'],
            'port' => $setup['port'],
            'binddn' => $setup['binddn'],
            'bindpw' => $setup['bindpw'],
            'basedn' => $this->basedn,
        );

        $this->conn = $this->connect($options);
    }

    /**
     * Create LDAP connection.
     *
     * @param array $options
     * @return Net_LDAP2
     */
    private function connect($options)
    {
        $conn = Net_LDAP2::connect($options);
        if (Misc::isError($conn)) {
            throw new AuthException($conn->getMessage(), $conn->getCode());
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
            $filter = Net_LDAP2_Filter::combine('and', array($filter, $user_filter));
        }

        $search = $this->conn->search($this->basedn, $filter);

        if (Misc::isError($search)) {
            throw new AuthException($search->getMessage(), $search->getCode());
        }

        return $search;
    }

    private function validatePassword($uid, $password)
    {
        $errors = array();

        foreach (explode('|', $this->getUserDNstring($uid)) as $userDNstring) {
            // Connecting using the configuration
            try {
                $res = $this->conn->bind($userDNstring, $password);
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
            $filter = Net_LDAP2_Filter::combine('and', array($filter, $user_filter));
        }
        $search = $this->conn->search($this->basedn, $filter, array('sizelimit' => 1));
        $entry = $search->shiftEntry();

        if (!$entry || Misc::isError($entry)) {
            return null;
        }

        $details = array(
            'uid' => $entry->get_value('uid'),
            'full_name' => $entry->get_value('cn'),
            'emails' => $entry->get_value('mail', 'all'),
            'customer_id' => $entry->get_value($this->customer_id_attribute),
            'contact_id' => $entry->get_value($this->contact_id_attribute),
        );

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

        $data = array(
            'password' => '',
            'full_name' => $remote['full_name'],
            'external_id' => $remote['uid'],
            'customer_id' => $remote['customer_id'],
            'contact_id' => $remote['contact_id'],
        );

        // if local user found, update it and return usr id
        if ($usr_id) {
            // do not reset user password, it maybe be set locally before ldap
            unset($data['password']);

            // perspective what is main address and what is alias may be different in ldap and in eventum
            $emails = $remote['emails'];
            $email = User::getEmail($usr_id);

            if (($key = array_search($email, $emails)) !== false) {
                unset($emails[$key]);
                $data['email'] = $email;
            } else {
                if (!$emails) {
                    throw new AuthException('E-mail is required');
                }
                // just use first email
                $data['email'] = array_shift($emails);
            }

            // do not clear full name if for some reason it is empty
            if (empty($data['full_name'])) {
                unset($data['full_name']);
            }

            $update = User::update($usr_id, $data, false);
            if ($update > 0) {
                $this->updateAliases($usr_id, $emails);
            }

            return $usr_id;
        }

        // create new local user
        $setup = self::loadSetup();
        $data['role'] = $setup['default_role'];

        $emails = $remote['emails'];
        if (!$emails) {
            throw new AuthException('E-mail is required');
        }
        $data['email'] = array_shift($emails);

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

    private function updateAliases($usr_id, $aliases)
    {
        foreach ($aliases as $alias) {
            User::addAlias($usr_id, $alias);
        }
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
        if ($user_info == null) {
            return false;
        } else {
            return true;
        }
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
     * TODO: discard this loadSetup/saveSetup, and use plain Setup class
     */
    public static function loadSetup($force = false)
    {
        static $setup;
        if (empty($setup) || $force == true) {
            $setup = array();
            $configfile = APP_CONFIG_PATH . '/ldap.php';

            if (file_exists($configfile)) {
                $ldap_setup_string = $ldap_setup = null;

                /** @noinspection PhpIncludeInspection */
                require $configfile;

                if (isset($ldap_setup)) {
                    $setup = $ldap_setup;
                } elseif (isset($ldap_setup_string)) {
                    // support reading legacy base64 encoded config
                    $setup = unserialize(base64_decode($ldap_setup_string));
                }
            }

            // merge with defaults
            $setup = Misc::array_extend(self::getDefaults(), $setup);
        }

        return $setup;
    }

    public static function saveSetup($options)
    {
        // this is needed to check if the file can be created or not
        if (!file_exists(APP_CONFIG_PATH . '/ldap.php')) {
            if (!is_writable(APP_CONFIG_PATH)) {
                clearstatcache();

                return -1;
            }
        } else {
            if (!is_writable(APP_CONFIG_PATH . '/ldap.php')) {
                clearstatcache();

                return -2;
            }
        }
        $contents = '<' . "?php\n\$ldap_setup = " . var_export($options, 1) . ";\n";
        $res = file_put_contents(APP_CONFIG_PATH . '/ldap.php', $contents);
        if ($res === false) {
            return -2;
        }

        return 1;
    }

    /**
     * Method used to get the system-wide defaults.
     *
     * @return  string array of the default parameters
     */
    public static function getDefaults()
    {
        $defaults = array(
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
            'default_role' => array(),
        );

        if (AuthCookie::hasAuthCookie()) {
            // ensure there is entry for current project
            $prj_id = Auth::getCurrentProject();

            $defaults['default_role'][$prj_id] = 0;
        }

        return $defaults;
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
     * @return mixed
     */
    public function logout()
    {
        return null;
    }
}
