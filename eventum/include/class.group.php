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
// | Authors: Bryan Alsdorf <bryan@mysql.com>                             |
// +----------------------------------------------------------------------+
//
// @(#) $Id$
//

include_once(APP_INC_PATH . "class.error_handler.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.prefs.php");
include_once(APP_INC_PATH . "class.date.php");
include_once(APP_INC_PATH . "class.project.php");


/**
 * Class to handle the business logic related to the administration
 * of groups.
 * Note! Any reference to the group table must use ` around the table name
 * due to "group" being a reserved word and some users don't use table prefixes.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
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
        GLOBAL $HTTP_POST_VARS;
        
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . ".`" . APP_TABLE_PREFIX . "group`
                 (
                    grp_name,
                    grp_description,
                    grp_manager_usr_id
                 ) VALUES (
                    '" . Misc::escapeString($HTTP_POST_VARS["group_name"]) . "',
                    '" . Misc::escapeString($HTTP_POST_VARS["description"]) . "',
                    '" . $HTTP_POST_VARS["manager"] . "'
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $grp_id = $GLOBALS["db_api"]->get_last_insert_id();
            
            Group::setProjects($grp_id, $HTTP_POST_VARS["projects"]);
            
            foreach ($HTTP_POST_VARS["users"] as $usr_id) {
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
        GLOBAL $HTTP_POST_VARS;
        
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . ".`" . APP_TABLE_PREFIX . "group`
                 SET
                    grp_name = '" . Misc::escapeString($HTTP_POST_VARS["group_name"]) . "',
                    grp_description = '" . Misc::escapeString($HTTP_POST_VARS["description"]) . "',
                    grp_manager_usr_id = '" . $HTTP_POST_VARS["manager"] . "'
                 WHERE
                    grp_id = " . $HTTP_POST_VARS["id"];
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            Group::setProjects($HTTP_POST_VARS["id"], $HTTP_POST_VARS["projects"]);
            
            // get old users so we can remove any ones that have been removed
            $existing_users = Group::getUsers($HTTP_POST_VARS["id"]);
            $diff = array_diff($existing_users, $HTTP_POST_VARS["users"]);
            if (count($diff) > 0) {
                foreach ($diff as $usr_id) {
                    User::setGroupID($usr_id, false);
                }
            }
            
            foreach ($HTTP_POST_VARS["users"] as $usr_id) {
                User::setGroupID($usr_id, $HTTP_POST_VARS["id"]);
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
        GLOBAL $HTTP_POST_VARS;
        
        foreach (@$HTTP_POST_VARS["items"] as $grp_id) {
            $users = Group::getUsers($grp_id);
            
            $stmt = "DELETE FROM
                        " . APP_DEFAULT_DB . ".`" . APP_TABLE_PREFIX . "group`
                     WHERE
                        grp_id = $grp_id";
            $res = $GLOBALS["db_api"]->dbh->query($stmt);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return -1;
            } else {
                Group::removeProjectsByGroup($grp_id);
                
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
        Group::removeProjectsByGroup($grp_id);
        
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
            $res = $GLOBALS["db_api"]->dbh->query($stmt);
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
                    pgr_grp_id = $grp_id";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
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
                    pgr_prj_id IN(" . join(",", $projects) . ")";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
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
        
        if (!empty($returns[$grp_id])) {
            return $returns[$grp_id];
        }
        
        $stmt = "SELECT
                    grp_name,
                    grp_description,
                    grp_manager_usr_id
                 FROM
                    " . APP_DEFAULT_DB . ".`" . APP_TABLE_PREFIX . "group`
                 WHERE
                    grp_id = $grp_id";
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            if (count($res) > 0) {
                $res["users"] = Group::getUsers($grp_id);
                $res["projects"] = Group::getProjects($grp_id);
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
        if (empty($grp_id)) {
            return "";
        }
        $details = Group::getDetails($grp_id);
        if (count($details) < 1) {
            return "";
        } else {
            return $details["grp_name"];
        }
    }


    /**
     * Returns a list of projects
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
                    " . APP_DEFAULT_DB . ".`" . APP_TABLE_PREFIX . "group`
                 ORDER BY
                    grp_name";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]["users"] = Group::getUsers($res[$i]['grp_id']);
                $res[$i]["projects"] = Group::getProjects($res[$i]['grp_id']);
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

        if (!empty($list[$prj_id])) {
            return $list[$prj_id];
        }

        $stmt = "SELECT
                    grp_id,
                    grp_name
                 FROM
                    " . APP_DEFAULT_DB . ".`" . APP_TABLE_PREFIX . "group`,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_group
                 WHERE
                    grp_id = pgr_grp_id AND
                    pgr_prj_id = $prj_id
                 ORDER BY
                    grp_name";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $list[$prj_id] = $res;
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
                    usr_grp_id = $grp_id";
        $res = $GLOBALS["db_api"]->dbh->getCol($stmt);
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
                    pgr_grp_id = $grp_id";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
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
                    " . APP_DEFAULT_DB . ".`" . APP_TABLE_PREFIX . "group`,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_group
                 WHERE
                    grp_id = pgr_grp_id AND
                    pgr_prj_id = " . Auth::getCurrentProject() . " AND
                    grp_name = '" . Misc::escapeString($name) . "'";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
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

?>