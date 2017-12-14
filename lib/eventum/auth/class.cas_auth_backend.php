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

        phpCAS::setPostAuthenticateCallback([$this, 'loginCallback']);
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

        $data = [
            'password' => '',
            'full_name' => $remote['firstname'] . ' ' . $remote['lastname'],
            'external_id' => $remote['uid'],
        ];

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
            $emails = [$remote['mail']];
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

            $updated = User::update($usr_id, $data, false);
            if ($updated) {
                $this->updateAliases($usr_id, $emails);
            }

            return $usr_id;
        }

        // create new local user
        $setup = self::loadSetup();
        if ($setup['create_users'] == false) {
            throw new AuthException('User does not exist and will not be created.');
        }
        $data['role'] = $setup['default_role'];

        $emails = [$remote['mail']];
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

        return $usr_id;
    }

    /**
     * @param int $usr_id
     */
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
     * @return  bool
     */
    public function verifyPassword($login, $password)
    {
        return false;
    }

    /**
     * Method used to update the account password for a specific user.
     *
     * @param   int $usr_id The user ID
     * @param   string $password the password
     * @return  bool true if update worked, false otherwise
     */
    public function updatePassword($usr_id, $password)
    {
        return true;
    }

    public function userExists($login)
    {
        $usr_id = $this->getUserIDByLogin($login);

        return $usr_id > 0;
    }

    /**
     * Returns the user ID for the specified email address. This will ONLY check for local accounts.
     *
     * By default, CAS cannot check for a user account without logging them in. If you need to be able to do that you
     * should extend this class and add custom functionality.
     *
     * @param string $login
     * @return  int|null The user id or null
     */
    public function getUserIDByLogin($login)
    {
        return User::getUserIDByEmail($login, true);
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
     * @param   int $usr_id The ID of the user
     * @return  bool
     */
    public function incrementFailedLogins($usr_id)
    {
        return true;
    }

    /**
     * Reset the failed logins attempts for this user
     *
     * @param   int $usr_id The ID of the user
     * @return  bool
     */
    public function resetFailedLogins($usr_id)
    {
        return true;
    }

    /**
     * Returns the true if the account is currently locked because of Back-Off locking
     *
     * @param   int $usr_id The ID of the user
     * @return  bool
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
            $setup = [];
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

    /**
     * Method used to get the system-wide defaults.
     *
     * @return  string array of the default parameters
     */
    public static function getDefaults()
    {
        $defaults = [
            'host' => 'localhost',
            'port' => 443,
            'context' => '/cas',
            'customer_id_attribute' => '',
            'contact_id_attribute' => '',
            'create_users' => null,
            'default_role' => [],
        ];

        if (AuthCookie::hasAuthCookie()) {
            // ensure there is entry for current project
            $prj_id = Auth::getCurrentProject();

            $defaults['default_role'][$prj_id] = 0;
        }

        return $defaults;
    }

    /**
     * Returns true if the user should automatically be redirected to the external login URL, false otherwise
     *
     * @return  bool
     */
    public function autoRedirectToExternalLogin()
    {
        return false;
    }
}
