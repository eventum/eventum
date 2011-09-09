<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005, 2006 MySQL AB                        |
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
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

/**
 * Holding all search relevant methods
 *
 * @author Elan Ruusamäe <glen@delfi.ee>
 */
class Search
{
    /**
     * Method used to get a specific parameter in the issue listing cookie.
     *
     * @param   string $name The name of the parameter
     * @return  mixed The value of the specified parameter
     */
    public static function getParam($name)
    {
        if (isset($_GET[$name])) {
            return $_GET[$name];
        } elseif (isset($_POST[$name])) {
            return $_POST[$name];
        }

        $profile = Search_Profile::getProfile(Auth::getUserID(), Auth::getCurrentProject(), 'issue');
        if (isset($profile[$name])) {
            return $profile[$name];
        } else {
            return "";
        }
    }

    /**
     * Method used to save the current search parameters in a cookie.
     *
     * @return  array The search parameters
     */
    public static function saveSearchParams()
    {
        $sort_by = self::getParam('sort_by');
        $sort_order = self::getParam('sort_order');
        $rows = self::getParam('rows');
        $hide_closed = self::getParam('hide_closed');
        if ($hide_closed === '') {
            $hide_closed = 1;
        }
        $search_type = self::getParam('search_type');
        if (empty($search_type)) {
            $search_type = 'all_text';
        }
        $custom_field = self::getParam('custom_field');
        if (is_string($custom_field)) {
            $custom_field = unserialize(urldecode($custom_field));
        }
        $cookie = array(
            'rows'           => Misc::escapeString($rows ? $rows : APP_DEFAULT_PAGER_SIZE),
            'pagerRow'       => Misc::escapeInteger(self::getParam('pagerRow')),
            'hide_closed'    => $hide_closed,
            "sort_by"        => Misc::stripHTML($sort_by ? $sort_by : "pri_rank"),
            "sort_order"     => Misc::stripHTML($sort_order ? $sort_order : "ASC"),
            "customer_id"    => Misc::escapeInteger(self::getParam('customer_id')),
            // quick filter form
            'keywords'       => self::getParam('keywords'),
            'search_type'    => Misc::stripHTML($search_type),
            'users'          => Misc::escapeInteger(self::getParam('users')),
            'status'         => Misc::escapeInteger(self::getParam('status')),
            'priority'       => Misc::escapeInteger(self::getParam('priority')),
            'category'       => Misc::escapeInteger(self::getParam('category')),
            'customer_email' => Misc::stripHTML(self::getParam('customer_email')),
            // advanced search form
            'show_authorized_issues'        => Misc::escapeInteger(self::getParam('show_authorized_issues')),
            'show_notification_list_issues' => Misc::escapeInteger(self::getParam('show_notification_list_issues')),
            'reporter'       => Misc::escapeInteger(self::getParam('reporter')),
            // other fields
            'release'        => Misc::escapeInteger(self::getParam('release')),
            // custom fields
            'custom_field'   => Misc::stripHTML($custom_field)
        );
        // now do some magic to properly format the date fields
        $date_fields = array(
            'created_date',
            'updated_date',
            'last_response_date',
            'first_response_date',
            'closed_date'
        );
        foreach ($date_fields as $field_name) {
            $field = Misc::stripHTML(self::getParam($field_name));
            if (empty($field)) {
                continue;
            }
            if (@$field['filter_type'] == 'in_past') {
                @$cookie[$field_name] = array(
                    'filter_type'   =>  'in_past',
                    'time_period'   =>  $field['time_period']
                );
            } else {
                $end_field_name = $field_name . '_end';
                $end_field = Misc::stripHTML(self::getParam($end_field_name));
                @$cookie[$field_name] = array(
                    'past_hour'   => $field['past_hour'],
                    'Year'        => $field['Year'],
                    'Month'       => $field['Month'],
                    'Day'         => $field['Day'],
                    'start'       => $field['Year'] . '-' . $field['Month'] . '-' . $field['Day'],
                    'filter_type' => $field['filter_type'],
                    'end'         => $end_field['Year'] . '-' . $end_field['Month'] . '-' . $end_field['Day']
                );
                @$cookie[$end_field_name] = array(
                    'Year'        => $end_field['Year'],
                    'Month'       => $end_field['Month'],
                    'Day'         => $end_field['Day']
                );
            }
        }
        Search_Profile::save(Auth::getUserID(), Auth::getCurrentProject(), 'issue', $cookie);
        return $cookie;
    }


