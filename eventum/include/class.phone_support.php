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
// @(#) $Id: s.class.phone_support.php 1.6 03/12/31 17:29:01-00:00 jpradomaia $
//


/**
 * Class to handle the business logic related to the phone support
 * feature of the application.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

include_once(APP_INC_PATH . "class.error_handler.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.user.php");
include_once(APP_INC_PATH . "class.history.php");
include_once(APP_INC_PATH . "class.issue.php");
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.date.php");

class Phone_Support
{
    /**
     * Method used to get the full listing of phone support entries 
     * associated with a specific issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  array The list of notes
     */
    function getListing($issue_id)
    {
        $stmt = "SELECT
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "phone_support.*,
                    usr_full_name
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "phone_support,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    phs_usr_id=usr_id AND
                    phs_iss_id=$issue_id
                 ORDER BY
                    phs_created_date ASC";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]["phs_description"] = Misc::activateLinks(nl2br(htmlspecialchars($res[$i]["phs_description"])));
                $res[$i]["phs_description"] = Misc::activateIssueLinks($res[$i]["phs_description"]);
                $res[$i]["phs_created_date"] = Date_API::getFormattedDate($res[$i]["phs_created_date"]);
                $res[$i]["phs_time_spent"] = Misc::getFormattedTime($res[$i]["phs_time_spent"]);
            }
            return $res;
        }
    }


    /**
     * Method used to add a phone support entry using the user 
     * interface form available in the application.
     *
     * @access  public
     * @return  integer 1 if the insert worked, -1 or -2 otherwise
     */
    function insert()
    {
        global $HTTP_POST_VARS;

        $usr_id = Auth::getUserID();
        // format the date from the form
        $created_date = sprintf('%04d-%02d-%02d %02d:%02d:%02d', 
            $HTTP_POST_VARS["date"]["Year"], $HTTP_POST_VARS["date"]["Month"],
            $HTTP_POST_VARS["date"]["Day"], $HTTP_POST_VARS["date"]["Hour"],
            $HTTP_POST_VARS["date"]["Minute"], 0);
        // convert the date to GMT timezone
        $created_date = Date_API::getDateGMT($created_date);
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "phone_support
                 (
                    phs_iss_id,
                    phs_usr_id,
                    phs_created_date,
                    phs_type,
                    phs_phone_number,
                    phs_time_spent,
                    phs_description,
                    phs_reason,
                    phs_phone_type,
                    phs_call_from_lname,
                    phs_call_from_fname,
                    phs_call_to_lname,
                    phs_call_to_fname
                 ) VALUES (
                    " . $HTTP_POST_VARS["issue_id"] . ",
                    $usr_id,
                    '" . Misc::escapeString($created_date) . "',
                    '" . Misc::escapeString($HTTP_POST_VARS["type"]) . "',
                    '" . Misc::escapeString($HTTP_POST_VARS["phone_number"]) . "',
                    " . $HTTP_POST_VARS["call_length"] . ",
                    '" . Misc::escapeString($HTTP_POST_VARS["description"]) . "',
                    '" . Misc::escapeString($HTTP_POST_VARS["reason"]) . "',
                    '" . Misc::escapeString($HTTP_POST_VARS["phone_type"]) . "',
                    '" . Misc::escapeString($HTTP_POST_VARS["from_lname"]) . "',
                    '" . Misc::escapeString($HTTP_POST_VARS["from_fname"]) . "',
                    '" . Misc::escapeString($HTTP_POST_VARS["to_lname"]) . "',
                    '" . Misc::escapeString($HTTP_POST_VARS["to_fname"]) . "'
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            Issue::markAsUpdated($HTTP_POST_VARS['issue_id']);
            // need to save a history entry for this
            History::add($HTTP_POST_VARS['issue_id'], $usr_id, History::getTypeID('phone_entry_added'), 
                            'Phone Support entry submitted by ' . User::getFullName($usr_id));
            // XXX: send notifications for the issue being updated (new notification type phone_support?)
            return 1;
        }
    }


    /**
     * Method used to remove a specific phone support entry from the 
     * application.
     *
     * @access  public
     * @param   integer $phone_id The phone support entry ID
     * @return  integer 1 if the removal worked, -1 or -2 otherwise
     */
    function remove($phone_id)
    {
        $stmt = "SELECT
                    phs_iss_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "phone_support
                 WHERE
                    phs_id=$phone_id";
        $issue_id = $GLOBALS["db_api"]->dbh->getOne($stmt);

        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "phone_support
                 WHERE
                    phs_id=$phone_id";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            Issue::markAsUpdated($issue_id);
            // need to save a history entry for this
            History::add($issue_id, Auth::getUserID(), History::getTypeID('phone_entry_removed'), 
                            'Phone Support entry removed by ' . User::getFullName(Auth::getUserID()));
            return 1;
        }
    }


    /**
     * Method used to remove all phone support entries associated with
     * a given set of issues.
     *
     * @access  public
     * @param   array $ids The array of issue IDs
     * @return  boolean
     */
    function removeByIssues($ids)
    {
        $items = implode(", ", $ids);
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "phone_support
                 WHERE
                    phs_iss_id IN ($items)";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }
    
    
    /**
     * Returns the number of calls by a user in a time range.
     * 
     * @access  public
     * @param   string $usr_id The ID of the user
     * @param   integer $start The timestamp of the start date
     * @param   integer $end The timestamp of the end date
     * @return  integer The number of phone calls by the user.
     */
    function getCountByUser($usr_id, $start, $end)
    {
        $stmt = "SELECT
                    COUNT(phs_id)
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "phone_support
                 WHERE
                    phs_created_date BETWEEN '$start' AND '$end' AND
                    phs_usr_id = $usr_id";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        }
        return $res;
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Phone_Support Class');
}
?>