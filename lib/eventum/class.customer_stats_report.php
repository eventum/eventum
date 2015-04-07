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
// | 51 Franklin Street, Suite 330                                          |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: Bryan Alsdorf <bryan@mysql.com>                             |
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

/**
 * The Customer Stats report will be too complex to group with the rest of
 * the reports so I am separating it into a separate class.
 */

class Customer_Stats_Report
{
    /**
     * The ID of the project this report is for.
     * @var integer
     */
    public $prj_id;

    /**
     * Support Levels to show
     * @var array
     */
    public $levels;

    /**
     * Customers to display stats for
     * @var array
     */
    public $customers;

    /**
     * Start date of the report
     * @var string
     */
    public $start_date;

    /**
     * End date of the report
     * @var string
     */
    public $end_date;

    /**
     * The current customer restriction
     * @var array
     */
    public $current_customers;

    /**
     * If expired contracts should be excluded.
     * @var boolean
     */
    public $exclude_expired_contracts;

    /**
     * An array listing the union of time tracking categories that have data.
     * @var array
     */
    public $time_tracking_categories = array();

    /**
     * Class Constructor. Accepts the support level, customer,
     * start date and end date to be used in this report. If a customer is
     * specified the support level is ignored. If the date is left off or invalid all dates are included.
     *
     * @param   integer $prj_id The id of the project this report is for.
     * @param   array $levels The support levels that should be shown in this report.
     * @param   array $customers The customers this report should be for.
     * @param   string $start_date The start date of this report.
     * @param   string $end_date The end date of this report.
     */
    public function __construct($prj_id, $levels, $customers, $start_date, $end_date)
    {
        $this->prj_id = $prj_id;
        $this->levels = $levels;
        $this->customers = $customers;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
    }

    /**
     * Returns all data for this report.
     *
     * @return  array
     */
    public function getData()
    {
        $data = array();
        $crm = CRM::getInstance($this->prj_id);

        // determine if this should be customer based or support level based.
        if ($this->isCustomerBased()) {
            // customer based

            // get "all" row of data
            $data[] = $this->getAllRow();

            foreach ($this->customers as $customer_id) {
                $customer = $crm->getCustomer($customer_id);
                $data[] = $this->getDataRow($customer->getName(), array($customer_id));
            }
        } else {
            // support level based
            if (count($this->levels) > 0) {
                $grouped_levels = $crm->getGroupedSupportLevels();
                foreach ($this->levels as $level_name) {
                    if ($level_name == 'Aggregate') {
                        // get "all" row of data
                        $data[] = $this->getAllRow();
                        continue;
                    }

                    $support_options = array();
                    if ($this->exclude_expired_contracts) {
                        $support_options[] = CRM_EXCLUDE_EXPIRED;
                    }
                    $customers = $crm->getCustomerIDsBySupportLevel($grouped_levels[$level_name], $support_options);
                    $data[] = $this->getDataRow($level_name, $customers);
                }
            }
        }

        return $data;
    }

    /**
     * Returns data row for specified name and customers.
     *
     * @param   string  $name Name of data row.
     * @param   string  $customers  Customers to include in this row.
     * @return  array   An array of data.
     */
    public function getDataRow($name, $customers)
    {
        $this->current_customers = $customers;

        return array(
            'title' =>  $name,
            'customer_counts'   =>  $this->getCustomerCounts($name),
            'issue_counts'  =>  $this->getIssueCounts($name),
            'email_counts'  =>  $this->getEmailCounts(),
            'time_tracking' =>  $this->getTimeTracking(),
            'time_stats'    =>  $this->getTimeStats(),
        );
    }

