<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005 MySQL AB                              |
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
// @(#) $Id: s.class.email_response.php 1.6 03/12/31 17:29:00-00:00 jpradomaia $
//

include_once(APP_INC_PATH . "class.error_handler.php");
include_once(APP_INC_PATH . "class.misc.php");

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
        $GLOBALS["db_api"]->dbh->query($stmt);
    }


    /**
     * Method used to add a new canned email response to the system.
     *
     * @access  public
     * @return  integer 1 if the insert worked, -1 otherwise
     */
    function insert()
    {
        global $HTTP_POST_VARS;

        if (Validation::isWhitespace($HTTP_POST_VARS["title"])) {
            return -2;
        }
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_response
                 (
                    ere_title,
                    ere_response_body
                 ) VALUES (
                    '" . Misc::escapeString($HTTP_POST_VARS["title"]) . "',
                    '" . Misc::escapeString($HTTP_POST_VARS["response_body"]) . "'
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $new_response_id = $GLOBALS["db_api"]->get_last_insert_id();
            // now populate the project-news mapping table
            foreach ($HTTP_POST_VARS['projects'] as $prj_id) {
                Email_Response::addProjectAssociation($new_response_id, $prj_id);
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
        global $HTTP_POST_VARS;

        $items = @implode(", ", Misc::escapeInteger($HTTP_POST_VARS["items"]));
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_response
                 WHERE
                    ere_id IN ($items)";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            Email_Response::removeProjectAssociations($HTTP_POST_VARS['items']);
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
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
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
        global $HTTP_POST_VARS;
        
        $HTTP_POST_VARS['id'] = Misc::escapeInteger($HTTP_POST_VARS['id']);

        if (Validation::isWhitespace($HTTP_POST_VARS["title"])) {
            return -2;
        }
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_response
                 SET
                    ere_title='" . Misc::escapeString($HTTP_POST_VARS["title"]) . "',
                    ere_response_body='" . Misc::escapeString($HTTP_POST_VARS["response_body"]) . "'
                 WHERE
                    ere_id=" . $HTTP_POST_VARS["id"];
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            // remove all of the associations with projects, then add them all again
            Email_Response::removeProjectAssociations($HTTP_POST_VARS['id']);
            foreach ($HTTP_POST_VARS['projects'] as $prj_id) {
                Email_Response::addProjectAssociation($HTTP_POST_VARS['id'], $prj_id);
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
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            // get all of the project associations here as well
            $res['projects'] = array_keys(Email_Response::getAssociatedProjects($res['ere_id']));
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
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
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
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            // get the list of associated projects
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]['projects'] = implode(", ", array_values(Email_Response::getAssociatedProjects($res[$i]['ere_id'])));
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
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
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
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
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

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Email_Response Class');
}
?>