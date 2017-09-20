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

/**
 * Class to handle the business logic related to the administration
 * of groups.
 * Note! Any reference to the group table must use quoteIdentifier() around
 * the table name due to "group" being a reserved word and some users don't
 * use table prefixes.
 */
class Group
{
    /**
     * Inserts a new group into the database
     *
     * @return int 1 if successful, -1 or -2 otherwise
     */
    public static function insert()
    {
        $stmt = 'INSERT INTO
                    `group`
                 (
                    grp_name,
                    grp_description,
                    grp_manager_usr_id
                 ) VALUES (
                    ?, ?, ?
                 )';
        $params = [$_POST['group_name'], $_POST['description'], $_POST['manager']];
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return -1;
        }

        $grp_id = DB_Helper::get_last_insert_id();

        self::setProjects($grp_id, $_POST['projects']);

        foreach ($_POST['users'] as $usr_id) {
            self::addUser($usr_id, $grp_id);
        }

        return 1;
    }

    /**
     * Updates a group
     *
     * @return int 1 if successful, -1 or -2 otherwise
     */
    public static function update()
    {
        $stmt = 'UPDATE
                    `group`
                 SET
                    grp_name = ?,
                    grp_description = ?,
                    grp_manager_usr_id = ?
                 WHERE
                    grp_id = ?';
        $params = [$_POST['group_name'], $_POST['description'], $_POST['manager'], $_POST['id']];
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return -1;
        }

        self::setProjects($_POST['id'], $_POST['projects']);
        // get old users so we can remove any ones that have been removed
        $existing_users = self::getUsers($_POST['id']);
        $diff = array_diff($existing_users, $_POST['users']);
        if (count($diff) > 0) {
            foreach ($diff as $usr_id) {
                self::removeUser($usr_id, $_POST['id']);
            }
        }
        $diff = array_diff($_POST['users'], $existing_users);
        if (count($diff) > 0) {
            foreach ($diff as $usr_id) {
                self::addUser($usr_id, $_POST['id']);
            }
        }

        return 1;
    }

    /**
     * Removes groups
     *
     * @return int
     */
    public static function remove()
    {
        $items = $_POST['items'];
        foreach ($items as $grp_id) {
            $users = self::getUsers($grp_id);

            $stmt = 'DELETE FROM
                        `group`
                     WHERE
                        grp_id = ?';
            try {
                DB_Helper::getInstance()->query($stmt, [$grp_id]);
            } catch (DatabaseException $e) {
                return -1;
            }

            self::removeProjectsByGroup($grp_id);

            foreach ($users as $usr_id) {
                self::removeUser($usr_id, $grp_id);
            }

            return 1;
        }

        return 1;
    }

    /**
     * Sets projects for the group.
     *
     * @param   int $grp_id the id of the group
     * @param   array $projects an array of projects to associate with the group
     * @return int
     */
    public static function setProjects($grp_id, $projects)
    {
        self::removeProjectsByGroup($grp_id);

        // make new associations
        foreach ($projects as $prj_id) {
            $stmt = 'INSERT INTO
                        `project_group`
                     (
                        pgr_prj_id,
                        pgr_grp_id
                     ) VALUES (
                        ?, ?
                     )';
            try {
                DB_Helper::getInstance()->query($stmt, [$prj_id, $grp_id]);
            } catch (DatabaseException $e) {
                return -1;
            }
        }

        return 1;
    }

    /**
     * Removes all the projects for a group
     *
     * @param   int $grp_id The ID of the group
     * @return int
     */
    private function removeProjectsByGroup($grp_id)
    {
        // delete all current associations
        $stmt = 'DELETE FROM
                    `project_group`
                 WHERE
                    pgr_grp_id = ?';
        try {
            DB_Helper::getInstance()->query($stmt, [$grp_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Returns details about a specific group
     *
     * @param   int $grp_id the ID of the group
     * @return  array An array of group information
     */
    public static function getDetails($grp_id)
    {
        static $returns;

        if (!empty($returns[$grp_id])) {
            return $returns[$grp_id];
        }

        $stmt = 'SELECT
                    grp_name,
                    grp_description,
                    grp_manager_usr_id
                 FROM
                    `group`
                 WHERE
                    grp_id = ?';

        try {
            $res = DB_Helper::getInstance()->getRow($stmt, [$grp_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        if (count($res) > 0) {
            $res['users'] = self::getUsers($grp_id);
            $res['projects'] = self::getProjects($grp_id);
            $res['project_ids'] = array_keys($res['projects']);
            $res['manager'] = User::getFullName($res['grp_manager_usr_id']);
        } else {
            $res = [];
        }
        $returns[$grp_id] = $res;

        return $res;
    }

    /**
     * Returns the name of the group
     *
     * @param   int $grp_id The id of the group
     * @return  string The name of the group
     */
    public static function getName($grp_id)
    {
        if (!$grp_id) {
            return '';
        }
        $details = self::getDetails($grp_id);
        if (count($details) < 1) {
            return '';
        }

        return $details['grp_name'];
    }

    /**
     * Returns a list of groups
     *
     * @return  array An array of group information
     */
    public static function getList()
    {
        $stmt = 'SELECT
                    grp_id,
                    grp_name,
                    grp_description,
                    grp_manager_usr_id
                 FROM
                    `group`
                 ORDER BY
                    grp_name';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt);
        } catch (DatabaseException $e) {
            return -1;
        }

        foreach ($res as &$row) {
            $row['users'] = self::getUsers($row['grp_id']);
            $row['projects'] = self::getProjects($row['grp_id']);
            $row['manager'] = User::getFullName($row['grp_manager_usr_id']);
        }

        return $res;
    }

    /**
     * Returns an associative array of groups
     *
     * @param   int $prj_id The project ID
     * @return  array An associated array of groups
     */
    public static function getAssocList($prj_id)
    {
        static $list;

        if (!empty($list[$prj_id])) {
            return $list[$prj_id];
        }

        $stmt = 'SELECT
                    grp_id,
                    grp_name
                 FROM
                    `group`,
                    `project_group`
                 WHERE
                    grp_id = pgr_grp_id AND
                    pgr_prj_id = ?
                 ORDER BY
                    grp_name';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, [$prj_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        $list[$prj_id] = $res;

        return $res;
    }

    /**
     * Method used to get an associative array of group ID and name
     * of all groups that exist in the system.
     *
     * @return  array List of groups
     */
    public static function getAssocListAllProjects()
    {
        $stmt = 'SELECT
                    grp_id,
                    grp_name
                 FROM
                    `group`
                 ORDER BY
                    grp_name';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Returns an array of user ids who belong to the current group.
     *
     * @param   int $grp_id the ID of the group
     * @return  array An array of usr ids
     */
    public static function getUsers($grp_id)
    {
        $stmt = 'SELECT
                    ugr_usr_id
                 FROM
                    `user_group`
                 WHERE
                    ugr_grp_id = ?';
        try {
            $res = DB_Helper::getInstance()->getColumn($stmt, [$grp_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        return $res;
    }

    /**
     * Returns an array of projects who belong to the current group.
     *
     * @param   int $grp_id the ID of the group
     * @return  array An array of project ids
     */
    public static function getProjects($grp_id)
    {
        $stmt = 'SELECT
                    pgr_prj_id,
                    prj_title
                 FROM
                    `project_group`,
                    `project`
                 WHERE
                    pgr_prj_id = prj_id AND
                    pgr_grp_id = ?';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, [$grp_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        return $res;
    }

    /**
     * Returns a group ID based on group name
     *
     * @param   string $name Name of the group
     * @return  int the ID of the group, or -1 if no group by that name could be found
     */
    public static function getGroupByName($name)
    {
        $stmt = 'SELECT
                    grp_id
                 FROM
                    `group`,
                    `project_group`
                 WHERE
                    grp_id = pgr_grp_id AND
                    grp_name = ?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$name]);
        } catch (DatabaseException $e) {
            return -1;
        }

        if (empty($res)) {
            return -2;
        }

        return $res;
    }

    /**
     * Add a user to the specified group
     *
     * @param   int $usr_id The ID of the user
     * @param   int $grp_id The ID of the group
     * @return  mixed -1 if there is an error, true otherwise
     */
    public static function addUser($usr_id, $grp_id)
    {
        $sql = 'INSERT INTO
                  `user_group`
                SET
                  ugr_usr_id = ?,
                  ugr_grp_id = ?,
                  ugr_created = ?';
        try {
            DB_Helper::getInstance()->query($sql, [$usr_id, $grp_id, Date_Helper::getCurrentDateGMT()]);
        } catch (DatabaseException $e) {
            return -1;
        }

        return true;
    }

    /**
     * Removes a user to the specified group
     *
     * @param   int $usr_id The ID of the user
     * @param   int $grp_id The ID of the group
     * @return  mixed -1 if there is an error, true otherwise
     */
    public static function removeUser($usr_id, $grp_id)
    {
        $sql = 'DELETE FROM
                  `user_group`
                WHERE
                  ugr_usr_id = ? AND
                  ugr_grp_id = ?';
        try {
            DB_Helper::getInstance()->query($sql, [$usr_id, $grp_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        return true;
    }
}
