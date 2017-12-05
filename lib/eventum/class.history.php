<?php

/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

use Eventum\Db\DatabaseException;
use Eventum\Event;
use Eventum\EventDispatcher\EventManager;

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
        }

        return $old_value . ' -> ' . $new_value;
    }

    /**
     * Method used to log the changes made against a specific issue.
     *
     * @param int $iss_id The issue ID
     * @param int $usr_id the ID of the user
     * @param int|string $htt_id the type ID of this history event
     * @param string $summary The summary of the changes
     * @param array $context parameters used in summary
     * @param null $min_role The minimum role that can view this entry. If null will default to role from $htt_id
     */
    public static function add($iss_id, $usr_id, $htt_id, $summary, $context = [], $min_role = null)
    {
        if (!is_numeric($htt_id)) {
            $htt_id = self::getTypeID($htt_id);
        }

        if ($min_role === null) {
            $min_role = self::getTypeRole($htt_id);
        }

        $params = [
            'his_iss_id' => $iss_id,
            'his_usr_id' => $usr_id,
            'his_created_date' => Date_Helper::getCurrentDateGMT(),
            'his_summary' => $summary,
            'his_context' => json_encode($context),
            'his_htt_id' => $htt_id,
            'his_min_role' => $min_role,
        ];

        $stmt = 'INSERT INTO `issue_history` SET ' . DB_Helper::buildSet($params);

        DB_Helper::getInstance()->query($stmt, $params);

        $params['his_id'] = DB_Helper::get_last_insert_id();
        $params['prj_id'] = Auth::getCurrentProject();

        $event = new Event\UnstructuredEvent(null, $params);
        EventManager::dispatch(Event\SystemEvents::HISTORY_ADD, $event);
    }

    /**
     * Method used to get the list of changes made against a specific issue.
     *
     * @param   int $iss_id The issue ID
     * @param   string $order_by The order to sort the history
     * @return  array The list of changes
     */
    public static function getListing($iss_id, $order_by = 'DESC')
    {
        $order_by = DB_Helper::orderBy($order_by);
        $stmt = "SELECT
                    *
                 FROM
                    `issue_history`,
                    `history_type`
                 WHERE
                    htt_id = his_htt_id AND
                    his_is_hidden != 1 AND
                    his_iss_id=? AND
                    his_min_role <= ?
                 ORDER BY
                    his_id $order_by";
        $params = [$iss_id, Auth::getCurrentRole()];
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, $params);
        } catch (DatabaseException $e) {
            return '';
        }

        foreach ($res as &$row) {
            $row['his_summary'] = Misc::processTokens(ev_gettext($row['his_summary']), $row['his_context']);
        }

        return $res;
    }

    /**
     * Returns the id for the history type based on name.
     *
     * @param   string $name The name of the history type
     * @return  int the id of this type
     */
    public static function getTypeID($name)
    {
        static $returns;

        $serialized = serialize($name);
        if (!empty($returns[$serialized])) {
            return $returns[$serialized];
        }

        if (!is_array($name)) {
            $name = [$name];
        }

        $stmt = "SELECT
                    htt_id
                 FROM
                    `history_type`
                 WHERE
                    htt_name IN('" . implode("','", $name) . "')";
        try {
            $res = DB_Helper::getInstance()->getColumn($stmt);
        } catch (DatabaseException $e) {
            return 'unknown';
        }

        if (count($name) == 1) {
            $res = current($res);
        }
        $returns[$serialized] = $res;

        return $res;
    }

    /**
     * Returns the role for the history type based on id.
     *
     * @param   int $id The id of the history type
     * @return  int the role of this type
     */
    public static function getTypeRole($id)
    {
        static $returns;

        if (!empty($returns[$id])) {
            return $returns[$id];
        }

        $sql = 'SELECT
                    htt_role
                FROM
                    `history_type`
                WHERE
                    htt_id = ?';
        try {
            $res = DB_Helper::getInstance()->getOne($sql, [$id]);
        } catch (DatabaseException $e) {
            return null;
        }
        $returns[$id] = $res;

        return $res;
    }

    /**
     * Returns a list of issues touched by the specified user in the specified time frame in specified project.
     *
     * @param int $usr_id The id of the user
     * @param int $prj_id The project id
     * @param string $start The start date
     * @param string $end The end date
     * @param array $htt_exclude Additional History Types to ignore
     * @return array an array of issues touched by the user
     */
    public static function getTouchedIssuesByUser($usr_id, $prj_id, $start, $end, $htt_exclude = [])
    {
        $htt_exclude_list = self::getTypeID(
            array_merge([
                'notification_removed',
                'notification_added',
                'notification_updated',
                'remote_replier_added',
                'replier_added',
                'replier_removed',
                'replier_other_added',
            ], $htt_exclude)
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
                    `issue_history`,
                    `issue`
                    LEFT JOIN
                        `status`
                    ON
                        iss_sta_id = sta_id
                 LEFT JOIN
                    `project_priority`
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
        $params = [$usr_id, $start, $end, $prj_id];
        try {
            return DB_Helper::getInstance()->getAll($stmt, $params);
        } catch (DatabaseException $e) {
            return null;
        }
    }

    /**
     * Returns the number of issues for the specified user that are currently set to the specified status(es).
     *
     * @param int $usr_id the id of the user
     * @param int $prj_id The project id
     * @param string $start The start date
     * @param string $end The end date
     * @param array $statuses an array of status abbreviations to return counts for
     * @return array an array containing the number of issues for the user set to the specified statuses
     */
    public static function getTouchedIssueCountByStatus($usr_id, $prj_id, $start, $end, $statuses = null)
    {
        $stmt = 'SELECT
                    sta_title,
                    count(DISTINCT iss_id) as total
                 FROM
                    `issue`,
                    `status`,
                    `issue_history`
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
        $params = [$prj_id, $usr_id, $start, $end];
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, $params);
        } catch (DatabaseException $e) {
            return [];
        }

        return $res;
    }

    /**
     * Returns the last person to close the issue
     *
     * @param   int $issue_id The ID of the issue
     * @return  int usr_id
     */
    public static function getIssueCloser($issue_id)
    {
        $sql = 'SELECT
                    his_usr_id
                FROM
                    `issue_history`
                WHERE
                    his_iss_id = ? AND
                    his_htt_id = ?
                ORDER BY
                    his_created_date DESC
                LIMIT 1';
        try {
            $res = DB_Helper::getInstance()->getOne($sql, [$issue_id, self::getTypeID('issue_closed')]);
        } catch (DatabaseException $e) {
            return 0;
        }

        return $res;
    }
}