    /**
     * Method used to get the current sorting options used in the grid layout
     * of the issue listing page.
     *
     * @param   array $options The current search parameters
     * @return  array The sorting options
     */
    public static function getSortingInfo($options)
    {
        $custom_fields = Custom_Field::getFieldsToBeListed(Auth::getCurrentProject());

        // default order for last action date, priority should be descending
        // for textual fields, like summary, ascending is reasonable
        $fields = array(
            "pri_rank" => "desc",
            "iss_id" => "desc",
            "iss_customer_id" => "desc",
            "prc_title" => "asc",
            "sta_rank" => "asc",
            "iss_created_date" => "desc",
            "iss_summary" => "asc",
            "last_action_date" => "desc",
            "usr_full_name" => "asc",
            "iss_expected_resolution_date" => "desc",
            "pre_title" => "asc",
            "assigned" => "asc",
        );

        foreach ($custom_fields as $fld_id => $fld_name) {
            $fields['custom_field_' . $fld_id] = "desc";
        }

        $sortfields = array_combine(array_keys($fields), array_keys($fields));
        $sortfields["pre_title"] = "pre_scheduled_date";
        $sortfields["assigned"] = "isu_usr_id";

        $items = array(
            "links"  => array(),
            "images" => array()
        );
        foreach ($sortfields as $field => $sortfield) {
            $sort_order = $fields[$field];
            if ($options["sort_by"] == $sortfield) {
                $items["images"][$field] = "images/" . strtolower($options["sort_order"]) . ".gif";
                if (strtolower($options["sort_order"]) == "asc") {
                    $sort_order = "desc";
                } else {
                    $sort_order = "asc";
                }
            }
            $items["links"][$field] = $_SERVER["PHP_SELF"] . "?sort_by=" . $sortfield . "&sort_order=" . $sort_order;
        }
        return $items;
    }