    /**
     * Returns the "all" row, that is the row that always appears at the top of the report
     * and covers all support levels and customers regardless of what is selected.
     *
     * @return  array The array of data for this row.
     */
    private function getAllRow()
    {
        $crm = CRM::getInstance($this->prj_id);
        $row = array(
            'title' =>  ev_gettext('Aggregate'),
        );

        // get complete list of customers.
        $all_levels = array();
        $levels = $crm->getSupportLevelAssocList();
        foreach ($levels as $level_id => $level_name) {
            $all_levels[] = $level_id;
        }
        if ($this->exclude_expired_contracts) {
            $support_option = CRM_EXCLUDE_EXPIRED;
        } else {
            $support_option = array();
        }
        $this->current_customers = $crm->getCustomerIDsBySupportLevel($all_levels, $support_option);

        // get customers
        $row['customer_counts'] = $this->getCustomerCounts('All');

        // get total # of issues, avg issues per customer, median issues per customer
        $row['issue_counts'] = $this->getIssueCounts('All');

        // get actions counts such as # of customer actions per issue, avg customer actions per issue,
        // median customer actions per issue.
        $row['email_counts'] = $this->getEmailCounts();

        // get time tracking information
        $row['time_tracking'] = $this->getTimeTracking();

        // get other time related stats such as avg and median time between issues and avg and median time to close.
        $row['time_stats'] = $this->getTimeStats();

        return $row;
    }

    /**
     * Returns various customer statistics.
     *
     * @param   string $name The name of this data row.
     * @return  array Array of statistics
     */
    private function getCustomerCounts($name)
    {
        $customer_count = count($this->current_customers);

        // split by low/medium/high
        $issue_counts = $this->getIssueCountsByCustomer($name);
        $activity = array(
            'low' => 0,
            'medium' => 0,
            'high' => 0,
        );
        if ((is_array($issue_counts)) && (count($issue_counts) > 0)) {
            foreach ($issue_counts as $count) {
                if ($count <= 2) {
                    $activity['low']++;
                } elseif ($count > 2 && $count <= 8) {
                    $activity['medium']++;
                } elseif ($count > 8) {
                    $activity['high']++;
                }
            }
        }
        if ($customer_count > 0) {
            foreach ($activity as $key => $value) {
                $activity[$key] = ($value * 100) / $customer_count;
            }
            $inactive_count = ((($customer_count - count($issue_counts)) * 100) / $customer_count);
        } else {
            $inactive_count = 0;
        }

        return array(
                'customer_count'    =>  $customer_count,
                'activity'  =>  $activity,
                'active'    =>  count($issue_counts),
                'inactive'  =>  $inactive_count,
        );
    }

    /**
     * Returns the counts relating to number of issues.
     *  - total: total number of issues for the support level.
     *  - avg: Average number of issues opened by customers for support level.
     *  - median: Median number of issues opened by customers for support level.
     *
     * @param   string $name The name of this data row.
     * @return  array Array of counts.
     */
    private function getIssueCounts($name)
    {
        $issue_counts = $this->getIssueCountsByCustomer($name);
        if ((is_array($issue_counts)) && (count($issue_counts) > 0)) {
            $stats = new Math_Stats();
            $stats->setData($issue_counts);

            return array(
                'total' =>  $stats->sum(),
                'avg'   =>  $stats->mean(),
                'median'    =>  $stats->median(),
                'max'   =>  $stats->max(),
            );
        } else {
            return array(
                'total' =>  0,
                'avg'   =>  0,
                'median'    =>  0,
                'max'   =>  0,
            );
        }
    }

    /**
     * Returns an array of issue counts for customers.
     *
     * @param   string $name The name of this data row.
     * @return string
     */
    private function getIssueCountsByCustomer($name)
    {
        static $issue_counts;

        // poor man's caching system...
        if (!empty($issue_counts[$name])) {
            return $issue_counts[$name];
        }

        $stmt = 'SELECT
                    count(*)
                 FROM
                    {{%issue}}
                 WHERE
                    ' . $this->getWhereClause('iss_customer_id', 'iss_created_date') . '
                 GROUP BY
                    iss_customer_id';
        try {
            $res = DB_Helper::getInstance()->getColumn($stmt);
        } catch (DbException $e) {
            return '';
        }
        $issue_counts[$name] = $res;

        return $res;
    }

