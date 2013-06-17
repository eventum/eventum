<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2013 Eventum Team.                              |
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


class Round_Robin
{
    /**
     * Returns the blackout dates according to the user's timezone.
     *
     * @access  public
     * @param   object $user The Date object associated with the user's timezone
     * @param   integer $start The blackout start hour
     * @param   integer $end The blackout end hour
     * @return  array The blackout dates
     */
    function getBlackoutDates(&$user, $start, $end)
    {
        $start = substr($start, 0, 2);
        $end = substr($end, 0, 2);

        // if start is AM and end is PM, then use only today
        // if start is AM and end is AM (and end is smaller than start), then use today and tomorrow
        //   - if date is between zero and the end, then use yesterday and today
        // if start is AM and end is AM (and end is bigger than start), then use only today
        // if start is PM and end is PM (and end is smaller than start), then use today and tomorrow
        //   - if date is between zero and the end, then use yesterday and today
        // if start is PM and end is PM (and end is bigger than start), then use only today
        // if start is PM and end is AM, then use today and tomorrow
        //   - if date is between zero and the end, then use yesterday and today
        if ((Date_Helper::isAM($start)) && (Date_Helper::isPM($end))) {
            $first = 0;
            $second = 0;
        }
        if ((Date_Helper::isAM($start)) && (Date_Helper::isAM($end)) && ($end < $start)) {
            if (($user->getHour() >= 0) && ($user->getHour() <= $end)) {
                $first = -Date_Helper::DAY;
                $second = 0;
            } else {
                $first = 0;
                $second = Date_Helper::DAY;
            }
        }
        if ((Date_Helper::isAM($start)) && (Date_Helper::isAM($end)) && ($end > $start)) {
            $first = 0;
            $second = 0;
        }
        if ((Date_Helper::isPM($start)) && (Date_Helper::isPM($end)) && ($end < $start)) {
            if (($user->getHour() >= 0) && ($user->getHour() <= $end)) {
                $first = -Date_Helper::DAY;
                $second = 0;
            } else {
                $first = 0;
                $second = Date_Helper::DAY;
            }
        }
        if ((Date_Helper::isPM($start)) && (Date_Helper::isPM($end)) && ($end > $start)) {
            $first = 0;
            $second = 0;
        }
        if ((Date_Helper::isPM($start)) && (Date_Helper::isAM($end))) {
            if (($user->getHour() >= 0) && ($user->getHour() <= $end)) {
                $first = -Date_Helper::DAY;
                $second = 0;
            } else {
                $first = 0;
                $second = Date_Helper::DAY;
            }
        }

        return array(
            date('Y-m-d', $user->getDate(DATE_FORMAT_UNIXTIME) + $first),
            date('Y-m-d', $user->getDate(DATE_FORMAT_UNIXTIME) + $second)
        );
    }


    /**
     * Retrieves the next assignee in the given project's round robin queue.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  integer The assignee's user ID
     */
    function getNextAssignee($prj_id)
    {
        // get the full list of users for the given project
        list($blackout_start, $blackout_end, $users) = self::getUsersByProject($prj_id);
        if (count($users) == 0) {
            return 0;
        } else {
            $user_ids = array_keys($users);
            $next_usr_id = 0;
            foreach ($users as $usr_id => $details) {
                if ($details['is_next']) {
                    $next_usr_id = $usr_id;
                    break;
                }
            }
            // if no user is currently set as the 'next' assignee,
            // then just get the first one in the list
            if (empty($next_usr_id)) {
                $next_usr_id = $user_ids[0];
            }
            // counter to keep the number of times we found an invalid user
            $ignored_users = 0;
            // check the blackout hours
            do {
                $user = new Date(Date_Helper::getCurrentUnixTimestampGMT());
                $user->convertTZById($users[$next_usr_id]['timezone']);
                list($today, $tomorrow) = self::getBlackoutDates($user, $blackout_start, $blackout_end);
                $first = new Date($today . ' ' . $blackout_start);
                $first->setTZById($users[$next_usr_id]['timezone']);
                $second = new Date($tomorrow . ' ' . $blackout_end);
                $second->setTZById($users[$next_usr_id]['timezone']);
                if ((Date::compare($first, $user) == -1) && (Date::compare($user, $second) == -1)) {
                    $ignored_users++;
                    $current_index = array_search($next_usr_id, $user_ids);
                    // if we reached the end of the list of users and none of them
                    // was a valid one, then just select the first one
                    // however, we want to complete at least one full iteration over the list of users
                    // that is, if we didn't start checking the users in the beginning of the list,
                    // then do another run over the users just in case
                    if (($ignored_users >= count($user_ids)) && ($current_index == (count($user_ids) - 1))) {
                        $assignee = $user_ids[0];
                        break;
                    }
                    // if we reached the end of the list, and we still didn't find an user,
                    // then go back to the beginning of the list one last time
                    if ($current_index == (count($user_ids) - 1)) {
                        $current_index = 0;
                        $next_usr_id = $user_ids[++$current_index];
                        $found = 0;
                        continue;
                    }
                    $next_usr_id = $user_ids[++$current_index];
                    $found = 0;
                } else {
                    $assignee = $next_usr_id;
                    $found = 1;
                }
            } while (!$found);
            // mark the next user in the list as the 'next' assignment
            $assignee_index = array_search($assignee, $user_ids);
            if ($assignee_index == (count($user_ids) -1)) {
                $next_assignee = $user_ids[0];
            } else {
                $next_assignee = $user_ids[++$assignee_index];
            }
            self::markNextAssignee($prj_id, $next_assignee);
            return $assignee;
        }
    }


