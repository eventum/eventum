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
// @(#) $Id: s.class.project.php 1.36 04/01/07 20:59:37-00:00 jpradomaia $
//


/**
 * Class to handle the business logic related to the administration
 * of projects in the system.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

include_once(APP_INC_PATH . "class.error_handler.php");
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.validation.php");
include_once(APP_INC_PATH . "class.date.php");
include_once(APP_INC_PATH . "class.category.php");
include_once(APP_INC_PATH . "class.release.php");
include_once(APP_INC_PATH . "class.filter.php");
include_once(APP_INC_PATH . "class.support.php");
include_once(APP_INC_PATH . "class.issue.php");
include_once(APP_INC_PATH . "class.status.php");

class Project
{
    /**
     * Method used to get the outgoing email sender address associated with
     * a given project.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  array The outgoing sender information
     */
    function getOutgoingSenderAddress($prj_id)
    {
        $stmt = "SELECT
                    prj_outgoing_sender_name,
                    prj_outgoing_sender_email
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project
                 WHERE
                    prj_id=$prj_id";
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array(
                'name'  => '',
                'email' => ''
            );
        } else {
            if (!empty($res)) {
                return array(
                    'name'  => $res['prj_outgoing_sender_name'],
                    'email' => $res['prj_outgoing_sender_email']
                );
            } else {
                return array(
                    'name'  => '',
                    'email' => ''
                );
            }
        }
    }


    /**
     * Method used to get the initial status that should be set to a new issue
     * created and associated with a given project.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  integer The status ID
     */
    function getInitialStatus($prj_id)
    {
        $stmt = "SELECT
                    prj_initial_sta_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project
                 WHERE
                    prj_id=$prj_id";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the options related to the anonymous posting
     * of new issues.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  array The anonymous posting options
     */
    function getAnonymousPostOptions($prj_id)
    {
        $stmt = "SELECT
                    prj_anonymous_post_options
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project
                 WHERE
                    prj_id=$prj_id";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            if (!is_string($res)) {
                $res = (string) $res;
            }
            return @unserialize($res);
        }
    }


    /**
     * Method used to update the anonymous posting related options.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function updateAnonymousPost($prj_id)
    {
        global $HTTP_POST_VARS;

        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project
                 SET
                    prj_anonymous_post='" . $HTTP_POST_VARS["anonymous_post"] . "',
                    prj_anonymous_post_options='" . @serialize($HTTP_POST_VARS["options"]) . "'
                 WHERE
                    prj_id=$prj_id";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            return 1;
        }
    }


    /**
     * Method used to get the list of projects that allow anonymous
     * posting of new issues.
     *
     * @access  public
     * @return  array The list of projects
     */
    function getAnonymousList()
    {
        $stmt = "SELECT
                    prj_id,
                    prj_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project
                 WHERE
                    prj_anonymous_post='enabled'
                 ORDER BY
                    prj_title";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to check whether a project exists or not.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  boolean
     */
    function exists($prj_id)
    {
        $stmt = "SELECT
                    COUNT(*) AS total
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project
                 WHERE
                    prj_id=$prj_id";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
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
     * Method used to get the project ID of the given project title.
     *
     * @access  public
     * @param   string $prj_title The project title
     * @return  integer The project ID
     */
    function getID($prj_title)
    {
        $stmt = "SELECT
                    prj_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project
                 WHERE
                    prj_title='$prj_title'";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the title of a given project ID.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  string The project title
     */
    function getName($prj_id)
    {
        static $returns;

        if (!empty($returns[$prj_id])) {
            return $returns[$prj_id];
        }

        $stmt = "SELECT
                    prj_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project
                 WHERE
                    prj_id=$prj_id";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            $returns[$prj_id] = $res;
            return $res;
        }
    }


    /**
     * Method used to get the details for a given project ID.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  array The project details
     */
    function getDetails($prj_id)
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project
                 WHERE
                    prj_id=$prj_id";
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            $res["prj_assigned_users"] = Project::getUserColList($res["prj_id"]);
            $res['assigned_statuses'] = array_keys(Status::getAssocStatusList($res['prj_id']));
            return $res;
        }
    }


    /**
     * Method used to remove a given set of projects from the system.
     *
     * @access  public
     * @return  boolean
     */
    function remove()
    {
        global $HTTP_POST_VARS;

        $items = @implode(", ", $HTTP_POST_VARS["items"]);
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project
                 WHERE
                    prj_id IN ($items)";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            Project::removeUserByProjects($HTTP_POST_VARS["items"]);
            Category::removeByProjects($HTTP_POST_VARS["items"]);
            Release::removeByProjects($HTTP_POST_VARS["items"]);
            Filter::removeByProjects($HTTP_POST_VARS["items"]);
            Email_Account::removeAccountByProjects($HTTP_POST_VARS["items"]);
            Issue::removeByProjects($HTTP_POST_VARS["items"]);
            Custom_Field::removeByProjects($HTTP_POST_VARS["items"]);
            $statuses = array_keys(Status::getAssocStatusList($HTTP_POST_VARS["items"]));
            foreach ($HTTP_POST_VARS["items"] as $prj_id) {
                Status::removeProjectAssociations($statuses, $prj_id);
            }
            return true;
        }
    }


    /**
     * Method used to remove all project/user associations for a given
     * set of projects.
     *
     * @access  public
     * @param   array $ids The project IDs
     * @return  boolean
     */
    function removeUserByProjects($ids)
    {
        $items = @implode(", ", $ids);
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_user
                 WHERE
                    pru_prj_id IN ($items)";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method used to update the details of the project information.
     *
     * @access  public
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function update()
    {
        global $HTTP_POST_VARS;

        if (Validation::isWhitespace($HTTP_POST_VARS["title"])) {
            return -2;
        }
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project
                 SET
                    prj_title='" . Misc::escapeString($HTTP_POST_VARS["title"]) . "',
                    prj_status='" . Misc::escapeString($HTTP_POST_VARS["status"]) . "',
                    prj_lead_usr_id=" . $HTTP_POST_VARS["lead_usr_id"] . ",
                    prj_initial_sta_id=" . $HTTP_POST_VARS["initial_status"] . ",
                    prj_outgoing_sender_name='" . Misc::escapeString($HTTP_POST_VARS["outgoing_sender_name"]) . "',
                    prj_outgoing_sender_email='" . Misc::escapeString($HTTP_POST_VARS["outgoing_sender_email"]) . "',
                    prj_remote_invocation='" . Misc::escapeString($HTTP_POST_VARS["remote_invocation"]) . "',
                    prj_customer_backend='" . Misc::escapeString($HTTP_POST_VARS["customer_backend"]) . "'
                 WHERE
                    prj_id=" . $HTTP_POST_VARS["id"];
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            Project::removeUserByProjects(array($HTTP_POST_VARS["id"]));
            for ($i = 0; $i < count($HTTP_POST_VARS["users"]); $i++) {
                Project::associateUser($HTTP_POST_VARS["id"], $HTTP_POST_VARS["users"][$i]);
            }
            $statuses = array_keys(Status::getAssocStatusList($HTTP_POST_VARS["id"]));
            if (count($statuses) > 0) {
                Status::removeProjectAssociations($statuses, $HTTP_POST_VARS["id"]);
            }
            foreach ($HTTP_POST_VARS['statuses'] as $sta_id) {
                Status::addProjectAssociation($sta_id, $HTTP_POST_VARS["id"]);
            }
            return 1;
        }
    }


    /**
     * Method used to associate an user to a project.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $usr_id The user ID
     * @return  boolean
     */
    function associateUser($prj_id, $usr_id)
    {
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_user
                 (
                    pru_usr_id,
                    pru_prj_id
                 ) VALUES (
                    $usr_id,
                    $prj_id
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method used to add a new project to the system.
     *
     * @access  public
     * @return  integer 1 if the update worked, -1 or -2 otherwise
     */
    function insert()
    {
        global $HTTP_POST_VARS;

        if (Validation::isWhitespace($HTTP_POST_VARS["title"])) {
            return -2;
        }
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project
                 (
                    prj_created_date,
                    prj_title,
                    prj_status,
                    prj_lead_usr_id,
                    prj_initial_sta_id,
                    prj_outgoing_sender_name,
                    prj_outgoing_sender_email,
                    prj_remote_invocation,
                    prj_customer_backend
                 ) VALUES (
                    '" . Date_API::getCurrentDateGMT() . "',
                    '" . Misc::escapeString($HTTP_POST_VARS["title"]) . "',
                    '" . Misc::escapeString($HTTP_POST_VARS["status"]) . "',
                    " . $HTTP_POST_VARS["lead_usr_id"] . ",
                    " . $HTTP_POST_VARS["initial_status"] . ",
                    '" . Misc::escapeString($HTTP_POST_VARS["outgoing_sender_name"]) . "',
                    '" . Misc::escapeString($HTTP_POST_VARS["outgoing_sender_email"]) . "',
                    '" . Misc::escapeString($HTTP_POST_VARS["remote_invocation"]) . "',
                    '" . Misc::escapeString($HTTP_POST_VARS["customer_backend"]) . "'
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $new_prj_id = $GLOBALS["db_api"]->get_last_insert_id();
            for ($i = 0; $i < count($HTTP_POST_VARS["users"]); $i++) {
                Project::associateUser($new_prj_id, $HTTP_POST_VARS["users"][$i]);
            }
            foreach ($HTTP_POST_VARS['statuses'] as $sta_id) {
                Status::addProjectAssociation($sta_id, $new_prj_id);
            }
            return 1;
        }
    }


    /**
     * Method used to get the list of projects available in the 
     * system.
     *
     * @access  public
     * @return  array The list of projects
     */
    function getList()
    {
        $stmt = "SELECT
                    prj_id,
                    prj_title,
                    prj_status,
                    usr_full_name
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    prj_lead_usr_id=usr_id
                 ORDER BY
                    prj_title";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get an associative array of project ID and title
     * of all projects available in the system.
     *
     * @access  public
     * @param   integer $usr_id The user ID
     * @return  array The list of projects
     */
    function getAssocList($usr_id)
    {
        static $returns;

        if (!empty($returns[$usr_id])) {
            return $returns[$usr_id];
        }

        $stmt = "SELECT
                    prj_id,
                    prj_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_user
                 WHERE
                    prj_id=pru_prj_id AND
                    pru_usr_id=$usr_id
                 ORDER BY
                    prj_title";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            $returns[$usr_id] = $res;
            return $res;
        }
    }


    /**
     * Method used to get the list of users associated with a given project.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   string $status The desired user status
     * @param   integer $role The role ID of the user
     * @return  array The list of users
     */
    function getUserAssocList($prj_id, $status = NULL, $role = NULL)
    {
        $stmt = "SELECT
                    usr_id,
                    usr_full_name
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_user
                 WHERE
                    pru_prj_id=$prj_id AND
                    pru_usr_id=usr_id AND
                    usr_id != " . APP_SYSTEM_USER_ID;
        if ($status != NULL) {
            $stmt .= " AND usr_status='active' ";
        }
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
     * Method used to get a list of user IDs associated with a given
     * project.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  array The list of user IDs
     */
    function getUserColList($prj_id)
    {
        $stmt = "SELECT
                    usr_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_user
                 WHERE
                    pru_prj_id=$prj_id AND
                    pru_usr_id=usr_id
                 ORDER BY
                    usr_full_name ASC";
        $res = $GLOBALS["db_api"]->dbh->getCol($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get an associative array of project ID and title
     * of all projects that exist in the system.
     *
     * @access  public
     * @return  array List of projects
     */
    function getAll()
    {
        $stmt = "SELECT
                    prj_id,
                    prj_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project
                 ORDER BY
                    prj_title";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get a list of names and emails that are 
     * associated with a given project and issue.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The issue ID
     * @return  array List of names and emails
     */
    function getAddressBook($prj_id, $issue_id = FALSE)
    {
        $stmt = "SELECT
                    usr_full_name,
                    usr_email
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_user
                 WHERE
                    pru_prj_id=$prj_id AND
                    pru_usr_id=usr_id AND
                    usr_id != " . APP_SYSTEM_USER_ID . "
                 ORDER BY
                    usr_full_name ASC";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            $temp = array();
            for ($i = 0; $i < count($res); $i++) {
                $key = $res[$i]["usr_full_name"] . " <" . $res[$i]["usr_email"] . ">";
                $temp[$key] = $res[$i]["usr_full_name"];
            }
            return $temp;
        }
    }


    /**
     * Method used to get the list of projects that allow remote 
     * invocation of issues.
     *
     * @access  public
     * @return  array The list of projects
     */
    function getRemoteAssocList()
    {
        $stmt = "SELECT
                    prj_id,
                    prj_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project
                 WHERE
                    prj_remote_invocation='enabled'
                 ORDER BY
                    prj_title";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the list of projects assigned to a given user that 
     * allow remote invocation of issues.
     *
     * @access  public
     * @param   integer $usr_id The user ID
     * @return  array The list of projects
     */
    function getRemoteAssocListByUser($usr_id)
    {
        static $returns;

        if (!empty($returns[$usr_id])) {
            return $returns[$usr_id];
        }

        $stmt = "SELECT
                    prj_id,
                    prj_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_user
                 WHERE
                    prj_id=pru_prj_id AND
                    pru_usr_id=$usr_id AND
                    prj_remote_invocation='enabled'
                 ORDER BY
                    prj_title";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            $returns[$usr_id] = $res;
            return $res;
        }
    }


    /**
     * Method used to get the list of users associated with a given project.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   string $status The desired user status
     * @return  array The list of users
     */
    function getUserEmailAssocList($prj_id, $status = NULL, $role = NULL)
    {
        static $returns;

        if (!empty($returns[$prj_id])) {
            return $returns[$prj_id];
        }

        $stmt = "SELECT
                    usr_id,
                    usr_email
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_user
                 WHERE
                    pru_prj_id=$prj_id AND
                    pru_usr_id=usr_id";
        if ($status != NULL) {
            $stmt .= " AND usr_status='active' ";
        }
        if ($role != NULL) {
            $stmt .= " AND usr_role > $role ";
        }
        $stmt .= "
                 ORDER BY
                    usr_email ASC";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            $returns[$prj_id] = $res;
            return $res;
        }
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Project Class');
}
?>