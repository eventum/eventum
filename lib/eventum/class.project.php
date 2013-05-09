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


/**
 * Class to handle the business logic related to the administration
 * of projects in the system.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

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
                    prj_id=" . Misc::escapeInteger($prj_id);
        $res = DB_Helper::getInstance()->getRow($stmt, DB_FETCHMODE_ASSOC);
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
                    prj_id=" . Misc::escapeInteger($prj_id);
        $res = DB_Helper::getInstance()->getOne($stmt);
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
                    prj_id=" . Misc::escapeInteger($prj_id);
        $res = DB_Helper::getInstance()->getOne($stmt);
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
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project
                 SET
                    prj_anonymous_post='" . Misc::escapeString($_POST["anonymous_post"]) . "',
                    prj_anonymous_post_options='" . Misc::escapeString(@serialize($_POST["options"])) . "'
                 WHERE
                    prj_id=" . Misc::escapeInteger($prj_id);
        $res = DB_Helper::getInstance()->query($stmt);
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
        $res = DB_Helper::getInstance()->getAssoc($stmt);
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
                    prj_id=" . Misc::escapeInteger($prj_id);
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
                    prj_title='" . Misc::escapeString($prj_title) . "'";
        $res = DB_Helper::getInstance()->getOne($stmt);
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
                    prj_id=" . Misc::escapeInteger($prj_id);
        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            $returns[$prj_id] = $res;
            return $res;
        }
    }


    /**
     * Method used to get if reporters should be segregated for a project ID
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  boolean If reporters should be segregated
     */
    function getSegregateReporters($prj_id)
    {
        static $returns;

        if (!empty($returns[$prj_id])) {
            return $returns[$prj_id];
        }

        $stmt = "SELECT
                    prj_segregate_reporter
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project
                 WHERE
                    prj_id="  . Misc::escapeInteger($prj_id);
        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return true;
        } else {
            if ($res == 1) {
                $res = true;
            } else {
                $res = false;
            }
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
                    prj_id=" . Misc::escapeInteger($prj_id);
        $res = DB_Helper::getInstance()->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            $res["prj_assigned_users"] = self::getUserColList($res["prj_id"]);
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
        $items = @implode(", ", Misc::escapeInteger($_POST["items"]));
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project
                 WHERE
                    prj_id IN ($items)";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            self::removeUserByProjects($_POST["items"]);
            Category::removeByProjects($_POST["items"]);
            Release::removeByProjects($_POST["items"]);
            Filter::removeByProjects($_POST["items"]);
            Email_Account::removeAccountByProjects($_POST["items"]);
            Issue::removeByProjects($_POST["items"]);
            Custom_Field::removeByProjects($_POST["items"]);
            $statuses = array_keys(Status::getAssocStatusList($_POST["items"]));
            foreach ($_POST["items"] as $prj_id) {
                Status::removeProjectAssociations($statuses, $prj_id);
            }
            Group::disassociateProjects($_POST["items"]);
            return true;
        }
    }


    /**
     * Method used to remove all project/user associations for a given
     * set of projects.
     *
     * @param   array $ids The project IDs
     * @param   array $users_to_not_remove Users that should not be removed
     * @return  boolean
     */
    public static function removeUserByProjects($ids, $users_to_not_remove = false)
    {
        $items = @implode(", ", Misc::escapeInteger($ids));
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_user
                 WHERE
                    pru_prj_id IN ($items)";
        if ($users_to_not_remove != false) {
            $stmt .= " AND\n pru_usr_id NOT IN(" . join(', ', Misc::escapeInteger($users_to_not_remove)) . ")";
        }
        $res = DB_Helper::getInstance()->query($stmt);
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
        if (Validation::isWhitespace($_POST["title"])) {
            return -2;
        }
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project
                 SET
                    prj_title='" . Misc::escapeString($_POST["title"]) . "',
                    prj_status='" . Misc::escapeString($_POST["status"]) . "',
                    prj_lead_usr_id=" . Misc::escapeInteger($_POST["lead_usr_id"]) . ",
                    prj_initial_sta_id=" . Misc::escapeInteger($_POST["initial_status"]) . ",
                    prj_outgoing_sender_name='" . Misc::escapeString($_POST["outgoing_sender_name"]) . "',
                    prj_outgoing_sender_email='" . Misc::escapeString($_POST["outgoing_sender_email"]) . "',
                    prj_mail_aliases='" . Misc::escapeString($_POST["mail_aliases"]) . "',
                    prj_remote_invocation='" . Misc::escapeString($_POST["remote_invocation"]) . "',
                    prj_segregate_reporter='" . Misc::escapeString($_POST["segregate_reporter"]) . "',
                    prj_customer_backend='" . Misc::escapeString($_POST["customer_backend"]) . "',
                    prj_workflow_backend='" . Misc::escapeString($_POST["workflow_backend"]) . "'
                 WHERE
                    prj_id=" . Misc::escapeInteger($_POST["id"]);
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            self::removeUserByProjects(array($_POST["id"]), $_POST["users"]);
            for ($i = 0; $i < count($_POST["users"]); $i++) {
                if ($_POST["users"][$i] == $_POST["lead_usr_id"]) {
                    self::associateUser($_POST["id"], $_POST["users"][$i], User::getRoleID("Manager"));
                } elseif (User::getRoleByUser($_POST["users"][$i], $_POST["id"]) == '') {
                    // users who are now being associated with this project should be set to 'Standard User'
                    self::associateUser($_POST["id"], $_POST["users"][$i], User::getRoleID("Standard User"));
                }
            }
            $statuses = array_keys(Status::getAssocStatusList($_POST["id"]));
            if (count($statuses) > 0) {
                Status::removeProjectAssociations($statuses, $_POST["id"]);
            }
            foreach ($_POST['statuses'] as $sta_id) {
                Status::addProjectAssociation($sta_id, $_POST["id"]);
            }
            return 1;
        }
    }


    /**
     * Method used to associate an user to a project. If the user association already exists
     * no change will be made.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $usr_id The user ID
     * @param   integer $role The role of the user
     * @return  boolean
     */
    function associateUser($prj_id, $usr_id, $role)
    {
        $prj_id = Misc::escapeInteger($prj_id);
        $usr_id = Misc::escapeInteger($usr_id);
        // see if association already exists
        $sql = "SELECT
                    pru_id
                FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_user
                WHERE
                    pru_prj_id = $prj_id AND
                    pru_usr_id = $usr_id";
        $res = DB_Helper::getInstance()->getOne($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            if (empty($res)) {
                $stmt = "INSERT INTO
                            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_user
                         (
                            pru_usr_id,
                            pru_prj_id,
                            pru_role
                         ) VALUES (
                            $usr_id,
                            $prj_id,
                            " . Misc::escapeInteger($role) . "
                         )";
                $res = DB_Helper::getInstance()->query($stmt);
                if (PEAR::isError($res)) {
                    Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                    return false;
                } else {
                    return true;
                }
            }
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
        if (Validation::isWhitespace($_POST["title"])) {
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
                    prj_mail_aliases,
                    prj_remote_invocation,
                    prj_customer_backend,
                    prj_workflow_backend
                 ) VALUES (
                    '" . Date_Helper::getCurrentDateGMT() . "',
                    '" . Misc::escapeString($_POST["title"]) . "',
                    '" . Misc::escapeString($_POST["status"]) . "',
                    " . Misc::escapeInteger($_POST["lead_usr_id"]) . ",
                    " . Misc::escapeInteger($_POST["initial_status"]) . ",
                    '" . Misc::escapeString($_POST["outgoing_sender_name"]) . "',
                    '" . Misc::escapeString($_POST["outgoing_sender_email"]) . "',
                    '" . Misc::escapeString($_POST["mail_aliases"]) . "',
                    '" . Misc::escapeString($_POST["remote_invocation"]) . "',
                    '" . Misc::escapeString($_POST["customer_backend"]) . "',
                    '" . Misc::escapeString($_POST["workflow_backend"]) . "'
                 )";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        }

        $new_prj_id = DB_Helper::get_last_insert_id();
        for ($i = 0; $i < count($_POST["users"]); $i++) {
            if ($_POST["users"][$i] == $_POST["lead_usr_id"]) {
                $role_id = User::getRoleID("Manager");
            } else {
                $role_id = User::getRoleID("Standard User");
            }
            self::associateUser($new_prj_id, $_POST["users"][$i], $role_id);
        }
        foreach ($_POST['statuses'] as $sta_id) {
            Status::addProjectAssociation($sta_id, $new_prj_id);
        }
        Display_Column::setupNewProject($new_prj_id);

        // insert default timetracking categories
        $res = Time_Tracking::addProjectDefaults($new_prj_id);

        return 1;
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
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get an associative array of project ID and title
     * of all projects available in the system to a given user ID.
     *
     * @access  public
     * @param   integer $usr_id The user ID
     * @param   boolean $force_refresh If the cache should not be used.
     * @param   boolean $include_extra If extra data should be included.
     * @return  array The list of projects
     */
    public static function getAssocList($usr_id, $force_refresh = false, $include_extra = false)
    {
        static $returns;

        if ((!empty($returns[$usr_id][$include_extra])) && ($force_refresh != true)) {
            return $returns[$usr_id][$include_extra];
        }

        $stmt = "SELECT
                    prj_id,
                    prj_title";
        if ($include_extra) {
            $stmt .= ",
                    pru_role,
                    prj_status as status";
        }
        $stmt .= "
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_user
                 WHERE
                    prj_id=pru_prj_id AND
                    pru_usr_id=" .  Misc::escapeInteger($usr_id) . " AND
                    (
                        prj_status != 'archived' OR
                        pru_role >= " . User::getRoleID('Manager') . "
                    )
                 ORDER BY
                    prj_title";
        if ($include_extra) {
            $res = DB_Helper::getInstance()->getAssoc($stmt, true, array(), DB_FETCHMODE_ASSOC);
        } else {
            $res = DB_Helper::getInstance()->getAssoc($stmt);
        }
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            if ($include_extra) {
                foreach ($res as $prj_id => $data) {
                    $res[$prj_id]['role'] = User::getRole($data['pru_role']);
                }
            }
            $returns[$usr_id][$include_extra] = $res;
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
                    pru_prj_id=" . Misc::escapeInteger($prj_id) . " AND
                    pru_usr_id=usr_id AND
                    usr_id != " . APP_SYSTEM_USER_ID;
        if ($status != NULL) {
            $stmt .= " AND usr_status='active' ";
        }
        if ($role != NULL) {
            $stmt .= " AND pru_role > " . Misc::escapeInteger($role);
        }
        $stmt .= "
                 ORDER BY
                    usr_full_name ASC";
        $res = DB_Helper::getInstance()->getAssoc($stmt);
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
                    pru_prj_id=" . Misc::escapeInteger($prj_id) . " AND
                    pru_usr_id=usr_id
                 ORDER BY
                    usr_full_name ASC";
        $res = DB_Helper::getInstance()->getCol($stmt);
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
     * @param   boolean $include_no_customer_association Whether to include in the results projects with customer integration or not
     * @return  array List of projects
     */
    function getAll($include_no_customer_association = TRUE)
    {
        $stmt = "SELECT
                    prj_id,
                    prj_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project";
        if (!$include_no_customer_association) {
            $stmt .= " WHERE prj_customer_backend <> '' AND prj_customer_backend IS NOT NULL ";
        }
        $stmt .= "
                 ORDER BY
                    prj_title";
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get a list of emails that are associated with a given
     * project and issue.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The issue ID
     * @return  array List of emails
     */
    function getAddressBookEmails($prj_id, $issue_id)
    {
        $list = self::getAddressBook($prj_id, $issue_id);
        $emails = array();
        foreach ($list as $address => $name) {
            $emails[] = Mail_Helper::getEmailAddress($address);
        }
        return $emails;
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
        static $returns;

        $key = serialize(array($prj_id, $issue_id));
        if (!empty($returns[$key])) {
            return $returns[$key];
        }

        $res = self::getAddressBookAssocList($prj_id, $issue_id);
        if (empty($res)) {
            return "";
        } else {
            $temp = array();
            foreach ($res as $name => $email) {
                $temp["$name <$email>"] = $name;
            }
            $returns[$key] = $temp;
            return $temp;
        }
    }


    /**
     * Method used to get an associative array of names and emails
     * that are associated with a given project and issue.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The issue ID
     * @return  array List of names and emails
     */
    function getAddressBookAssocList($prj_id, $issue_id = FALSE)
    {
        if ($issue_id) {
            $customer_id = Issue::getCustomerID($issue_id);
        }

        $stmt = "SELECT
                    usr_full_name,
                    usr_email
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_user
                 WHERE
                    pru_prj_id=" . Misc::escapeInteger($prj_id) . " AND
                    pru_usr_id=usr_id AND
                    usr_status='active' AND
                    usr_id <> " . APP_SYSTEM_USER_ID;
        if (!empty($customer_id)) {
            $stmt .= " AND (usr_customer_id IS NULL OR usr_customer_id IN (0, " . Misc::escapeInteger($customer_id) . ")) ";
        } else {
            $stmt .= " AND (usr_customer_id IS NULL OR usr_customer_id=0) ";
        }
        $stmt .= "
                 ORDER BY
                    usr_customer_id DESC,
                    usr_full_name ASC";
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
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
        $res = DB_Helper::getInstance()->getAssoc($stmt);
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
     * @param   boolean $only_customer_projects Whether to only include projects with customer integration or not
     * @return  array The list of projects
     */
    function getRemoteAssocListByUser($usr_id, $only_customer_projects = FALSE)
    {
        static $returns;

        if ((!$only_customer_projects) && (!empty($returns[$usr_id]))) {
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
                    pru_usr_id=" . Misc::escapeInteger($usr_id) . " AND
                    prj_remote_invocation='enabled'";
        if ($only_customer_projects) {
            $stmt .= " AND prj_customer_backend <> '' AND prj_customer_backend IS NOT NULL ";
        }
        $stmt .= "
                 ORDER BY
                    prj_title";
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            // don't cache the results when the optional argument is used to avoid getting bogus results
            if (!$only_customer_projects) {
                $returns[$usr_id] = $res;
            }
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
                    pru_prj_id=" . Misc::escapeInteger($prj_id) . " AND
                    pru_usr_id=usr_id";
        if ($status != NULL) {
            $stmt .= " AND usr_status='active' ";
        }
        if ($role != NULL) {
            $stmt .= " AND pru_role > $role ";
        }
        $stmt .= "
                 ORDER BY
                    usr_email ASC";
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            $returns[$prj_id] = $res;
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
    function getReporters($prj_id)
    {

        $stmt = "SELECT
                    DISTINCT usr_id,
                    usr_full_name
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_user,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 WHERE
                    pru_prj_id = " . Misc::escapeInteger($prj_id) . " AND
                    iss_prj_id = " . Misc::escapeInteger($prj_id) . " AND
                    pru_usr_id = usr_id AND
                    usr_id = iss_usr_id
                 ORDER BY
                    usr_full_name ASC";
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            return $res;
        }
    }


    /**
     * Sets the minimum role needed to view a specific field on the issue creation form.
     *
     * @access  public
     * @param   integer $prj_id The project ID.
     * @param   array $settings An array of fields and role is required to view them.
     * @return  integer 1 if the update worked, -1 otherwise.
     */
    function updateFieldDisplaySettings($prj_id, $settings)
    {
        // delete current settings
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_field_display
                 WHERE
                    pfd_prj_id = " . Misc::escapeInteger($prj_id);
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        }

        // insert new values
        foreach ($settings as $field => $min_role) {
            $stmt = "INSERT INTO
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_field_display
                     (
                        pfd_prj_id,
                        pfd_field,
                        pfd_min_role
                     ) VALUES (
                        " . Misc::escapeInteger($prj_id) . ",
                        '" . Misc::escapeString($field) . "',
                        " . Misc::escapeInteger($min_role) . "
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
     * Returns display settings for a specific project.
     *
     * @access public
     * @param   integer $prj_id The project ID
     * @return  array An associative array of minimum role required to access a field.
     */
    function getFieldDisplaySettings($prj_id)
    {
        $stmt = "SELECT
                    pfd_field,
                    pfd_min_role
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_field_display
                 WHERE
                    pfd_prj_id = " . Misc::escapeInteger($prj_id);
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        }
        $fields = self::getDisplayFields();
        foreach ($fields as $field_name => $field_title) {
            if (!isset($res[$field_name])) {
                $res[$field_name] = 0;
            }
        }
        return $res;
    }


    /**
     * Returns an array of fields which can be hidden.
     *
     * @access  public
     * @return  array
     */
    function getDisplayFields()
    {
        return array(
            "category"  =>  ev_gettext("Category"),
            "priority"  =>  ev_gettext("Priority"),
            "assignment"    =>  ev_gettext("Assignment"),
            "release"   =>  ev_gettext("Scheduled Release"),
            "estimated_dev_time"    =>  ev_gettext("Estimated Dev. Time"),
            "group"     =>  ev_gettext("Group"),
            "file"  =>  ev_gettext("File"),
            "private"   =>  ev_gettext("Private")
        );
    }
}