    /**
     * Marks the next user in the round robin list as the next assignee in the
     * round robin queue.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $usr_id The assignee's user ID
     * @return  boolean
     */
    function markNextAssignee($prj_id, $usr_id)
    {
        $prr_id = self::getID($prj_id);
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "round_robin_user
                 SET
                    rru_next=0
                 WHERE
                    rru_prr_id=$prr_id";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            $stmt = "UPDATE
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "round_robin_user
                     SET
                        rru_next=1
                     WHERE
                        rru_usr_id=" . Misc::escapeInteger($usr_id) . " AND
                        rru_prr_id=$prr_id";
            $res = DB_Helper::getInstance()->query($stmt);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return false;
            } else {
                return true;
            }
        }
    }


    /**
     * Returns the round robin entry ID associated with a given project.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  integer The round robin entry ID
     */
    function getID($prj_id)
    {
        $stmt = "SELECT
                    prr_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_round_robin
                 WHERE
                    prr_prj_id=" . Misc::escapeInteger($prj_id);
        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Retrieves the list of users, round robin blackout hours and their
     * respective preferences with regards to timezones.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  array The list of users
     */
    function getUsersByProject($prj_id)
    {
        $stmt = "SELECT
                    usr_id,
                    rru_next,
                    prr_blackout_start,
                    prr_blackout_end
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_round_robin,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "round_robin_user,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    prr_prj_id=" . Misc::escapeInteger($prj_id) . " AND
                    prr_id=rru_prr_id AND
                    rru_usr_id=usr_id
                 ORDER BY
                    usr_id ASC";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            $blackout_start = '';
            $blackout_end = '';
            $t = array();
            for ($i = 0; $i < count($res); $i++) {
                $blackout_start = $res[$i]['prr_blackout_start'];
                $blackout_end = $res[$i]['prr_blackout_end'];
                $prefs = Prefs::get($res[$i]['usr_id']);
                $t[$res[$i]['usr_id']] = array(
                    'timezone' => $prefs['timezone'],
                    'is_next'  => $res[$i]['rru_next']
                );
            }
            return array(
                $blackout_start,
                $blackout_end,
                $t
            );
        }
    }


    /**
     * Creates a new round robin entry.
     *
     * @access  public
     * @return  integer 1 if the creation worked, -1 otherwise
     */
    function insert()
    {
        $blackout_start = $_POST['blackout_start']['Hour'] . ':' . $_POST['blackout_start']['Minute'] . ':00';
        $blackout_end = $_POST['blackout_end']['Hour'] . ':' . $_POST['blackout_end']['Minute'] . ':00';
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_round_robin
                 (
                    prr_prj_id,
                    prr_blackout_start,
                    prr_blackout_end
                 ) VALUES (
                    " . Misc::escapeInteger($_POST["project"]) . ",
                    '" . Misc::escapeString($blackout_start) . "',
                    '" . Misc::escapeString($blackout_end) . "'
                 )";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $new_id = DB_Helper::get_last_insert_id();
            // add all of the user associated with this round robin entry
            foreach ($_POST['users'] as $usr_id) {
                self::addUserAssociation($new_id, $usr_id);
            }
            return 1;
        }
    }


    /**
     * Associates a round robin entry with a user ID.
     *
     * @access  public
     * @param   integer $prr_id The round robin entry ID
     * @param   integer $usr_id The user ID
     * @return  boolean
     */
    function addUserAssociation($prr_id, $usr_id)
    {
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "round_robin_user
                 (
                    rru_prr_id,
                    rru_usr_id,
                    rru_next
                 ) VALUES (
                    " . Misc::escapeInteger($prr_id) . ",
                    " . Misc::escapeInteger($usr_id) . ",
                    0
                 )";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method used to get the list of round robin entries available in the
     * system.
     *
     * @access  public
     * @return  array The list of round robin entries
     */
    function getList()
    {
        $stmt = "SELECT
                    prr_id,
                    prj_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_round_robin,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project
                 WHERE
                    prr_prj_id=prj_id
                 ORDER BY
                    prj_title ASC";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            // get the list of associated users
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]['users'] = implode(", ", array_values(self::getAssociatedUsers($res[$i]['prr_id'])));
            }
            return $res;
        }
    }


    /**
     * Returns an associative array in the form of user id => name of the users
     * associated to a given round robin entry ID.
     *
     * @access  public
     * @param   integer $prr_id The round robin entry ID
     * @return  array The list of users
     */
    function getAssociatedUsers($prr_id)
    {
        $stmt = "SELECT
                    usr_id,
                    usr_full_name
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "round_robin_user,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    rru_usr_id=usr_id AND
                    rru_prr_id=" . Misc::escapeInteger($prr_id) . "
                 ORDER BY
                    usr_id ASC";
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the details of a round robin entry.
     *
     * @access  public
     * @param   integer $prr_id The round robin entry ID
     * @return  array The round robin entry details
     */
    function getDetails($prr_id)
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_round_robin
                 WHERE
                    prr_id=" . Misc::escapeInteger($prr_id);
        $res = DB_Helper::getInstance()->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            // get all of the user associations here as well
            $res['users'] = array_keys(self::getAssociatedUsers($res['prr_id']));
            return $res;
        }
    }


    /**
     * Method used to update a round robin entry in the system.
     *
     * @access  public
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function update()
    {
        $blackout_start = $_POST['blackout_start']['Hour'] . ':' . $_POST['blackout_start']['Minute'] . ':00';
        $blackout_end = $_POST['blackout_end']['Hour'] . ':' . $_POST['blackout_end']['Minute'] . ':00';
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_round_robin
                 SET
                    prr_prj_id=" . Misc::escapeInteger($_POST["project"]) . ",
                    prr_blackout_start='" . Misc::escapeString($blackout_start) . "',
                    prr_blackout_end='" . Misc::escapeString($blackout_end) . "'
                 WHERE
                    prr_id=" . Misc::escapeInteger($_POST["id"]);
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            // remove all of the associations with users, then add them all again
            self::removeUserAssociations($_POST['id']);
            foreach ($_POST['users'] as $usr_id) {
                self::addUserAssociation($_POST['id'], $usr_id);
            }
            return 1;
        }
    }


    /**
     * Method used to remove the user associations for a given round robin
     * entry ID.
     *
     * @access  public
     * @param   integer $prr_id The round robin ID
     * @return  boolean
     */
    function removeUserAssociations($prr_id)
    {
        if (!is_array($prr_id)) {
            $prr_id = array($prr_id);
        }
        $items = @implode(", ", Misc::escapeInteger($prr_id));
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "round_robin_user
                 WHERE
                    rru_prr_id IN ($items)";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method used to remove a round robin entry from the system.
     *
     * @access  public
     * @return  boolean
     */
    function remove()
    {
        $items = @implode(", ", Misc::escapeInteger($_POST["items"]));
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_round_robin
                 WHERE
                    prr_id IN ($items)";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            self::removeUserAssociations($_POST['items']);
            return true;
        }
    }
}
