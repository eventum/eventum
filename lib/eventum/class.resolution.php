<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
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
 * of resolutions in the system.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

class Resolution
{
    /**
     * Method used to get the title of a specific resolution.
     *
     * @access  public
     * @param   integer $res_id The resolution ID
     * @return  string The title of the resolution
     */
    function getTitle($res_id)
    {
        $stmt = "SELECT
                    res_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "resolution
                 WHERE
                    res_id=" . Misc::escapeInteger($res_id);
        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to remove resolutions by using the administrative
     * interface of the system.
     *
     * @access  public
     * @return  boolean
     */
    function remove()
    {
        $items = @implode(", ", Misc::escapeInteger($_POST["items"]));
        // gotta fix the issues before removing the resolution
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 SET
                    iss_res_id=0
                 WHERE
                    iss_res_id IN ($items)";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            $stmt = "DELETE FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "resolution
                     WHERE
                        res_id IN ($items)";
            $res = DB_Helper::getInstance()->query($stmt);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return false;
            } else {
                return true;
            }
        }
    }


    /**
     * Method used to update the resolution by using the administrative
     * interface of the system.
     *
     * @access  public
     * @return  integer 1 if the update worked, -1 or -2 otherwise
     */
    function update()
    {
        if (Validation::isWhitespace($_POST["title"])) {
            return -2;
        }
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "resolution
                 SET
                    res_title='" . Misc::escapeString($_POST["title"]) . "'
                 WHERE
                    res_id=" . Misc::escapeInteger($_POST["id"]);
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            return 1;
        }
    }


    /**
     * Method used to get the details of a specific resolution.
     *
     * @access  public
     * @param   integer $res_id The resolution ID
     * @return  array The details of the resolution
     */
    function getDetails($res_id)
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "resolution
                 WHERE
                    res_id=" . Misc::escapeInteger($res_id);
        $res = DB_Helper::getInstance()->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the full list of resolutions.
     *
     * @access  public
     * @return  array The list of resolutions
     */
    function getList()
    {
        $stmt = "SELECT
                    res_id,
                    res_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "resolution
                 ORDER BY
                    res_title ASC";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get a list as an associative array of the
     * resolutions.
     *
     * @access  public
     * @return  array The list of resolutions
     */
    function getAssocList()
    {
        $stmt = "SELECT
                    res_id,
                    res_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "resolution
                 ORDER BY
                    res_id ASC";
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to add a new resolution by using the administrative
     * interface of the system.
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
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "resolution
                 (
                    res_title,
                    res_created_date
                 ) VALUES (
                    '" . Misc::escapeString($_POST["title"]) . "',
                    '" . Date_Helper::getCurrentDateGMT() . "'
                 )";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            return 1;
        }
    }
}
