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

use Eventum\Db\DatabaseException;

class Round_Robin
{
    /**
     * Returns the blackout dates according to the user's timezone.
     *
     * @param   DateTime $date The DateTime object associated with the user's timezone
     * @param   int $start The blackout start hour
     * @param   int $end The blackout end hour
     * @return  string[] The blackout dates
     */
    public static function getBlackoutDates($date, $start, $end)
    {
        $start = substr($start, 0, 2);
        $end = substr($end, 0, 2);
        $hour = $date->format('H');

        // if start is AM and end is PM, then use only today
        // if start is AM and end is AM (and end is smaller than start), then use today and tomorrow
        //   - if date is between zero and the end, then use yesterday and today
        // if start is AM and end is AM (and end is bigger than start), then use only today
        // if start is PM and end is PM (and end is smaller than start), then use today and tomorrow
        //   - if date is between zero and the end, then use yesterday and today
        // if start is PM and end is PM (and end is bigger than start), then use only today
        // if start is PM and end is AM, then use today and tomorrow
        //   - if date is between zero and the end, then use yesterday and today
        if (Date_Helper::isAM($start) && Date_Helper::isPM($end)) {
            $first = 0;
            $second = 0;
        }
        if (Date_Helper::isAM($start) && Date_Helper::isAM($end) && $end < $start) {
            if ($hour >= 0 && $hour <= $end) {
                $first = -Date_Helper::DAY;
                $second = 0;
            } else {
                $first = 0;
                $second = Date_Helper::DAY;
            }
        }
        if (Date_Helper::isAM($start) && Date_Helper::isAM($end) && $end > $start) {
            $first = 0;
            $second = 0;
        }
        if (Date_Helper::isPM($start) && Date_Helper::isPM($end) && $end < $start) {
            if (($hour >= 0) && ($hour <= $end)) {
                $first = -Date_Helper::DAY;
                $second = 0;
            } else {
                $first = 0;
                $second = Date_Helper::DAY;
            }
        }
        if (Date_Helper::isPM($start) && Date_Helper::isPM($end) && $end > $start) {
            $first = 0;
            $second = 0;
        }
        if (Date_Helper::isPM($start) && Date_Helper::isAM($end)) {
            if ($hour >= 0 && $hour <= $end) {
                $first = -Date_Helper::DAY;
                $second = 0;
            } else {
                $first = 0;
                $second = Date_Helper::DAY;
            }
        }

        return [
            date('Y-m-d', $date->getTimestamp() + $first),
            date('Y-m-d', $date->getTimestamp() + $second),
        ];
    }