    /**
     * Method used to get the list of issues to be displayed in the grid layout.
     *
     * @param   integer $prj_id The current project ID
     * @param   array $options The search parameters
     * @param   integer $current_row The current page number
     * @param   integer $max The maximum number of rows per page. 'ALL' for unlimited.
     * @return  array The list of issues to be displayed
     */
    public static function getListing($prj_id, $options, $current_row = 0, $max = 5)
    {
        if (strtoupper($max) == "ALL") {
            $max = 9999999;
        }
        $start = $current_row * $max;
        // get the current user's role
        $usr_id = Auth::getUserID();
        $role_id = User::getRoleByUser($usr_id, $prj_id);

        // get any custom fields that should be displayed
        $custom_fields = Custom_Field::getFieldsToBeListed($prj_id);

        $stmt = "SELECT
                    iss_id,
                    iss_grp_id,
                    iss_prj_id,
                    iss_sta_id,
                    iss_customer_id,
                    iss_customer_contract_id,
                    iss_created_date,
                    iss_updated_date,
                    iss_last_response_date,
                    iss_closed_date,
                    iss_last_customer_action_date,
                    iss_usr_id,
                    iss_summary,
                    pri_title,
                    prc_title,
                    sta_title,
                    sta_color status_color,
                    sta_id,
                    iqu_status,
                    grp_name `group`,
                    pre_title,
                    iss_last_public_action_date,
                    iss_last_public_action_type,
                    iss_last_internal_action_date,
                    iss_last_internal_action_type,
                    " . Issue::getLastActionFields() . ",
                    IF(iss_last_internal_action_date > iss_last_public_action_date, 'internal', 'public') AS action_type,
                    iss_private,
                    usr_full_name,
                    iss_percent_complete,
                    iss_dev_time,
                    iss_expected_resolution_date,
                    sev_title
                 FROM
                    (
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user";

        // join custom fields if we are searching by custom fields
        if ((is_array($options['custom_field'])) && (count($options['custom_field']) > 0)) {
            foreach ($options['custom_field'] as $fld_id => $search_value) {
                if (empty($search_value)) {
                    continue;
                }
                $field = Custom_Field::getDetails($fld_id);
                if (($field['fld_type'] == 'date') && ((empty($search_value['Year'])) || (empty($search_value['Month'])) || (empty($search_value['Day'])))) {
                    continue;
                }
                if (($field['fld_type'] == 'integer') && empty($search_value['value'])) {
                    continue;
                }
                if ($field['fld_type'] == 'multiple') {
                    $search_value = Misc::escapeString($search_value);
                    foreach ($search_value as $cfo_id) {
                        $stmt .= ",\n" . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_custom_field as cf" . $fld_id . '_' . $cfo_id . "\n";
                    }
                } else {
                    $stmt .= ",\n" . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_custom_field as cf" . $fld_id . "\n";
                }
            }
        }
        $stmt .= ")";

        // check for the custom fields we want to sort by
        if (strstr($options['sort_by'], 'custom_field') !== false) {
            $fld_id = str_replace("custom_field_", '', $options['sort_by']);
            $stmt .= "\n LEFT JOIN \n" .
                APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_custom_field as cf_sort
                ON
                    (cf_sort.icf_iss_id = iss_id AND cf_sort.icf_fld_id = $fld_id) \n";
        }

        if (!empty($options["users"]) || $options["sort_by"] === "isu_usr_id") {
            $stmt .= "
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user
                 ON
                    isu_iss_id=iss_id";
        }
        if ((!empty($options["show_authorized_issues"])) || (($role_id == User::getRoleID("Reporter")) && (Project::getSegregateReporters($prj_id)))) {
            $stmt .= "
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_user_replier
                 ON
                    iur_iss_id=iss_id";
        }
        if (!empty($options["show_notification_list_issues"])) {
            $stmt .= "
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "subscription
                 ON
                    sub_iss_id=iss_id";
        }
        $stmt .= "
                 LEFT JOIN
                    " . APP_DEFAULT_DB . ".`" . APP_TABLE_PREFIX . "group`
                 ON
                    iss_grp_id=grp_id
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_category
                 ON
                    iss_prc_id=prc_id
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_release
                 ON
                    iss_pre_id = pre_id
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 ON
                    iss_sta_id=sta_id
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_priority
                 ON
                    iss_pri_id=pri_id
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_severity
                 ON
                    iss_sev_id=sev_id
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_quarantine
                 ON
                    iss_id=iqu_iss_id AND
                    (iqu_expiration > '" . Date_Helper::getCurrentDateGMT() . "' OR iqu_expiration IS NULL)
                 WHERE
                    iss_prj_id= " . Misc::escapeInteger($prj_id);
        $stmt .= self::buildWhereClause($options);

        if (strstr($options["sort_by"], 'custom_field') !== false) {
            $fld_details = Custom_Field::getDetails($fld_id);
            $sort_by = 'cf_sort.' . Custom_Field::getDBValueFieldNameByType($fld_details['fld_type']);
        } else {
            $sort_by = Misc::escapeString($options["sort_by"]);
        }

        $stmt .= "
                 GROUP BY
                    iss_id
                 ORDER BY
                    " . $sort_by . " " . Misc::escapeString($options["sort_order"]) . ",
                    iss_id DESC";
        $total_rows = Pager::getTotalRows($stmt);
        $stmt .= "
                 LIMIT
                    " . Misc::escapeInteger($start) . ", " . Misc::escapeInteger($max);
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array(
                "list" => "",
                "info" => ""
            );
        }

        if (count($res) > 0) {
            Issue::getAssignedUsersByIssues($res);
            Time_Tracking::getTimeSpentByIssues($res);
            // need to get the customer titles for all of these issues...
            if (Customer::hasCustomerIntegration($prj_id)) {
                Customer::getCustomerTitlesByIssues($prj_id, $res);
                Customer::getSupportLevelsByIssues($prj_id, $res);
            }
            Issue::formatLastActionDates($res);
            Issue::getLastStatusChangeDates($prj_id, $res);
        } elseif ($current_row > 0) {
            // if there are no results, and the page is not the first page reset page to one and reload results
            Auth::redirect("list.php?pagerRow=0&rows=$max");
        }

        $groups = Group::getAssocList($prj_id);
        $categories = Category::getAssocList($prj_id);
        $column_headings = Issue::getColumnHeadings($prj_id);
        if (count($custom_fields) > 0) {
            $column_headings = array_merge($column_headings,$custom_fields);
        }
        $csv[] = @implode("\t", $column_headings);

        for ($i = 0; $i < count($res); $i++) {
            $res[$i]["time_spent"] = Misc::getFormattedTime($res[$i]["time_spent"]);
            $res[$i]["iss_created_date"] = Date_Helper::getFormattedDate($res[$i]["iss_created_date"]);
            $res[$i]["iss_expected_resolution_date"] = Date_Helper::getSimpleDate($res[$i]["iss_expected_resolution_date"], false);
            $fields = array(
                $res[$i]['pri_title'],
                $res[$i]['iss_id'],
                $res[$i]['usr_full_name'],
            );
            // hide the group column from the output if no
            // groups are available in the database
            if (count($groups) > 0) {
                $fields[] = $res[$i]['group'];
            }
            $fields[] = $res[$i]['assigned_users'];
            $fields[] = $res[$i]['time_spent'];
            // hide the category column from the output if no
            // categories are available in the database
            if (count($categories) > 0) {
                $fields[] = $res[$i]['prc_title'];
            }
            if (Customer::hasCustomerIntegration($prj_id)) {
                $fields[] = @$res[$i]['customer_title'];
                // check if current user is acustomer and has a per incident contract.
                // if so, check if issue is redeemed.
                if (User::getRoleByUser($usr_id, $prj_id) == User::getRoleID('Customer')) {
                    if ((Customer::hasPerIncidentContract($prj_id, Issue::getCustomerID($res[$i]['iss_id'])) &&
                            (Customer::isRedeemedIncident($prj_id, $res[$i]['iss_id'])))) {
                        $res[$i]['redeemed'] = true;
                    }
                }
            }
            $fields[] = $res[$i]['sta_title'];
            $fields[] = $res[$i]["status_change_date"];
            $fields[] = $res[$i]["last_action_date"];
            $fields[] = $res[$i]['iss_dev_time'];
            $fields[] = $res[$i]['iss_summary'];
            $fields[] = $res[$i]['iss_expected_resolution_date'];

            if (count($custom_fields) > 0) {
                $res[$i]['custom_field'] = array();
                $custom_field_values = Custom_Field::getListByIssue($prj_id, $res[$i]['iss_id']);
                foreach ($custom_field_values as $this_field) {
                    if (!empty($custom_fields[$this_field['fld_id']])) {
                        $res[$i]['custom_field'][$this_field['fld_id']] = $this_field['value'];
                        $fields[] = $this_field['value'];
                    }
                }
            }

            $csv[] = @implode("\t", $fields);
        }

        $total_pages = ceil($total_rows / $max);
        $last_page = $total_pages - 1;
        return array(
            "list" => $res,
            "info" => array(
                "current_page"  => $current_row,
                "start_offset"  => $start,
                "end_offset"    => $start + count($res),
                "total_rows"    => $total_rows,
                "total_pages"   => $total_pages,
                "previous_page" => ($current_row == 0) ? "-1" : ($current_row - 1),
                "next_page"     => ($current_row == $last_page) ? "-1" : ($current_row + 1),
                "last_page"     => $last_page,
                "custom_fields" => $custom_fields
            ),
            "csv" => @implode("\n", $csv)
        );
    }

