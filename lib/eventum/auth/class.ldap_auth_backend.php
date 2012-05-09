<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2012 Eventum Team.                                     |
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
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: Bryan Alsdorf <balsdorf@gmail.com>                          |
// +----------------------------------------------------------------------+

require_once 'Net/LDAP2.php';

/**
 * This auth backend integrates with an LDAP server and if set to, will create
 * a local user with the specified name and email. The user will be
 * authenticated against the LDAP server on each login.
 *
 * This backend will look for users in the default mysql backend if no LDAP
 * user is found. This behaviour may be
 * configurable in the future.
 *
 * Set define('APP_AUTH_BACKEND', 'ldap_auth_backend') in the config file and
 * then fill in the LDAP server details
 * in manage
 */
class LDAP_Auth_Backend extends Abstract_Auth_Backend
{
    /**
     * @var $conn
     *
     * The admin connection
     */
    protected $conn;

    protected $config;

    protected $user_dn_string;

    public function __construct() {
        $setup = self::loadSetup();
        $this->config = array (
            'binddn'    =>  $setup['binddn'],
            'bindpw'    =>  $setup['bindpw'],
            'basedn'    =>  $setup['basedn'],
            'host'      =>  $setup['host'],
            'port'      =>  $setup['port'],
        );

        $this->user_dn_string = $setup['userdn'];

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

    private function isValidUser($uid, $password)
    {
        $setup = self::loadSetup();

        // TODO: Load from setup
        $config = array (
            'binddn'    =>  $this->getUserDNstring($uid),
            'bindpw'    =>  $password,
            'basedn'    =>  $setup['basedn'],
            'host'      =>  $setup['host'],
            'port'      =>  $setup['port'],
        );

        // Connecting using the configuration:
        $ldap = Net_LDAP2::connect($config);

        // Testing for connection error
        if (PEAR::isError($ldap)) {
            return false;
        } else {
            return true;
        }
    }

    public function getRemoteUserInfo($uid)
    {
        $entry = $this->conn->getEntry($this->getUserDNstring($uid), array('cn', 'uid', 'mail'));

        if (PEAR::isError($entry)) {
            return null;
        }

        $details = array(
            'uid'   =>  $entry->get_value('uid'),
            'full_name'  =>  $entry->get_value('cn'),
            'email'  =>  $entry->get_value('mail', 'single'),
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

        $local_usr_id = User::getUserIDByEmail($login, true);
        if (empty($local_usr_id)) {
            $local_usr_id = User::getUserIDByExternalID($login);
        }

        $data = array(
            'password'  =>  '',
            'full_name' =>  $remote['full_name'],
            'email'     =>  $remote['email'],
            'grp_id'    =>  '',
            'external_id'   =>  $remote['uid'],
        );
        if ($local_usr_id == null) {
            $setup = $this->loadSetup();
            $data['role'] = $setup['default_role'];
            $return = User::insert($data);
            return $return;
        } else {
            $update = User::update($local_usr_id, $data, false);
            return $local_usr_id;
        }
    }

    public function getUserIDByLogin($login)
    {
        $usr_id = User::getUserIDByEmail($login, true);
        if (empty($usr_id)) {
            // the login is not a local email address, try external id
            $usr_id = User::getUserIDByExternalID($login);
        }

        $local_user_info = User::getDetails($usr_id);
        if ($local_user_info !== false && empty($local_user_info['usr_external_id'])) {
            // local user exist and is not associated with LDAP, don't try to update.
            return $usr_id;
        }

        // try to create or update local user from ldap info
        $created = $this->updateLocalUserFromBackend($login);
        return $created;
    }

    public function verifyPassword($login, $password)
    {
        // check if this is an ldap or internal
        $usr_id = self::getUserIDByLogin($login);
        $local_user_info = User::getDetails($usr_id);
        if (empty($local_user_info['usr_external_id'])) {
            return Auth::getFallBackAuthBackend()->verifyPassword($login, $password);
        }

        $user_info = $this->isValidUser($login, $password);
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


    public static function loadSetup($force = false)
    {
        static $setup;
        if (empty($setup) || $force == true) {
            $eventum_setup_string = null;
            if (!file_exists(APP_CONFIG_PATH . '/ldap.php')) {
                return array();
            }
            require APP_CONFIG_PATH . '/ldap.php';
            if (empty($ldap_setup_string)) {
                return null;
            }
            $setup = unserialize(base64_decode($ldap_setup_string));
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
        $contents = "<"."?php\n\$ldap_setup_string='" . base64_encode(serialize($options)) . "';\n";
        $res = file_put_contents(APP_CONFIG_PATH . '/ldap.php', $contents);
        if ($res === false) {
            return -2;
        }
        return 1;
    }
}
