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
// @(#) $Id: s.class.user.php 1.55 04/01/26 20:37:04-06:00 joao@kickass. $
//


/**
 * Class to handle the business logic related to the administration
 * of users and permissions in the system.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

include_once(APP_INC_PATH . "class.error_handler.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.mail.php");
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.prefs.php");
include_once(APP_INC_PATH . "class.notification.php");
include_once(APP_INC_PATH . "class.validation.php");
include_once(APP_INC_PATH . "class.date.php");
include_once(APP_INC_PATH . "class.project.php");
include_once(APP_INC_PATH . "class.setup.php");
include_once(APP_INC_PATH . "private_key.php");

// definition of roles
$roles = array(
    1 => "Viewer",
    2 => "Reporter",
    3 => "Standard User",
    4 => "Manager",
    5 => "Administrator"
);

class User
{
    /**
     * Method used to get the SMS email address associated with the given 
     * user ID.
     *
     * @access  public
     * @param   integer $usr_id The user ID
     * @return  string The user's SMS email address
     */
    function getSMS($usr_id)
    {
        $stmt = "SELECT
                    usr_sms_email
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    usr_id=$usr_id";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return '';
        } else {
            return $res;
        }
    }


    /**
     * Method used to update the SMS email address associated with the given 
     * user ID.
     *
     * @access  public
     * @param   integer $usr_id The user ID
     * @param   string $sms_email The user's SMS email address
     * @return  boolean Whether the update was successfull or not
     */
    function updateSMS($usr_id, $sms_email)
    {
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 SET
                    usr_sms_email='" . Misc::runSlashes($sms_email) . "'
                 WHERE
                    usr_id=$usr_id";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method used to update the user account and set the user as a confirmed one.
     *
     * @access  public
     * @param   string $email The email address
     * @return  boolean
     */
    function confirmVisitorAccount($email)
    {
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 SET
                    usr_status='active'
                 WHERE
                    usr_email='" . Misc::runSlashes($email) . "'";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method used to check whether the hash passed in the confirmation URL is
     * a valid one when comparing against the provided email address.
     *
     * @access  public
     * @param   string $email The email address associated with the user account
     * @param   string $hash The md5 hash string to be checked against
     * @return  integer -1 if there was an error in the query, -2 for users that don't exist,
     *                  -3 if it cannot be authenticated and 1 if it did work
     */
    function checkHash($email, $hash)
    {
        $stmt = "SELECT
                    usr_full_name
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    usr_email='" . Misc::runSlashes($email) . "'";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            if ($res == NULL) {
                return -2;
            } else {
                $check_hash = md5($res . md5($email) . $GLOBALS["private_key"]);
                if ($hash != $check_hash) {
                    return -3;
                } else {
                    return 1;
                }
            }
        }
    }


    /**
     * Method used to create a new user account with pending status and send a 
     * confirmation email to the prospective user.
     *
     * @access  public
     * @param   string $role The user role
     * @param   array $projects The list of projects that this user will be associated with
     * @return  integer 1 if the creation worked, -1 otherwise
     */
    function createVisitorAccount($role, $projects)
    {
        global $HTTP_POST_VARS;

        // check for double submits
        if (Auth::userExists($HTTP_POST_VARS["email"])) {
            return -2;
        }
        $prefs = Prefs::getDefaults();
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 (
                    usr_created_date,
                    usr_password,
                    usr_full_name,
                    usr_email,
                    usr_role,
                    usr_preferences,
                    usr_status
                 ) VALUES (
                    '" . Date_API::getCurrentDateGMT() . "',
                    '" . md5(Misc::runSlashes($HTTP_POST_VARS["passwd"])) . "',
                    '" . Misc::runSlashes($HTTP_POST_VARS["full_name"]) . "',
                    '" . Misc::runSlashes($HTTP_POST_VARS["email"]) . "',
                    " . $role . ",
                    '" . addslashes($prefs) . "',
                    'pending'
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $new_usr_id = $GLOBALS["db_api"]->get_last_insert_id();
            // add the project associations!
            for ($i = 0; $i < count($projects); $i++) {
                Project::associateUser($projects[$i], $new_usr_id);
            }
            // send confirmation email to user
            $hash = md5($HTTP_POST_VARS["full_name"] . md5($HTTP_POST_VARS["email"]) . $GLOBALS["private_key"]);
            $msg = "Hello,\n\n";
            $msg .= "We just received a request to create a new account in our issue tracking system. ";
            $msg .= "For security reasons we need you to confirm this request so we can finish the account creation process.\n\n";
            $msg .= "If this is not a real request from you, or you are not interested in creating a new account anymore, ";
            $msg .= "please disregard this email. In a week the request will be erased automatically.\n\n";
            $msg .= "However, if you would like to confirm the new account, please do so by visiting the URL below:\n\n";
            $msg .= APP_BASE_URL . "confirm.php?cat=newuser&email=" . $HTTP_POST_VARS["email"] . "&hash=" . $hash . "\n\n";

            $setup = Setup::load();
            $mail = new Mail_API;
            // need to make this message MIME based
            $mail->setTextBody($msg);
            $mail->send($setup["smtp"]["from"], $HTTP_POST_VARS["email"], APP_SHORT_NAME . ": New Account - Confirmation Required");
            return 1;
        }
    }


    /**
     * Method used to send a confirmation email to the user that is associated
     * to the email address.
     *
     * @access  public
     * @param   string $usr_id The user ID
     * @return  void
     */
    function sendPasswordConfirmationEmail($usr_id)
    {
        $info = User::getDetails($usr_id);
        // send confirmation email to user
        $hash = md5($info["usr_full_name"] . md5($info["usr_email"]) . $GLOBALS["private_key"]);
        $msg = "Hello,\n\n";
        $msg .= "We just received a request to create a new random password for your account in our issue tracking system. ";
        $msg .= "For security reasons we need you to confirm this request so we can finish the password creation process.\n\n";
        $msg .= "If this is not a real request from you, or if you don't need a new password anymore, ";
        $msg .= "please disregard this email.\n\n";
        $msg .= "However, if you would like to confirm this request, please do so by visiting the URL below:\n\n";
        $msg .= APP_BASE_URL . "confirm.php?cat=password&email=" . $info["usr_email"] . "&hash=" . $hash . "\n\n";

        $setup = Setup::load();
        $mail = new Mail_API;
        // need to make this message MIME based
        $mail->setTextBody($msg);
        $mail->send($setup["smtp"]["from"], $info["usr_email"], APP_SHORT_NAME . ": New Password - Confirmation Required");
    }


    /**
     * Method used to confirm the request of a new password and send an email
     * to the user with the new random password.
     *
     * @access  public
     * @param   string $email The email address
     * @return  void
     */
    function confirmNewPassword($email)
    {
        global $HTTP_POST_VARS;

        $usr_id = User::getUserIDByEmail($email);
        // create the new password
        $HTTP_POST_VARS["new_password"] = substr(md5(microtime() . uniqid("")), 0, 12);
        $HTTP_POST_VARS["confirm_password"] = $HTTP_POST_VARS["new_password"];
        User::updatePassword($usr_id, true);
    }


    /**
     * Method used to lookup the user ID of a given email address.
     *
     * @access  public
     * @param   string $email The email address associated with the user account
     * @return  integer The user ID
     */
    function getUserIDByEmail($email)
    {
        static $returns;

        if (!empty($returns[$email])) {
            return $returns[$email];
        }

        $stmt = "SELECT
                    usr_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    usr_email='" . Misc::runSlashes($email) . "'";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            $returns[$email] = $res;
            return $res;
        }
    }


    /**
     * Method used to check whether an user is set to status active 
     * or not.
     *
     * @access  public
     * @param   string $status The status of the user
     * @return  boolean
     */
    function isActiveStatus($status)
    {
        if ($status == 'active') {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Method used to get the list of all active users available in the system 
     * as an associative array of user IDs => user full names.
     *
     * @access  public
     * @param   integer $role The role ID of the user
     * @return  array The associative array of users
     */
    function getActiveAssocList($role = NULL)
    {
        $stmt = "SELECT
                    usr_id,
                    usr_full_name
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    usr_status='active'";
        if ($role != NULL) {
            $stmt .= " AND usr_role > $role ";
        }
        $stmt .= "
                 ORDER BY
                    usr_full_name ASC";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get an associative array of the available roles.
     *
     * @access  public
     * @return  array The list of roles
     */
    function getAssocRoleIDs()
    {
        global $roles;
        reset($roles);
        $assoc_roles = array();
        while (list($key, $value) = each($roles)) {
            $value = str_replace(" ", "_", strtolower($value));
            $assoc_roles[$value] = (integer) $key;
        }
        return $assoc_roles;
    }


    /**
     * Method used to get the full list of roles available in the
     * system.
     *
     * @access  public
     * @return  array The list of roles
     */
    function getRoles($exclude_role = FALSE)
    {
        if ($exclude_role == false) {
            return $GLOBALS["roles"];
        } else {
            $t = array();
            foreach ($GLOBALS["roles"] as $role_id => $role_title) {
                if (strtolower($role_title) != strtolower($exclude_role)) {
                    $t[$role_id] = $role_title;
                }
            }
            return $t;
        }
    }


    /**
     * Method used to get the role title for a specific role ID.
     *
     * @access  public
     * @param   integer $role_id The role ID
     * @return  string The role title
     */
    function getRole($role_id)
    {
        return $GLOBALS["roles"][$role_id];
    }


    /**
     * Method used to get the role ID for a specific role title.
     *
     * @access  public
     * @param   string $role_title The role title
     * @return  integer The role ID
     */
    function getRoleID($role_title)
    {
        global $roles;

        foreach ($roles as $role_id => $role) {
            if (strtolower($role) == strtolower($role_title)) {
                return $role_id;
            }
        }
    }


    /**
     * Method used to get the role for a specific user.
     *
     * @access  public
     * @param   integer $usr_id The user ID
     * @return  integer The role ID
     */
    function getRoleByUser($usr_id)
    {
        static $returns;

        if (!empty($returns[$usr_id])) {
            return $returns[$usr_id];
        }

        $stmt = "SELECT
                    usr_role
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    usr_id=$usr_id";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            return "";
        } else {
            $returns[$usr_id] = $res;
            return $res;
        }
    }


    /**
     * Method used to get the account details of a specific user.
     *
     * @access  public
     * @param   integer $usr_id The user ID
     * @return  array The account details
     */
    function getDetails($usr_id)
    {
        static $returns;

        if (!empty($returns[$usr_id])) {
            return $returns[$usr_id];
        }

        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    usr_id=$usr_id";
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            $res["projects"] = @array_keys(Project::getAssocList($usr_id));
            $returns[$usr_id] = $res;
            return $res;
        }
    }


    /**
     * Method used to get the full name of the specified user.
     *
     * @access  public
     * @param   integer $usr_id The user ID
     * @return  string The user' full name
     */
    function getFullName($usr_id)
    {
        $stmt = "SELECT
                    usr_full_name
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    usr_id=$usr_id";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to change the status of users, making them inactive
     * or active.
     *
     * @access  public
     * @return  boolean
     */
    function changeStatus()
    {
        global $HTTP_POST_VARS;

        // check if the user being inactivated is the last one
        $stmt = "SELECT
                    COUNT(*)
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    usr_status='active'";
        $total_active = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (($total_active < 2) && ($HTTP_POST_VARS["status"] == "inactive")) {
            return false;
        }

        $items = @implode(", ", $HTTP_POST_VARS["items"]);
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 SET
                    usr_status='" . $HTTP_POST_VARS["status"] . "'
                 WHERE
                    usr_id IN ($items)";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method used to update the account password for a specific user.
     *
     * @access  public
     * @param   integer $usr_id The user ID
     * @param   boolean $send_notification Whether to send the notification email or not
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function updatePassword($usr_id, $send_notification = FALSE)
    {
        global $HTTP_POST_VARS;

        if ($HTTP_POST_VARS['new_password'] != $HTTP_POST_VARS['confirm_password']) {
            return -2;
        }
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 SET
                    usr_password='" . md5($HTTP_POST_VARS["new_password"]) . "'
                 WHERE
                    usr_id=$usr_id";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            if ($send_notification) {
                Notification::notifyUserPassword($usr_id, $HTTP_POST_VARS["new_password"]);
            }
            return 1;
        }
    }


    /**
     * Method used to update the account full name for a specific user.
     *
     * @access  public
     * @param   integer $usr_id The user ID
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function updateFullName($usr_id)
    {
        global $HTTP_POST_VARS;

        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 SET
                    usr_full_name='" . Misc::runSlashes($HTTP_POST_VARS["full_name"]) . "'
                 WHERE
                    usr_id=$usr_id";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            Notification::notifyUserAccount($usr_id);
            return 1;
        }
    }


    /**
     * Method used to update the account email for a specific user.
     *
     * @access  public
     * @param   integer $usr_id The user ID
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function updateEmail($usr_id)
    {
        global $HTTP_POST_VARS;

        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 SET
                    usr_email='" . Misc::runSlashes($HTTP_POST_VARS["email"]) . "'
                 WHERE
                    usr_id=$usr_id";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            Notification::notifyUserAccount($usr_id);
            return 1;
        }
    }


    /**
     * Method used to update the account details for a specific user.
     *
     * @access  public
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function update()
    {
        global $HTTP_POST_VARS;

        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 SET
                    usr_full_name='" . Misc::runSlashes($HTTP_POST_VARS["full_name"]) . "',
                    usr_email='" . Misc::runSlashes($HTTP_POST_VARS["email"]) . "',
                    usr_role=" . $HTTP_POST_VARS["role"];
        if (!empty($HTTP_POST_VARS["password"])) {
            $stmt .= ",
                    usr_password='" . md5($HTTP_POST_VARS["password"]) . "'";
        }
        $stmt .= "
                 WHERE
                    usr_id=" . $HTTP_POST_VARS["id"];
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            // update the project associations now
            $stmt = "DELETE FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_user
                     WHERE
                        pru_usr_id=" . $HTTP_POST_VARS["id"];
            $res = $GLOBALS["db_api"]->dbh->query($stmt);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return -1;
            } else {
                for ($i = 0; $i < count($HTTP_POST_VARS["projects"]); $i++) {
                    $stmt = "INSERT INTO
                                " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_user
                             (
                                pru_prj_id,
                                pru_usr_id
                             ) VALUES (
                                " . $HTTP_POST_VARS["projects"][$i] . ",
                                " . $HTTP_POST_VARS["id"] . "
                             )";
                    $res = $GLOBALS["db_api"]->dbh->query($stmt);
                    if (PEAR::isError($res)) {
                        Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                        return -1;
                    }
                }
            }
            if (!empty($HTTP_POST_VARS["password"])) {
                Notification::notifyUserPassword($HTTP_POST_VARS["id"], $HTTP_POST_VARS["password"]);
            } else {
                Notification::notifyUserAccount($HTTP_POST_VARS["id"]);
            }
            return 1;
        }
    }


    /**
     * Method used to add a new user to the system.
     *
     * @access  public
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function insert()
    {
        global $HTTP_POST_VARS;

        $prefs = Prefs::getDefaults();
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 (
                    usr_created_date,
                    usr_password,
                    usr_full_name,
                    usr_email,
                    usr_role,
                    usr_preferences
                 ) VALUES (
                    '" . Date_API::getCurrentDateGMT() . "',
                    '" . md5(Misc::runSlashes($HTTP_POST_VARS["password"])) . "',
                    '" . Misc::runSlashes($HTTP_POST_VARS["full_name"]) . "',
                    '" . Misc::runSlashes($HTTP_POST_VARS["email"]) . "',
                    " . $HTTP_POST_VARS["role"] . ",
                    '" . addslashes($prefs) . "'
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $new_usr_id = $GLOBALS["db_api"]->get_last_insert_id();
            // add the project associations!
            for ($i = 0; $i < count($HTTP_POST_VARS["projects"]); $i++) {
                Project::associateUser($HTTP_POST_VARS["projects"][$i], $new_usr_id);
            }
            // send email to user
            Notification::notifyNewUser($new_usr_id, $HTTP_POST_VARS["password"]);
            return 1;
        }
    }


    /**
     * Method used to get the list of users available in the system.
     *
     * @access  public
     * @return  array The list of users
     */
    function getList()
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 ORDER BY
                    usr_status ASC,
                    usr_full_name ASC";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]["usr_role"] = User::getRole($res[$i]["usr_role"]);
            }
            return $res;
        }
    }


    /**
     * Method used to get an associative array of the user's email address and 
     * user ID.
     *
     * @access  public
     * @return  array The list of users
     */
    function getAssocEmailList()
    {
        static $emails;

        if (!empty($emails)) {
            return $emails;
        }

        $stmt = "SELECT
                    usr_email,
                    usr_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            $emails = $res;
            return $res;
        }
    }


    /**
     * Method used to get an associative array of the user ID and 
     * full name of the users available in the system.
     *
     * @access  public
     * @return  array The list of users
     */
    function getAssocList()
    {
        $stmt = "SELECT
                    usr_id,
                    usr_full_name
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 ORDER BY
                    usr_full_name ASC";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the full name and email for the specified
     * user.
     *
     * @access  public
     * @param   integer $usr_id The user ID
     * @return  array The email and full name
     */
    function getNameEmail($usr_id)
    {
        static $returns;

        if (!empty($returns[$usr_id])) {
            return $returns[$usr_id];
        }

        $stmt = "SELECT
                    usr_full_name,
                    usr_email
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    usr_id=$usr_id";
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            $returns[$usr_id] = $res;
            return $res;
        }
    }


    /**
     * Method used to get the appropriate 'From' header for a 
     * specified user.
     *
     * @access  public
     * @param   integer $usr_id The user ID
     * @return  string The formatted 'From' header
     */
    function getFromHeader($usr_id)
    {
        $info = User::getNameEmail($usr_id);
        return $info["usr_full_name"] . " <" . $info["usr_email"] . ">";
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included User Class');
}
?>