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

use Eventum\Db\Adapter\AdapterInterface;
use Eventum\Db\DatabaseException;

/**
 * Class to handle the business logic related to the administration
 * of time tracking categories in the system.
 */
class Time_Tracking
{
    /**
     * These categories are required by Eventum and cannot be deleted.
     * @var array
     */
    private static $default_categories = ['Note Discussion', 'Email Discussion', 'Telephone Discussion'];

    /**
     * Method used to get the ID of a given category.
     *
     * @param   int $prj_id The project ID
     * @param   string $ttc_title The time tracking category title
     * @return  int The time tracking category ID
     */
    public static function getCategoryId($prj_id, $ttc_title)
    {
        $stmt = 'SELECT
                    ttc_id
                 FROM
                    `time_tracking_category`
                 WHERE
                    ttc_prj_id=? AND
                    ttc_title=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$prj_id, $ttc_title]);
        } catch (DatabaseException $e) {
            return 0;
        }

        return $res;
    }

    /**
     * Method used to get the details of a time tracking category.
     *
     * @param   int $ttc_id The time tracking category ID
     * @return  array The details of the category
     */
    public static function getCategoryDetails($ttc_id)
    {
        $stmt = 'SELECT
                    *
                 FROM
                    `time_tracking_category`
                 WHERE
                    ttc_id=?';
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, [$ttc_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Get statistic of time categories usage
     *
     * @return  array $ttc_id => issue_count
     */
    private static function getCategoryStats($ttc_ids)
    {
        $stmt = 'SELECT
                    ttr_ttc_id,
                    COUNT(ttr_ttc_id)
                 FROM
                    `time_tracking`';
        if (count($ttc_ids) > 0) {
            $stmt .= ' WHERE ttr_ttc_id IN (' . DB_Helper::buildList($ttc_ids) . ')';
        }
        $stmt .= ' GROUP BY 1';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, $ttc_ids);
        } catch (DatabaseException $e) {
            return null;
        }

        return $res;
    }

    /**
     * Method used to remove a specific set of time tracking categories
     *
     * @param array $items
     * @return int, 1 on success, -1 on error, -2 if can't remove because time category is being used
     */
    public static function removeCategory($items)
    {
        // check that none of the categories are in use
        $usage = self::getCategoryStats($items);
        foreach ($usage as $count) {
            if ($count > 0) {
                return -2;
            }
        }

        $stmt = 'DELETE FROM
                    `time_tracking_category`
                 WHERE
                    ttc_id IN (' . DB_Helper::buildList($items) . ')';
        try {
            DB_Helper::getInstance()->query($stmt, $items);
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Method used to update a specific time tracking category
     *
     * @param int $prj_id project id
     * @param int $ttc_id time tracking category id
     * @param string $title
     * @return int 1 if the update worked, -1 otherwise
     */
    public static function updateCategory($prj_id, $ttc_id, $title)
    {
        if (Validation::isWhitespace($title)) {
            return -2;
        }
        $stmt = 'UPDATE
                    `time_tracking_category`
                 SET
                    ttc_title=?
                 WHERE
                    ttc_prj_id=? AND
                    ttc_id=?';
        try {
            DB_Helper::getInstance()->query($stmt, [$title, $prj_id, $ttc_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Method used to add a new time tracking category
     *
     * @param   int $prj_id The project ID
     * @param   string $title The title of the time tracking category
     * @return  int 1 if the update worked, -1 otherwise
     */
    public static function insertCategory($prj_id, $title)
    {
        if (Validation::isWhitespace($title)) {
            return -2;
        }

        $stmt = 'INSERT INTO
                    `time_tracking_category`
                 (
                    ttc_prj_id,
                    ttc_title,
                    ttc_created_date
                 ) VALUES (
                    ?, ?, ?
                 )';
        try {
            DB_Helper::getInstance()->query($stmt, [$prj_id, $title, Date_Helper::getCurrentDateGMT()]);
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Method used to add a default timetracking categories for project.
     *
     * @param   int $prj_id The project ID
     * @return  int 1 if the inserts worked, -1 otherwise
     */
    public static function addProjectDefaults($prj_id)
    {
        $res = 1;
        foreach (self::$default_categories as $title) {
            $res = min($res, self::insertCategory($prj_id, $title));
        }

        return $res;
    }

    /**
     * Method used to get the full list of time tracking categories associated
     * with a specific project.
     *
     * @param   int $prj_id The project ID
     * @return  array The list of categories
     */
    public static function getCategoryList($prj_id)
    {
        $stmt = 'SELECT
                    ttc_id,
                    ttc_title
                 FROM
                    `time_tracking_category`
                 WHERE
                    ttc_title NOT IN (' . DB_Helper::buildList(self::$default_categories) . ') AND
                    ttc_prj_id=?
                 ORDER BY
                    ttc_title ASC';
        $params = self::$default_categories;
        $params[] = $prj_id;
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, $params);
        } catch (DatabaseException $e) {
            return null;
        }

        $ttc_usage = self::getCategoryStats(array_column($res, 'ttc_id'));
        foreach ($res as &$row) {
            $ttc_id = $row['ttc_id'];
            if (isset($ttc_usage[$ttc_id])) {
                $row['ttc_count'] = $ttc_usage[$ttc_id];
            }
        }

        return $res;
    }

    /**
     * Method used to get the full list of time tracking categories as an
     * associative array in the style of (id => title)
     *
     * @param int $prj_id
     * @return  array The list of categories
     */
    public static function getAssocCategories($prj_id)
    {
        $stmt = 'SELECT
                    ttc_id,
                    ttc_title
                 FROM
                    `time_tracking_category`
                 WHERE
                    ttc_prj_id=?
                 ORDER BY
                    ttc_title ASC';

        return DB_Helper::getInstance()->getPair($stmt, [$prj_id]);
    }

    /**
     * Method used to get the time spent on a given list of issues.
     *
     * @param   array $result The result set
     */
    public static function fillTimeSpentByIssues(&$result)
    {
        $ids = [];
        foreach ($result as $res) {
            $ids[] = $res['iss_id'];
        }

        if (!$ids) {
            return;
        }

        $stmt = 'SELECT
                    ttr_iss_id,
                    SUM(ttr_time_spent)
                 FROM
                    `time_tracking`
                 WHERE
                    ttr_iss_id IN (' . DB_Helper::buildList($ids) . ')
                 GROUP BY
                    ttr_iss_id';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, $ids);
        } catch (DatabaseException $e) {
            return;
        }

        foreach ($result as $i => &$row) {
            $iss_id = $row['iss_id'];
            $row['time_spent'] = isset($res[$iss_id]) ? $res[$iss_id] : 0;
        }
    }

    /**
     * Method used to get the full listing of time entries in the system for a
     * specific issue
     *
     * @param   int $issue_id The issue ID
     * @return  array The full list of time entries
     */
    public static function getTimeEntryListing($issue_id)
    {
        $stmt = 'SELECT
                    ttr_id,
                    ttr_created_date,
                    ttr_summary,
                    ttr_time_spent,
                    ttc_title,
                    ttr_usr_id,
                    usr_full_name
                 FROM
                    `time_tracking`,
                    `time_tracking_category`,
                    `user`
                 WHERE
                    ttr_ttc_id=ttc_id AND
                    ttr_usr_id=usr_id AND
                    ttr_iss_id=?
                 ORDER BY
                    ttr_created_date ASC';

        $res = DB_Helper::getInstance()->getAll($stmt, [$issue_id]);

        $total_time_spent = 0;
        $total_time_by_user = [];

        foreach ($res as &$row) {
            $row['ttr_summary'] = Link_Filter::processText(Issue::getProjectID($issue_id), nl2br(htmlspecialchars($row['ttr_summary'])));
            $row['formatted_time'] = Misc::getFormattedTime($row['ttr_time_spent']);

            if (isset($total_time_by_user[$row['ttr_usr_id']])) {
                $total_time_by_user[$row['ttr_usr_id']]['time_spent'] += $row['ttr_time_spent'];
            } else {
                $total_time_by_user[$row['ttr_usr_id']] = [
                    'usr_full_name' => $row['usr_full_name'],
                    'time_spent' => $row['ttr_time_spent'],
                ];
            }
            $total_time_spent += $row['ttr_time_spent'];
        }

        usort($total_time_by_user,
            function ($a, $b) {
                return $a['time_spent'] < $b['time_spent'];
            }
        );

        foreach ($total_time_by_user as &$item) {
            $item['time_spent'] = Misc::getFormattedTime($item['time_spent']);
        }

        return [
            'total_time_spent' => Misc::getFormattedTime($total_time_spent),
            'total_time_by_user' => $total_time_by_user,
            'list' => $res,
        ];
    }

    /**
     * Method used to get the details of a specific entry
     *
     * @param   int $ttr_id The time tracking ID
     * @return  array The time tracking details
     */
    public static function getTimeEntryDetails($ttr_id)
    {
        $stmt = 'SELECT
                    ttr_id,
                    ttr_iss_id,
                    ttr_created_date,
                    ttr_summary,
                    ttr_time_spent,
                    ttc_id,
                    ttc_title,
                    ttr_usr_id,
                    usr_full_name
                 FROM
                    `time_tracking`,
                    `time_tracking_category`,
                    `user`
                 WHERE
                    ttr_ttc_id=ttc_id AND
                    ttr_usr_id=usr_id AND
                    ttr_id=?';

        return DB_Helper::getInstance()->getRow($stmt, [$ttr_id]);
    }

    /**
     * Method used to remove a specific time entry from the system.
     *
     * @param   int $time_id The time entry ID
     * @param   int $usr_id The user ID of the person trying to remove this entry
     * @return  int 1 if the update worked, -1 otherwise
     */
    public static function removeTimeEntry($time_id, $usr_id)
    {
        $stmt = 'SELECT
                    ttr_iss_id issue_id,
                    ttr_usr_id owner_usr_id
                 FROM
                    `time_tracking`
                 WHERE
                    ttr_id=?';

        $details = DB_Helper::getInstance()->getRow($stmt, [$time_id]);
        // check if the owner is the one trying to remove this entry
        if (($details['owner_usr_id'] != $usr_id) || (!Issue::canAccess($details['issue_id'], $usr_id))) {
            return -1;
        }

        $stmt = 'DELETE FROM
                    `time_tracking`
                 WHERE
                    ttr_id=?';
        try {
            DB_Helper::getInstance()->query($stmt, [$time_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        Issue::markAsUpdated($details['issue_id']);
        History::add($details['issue_id'], $usr_id, 'time_removed', 'Time tracking entry removed by {user}', [
            'user' => User::getFullName($usr_id),
        ]);

        return 1;
    }

    /**
     * Method used to add a new time entry in the system.
     *
     * @param int $iss_id issue id the time entry is associated with
     * @param int $ttc_id time tracking category id
     * @param int $time_spent time spent in minutes
     * @param array $date date structure
     * @param string $summary summary about time tracking entry
     * @return int 1 if the update worked, -1 otherwise
     */
    public static function addTimeEntry($iss_id, $ttc_id, $time_spent, $date, $summary)
    {
        if ($date) {
            // format the date from the form
            $created_date = sprintf('%04d-%02d-%02d %02d:%02d:%02d',
                $date['Year'], $date['Month'],
                $date['Day'], $date['Hour'],
                $date['Minute'], 0);
            // convert the date to GMT timezone
            $created_date = Date_Helper::convertDateGMT($created_date . ' ' . Date_Helper::getPreferredTimezone());
        } else {
            $created_date = Date_Helper::getCurrentDateGMT();
        }

        $usr_id = Auth::getUserID();
        $stmt = 'INSERT INTO
                    `time_tracking`
                 (
                    ttr_ttc_id,
                    ttr_iss_id,
                    ttr_usr_id,
                    ttr_created_date,
                    ttr_time_spent,
                    ttr_summary
                 ) VALUES (
                    ?, ?, ?, ?, ?, ?
                 )';
        $params = [
            $ttc_id,
            $iss_id,
            $usr_id,
            $created_date,
            $time_spent,
            $summary,
        ];
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return -1;
        }

        Issue::markAsUpdated($iss_id, 'time added');
        History::add($iss_id, $usr_id, 'time_added', 'Time tracking entry submitted by {user}', [
            'user' => User::getFullName($usr_id),
        ]);

        return 1;
    }

    /**
     * Method used to update an existing time entry in the system.
     *
     * @param int $ttr_id The id the time entry is associated with
     * @param int $ttc_id time tracking category id
     * @param int $time_spent time spent in minutes
     * @param array $date date structure
     * @param string $summary summary about time tracking entry
     * @return int 1 if the update worked, -1 otherwise
     */
    public static function updateTimeEntry($ttr_id, $ttc_id, $time_spent, $date, $summary)
    {
        if ($date) {
            // format the date from the form
            $created_date = sprintf('%04d-%02d-%02d %02d:%02d:%02d',
                $date['Year'], $date['Month'],
                $date['Day'], $date['Hour'],
                $date['Minute'], 0);
            // convert the date to GMT timezone
            $created_date = Date_Helper::convertDateGMT($created_date . ' ' . Date_Helper::getPreferredTimezone());
        } else {
            $created_date = Date_Helper::getCurrentDateGMT();
        }

        $usr_id = Auth::getUserID();
        $stmt = 'UPDATE
                    `time_tracking`
                 SET
                    ttr_ttc_id = ?,
                    ttr_created_date = ?,
                    ttr_time_spent = ?,
                    ttr_summary = ?
                  WHERE
                    ttr_id = ?';
        $params = [
            $ttc_id,
            $created_date,
            $time_spent,
            $summary,
            $ttr_id,
        ];
        DB_Helper::getInstance()->query($stmt, $params);

        $details = self::getTimeEntryDetails($ttr_id);

        History::add($details['ttr_iss_id'], $usr_id, 'time_update', "Time tracking entry '{summary}' updated by {user}", [
            'user' => User::getFullName($usr_id),
            'summary' => $summary,
            'ttr_id' => $ttr_id,
        ]);

        return 1;
    }

    /**
     * Method used to remotely record a time tracking entry.
     *
     * @param   int $issue_id The issue ID
     * @param   int $usr_id The user ID
     * @param   int $cat_id The time tracking category ID
     * @param   string $summary The summary of the work entry
     * @param   int $time_spent The time spent in minutes
     * @return  int 1 if the insert worked, -1 otherwise
     */
    public static function recordRemoteTimeEntry($issue_id, $usr_id, $cat_id, $summary, $time_spent)
    {
        $stmt = 'INSERT INTO
                    `time_tracking`
                 (
                    ttr_ttc_id,
                    ttr_iss_id,
                    ttr_usr_id,
                    ttr_created_date,
                    ttr_time_spent,
                    ttr_summary
                 ) VALUES (
                    ?, ?, ?, ?, ?, ?
                 )';
        $params = [
            $cat_id,
            $issue_id,
            $usr_id,
            Date_Helper::getCurrentDateGMT(),
            $time_spent,
            $summary,
        ];
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return -1;
        }

        Issue::markAsUpdated($issue_id);
        History::add($issue_id, $usr_id, 'remote_time_added', 'Time tracking entry submitted remotely by {user}', [
            'user' => User::getFullName($usr_id),
        ]);

        return 1;
    }

    /**
     * Returns summary information about all time spent by a user in a specified time frame.
     *
     * @param int $usr_id the ID of the user this report is for
     * @param int $prj_id The project id
     * @param string $start the datetime of the beginning of the report
     * @param string $end the datetime of the end of this report
     * @return array An array of data containing information about time tracking
     */
    public static function getSummaryByUser($usr_id, $prj_id, $start, $end)
    {
        $stmt = 'SELECT
                    ttc_title,
                    COUNT(ttr_id) as total,
                    SUM(ttr_time_spent) as total_time
                 FROM
                    `time_tracking`,
                    `issue`,
                    `time_tracking_category`
                 WHERE
                    iss_id = ttr_iss_id AND
                    ttr_ttc_id = ttc_id AND
                    iss_prj_id = ? AND
                    ttr_usr_id = ? AND
                    ttr_created_date BETWEEN ? AND ?
                 GROUP BY
                    ttc_title';

        $params = [$prj_id, $usr_id, $start, $end];

        try {
            $res = DB_Helper::getInstance()->fetchAssoc($stmt, $params, AdapterInterface::DB_FETCHMODE_ASSOC);
        } catch (DatabaseException $e) {
            return [];
        }

        if (count($res) > 0) {
            foreach ($res as $index => $row) {
                $res[$index]['formatted_time'] = Misc::getFormattedTime($res[$index]['total_time'], true);
            }
        }

        return $res;
    }

    /**
     * Returns a list of issues touched by the specified user in the specified time frame in specified project.
     *
     * @param int $usr_id The id of the user
     * @param int $prj_id The project id
     * @param string $start The start date
     * @param string $end The end date
     * @return array an array of issues touched by the user
     */
    public static function getTouchedIssuesByUser($usr_id, $prj_id, $start, $end)
    {
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
                    `time_tracking`,
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
                    ttr_iss_id = iss_id AND
                    ttr_usr_id = ? AND
                    ttr_created_date BETWEEN ? AND ? AND
                    iss_prj_id = ?
                 GROUP BY
                    iss_id
                 ORDER BY
                    iss_id ASC';
        $params = [$usr_id, $start, $end, $prj_id];

        return DB_Helper::getInstance()->getAll($stmt, $params);
    }

    /**
     * Method used to add time spent on issue to a list of user issues.
     *
     * @param   array $res User issues
     * @param   string $usr_id the ID of the user this report is for
     * @param   int $start the timestamp of the beginning of the report
     * @param   int $end the timestamp of the end of this report
     */
    public static function fillTimeSpentByIssueAndTime(&$res, $usr_id, $start, $end)
    {
        $issue_ids = [];
        foreach ($res as $row) {
            $issue_ids[] = $row['iss_id'];
        }

        $stmt = 'SELECT
                    ttr_iss_id, sum(ttr_time_spent)
                 FROM
                    `time_tracking`
                 WHERE
                    ttr_usr_id = ? AND
                    ttr_created_date BETWEEN ? AND ? AND
                    ttr_iss_id in (' . DB_Helper::buildList($issue_ids) . ')
                 GROUP BY ttr_iss_id';
        $params = [$usr_id, $start, $end];
        $params = array_merge($params, $issue_ids);
        try {
            $result = DB_Helper::getInstance()->getPair($stmt, $params);
        } catch (DatabaseException $e) {
            return;
        }

        foreach ($res as $key => $item) {
            @$res[$key]['it_spent'] = $result[$item['iss_id']];
            @$res[$key]['time_spent'] = Misc::getFormattedTime($result[$item['iss_id']], false);
        }
    }
}
