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
// @(#) $Id: s.class.email_response.php 1.6 03/12/31 17:29:00-00:00 jpradomaia $
//


/**
 * Class to handle the business logic related to the administration
 * of canned email responses in the system.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

include_once(APP_INC_PATH . "class.error_handler.php");
include_once(APP_INC_PATH . "class.misc.php");

class Email_Response
{
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
                    '" . Misc::runSlashes($HTTP_POST_VARS["title"]) . "',
                    '" . Misc::runSlashes($HTTP_POST_VARS["response_body"]) . "'
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
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

        $items = @implode(", ", $HTTP_POST_VARS["items"]);
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_response
                 WHERE
                    ere_id IN ($items)";
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

        if (Validation::isWhitespace($HTTP_POST_VARS["title"])) {
            return -2;
        }
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_response
                 SET
                    ere_title='" . Misc::runSlashes($HTTP_POST_VARS["title"]) . "',
                    ere_response_body='" . Misc::runSlashes($HTTP_POST_VARS["response_body"]) . "'
                 WHERE
                    ere_id=" . $HTTP_POST_VARS["id"];
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
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
            return $res;
        }
    }


    /**
     * Method used to get an associate array of all canned email responses
     * available in the system.
     *
     * @access  public
     * @return  array The list of canned email responses
     */
    function getAssocList()
    {
        $stmt = "SELECT
                    ere_id,
                    ere_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_response
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
     * @return  array The list of canned email responses' bodies.
     */
    function getAssocListBodies()
    {
        $stmt = "SELECT
                    ere_id,
                    ere_response_body
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_response";
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