<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
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
// | Authors: Bryan Alsdorf <bryan@mysql.com>                             |
// +----------------------------------------------------------------------+


/**
 * Class designed to handle adding, removing and viewing authorized repliers for an issue.
 *
 * @author  Bryan Alsdorf <bryan@mysql.com>
 */
class Authorized_Replier
{
    /**
     * Method used to get the full list of users (the full names) authorized to
     * reply to emails in a given issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  array The list of users
     */
    function getAuthorizedRepliers($issue_id)
    {
        $issue_id = Misc::escapeInteger($issue_id);
        // split into users and others (those with email address but no real user accounts)
        $repliers = array(
            "users" =>  array(),
            "other" =>  array()
        );

        $stmt = "SELECT
                    iur_id,
                    iur_usr_id,
                    usr_email,
                    if (iur_usr_id = '" . APP_SYSTEM_USER_ID . "', iur_email, usr_full_name) replier,
                    if (iur_usr_id = '" . APP_SYSTEM_USER_ID . "', 'other', 'user') replier_type
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user_replier,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    iur_iss_id=" . Misc::escapeInteger($issue_id) . " AND
                    iur_usr_id=usr_id";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array(
                array(),
                $repliers
            );
        } else {
            // split into users and others (those with email address but no real user accounts)
            $names = array();
            if (count($res) > 0) {
                foreach ($res as $row) {
                    if ($row["iur_usr_id"] == APP_SYSTEM_USER_ID) {
                        $repliers["other"][] = $row;
                    } else {
                        $repliers["users"][] = $row;
                    }
                    $names[] = $row['replier'];
                }
            }
            $repliers["all"]  = array_merge($repliers["users"], $repliers["other"]);
            return array(
                $names,
                $repliers
            );
        }
    }


    /**
     * Removes the specified authorized replier
     *
     * @access  public
     * @param   integer $iur_id The id of the authorized replier
     */
    function removeRepliers($iur_ids)
    {
        $iur_ids = Misc::escapeInteger($iur_ids);

        // get issue_id for logging
        $stmt = "SELECT
                    iur_iss_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user_replier
                 WHERE
                    iur_id IN(" . join(",", $iur_ids) . ")";
        $issue_id = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($issue_id)) {
            Error_Handler::logError(array($issue_id->getMessage(), $issue_id->getDebugInfo()), __FILE__, __LINE__);
        }

        foreach ($iur_ids as $id) {
            $replier = self::getReplier($id);
            $stmt = "DELETE FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user_replier
                     WHERE
                        iur_id IN(" . join(",", $iur_ids) . ")";
            $res = DB_Helper::getInstance()->query($stmt);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return "";
            } else {
                History::add($issue_id, Auth::getUserID(), History::getTypeID('replier_removed'),
                                "Authorized replier $replier removed by " . User::getFullName(Auth::getUserID()));
            }
        }
    }


    /**
     * Adds the specified email address to the list of authorized users.
     *
     * @param   integer $issue_id The id of the issue.
     * @param   string $email The email of the user.
     * @param   boolean $add_history If this should be logged.
     */
    public static function manualInsert($issue_id, $email, $add_history = true)
    {
        if (self::isAuthorizedReplier($issue_id, $email)) {
            return -1;
        } else {
            $email = strtolower(Mail_Helper::getEmailAddress($email));

            $workflow = Workflow::handleAuthorizedReplierAdded(Issue::getProjectID($issue_id), $issue_id, $email);
            if ($workflow === false) {
                // cancel subscribing the user
                return -1;
            }

            // first check if this is an actual user or just an email address
            $usr_id = User::getUserIDByEmail($email, true);
            if (!empty($usr_id)) {
                return self::addUser($issue_id, $usr_id, $add_history);
            }

            $stmt = "INSERT INTO
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user_replier
                     (
                        iur_iss_id,
                        iur_usr_id,
                        iur_email
                     ) VALUES (
                        " . Misc::escapeInteger($issue_id) . ",
                        " . APP_SYSTEM_USER_ID . ",
                        '" . Misc::escapeString($email) . "'
                     )";
            $res = DB_Helper::getInstance()->query($stmt);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return -1;
            } else {
                if ($add_history) {
                    // add the change to the history of the issue
                    $summary = $email . ' added to the authorized repliers list by ' . User::getFullName(Auth::getUserID());
                    History::add($issue_id, Auth::getUserID(), History::getTypeID('replier_other_added'), $summary);
                }
            }
            return 1;
        }
    }


    /**
     * Adds a real user to the authorized repliers list.
     *
     * @access  public
     * @param   integer $issue_id The id of the issue.
     * @param   integer $usr_id The id of the user.
     * @param   boolean $add_history If this should be logged.
     */
    function addUser($issue_id, $usr_id, $add_history = true)
    {
        // don't add customers to this list. They should already be able to send
        if (User::getRoleByUser($usr_id, Issue::getProjectID($issue_id)) == User::getRoleID("Customer")) {
            return -2;
        }

        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user_replier
                 (
                    iur_iss_id,
                    iur_usr_id
                 ) VALUES (
                    " . Misc::escapeInteger($issue_id) . ",
                    " . Misc::escapeInteger($usr_id) . "
                 )";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            if ($add_history) {
                // add the change to the history of the issue
                $summary = User::getFullName($usr_id) . ' added to the authorized repliers list by ' . User::getFullName(Auth::getUserID());
                History::add($issue_id, Auth::getUserID(), History::getTypeID('replier_added'), $summary);
            }
        }
        return 1;
    }


    /**
     * Returns if the specified user is authorized to reply to this issue.
     *
     * @param   integer $issue_id The id of the issue.
     * @param   string  $email The email address to check.
     * @return  boolean If the specified user is allowed to reply to the issue.
     */
    public static function isAuthorizedReplier($issue_id, $email)
    {
        // XXX: Add caching

        $email = strtolower(Mail_Helper::getEmailAddress($email));
        // first check if this is an actual user or just an email address
        $usr_id = User::getUserIDByEmail($email, true);
        if (!empty($usr_id)) {
            // real user, get id
            $is_usr_authorized = self::isUserAuthorizedReplier($issue_id, $usr_id);
            if ($is_usr_authorized) {
                return true;
            }
            // if user is not authorized by user ID, continue to check by email in case the user account was added
            // after the email address was added to authorized repliers list.
        }
        // not a real user
        $stmt = "SELECT
                    COUNT(*) AS total
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user_replier
                 WHERE
                    iur_iss_id=" . Misc::escapeInteger($issue_id) . " AND
                    iur_email='" . Misc::escapeString($email) . "'";
        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            if ($res > 0) {
                return true;
            } else {
                return false;
            }
        }
    }


    /**
     * Returns if the specified usr_id is authorized to reply.
     *
     * @access  public
     * @param   integer $issue_id The id of the issue
     * @param   integer $usr_id The id of the user.
     * @return  boolean If the user is authorized to reply.
     */
    function isUserAuthorizedReplier($issue_id, $usr_id)
    {
        $stmt = "SELECT
                    count(iur_id)
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user_replier
                 WHERE
                    iur_iss_id = " . Misc::escapeInteger($issue_id) . " AND
                    iur_usr_id = " . Misc::escapeInteger($usr_id);
        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            if ($res > 0) {
                return true;
            } else {
                return false;
            }
        }
    }


    /**
     * Returns the replier based on the iur_id
     *
     * @access  public
     * @param   integer iur_id The id of the authorized replier
     * @return  string The name/email of the replier
     */
    function getReplier($iur_id)
    {
        $stmt = "SELECT
                    if (iur_usr_id = '" . APP_SYSTEM_USER_ID . "', iur_email, usr_full_name) replier
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user_replier,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    iur_usr_id = usr_id AND
                    iur_id = " . Misc::escapeInteger($iur_id);
        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        }
        return $res;
    }


    /**
     * Returns the replier based on the given issue and email address combo.
     *
     * @access  public
     * @param   integer $issue_id The id of the issue.
     * @param   string $email The email address of the user
     * @return  integer The id of the replier
     */
    function getReplierIDByEmail($issue_id, $email)
    {
        $stmt = "SELECT
                    iur_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user_replier
                    LEFT JOIN
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                    ON
                        iur_usr_id = usr_id
                 WHERE
                    iur_iss_id = " . Misc::escapeInteger($issue_id) . " AND
                    (iur_email = '" . Misc::escapeString($email) . "' OR usr_email = '" . Misc::escapeString($email) . "')";
        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return 0;
        }
        return $res;
    }


    /**
     * Method used to remotely add an authorized replier to a given issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   integer $usr_id The user ID of the person performing the change
     * @param   boolean $replier The user ID of the authorized replier
     * @return  integer The status ID
     */
    function remoteAddAuthorizedReplier($issue_id, $usr_id, $replier)
    {
        $res = self::manualInsert($issue_id, $replier, false);
        if ($res != -1) {
            // save a history entry about this...
            History::add($issue_id, $usr_id, History::getTypeID('remote_replier_added'),
                            $replier . " remotely added to authorized repliers by " . User::getFullName($usr_id));
        }
        return $res;
    }
}
