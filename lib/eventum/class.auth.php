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

use Eventum\Auth\Adapter\AdapterInterface;
use Eventum\Auth\AuthException;
use Eventum\Model\Entity;
use Eventum\Monolog\Logger;
use Eventum\Session;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class to handle authentication issues.
 */
class Auth
{
    /**
     * Method used to get private key used for hashing session cookies.
     *
     * @return  string  the private_key hash
     */
    public static function privateKey()
    {
        static $private_key;
        if ($private_key === null) {
            require_once Setup::getConfigPath() . '/private_key.php';
        }

        return $private_key;
    }

    /**
     * Method used to regenerate private key.
     *
     * IMPORTANT: regenerating private key will invalidate all user sessions.
     */
    public static function generatePrivateKey(): void
    {
        $path = Setup::getConfigPath() . '/private_key.php';
        $private_key = md5(Misc::generateRandom(32));

        $contents = '<' . "?php\n\$private_key = " . var_export($private_key, 1) . ";\n";

        try {
            $fs = new Filesystem();
            $fs->dumpFile($path, $contents);
        } catch (IOException $e) {
            throw new RuntimeException($e->getMessage(), -2);
        }
    }

    /**
     * Method used to save the login information into a log file. It will be
     * useful for administrative purposes, so we know which customers were able
     * to login.
     *
     * @param   string $email The email associated with the user
     * @param   string $type Whether it was a successful login or not
     * @param   string $extra The reason for not being a successful login
     */
    public static function saveLoginAttempt($email, $type, $extra = null): void
    {
        $msg = "Login attempt by '$email' was ";
        if ($type === 'success') {
            $msg .= 'successful.';
        } else {
            $msg .= "not successful because of '$extra'.";
        }
        Logger::auth()->info($msg, ['user' => $email, 'type' => $type, 'extra' => $extra]);
    }

