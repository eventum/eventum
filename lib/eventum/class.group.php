<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2015 Eventum Team.                              |
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
     * @return integer 1 if successful, -1 or -2 otherwise
     */
    public static function insert()
    {
        $stmt = 'INSERT INTO
                    {{%group}}
                 (
                    grp_name,
                    grp_description,
                    grp_manager_usr_id
                 ) VALUES (
                    ?, ?, ?
                 )';
        $params = array($_POST['group_name'], $_POST['description'], $_POST['manager']);
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DbException $e) {
            return -1;
        }

        $grp_id = DB_Helper::get_last_insert_id();

        self::setProjects($grp_id, $_POST['projects']);

        foreach ($_POST['users'] as $usr_id) {
            User::setGroupID($usr_id, $grp_id);
        }

        return 1;
    }

    /**
     * Updates a group
     *
     * @return integer 1 if successful, -1 or -2 otherwise
     */
    public static function update()
    {
        $stmt = 'UPDATE
                    {{%group}}
                 SET
                    grp_name = ?,
                    grp_description = ?,
                    grp_manager_usr_id = ?
                 WHERE
                    grp_id = ?';
        $params = array($_POST['group_name'], $_POST['description'], $_POST['manager'], $_POST['id']);
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DbException $e) {
            return -1;
        }

        self::setProjects($_POST['id'], $_POST['projects']);
        // get old users so we can remove any ones that have been removed
        $existing_users = self::getUsers($_POST['id']);
        $diff = array_diff($existing_users, $_POST['users']);
        if (count($diff) > 0) {
            foreach ($diff as $usr_id) {
                User::setGroupID($usr_id, false);
            }
        }
        foreach ($_POST['users'] as $usr_id) {
            User::setGroupID($usr_id, $_POST['id']);
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
                        {{%group}}
                     WHERE
                        grp_id = ?';
            try {
                DB_Helper::getInstance()->query($stmt, array($grp_id));
            } catch (DbException $e) {
                return -1;
            }

            self::removeProjectsByGroup($grp_id);

            foreach ($users as $usr_id) {
                User::setGroupID($usr_id, false);
            }

            return 1;
        }

        return 1;
    }

    /**
     * Sets projects for the group.
     *
     * @param   integer $grp_id The id of the group.
     * @param   array $projects An array of projects to associate with the group.
     * @return int
     */
    public function setProjects($grp_id, $projects)
    {
        self::removeProjectsByGroup($grp_id);

        // make new associations
        foreach ($projects as $prj_id) {
            $stmt = 'INSERT INTO
                        {{%project_group}}
                     (
                        pgr_prj_id,
                        pgr_grp_id
                     ) VALUES (
                        ?, ?
                     )';
            try {
                DB_Helper::getInstance()->query($stmt, array($prj_id, $grp_id));
            } catch (DbException $e) {
                return -1;
            }
        }

        return 1;
    }

    /**
     * Removes all the projects for a group
     *
     * @param   integer $grp_id The ID of the group
     * @return int
     */
    private function removeProjectsByGroup($grp_id)
    {
        // delete all current associations
        $stmt = 'DELETE FROM
                    {{%project_group}}
                 WHERE
                    pgr_grp_id = ?';
        try {
            DB_Helper::getInstance()->query($stmt, array($grp_id));
        } catch (DbException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Removes specified projects from all groups.
     *
     * @param   array $projects An array of projects to remove from all groups.
     * @return  integer 1 if successful, -1 otherwise
     */
    public static function disassociateProjects($projects)
    {
        // delete all current associations
        $stmt = 'DELETE FROM
                    {{%project_group}}
                 WHERE

                    pgr_prj_id IN (' . DB_Helper::buildList($projects) . ')';
        try {
            DB_Helper::getInstance()->query($stmt, $projects);
        } catch (DbException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Returns details about a specific group
     *
     * @param   integer $grp_id The ID of the group.
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
                    {{%group}}
                 WHERE
                    grp_id = ?';

        try {
            $res = DB_Helper::getInstance()->getRow($stmt, array($grp_id));
        } catch (DbException $e) {
            return -1;
        }

        if (count($res) > 0) {
            $res['users'] = self::getUsers($grp_id);
            $res['projects'] = self::getProjects($grp_id);
            $res['project_ids'] = array_keys($res['projects']);
            $res['manager'] = User::getFullName($res['grp_manager_usr_id']);
        } else {
            $res = array();
        }
        $returns[$grp_id] = $res;

        return $res;
    }

    /**
     * Returns the name of the group
     *
     * @param   integer $grp_id The id of the group
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
                    {{%group}}
                 ORDER BY
                    grp_name';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt);
        } catch (DbException $e) {
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
     * @param   integer $prj_id The project ID
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
                    {{%group}},
                    {{%project_group}}
                 WHERE
                    grp_id = pgr_grp_id AND
                    pgr_prj_id = ?
                 ORDER BY
                    grp_name';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, array($prj_id));
        } catch (DbException $e) {
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
                    {{%group}}
                 ORDER BY
                    grp_name';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt);
        } catch (DbException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Returns an array of user ids who belong to the current group.
     *
     * @param   integer $grp_id The ID of the group.
     * @return  array An array of usr ids
     */
    public static function getUsers($grp_id)
    {
        $stmt = 'SELECT
                    usr_id
                 FROM
                    {{%user}}
                 WHERE
                    usr_grp_id = ?';
        try {
            $res = DB_Helper::getInstance()->getColumn($stmt, array($grp_id));
        } catch (DbException $e) {
            return -1;
        }

        return $res;
    }

    /**
     * Returns an array of projects who belong to the current group.
     *
     * @param   integer $grp_id The ID of the group.
     * @return  array An array of project ids
     */
    public static function getProjects($grp_id)
    {
        $stmt = 'SELECT
                    pgr_prj_id,
                    prj_title
                 FROM
                    {{%project_group}},
                    {{%project}}
                 WHERE
                    pgr_prj_id = prj_id AND
                    pgr_grp_id = ?';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, array($grp_id));
        } catch (DbException $e) {
            return -1;
        }

        return $res;
    }

    /**
     * Returns a group ID based on group name
     *
     * @param   string $name Name of the group
     * @return  integer The ID of the group, or -1 if no group by that name could be found.
     */
    public function getGroupByName($name)
    {
        $stmt = 'SELECT
                    grp_id
                 FROM
                    {{%group}},
                    {{%project_group}}
                 WHERE
                    grp_id = pgr_grp_id AND
                    grp_name = ?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, array($name));
        } catch (DbException $e) {
            return -1;
        }

        if (empty($res)) {
            return -2;
        }

        return $res;
    }
}
