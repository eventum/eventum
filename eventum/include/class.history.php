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
// @(#) $Id: s.class.history.php 1.15 03/12/31 17:29:00-00:00 jpradomaia $
//


/**
 * Class to handle the business logic related to the history information for
 * the issues entered in the system.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

include_once(APP_INC_PATH . "class.error_handler.php");
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.date.php");

class History
{
    /**
     * Method used to format the changes done to an issue.
     *
     * @access  public
     * @param   string $old_value The old value for a specific issue parameter
     * @param   string $new_value The new value for a specific issue parameter
     * @return  string The formatted string
     */
    function formatChanges($old_value, $new_value)
    {
        if (empty($old_value)) {
            return '<i>no value set</i> -> ' . $new_value;
        } else {
            return $old_value . ' -> ' . $new_value;
        }
    }


    /**
     * Method used to log the changes made against a specific issue.
     *
     * @access  public
     * @param   integer $iss_id The issue ID
     * @param   string $summary The summary of the changes
     * @return  void
     */
    function add($iss_id, $summary)
    {
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_history
                 (
                    his_iss_id,
                    his_created_date,
                    his_summary
                 ) VALUES (
                    $iss_id,
                    '" . Date_API::getCurrentDateGMT() . "',
                    '" . addslashes($summary) . "'
                 )";
        $GLOBALS["db_api"]->dbh->query($stmt);
    }


    /**
     * Method used to get the list of changes made against a specific issue.
     *
     * @access  public
     * @param   integer $iss_id The issue ID
     * @return  array The list of changes
     */
    function getListing($iss_id)
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_history
                 WHERE
                    his_iss_id=$iss_id
                 ORDER BY
                    his_id DESC";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]["his_created_date"] = Date_API::getFormattedDate($res[$i]["his_created_date"]);
            }
            return $res;
        }
    }


    /**
     * Method used to remove all history entries associated with a 
     * given set of issues.
     *
     * @access  public
     * @param   array $ids The array of issue IDs
     * @return  boolean
     */
    function removeByIssues($ids)
    {
        $items = implode(", ", $ids);
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_history
                 WHERE
                    his_iss_id IN ($items)";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included History Class');
}
?>