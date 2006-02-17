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
// @(#) $Id: s.class.history.php 1.15 03/12/31 17:29:00-00:00 jpradomaia $
//

include_once(APP_INC_PATH . "class.error_handler.php");
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.mime_helper.php");
include_once(APP_INC_PATH . "class.date.php");

/**
 * Class to handle the business logic related to the history information for
 * the issues entered in the system.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

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
            return 'no value set -> ' . $new_value;
        } elseif (empty($new_value)) {
            return $old_value . ' -> no value set';
        } else {
            return $old_value . ' -> ' . $new_value;
        }
    }


    /**
     * Method used to log the changes made against a specific issue.
     *
     * @access  public
     * @param   integer $iss_id The issue ID
     * @param   integer $usr_id The ID of the user.
     * @param   integer $htt_id The type ID of this history event.
     * @param   string $summary The summary of the changes
     * @param   boolean $hide If this history item should be hidden.
     * @return  void
     */
    function add($iss_id, $usr_id, $htt_id, $summary, $hide = false)
    {
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_history
                 (
                    his_iss_id,
                    his_usr_id,
                    his_created_date,
                    his_summary,
                    his_htt_id";
        if ($hide == true) {
            $stmt .= ", his_is_hidden";
        }
        $stmt .= ") VALUES (
                    " . Misc::escapeInteger($iss_id) . ",
                    " . Misc::escapeInteger($usr_id) . ",
                    '" . Date_API::getCurrentDateGMT() . "',
                    '" . Misc::escapeString($summary) . "',
                    $htt_id";
        if ($hide == true) {
            $stmt .= ", 1";
        }
        $stmt .= ")";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        }
    }


    /**
     * Method used to get the list of changes made against a specific issue.
     *
     * @access  public
     * @param   integer $iss_id The issue ID
     * @param   string $order_by The order to sort the history
     * @return  array The list of changes
     */
    function getListing($iss_id, $order_by = 'DESC')
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_history,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "history_type
                 WHERE
                    htt_id = his_htt_id AND
                    his_is_hidden != 1 AND
                    his_iss_id=" . Misc::escapeInteger($iss_id) . " AND
                    htt_role <= " . Auth::getCurrentRole() . "
                 ORDER BY
                    his_id $order_by";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]["his_created_date"] = Date_API::getFormattedDate($res[$i]["his_created_date"]);
                $res[$i]["his_summary"] = Mime_Helper::fixEncoding($res[$i]["his_summary"]);
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


    /**
     * Returns the id for the history type based on name.
     *
     * @access  public
     * @param   string The name of the history type
     * @return  integer The id of this type.
     */
    function getTypeID($name)
    {
        static $returns;


        $serialized = serialize($name);
        if (!empty($returns[$serialized])) {
            return $returns[$serialized];
        }

        if (!is_array($name)) {
            $name = array($name);
        }

        $stmt = "SELECT
                    htt_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "history_type
                 WHERE
                    htt_name IN('" . join("','", $name) . "')";
        $res = $GLOBALS["db_api"]->dbh->getCol($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "unknown";
        } else {
            if (count($name) == 1) {
                $res = current($res);
            }
            $returns[$serialized] = $res;
            return $res;
        }
    }


    /**
     * Returns a list of issues touched by the specified user in the specified time frame.
     *
     * @access  public
     * @param   integer $usr_id The id of the user.
     * @param   date $start The start date
     * @param   date $end The end date
     * @param   date $separate_closed If closed issues should be included in a separate array
     * @return  array An array of issues touched by the user.
     */
    function getTouchedIssuesByUser($usr_id, $start, $end, $separate_closed = false)
    {
        $stmt = "SELECT
                    iss_id,
                    iss_prj_id,
                    iss_summary,
                    iss_customer_id,
                    sta_is_closed
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_history,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                    LEFT JOIN
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                    ON
                        iss_sta_id = sta_id
                 WHERE
                    his_iss_id = iss_id AND
                    his_usr_id = " . Misc::escapeInteger($usr_id) . " AND
                    his_created_date BETWEEN '" . Misc::escapeString($start) . "' AND '" . Misc::escapeString($end) . "' AND
                    his_htt_id NOT IN(" . join(',', History::getTypeID(array(
                        'notification_removed',
                        'notification_added',
                        'notification_updated',
                        'remote_replier_added',
                        'replier_added',
                        'replier_removed',
                        'replier_other_added'))) . ")
                 GROUP BY
                    iss_id";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            $data = array(
                "closed"    =>  array(),
                "other"     =>  array()
            );
            if (count($res) > 0) {
                foreach ($res as $index => $row) {
                    if ((!empty($row["iss_customer_id"])) && (Customer::hasCustomerIntegration($row['iss_prj_id']))) {
                        $details = Customer::getDetails($row["iss_prj_id"], $row["iss_customer_id"]);
                        $row["customer_name"] = $details["customer_name"];
                    }
                    if (($separate_closed) && ($row['sta_is_closed'] == 1)) {
                        $data['closed'][] = $row;
                    } else {
                        $data['other'][] = $row;
                    }
                }
                $sort_function = create_function('$a,$b', 'return strcasecmp(@$a["customer_name"], @$b["customer_name"]);');
                @usort($data['closed'], $sort_function);
                @usort($data['other'], $sort_function);
            }
        }
        return $data;
    }


    /**
     * Returns the number of issues for the specified user that are currently set to the specified status(es).
     *
     * @access  public
     * @param   integer $usr_id The id of the user.
     * @param   date $start The start date
     * @param   date $end The end date
     * @param   array $statuses An array of status abreviations to return counts for.
     * @return  array An array containing the number of issues for the user set tothe specified statuses.
     */
    function getTouchedIssueCountByStatus($usr_id, $start, $end, $statuses = false)
    {
        $stmt = "SELECT
                    sta_title,
                    count(DISTINCT iss_id) as total
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_history
                 WHERE
                    his_iss_id = iss_id AND
                    iss_sta_id = sta_id AND
                    his_usr_id = " . Misc::escapeInteger($usr_id) . " AND
                    his_created_date BETWEEN '" . Misc::escapeString($start) . "' AND '" . Misc::escapeString($end) . "'";
        if ($statuses != false) {
            $stmt .= " AND
                    (
                        sta_abbreviation IN('" . join("','", $statuses) . "') OR
                        sta_is_closed = 1
                    )";
        }
        $stmt .= "
                 GROUP BY
                    sta_title
                 ORDER BY
                    sta_rank";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            return $res;
        }
    }


    /**
     * Returns the history for a specified user in a specified time frame for an optional type
     *
     * @access  public
     * @param   integer $usr_id The id of the user.
     * @param   date $start The start date
     * @param   date $end The end date
     * @param   array $htt_id The htt_id or id's to to return history for.
     * @return  array An array of history items
     */
    function getHistoryByUser($usr_id, $start, $end, $htt_id = false)
    {
        $stmt = "SELECT
                    his_id,
                    his_iss_id,
                    his_created_date,
                    his_summary,
                    his_htt_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_history
                 WHERE
                    his_usr_id = " . Misc::escapeInteger($usr_id) . " AND
                    his_created_date BETWEEN '" . date("Y/m/d", $start) . "' AND '" . date("Y/m/d", $end) . "'";
        if ($htt_id != false) {
            $stmt .= "
                    AND his_htt_id IN(" . join(",", Misc::escapeInteger($htt_id)) . ")";
        }
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        }
        return $res;
    }


    /**
     * Returns the last person to close the issue
     *
     * @param   integer $issue_id The ID of the issue
     * @return  integer usr_id
     */
    function getIssueCloser($issue_id)
    {
        $sql = "SELECT
                    his_usr_id
                FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_history
                WHERE
                    his_iss_id = " . Misc::escapeInteger($issue_id) . " AND
                    his_htt_id = '" . History::getTypeID('issue_closed') . "'
                ORDER BY
                    his_created_date DESC
                LIMIT 1";
        $res = $GLOBALS["db_api"]->dbh->getOne($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return 0;
        }
        return $res;
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included History Class');
}
?>