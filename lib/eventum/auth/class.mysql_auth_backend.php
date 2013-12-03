<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2012 - 2013 Eventum Team.                              |
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
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// | Authors: Bryan Alsdorf <balsdorf@gmail.com>                          |
// +----------------------------------------------------------------------+
//


/**
 * Abstract class for auth backend
 */
class Mysql_Auth_Backend extends Abstract_Auth_Backend
{
    /**
     * Checks whether the provided password match against the email
     * address provided.
     *
     * @access  public
     * @param   string $login The email address to check for
     * @param   string $password The password of the user to check for
     * @return  boolean
     */
    public function verifyPassword($login, $password)
    {
        $usr_id = User::getUserIDByEmail($login, true);
        $user = User::getDetails($usr_id);
        if ($user['usr_password'] == self::hashPassword($password)) {
            self::resetFailedLogins($usr_id);
            return true;
        } else {
            self::incrementFailedLogins($usr_id);
            return false;
        }
    }

    /**
     * Method used to update the account password for a specific user.
     *
     * @access  public
     * @param   integer $usr_id The user ID
     * @param   string  $password The password.
     * @return  boolean
     */
    function updatePassword($usr_id, $password)
    {
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 SET
                    usr_password='" . self::hashPassword($password) . "'
                 WHERE
                    usr_id=" . Misc::escapeInteger($usr_id);
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        }

        # NOTE: this will say updated failed if password is identical to old one
        $updated = DB_Helper::getInstance()->affectedRows();
        return $updated > 0;
    }

    public function getUserIDByLogin($login)
    {
        return User::getUserIDByEmail($login, true);
    }

     /**
     * Increment the failed logins attempts for this user
     *
     * @access  public
     * @param   integer $usr_id The ID of the user
     * @return  boolean
     */
    public function incrementFailedLogins($usr_id)
    {
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 SET
                    usr_failed_logins = usr_failed_logins + 1,
                    usr_last_failed_login = NOW()
                 WHERE
                    usr_id=" . Misc::escapeInteger($usr_id);
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        }
        return true;
    }

    /**
     * Reset the failed logins attempts for this user
     *
     * @access  public
     * @param   integer $usr_id The ID of the user
     * @return  boolean
     */
    public function resetFailedLogins($usr_id)
    {
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 SET
                    usr_failed_logins = 0,
                    usr_last_login = NOW(),
                    usr_last_failed_login = NULL
                 WHERE
                    usr_id=" . Misc::escapeInteger($usr_id);
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        }
        return true;
    }

    /**
     * Returns the true if the account is currently locked because of Back-Off locking
     *
     * @access  public
     * @param   string $usr_id The email address to check for
     * @return  boolean
     */
    function isUserBackOffLocked($usr_id)
    {
        if (!is_int(APP_FAILED_LOGIN_BACKOFF_COUNT)) {
            return false;
        }
        $stmt = "SELECT
                    IF( usr_failed_logins >= " . APP_FAILED_LOGIN_BACKOFF_COUNT . ", NOW() < DATE_ADD(usr_last_failed_login, INTERVAL " . APP_FAILED_LOGIN_BACKOFF_MINUTES . " MINUTE), 0)
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    usr_id=" . Misc::escapeInteger($usr_id);
        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return true;
        }
        return $res == 1;
    }
}