    /**
     * Method used to get the list of issues to be displayed in the grid layout.
     *
     * @param   array $options The search parameters
     * @return  string The where clause
     */
    public static function buildWhereClause($options)
    {
        $usr_id = Auth::getUserID();
        $prj_id = Auth::getCurrentProject();
        $role_id = User::getRoleByUser($usr_id, $prj_id);

        $stmt = ' AND iss_usr_id = usr_id';
        if ($role_id == User::getRoleID('Customer')) {
            $stmt .= " AND iss_customer_id=" . User::getCustomerID($usr_id);
        } elseif (($role_id == User::getRoleID("Reporter")) && (Project::getSegregateReporters($prj_id))) {
            $stmt .= " AND (
                        iss_usr_id = $usr_id OR
                        iur_usr_id = $usr_id
                        )";
        }

        if (!empty($options["users"])) {
            $stmt .= " AND (\n";
            if (stristr($options["users"], "grp") !== false) {
                $chunks = explode(":", $options["users"]);
                $stmt .= 'iss_grp_id = ' . Misc::escapeInteger($chunks[1]);
            } else {
                if ($options['users'] == '-1') {
                    $stmt .= 'isu_usr_id IS NULL';
                } elseif ($options['users'] == '-2') {
                    $stmt .= 'isu_usr_id IS NULL OR isu_usr_id=' . $usr_id;
                } elseif ($options['users'] == '-3') {
                    $stmt .= 'isu_usr_id = ' . $usr_id . ' OR iss_grp_id = ' . User::getGroupID($usr_id);
                } elseif ($options['users'] == '-4') {
                    $stmt .= 'isu_usr_id IS NULL OR isu_usr_id = ' . $usr_id . ' OR iss_grp_id = ' . User::getGroupID($usr_id);
                } else {
                    $stmt .= 'isu_usr_id =' . Misc::escapeInteger($options["users"]);
                }
            }
            $stmt .= ')';
        }
        if (!empty($options["reporter"])) {
            $stmt .= " AND iss_usr_id = " . Misc::escapeInteger($options["reporter"]);
        }
        if (!empty($options["show_authorized_issues"])) {
            $stmt .= " AND (iur_usr_id=$usr_id)";
        }
        if (!empty($options["show_notification_list_issues"])) {
            $stmt .= " AND (sub_usr_id=$usr_id)";
        }
        if (!empty($options["keywords"])) {
            $stmt .= " AND (\n";
            if (($options['search_type'] == 'all_text') && (APP_ENABLE_FULLTEXT)) {
                $stmt .= "iss_id IN(" . join(', ', self::getFullTextIssues($options)) . ")";
            } elseif (($options['search_type'] == 'customer') && (Customer::hasCustomerIntegration($prj_id))) {
                // check if the user is trying to search by customer email
                $customer_ids = Customer::getCustomerIDsLikeEmail($prj_id, $options['keywords']);
                if (count($customer_ids) > 0) {
                    $stmt .= " iss_customer_id IN (" . implode(', ', $customer_ids) . ")";
                } else {
                    // no results, kill query
                    $stmt .= " iss_customer_id = -1";
                }
            } else {
                $stmt .= "(" . Misc::prepareBooleanSearch('iss_summary', $options["keywords"]);
                $stmt .= " OR " . Misc::prepareBooleanSearch('iss_description', $options["keywords"]) . ")";
            }
            $stmt .= "\n) ";
        }
        if (!empty($options['customer_id'])) {
            $stmt .= " AND iss_customer_id=" . Misc::escapeInteger($options["customer_id"]);
        }
        if (!empty($options["priority"])) {
            $stmt .= " AND iss_pri_id=" . Misc::escapeInteger($options["priority"]);
        }
        if (!empty($options["status"])) {
            $stmt .= " AND iss_sta_id=" . Misc::escapeInteger($options["status"]);
        }
        if (!empty($options["category"])) {
            if (!is_array($options['category'])) {
                $options['category'] = array($options['category']);
            }
            $stmt .= " AND iss_prc_id IN(" . join(', ', Misc::escapeInteger($options["category"])) . ")";
        }
        if (!empty($options["hide_closed"])) {
            $stmt .= " AND sta_is_closed=0";
        }
        if (!empty($options['release'])) {
            $stmt .= " AND iss_pre_id = " . Misc::escapeInteger($options['release']);
        }
        // now for the date fields
        $date_fields = array(
            'created_date',
            'updated_date',
            'last_response_date',
            'first_response_date',
            'closed_date'
        );
        foreach ($date_fields as $field_name) {
            if (!empty($options[$field_name])) {
                switch ($options[$field_name]['filter_type']) {
                    case 'greater':
                        $stmt .= " AND iss_$field_name >= '" . Misc::escapeString($options[$field_name]['start']) . "'";
                        break;
                    case 'less':
                        $stmt .= " AND iss_$field_name <= '" . Misc::escapeString($options[$field_name]['start']) . "'";
                        break;
                    case 'between':
                        $stmt .= " AND iss_$field_name BETWEEN '" . Misc::escapeString($options[$field_name]['start']) . "' AND '" . Misc::escapeString($options[$field_name]['end']) . "'";
                        break;
                    case 'null':
                        $stmt .= " AND iss_$field_name IS NULL";
                        break;
                    case 'in_past':
                        if (strlen($options[$field_name]['time_period']) == 0) {
                            $options[$field_name]['time_period'] = 0;
                        }
                        $stmt .= " AND (UNIX_TIMESTAMP('" . Date_Helper::getCurrentDateGMT() . "') - UNIX_TIMESTAMP(iss_$field_name)) <= (" .
                            Misc::escapeInteger($options[$field_name]['time_period']) . "*3600)";
                        break;
                }
            }
        }
        // custom fields
        if ((is_array($options['custom_field'])) && (count($options['custom_field']) > 0)) {
            foreach ($options['custom_field'] as $fld_id => $search_value) {
                if (empty($search_value)) {
                    continue;
                }
                $field = Custom_Field::getDetails($fld_id);
                $fld_db_name = Custom_Field::getDBValueFieldNameByType($field['fld_type']);
                if (($field['fld_type'] == 'date') &&
                        ((empty($search_value['Year'])) || (empty($search_value['Month'])) || (empty($search_value['Day'])))) {
                    continue;
                }
                if (($field['fld_type'] == 'integer') && empty($search_value['value'])) {
                    continue;
                }

                if ($field['fld_type'] == 'multiple') {
                    $search_value = Misc::escapeString($search_value);
                    foreach ($search_value as $cfo_id) {
                        $cfo_id = Misc::escapeString($cfo_id);
                        $stmt .= " AND\n cf" . $fld_id . '_' . $cfo_id . ".icf_iss_id = iss_id";
                        $stmt .= " AND\n cf" . $fld_id . '_' . $cfo_id . ".icf_fld_id = $fld_id";
                        $stmt .= " AND\n cf" . $fld_id . '_' . $cfo_id . "." . $fld_db_name . " = '$cfo_id'";
                    }
                } elseif ($field['fld_type'] == 'date') {
                    if ((empty($search_value['Year'])) || (empty($search_value['Month'])) || (empty($search_value['Day']))) {
                        continue;
                    }
                    $search_value = $search_value['Year'] . "-" . $search_value['Month'] . "-" . $search_value['Day'];
                    $stmt .= " AND\n (iss_id = cf" . $fld_id . ".icf_iss_id AND
                        cf" . $fld_id . "." . $fld_db_name . " = '" . Misc::escapeString($search_value) . "')";
                } else if ($field['fld_type'] == 'integer') {
                    $value = $search_value['value'];
                    switch ($search_value['filter_type']) {
                    case 'ge':
                        $cmp = '>=';
                        break;
                    case 'le':
                        $cmp = '<=';
                        break;
                    case 'gt':
                        $cmp = '>';
                        break;
                    case 'lt':
                        $cmp = '<';
                        break;
                    default:
                        $cmp = '=';
                        break;
                    }
                    $stmt .= " AND\n (iss_id = cf" . $fld_id . ".icf_iss_id";
                    $stmt .= " AND\n cf" . $fld_id . ".icf_fld_id = $fld_id";
                    $stmt .= " AND cf" . $fld_id . "." . $fld_db_name . $cmp . Misc::escapeString($value) . ')';
                } else {
                    $stmt .= " AND\n (iss_id = cf" . $fld_id . ".icf_iss_id";
                    $stmt .= " AND\n cf" . $fld_id . ".icf_fld_id = $fld_id";
                    if ($field['fld_type'] == 'combo') {
                        $stmt .= " AND cf" . $fld_id . "." . $fld_db_name . " IN(" . join(', ', Misc::escapeInteger($search_value)) . ")";
                    } else {
                        $stmt .= " AND cf" . $fld_id . "." . $fld_db_name . " LIKE '%" . Misc::escapeString($search_value) . "%'";
                    }
                    $stmt .= ')';
                }
            }
        }
        // clear cached full-text values if we are not searching fulltext anymore
        if ((APP_ENABLE_FULLTEXT) && (@$options['search_type'] != 'all_text')) {
            Session::set('fulltext_string', '');
            Session::set('fulltext_issues', '');
        }
        return $stmt;
    }

    /**
     * Returns an array of issues based on full text search results.
     *
     * @param   array $options An array of search options
     * @return  array An array of issue IDS
     */
    private static function getFullTextIssues($options)
    {
        // check if a list of issues for this full text search is already cached
        $fulltext_string = Session::get('fulltext_string');
        if ((!empty($fulltext_string)) && ($fulltext_string == $options['keywords'])) {
            return Session::get('fulltext_issues');
        }

        // no pre-existing list, generate them
        $stmt = "(SELECT
                    DISTINCT(iss_id)
                 FROM
                     " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 WHERE
                     MATCH(iss_summary, iss_description) AGAINST ('" . Misc::escapeString($options['keywords']) . "' IN BOOLEAN MODE)
                 ) UNION (
                 SELECT
                    DISTINCT(not_iss_id)
                 FROM
                     " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "note
                 WHERE
                     MATCH(not_note) AGAINST ('" . Misc::escapeString($options['keywords']) . "' IN BOOLEAN MODE)
                 ) UNION (
                 SELECT
                    DISTINCT(ttr_iss_id)
                 FROM
                     " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "time_tracking
                 WHERE
                     MATCH(ttr_summary) AGAINST ('" . Misc::escapeString($options['keywords']) . "' IN BOOLEAN MODE)
                 ) UNION (
                 SELECT
                    DISTINCT(phs_iss_id)
                 FROM
                     " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "phone_support
                 WHERE
                     MATCH(phs_description) AGAINST ('" . Misc::escapeString($options['keywords']) . "' IN BOOLEAN MODE)
                 ) UNION (
                 SELECT
                     DISTINCT(sup_iss_id)
                 FROM
                     " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email,
                     " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email_body
                 WHERE
                     sup_id = seb_sup_id AND
                     MATCH(seb_body) AGAINST ('" . Misc::escapeString($options['keywords']) . "' IN BOOLEAN MODE)
                 )";
        $res = DB_Helper::getInstance()->getCol($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array(-1);
        } else {
            $stmt = "SELECT
                        DISTINCT(icf_iss_id)
                    FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_custom_field
                    WHERE
                        MATCH (icf_value) AGAINST ('" . Misc::escapeString($options['keywords']) . "' IN BOOLEAN MODE)";
            $custom_res = DB_Helper::getInstance()->getCol($stmt);
            if (PEAR::isError($custom_res)) {
                Error_Handler::logError(array($custom_res->getMessage(), $custom_res->getDebugInfo()), __FILE__, __LINE__);
                return array(-1);
            }
            $issues = array_merge($res, $custom_res);
            // we kill the query results on purpose to flag that no
            // issues could be found with fulltext search
            if (count($issues) < 1) {
                $issues = array(-1);
            }
            Session::set('fulltext_string', $options['keywords']);
            Session::set('fulltext_issues', $issues);
            return $issues;
        }
    }
}