    /**
     * Returns the counts relating to # of customer and developer emails.
     *
     * @return  array Array of counts.
     */
    public function getEmailCounts()
    {
        $counts = array(
            'customer'  =>  array(),
            'developer' =>  array(),
        );
        $stmt = 'SELECT
                    count(*)
                 FROM
                    {{%support_email}},
                    {{%email_account}},
                    {{%issue}},
                    {{%project_user}}
                 WHERE
                    sup_ema_id = ema_id AND
                    sup_iss_id = iss_id AND
                    sup_usr_id = pru_usr_id AND
                    ema_prj_id = pru_prj_id AND
                    pru_role = ? AND
                    ' . $this->getWhereClause('iss_customer_id', 'sup_date') . '
                 GROUP BY
                    sup_iss_id';
        $params = array(User::getRoleID('Customer'));
        try {
            $res = DB_Helper::getInstance()->getColumn($stmt, $params);
        } catch (DbException $e) {
            return array();
        }

        if (count($res) > 0) {
            $stats = new Math_Stats();
            $stats->setData($res);

            $counts['customer']['total'] = $stats->sum();
            $counts['customer']['avg'] = $stats->mean();
            $counts['customer']['median'] = $stats->median();
        } else {
            $counts['customer']['total'] = 0;
            $counts['customer']['avg'] = 0;
            $counts['customer']['median'] = 0;
        }

        $stmt = 'SELECT
                    count(*)
                 FROM
                    {{%support_email}},
                    {{%email_account}},
                    {{%issue}},
                    {{%project_user}}
                 WHERE
                    sup_ema_id = ema_id AND
                    sup_iss_id = iss_id AND
                    sup_usr_id = pru_usr_id AND
                    ema_prj_id = pru_prj_id AND
                    pru_role != ? AND
                    ' . $this->getWhereClause('iss_customer_id', 'sup_date') . '
                 GROUP BY
                    sup_iss_id';
        $params1 = array(User::getRoleID('Customer'));
        try {
            $res = DB_Helper::getInstance()->getColumn($stmt, $params1);
        } catch (DbException $e) {
            return array();
        }

        if (count($res) > 0) {
            $stats = new Math_Stats();
            $stats->setData($res);

            $counts['developer']['total'] = $stats->sum();
            $counts['developer']['avg'] = $stats->mean();
            $counts['developer']['median'] = $stats->median();
        } else {
            $counts['developer']['total'] = 0;
            $counts['developer']['avg'] = 0;
            $counts['developer']['median'] = 0;
        }

        return $counts;
    }

    /**
     * Returns information from time tracking module, split by category
     *
     * @return  array Array of counts.
     */
    private function getTimeTracking()
    {
        $time = array();

        // get total stats
        $time[0] = $this->getIndividualTimeTracking();
        $time[0]['name'] = 'Total';
        $this->time_tracking_categories[0] = 'Total';

        // get categories
        $categories = Time_Tracking::getAssocCategories($this->prj_id);
        foreach ($categories as $ttc_id => $category) {
            $individual = $this->getIndividualTimeTracking($ttc_id);
            if (count($individual) > 0) {
                $time[$ttc_id] = $individual;
                $time[$ttc_id]['name'] = $category;

                $this->time_tracking_categories[$ttc_id] = $category;
            }
        }

        return $time;
    }

    /**
     * Returns time tracking information for a certain category, or all categories if no category is passed.
     *
     * @param   int $ttc_id The id of the time tracking category. Default false
     * @return  array Array of time tracking information
     */
    public function getIndividualTimeTracking($ttc_id = false)
    {
        $stmt = 'SELECT
                    ttr_time_spent
                 FROM
                    {{%time_tracking}},
                    {{%issue}}
                 WHERE
                    ttr_iss_id = iss_id';
        $params = array();
        if ($ttc_id != false) {
            $stmt .= "\n AND ttr_ttc_id = ?";
            $params[] = $ttc_id;
        }
        $stmt .= "\nAND " . $this->getWhereClause('iss_customer_id', 'ttr_created_date');
        try {
            $res = DB_Helper::getInstance()->getColumn($stmt, $params);
        } catch (DbException $e) {
            return array();
        }

        if (count($res) > 0) {
            $stats = new Math_Stats();
            $stats->setData($res);
            $total = $stats->sum();
            $avg = $stats->mean();
            $median = $stats->median();

            return array(
                'total' =>  $total,
                'total_formatted'   =>  Misc::getFormattedTime($total, true),
                'avg'   =>  $avg,
                'avg_formatted' =>  Misc::getFormattedTime($avg),
                'median' =>  $median,
                'median_formatted'  =>  Misc::getFormattedTime($median),
            );
        } else {
            return array();
        }
    }

