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
    /**
     * @var $conn
     *
     * The admin connection
     */
    protected $conn;

    protected $config;

    protected $user_dn_string;

    protected $customer_id_attribute;
    protected $contact_id_attribute;

    public function __construct()
    {
        $setup = self::loadSetup();
        $this->config = array (
            'binddn'    =>  $setup['binddn'],
            'bindpw'    =>  $setup['bindpw'],
            'basedn'    =>  $setup['basedn'],
            'host'      =>  $setup['host'],
            'port'      =>  $setup['port'],
        );

        $this->user_dn_string = $setup['userdn'];
        $this->user_filter_string = $setup['user_filter'];
        $this->customer_id_attribute = $setup['customer_id_attribute'];
        $this->contact_id_attribute = $setup['contact_id_attribute'];

        $this->conn = Net_LDAP2::connect($this->config);
    }

    public function isSetup()
    {
        // Testing for connection error
        if (PEAR::isError($this->conn)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * TODO: refactor this and make __construct to throw on error
     */
    public function getConnectError()
    {
        if (PEAR::isError($this->conn)) {
            return $this->conn->getMessage();
        } else {
            return false;
        }
    }

    private function isValidUser($uid, $password)
    {
        $setup = self::loadSetup();
        $errors = array();

        foreach (explode('|', $this->getUserDNstring($uid)) as $userDNstring) {
            $config = array (
                'binddn'    =>  $userDNstring,
                'bindpw'    =>  $password,
                'basedn'    =>  $setup['basedn'],
                'host'      =>  $setup['host'],
                'port'      =>  $setup['port'],
            );

            // Connecting using the configuration:
            $ldap = Net_LDAP2::connect($config);

            // Testing for connection error
            if (PEAR::isError($ldap)) {
                $errors[] = $ldap;
            } else {
                return true;
            }
        }

        foreach ($errors as $error) {
            Auth::saveLoginAttempt($uid, 'failure', $error->getMessage());
        }

        return false;
    }

    public function getRemoteUserInfo($uid)
    {
        if (strpos($uid, '@') === false) {
            $filter = Net_LDAP2_Filter::create('uid', 'equals',  $uid);
        } else {
            $filter = Net_LDAP2_Filter::create('mail', 'equals',  $uid);
        }
        if (!empty($this->user_filter_string)) {
            $user_filter = Net_LDAP2_Filter::parse($this->user_filter_string);
            $filter = Net_LDAP2_Filter::combine("and", array($filter, $user_filter));
        }
        $search = $this->conn->search($this->config['basedn'], $filter, array('sizelimit' => 1));
        $entry = $search->shiftEntry();

        if (!$entry || PEAR::isError($entry)) {
            return null;
        }

        $email = $entry->get_value('mail', 'single');
        $aliases = $entry->get_value('mail', 'all');
        if(($key = array_search($email, $aliases)) !== false) {
            unset($aliases[$key]);
        }

        $details = array(
            'uid'   =>  $entry->get_value('uid'),
            'full_name'  =>  $entry->get_value('cn'),
            'email'  =>  $email,
            'customer_id'   =>  $entry->get_value($this->customer_id_attribute),
            'contact_id'  =>  $entry->get_value($this->contact_id_attribute),
            'aliases'   =>  $aliases,
        );

        return $details;
    }

    protected function getUserDNstring($uid)
    {
        return str_replace('%UID%', $uid, $this->user_dn_string);
    }

    public function updateLocalUserFromBackend($login)
    {
        $remote = $this->getRemoteUserInfo($login);
        if ($remote == null) {
            return false;
        }

        // first try with user supplied input
        // FIXME: this is duplicate with all emails check below?
        $local_usr_id = User::getUserIDByEmail($login, true);

        if (!$local_usr_id) {
            // need to find local user by email by ALL aliases from remote system
            $emails = array_merge((array)$remote['email'], (array)$remote['aliases']);
            foreach ($emails as $email) {
                $local_usr_id = User::getUserIDByEmail($email, true);
                if ($local_usr_id) {
                    break;
                }
            }
        }

        if (!$local_usr_id) {
            // try by login name
            $local_usr_id = User::getUserIDByExternalID($login);
        }

        $data = array(
            'password'    => '',
            'full_name'   => $remote['full_name'],
            'email'       => $remote['email'],
            'external_id' => $remote['uid'],
            'customer_id' => $remote['customer_id'],
            'contact_id'  => $remote['contact_id'],
        );

        // if local user found, update it and return usr id
        if ($local_usr_id) {
            // do not reset user password, it maybe be set locally before ldap
            unset($data['password']);

            // perspective what is main address and what is alias may be different in ldap and in eventum
            $emails = array_merge((array)$remote['email'], (array)$remote['aliases']);
            $email = User::getEmail($local_usr_id);

            if (($key = array_search($email, $emails)) !== false) {
                unset($emails[$key]);
                $data['email'] = $email;
            }

            $update = User::update($local_usr_id, $data, false);
            if ($update > 0) {
                $this->updateAliases($local_usr_id, $emails);
            }
            return $local_usr_id;
        }

        // create new local user
        $setup = $this->loadSetup();
        $data['role'] = $setup['default_role'];

        if (!empty($data['customer_id']) && !empty($data['contact_id'])) {
            foreach ($data['role'] as $prj_id => $role) {
                if ($role > 0) {
                    $data['role'][$prj_id] = User::getRoleID('Customer');
                }
            }
        }
        $return = User::insert($data);
        if ($return > 0) {
            $this->updateAliases($return, $remote['aliases']);
        }
        return $return;
    }

    private function updateAliases($local_usr_id, $aliases)
    {
        foreach ($aliases as $alias) {
            User::addAlias($local_usr_id, $alias);
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

        $user_info = $this->isValidUser($local_user_info['usr_external_id'], $password);
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
        $contents = "<" . "?php\n\$ldap_setup = " . var_export($options, 1) . ";\n";
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
            'userdn'                => 'uid=%UID%,ou=People,dc=example,dc=org',
            'customer_id_attribute' => '',
            'contact_id_attribute'  => '',
            'create_users'          => null,
            'default_role'          => array(
                // ensure there is entry for current project
                Auth::getCurrentProject() => 0,
            ),
        );

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
}
