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
     * @param   integer $iss_id The issue ID
     * @param   integer $usr_id The ID of the user.
     * @param   integer $htt_id The type ID of this history event.
     * @param   string $summary The summary of the changes
     * @param   boolean $hide If this history item should be hidden.
     * @return  void
     */
    public static function add($iss_id, $usr_id, $htt_id, $summary, $hide = false)
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
                    '" . Date_Helper::getCurrentDateGMT() . "',
                    '" . Misc::escapeString($summary) . "',
                    $htt_id";
        if ($hide == true) {
            $stmt .= ", 1";
        }
        $stmt .= ")";
        $res = DB_Helper::getInstance()->query($stmt);
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
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]["his_created_date"] = Date_Helper::getFormattedDate($res[$i]["his_created_date"]);
                $res[$i]["his_summary"] = Link_Filter::processText(Auth::getCurrentProject(), Mime_Helper::fixEncoding($res[$i]["his_summary"]));
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
        $res = DB_Helper::getInstance()->query($stmt);
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
     * @param   string The name of the history type
     * @return  integer The id of this type.
     */
    public static function getTypeID($name)
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
        $res = DB_Helper::getInstance()->getCol($stmt);
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
     * @param   array $htt_exclude Addtional History Types to ignore
     * @return  array An array of issues touched by the user.
     */
    function getTouchedIssuesByUser($usr_id, $start, $end, $separate_closed = false, $htt_exclude = array())
    {

        $htt_list = self::getTypeID(
            array_merge(array(
                'notification_removed',
                'notification_added',
                'notification_updated',
                'remote_replier_added',
                'replier_added',
                'replier_removed',
                'replier_other_added',
            ), $htt_exclude)
        );

        $stmt = "SELECT
                    iss_id,
                    iss_prj_id,
                    iss_summary,
                    iss_customer_id,
                    iss_customer_contract_id,
                    sta_title,
                    pri_title,
                    sta_is_closed
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_history,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                    LEFT JOIN
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                    ON
                        iss_sta_id = sta_id
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_priority
                 ON
                    iss_pri_id = pri_id
                 WHERE
                    his_iss_id = iss_id AND
                    his_usr_id = " . Misc::escapeInteger($usr_id) . " AND
                    his_created_date BETWEEN '" . Misc::escapeString($start) . "' AND '" . Misc::escapeString($end) . "' AND
                    his_htt_id NOT IN(" . join(',', $htt_list) . ") AND
                    iss_prj_id = " . Auth::getCurrentProject() . "
                 GROUP BY
                    iss_id
                 ORDER BY
                    iss_id ASC";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            $data = array(
                "closed"    =>  array(),
                "other"     =>  array()
            );
            if (count($res) > 0) {
                if (isset($_REQUEST['show_per_issue'])) {
                    Time_Tracking::fillTimeSpentByIssueAndTime($res, $usr_id, $start, $end);
                }
                if (isset($_REQUEST['separate_status_changed'])) {
                    self::fillStatusChangedOnlyIssues($res, $usr_id, $start, $end);
                }
                foreach ($res as $index => $row) {
                    if ((!empty($row["iss_customer_id"])) && (Customer::hasCustomerIntegration($row['iss_prj_id']))) {
                        $details = Customer::getDetails($row["iss_prj_id"], $row["iss_customer_id"], $res['iss_customer_contract_id']);
                        $row["customer_name"] = $details["customer_name"];
                    }
                    if (($separate_closed) && ($row['sta_is_closed'] == 1)) {
                        $data['closed'][] = $row;
                    } elseif ((isset($_REQUEST['separate_status_changed'])) && $row['only_stat_changed']) {
                        $data['status_changed'][] = $row;
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
                    iss_prj_id = " . Auth::getCurrentProject() . " AND
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
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
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
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
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
                    his_htt_id = '" . self::getTypeID('issue_closed') . "'
                ORDER BY
                    his_created_date DESC
                LIMIT 1";
        $res = DB_Helper::getInstance()->getOne($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return 0;
        }
        return $res;
    }

    /**
     * Fills a result set with a flag indicating if this issue only had it's status
     * changed in the given time period.
     *
     * @param   array $res User issues
     * @param   integer $usr_id The ID of the user this report is for.
     * @param   integer $start The timestamp of the beginning of the report.
     * @param   integer $end The timestamp of the end of this report.
     * @return  boolean True if only status changed else false
     */
    public static function fillStatusChangedOnlyIssues(&$res, $usr_id, $start, $end) {

        $issue_ids = array();
        for ($i = 0; $i < count($res); $i++) {
            $issue_ids[] = Misc::escapeInteger($res[$i]["iss_id"]);
        }
        $ids = implode(", ", $issue_ids);

        $sql = "SELECT
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_history.his_iss_id,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_history.his_htt_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_history
                 WHERE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_history.his_htt_id != " . self::getTypeID('status_changed') . " AND
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_history.his_htt_id != " . self::getTypeID('issue_updated') . " AND
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_history.his_iss_id IN (" . $ids . ") AND
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_history.his_usr_id = " . Misc::escapeInteger($usr_id) . " AND
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_history.his_created_date BETWEEN '" . Misc::escapeString($start) . "' AND '" . Misc::escapeString($end) . "'
                GROUP BY his_iss_id";

        $result = DB_Helper::getInstance()->getAssoc($sql);
        if (PEAR::isError($result)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
        } else {
            foreach($res as $key => $item) {
                @$res[$key]['only_stat_changed'] = (array_key_exists($item['iss_id'], $result) ? false : true);
            }
        }
    }
}
