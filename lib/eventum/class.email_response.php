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
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// +----------------------------------------------------------------------+


/**
 * Class to handle the business logic related to the administration
 * of canned email responses in the system.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

class Email_Response
{
    /**
     * Method used to add a project association to a email
     * response entry.
     *
     * @access  public
     * @param   integer $ere_id The email response ID
     * @param   integer $prj_id The project ID
     * @return  void
     */
    function addProjectAssociation($ere_id, $prj_id)
    {
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_email_response
                 (
                    per_ere_id,
                    per_prj_id
                 ) VALUES (
                    " . Misc::escapeInteger($ere_id) . ",
                    " . Misc::escapeInteger($prj_id) . "
                 )";
        DB_Helper::getInstance()->query($stmt);
    }


    /**
     * Method used to add a new canned email response to the system.
     *
     * @access  public
     * @return  integer 1 if the insert worked, -1 otherwise
     */
    function insert()
    {
        if (Validation::isWhitespace($_POST["title"])) {
            return -2;
        }
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_response
                 (
                    ere_title,
                    ere_response_body
                 ) VALUES (
                    '" . Misc::escapeString($_POST["title"]) . "',
                    '" . Misc::escapeString($_POST["response_body"]) . "'
                 )";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $new_response_id = DB_Helper::get_last_insert_id();
            // now populate the project-news mapping table
            foreach ($_POST['projects'] as $prj_id) {
                self::addProjectAssociation($new_response_id, $prj_id);
            }
            return 1;
        }
    }


    /**
     * Method used to remove a canned email response from the system.
     *
     * @access  public
     * @return  boolean
     */
    function remove()
    {
        $items = @implode(", ", Misc::escapeInteger($_POST["items"]));
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_response
                 WHERE
                    ere_id IN ($items)";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            self::removeProjectAssociations($_POST['items']);
            return true;
        }
    }


    /**
     * Method used to remove the project associations for a given
     * email response entry.
     *
     * @access  public
     * @param   integer $ere_id The email response ID
     * @param   integer $prj_id The project ID
     * @return  boolean
     */
    function removeProjectAssociations($ere_id, $prj_id=FALSE)
    {
        $ere_id = Misc::escapeInteger($ere_id);
        if (!is_array($ere_id)) {
            $ere_id = array($ere_id);
        }
        $items = @implode(", ", $ere_id);
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_email_response
                 WHERE
                    per_ere_id IN ($items)";
        if ($prj_id) {
            $stmt .= " AND per_prj_id=$prj_id";
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
     * Method used to update a canned email response in the system.
     *
     * @access  public
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function update()
    {
        $_POST['id'] = Misc::escapeInteger($_POST['id']);

        if (Validation::isWhitespace($_POST["title"])) {
            return -2;
        }
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_response
                 SET
                    ere_title='" . Misc::escapeString($_POST["title"]) . "',
                    ere_response_body='" . Misc::escapeString($_POST["response_body"]) . "'
                 WHERE
                    ere_id=" . $_POST["id"];
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            // remove all of the associations with projects, then add them all again
            self::removeProjectAssociations($_POST['id']);
            foreach ($_POST['projects'] as $prj_id) {
                self::addProjectAssociation($_POST['id'], $prj_id);
            }
            return 1;
        }
    }


    /**
     * Method used to get the details of a canned email response for a given
     * response ID.
     *
     * @access  public
     * @param   integer $ere_id The email response ID
     * @return  array The canned email response details
     */
    function getDetails($ere_id)
    {
        $ere_id = Misc::escapeInteger($ere_id);
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_response
                 WHERE
                    ere_id=$ere_id";
        $res = DB_Helper::getInstance()->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            // get all of the project associations here as well
            $res['projects'] = array_keys(self::getAssociatedProjects($res['ere_id']));
            return $res;
        }
    }


    /**
     * Method used to get the list of associated projects for a given
     * email response entry.
     *
     * @access  public
     * @param   integer $ere_id The email response ID
     * @return  array The list of projects
     */
    function getAssociatedProjects($ere_id)
    {
        $ere_id = Misc::escapeInteger($ere_id);
        $stmt = "SELECT
                    prj_id,
                    prj_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_email_response
                 WHERE
                    prj_id=per_prj_id AND
                    per_ere_id=$ere_id";
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the list of canned email responses available in the
     * system.
     *
     * @access  public
     * @return  array The list of canned email responses
     */
    function getList()
    {
        $stmt = "SELECT
                    ere_id,
                    ere_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_response
                 ORDER BY
                    ere_title ASC";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            // get the list of associated projects
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]['projects'] = implode(", ", array_values(self::getAssociatedProjects($res[$i]['ere_id'])));
            }
            return $res;
        }
    }


    /**
     * Method used to get an associate array of all canned email responses
     * available in the system.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  array The list of canned email responses
     */
    function getAssocList($prj_id)
    {
        $stmt = "SELECT
                    ere_id,
                    ere_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_response,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_email_response
                 WHERE
                    per_ere_id=ere_id AND
                    per_prj_id=" . Misc::escapeInteger($prj_id) . "
                 ORDER BY
                    ere_title ASC";
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get an associative array of all of the canned email
     * responses' bodies.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  array The list of canned email responses' bodies.
     */
    function getAssocListBodies($prj_id)
    {
        $stmt = "SELECT
                    ere_id,
                    ere_response_body
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_response,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_email_response
                 WHERE
                    per_ere_id=ere_id AND
                    per_prj_id=" . Misc::escapeInteger($prj_id);
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            // fix the newlines in the response bodies so javascript doesn't die
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]['ere_response_body'] = Misc::escapeWhitespace($res[$i]['ere_response_body']);
                $res[$i]['ere_response_body'] = str_replace('"', '\"', $res[$i]['ere_response_body']);
            }
            return $res;
        }
    }
}