    /**
     * Retrieves the next assignee in the given project's round robin queue.
     *
     * @param   int $prj_id The project ID
     * @return  int The assignee's user ID
     */
    public static function getNextAssignee($prj_id)
    {
        // get the full list of users for the given project
        list($blackout_start, $blackout_end, $users) = self::getUsersByProject($prj_id);
        if (count($users) == 0) {
            return 0;
        }
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
            $timezone = $users[$next_usr_id]['timezone'];
            $user = Date_Helper::getDateTime(false, $timezone);
            list($today, $tomorrow) = self::getBlackoutDates($user, $blackout_start, $blackout_end);
            $first = Date_Helper::getDateTime($today . ' ' . $blackout_start, $timezone);
            $second = Date_Helper::getDateTime($tomorrow . ' ' . $blackout_end, $timezone);

            if ($first < $user && $user < $second) {
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
        if ($assignee_index == (count($user_ids) - 1)) {
            $next_assignee = $user_ids[0];
        } else {
            $next_assignee = $user_ids[++$assignee_index];
        }
        self::markNextAssignee($prj_id, $next_assignee);

        return $assignee;
    }

    /**
     * Marks the next user in the round robin list as the next assignee in the
     * round robin queue.
     *
     * @param   int $prj_id The project ID
     * @param   int $usr_id The assignee's user ID
     * @return  bool
     */
    public static function markNextAssignee($prj_id, $usr_id)
    {
        $prr_id = self::getID($prj_id);
        $stmt = 'UPDATE
                    `round_robin_user`
                 SET
                    rru_next=0
                 WHERE
                    rru_prr_id=?';
        try {
            DB_Helper::getInstance()->query($stmt, [$prr_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        $stmt = 'UPDATE
                    `round_robin_user`
                 SET
                    rru_next=1
                 WHERE
                    rru_usr_id=? AND
                    rru_prr_id=?';
        try {
            DB_Helper::getInstance()->query($stmt, [$usr_id, $prr_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Returns the round robin entry ID associated with a given project.
     *
     * @param   int $prj_id The project ID
     * @return  int The round robin entry ID
     */
    public static function getID($prj_id)
    {
        $stmt = 'SELECT
                    prr_id
                 FROM
                    `project_round_robin`
                 WHERE
                    prr_prj_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$prj_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Retrieves the list of users, round robin blackout hours and their
     * respective preferences with regards to timezones.
     *
     * @param   int $prj_id The project ID
     * @return  array The list of users
     */
    public static function getUsersByProject($prj_id)
    {
        $stmt = 'SELECT
                    usr_id,
                    rru_next,
                    prr_blackout_start,
                    prr_blackout_end
                 FROM
                    `project_round_robin`,
                    `round_robin_user`,
                    `user`
                 WHERE
                    prr_prj_id=? AND
                    prr_id=rru_prr_id AND
                    rru_usr_id=usr_id
                 ORDER BY
                    usr_id ASC';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, [$prj_id]);
        } catch (DatabaseException $e) {
            return [];
        }

        $blackout_start = '';
        $blackout_end = '';
        $t = [];
        foreach ($res as $row) {
            $blackout_start = $row['prr_blackout_start'];
            $blackout_end = $row['prr_blackout_end'];
            $prefs = Prefs::get($row['usr_id']);
            $t[$row['usr_id']] = [
                'timezone' => $prefs['timezone'],
                'is_next' => $row['rru_next'],
            ];
        }

        return [
            $blackout_start,
            $blackout_end,
            $t,
        ];
    }

    /**
     * Creates a new round robin entry.
     *
     * @return  int 1 if the creation worked, -1 otherwise
     */
    public static function insert()
    {
        $blackout_start = $_POST['blackout_start']['Hour'] . ':' . $_POST['blackout_start']['Minute'] . ':00';
        $blackout_end = $_POST['blackout_end']['Hour'] . ':' . $_POST['blackout_end']['Minute'] . ':00';
        $stmt = 'INSERT INTO
                    `project_round_robin`
                 (
                    prr_prj_id,
                    prr_blackout_start,
                    prr_blackout_end
                 ) VALUES (
                    ?, ?, ?
                 )';
        try {
            DB_Helper::getInstance()->query($stmt, [$_POST['project'], $blackout_start, $blackout_end]);
        } catch (DatabaseException $e) {
            return -1;
        }

        $new_id = DB_Helper::get_last_insert_id();
        // add all of the user associated with this round robin entry
        foreach ($_POST['users'] as $usr_id) {
            self::addUserAssociation($new_id, $usr_id);
        }

        return 1;
    }

    /**
     * Associates a round robin entry with a user ID.
     *
     * @param   int $prr_id The round robin entry ID
     * @param   int $usr_id The user ID
     * @return  bool
     */
    public static function addUserAssociation($prr_id, $usr_id)
    {
        $stmt = 'INSERT INTO
                    `round_robin_user`
                 (
                    rru_prr_id,
                    rru_usr_id,
                    rru_next
                 ) VALUES (
                    ?, ?, ?
                 )';
        try {
            DB_Helper::getInstance()->query($stmt, [$prr_id, $usr_id, 0]);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to get the list of round robin entries available in the
     * system.
     *
     * @return  array The list of round robin entries
     */
    public static function getList()
    {
        $stmt = 'SELECT
                    prr_id,
                    prj_title
                 FROM
                    `project_round_robin`,
                    `project`
                 WHERE
                    prr_prj_id=prj_id
                 ORDER BY
                    prj_title ASC';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt);
        } catch (DatabaseException $e) {
            return '';
        }

        // get the list of associated users
        foreach ($res as &$row) {
            $row['users'] = implode(', ', array_values(self::getAssociatedUsers($row['prr_id'])));
        }

        return $res;
    }

    /**
     * Returns an associative array in the form of user id => name of the users
     * associated to a given round robin entry ID.
     *
     * @param   int $prr_id The round robin entry ID
     * @return  array The list of users
     */
    public static function getAssociatedUsers($prr_id)
    {
        $stmt = 'SELECT
                    usr_id,
                    usr_full_name
                 FROM
                    `round_robin_user`,
                    `user`
                 WHERE
                    rru_usr_id=usr_id AND
                    rru_prr_id=?
                 ORDER BY
                    usr_id ASC';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, [$prr_id]);
        } catch (DatabaseException $e) {
            return [];
        }

        return $res;
    }

    /**
     * Method used to get the details of a round robin entry.
     *
     * @param   int $prr_id The round robin entry ID
     * @return  array The round robin entry details
     */
    public static function getDetails($prr_id)
    {
        $stmt = 'SELECT
                    *
                 FROM
                    `project_round_robin`
                 WHERE
                    prr_id=?';
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, [$prr_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        // get all of the user associations here as well
        $res['users'] = array_keys(self::getAssociatedUsers($res['prr_id']));

        return $res;
    }

    /**
     * Method used to update a round robin entry in the system.
     *
     * @return  int 1 if the update worked, -1 otherwise
     */
    public static function update()
    {
        $blackout_start = $_POST['blackout_start']['Hour'] . ':' . $_POST['blackout_start']['Minute'] . ':00';
        $blackout_end = $_POST['blackout_end']['Hour'] . ':' . $_POST['blackout_end']['Minute'] . ':00';
        $stmt = 'UPDATE
                    `project_round_robin`
                 SET
                    prr_prj_id=?,
                    prr_blackout_start=?,
                    prr_blackout_end=?
                 WHERE
                    prr_id=?';
        try {
            DB_Helper::getInstance()->query($stmt, [$_POST['project'], $blackout_start, $blackout_end, $_POST['id']]);
        } catch (DatabaseException $e) {
            return -1;
        }

        // remove all of the associations with users, then add them all again
        self::removeUserAssociations($_POST['id']);
        foreach ($_POST['users'] as $usr_id) {
            self::addUserAssociation($_POST['id'], $usr_id);
        }

        return 1;
    }

    /**
     * Method used to remove the user associations for a given round robin
     * entry ID.
     *
     * @param   int $prr_id The round robin ID
     * @return  bool
     */
    public static function removeUserAssociations($prr_id)
    {
        if (!is_array($prr_id)) {
            $prr_id = [$prr_id];
        }
        $items = DB_Helper::buildList($prr_id);
        $stmt = "DELETE FROM
                    `round_robin_user`
                 WHERE
                    rru_prr_id IN ($items)";
        try {
            DB_Helper::getInstance()->query($stmt, $prr_id);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to remove a round robin entry from the system.
     *
     * @return  bool
     */
    public static function remove()
    {
        $items = $_POST['items'];
        $itemlist = DB_Helper::buildList($items);

        $stmt = "DELETE FROM
                    `project_round_robin`
                 WHERE
                    prr_id IN ($itemlist)";
        try {
            DB_Helper::getInstance()->query($stmt, $items);
        } catch (DatabaseException $e) {
            return false;
        }

        self::removeUserAssociations($items);

        return true;
    }
}
