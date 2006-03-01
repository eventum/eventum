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
// @(#) $Id: s.class.release.php 1.13 03/12/31 17:29:01-00:00 jpradomaia $
//

include_once(APP_INC_PATH . "class.error_handler.php");
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.validation.php");

/**
 * Class to handle the business logic related to the administration
 * of releases in the system.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

class Release
{
    /**
     * Method used to check whether a release is assignable or not.
     *
     * @access  public
     * @param   integer $pre_id The release ID
     * @return  boolean
     */
    function isAssignable($pre_id)
    {
        $stmt = "SELECT
                    COUNT(*)
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_release
                 WHERE
                    pre_id=" . Misc::escapeInteger($pre_id) . " AND
                    pre_status='available'";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            if ($res == 0) {
                return false;
            } else {
                return true;
            }
        }
    }


    /**
     * Method used to get the details of a specific release.
     *
     * @access  public
     * @param   integer $pre_id The release ID
     * @return  array The details of the release
     */
    function getDetails($pre_id)
    {
        $stmt = "SELECT
                    *,
                    MONTH(pre_scheduled_date) AS scheduled_month,
                    YEAR(pre_scheduled_date) AS scheduled_year
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_release
                 WHERE
                    pre_id=" . Misc::escapeInteger($pre_id);
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the title of a specific release.
     *
     * @access  public
     * @param   integer $pre_id The release ID
     * @return  string The title of the release
     */
    function getTitle($pre_id)
    {
        $stmt = "SELECT
                    pre_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_release
                 WHERE
                    pre_id=" . Misc::escapeInteger($pre_id);
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to remove all releases associated with a specific
     * set of projects.
     *
     * @access  public
     * @param   array $ids The list of projects
     * @return  boolean
     */
    function removeByProjects($ids)
    {
        $items = @implode(", ", Misc::escapeInteger($ids));
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_release
                 WHERE
                    pre_prj_id IN ($items)";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method used to remove releases by using the administrative
     * interface of the system.
     *
     * @access  public
     * @return  boolean
     */
    function remove()
    {
        global $HTTP_POST_VARS;

        $items = @implode(", ", Misc::escapeInteger($HTTP_POST_VARS["items"]));
        // gotta fix the issues that are using this release
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 SET
                    iss_pre_id=0
                 WHERE
                    iss_pre_id IN ($items)";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            $stmt = "DELETE FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_release
                     WHERE
                        pre_id IN ($items)";
            $res = $GLOBALS["db_api"]->dbh->query($stmt);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return false;
            } else {
                return true;
            }
        }
    }


    /**
     * Method used to update the release by using the administrative
     * interface of the system.
     *
     * @access  public
     * @return  integer 1 if the update worked, -1 or -2 otherwise
     */
    function update()
    {
        global $HTTP_POST_VARS;

        if (Validation::isWhitespace($HTTP_POST_VARS["title"])) {
            return -2;
        }
        $scheduled_date = $HTTP_POST_VARS["scheduled_date"]["Year"] . "-" . $HTTP_POST_VARS["scheduled_date"]["Month"] . "-" . $HTTP_POST_VARS["scheduled_date"]["Day"];
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_release
                 SET
                    pre_title='" . Misc::escapeString($HTTP_POST_VARS["title"]) . "',
                    pre_scheduled_date='" . Misc::escapeString($scheduled_date) . "',
                    pre_status='" . Misc::escapeString($HTTP_POST_VARS["status"]) . "'
                 WHERE
                    pre_prj_id=" . Misc::escapeInteger($HTTP_POST_VARS["prj_id"]) . " AND
                    pre_id=" . Misc::escapeInteger($HTTP_POST_VARS["id"]);
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            return 1;
        }
    }


    /**
     * Method used to add a new release by using the administrative
     * interface of the system.
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
        $scheduled_date = $HTTP_POST_VARS["scheduled_date"]["Year"] . "-" . $HTTP_POST_VARS["scheduled_date"]["Month"] . "-" . $HTTP_POST_VARS["scheduled_date"]["Day"];
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_release
                 (
                    pre_prj_id,
                    pre_title,
                    pre_scheduled_date,
                    pre_status
                 ) VALUES (
                    " . Misc::escapeInteger($HTTP_POST_VARS["prj_id"]) . ",
                    '" . Misc::escapeString($HTTP_POST_VARS["title"]) . "',
                    '" . Misc::escapeString($scheduled_date) . "',
                    '" . Misc::escapeString($HTTP_POST_VARS["status"]) . "'
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
     * Method used to get the list of releases associated with a
     * specific project.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  array The list of releases
     */
    function getList($prj_id)
    {
        $stmt = "SELECT
                    pre_id,
                    pre_title,
                    pre_scheduled_date,
                    pre_status
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_release
                 WHERE
                    pre_prj_id=" . Misc::escapeInteger($prj_id) . "
                 ORDER BY
                    pre_scheduled_date ASC";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get a list as an associative array of the
     * releases.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   boolean $show_all_dates If true all releases, not just those with future dates will be returned
     * @return  array The list of releases
     */
    function getAssocList($prj_id, $show_all_dates = false)
    {
        $stmt = "SELECT
                    pre_id,
                    pre_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_release
                 WHERE
                    pre_prj_id=" . Misc::escapeInteger($prj_id) . " AND
                    (
                      pre_status='available'";
        if ($show_all_dates != true) {
            $stmt .= " AND
                      pre_scheduled_date >= '" . gmdate('Y-m-d') . "'";
        }
        $stmt .= "
                    )
                 ORDER BY
                    pre_scheduled_date ASC";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Release Class');
}
?>