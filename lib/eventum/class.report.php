<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2014 Eventum Team.                              |
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
// | 51 Franklin Street, Suite 330                                          |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

/**
 * Class to handle the business logic related to all aspects of the
 * reporting system.
 */

class Report
{

    /**
     * Method used to get all open issues and group them by user.
     *
     * @param   integer $prj_id The project ID
     * @param $users
     * @param $status
     * @param $before_date
     * @param $after_date
     * @param $sort_order
     * @return  array The list of issues
     */
    public static function getStalledIssuesByUser($prj_id, $users, $status, $before_date, $after_date, $sort_order)
    {
        $prj_id = Misc::escapeInteger($prj_id);
        $ts = time();
        $before_ts = strtotime($before_date);
        $after_ts = strtotime($after_date);

        // split groups out of users array
        $groups = array();
        if (count($users) > 0) {
            foreach ($users as $key => $value) {
                if (substr($value, 0, 3) == 'grp') {
                    $groups[] = substr($value, 4);
                    unset($users[$key]);
                }
            }
        }

        $stmt = "SELECT
                    usr_full_name,
                    iss_id,
                    iss_summary,
                    sta_title,
                    iss_sta_id,
                    iss_created_date,
                    iss_updated_date,
                    iss_last_response_date,
                    sta_color,
                    iss_private
                 FROM
                    (
                    {{%issue}},
                    {{%issue_user}},
                    {{%user}}
                    )
                 LEFT JOIN
                    {{%status}}
                 ON
                    iss_sta_id=sta_id
                 WHERE
                    sta_is_closed=0 AND
                    iss_prj_id=? AND
                    iss_id=isu_iss_id AND
                    isu_usr_id=usr_id AND
                    UNIX_TIMESTAMP(iss_last_response_date) < ? AND
                    UNIX_TIMESTAMP(iss_last_response_date) > ?";
        if (count($users) > 0) {
            $stmt .= " AND\nisu_usr_id IN(" . join(', ', Misc::escapeInteger($users)) . ")";
        }
        if (count($groups) > 0) {
            $stmt .= " AND\nusr_grp_id IN(" . join(', ', Misc::escapeInteger($groups)) . ")";
        }
        if (count($status) > 0) {
            $stmt .= " AND\niss_sta_id IN(" . join(', ', Misc::escapeInteger($status)) . ")";
        }
        $stmt .= "
                 ORDER BY
                    usr_full_name,
                    iss_last_response_date " . Misc::escapeString($sort_order);
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, array($prj_id, $before_ts, $after_ts));
        } catch (DbException $e) {
            return "";
        }

        Time_Tracking::getTimeSpentByIssues($res);
        $issues = array();
        for ($i = 0; $i < count($res); $i++) {
            if (empty($res[$i]['iss_updated_date'])) {
                $res[$i]['iss_updated_date'] = $res[$i]['iss_created_date'];
            }
            if (empty($res[$i]['iss_last_response_date'])) {
                $res[$i]['iss_last_response_date'] = $res[$i]['iss_created_date'];
            }
            $updated_date_ts = Date_Helper::getUnixTimestamp(
                $res[$i]['iss_updated_date'],
                Date_Helper::getDefaultTimezone()
            );
            $last_response_ts = Date_Helper::getUnixTimestamp(
                $res[$i]['iss_last_response_date'],
                Date_Helper::getDefaultTimezone()
            );
            $issues[$res[$i]['usr_full_name']][$res[$i]['iss_id']] = array(
                'iss_summary'         => $res[$i]['iss_summary'],
                'sta_title'           => $res[$i]['sta_title'],
                'iss_created_date'    => Date_Helper::getFormattedDate($res[$i]['iss_created_date']),
                'iss_last_response_date'    => Date_Helper::getFormattedDate($res[$i]['iss_last_response_date']),
                'time_spent'          => Misc::getFormattedTime($res[$i]['time_spent']),
                'status_color'        => $res[$i]['sta_color'],
                'last_update'         => Date_Helper::getFormattedDateDiff($ts, $updated_date_ts),
                'last_email_response' => Date_Helper::getFormattedDateDiff($ts, $last_response_ts),
            );
        }

        return $issues;
    }

    /**
     * Method used to get all open issues and group them by assignee or reporter.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $cutoff_days The number of days to use as a cutoff period
     * @param bool $group_by_reporter
     * @return  array The list of issues
     */
    public static function getOpenIssuesByUser($prj_id, $cutoff_days, $group_by_reporter = false)
    {
        $prj_id = Misc::escapeInteger($prj_id);
        $cutoff_days = Misc::escapeInteger($cutoff_days);
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
                    {{%issue}},
                    {{%issue_user}},
                    {{%user}} as assignee,
                    {{%user}} as reporter
                    )
                 LEFT JOIN
                    {{%status}}
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
            $stmt .= "reporter.usr_full_name";
        } else {
            $stmt .= "assignee.usr_full_name";
        }
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, array($prj_id, $ts_diff));
        } catch (DbException $e) {
            return "";
        }

        Time_Tracking::getTimeSpentByIssues($res);
        $issues = array();
        for ($i = 0; $i < count($res); $i++) {
            if (empty($res[$i]['iss_updated_date'])) {
                $res[$i]['iss_updated_date'] = $res[$i]['iss_created_date'];
            }
            if (empty($res[$i]['iss_last_response_date'])) {
                $res[$i]['iss_last_response_date'] = $res[$i]['iss_created_date'];
            }
            if ($group_by_reporter) {
                $name = $res[$i]['reporter_name'];
            } else {
                $name = $res[$i]['assignee_name'];
            }
            $update_date_ts = Date_Helper::getUnixTimestamp(
                $res[$i]['iss_updated_date'],
                Date_Helper::getDefaultTimezone()
            );
            $last_response_ts = Date_Helper::getUnixTimestamp(
                $res[$i]['iss_last_response_date'],
                Date_Helper::getDefaultTimezone()
            );
            $issues[$name][$res[$i]['iss_id']] = array(
                'iss_summary'         => $res[$i]['iss_summary'],
                'sta_title'           => $res[$i]['sta_title'],
                'iss_created_date'    => Date_Helper::getFormattedDate($res[$i]['iss_created_date']),
                'time_spent'          => Misc::getFormattedTime($res[$i]['time_spent']),
                'status_color'        => $res[$i]['sta_color'],
                'last_update'         => Date_Helper::getFormattedDateDiff($ts, $update_date_ts),
                'last_email_response' => Date_Helper::getFormattedDateDiff($ts, $last_response_ts)
            );
        }

        return $issues;
    }

    /**
     * Method used to get the list of issues in a project, and group
     * them by the assignee.
     *
     * @param   integer $prj_id The project ID
     * @return  array The list of issues
     */
    public static function getIssuesByUser($prj_id)
    {
        $stmt = "SELECT
                    usr_full_name,
                    iss_id,
                    iss_summary,
                    sta_title,
                    iss_sta_id,
                    iss_created_date,
                    sta_color
                 FROM
                    (
                    {{%issue}},
                    {{%issue_user}},
                    {{%user}}
                    )
                 LEFT JOIN
                    {{%status}}
                 ON
                    iss_sta_id=sta_id
                 WHERE
                    iss_prj_id=? AND
                    iss_id=isu_iss_id AND
                    isu_usr_id=usr_id
                 ORDER BY
                    usr_full_name";
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, array($prj_id));
        } catch (DbException $e) {
            return "";
        }

        Time_Tracking::getTimeSpentByIssues($res);
        $issues = array();
        for ($i = 0; $i < count($res); $i++) {
            $issues[$res[$i]['usr_full_name']][$res[$i]['iss_id']] = array(
                'iss_summary'      => $res[$i]['iss_summary'],
                'sta_title'        => $res[$i]['sta_title'],
                'iss_created_date' => Date_Helper::getFormattedDate($res[$i]['iss_created_date']),
                'time_spent'       => Misc::getFormattedTime($res[$i]['time_spent']),
                'status_color'     => $res[$i]['sta_color']
            );
        }

        return $issues;
    }

    /**
     * Returns the data used by the weekly report.
     *
     * @param   string $usr_id The ID of the user this report is for.
     * @param   string $start The start date of this report.
     * @param   string $end The end date of this report.
     * @param   boolean $separate_closed If closed issues should be separated from other issues.
     * @param   boolean $ignore_statuses If issue status changes should be ignored in report.
     * @param   boolean $separate_not_assigned_to_user Separate Issues Not Assigned to User
     * @return  array An array of data containing all the elements of the weekly report.
     */
    public static function getWeeklyReport($usr_id, $start, $end, $separate_closed = false, $ignore_statuses = false, $separate_not_assigned_to_user = false)
    {
        $usr_id = Misc::escapeInteger($usr_id);

        // figure out timezone
        $user_prefs = Prefs::get($usr_id);
        $tz = $user_prefs["timezone"];

        $start_ts = Date_Helper::getDateTime($start, $tz)->setTime(0, 0, 0)->format('Y-m-d H:i:s');
        $end_ts = Date_Helper::getDateTime($end, $tz)->setTime(23, 59, 59)->format('Y-m-d H:i:s');

        $time_tracking = Time_Tracking::getSummaryByUser($usr_id, $start_ts, $end_ts);

        // replace spaces in index with _ and calculate total time
        $total_time = 0;
        foreach ($time_tracking as $category => $data) {
            unset($time_tracking[$category]);
            $time_tracking[str_replace(" ", "_", $category)] = $data;
            $total_time += $data["total_time"];
        }

        // get count of issues assigned in week of report.
        $stmt = "SELECT
                    COUNT(*)
                 FROM
                    {{%issue}},
                    {{%issue_user}},
                    {{%status}}
                 WHERE
                    iss_id = isu_iss_id AND
                    iss_sta_id = sta_id AND
                    isu_usr_id = ? AND
                    iss_prj_id = ? AND
                    isu_assigned_date BETWEEN ? AND ?";
        $params = array($usr_id, Auth::getCurrentProject(), $start_ts, $end_ts);
        try {
            $newly_assigned = DB_Helper::getInstance()->getOne($stmt, $params);
        } catch (DbException $e) {
            // FIXME: why no handling
        }

        $email_count = array(
            "associated"    =>  Support::getSentEmailCountByUser($usr_id, $start_ts, $end_ts, true),
            "other"         =>  Support::getSentEmailCountByUser($usr_id, $start_ts, $end_ts, false)
        );

        $htt_exclude = array();
        if ($ignore_statuses) {
            $htt_exclude[] = 'status_changed';
            $htt_exclude[] = 'status_auto_changed';
            $htt_exclude[] = 'remote_status_change';
        }

        $data = array(
            "start"     => str_replace('-', '.', $start),
            "end"       => str_replace('-', '.', $end),
            "user"      => User::getDetails($usr_id),
            "group_name"=> Group::getName(User::getGroupID($usr_id)),
            "issues"    => History::getTouchedIssuesByUser($usr_id, $start_ts, $end_ts, $separate_closed, $htt_exclude, $separate_not_assigned_to_user),
            "status_counts" => History::getTouchedIssueCountByStatus($usr_id, $start_ts, $end_ts),
            "new_assigned_count"    =>  $newly_assigned,
            "time_tracking" => $time_tracking,
            "email_count"   => $email_count,
            "phone_count"   => Phone_Support::getCountByUser($usr_id, $start_ts, $end_ts),
            "note_count"    => Note::getCountByUser($usr_id, $start_ts, $end_ts),
            "total_time"    => Misc::getFormattedTime($total_time, false)
        );

        return $data;
    }

    /**
     * Returns data used by the workload by time period report.
     *
     * @param   string $timezone Timezone to display time in in addition to GMT
     * @param   boolean $graph If the data should be formatted for use in a graph. Default false
     * @return  array An array of data.
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
                    {{%issue_history}},
                    {{%user}},
                    {{%project_user}}
                 WHERE
                    his_usr_id = usr_id AND
                    usr_id = pru_usr_id AND
                    pru_prj_id = ?
                 GROUP BY
                    time_period, performer
                 ORDER BY
                    time_period";
        $params = array(Auth::getCurrentProject());
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, $params);
        } catch (DbException $e) {
            return array();
        }

        // get total number of developer and customer events
        $event_count = array(
            "developer" =>  0,
            "customer"  =>  0
        );
        foreach ($res as $row) {
            $event_count["developer"] += $row["dev_events"];
            $event_count["customer"] += $row["cust_events"];
        }

        $data = array();
        $sort_values = array();
        for ($i = 0; $i < 24; $i++) {
            $dt = Date_Helper::getDateTime(mktime($i, 0, 0), 'GMT');
            $gmt_time = $dt->format('H:i');
            // convert to the users time zone
            $dt->setTimeZone(new DateTimeZone($timezone));
            $hour = $dt->format('H');
            $user_time = $dt->format('H:i');

            if ($graph) {
                $data["developer"][$hour] = "";
                $data["customer"][$hour] = "";
            } else {
                $data[$i]["display_time_gmt"] = $gmt_time;
                $data[$i]["display_time_user"] = $user_time;
            }

            // loop through results, assigning appropriate results to data array
            foreach ($res as $index => $row) {
                if ($row["time_period"] == $i) {
                    $sort_values[$row["performer"]][$i] = $row["events"];

                    if ($graph) {
                        $data[$row["performer"]][$hour] = (($row["events"] / $event_count[$row["performer"]]) * 100);
                    } else {
                        $data[$i][$row["performer"]]["count"] = $row["events"];
                        $data[$i][$row["performer"]]["percentage"] = (($row["events"] / $event_count[$row["performer"]]) * 100);
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
                $data[key($values)][$performer]["rank"] = 1;
            }
        }

        return $data;
    }

    /**
     * Returns data on when support emails are sent/received.
     *
     * @param   string $timezone Timezone to display time in in addition to GMT
     * @param   boolean $graph If the data should be formatted for use in a graph. Default false
     * @return  array An array of data.
     */
    public static function getEmailWorkloadByTimePeriod($timezone, $graph = false)
    {
        // get total counts
        $stmt = "SELECT
                    hour(sup_date) AS time_period,
                    count(*) as events
                 FROM
                    {{%support_email}}
                 GROUP BY
                    time_period";
        try {
            $total = DB_Helper::getInstance()->getAssoc($stmt);
        } catch (DbException $e) {
            return array();
        }

        // get all developer email addresses
        $users = User::getActiveAssocList(Auth::getCurrentProject(), User::getRoleID("customer"));
        $emails = array();
        foreach ($users as $usr_id => $usr_full_name) {
            $emails[] = Misc::escapeString(User::getFromHeader($usr_id));
        }

        // get number of support emails from developers
        $stmt = "SELECT
                    hour(sup_date) AS time_period,
                    count(*) as events
                 FROM
                    {{%support_email}}
                 WHERE
                    sup_from IN('" . join("','", $emails) . "')
                 GROUP BY
                    time_period";
        try {
            $dev_stats = DB_Helper::getInstance()->getAssoc($stmt);
        } catch (DbException $e) {
            return array();
        }

        // get total number of developer and customer events and build cust_stats array
        $dev_count = 0;
        $cust_count = 0;
        $cust_stats = array();
        for ($i = 0; $i < 24; $i++) {
            if (empty($dev_stats[$i])) {
                $dev_stats[$i] = 0;
            }
            $cust_stats[$i] = (@$total[$i] - @$dev_stats[$i]);
            $cust_count += (@$total[$i] - @$dev_stats[$i]);
            $dev_count += @$dev_stats[$i];
        }

        $data = array();
        $sort_values = array();
        for ($i = 0; $i < 24; $i++) {
            // convert to the users time zone
            $dt = Date_Helper::getDateTime(mktime($i, 0, 0), 'GMT');
            $gmt_time = $dt->format('H:i');
            $dt->setTimeZone(new DateTimeZone($timezone));
            $hour = $dt->format('H');
            $user_time = $dt->format('H:i');

            if ($graph) {
                $data["developer"][$hour] = "";
                $data["customer"][$hour] = "";
            } else {
                $data[$i]["display_time_gmt"] = $gmt_time;
                $data[$i]["display_time_user"] = $user_time;
            }

            // use later to find highest value
            $sort_values["developer"][$i] = $dev_stats[$i];
            $sort_values["customer"][$i] = $cust_stats[$i];

            if ($graph) {
                if ($dev_count == 0) {
                    $data["developer"][$hour] = 0;
                } else {
                    $data["developer"][$hour] = (($dev_stats[$i] / $dev_count) * 100);
                }
                if ($cust_count == 0) {
                    $data["customer"][$hour] = 0;
                } else {
                    $data["customer"][$hour] = (($cust_stats[$i] / $cust_count) * 100);
                }
            } else {
                $data[$i]["developer"]["count"] = $dev_stats[$i];
                if ($dev_count == 0) {
                    $data[$i]["developer"]["percentage"] = 0;
                } else {
                    $data[$i]["developer"]["percentage"] = (($dev_stats[$i] / $dev_count) * 100);
                }
                $data[$i]["customer"]["count"] = $cust_stats[$i];
                if ($cust_count == 0) {
                    $data[$i]["customer"]["percentage"] = 0;
                } else {
                    $data[$i]["customer"]["percentage"] = (($cust_stats[$i] / $cust_count) * 100);
                }
            }
        }

        if (!$graph) {
            // get the highest action times
            foreach ($sort_values as $performer => $values) {
                arsort($values);
                reset($values);
                $data[key($values)][$performer]["rank"] = 1;
            }
        }

        return $data;
    }

    /**
     * Returns data for the custom fields report, based on the field and options passed in.
     *
     * @param   integer $fld_id The id of the custom field.
     * @param   array $cfo_ids An array of option ids.
     * @param   string $group_by How the data should be grouped.
     * @param   string $start_date
     * @param   string $end_date
     * @param   boolean $list If the values should be listed out instead of just counted.
     * @param   string $interval The interval values should be grouped over time, empty (none) by default.
     * @param   integer $assignee The assignee the issue should belong to.
     * @return  array An array of data.
     */
    public static function getCustomFieldReport($fld_id, $cfo_ids, $group_by = "issue", $start_date = false, $end_date = false, $list = false, $interval = '', $assignee = false)
    {
        $prj_id = Auth::getCurrentProject();
        $fld_id = Misc::escapeInteger($fld_id);
        $cfo_ids = Misc::escapeInteger($cfo_ids);

        // get field values
        $options = Custom_Field::getOptions($fld_id, $cfo_ids);

        if ($group_by == "customer") {
            $group_by_field = "iss_customer_id";
        } else {
            $group_by_field = "iss_id";
        }

        if ($assignee == -1) {
            $assignee = false;
        }

        $label_field = '';
        $interval_group_by_field = '';
        switch ($interval) {
            case "day":
                $label_field = "CONCAT(YEAR(iss_created_date), '-', MONTH(iss_created_date), '-', DAY(iss_created_date))";
                $interval_group_by_field = "CONCAT(YEAR(iss_created_date), MONTH(iss_created_date), DAY(iss_created_date))";
                break;
            case "week":
                $label_field = "CONCAT(YEAR(iss_created_date), '/', WEEK(iss_created_date))";
                $interval_group_by_field = "WEEK(iss_created_date)";
                break;
            case "month":
                $label_field = "CONCAT(YEAR(iss_created_date), '/', MONTH(iss_created_date))";
                $interval_group_by_field = "MONTH(iss_created_date)";
                break;
            case "year":
                $label_field = "YEAR(iss_created_date)";
                $interval_group_by_field = "YEAR(iss_created_date)";
                break;
        }

        if ($list == true) {
            $sql = "SELECT
                        DISTINCT($group_by_field),
                        iss_id,
                        iss_summary,
                        iss_customer_id,
                        count(DISTINCT(iss_id)) as row_count,
                        iss_private,
                        fld_id";
            if ($label_field != '') {
                $sql .= ",
                        $label_field as interval_label";
            }
            $sql .= "
                    FROM
                        {{%custom_field}},";
            if (count($options) > 0) {
                $sql .= "
                        {{%custom_field_option}},";
            }
            $sql .= "
                        {{%issue_custom_field}},
                        {{%issue}},
                        {{%issue_user}}
                    WHERE
                        fld_id = icf_fld_id AND";
            if (count($options) > 0) {
                $sql .=
                        " cfo_id = icf_value AND";
            }
            $sql .= "
                        icf_iss_id = iss_id AND
                        isu_iss_id = iss_id AND
                        icf_fld_id = $fld_id";
            if (count($options) > 0) {
                $sql .= " AND
                        cfo_id IN('" . join("','", Misc::escapeString(array_keys($options))) . "')";
            }
            if (($start_date != false) && ($end_date != false)) {
                $sql .= " AND\niss_created_date BETWEEN '" . Misc::escapeString($start_date) . "' AND '" . Misc::escapeString($end_date) . "'";
            }
            if ($assignee != false) {
                $sql .= " AND\nisu_usr_id = " . Misc::escapeInteger($assignee);
            }
            $sql .= "
                    GROUP BY
                        $group_by_field
                    ORDER BY";
            if ($label_field != '') {
                $sql .= "
                        $label_field DESC,";
            }
            $sql .= "
                        row_count DESC";
            try {
                $res = DB_Helper::getInstance()->getAll($sql);
            } catch (DbException $e) {
                return array();
            }

            if (CRM::hasCustomerIntegration($prj_id)) {
                $crm = CRM::getInstance($prj_id);
                $crm->processListIssuesResult($res);
                if ($group_by == "issue") {
                    usort($res, create_function('$a,$b', 'if ($a["customer_title"] < $b["customer_title"]) {
                        return -1;
                    } elseif ($a["customer_title"] > $b["customer_title"]) {
                        return 1;
                    } else {
                        return 0;
                    }'));
                }
            }
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]['field_value'] = Custom_Field::getDisplayValue($res[$i]['iss_id'], $res[$i]['fld_id']);
            }

            return $res;
        }

        $data = array();
        foreach ($options as $cfo_id => $value) {
            $stmt = "SELECT";
            if ($label_field != '') {
                $stmt .= "
                        $label_field as label,";
            }
            $stmt .= "
                        COUNT(DISTINCT $group_by_field)
                    FROM
                        {{%issue_custom_field}},
                        {{%issue}},
                        {{%issue_user}}
                    WHERE
                        icf_iss_id = iss_id AND
                        isu_iss_id = iss_id AND
                        icf_fld_id = $fld_id AND
                        icf_value = '$cfo_id'";
            if (($start_date != false) && ($end_date != false)) {
                $stmt .= " AND\niss_created_date BETWEEN '" . Misc::escapeString($start_date) . "' AND '" . Misc::escapeString($end_date) . "'";
            }
            if ($assignee != false) {
                $stmt .= " AND\nisu_usr_id = " . Misc::escapeInteger($assignee);
            }
            if ($interval_group_by_field != '') {
                $stmt .= "
                    GROUP BY
                        $interval_group_by_field
                    ORDER BY
                        $label_field ASC";
                try {
                    $res = DB_Helper::getInstance()->getAssoc($stmt);
                } catch (DbException $e) {
                    return array();
                }
            } else {
                try {
                    $res = DB_Helper::getInstance()->getOne($stmt);
                } catch (DbException $e) {
                    return array();
                }
            }
            $data[$value] = $res;
        }

        // include count of all other values (used in pie chart)
        $stmt = "SELECT
                    COUNT(DISTINCT $group_by_field)
                FROM
                    {{%custom_field_option}},
                    {{%issue_custom_field}},
                    {{%issue}}
                WHERE
                    cfo_id = icf_value AND
                    icf_iss_id = iss_id AND
                    icf_fld_id = $fld_id AND
                    cfo_id NOT IN(" . join(",", $cfo_ids) . ")";
        try {
            $res = DB_Helper::getInstance()->getOne($stmt);
        } catch (DbException $e) {
            return array();
        }
        $data["All Others"] = $res;

        return $data;
    }
    /**
     * Returns data for the custom fields weekly report, based on the field and options passed in.
     *
     * @param   integer $fld_id The id of the custom field.
     * @param   array $cfo_ids An array of option ids.
     * @param   string $start_date
     * @param   string $end_date
     * @param   boolean $per_user Show time spent per user
     * @return  array An array of data.
     */
    public static function getCustomFieldWeeklyReport($fld_id, $cfo_ids, $start_date, $end_date, $per_user = false)
    {
        $fld_id = Misc::escapeInteger($fld_id);
        $cfo_ids = Misc::escapeInteger($cfo_ids);
        // get field values
        $options = Custom_Field::getOptions($fld_id, $cfo_ids);

        $sql = "SELECT
                    iss_id,
                    SUM(ttr_time_spent) ttr_time_spent_sum,
                    iss_summary,
                    iss_customer_id,
                    iss_private
               ";

            if ($per_user) {
                $sql .= ', usr_full_name ';
            }
            $sql .= "
                 FROM
                    {{%time_tracking}},";

            if ($per_user) {
                    $sql .= "{{%user}}, ";
            }

            $sql .= "
                        {{%issue}}
                    WHERE
                        iss_prj_id=" . Auth::getCurrentProject() . " AND
                        ttr_created_date BETWEEN '" . Misc::escapeString($start_date) . " 00:00:00' AND '" . Misc::escapeString($end_date) . " 23:59:59' AND
                        ttr_iss_id = iss_id AND
                        ";
            if ($per_user) {
                 $sql .= " usr_id = ttr_usr_id AND ";
            }
            $sql .= "
                        ttr_iss_id = iss_id
                        ";
            if (count($options) > 0) {
            $sql .= " AND (
                SELECT
                    count(*)
                FROM
                    {{%issue_custom_field}} a
                WHERE
                    a.icf_fld_id = $fld_id AND
                    a.icf_value IN('" . join("','", Misc::escapeString(array_keys($options))) . "') AND
                    a.icf_iss_id = ttr_iss_id
                ) > 0";
            }
            if ($per_user) {
                $sql .= "
                    GROUP BY
                    iss_id, ttr_usr_id";
            } else {
                $sql .= "
                    GROUP BY
                    iss_id";
           }

        try {
            $res = DB_Helper::getInstance()->getAll($sql);
        } catch (DbException $e) {
            return array();
        }

        for ($i = 0; $i < count($res); $i++) {
            $res[$i]['field_value'] = Custom_Field::getDisplayValue($res[$i]['iss_id'], $fld_id);
            $res[$i]['ttr_time_spent_sum_formatted'] = Misc::getFormattedTime($res[$i]['ttr_time_spent_sum'], false);
        }

        return $res;
    }
    /**
     * Returns workload information for the specified date range and interval.
     *
     * @param   string $interval The interval to use in this report.
     * @param   string $type If this report is aggregate or individual
     * @param   string $start The start date of this report.
     * @param   string $end The end date of this report.
     * @param   integer $category The category to restrict this report to
     * @return  array An array containing workload data.
     */
    public static function getWorkloadByDateRange($interval, $type, $start, $end, $category)
    {
        $data = array();
        $start = Misc::escapeString($start);
        $end = Misc::escapeString($end);
        $category = Misc::escapeInteger($category);

        // figure out the correct format code
        switch ($interval) {
            case "day":
                $format = '%m/%d/%y';
                $order_by = "%1\$s";
                break;
            case "dow":
                $format = '%W';
                $order_by = "CASE WHEN DATE_FORMAT(%1\$s, '%%w') = 0 THEN 7 ELSE DATE_FORMAT(%1\$s, '%%w') END";
                break;
            case "week":
                if ($type == "aggregate") {
                    $format = '%v';
                } else {
                    $format = '%v/%y';
                }
                $order_by = "%1\$s";
                break;
            case "dom":
                $format = '%d';
                break;
            case "month":
                if ($type == "aggregate") {
                    $format = '%b';
                    $order_by = "DATE_FORMAT(%1\$s, '%%m')";
                } else {
                    $format = '%b/%y';
                    $order_by = "%1\$s";
                }
                break;
        }

        // get issue counts
        $stmt = "SELECT
                    DATE_FORMAT(iss_created_date, '$format'),
                    count(*)
                 FROM
                    {{%issue}}
                 WHERE
                    iss_prj_id=" . Auth::getCurrentProject() . " AND
                    iss_created_date BETWEEN '$start' AND '$end'";
        if (!empty($category)) {
            $stmt .= " AND
                    iss_prc_id = $category";
        }
        $stmt .= "
                 GROUP BY
                    DATE_FORMAT(iss_created_date, '$format')";
        if (!empty($order_by)) {
            $stmt .= "\nORDER BY " . sprintf($order_by, 'iss_created_date');
        }
        try {
            $res = DB_Helper::getInstance()->getAssoc($stmt);
        } catch (DbException $e) {
            return array();
        }
        $data["issues"]["points"] = $res;

        if (count($res) > 0) {
            $stats = new Math_Stats();
            $stats->setData($res);

            $data["issues"]["stats"] = array(
                "total" =>  $stats->sum(),
                "avg"   =>  $stats->mean(),
                "median"    =>  $stats->median(),
                "max"   =>  $stats->max()
            );
        } else {
            $data["issues"]["stats"] = array(
                "total" =>  0,
                "avg"   =>  0,
                "median"    =>  0,
                "max"   =>  0
            );
        }

        // get email counts
        $stmt = "SELECT
                    DATE_FORMAT(sup_date, '$format'),
                    count(*)
                 FROM
                    {{%support_email}},
                    {{%email_account}}";
        if (!empty($category)) {
            $stmt .= ",
                     {{%issue}}";
        }
        $stmt .= "
                 WHERE
                    sup_ema_id=ema_id AND
                    ema_prj_id=" . Auth::getCurrentProject() . " AND
                    sup_date BETWEEN '$start' AND '$end'";
        if (!empty($category)) {
            $stmt .= " AND
                    sup_iss_id = iss_id AND
                    iss_prc_id = $category";
        }
        $stmt .= "
                 GROUP BY
                    DATE_FORMAT(sup_date, '$format')";
        if (!empty($order_by)) {
            $stmt .= "\nORDER BY " . sprintf($order_by, 'sup_date');
        }

        try {
            $res = DB_Helper::getInstance()->getAssoc($stmt);
        } catch (DbException $e) {
            return array();
        }
        $data["emails"]["points"] = $res;

        if (count($res) > 0) {
            $stats = new Math_Stats();
            $stats->setData($res);

            $data["emails"]["stats"] = array(
                "total" =>  $stats->sum(),
                "avg"   =>  $stats->mean(),
                "median"    =>  $stats->median(),
                "max"   =>  $stats->max()
            );
        } else {
            $data["emails"]["stats"] = array(
                "total" =>  0,
                "avg"   =>  0,
                "median"    =>  0,
                "max"   =>  0
            );
        }

        return $data;
    }
}