    /**
     * Returns information about time to close and time to first response.
     *
     * @return  array Array of counts.
     */
    private function getTimeStats()
    {
        // time to close
        $stmt = 'SELECT
                    round(((unix_timestamp(iss_closed_date) - unix_timestamp(iss_created_date)) / 60))
                 FROM
                    {{%issue}}
                 WHERE
                    iss_closed_date IS NOT NULL AND
                    ' . $this->getWhereClause('iss_customer_id', array('iss_created_date', 'iss_closed_date'));
        try {
            $res = DB_Helper::getInstance()->getColumn($stmt);
        } catch (DbException $e) {
            return array();
        }

        if (count($res) > 0) {
            $stats = new Math_Stats();
            $stats->setData($res);

            $time_to_close = array(
                'avg'   =>  $stats->mean(),
                'avg_formatted' =>  Misc::getFormattedTime($stats->mean()),
                'median' =>  $stats->median(),
                'median_formatted'  =>  Misc::getFormattedTime($stats->median()),
                'max'   =>  $stats->max(),
                'max_formatted' =>  Misc::getFormattedTime($stats->max()),
                'min'   =>  $stats->min(),
                'min_formatted' =>  Misc::getFormattedTime($stats->min()),
            );
        } else {
            $time_to_close = array(
                'avg'   =>  0,
                'avg_formatted' =>  Misc::getFormattedTime(0),
                'median' =>  0,
                'median_formatted'  =>  Misc::getFormattedTime(0),
                'max'   =>  0,
                'max_formatted' =>  Misc::getFormattedTime(0),
                'min'   =>  0,
                'min_formatted' =>  Misc::getFormattedTime(0),
            );
        }

        // time to first response
        $stmt = 'SELECT
                    round(((unix_timestamp(iss_first_response_date) - unix_timestamp(iss_created_date)) / 60))
                 FROM
                    {{%issue}}
                 WHERE
                    iss_first_response_date IS NOT NULL AND
                    ' . $this->getWhereClause('iss_customer_id', array('iss_created_date', 'iss_closed_date'));
        try {
            $res = DB_Helper::getInstance()->getColumn($stmt);
        } catch (DbException $e) {
            return array();
        }

        if (count($res) > 0) {
            $stats = new Math_Stats();
            $stats->setData($res);

            $time_to_first_response = array(
                'avg'   =>  $stats->mean(),
                'avg_formatted' =>  Misc::getFormattedTime($stats->mean()),
                'median' =>  $stats->median(),
                'median_formatted'  =>  Misc::getFormattedTime($stats->median()),
                'max'   =>  $stats->max(),
                'max_formatted' =>  Misc::getFormattedTime($stats->max()),
                'min'   =>  $stats->min(),
                'min_formatted' =>  Misc::getFormattedTime($stats->min()),
            );
        } else {
            $time_to_first_response = array(
                'avg'   =>  0,
                'avg_formatted' =>  Misc::getFormattedTime(0),
                'median' =>  0,
                'median_formatted'  =>  Misc::getFormattedTime(0),
                'max'   =>  0,
                'max_formatted' =>  Misc::getFormattedTime(0),
                'min'   =>  0,
                'min_formatted' =>  Misc::getFormattedTime(0),
            );
        }

        return array(
            'time_to_close' => $time_to_close,
            'time_to_first_response'    =>  $time_to_first_response,
        );
    }

    /**
     * Returns if this report is customer based
     *
     * @return  boolean
     */
    public function isCustomerBased()
    {
        return ((is_array($this->customers)) && (count($this->customers) > 0) && (!in_array('', $this->customers)));
    }

