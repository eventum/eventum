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

/**
 * Class to handle the business logic related to all aspects of the
 * reporting system.
 */
class Report
{
    /**
     * Method used to get all open issues and group them by user.
     *
     * @param int $prj_id The project ID
     * @param array $users
     * @param array $status
     * @param string $before_date
     * @param string $after_date
     * @param string $sort_order
     * @return array The list of issues
     */
    public static function getStalledIssuesByUser($prj_id, $users, $status, $before_date, $after_date, $sort_order)
    {
        $prj_id = (int) $prj_id;
        $ts = time();
        $before_ts = strtotime($before_date);
        $after_ts = strtotime($after_date);

        // split groups out of users array
        $groups = [];
        if (count($users) > 0) {
            foreach ($users as $key => $value) {
                if (substr($value, 0, 3) == 'grp') {
                    $groups[] = substr($value, 4);
                    unset($users[$key]);
                }
            }
        }

        $stmt = 'SELECT
                    usr_full_name,
                    iss_id,
                    iss_summary,
                    sta_title,
                    iss_sta_id,
                    iss_created_date,
                    iss_updated_date,
                    iss_last_response_date,
                    sta_color
                 FROM
                    (
                    `issue`,
                    `issue_user`,
                    `user`
                    )
                 LEFT JOIN
                    `status`
                 ON
                    iss_sta_id=sta_id
                 LEFT JOIN
                    `user_group`
                 ON
                    ugr_grp_id=usr_id
                 WHERE
                    sta_is_closed=0 AND
                    iss_prj_id=? AND
                    iss_id=isu_iss_id AND
                    isu_usr_id=usr_id AND
                    UNIX_TIMESTAMP(iss_last_response_date) < ? AND
                    UNIX_TIMESTAMP(iss_last_response_date) > ?';
        $params = [$prj_id, $before_ts, $after_ts];

        if ($users) {
            $ids = (array) $users;
            $list = DB_Helper::buildList($ids);
            $params = array_merge($params, $ids);
            $stmt .= " AND\nisu_usr_id IN($list)";
        }
        if ($groups) {
            $ids = (array) $groups;
            $list = DB_Helper::buildList($ids);
            $params = array_merge($params, $ids);
            $stmt .= " AND\nugr_grp_id IN($list)";
        }
        if ($status) {
            $ids = (array) $status;
            $list = DB_Helper::buildList($ids);
            $params = array_merge($params, $ids);
            $stmt .= " AND\niss_sta_id IN($list)";
        }

        $sort_order = Misc::escapeString($sort_order);
        $stmt .= '
                 ORDER BY
                    usr_full_name,
                    iss_last_response_date ' . $sort_order;
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, $params);
        } catch (DatabaseException $e) {
            return '';
        }

        Time_Tracking::fillTimeSpentByIssues($res);
        $issues = [];
        foreach ($res as &$row) {
            if (empty($row['iss_updated_date'])) {
                $row['iss_updated_date'] = $row['iss_created_date'];
            }
            if (empty($row['iss_last_response_date'])) {
                $row['iss_last_response_date'] = $row['iss_created_date'];
            }

            $updated_date_ts = Date_Helper::getUnixTimestamp($row['iss_updated_date'], Date_Helper::getDefaultTimezone());
            $last_response_ts = Date_Helper::getUnixTimestamp($row['iss_last_response_date'], Date_Helper::getDefaultTimezone());
            $issues[$row['usr_full_name']][$row['iss_id']] = [
                'iss_summary' => $row['iss_summary'],
                'sta_title' => $row['sta_title'],
                'iss_created_date' => $row['iss_created_date'],
                'iss_last_response_date' => $row['iss_last_response_date'],
                'time_spent' => Misc::getFormattedTime($row['time_spent']),
                'status_color' => $row['sta_color'],
                'last_update' => Date_Helper::getFormattedDateDiff($ts, $updated_date_ts),
                'last_email_response' => Date_Helper::getFormattedDateDiff($ts, $last_response_ts),
            ];
        }

        return $issues;
    }

    /**
     * Method used to get all open issues and group them by assignee or reporter.
     *
     * @param   int $prj_id The project ID
     * @param   int $cutoff_days The number of days to use as a cutoff period
     * @param bool $group_by_reporter
     * @return  array The list of issues
     */
    public static function getOpenIssuesByUser($prj_id, $cutoff_days, $group_by_reporter = false)
    {
        $prj_id = (int) $prj_id;
        $cutoff_days = (int) $cutoff_days;
        $ts = time();
        $ts_diff = $cutoff_days * Date_Helper::DAY;

        $stmt = "SELECT
                    assignee.usr_full_name as assignee_name,
                    reporter.usr_full_name as reporter_name,
                    iss_id,
                    iss_summary,
                    sta_title,
                    iss_sta_id,
                    iss_created_date,
                    iss_updated_date,
                    iss_last_response_date,
                    sta_color
                 FROM
                    (
                    `issue`,
                    `issue_user`,
                    `user` as assignee,
                    `user` as reporter
                    )
                 LEFT JOIN
                    `status`
                 ON
                    iss_sta_id=sta_id
                 WHERE
                    sta_is_closed=0 AND
                    iss_prj_id=? AND
                    iss_id=isu_iss_id AND
                    isu_usr_id=assignee.usr_id AND
                    iss_usr_id=reporter.usr_id AND
                    UNIX_TIMESTAMP(iss_created_date) < (UNIX_TIMESTAMP() - ?)
                 ORDER BY\n";
        if ($group_by_reporter) {
            $stmt .= 'reporter.usr_full_name';
        } else {
            $stmt .= 'assignee.usr_full_name';
        }
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, [$prj_id, $ts_diff]);
        } catch (DatabaseException $e) {
            return '';
        }

        Time_Tracking::fillTimeSpentByIssues($res);
        $issues = [];
        foreach ($res as &$row) {
            if (empty($row['iss_updated_date'])) {
                $row['iss_updated_date'] = $row['iss_created_date'];
            }
            if (empty($row['iss_last_response_date'])) {
                $row['iss_last_response_date'] = $row['iss_created_date'];
            }
            if ($group_by_reporter) {
                $name = $row['reporter_name'];
            } else {
                $name = $row['assignee_name'];
            }
            $update_date_ts = Date_Helper::getUnixTimestamp(
                $row['iss_updated_date'],
                Date_Helper::getDefaultTimezone()
            );
            $last_response_ts = Date_Helper::getUnixTimestamp(
                $row['iss_last_response_date'],
                Date_Helper::getDefaultTimezone()
            );
            $issues[$name][$row['iss_id']] = [
                'iss_summary' => $row['iss_summary'],
                'sta_title' => $row['sta_title'],
                'iss_created_date' => $row['iss_created_date'],
                'time_spent' => Misc::getFormattedTime($row['time_spent']),
                'status_color' => $row['sta_color'],
                'last_update' => Date_Helper::getFormattedDateDiff($ts, $update_date_ts),
                'last_email_response' => Date_Helper::getFormattedDateDiff($ts, $last_response_ts),
            ];
        }

        return $issues;
    }

    /**
     * Method used to get the list of issues in a project, and group
     * them by the assignee.
     *
     * @param   int $prj_id The project ID
     * @return  array The list of issues
     */
    public static function getIssuesByUser($prj_id)
    {
        $stmt = 'SELECT
                    usr_full_name,
                    iss_id,
                    iss_summary,
                    sta_title,
                    iss_sta_id,
                    iss_created_date,
                    sta_color
                 FROM
                    (
                    `issue`,
                    `issue_user`,
                    `user`
                    )
                 LEFT JOIN
                    `status`
                 ON
                    iss_sta_id=sta_id
                 WHERE
                    iss_prj_id=? AND
                    iss_id=isu_iss_id AND
                    isu_usr_id=usr_id
                 ORDER BY
                    usr_full_name';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, [$prj_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        Time_Tracking::fillTimeSpentByIssues($res);
        $issues = [];
        foreach ($res as $row) {
            $issues[$row['usr_full_name']][$row['iss_id']] = [
                'iss_summary' => $row['iss_summary'],
                'sta_title' => $row['sta_title'],
                'iss_created_date' => $row['iss_created_date'],
                'time_spent' => Misc::getFormattedTime($row['time_spent']),
                'status_color' => $row['sta_color'],
            ];
        }

        return $issues;
    }

    /**
     * Returns the data used by the weekly report.
     *
     * @param int $usr_id the ID of the user this report is for
     * @param int $prj_id The project id
     * @param string|DateTime $start the start date of this report
     * @param string|DateTime $end the end date of this report
     * @param array $options extra options for report:
     * - $separate_closed If closed issues should be separated from other issues.
     * - $ignore_statuses If issue status changes should be ignored in report.
     * - $separate_not_assigned_to_user Separate Issues Not Assigned to User
     * - $show_per_issue Add time spent on issue to issues
     * - $separate_no_time Separate No time spent issues
     * @return array an array of data containing all the elements of the weekly report
     */
    public static function getWeeklyReport($usr_id, $prj_id, $start, $end, $options = [])
    {
        // figure out timezone
        $user_prefs = Prefs::get($usr_id);
        $tz = $user_prefs['timezone'];

        // if start or end is string, convert assume min and max date are specified
        if (!$start instanceof DateTime) {
            $start = Date_Helper::getDateTime($start, $tz)->setTime(0, 0, 0);
        }
        if (!$end instanceof DateTime) {
            $end = Date_Helper::getDateTime($end, $tz)->setTime(23, 59, 59);
        }

        $start_ts = Date_Helper::getSqlDateTime($start);
        $end_ts = Date_Helper::getSqlDateTime($end);

        $time_tracking = Time_Tracking::getSummaryByUser($usr_id, $prj_id, $start_ts, $end_ts);

        // replace spaces in index with _ and calculate total time
        $total_time = 0;
        foreach ($time_tracking as $category => $data) {
            unset($time_tracking[$category]);
            $time_tracking[str_replace(' ', '_', $category)] = $data;
            $total_time += $data['total_time'];
        }

        // get count of issues assigned in week of report.
        $stmt = 'SELECT
                    COUNT(*)
                 FROM
                    `issue`,
                    `issue_user`,
                    `status`
                 WHERE
                    iss_id = isu_iss_id AND
                    iss_sta_id = sta_id AND
                    isu_usr_id = ? AND
                    iss_prj_id = ? AND
                    isu_assigned_date BETWEEN ? AND ?';
        $params = [$usr_id, Auth::getCurrentProject(), $start_ts, $end_ts];
        try {
            $newly_assigned = DB_Helper::getInstance()->getOne($stmt, $params);
        } catch (DatabaseException $e) {
            $newly_assigned = null;
        }

        $email_count = [
            'associated' => Support::getSentEmailCountByUser($usr_id, $start_ts, $end_ts, true),
            'other' => Support::getSentEmailCountByUser($usr_id, $start_ts, $end_ts, false),
        ];

        $htt_exclude = [];
        if (!empty($options['ignore_statuses'])) {
            $htt_exclude[] = 'status_changed';
            $htt_exclude[] = 'status_auto_changed';
            $htt_exclude[] = 'remote_status_change';
        }
        $issue_list = History::getTouchedIssuesByUser($usr_id, $prj_id, $start_ts, $end_ts, $htt_exclude);
        $issue_list += Time_Tracking::getTouchedIssuesByUser($usr_id, $prj_id, $start_ts, $end_ts);

        $issues = [
            'no_time' => [],
            'not_mine' => [],
            'closed' => [],
            'other' => [],
        ];

        // organize issues into categories
        if ($issue_list) {
            if (!empty($options['show_per_issue']) || !empty($options['separate_no_time'])) {
                Time_Tracking::fillTimeSpentByIssueAndTime($issue_list, $usr_id, $start_ts, $end_ts);
            }

            foreach ($issue_list as $row) {
                if (!empty($row['iss_customer_id']) && CRM::hasCustomerIntegration($row['iss_prj_id'])) {
                    $row['customer_name'] = CRM::getCustomerName($row['iss_prj_id'], $row['iss_customer_id']);
                } else {
                    $row['customer_name'] = null;
                }
                if (!empty($options['separate_closed']) && $row['sta_is_closed'] == 1) {
                    $issues['closed'][] = $row;
                } elseif (!empty($options['separate_not_assigned_to_user']) && !Issue::isAssignedToUser($row['iss_id'], $usr_id)) {
                    $issues['not_mine'][] = $row;
                } elseif (!empty($options['separate_no_time']) && empty($row['it_spent'])) {
                    $issues['no_time'][] = $row;
                } else {
                    $issues['other'][] = $row;
                }
            }

            $sort_function = function ($a, $b) {
                return strcasecmp($a['customer_name'], $b['customer_name']);
            };
            usort($issues['closed'], $sort_function);
            usort($issues['other'], $sort_function);
        }

        return [
            'start' => $start_ts,
            'end' => $end_ts,
            'user' => User::getDetails($usr_id),
            'issues' => $issues,
            'status_counts' => History::getTouchedIssueCountByStatus($usr_id, $prj_id, $start_ts, $end_ts),
            'new_assigned_count' => $newly_assigned,
            'time_tracking' => $time_tracking,
            'email_count' => $email_count,
            'phone_count' => Phone_Support::getCountByUser($usr_id, $start_ts, $end_ts),
            'note_count' => Note::getCountByUser($usr_id, $start_ts, $end_ts),
            'total_time' => Misc::getFormattedTime($total_time, false),
        ];
    }

    /**
     * Returns data used by the workload by time period report.
     *
     * @param   string $timezone Timezone to display time in in addition to GMT
     * @param   bool $graph If the data should be formatted for use in a graph. Default false
     * @return  array an array of data
     */
    public static function getWorkloadByTimePeriod($timezone, $graph = false)
    {
        $stmt = "SELECT
                    count(*) as events,
                    hour(his_created_date) AS time_period,
                    if (pru_role > 3, 'developer', 'customer') as performer,
                    SUM(if (pru_role > 3, 1, 0)) as dev_events,
                    SUM(if (pru_role > 3, 0, 1)) as cust_events
                 FROM
                    `issue_history`,
                    `user`,
                    `project_user`
                 WHERE
                    his_usr_id = usr_id AND
                    usr_id = pru_usr_id AND
                    pru_prj_id = ?
                 GROUP BY
                    time_period, performer
                 ORDER BY
                    time_period";
        $params = [Auth::getCurrentProject()];
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, $params);
        } catch (DatabaseException $e) {
            return [];
        }

        // get total number of developer and customer events
        $event_count = [
            'developer' => 0,
            'customer' => 0,
        ];
        foreach ($res as $row) {
            $event_count['developer'] += $row['dev_events'];
            $event_count['customer'] += $row['cust_events'];
        }

        $data = [];
        $sort_values = [];
        for ($i = 0; $i < 24; $i++) {
            $dt = Date_Helper::getDateTime(mktime($i, 0, 0), 'GMT');
            $gmt_time = $dt->format('H:i');
            // convert to the users time zone
            $dt->setTimezone(new DateTimeZone($timezone));
            $hour = $dt->format('H');
            $user_time = $dt->format('H:i');

            if ($graph) {
                $data['developer'][$hour] = '';
                $data['customer'][$hour] = '';
            } else {
                $data[$i]['display_time_gmt'] = $gmt_time;
                $data[$i]['display_time_user'] = $user_time;
            }

            // loop through results, assigning appropriate results to data array
            foreach ($res as $index => $row) {
                if ($row['time_period'] == $i) {
                    $sort_values[$row['performer']][$i] = $row['events'];

                    if ($graph) {
                        $data[$row['performer']][$hour] = (($row['events'] / $event_count[$row['performer']]) * 100);
                    } else {
                        $data[$i][$row['performer']]['count'] = $row['events'];
                        $data[$i][$row['performer']]['percentage'] = (($row['events'] / $event_count[$row['performer']]) * 100);
                    }
                    unset($res[$index]);
                }
            }
        }

        if (!$graph) {
            // get the highest action times
            foreach ($sort_values as $performer => $values) {
                arsort($values);
                reset($values);
                $data[key($values)][$performer]['rank'] = 1;
            }
        }

        return $data;
    }

    /**
     * Returns data on when support emails are sent/received.
     *
     * @param   string $timezone Timezone to display time in in addition to GMT
     * @param   bool $graph If the data should be formatted for use in a graph. Default false
     * @return  array an array of data
     */
    public static function getEmailWorkloadByTimePeriod($timezone, $graph = false)
    {
        // get total counts
        $stmt = 'SELECT
                    hour(sup_date) AS time_period,
                    count(*) as events
                 FROM
                    `support_email`
                 GROUP BY
                    time_period';
        try {
            $total = DB_Helper::getInstance()->getPair($stmt);
        } catch (DatabaseException $e) {
            return [];
        }

        // get all developer email addresses
        $users = User::getActiveAssocList(Auth::getCurrentProject(), User::ROLE_CUSTOMER);
        $emails = [];
        foreach ($users as $usr_id => $usr_full_name) {
            $emails[] = User::getFromHeader($usr_id);
        }

        // get number of support emails from developers
        $list = DB_Helper::buildList($emails);
        $stmt = "SELECT
                    hour(sup_date) AS time_period,
                    count(*) as events
                 FROM
                    `support_email`
                 WHERE
                    sup_from IN($list)
                 GROUP BY
                    time_period";
        try {
            $dev_stats = DB_Helper::getInstance()->getPair($stmt, $emails);
        } catch (DatabaseException $e) {
            return [];
        }

        // get total number of developer and customer events and build cust_stats array
        $dev_count = 0;
        $cust_count = 0;
        $cust_stats = [];
        for ($i = 0; $i < 24; $i++) {
            if (empty($dev_stats[$i])) {
                $dev_stats[$i] = 0;
            }
            $cust_stats[$i] = (@$total[$i] - @$dev_stats[$i]);
            $cust_count += (@$total[$i] - @$dev_stats[$i]);
            $dev_count += @$dev_stats[$i];
        }

        $data = [];
        $sort_values = [];
        for ($i = 0; $i < 24; $i++) {
            // convert to the users time zone
            $dt = Date_Helper::getDateTime(mktime($i, 0, 0), 'GMT');
            $gmt_time = $dt->format('H:i');
            $dt->setTimezone(new DateTimeZone($timezone));
            $hour = $dt->format('H');
            $user_time = $dt->format('H:i');

            if ($graph) {
                $data['developer'][$hour] = '';
                $data['customer'][$hour] = '';
            } else {
                $data[$i]['display_time_gmt'] = $gmt_time;
                $data[$i]['display_time_user'] = $user_time;
            }

            // use later to find highest value
            $sort_values['developer'][$i] = $dev_stats[$i];
            $sort_values['customer'][$i] = $cust_stats[$i];

            if ($graph) {
                if ($dev_count == 0) {
                    $data['developer'][$hour] = 0;
                } else {
                    $data['developer'][$hour] = (($dev_stats[$i] / $dev_count) * 100);
                }
                if ($cust_count == 0) {
                    $data['customer'][$hour] = 0;
                } else {
                    $data['customer'][$hour] = (($cust_stats[$i] / $cust_count) * 100);
                }
            } else {
                $data[$i]['developer']['count'] = $dev_stats[$i];
                if ($dev_count == 0) {
                    $data[$i]['developer']['percentage'] = 0;
                } else {
                    $data[$i]['developer']['percentage'] = (($dev_stats[$i] / $dev_count) * 100);
                }
                $data[$i]['customer']['count'] = $cust_stats[$i];
                if ($cust_count == 0) {
                    $data[$i]['customer']['percentage'] = 0;
                } else {
                    $data[$i]['customer']['percentage'] = (($cust_stats[$i] / $cust_count) * 100);
                }
            }
        }

        if (!$graph) {
            // get the highest action times
            foreach ($sort_values as $performer => $values) {
                arsort($values);
                reset($values);
                $data[key($values)][$performer]['rank'] = 1;
            }
        }

        return $data;
    }

    /**
     * Returns data for the custom fields report, based on the field and options passed in.
     *
     * @param   int $fld_id the id of the custom field
     * @param   array $cfo_ids an array of option ids
     * @param   string $group_by how the data should be grouped
     * @param   string $start_date
     * @param   string $end_date
     * @param   bool $list if the values should be listed out instead of just counted
     * @param   string $interval the interval values should be grouped over time, empty (none) by default
     * @param   int $assignee the assignee the issue should belong to
     * @return  array an array of data
     */
    public static function getCustomFieldReport($fld_id, $cfo_ids, $group_by = 'issue', $start_date = null, $end_date = null, $list = false, $interval = null, $assignee = null)
    {
        $prj_id = Auth::getCurrentProject();
        $fld_id = (int) $fld_id;

        // get field values
        $options = Custom_Field::getOptions($fld_id, $cfo_ids);

        if ($group_by == 'customer') {
            $group_by_field = 'iss_customer_id';
        } else {
            $group_by_field = 'iss_id';
        }

        if ($assignee == -1) {
            $assignee = null;
        }

        $label_field = '';
        $interval_group_by_field = '';
        switch ($interval) {
            case 'day':
                $label_field = "CONCAT(YEAR(iss_created_date), '-', MONTH(iss_created_date), '-', DAY(iss_created_date))";
                $interval_group_by_field = 'CONCAT(YEAR(iss_created_date), MONTH(iss_created_date), DAY(iss_created_date))';
                break;
            case 'week':
                $label_field = "CONCAT(YEAR(iss_created_date), '/', WEEK(iss_created_date))";
                $interval_group_by_field = 'WEEK(iss_created_date)';
                break;
            case 'month':
                $label_field = "CONCAT(YEAR(iss_created_date), '/', MONTH(iss_created_date))";
                $interval_group_by_field = 'MONTH(iss_created_date)';
                break;
            case 'year':
                $label_field = 'YEAR(iss_created_date)';
                $interval_group_by_field = 'YEAR(iss_created_date)';
                break;
        }

        if ($list == true) {
            $params = [];
            $sql = "SELECT
                        DISTINCT($group_by_field),
                        iss_id,
                        iss_summary,
                        iss_customer_id,
                        count(DISTINCT(iss_id)) as row_count,
                        fld_id";
            if ($label_field) {
                $sql .= ",
                        $label_field as interval_label";
            }
            $sql .= '
                    FROM
                        `custom_field`,';
            if (count($options) > 0) {
                $sql .= '
                        `custom_field_option`,';
            }
            $sql .= '
                        `issue_custom_field`,
                        `issue`,
                        `issue_user`
                    WHERE
                        fld_id = icf_fld_id AND';
            if (count($options) > 0) {
                $sql .=
                        ' cfo_id = icf_value AND';
            }
            $sql .= '
                        icf_iss_id = iss_id AND
                        isu_iss_id = iss_id AND
                        icf_fld_id = ?';
            $params[] = $fld_id;
            if (count($options) > 0) {
                $ids = array_keys($options);
                $list = DB_Helper::buildList($ids);
                $sql .= " AND cfo_id IN($list)";
                $params = array_merge($params, $ids);
            }
            if ($start_date && $end_date) {
                $sql .= " AND\niss_created_date BETWEEN ? AND ?";
                $params[] = $start_date;
                $params[] = $end_date;
            }
            if ($assignee) {
                $sql .= " AND\nisu_usr_id = ?";
                $params[] = $assignee;
            }
            $sql .= "
                    GROUP BY
                        $group_by_field
                    ORDER BY";
            if ($label_field) {
                $sql .= "
                        $label_field DESC,";
            }
            $sql .= '
                        row_count DESC';
            try {
                $res = DB_Helper::getInstance()->getAll($sql, $params);
            } catch (DatabaseException $e) {
                return [];
            }

            if (CRM::hasCustomerIntegration($prj_id)) {
                $crm = CRM::getInstance($prj_id);
                $crm->processListIssuesResult($res);
                if ($group_by == 'issue') {
                    usort($res,
                        function ($a, $b) {
                            if ($a['customer_title'] < $b['customer_title']) {
                                return -1;
                            } elseif ($a['customer_title'] > $b['customer_title']) {
                                return 1;
                            }

                            return 0;
                        }
                    );
                }
            }

            foreach ($res as &$row) {
                $row['field_value'] = Custom_Field::getDisplayValue($row['iss_id'], $row['fld_id']);
            }

            return $res;
        }

        $data = [];
        foreach ($options as $cfo_id => $value) {
            $fields = 1;
            $stmt = 'SELECT';
            if ($label_field != '') {
                $stmt .= "
                        $label_field as label,";
                $fields++;
            }
            $stmt .= "
                        COUNT(DISTINCT $group_by_field)
                    FROM
                        `issue_custom_field`,
                        `issue`,
                        `issue_user`
                    WHERE
                        icf_iss_id = iss_id AND
                        isu_iss_id = iss_id AND
                        icf_fld_id = ? AND
                        icf_value = ?";
            $params = [$fld_id, $cfo_id];
            if ($start_date && $end_date) {
                $stmt .= " AND\niss_created_date BETWEEN ? AND ?";
                $params[] = $start_date;
                $params[] = $end_date;
            }
            if ($assignee) {
                $stmt .= " AND\nisu_usr_id = ?";
                $params[] = $assignee;
            }
            if ($interval_group_by_field != '') {
                $stmt .= "
                    GROUP BY
                        $interval_group_by_field
                    ORDER BY
                        $label_field ASC";
                try {
                    if ($fields > 2) {
                        $res = DB_Helper::getInstance()->fetchAssoc($stmt, $params);
                    } else {
                        $res = DB_Helper::getInstance()->getPair($stmt, $params);
                    }
                } catch (DatabaseException $e) {
                    return [];
                }
            } else {
                try {
                    $res = DB_Helper::getInstance()->getOne($stmt, $params);
                } catch (DatabaseException $e) {
                    return [];
                }
            }
            $data[$value] = $res;
        }

        // include count of all other values (used in pie chart)
        $list = DB_Helper::buildList($cfo_ids);
        $stmt = "SELECT
                    COUNT(DISTINCT $group_by_field)
                FROM
                    `custom_field_option`,
                    `issue_custom_field`,
                    `issue`
                WHERE
                    cfo_id = icf_value AND
                    icf_iss_id = iss_id AND
                    cfo_id NOT IN($list) AND
                    icf_fld_id = ?
                    ";
        $params = $cfo_ids;
        $params[] = $fld_id;
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, $params);
        } catch (DatabaseException $e) {
            return [];
        }
        $data['All Others'] = $res;

        return $data;
    }

    /**
     * Returns data for the custom fields weekly report, based on the field and options passed in.
     *
     * @param   int $fld_id the id of the custom field
     * @param   array $cfo_ids an array of option ids
     * @param   string $start_date
     * @param   string $end_date
     * @param   bool $per_user Show time spent per user
     * @return  array an array of data
     */
    public static function getCustomFieldWeeklyReport($fld_id, $cfo_ids, $start_date, $end_date, $per_user = false)
    {
        $fld_id = (int) $fld_id;
        $cfo_ids = (array) $cfo_ids;
        // get field values
        $options = Custom_Field::getOptions($fld_id, $cfo_ids);

        $params = [];
        $sql = 'SELECT
                    iss_id,
                    SUM(ttr_time_spent) ttr_time_spent_sum,
                    iss_summary,
                    iss_customer_id
               ';

        if ($per_user) {
            $sql .= ', usr_full_name ';
        }
        $sql .= '
                 FROM
                    `time_tracking`,';

        if ($per_user) {
            $sql .= '`user`, ';
        }

        $sql .= '
                        `issue`
                    WHERE
                        iss_prj_id=? AND
                        ttr_created_date BETWEEN ? AND ? AND
                        ttr_iss_id = iss_id AND
                        ';
        $params[] = Auth::getCurrentProject();
        $params[] = "$start_date 00:00:00";
        $params[] = "$end_date 23:59:59";
        if ($per_user) {
            $sql .= ' usr_id = ttr_usr_id AND ';
        }
        $sql .= '
                        ttr_iss_id = iss_id
                        ';
        if (count($options) > 0) {
            $ids = array_keys($options);
            $list = DB_Helper::buildList($ids);
            $sql .= " AND (
                SELECT
                    count(*)
                FROM
                    `issue_custom_field` a
                WHERE
                    a.icf_fld_id = ? AND
                    a.icf_value IN($list) AND
                    a.icf_iss_id = ttr_iss_id
                ) > 0";
            $params[] = $fld_id;
            $params = array_merge($params, $ids);
        }
        if ($per_user) {
            $sql .= '
                    GROUP BY
                    iss_id, ttr_usr_id';
        } else {
            $sql .= '
                    GROUP BY
                    iss_id';
        }

        try {
            $res = DB_Helper::getInstance()->getAll($sql, $params);
        } catch (DatabaseException $e) {
            return [];
        }

        foreach ($res as &$row) {
            $row['field_value'] = Custom_Field::getDisplayValue($row['iss_id'], $fld_id);
            $row['ttr_time_spent_sum_formatted'] = Misc::getFormattedTime($row['ttr_time_spent_sum'], false);
        }

        return $res;
    }

    /**
     * Returns workload information for the specified date range and interval.
     *
     * @param   string $interval the interval to use in this report
     * @param   string $type If this report is aggregate or individual
     * @param   string $start the start date of this report
     * @param   string $end the end date of this report
     * @param   int $category_id The category to restrict this report to
     * @return  array an array containing workload data
     */
    public static function getWorkloadByDateRange($interval, $type, $start, $end, $category_id)
    {
        $data = [];
        $category_id = (int) $category_id;

        // figure out the correct format code
        switch ($interval) {
            case 'day':
                $format = '%m/%d/%y';
                $order_by = '%1$s';
                break;
            case 'dow':
                $format = '%W';
                $order_by = "CASE WHEN DATE_FORMAT(%1\$s, '%%w') = 0 THEN 7 ELSE DATE_FORMAT(%1\$s, '%%w') END";
                break;
            case 'week':
                if ($type == 'aggregate') {
                    $format = '%v';
                } else {
                    $format = '%v/%y';
                }
                $order_by = '%1$s';
                break;
            case 'dom':
                $format = '%d';
                break;
            case 'month':
                if ($type == 'aggregate') {
                    $format = '%b';
                    $order_by = "DATE_FORMAT(%1\$s, '%%m')";
                } else {
                    $format = '%b/%y';
                    $order_by = '%1$s';
                }
                break;
            default:
                throw new LogicException('Invalid interval');
        }

        // get issue counts
        $stmt = 'SELECT
                    DATE_FORMAT(iss_created_date, ?),
                    count(*)
                 FROM
                    `issue`
                 WHERE
                    iss_prj_id=? AND
                    iss_created_date BETWEEN ? AND ?';
        $params = [$format, Auth::getCurrentProject(), $start, $end];
        if (!empty($category_id)) {
            $stmt .= ' AND
                    iss_prc_id = ?';
            $params[] = $category_id;
        }
        $stmt .= '
                 GROUP BY
                    DATE_FORMAT(iss_created_date, ?)';
        $params[] = $format;
        if (!empty($order_by)) {
            $stmt .= "\nORDER BY " . sprintf($order_by, 'iss_created_date');
        }
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, $params);
        } catch (DatabaseException $e) {
            return [];
        }
        $data['issues']['points'] = $res;

        $data['issues']['stats'] = [
            'total' => 0,
            'avg' => 0,
            'median' => 0,
            'max' => 0,
        ];

        if ($res) {
            $stats = new Math_Stats();
            $stats->setData($res);

            $data['issues']['stats'] = [
                'total' => $stats->sum(),
                'avg' => $stats->mean(),
                'median' => $stats->median(),
                'max' => $stats->max(),
            ];
        }

        // get email counts
        $params = [];
        $stmt = 'SELECT
                    DATE_FORMAT(sup_date, ?),
                    count(*)
                 FROM
                    `support_email`,
                    `email_account`';
        $params[] = $format;
        if (!empty($category_id)) {
            $stmt .= ',
                     `issue`';
        }
        $stmt .= '
                 WHERE
                    sup_ema_id=ema_id AND
                    ema_prj_id=? AND
                    sup_date BETWEEN ? AND ?';
        $params[] = Auth::getCurrentProject();
        $params[] = $start;
        $params[] = $end;
        if (!empty($category_id)) {
            $stmt .= ' AND
                    sup_iss_id = iss_id AND
                    iss_prc_id = ?';
            $params[] = $category_id;
        }
        $stmt .= '
                 GROUP BY
                    DATE_FORMAT(sup_date, ?)';
        $params[] = $format;
        if (!empty($order_by)) {
            $stmt .= "\nORDER BY " . sprintf($order_by, 'sup_date');
        }

        try {
            $res = DB_Helper::getInstance()->getPair($stmt, $params);
        } catch (DatabaseException $e) {
            return [];
        }
        $data['emails']['points'] = $res;

        if (count($res) > 0) {
            $stats = new Math_Stats();
            $stats->setData($res);

            $data['emails']['stats'] = [
                'total' => $stats->sum(),
                'avg' => $stats->mean(),
                'median' => $stats->median(),
                'max' => $stats->max(),
            ];
        } else {
            $data['emails']['stats'] = [
                'total' => 0,
                'avg' => 0,
                'median' => 0,
                'max' => 0,
            ];
        }

        return $data;
    }

    /**
     * @param int $prj_id
     * @return array
     */
    public static function getEstimatedDevTimeReport($prj_id)
    {
        $sql = 'SELECT
            prc_id,
        	prc_title,
        	SUM(iss_dev_time) as dev_time
        FROM
            `issue`,
            `project_category`,
            `status`
        WHERE
        	iss_prc_id = prc_id AND
        	iss_sta_id = sta_id AND
        	sta_is_closed != 1 AND
        	iss_prj_id = ?
        GROUP BY
        	iss_prc_id';
        try {
            $res = DB_Helper::getInstance()->getAll($sql, [$prj_id]);
        } catch (DatabaseException $e) {
            return null;
        }

        return $res;
    }

    /**
     * @param int $prj_id
     * @param array $categories
     * @param array $statuses
     * @return array
     * @see \Eventum\Controller\Report\CategoryStatusController
     */
    public static function getCategoryStatusReport($prj_id, $categories, $statuses)
    {
        $data = [];
        foreach ($categories as $cat_id => $cat_title) {
            $data[$cat_id] = [
                'title' => $cat_title,
                'statuses' => [],
            ];

            foreach ($statuses as $sta_id => $sta_title) {
                $sql
                    = 'SELECT
                    count(*)
                FROM
                    `issue`
                WHERE
                    iss_prj_id = ? AND
                    iss_sta_id = ? AND
                    iss_prc_id = ?';
                try {
                    $res = DB_Helper::getInstance()->getOne($sql, [$prj_id, $sta_id, $cat_id]);
                } catch (DatabaseException $e) {
                    break 2;
                }
                $data[$cat_id]['statuses'][$sta_id] = [
                    'title' => $sta_title,
                    'count' => $res,
                ];
            }
        }

        return $data;
    }
}
