<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2015 Eventum Team.                              |
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
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+


/**
 * Class to handle the business logic related to the history information for
 * the issues entered in the system.
 */

class History
{
    /**
     * Method used to format the changes done to an issue.
     *
     * @param   string $old_value The old value for a specific issue parameter
     * @param   string $new_value The new value for a specific issue parameter
     * @return  string The formatted string
     */
    public static function formatChanges($old_value, $new_value)
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
     * @param integer $iss_id The issue ID
     * @param integer $usr_id The ID of the user.
     * @param integer|string $htt_id The type ID of this history event.
     * @param string $summary The summary of the changes
     * @param array $context parameters used in summary
     */
    public static function add($iss_id, $usr_id, $htt_id, $summary, $context = array())
    {
        if (!is_numeric($htt_id)) {
            $htt_id = History::getTypeID($htt_id);
        }

        $params = array(
            'his_iss_id' => $iss_id,
            'his_usr_id' => $usr_id,
            'his_created_date' => Date_Helper::getCurrentDateGMT(),
            'his_summary' => $summary,
            'his_context' => json_encode($context),
            'his_htt_id' => $htt_id,
        );

        $stmt = 'INSERT INTO {{%issue_history}} SET '. DB_Helper::buildSet($params);

        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DbException $e) {
        }
    }

    /**
     * Method used to get the list of changes made against a specific issue.
     *
     * @param   integer $iss_id The issue ID
     * @param   string $order_by The order to sort the history
     * @return  array The list of changes
     */
    public static function getListing($iss_id, $order_by = 'DESC')
    {
        $order_by = DB_Helper::orderBy($order_by);
        $stmt = "SELECT
                    *
                 FROM
                    {{%issue_history}},
                    {{%history_type}}
                 WHERE
                    htt_id = his_htt_id AND
                    his_is_hidden != 1 AND
                    his_iss_id=? AND
                    htt_role <= ?
                 ORDER BY
                    his_id $order_by";
        $params = array($iss_id, Auth::getCurrentRole());
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, $params);
        } catch (DbException $e) {
            return '';
        }

        foreach ($res as &$row) {
            $row['his_created_date'] = Date_Helper::getFormattedDate($row['his_created_date']);
            $row['his_summary'] = Misc::processTokens(ev_gettext($row['his_summary']), $row['his_context']);
        }

        return $res;
    }

    /**
     * Method used to remove all history entries associated with a
     * given set of issues.
     *
     * @param   array $ids The array of issue IDs
     * @return  boolean
     */
    public static function removeByIssues($ids)
    {
        $items = implode(', ', $ids);
        $stmt = "DELETE FROM
                    {{%issue_history}}
                 WHERE
                    his_iss_id IN ($items)";
        try {
            DB_Helper::getInstance()->query($stmt);
        } catch (DbException $e) {
            return false;
        }

        return true;
    }

    /**
     * Returns the id for the history type based on name.
     *
     * @param   string $name The name of the history type
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
                    {{%history_type}}
                 WHERE
                    htt_name IN('" . implode("','", $name) . "')";
        try {
            $res = DB_Helper::getInstance()->getColumn($stmt);
        } catch (DbException $e) {
            return 'unknown';
        }

        if (count($name) == 1) {
            $res = current($res);
        }
        $returns[$serialized] = $res;

        return $res;
    }

    /**
     * Returns a list of issues touched by the specified user in the specified time frame in specified project.
     *
     * @param integer $usr_id The id of the user
     * @param int $prj_id The project id
     * @param string $start The start date
     * @param string $end The end date
     * @param array $htt_exclude Additional History Types to ignore
     * @return array An array of issues touched by the user.
     */
    public static function getTouchedIssuesByUser($usr_id, $prj_id, $start, $end, $htt_exclude = array())
    {
        $htt_exclude_list = self::getTypeID(
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

        $stmt = 'SELECT
                    iss_id,
                    iss_prj_id,
                    iss_summary,
                    iss_customer_id,
                    iss_customer_contract_id,
                    sta_title,
                    pri_title,
                    sta_is_closed
                 FROM
                    {{%issue_history}},
                    {{%issue}}
                    LEFT JOIN
                        {{%status}}
                    ON
                        iss_sta_id = sta_id
                 LEFT JOIN
                    {{%project_priority}}
                 ON
                    iss_pri_id = pri_id
                 WHERE
                    his_iss_id = iss_id AND
                    his_usr_id = ? AND
                    his_created_date BETWEEN ? AND ? AND
                    his_htt_id NOT IN(' . implode(',', $htt_exclude_list) . ') AND
                    iss_prj_id = ?
                 GROUP BY
                    iss_id
                 ORDER BY
                    iss_id ASC';
        $params = array($usr_id, $start, $end, $prj_id);
        try {
            return DB_Helper::getInstance()->getAll($stmt, $params);
        } catch (DbException $e) {
            return null;
        }
    }

    /**
     * Returns the number of issues for the specified user that are currently set to the specified status(es).
     *
     * @param integer $usr_id The id of the user.
     * @param int $prj_id The project id
     * @param string $start The start date
     * @param string $end The end date
     * @param array $statuses An array of status abbreviations to return counts for.
     * @return array An array containing the number of issues for the user set to the specified statuses.
     */
    public static function getTouchedIssueCountByStatus($usr_id, $prj_id, $start, $end, $statuses = null)
    {
        $stmt = 'SELECT
                    sta_title,
                    count(DISTINCT iss_id) as total
                 FROM
                    {{%issue}},
                    {{%status}},
                    {{%issue_history}}
                 WHERE
                    his_iss_id = iss_id AND
                    iss_sta_id = sta_id AND
                    iss_prj_id = ? AND
                    his_usr_id = ? AND
                    his_created_date BETWEEN ? AND ?';
        if ($statuses) {
            $stmt .= " AND
                    (
                        sta_abbreviation IN('" . implode("','", $statuses) . "') OR
                        sta_is_closed = 1
                    )";
        }
        $stmt .= '
                 GROUP BY
                    sta_title
                 ORDER BY
                    sta_rank';
        $params = array($prj_id, $usr_id, $start, $end);
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, $params);
        } catch (DbException $e) {
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
    public static function getIssueCloser($issue_id)
    {
        $sql = 'SELECT
                    his_usr_id
                FROM
                    {{%issue_history}}
                WHERE
                    his_iss_id = ? AND
                    his_htt_id = ?
                ORDER BY
                    his_created_date DESC
                LIMIT 1';
        try {
            $res = DB_Helper::getInstance()->getOne($sql, array($issue_id, self::getTypeID('issue_closed')));
        } catch (DbException $e) {
            return 0;
        }

        return $res;
    }
}
