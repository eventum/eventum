<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2012 Eventum Team.                              |
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
 * Class to handle the business logic related to the administration
 * of groups.
 * Note! Any reference to the group table must use quoteIdentifier() around
 * the table name due to "group" being a reserved word and some users don't
 * use table prefixes.
 *
 * @version 1.0
 * @author Bryan Alsdorf <bryan@mysql.com>
 */

class Group
{
    /**
     * Inserts a new group into the database
     *
     * @access  public
     * @return integer 1 if successful, -1 or -2 otherwise
     */
    function insert()
    {
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . DB_Helper::getInstance()->quoteIdentifier(APP_TABLE_PREFIX . "group") . "
                 (
                    grp_name,
                    grp_description,
                    grp_manager_usr_id
                 ) VALUES (
                    '" . Misc::escapeString($_POST["group_name"]) . "',
                    '" . Misc::escapeString($_POST["description"]) . "',
                    '" . Misc::escapeInteger($_POST["manager"]) . "'
                 )";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $grp_id = DB_Helper::get_last_insert_id();

            self::setProjects($grp_id, $_POST["projects"]);

            foreach ($_POST["users"] as $usr_id) {
                User::setGroupID($usr_id, $grp_id);
            }
            return 1;
        }
    }


    /**
     * Updates a group
     *
     * @access  public
     * @return integer 1 if successful, -1 or -2 otherwise
     */
    function update()
    {
        $_POST['id'] = Misc::escapeInteger($_POST['id']);

        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . DB_Helper::getInstance()->quoteIdentifier(APP_TABLE_PREFIX . "group") . "
                 SET
                    grp_name = '" . Misc::escapeString($_POST["group_name"]) . "',
                    grp_description = '" . Misc::escapeString($_POST["description"]) . "',
                    grp_manager_usr_id = '" . Misc::escapeInteger($_POST["manager"]) . "'
                 WHERE
                    grp_id = " . $_POST["id"];
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            self::setProjects($_POST["id"], $_POST["projects"]);
            // get old users so we can remove any ones that have been removed
            $existing_users = self::getUsers($_POST["id"]);
            $diff = array_diff($existing_users, Misc::escapeInteger($_POST["users"]));
            if (count($diff) > 0) {
                foreach ($diff as $usr_id) {
                    User::setGroupID($usr_id, false);
                }
            }
            foreach ($_POST["users"] as $usr_id) {
                User::setGroupID($usr_id, $_POST["id"]);
            }
            return 1;
        }
    }


    /**
     * Removes groups
     *
     * @access  public
     */
    function remove()
    {
        foreach (Misc::escapeInteger(@$_POST["items"]) as $grp_id) {
            $users = self::getUsers($grp_id);

            $stmt = "DELETE FROM
                        " . APP_DEFAULT_DB . "." . DB_Helper::getInstance()->quoteIdentifier(APP_TABLE_PREFIX . "group") . "
                     WHERE
                        grp_id = $grp_id";
            $res = DB_Helper::getInstance()->query($stmt);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return -1;
            } else {
                self::removeProjectsByGroup($grp_id);

                foreach ($users as $usr_id) {
                    User::setGroupID($usr_id, false);
                }
                return 1;
            }
        }
    }


    /**
     * Sets projects for the group.
     *
     * @access  public
     * @param   integer $grp_id The id of the group.
     * @param   array $projects An array of projects to associate with the group.
     */
    function setProjects($grp_id, $projects)
    {
        $grp_id = Misc::escapeInteger($grp_id);
        $projects = Misc::escapeInteger($projects);
        self::removeProjectsByGroup($grp_id);

        // make new associations
        foreach ($projects as $prj_id) {
            $stmt = "INSERT INTO
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_group
                     (
                        pgr_prj_id,
                        pgr_grp_id
                     ) VALUES (
                        $prj_id,
                        $grp_id
                     )";
            $res = DB_Helper::getInstance()->query($stmt);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return -1;
            }
        }
        return 1;
    }


    /**
     * Removes all the projects for a group
     *
     * @access  private
     * @param   integer $grp_id The ID of the group
     */
    function removeProjectsByGroup($grp_id)
    {
        // delete all current associations
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_group
                 WHERE
                    pgr_grp_id = " . Misc::escapeInteger($grp_id);
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        }
        return 1;
    }


    /**
     * Removes specified projects from all groups.
     *
     * @access  public
     * @param   array $projects An array of projects to remove from all groups.
     * @return  integer 1 if successful, -1 otherwise
     */
    function disassociateProjects($projects)
    {
        // delete all current associations
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_group
                 WHERE
                    pgr_prj_id IN(" . join(",", Misc::escapeInteger($projects)) . ")";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        }
        return 1;
    }


    /**
     * Returns details about a specific group
     *
     * @access  public
     * @param   integer $grp_id The ID of the group.
     * @return  array An array of group information
     */
    function getDetails($grp_id)
    {
        static $returns;

        $grp_id = Misc::escapeInteger($grp_id);

        if (!empty($returns[$grp_id])) {
            return $returns[$grp_id];
        }

        $stmt = "SELECT
                    grp_name,
                    grp_description,
                    grp_manager_usr_id
                 FROM
                    " . APP_DEFAULT_DB . "." . DB_Helper::getInstance()->quoteIdentifier(APP_TABLE_PREFIX . "group") . "
                 WHERE
                    grp_id = $grp_id";
        $res = DB_Helper::getInstance()->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            if (count($res) > 0) {
                $res["users"] = self::getUsers($grp_id);
                $res["projects"] = self::getProjects($grp_id);
                $res["project_ids"] = array_keys($res["projects"]);
                $res["manager"] = User::getFullName($res["grp_manager_usr_id"]);
            } else {
                $res = array();
            }
            $returns[$grp_id] = $res;
            return $res;
        }
    }


    /**
     * Returns the name of the group
     *
     * @access  public
     * @param   integer $grp_id The id of the group
     * @return  string The name of the group
     */
    function getName($grp_id)
    {
        $grp_id = Misc::escapeInteger($grp_id);
        if (empty($grp_id)) {
            return "";
        }
        $details = self::getDetails($grp_id);
        if (count($details) < 1) {
            return "";
        } else {
            return $details["grp_name"];
        }
    }


    /**
     * Returns a list of groups
     *
     * @access  public
     * @return  array An array of group information
     */
    function getList()
    {
        $stmt = "SELECT
                    grp_id,
                    grp_name,
                    grp_description,
                    grp_manager_usr_id
                 FROM
                    " . APP_DEFAULT_DB . "." . DB_Helper::getInstance()->quoteIdentifier(APP_TABLE_PREFIX . "group") . "
                 ORDER BY
                    grp_name";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]["users"] = self::getUsers($res[$i]['grp_id']);
                $res[$i]["projects"] = self::getProjects($res[$i]['grp_id']);
                $res[$i]["manager"] = User::getFullName($res[$i]["grp_manager_usr_id"]);
            }
            return $res;
        }
    }


    /**
     * Returns an associative array of groups
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  array An associated array of groups
     */
    function getAssocList($prj_id)
    {
        static $list;

        $prj_id = Misc::escapeInteger($prj_id);

        if (!empty($list[$prj_id])) {
            return $list[$prj_id];
        }

        $stmt = "SELECT
                    grp_id,
                    grp_name
                 FROM
                    " . APP_DEFAULT_DB . "." . DB_Helper::getInstance()->quoteIdentifier(APP_TABLE_PREFIX . "group") . ",
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_group
                 WHERE
                    grp_id = pgr_grp_id AND
                    pgr_prj_id = $prj_id
                 ORDER BY
                    grp_name";
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $list[$prj_id] = $res;
            return $res;
        }
    }


    /**
     * Method used to get an associative array of group ID and name
     * of all groups that exist in the system.
     *
     * @access  public
     * @return  array List of groups
     */
    function getAssocListAllProjects()
    {
        $stmt = "SELECT
                    grp_id,
                    grp_name
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "group
                 ORDER BY
                    grp_name";
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Returns an array of users who belong to the current group.
     *
     * @access  public
     * @param   integer $grp_id The ID of the group.
     * @return  array An array of usr ids
     */
    function getUsers($grp_id)
    {
        $stmt = "SELECT
                    usr_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    usr_grp_id = " . Misc::escapeInteger($grp_id);
        $res = DB_Helper::getInstance()->getCol($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        }
        return $res;
    }


    /**
     * Returns an array of projects who belong to the current group.
     *
     * @access  public
     * @param   integer $grp_id The ID of the group.
     * @return  array An array of project ids
     */
    function getProjects($grp_id)
    {
        $stmt = "SELECT
                    pgr_prj_id,
                    prj_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_group,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project
                 WHERE
                    pgr_prj_id = prj_id AND
                    pgr_grp_id = " . Misc::escapeInteger($grp_id);
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        }
        return $res;
    }


    /**
     * Returns a group ID based on group name
     *
     * @access  public
     * @param   string $name Name of the group
     * @return  integer The ID of the group, or -1 if no group by that name could be found.
     */
    function getGroupByName($name)
    {
        $stmt = "SELECT
                    grp_id
                 FROM
                    " . APP_DEFAULT_DB . "." . DB_Helper::getInstance()->quoteIdentifier(APP_TABLE_PREFIX . "group") . ",
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_group
                 WHERE
                    grp_id = pgr_grp_id AND
                    pgr_prj_id = " . Auth::getCurrentProject() . " AND
                    grp_name = '" . Misc::escapeString($name) . "'";
        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        }
        if (empty($res)) {
            return -2;
        } else {
            return $res;
        }
    }
}