    /**
     * Sets if expired contracts should be exclude
     *
     * @param   boolean $split If expired contracts should be excluded
     */
    public function excludeExpired($exclude)
    {
        $this->exclude_expired_contracts = $exclude;
    }

    /**
     * Returns where clause based on what the current support level/customer is set to, and date range currently set.
     * If $date_field is an array, the fields will be ORed together.
     *
     * @param   string $customer_field The name of customer_id field
     * @param   mixed $date_field The name of the date field
     * @return  string A string with the SQL limiting the resultset
     */
    public function getWhereClause($customer_field, $date_field)
    {
        $where = '';
        if (!empty($customer_field)) {
            if (count($this->current_customers) > 0) {
                $where .= $customer_field . ' IN(' . implode(',', $this->current_customers) . ')';
            } else {
                // XXX: this is a dirty hack to handle support levels that don't have customers, but I can't think of anything better right now.
                $where .= '1 = 2';
            }
        }

        if ((!empty($this->start_date)) && (!empty($this->end_date))) {
            if (!empty($customer_field)) {
                $where .= " AND\n";
            }
            if (is_array($date_field)) {
                $date_conditions = array();
                foreach ($date_field as $field) {
                    $date_conditions[] = "($field BETWEEN '" . $this->start_date . "' AND '" . $this->end_date . "')";
                }
                $where .= '(' . implode(' OR ', $date_conditions) . ')';
            } else {
                $where .= "($date_field BETWEEN '" . $this->start_date . "' AND '" . $this->end_date . "')";
            }
        }

        return $where;
    }

    /**
     * Returns the text for the row label. Will be "Support Level" if viewing support levels and "Customer" if viewing a specific customer.
     *
     * @return  string The text for the row label.
     */
    public function getRowLabel()
    {
        if ($this->isCustomerBased()) {
            return ev_gettext('Customer');
        } else {
            return ev_gettext('Support Level');
        }
    }

    /**
     * Returns an array of graph types
     *
     * @return  array An array of graph types
     */
    public static function getGraphTypes()
    {
        return array(
            1   =>  array(
                        'title' =>  ev_gettext('Total Workload by Support Level'),
                        'desc'  =>  ev_gettext('Includes issue count, Developer email Count, Customer Email Count, Customers count by Support Level'),
                        'size'  => array(
                                        'x' =>  800,
                                        'y' =>  350,
                        ),
            ),
            2   =>  array(
                        'title' =>  ev_gettext('Avg Workload per Customer by Support Level'),
                        'desc'  =>  ev_gettext('Displays average number of issues, developer emails and customer emails per issue by support level'),
                        'size'  =>  array(
                                        'x' =>  800,
                                        'y' =>  350,
                        ),
                        'value_format'  =>  '%.1f',
            ),
            3   =>  array(
                        'title' =>  ev_gettext('Avg and Median Time to Close by Support Level'),
                        'desc'  =>  ev_gettext('Displays time stats'),
                        'size'  =>  array(
                                        'x' =>  600,
                                        'y' =>  350,
                        ),
                        'y_label'   =>  ev_gettext('Days'),
            ),
            4   =>  array(
                        'title' =>  ev_gettext('Avg and Median Time to First Response by Support Level'),
                        'desc'  =>  ev_gettext('Displays time stats'),
                        'size'  =>  array(
                                        'x' =>  600,
                                        'y' =>  350,
                        ),
                        'y_label'   =>  ev_gettext('Hours'),
            ),
        );
    }

    /**
     * Returns the list of sections that can be displayed.
     *
     * @return  array An array of sections.
     */
    public static function getDisplaySections()
    {
        return array(
            'customer_counts'   =>  ev_gettext('Customer Counts'),
            'issue_counts'  =>  ev_gettext('Issue Counts'),
            'email_counts'  =>  ev_gettext('Email Counts'),
            'time_stats'    =>  ev_gettext('Time Statistics'),
            'time_tracking' =>  ev_gettext('Time Tracking'),
        );
    }

    /**
     * Returns the list of time tracking categories that have data.
     *
     * @return  array An array of time tracking categories
     */
    public function getTimeTrackingCategories()
    {
        return $this->time_tracking_categories;
    }
}
