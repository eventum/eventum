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
// @(#) $Id: s.class.status.php 1.5 04/01/09 05:04:10-00:00 jpradomaia $
//


/**
 * Class to handle all business logic related to the way statuses
 * are represented in the system.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

include_once(APP_INC_PATH . "class.error_handler.php");

class Status
{
    /**
     * Method used to add a new custom status to the system.
     *
     * @access  public
     * @return  integer 1 if the insert worked properly, any other value otherwise
     */
    function insert()
    {
        global $HTTP_POST_VARS;

        if (Validation::isWhitespace($HTTP_POST_VARS['title'])) {
            return -2;
        }
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 (
                    sta_title,
                    sta_rank,
                    sta_color,
                    sta_is_closed
                 ) VALUES (
                    '" . Misc::runSlashes($HTTP_POST_VARS['title']) . "',
                    " . $HTTP_POST_VARS['rank'] . ",
                    '" . Misc::runSlashes($HTTP_POST_VARS['color']) . "',
                    " . $HTTP_POST_VARS['is_closed'] . "
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $new_status_id = $GLOBALS["db_api"]->get_last_insert_id();
            // now populate the project-status mapping table
            foreach ($HTTP_POST_VARS['projects'] as $prj_id) {
                Status::addProjectAssociation($new_status_id, $prj_id);
            }
            return 1;
        }
    }


    /**
     * Method used to update the details of a given custom status.
     *
     * @access  public
     * @return  integer 1 if the update worked properly, any other value otherwise
     */
    function update()
    {
        global $HTTP_POST_VARS;

        if (Validation::isWhitespace($HTTP_POST_VARS["title"])) {
            return -2;
        }
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 SET
                    sta_title='" . Misc::runSlashes($HTTP_POST_VARS["title"]) . "',
                    sta_rank=" . $HTTP_POST_VARS['rank'] . ",
                    sta_color='" . Misc::runSlashes($HTTP_POST_VARS["color"]) . "',
                    sta_is_closed=" . $HTTP_POST_VARS['is_closed'] . "
                 WHERE
                    sta_id=" . $HTTP_POST_VARS["id"];
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            // remove all of the associations with projects, then add them all again
            Status::removeProjectAssociations($HTTP_POST_VARS['id']);
            foreach ($HTTP_POST_VARS['projects'] as $prj_id) {
                Status::addProjectAssociation($HTTP_POST_VARS['id'], $prj_id);
            }
            // XXX: need to update all issues that are not supposed to have the sta_id to '0'
            return 1;
        }
    }


    /**
     * Method used to remove a set of custom statuses.
     *
     * @access  public
     * @return  boolean
     */
    function remove()
    {
        global $HTTP_POST_VARS;

        $items = @implode(", ", $HTTP_POST_VARS["items"]);
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 WHERE
                    sta_id IN ($items)";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            Status::removeProjectAssociations($HTTP_POST_VARS['items']);
            // also set all issues currently set to these statuses to status '0'
            $stmt = "UPDATE
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                     SET
                        iss_sta_id=0
                     WHERE
                        iss_sta_id IN ($items)";
            $GLOBALS["db_api"]->dbh->query($stmt);
            return true;
        }
    }


    /**
     * Method used to add a project association to a status.
     *
     * @access  public
     * @param   integer $sta_id The status ID
     * @param   integer $prj_id The project ID
     * @return  void
     */
    function addProjectAssociation($sta_id, $prj_id)
    {
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_status
                 (
                    prs_sta_id,
                    prs_prj_id
                 ) VALUES (
                    $sta_id,
                    $prj_id
                 )";
        $GLOBALS["db_api"]->dbh->query($stmt);
    }


    /**
     * Method used to remove the project associations for a given
     * custom status.
     *
     * @access  public
     * @param   integer $sta_id The custom status ID
     * @param   integer $prj_id The project ID
     * @return  boolean
     */
    function removeProjectAssociations($sta_id, $prj_id=FALSE)
    {
        if (!is_array($sta_id)) {
            $sta_id = array($sta_id);
        }
        $items = @implode(", ", $sta_id);
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_status
                 WHERE
                    prs_sta_id IN ($items)";
        if ($prj_id) {
            $stmt .= " AND prs_prj_id=$prj_id";
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
     * Method used to get the details of a given status ID.
     *
     * @access  public
     * @param   integer $sta_id The custom status ID
     * @return  array The status details
     */
    function getDetails($sta_id)
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 WHERE
                    sta_id=$sta_id";
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            // get all of the project associations here as well
            $res['projects'] = array_keys(Status::getAssociatedProjects($res['sta_id']));
            return $res;
        }
    }


    /**
     * Method used to get the list of statuses ordered by title.
     *
     * @access  public
     * @return  array The list of statuses
     */
    function getList()
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 ORDER BY
                    sta_title ASC";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            // get the list of associated projects
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]['projects'] = implode(", ", array_values(Status::getAssociatedProjects($res[$i]['sta_id'])));
            }
            return $res;
        }
    }


    /**
     * Method used to get the list of associated projects for a given
     * custom status.
     *
     * @access  public
     * @param   integer $sta_id The custom status ID
     * @return  array The list of projects
     */
    function getAssociatedProjects($sta_id)
    {
        $stmt = "SELECT
                    prj_id,
                    prj_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_status
                 WHERE
                    prj_id=prs_prj_id AND
                    prs_sta_id=$sta_id";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the status ID for a given status title.
     *
     * @access  public
     * @param   string $sta_title The status title
     * @return  integer The status ID
     */
    function getStatusID($sta_title)
    {
        $stmt = "SELECT
                    sta_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 WHERE
                    sta_title='$sta_title'";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the status title for a given status ID.
     *
     * @access  public
     * @param   integer $sta_id The status ID
     * @return  string The status title
     */
    function getStatusTitle($sta_id)
    {
        $stmt = "SELECT
                    sta_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 WHERE
                    sta_id=$sta_id";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the list of available statuses as an associative array
     * in the style of (id => title)
     *
     * @access  public
     * @param   array $prj_id List of project IDs
     * @return  array The list of statuses
     */
    function getAssocStatusList($prj_id)
    {
        if (!is_array($prj_id)) {
            $prj_id = array($prj_id);
        }
        $items = @implode(", ", $prj_id);
        $stmt = "SELECT
                    sta_id,
                    sta_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_status
                 WHERE
                    prs_prj_id IN ($items) AND
                    prs_sta_id=sta_id
                 ORDER BY
                    sta_rank ASC";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the list of available statuses as an associative array
     * in the style of (id => title)
     *
     * @access  public
     * @return  array The list of statuses
     */
    function getAssocList()
    {
        $stmt = "SELECT
                    sta_id,
                    sta_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 ORDER BY
                    sta_title ASC";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the list of available statuses as an associative array
     * in the style of (id => title). Only return the list of statuses that have
     * a 'closed' context.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  array The list of statuses
     */
    function getClosedAssocList($prj_id)
    {
        $stmt = "SELECT
                    sta_id,
                    sta_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_status
                 WHERE
                    prs_prj_id=$prj_id AND
                    prs_sta_id=sta_id AND
                    sta_is_closed=1
                 ORDER BY
                    sta_rank ASC";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the list of statuses and their respective colors
     *
     * @access  public
     * @return  array List of statuses
     */
    function getStatusColors()
    {
        $stmt = "SELECT
                    sta_color,
                    sta_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 ORDER BY
                    sta_rank ASC";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the color for a given status ID
     *
     * @access  public
     * @param   integer $sta_id The status ID
     * @return  string The status color
     */
    function getStatusColor($sta_id)
    {
        static $returns;

        if (!empty($returns[$sta_id])) {
            return $returns[$sta_id];
        }

        $stmt = "SELECT
                    sta_color
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 WHERE
                    sta_id=$sta_id";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            $returns[$sta_id] = $res;
            return $res;
        }
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Status Class');
}
?>