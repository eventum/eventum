<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004 MySQL AB                                    |
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
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// +----------------------------------------------------------------------+
//
// @(#) $Id: s.class.auth.php 1.37 04/01/16 23:25:25-00:00 jpradomaia $
//


/**
 * Class to handle authentication issues.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

include_once(APP_INC_PATH . "class.error_handler.php");
include_once(APP_INC_PATH . "class.project.php");
include_once(APP_INC_PATH . "class.user.php");
require_once(APP_INC_PATH . "class.customer.php");
include_once(APP_INC_PATH . "class.date.php");
include_once(APP_INC_PATH . "private_key.php");

class Auth
{
    /**
     * Method used to save the login information into a log file. It will be
     * useful for administrative purposes, so we know which customers were able
     * to login.
     *
     * @access  public
     * @param   string $email The email associated with the user
     * @param   string $type Whether it was a successful login or not
     * @param   string $extra The reason for not being a successful login
     */
    function saveLoginAttempt($email, $type, $extra = false)
    {
        $msg = Date_API::getCurrentDateGMT() . " - Login attempt by '$email' was ";
        if ($type == 'success') {
            $msg .= "successful.\n";
        } else {
            $msg .= "not successful because of '$extra'.\n";
        }
        $fp = @fopen(APP_LOGIN_LOG, "a");
        @fwrite($fp, $msg);
        @fclose($fp);
    }


    /**
     * Method used to get the requested URI for the 'current' page the user is
     * trying to access. This is used to get the appropriate URL and save it
     * if the user does not have the login cookie.
     *
     * @access  public
     * @return  string The requested URI for the current page
     */
    function getRequestedURL()
    {
        global $HTTP_SERVER_VARS;
        return urlencode($HTTP_SERVER_VARS["REQUEST_URI"]);
    }


    /**
     * Method used to check for the appropriate authentication for a specific
     * page. It will check for the cookie name provided and redirect the user
     * to another page if needed.
     *
     * @access  public
     * @param   string $cookie_name The name of the cookie to check for
     * @param   string $failed_url The URL to redirect to if the user is not authenticated
     * @param   boolean $is_popup Flag to tell the function if the current page is a popup window or not
     * @return  void
     */
    function checkAuthentication($cookie_name, $failed_url = NULL, $is_popup = false)
    {
        global $HTTP_COOKIE_VARS;

        if ($failed_url == NULL) {
            $failed_url = APP_RELATIVE_URL . "index.php?err=5";
        }
        if (!isset($HTTP_COOKIE_VARS[$cookie_name])) {
            $failed_url .= "&url=" . Auth::getRequestedURL();
            Auth::redirect($failed_url, $is_popup);
        }
        $cookie = $HTTP_COOKIE_VARS[$cookie_name];
        $cookie = unserialize(base64_decode($cookie));
        if (!Auth::isValidCookie($cookie)) {
            Auth::removeCookie($cookie_name);
            Auth::redirect($failed_url, $is_popup);
        }
        if (Auth::isPendingUser($cookie["email"])) {
            Auth::removeCookie($cookie_name);
            Auth::redirect(APP_RELATIVE_URL . "index.php?err=9", $is_popup);
        }
        if (!Auth::isActiveUser($cookie["email"])) {
            Auth::removeCookie($cookie_name);
            Auth::redirect(APP_RELATIVE_URL . "index.php?err=7", $is_popup);
        }
        // check whether the project selection is set or not
        $prj_id = Auth::getCurrentProject();
        if (empty($prj_id)) {
            Auth::removeCookie($cookie_name);
            Auth::redirect(APP_RELATIVE_URL . "index.php?err=12&email=" . $cookie['email'], $is_popup);
        }
        // check the expiration date for a 'Customer' type user
        $customer_id = User::getCustomerID(Auth::getUserID());
        if ((!empty($customer_id)) && ($customer_id != -1)) {
            $status = Customer::getContractStatus($prj_id, $customer_id);
            if ($status == 'expired') {
                Auth::removeCookie($cookie_name);
                Auth::redirect(APP_RELATIVE_URL . "index.php?err=10&email=" . $cookie["email"], $is_popup);
            }
        }
        // if the current session is still valid, then renew the expiration
        Auth::createLoginCookie($cookie_name, $cookie['email'], $cookie['autologin']);
        // renew the project cookie as well
        $prj_cookie = Auth::getCookieInfo(APP_PROJECT_COOKIE);
        Auth::setCurrentProject($prj_id, $prj_cookie["remember"]);
    }


    /**
     * Method to check whether an user is pending its confirmation 
     * or not.
     *
     * @access  public
     * @param   string $email The email address to be checked
     * @return  boolean
     */
    function isPendingUser($email)
    {
        $status = User::getStatusByEmail($email);
        if ($status != 'pending') {
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method to check whether an user is active or not.
     *
     * @access  public
     * @param   string $email The email address to be checked
     * @return  boolean
     */
    function isActiveUser($email)
    {
        $status = User::getStatusByEmail($email);
        if ($status != 'active') {
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method to check if the user has cookie support enabled in his browser or
     * not.
     *
     * @access  public
     * @param   string $cookie_name The name of the cookie to check for
     * @return  boolean
     */
    function hasCookieSupport($cookie_name)
    {
        global $HTTP_COOKIE_VARS;

        if (@!in_array($cookie_name, array_keys($HTTP_COOKIE_VARS))) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method to check if the user has a valid cookie.
     *
     * @access  public
     * @param   string $cookie_name The name of the cookie to check for
     * @return  boolean
     */
    function hasValidCookie($cookie_name)
    {
        global $HTTP_COOKIE_VARS;

        $cookie = @$HTTP_COOKIE_VARS[$cookie_name];
        $cookie = unserialize(base64_decode($cookie));
        if (!Auth::isValidCookie($cookie)) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method used to get the unserialized contents of the specified cookie
     * name.
     *
     * @access  public
     * @param   string $cookie_name The name of the cookie to check for
     * @return  array The unserialized contents of the cookie
     */
    function getCookieInfo($cookie_name)
    {
        global $HTTP_COOKIE_VARS;

        $cookie = @$HTTP_COOKIE_VARS[$cookie_name];
        return unserialize(base64_decode($cookie));
    }


    /**
     * Method used to check whether a cookie is valid or not.
     *
     * @access  public
     * @param   array $cookie The unserialized contents of the cookie
     * @return  boolean
     */
    function isValidCookie($cookie)
    {
        if ((empty($cookie["email"])) || (empty($cookie["hash"])) ||
               ($cookie["hash"] != md5($GLOBALS["private_key"] . md5($cookie["login_time"]) . $cookie["email"]))) {
            return false;
        } else {
            $usr_id = User::getUserIDByEmail(@$cookie["email"]);
            if (empty($usr_id)) {
                return false;
            } else {
                return true;
            }
        }
    }


    /**
     * Method used to create the login cookie in the user's machine.
     *
     * @access  public
     * @param   string $cookie_name The cookie name to be created
     * @param   string $email The email address to be stored in the cookie
     * @param   integer $autologin Flag to indicate whether this user should be automatically logged in or not
     * @return  void
     */
    function createLoginCookie($cookie_name, $email, $autologin = 0)
    {
        $time = time();
        $cookie = array(
            "email"      => $email,
            "login_time" => $time,
            "hash"       => md5($GLOBALS["private_key"] . md5($time) . $email),
            "autologin"  => $autologin
        );
        $cookie = base64_encode(serialize($cookie));
        setcookie($cookie_name, $cookie, APP_COOKIE_EXPIRE, APP_RELATIVE_URL);
    }


    /**
     * Method used to redirect people to another URL.
     *
     * @access  public
     * @param   string $new_url The URL the user should be redirected to
     * @param   boolean $is_popup Whether the current window is a popup or not
     * @return  void
     */
    function redirect($new_url, $is_popup = false)
    {
        if ($is_popup) {
            $html = '<script language="JavaScript">
                     <!--
                     if (window.opener) {
                         window.opener.location.href = "' . $new_url . '";
                         window.close();
                     } else {
                        location.href = "' . $new_url . '";
                     }
                     //-->
                     </script>';
            echo $html;
            exit;
        } else {
            header("Refresh: 0; URL=$new_url");
            exit;
        }
    }


    /**
     * Method used to remove a cookie from the user's browser.
     *
     * @access  public
     * @param   string $cookie_name The name of the cookie that needs to be deleted
     * @return  void
     */
    function removeCookie($cookie_name)
    {
        setcookie($cookie_name, "", time()-36000, APP_RELATIVE_URL);
    }


    /**
     * Checks whether an user exists or not in the database.
     *
     * @access  public
     * @param   string $email The email address to check for
     * @return  boolean
     */
    function userExists($email)
    {
        if (empty($email)) {
            return false;
          } else {
            $stmt = "SELECT usr_email FROM " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user WHERE usr_email='$email'";
            $info = $GLOBALS["db_api"]->dbh->getOne($stmt);
            if (PEAR::isError($info)) {
                Error_Handler::logError(array($info->getMessage(), $info->getDebugInfo()), __FILE__, __LINE__);
                return false;
            } elseif (empty($info)) {
                return false;
            } else {
                return true;
            }
        }
    }


    /**
     * Checks whether the provided password match against the email 
     * address provided.
     *
     * @access  public
     * @param   string $email The email address to check for
     * @param   string $password The password of the user to check for
     * @return  boolean
     */
    function isCorrectPassword($email, $password)
    {
        $stmt = "SELECT usr_password FROM " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user WHERE usr_email='$email'";
        $passwd = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($passwd)) {
            Error_Handler::logError(array($passwd->getMessage(), $passwd->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            if ($passwd != md5($password)) {
                return false;
            } else {
                return true;
            }
        }
    }


    /**
     * Gets the current user ID.
     *
     * @access  public
     * @return  integer The ID of the user
     */
    function getUserID()
    {
        $info = Auth::getCookieInfo(APP_COOKIE);
        if (empty($info)) {
            return '';
        } else {
            return @User::getUserIDByEmail($info["email"]);
        }
    }


    /**
     * Gets the current selected project from the project cookie.
     *
     * @access  public
     * @return  integer The project ID
     */
    function getCurrentProject()
    {
        $cookie = Auth::getCookieInfo(APP_PROJECT_COOKIE);
        if (empty($cookie)) {
            return "";
        }
        $usr_id = Auth::getUserID();
        $projects = Project::getAssocList($usr_id);
        if (!in_array($cookie["prj_id"], array_keys($projects))) {
            Auth::redirect(APP_RELATIVE_URL . "select_project.php?err=1");
        }
        return $cookie["prj_id"];
    }


    /**
     * Gets the current project name from the user's project cookie.
     *
     * @access  public
     * @return  string The current project name
     */
    function getCurrentProjectName()
    {
        $proj_id = Auth::getCurrentProject();
        if (!empty($proj_id)) {
            return Project::getName($proj_id);
        }
    }


    /**
     * Sets the current selected project for the user session.
     *
     * @access  public
     * @param   integer $project The project ID
     * @param   integer $remember Whether to automatically remember the setting or not
     * @return  void
     */
    function setCurrentProject($project, $remember, $auto_switched = false)
    {
        $cookie = array(
            "prj_id"   => $project,
            "remember" => $remember
        );
        if ($auto_switched == true) {
            $cookie['auto_switched_from'] = Auth::getCurrentProject();
        }
        $cookie = base64_encode(serialize($cookie));
        setcookie(APP_PROJECT_COOKIE, $cookie, APP_PROJECT_COOKIE_EXPIRE, APP_RELATIVE_URL);
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Auth Class');
}
?>