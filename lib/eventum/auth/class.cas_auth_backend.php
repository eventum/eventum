<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2012 - 2015 Eventum Team.                              |
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
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: Bryan Alsdorf <balsdorf@gmail.com>                          |
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

/**
 * This auth backend integrates with a CAS server
 *
 * This backend will look for users in the default mysql backend if no CAS
 * user is found. This behaviour may be configurable in the future.
 *
 * Set define('APP_AUTH_BACKEND', 'CAS_Auth_Backend') in the config file and
 * then fill in the CAS server details config/cas.php. An example config file is
 * in docs/examples/config/cas.php
 */
class CAS_Auth_Backend implements Auth_Backend_Interface
{
    protected $client;

    public function __construct()
    {
        $setup = self::loadSetup();
        $this->client = phpCAS::client(CAS_VERSION_2_0, $setup['host'], $setup['port'], $setup['context']);

        // For simplicities sake at the moment we are not validating the server auth.
        phpCAS::setNoCasServerValidation();

        phpCAS::setPostAuthenticateCallback(array($this, 'loginCallback'));
    }

    public function checkAuthentication()
    {
        if (phpCAS::isAuthenticated() && !AuthCookie::hasAuthCookie()) {
            $this->loginCallback();
        }

        // force CAS authentication
        phpCAS::forceAuthentication();
    }

    public function logout()
    {
        phpCAS::logoutWithRedirectService(APP_BASE_URL);
    }

    public function loginCallback()
    {
        $attributes = phpCAS::getAttributes();

        $this->updateLocalUserFromBackend($attributes);

        $usr_id = User::getUserIDByEmail($attributes['mail'], true);
        $user = User::getDetails($usr_id);

        AuthCookie::setAuthCookie($user['usr_email'], true);
    }

    public function updateLocalUserFromBackend($remote)
    {
        $setup = self::loadSetup();

        $usr_id = User::getUserIDByEmail($remote['mail'], true);

        $data = array(
            'password' => '',
            'full_name' => $remote['firstname'] . ' ' . $remote['lastname'],
            'external_id' => $remote['uid'],
        );

        if (!empty($setup['customer_id_attribute'])) {
            $data['customer_id'] = $remote[$setup['customer_id_attribute']];
        }
        if (!empty($setup['contact_id_attribute'])) {
            $data['contact_id'] = $remote[$setup['contact_id_attribute']];
        }

        // if local user found, update it and return usr id
        if ($usr_id) {
            // do not reset user password, it maybe be set locally before this
            unset($data['password']);

            // perspective what is main address and what is alias may be different in CAS and in eventum
            $emails = array($remote['mail']);
            $email = User::getEmail($usr_id);

            if (($key = array_search($email, $emails)) !== false) {
                unset($emails[$key]);
                $data['email'] = $email;
            } else {
                if (count($emails) < 1) {
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
        } else {
            // create new local user
            $setup = self::loadSetup();
            if ($setup['create_users'] == false) {
                throw new AuthException('User does not exist and will not be created.');
            }
            $data['role'] = $setup['default_role'];

            $emails = array($remote['mail']);
            if (count($emails) < 1) {
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
        }

        return $usr_id;
    }

    private function updateAliases($usr_id, $aliases)
    {
        foreach ($aliases as $alias) {
            User::addAlias($usr_id, $alias);
        }
    }

    /**
     * With CAS we cannot do a simple password check like this. This will prevent the CLI from working
     * so at some point in the future we need to find a solution.
     *
     * @param   string $login The login or email to check for
     * @param   string $password The password of the user to check for
     * @return  boolean
     */
    public function verifyPassword($login, $password)
    {
        return false;
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
        return false;
    }

    /**
     * Returns the user ID for the specified login. This can be the email address, an alias,
     * the external login id or any other info the backend can handle.
     *
     * @param $login
     * @return  int|null The user id or null
     */
    public function getUserIDByLogin($login)
    {
        return null;
    }

    /**
     * If this backend allows the user to update their name.
     *
     * @param int $usr_id
     * @return bool
     */
    public function canUserUpdateName($usr_id)
    {
        return false;
    }

    /**
     * If this backend allows the user to update their email.
     *
     * @param int $usr_id
     * @return bool
     */
    public function canUserUpdateEmail($usr_id)
    {
        return false;
    }

    /**
     * If this backend allows the user to update their password.
     *
     * @param int $usr_id
     * @return bool
     */
    public function canUserUpdatePassword($usr_id)
    {
        return false;
    }

    /**
     * Increment the failed logins attempts for this user
     *
     * @param   integer $usr_id The ID of the user
     * @return  boolean
     */
    public function incrementFailedLogins($usr_id)
    {
        return true;
    }

    /**
     * Reset the failed logins attempts for this user
     *
     * @param   integer $usr_id The ID of the user
     * @return  boolean
     */
    public function resetFailedLogins($usr_id)
    {
        return true;
    }

    /**
     * Returns the true if the account is currently locked because of Back-Off locking
     *
     * @param   integer $usr_id The ID of the user
     * @return  boolean
     */
    public function isUserBackOffLocked($usr_id)
    {
        return false;
    }

    /**
     * Just return the main eventum page since that will prompt a CAS login.
     *
     * @return  string The login url or null
     */
    public function getExternalLoginURL()
    {
        return APP_RELATIVE_URL . 'main.php';
    }

    public static function loadSetup($force = false)
    {
        static $setup;
        if (empty($setup) || $force == true) {
            $setup = array();
            $configfile = APP_CONFIG_PATH . '/cas.php';

            if (file_exists($configfile)) {
                /** @noinspection PhpIncludeInspection */
                $setup = require $configfile;
            }

            // merge with defaults
            $setup = Misc::array_extend(self::getDefaults(), $setup);
        }

        return $setup;
    }

    public static function saveSetup($options)
    {
        // this is needed to check if the file can be created or not
        if (!file_exists(APP_CONFIG_PATH . '/cas.php')) {
            if (!is_writable(APP_CONFIG_PATH)) {
                clearstatcache();

                return -1;
            }
        } else {
            if (!is_writable(APP_CONFIG_PATH . '/cas.php')) {
                clearstatcache();

                return -2;
            }
        }
        $contents = '<' . "?php\n\$cas_setup = " . var_export($options, 1) . ";\n";
        $res = file_put_contents(APP_CONFIG_PATH . '/cas.php', $contents);
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
            'port' => 443,
            'context'   =>  '/cas',
            'customer_id_attribute' => '',
            'contact_id_attribute' => '',
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
}
