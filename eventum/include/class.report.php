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
// @(#) $Id: s.class.report.php 1.10 04/01/26 20:37:04-06:00 joao@kickass. $
//

include_once(APP_INC_PATH . "class.error_handler.php");
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.user.php");
include_once(APP_INC_PATH . "class.date.php");
include_once(APP_INC_PATH . "class.status.php");
include_once(APP_INC_PATH . "class.history.php");
include_once(APP_INC_PATH . "class.phone_support.php");
include_once(APP_INC_PATH . "class.prefs.php");
include_once(APP_PEAR_PATH . "Math/Stats.php");

/**
 * Class to handle the business logic related to all aspects of the
 * reporting system.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

class Report
{


    /**
     * Method used to get all open issues and group them by user.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  array The list of issues
     */
    function getStalledIssuesByUser($prj_id, $users, $status, $before_date, $after_date, $sort_order)
    {
        $prj_id = Misc::escapeInteger($prj_id);
        $ts = Date_API::getCurrentUnixTimestampGMT();
        $before_ts = strtotime($before_date);
        $after_ts = strtotime($after_date);

        // split groups out of users array
        $groups = array();
        foreach ($users as $key => $value) {
            if (substr($value, 0, 3) == 'grp') {
                $groups[] = substr($value, 4);
                unset($users[$key]);
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
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                    )
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 ON
                    iss_sta_id=sta_id
                 WHERE
                    sta_is_closed=0 AND
                    iss_prj_id=$prj_id AND
                    iss_id=isu_iss_id AND
                    isu_usr_id=usr_id AND
                    UNIX_TIMESTAMP(iss_last_response_date) < $before_ts AND
                    UNIX_TIMESTAMP(iss_last_response_date) > $after_ts";
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
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            Time_Tracking::getTimeSpentByIssues($res);
            $issues = array();
            for ($i = 0; $i < count($res); $i++) {
                if (empty($res[$i]['iss_updated_date'])) {
                    $res[$i]['iss_updated_date'] = $res[$i]['iss_created_date'];
                }
                if (empty($res[$i]['iss_last_response_date'])) {
                    $res[$i]['iss_last_response_date'] = $res[$i]['iss_created_date'];
                }
                $issues[$res[$i]['usr_full_name']][$res[$i]['iss_id']] = array(
                    'iss_summary'         => $res[$i]['iss_summary'],
                    'sta_title'           => $res[$i]['sta_title'],
                    'iss_created_date'    => Date_API::getFormattedDate($res[$i]['iss_created_date']),
                    'iss_last_response_date'    => Date_API::getFormattedDate($res[$i]['iss_last_response_date']),
                    'time_spent'          => Misc::getFormattedTime($res[$i]['time_spent']),
                    'status_color'        => $res[$i]['sta_color'],
                    'last_update'         => Date_API::getFormattedDateDiff($ts, Date_API::getUnixTimestamp($res[$i]['iss_updated_date'], Date_API::getDefaultTimezone())),
                    'last_email_response' => Date_API::getFormattedDateDiff($ts, Date_API::getUnixTimestamp($res[$i]['iss_last_response_date'], Date_API::getDefaultTimezone()))
                );
            }
            return $issues;
        }
    }

    /**
     * Method used to get all open issues and group them by assignee or reporter.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $cutoff_days The number of days to use as a cutoff period
     * @return  array The list of issues
     */
    function getOpenIssuesByUser($prj_id, $cutoff_days, $group_by_reporter = false)
    {
        $prj_id = Misc::escapeInteger($prj_id);
        $cutoff_days = Misc::escapeInteger($cutoff_days);
        $ts = Date_API::getCurrentUnixTimestampGMT();
        $ts_diff = $cutoff_days * DAY;


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
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user as assignee,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user as reporter
                    )
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 ON
                    iss_sta_id=sta_id
                 WHERE
                    sta_is_closed=0 AND
                    iss_prj_id=$prj_id AND
                    iss_id=isu_iss_id AND
                    isu_usr_id=assignee.usr_id AND
                    iss_usr_id=reporter.usr_id AND
                    UNIX_TIMESTAMP(iss_created_date) < (UNIX_TIMESTAMP() - $ts_diff)
                 ORDER BY\n";
        if ($group_by_reporter) {
            $stmt .= "reporter.usr_full_name";
        } else {
            $stmt .= "assignee.usr_full_name";
        }
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            print_r($res);
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
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
                $issues[$name][$res[$i]['iss_id']] = array(
                    'iss_summary'         => $res[$i]['iss_summary'],
                    'sta_title'           => $res[$i]['sta_title'],
                    'iss_created_date'    => Date_API::getFormattedDate($res[$i]['iss_created_date']),
                    'time_spent'          => Misc::getFormattedTime($res[$i]['time_spent']),
                    'status_color'        => $res[$i]['sta_color'],
                    'last_update'         => Date_API::getFormattedDateDiff($ts, Date_API::getUnixTimestamp($res[$i]['iss_updated_date'], Date_API::getDefaultTimezone())),
                    'last_email_response' => Date_API::getFormattedDateDiff($ts, Date_API::getUnixTimestamp($res[$i]['iss_last_response_date'], Date_API::getDefaultTimezone()))
                );
            }
            return $issues;
        }
    }


    /**
     * Method used to get the list of issues in a project, and group
     * them by the assignee.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  array The list of issues
     */
    function getIssuesByUser($prj_id)
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
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                    )
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 ON
                    iss_sta_id=sta_id
                 WHERE
                    iss_prj_id=" . Misc::escapeInteger($prj_id) . " AND
                    iss_id=isu_iss_id AND
                    isu_usr_id=usr_id
                 ORDER BY
                    usr_full_name";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            Time_Tracking::getTimeSpentByIssues($res);
            $issues = array();
            for ($i = 0; $i < count($res); $i++) {
                $issues[$res[$i]['usr_full_name']][$res[$i]['iss_id']] = array(
                    'iss_summary'      => $res[$i]['iss_summary'],
                    'sta_title'        => $res[$i]['sta_title'],
                    'iss_created_date' => Date_API::getFormattedDate($res[$i]['iss_created_date']),
                    'time_spent'       => Misc::getFormattedTime($res[$i]['time_spent']),
                    'status_color'     => $res[$i]['sta_color']
                );
            }
            return $issues;
        }
    }


    /**
     * Returns the data used by the weekly report.
     *
     * @access  public
     * @param   string $usr_id The ID of the user this report is for.
     * @param   string The start date of this report.
     * @param   string The end date of this report.
     * @param   boolean If closed issues should be separated from other issues.
     * @return  array An array of data containing all the elements of the weekly report.
     */
    function getWeeklyReport($usr_id, $start, $end, $separate_closed = false)
    {
        $usr_id = Misc::escapeInteger($usr_id);

        // figure out timezone
        $user_prefs = Prefs::get($usr_id);
        $tz = @$user_prefs["timezone"];

        $start_dt = new Date();
        $end_dt = new Date();
        // set timezone to that of user.
        $start_dt->setTZById($tz);
        $end_dt->setTZById($tz);

        // set the dates in the users time zone
        $start_dt->setDate($start . " 00:00:00");
        $end_dt->setDate($end . " 23:59:59");

        // convert time to GMT
        $start_dt->toUTC();
        $end_dt->toUTC();

        $start_ts = $start_dt->getDate();
        $end_ts = $end_dt->getDate();

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
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 WHERE
                    iss_id = isu_iss_id AND
                    iss_sta_id = sta_id AND
                    isu_usr_id = $usr_id AND
                    isu_assigned_date BETWEEN '$start_ts' AND '$end_ts'";
        $newly_assigned = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($newly_assigned)) {
            Error_Handler::logError(array($newly_assigned->getMessage(), $newly_assigned->getDebugInfo()), __FILE__, __LINE__);
        }

        $email_count = array(
            "associated"    =>  Support::getSentEmailCountByUser($usr_id, $start_ts, $end_ts, true),
            "other"         =>  Support::getSentEmailCountByUser($usr_id, $start_ts, $end_ts, false)
        );

        $data = array(
            "start"     => str_replace('-', '.', $start),
            "end"       => str_replace('-', '.', $end),
            "user"      => User::getDetails($usr_id),
            "group_name"=> Group::getName(User::getGroupID($usr_id)),
            "issues"    => History::getTouchedIssuesByUser($usr_id, $start_ts, $end_ts, $separate_closed),
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
     * @access  public
     * @param   string $timezone Timezone to display time in in addition to GMT
     * @param   boolean $graph If the data should be formatted for use in a graph. Default false
     * @return  array An array of data.
     */
    function getWorkloadByTimePeriod($timezone, $graph = false)
    {
        $stmt = "SELECT
                    count(*) as events,
                    hour(his_created_date) AS time_period,
                    if (pru_role > 3, 'developer', 'customer') as performer,
                    SUM(if (pru_role > 3, 1, 0)) as dev_events,
                    SUM(if (pru_role > 3, 0, 1)) as cust_events
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_history,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_user
                 WHERE
                    his_usr_id = usr_id AND
                    usr_id = pru_usr_id AND
                    pru_prj_id = " . Auth::getCurrentProject() . "
                 GROUP BY
                    time_period, performer
                 ORDER BY
                    time_period";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
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

            // convert to the users time zone
            $dt = new Date(mktime($i,0,0));
            $gmt_time = $dt->format('%H:%M');
            $dt->convertTZbyID($timezone);
            if ($graph) {
                $data["developer"][$dt->format('%H')] = "";
                $data["customer"][$dt->format('%H')] = "";
            } else {
                $data[$i]["display_time_gmt"] = $gmt_time;
                $data[$i]["display_time_user"] = $dt->format('%H:%M');
            }

            // loop through results, assigning appropriate results to data array
            foreach ($res as $index => $row) {
                if ($row["time_period"] == $i) {
                    $sort_values[$row["performer"]][$i] = $row["events"];

                    if ($graph) {
                        $data[$row["performer"]][$dt->format('%H')] = (($row["events"] / $event_count[$row["performer"]]) * 100);
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
     * Returns data on when support emails are sent/recieved.
     *
     * @access  public
     * @param   string $timezone Timezone to display time in in addition to GMT
     * @param   boolean $graph If the data should be formatted for use in a graph. Default false
     * @return  array An array of data.
     */
    function getEmailWorkloadByTimePeriod($timezone, $graph = false)
    {
        // get total counts
        $stmt = "SELECT
                    hour(sup_date) AS time_period,
                    count(*) as events
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                 GROUP BY
                    time_period";
        $total = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($total)) {
            Error_Handler::logError(array($total->getMessage(), $total->getDebugInfo()), __FILE__, __LINE__);
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
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                 WHERE
                    sup_from IN('" . join("','", $emails) . "')
                 GROUP BY
                    time_period";
        $dev_stats = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($dev_stats)) {
            Error_Handler::logError(array($dev_stats->getMessage(), $dev_stats->getDebugInfo()), __FILE__, __LINE__);
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
            $dt = new Date(mktime($i,0,0));
            $gmt_time = $dt->format('%H:%M');
            $dt->convertTZbyID($timezone);
            if ($graph) {
                $data["developer"][$dt->format('%H')] = "";
                $data["customer"][$dt->format('%H')] = "";
            } else {
                $data[$i]["display_time_gmt"] = $gmt_time;
                $data[$i]["display_time_user"] = $dt->format('%H:%M');
            }

            // use later to find highest value
            $sort_values["developer"][$i] = $dev_stats[$i];
            $sort_values["customer"][$i] = $cust_stats[$i];

            if ($graph) {
                if ($dev_count == 0) {
                    $data["developer"][$dt->format('%H')] = 0;
                } else {
                    $data["developer"][$dt->format('%H')] = (($dev_stats[$i] / $dev_count) * 100);
                }
                if ($cust_count == 0) {
                    $data["customer"][$dt->format('%H')] = 0;
                } else {
                    $data["customer"][$dt->format('%H')] = (($cust_stats[$i] / $cust_count) * 100);
                }
            } else {
                $data[$i]["developer"]["count"] = $dev_stats[$i];
                if ($dev_count == 0){
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
     * @access  public
     * @param   integer $fld_id The id of the custom field.
     * @param   array $cfo_ids An array of option ids.
     * @param   string $group_by How the data should be grouped.
     * @param   boolean $list If the values should be listed out instead of just counted.
     * @return  array An array of data.
     */
    function getCustomFieldReport($fld_id, $cfo_ids, $group_by = "issue", $list = false)
    {
        $prj_id = Auth::getCurrentProject();
        $fld_id = Misc::escapeInteger($fld_id);
        $cfo_ids = array_map(array('Misc', 'escapeString'), $cfo_ids);

        $backend = Custom_Field::getBackend($fld_id);

        if (is_object($backend)) {
            $options = array();
            foreach ($cfo_ids as $cfo_id) {
                $options[$cfo_id] = Custom_Field::getOptionValue($fld_id, $cfo_id);
            }
            $in_field = 'icf_value';
        } else {
            // get field values
            $stmt = "SELECT
                        cfo_id,
                        cfo_value
                     FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field_option
                     WHERE
                        cfo_fld_id = $fld_id AND
                        cfo_id IN('" . join("','", $cfo_ids) . "')
                     ORDER BY
                        cfo_id";
            $options = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
            if (PEAR::isError($options)) {
                Error_Handler::logError(array($options->getMessage(), $options->getDebugInfo()), __FILE__, __LINE__);
                return array();
            }
            $in_field = 'cfo_id';
        }

        if ($group_by == "customer") {
            $group_by_field = "iss_customer_id";
        } else {
            $group_by_field = "iss_id";
        }

        if ($list == true) {
            $sql = "SELECT
                        DISTINCT($group_by_field),
                        iss_id,
                        iss_summary,
                        iss_customer_id,
                        count(DISTINCT(iss_id)) as row_count
                    FROM\n";
            if (!is_object($backend)) {
                $sql .= APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field_option,\n";
            }
            $sql .= APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_custom_field,
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                    WHERE
                        iss_prj_id = $prj_id AND\n";
            if (!is_object($backend)) {
                $sql .= "cfo_id = icf_value AND";
            }
            $sql .= "\nicf_iss_id = iss_id AND
                        icf_fld_id = $fld_id AND
                        $in_field IN('" . join("','", array_keys($options)) . "')
                    GROUP BY
                        $group_by_field
                    ORDER BY
                        row_count DESC";
            $res = $GLOBALS["db_api"]->dbh->getAll($sql, DB_FETCHMODE_ASSOC);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return array();
            }
            if (Customer::hasCustomerIntegration($prj_id)) {
                Customer::getCustomerTitlesByIssues($prj_id, $res);
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
            return $res;
        }

        $data = array();
        foreach ($options as $cfo_id => $value) {
            $stmt = "SELECT
                        COUNT(DISTINCT $group_by_field)
                    FROM\n";
            if (!is_object($backend)) {
                $stmt .= APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field_option,\n";
            }
            $stmt .= APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_custom_field,
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                    WHERE
                        iss_prj_id = $prj_id AND\n";
            if (!is_object($backend)) {
                $stmt .= "cfo_id = icf_value AND";
            }
            $stmt .= "\nicf_iss_id = iss_id AND
                        icf_fld_id = $fld_id AND
                        $in_field = '" . Misc::escapeString($cfo_id) . "'";
            $count = $GLOBALS["db_api"]->dbh->getOne($stmt);
            if (PEAR::isError($count)) {
                Error_Handler::logError(array($count->getMessage(), $count->getDebugInfo()), __FILE__, __LINE__);
                return array();
            }
            $data[$value] = $count;
        }


        // include count of all other values (used in pie chart)
        $stmt = "SELECT
                    COUNT(DISTINCT $group_by_field)
                FROM\n";
        if (!is_object($backend)) {
            $stmt .= APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field_option,\n";
        }
        $stmt .= APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_custom_field,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                WHERE\n";
        if (!is_object($backend)) {
            $stmt .= "cfo_id = icf_value AND";
        }
        $stmt .= "\nicf_iss_id = iss_id AND
                    icf_fld_id = $fld_id AND
                    $in_field NOT IN('" . join("','", $cfo_ids) . "')";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        }
        $data["All Others"] = $res;

        return $data;
    }


    /**
     * Returns workload information for the specified date range and interval.
     *
     * @access  public
     * @param   string $interval The interval to use in this report.
     * @param   string $type If this report is aggregate or individual
     * @param   string $start The start date of this report.
     * @param   string $end The end date of this report.
     * @return  array An array containing workload data.
     */
    function getWorkloadByDateRange($interval, $type, $start, $end)
    {
        $data = array();
        $start = Misc::escapeString($start);
        $end = Misc::escapeString($end);

        // figure out the correct format code
        switch ($interval) {
            case "day":
                $format = '%m/%d/%y';
                $order_by = "%1\$s";
                break;
            case "dow":
                $format = '%W';
                $order_by = "IF(DATE_FORMAT(%1\$s, '%%w') = 0, 7, DATE_FORMAT(%1\$s, '%%w'))";
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
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 WHERE
                    iss_prj_id=" . Auth::getCurrentProject() . " AND
                    iss_created_date BETWEEN '$start' AND '$end'
                 GROUP BY
                    DATE_FORMAT(iss_created_date, '$format')";
        if (!empty($order_by)) {
            $stmt .= "\nORDER BY " . sprintf($order_by, 'iss_created_date');
        }
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
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
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account
                 WHERE
                    sup_ema_id=ema_id AND
                    ema_prj_id=" . Auth::getCurrentProject() . " AND
                    sup_date BETWEEN '$start' AND '$end'
                 GROUP BY
                    DATE_FORMAT(sup_date, '$format')";
        if (!empty($order_by)) {
            $stmt .= "\nORDER BY " . sprintf($order_by, 'sup_date');
        }
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
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

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Report Class');
}
?>