    /**
     * Method used to check for the appropriate authentication for a specific
     * page. It will check for the cookie name provided and redirect the user
     * to another page if needed.
     *
     * @param   string $failed_url The URL to redirect to if the user is not authenticated
     * @param   bool $is_popup Flag to tell the function if the current page is a popup window or not
     */
    public static function checkAuthentication($failed_url = null, $is_popup = false): void
    {
        try {
            self::getAuthBackend()->checkAuthentication();

            if ($failed_url === null) {
                $failed_url = APP_RELATIVE_URL . 'index.php?err=5';
            }
            $failed_url .= '&url=' . urlencode($_SERVER['REQUEST_URI']);
            if (!AuthCookie::hasAuthCookie()) {
                if (APP_ANON_USER) {
                    $anon_usr_id = User::getUserIDByEmail(APP_ANON_USER);
                    $prj_id = reset(array_keys(Project::getAssocList($anon_usr_id)));
                    AuthCookie::setAuthCookie(APP_ANON_USER, false);
                    AuthCookie::setProjectCookie($prj_id);
                    Session::init($anon_usr_id);
                } else {
                    // check for valid HTTP_BASIC params
                    if (isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
                        if (self::isCorrectPassword($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
                            $usr_id = User::getUserIDByEmail($_SERVER['PHP_AUTH_USER'], true);
                            $prj_id = reset(array_keys(Project::getAssocList($usr_id)));
                            AuthCookie::setAuthCookie(APP_ANON_USER);
                            AuthCookie::setProjectCookie($prj_id);
                        } else {
                            header('WWW-Authenticate: Basic realm="Eventum"');
                            header('HTTP/1.0 401 Unauthorized');
                            echo 'Login Failed';

                            return;
                        }
                    } else {
                        self::redirect($failed_url, $is_popup);
                    }
                }
            }

            $cookie = AuthCookie::getAuthCookie();
            if (!$cookie) {
                AuthCookie::removeAuthCookie();
                self::redirect($failed_url, $is_popup);
            }
            if (self::isPendingUser($cookie['email'])) {
                AuthCookie::removeAuthCookie();
                self::redirect('index.php?err=9', $is_popup);
            }
            if (!self::isActiveUser($cookie['email'])) {
                AuthCookie::removeAuthCookie();
                self::redirect('index.php?err=7', $is_popup);
            }

            $usr_id = self::getUserID();

            Session::init($usr_id);
            Language::setPreference();

            // check whether the project selection is set or not
            $prj_id = self::getCurrentProject();
            if (empty($prj_id)) {
                // redirect to select project page
                self::redirect(APP_RELATIVE_URL . 'select_project.php?url=' . urlencode($_SERVER['REQUEST_URI']), $is_popup);
            }
            // check the expiration date for a 'Customer' type user
            $contact_id = User::getCustomerContactID($usr_id);
            if ((!empty($contact_id)) && (CRM::hasCustomerIntegration($prj_id))) {
                $crm = CRM::getInstance($prj_id);
                $crm->authenticateCustomer();
            }

            // auto switch project
            if (isset($_GET['switch_prj_id'])) {
                $prj_id = $_GET['switch_prj_id'];
                AuthCookie::setProjectCookie($prj_id);
                $url = $_SERVER['PHP_SELF'] . '?' .
                    str_replace(
                        "switch_prj_id={$prj_id}", '',
                        $_SERVER['QUERY_STRING']
                    );
                self::redirect($url);
            }

            // if the current session is still valid, then renew the expiration
            AuthCookie::setAuthCookie($cookie['email'], $cookie['permanent']);
            // renew the project cookie as well
            AuthCookie::setProjectCookie($prj_id);
        } catch (AuthException $e) {
            $tpl = new Template_Helper();
            $tpl->setTemplate('authentication_error.tpl.html');
            $tpl->assign('error_message', $e->getMessage());
            $tpl->displayTemplate();
            exit;
        }
    }

    /**
     * Method for logging out the currently logged in user. Called after the normal logout process has completed.
     *
     * @returns void
     */
    public static function logout(): void
    {
        AuthCookie::removeAuthCookie();
        // if 'remember projects' is true don't remove project cookie
        $cookie = AuthCookie::getProjectCookie();
        if (empty($cookie['remember'])) {
            AuthCookie::removeProjectCookie();
        }
        self::getAuthBackend()->logout();
    }

    /**
     * Method to check whether an user is pending its confirmation
     * or not.
     *
     * @param   string $email The email address to be checked
     * @return  bool
     * @deprecated
     */
    public static function isPendingUser($email): bool
    {
        $status = User::getStatusByEmail($email);

        return $status === Entity\User::STATUS_PENDING;
    }

    /**
     * Method to check whether an user is active or not.
     *
     * @param   string $email The email address to be checked
     * @return  bool
     * @deprecated
     */
    public static function isActiveUser($email): bool
    {
        $status = User::getStatusByEmail($email);

        return $status === Entity\User::STATUS_ACTIVE;
    }

    /**
     * Method to check if the current user is an anonymous user.
     *
     * @return  bool
     */
    public static function isAnonUser()
    {
        return self::getUserID() == User::getUserIDByEmail(APP_ANON_USER);
    }

    /**
     * Method used to redirect people to another URL.
     *
     * @param   string $url The URL the user should be redirected to
     * @param   bool $is_popup Whether the current window is a popup or not
     */
    public static function redirect($url, $is_popup = false): void
    {
        if ($is_popup) {
            $html = '<script type="text/javascript">
                     <!--
                     if (window.opener) {
                         window.opener.location.href = "' . $url . '";
                         window.close();
                     } else {
                        location.href = "' . $url . '";
                     }
                     //-->
                     </script>';
            echo $html;
        } else {
            // IIS 5 has problems with "Location" header so don't use it under IIS
            if (!empty($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'IIS') !== false) {
                // IIS
                header("Refresh: 0; URL=$url");
            } else {
                // all servers that work correctly
                header("Location: $url");
            }
        }
        exit;
    }

    /**
     * Method used to remove a cookie from the user's browser.
     *
     * @param   string $cookie_name The name of the cookie that needs to be deleted
     */
    public static function removeCookie($cookie_name): void
    {
        self::setCookie($cookie_name, '', time() - 36000);
    }

    /**
     * Checks whether an user exists or not in the database.
     *
     * @param string $login The email address to check for
     * @return bool
     */
    public static function userExists($login)
    {
        if (empty($login)) {
            return false;
        }

        return self::getAuthBackend()->userExists($login);
    }

    /**
     * Checks whether the provided password match against the email
     * address provided.
     *
     * @param   string $email The email address to check for
     * @param   string $password The password of the user to check for
     * @return  bool
     */
    public static function isCorrectPassword($email, $password)
    {
        return self::getAuthBackend()->verifyPassword($email, $password);
    }

    /**
     * Gets the current user ID.
     *
     * @return  int The ID of the user
     */
    public static function getUserID()
    {
        $info = AuthCookie::getAuthCookie();
        if (!$info) {
            return '';
        }

        return User::getUserIDByEmail($info['email']);
    }

    /**
     * Gets the current user login.
     *
     * @return  string  The login of the user
     */
    public static function getUserLogin()
    {
        $info = AuthCookie::getAuthCookie();
        if (!$info) {
            return null;
        }

        return $info['email'];
    }

    /**
     * Gets the current selected project from the project cookie.
     *
     * @return  int The project ID
     */
    public static function getCurrentProject($redirect = true)
    {
        $cookie = AuthCookie::getProjectCookie();
        if (!$cookie) {
            return '';
        }
        $usr_id = self::getUserID();
        $projects = Project::getAssocList($usr_id);
        if ($usr_id == APP_SYSTEM_USER_ID) {
            return isset($cookie['prj_id']) ? (int)$cookie['prj_id'] : null;
        }

        if ($projects != null && !in_array($cookie['prj_id'], array_keys($projects))) {
            if ($redirect) {
                self::redirect('select_project.php');
            } else {
                return false;
            }
        }

        return $cookie['prj_id'];
    }

    /**
     * Gets the current project name from the user's project cookie. If no project ID is set it will redirect
     * to the select project page.
     *
     * @return  string The current project name
     */
    public static function getCurrentProjectName()
    {
        return Project::getName(self::getCurrentProject(true));
    }

    /**
     * Gets the current role in the current project.
     *
     * @return  int The current role ID
     */
    public static function getCurrentRole()
    {
        $prj_id = self::getCurrentProject();
        $usr_id = self::getUserID();
        if ($prj_id && $usr_id) {
            return User::getRoleByUser($usr_id, $prj_id);
        }

        return 1;
    }

    /**
     * Returns the current customer ID.
     *
     * @param bool $redirect
     * @return  string  The current customer ID
     */
    public static function getCurrentCustomerID($redirect = true)
    {
        $customer_id = Session::get('current_customer_id');
        if (empty($customer_id) && $redirect === true) {
            self::redirect(APP_RELATIVE_URL . 'select_customer.php');
        } else {
            return $customer_id;
        }
    }

    public static function setCurrentCustomerID($customer_id): void
    {
        Session::set('current_customer_id', $customer_id);
    }

    /**
     * @static
     * @return Contact
     */
    public static function getCurrentContact()
    {
        $crm = CRM::getInstance(self::getCurrentProject());

        return $crm->getContact(User::getCustomerContactID(self::getUserID()));
    }

    /**
     * Sets a cookie in the browser
     *
     * @param   string $name The name of the cookie
     * @param   string $value The value of the cookie
     * @param   string $expiration The expiration data of the cookie
     */
    public static function setCookie($name, $value, $expiration): void
    {
        // for testing
        if (PHP_SAPI === 'cli') {
            return;
        }
        if (APP_COOKIE_DOMAIN === null) {
            setcookie($name, $value, $expiration, APP_COOKIE_URL);
        } else {
            setcookie($name, $value, $expiration, APP_COOKIE_URL, APP_COOKIE_DOMAIN);
        }
    }

    public static function getAuthBackend(): AdapterInterface
    {
        /** @var AdapterInterface $adapter */
        static $adapter = false;

        if ($adapter === false) {
            $spec = Setup::get()['auth'] ?? [];

            try {
                $adapter = Eventum\Auth\Adapter\Factory::create($spec);
            } catch (Throwable $e) {
                $message = 'Unable to instantiate auth adapter';
                Logger::app()->critical($message, ['exception' => $e]);

                $tpl = new Template_Helper();
                $tpl->setTemplate('authentication_error.tpl.html');
                $tpl->assign('error_message', $e->getMessage());
                $tpl->displayTemplate();
                exit;
            }
        }

        return $adapter;
    }

    /**
     * Returns the user ID for the specified login. This can be the email address, an alias,
     * the external login id or any other info the backend can handle.
     *
     * @abstract
     * @param $login
     * @return  int|null The user id or null
     */
    public static function getUserIDByLogin($login)
    {
        return self::getAuthBackend()->getUserId($login);
    }

    /**
     * @param int $usr_id
     */
    public static function canUserUpdateName($usr_id)
    {
        return self::getAuthBackend()->canUserUpdateName($usr_id);
    }

    /**
     * @param int $usr_id
     */
    public static function canUserUpdateEmail($usr_id)
    {
        return self::getAuthBackend()->canUserUpdateEmail($usr_id);
    }

    /**
     * @param int $usr_id
     */
    public static function canUserUpdatePassword($usr_id)
    {
        return self::getAuthBackend()->canUserUpdatePassword($usr_id);
    }

    /**
     * @return string
     */
    public static function getExternalLoginURL()
    {
        $backend_login_url = self::getAuthBackend()->getExternalLoginURL();
        if (!$backend_login_url) {
            return null;
        }

        return $backend_login_url;
    }

    public static function autoRedirectToExternalLogin()
    {
        return self::getAuthBackend()->autoRedirectToExternalLogin();
    }
}
