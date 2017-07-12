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
use Eventum\Session;

/**
 * Holding all search relevant methods
 */
class Search
{
    /**
     * Method used to get a specific parameter in the issue listing cookie.
     *
     * @param string $name The name of the parameter
     * @param bool $request_only If only $_GET and $_POST should be checked
     * @param array $valid_values
     * @return  mixed The value of the specified parameter
     * @return string
     */
    public static function getParam($name, $request_only = false, $valid_values = null)
    {
        $value = null;
        if (isset($_GET[$name])) {
            $value = $_GET[$name];
        } elseif (isset($_POST[$name])) {
            $value = $_POST[$name];
        } elseif ($request_only) {
            return '';
        }

        if (isset($value)) {
            if ($valid_values && !in_array($value, $valid_values)) {
                return '';
            }

            return $value;
        }

        $profile = Search_Profile::getProfile(Auth::getUserID(), Auth::getCurrentProject(), 'issue');
        if (isset($profile[$name])) {
            return $profile[$name];
        }

        return '';
    }

    /**
     * Method used to save the current search parameters in a cookie.
     * TODO: split to buildSearchParams() and actual saveSearchParams()
     *
     * @param bool|string $save_db Whether to save search parameters also to database
     * @return  array The search parameters
     */
    public static function saveSearchParams($save_db = true)
    {
        $request_only = !$save_db; // if we should only look at get / post not the DB or cookies

        $sort_by = self::getParam('sort_by', $request_only);
        $sort_order = self::getParam('sort_order', $request_only, ['asc', 'desc']);
        $rows = self::getParam('rows', $request_only);
        $hide_closed = self::getParam('hide_closed', $request_only);
        if ($hide_closed === '') {
            $hide_closed = 1;
        }
        $search_type = self::getParam('search_type', $request_only);
        if (empty($search_type)) {
            $search_type = 'all_text';
        }
        $custom_field = self::getParam('custom_field', $request_only);
        if (is_string($custom_field)) {
            $custom_field = unserialize(urldecode($custom_field));
        }
        $cookie = [
            'rows' => Misc::escapeString($rows ? $rows : APP_DEFAULT_PAGER_SIZE),
            'pagerRow' => Misc::escapeInteger(self::getParam('pagerRow', $request_only)),
            'hide_closed' => $hide_closed,
            'sort_by' => Misc::stripHTML($sort_by ? $sort_by : 'pri_rank'),
            'sort_order' => Misc::stripHTML($sort_order ? $sort_order : 'ASC'),
            'customer_id' => Misc::escapeString(self::getParam('customer_id')),
            'nosave' => self::getParam('nosave', $request_only),
            // quick filter form
            'keywords' => self::getParam('keywords', $request_only),
            'match_mode' => self::getParam('match_mode', $request_only),
            'hide_excerpts' => self::getParam('hide_excerpts', $request_only),
            'search_type' => Misc::stripHTML($search_type),
            'users' => Misc::escapeString(self::getParam('users', $request_only)),
            'status' => Misc::escapeInteger(self::getParam('status', $request_only)),
            'priority' => Misc::escapeInteger(self::getParam('priority', $request_only)),
            'severity' => Misc::escapeInteger(self::getParam('severity', $request_only)),
            'category' => Misc::escapeInteger(self::getParam('category', $request_only)),
            'customer_email' => Misc::stripHTML(self::getParam('customer_email', $request_only)),
            // advanced search form
            'show_authorized_issues' => Misc::escapeString(self::getParam('show_authorized_issues', $request_only)),
            'show_notification_list_issues' => Misc::escapeString(self::getParam('show_notification_list_issues', $request_only)),
            'reporter' => Misc::escapeInteger(self::getParam('reporter', $request_only)),
            'product' => Misc::escapeInteger(self::getParam('product', $request_only)),
            // other fields
            'release' => Misc::escapeInteger(self::getParam('release', $request_only)),
            // custom fields
            'custom_field' => Misc::stripHTML($custom_field),
        ];
        // now do some magic to properly format the date fields
        $date_fields = [
            'created_date',
            'updated_date',
            'last_response_date',
            'first_response_date',
            'closed_date',
        ];
        foreach ($date_fields as $field_name) {
            $field = Misc::stripHTML(self::getParam($field_name, $request_only));
            if (empty($field)) {
                continue;
            }
            if (@$field['filter_type'] == 'in_past') {
                @$cookie[$field_name] = [
                    'filter_type' => 'in_past',
                    'time_period' => $field['time_period'],
                ];
            } else {
                $end_field_name = $field_name . '_end';
                $end_field = Misc::stripHTML(self::getParam($end_field_name, $request_only));
                @$cookie[$field_name] = [
                    'past_hour' => $field['past_hour'],
                    'Year' => $field['Year'],
                    'Month' => $field['Month'],
                    'Day' => $field['Day'],
                    'start' => $field['Year'] . '-' . $field['Month'] . '-' . $field['Day'],
                    'filter_type' => $field['filter_type'],
                    'end' => $end_field['Year'] . '-' . $end_field['Month'] . '-' . $end_field['Day'],
                ];
                @$cookie[$end_field_name] = [
                    'Year' => $end_field['Year'],
                    'Month' => $end_field['Month'],
                    'Day' => $end_field['Day'],
                ];
            }
        }

        if ($save_db) {
            Search_Profile::save(Auth::getUserID(), Auth::getCurrentProject(), 'issue', $cookie);
        }

        return $cookie;
    }

    /**
     * Method used to get the list of issues to be displayed in the grid layout.
     *
     * @param   int $prj_id The current project ID
     * @param   array $options The search parameters
     * @param   int $current_row The current page number
     * @param   int $max The maximum number of rows per page. 'ALL' for unlimited.
     * @return  array The list of issues to be displayed
     */
    public static function getListing($prj_id, $options, $current_row = 0, $max = 5)
    {
        if (strtoupper($max) == 'ALL') {
            $max = 9999999;
        }
        $start = $current_row * $max;
        // get the current user's role
        $usr_id = Auth::getUserID();
        $role_id = User::getRoleByUser($usr_id, $prj_id);
        $usr_details = User::getDetails($usr_id);

        // get any custom fields that should be displayed
        $custom_fields = Custom_Field::getFieldsToBeListed($prj_id);

        $stmt = 'SELECT
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
                    pri_icon,
                    prc_title,
                    sta_title,
                    sta_color status_color,
                    sta_id,
                    iqu_status,
                    grp_name,
                    pre_title,
                    iss_last_public_action_date,
                    iss_last_public_action_type,
                    iss_last_internal_action_date,
                    iss_last_internal_action_type,
                    iss_status_change_date,
                    ' . Issue::getLastActionFields() . ",
                    CASE WHEN iss_last_internal_action_date > iss_last_public_action_date THEN 'internal' ELSE 'public' END AS action_type,
                    usr_full_name,
                    iss_percent_complete,
                    iss_dev_time,
                    iss_expected_resolution_date,
                    sev_title,
                    iss_access_level
                 FROM
                    (
                    {{%issue}},
                    {{%user}}";

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
                        $stmt .= ",\n{{%issue_custom_field}} as `cf" . $fld_id . '_' . $cfo_id . "`\n";
                    }
                } else {
                    $stmt .= ",\n{{%issue_custom_field}} as `cf" . $fld_id . "`\n";
                }
            }
        }
        $stmt .= ')';

        // check for the custom fields we want to sort by
        if (strstr($options['sort_by'], 'custom_field') !== false) {
            $fld_id = str_replace('custom_field_', '', $options['sort_by']);
            $stmt .= "\n LEFT JOIN \n
                    {{%issue_custom_field}} as cf_sort
                ON
                    (cf_sort.icf_iss_id = iss_id AND cf_sort.icf_fld_id = $fld_id) \n";
        }

        $stmt .= '
             LEFT JOIN
                {{%issue_user}}
             ON
                isu_iss_id=iss_id';
        if (!empty($usr_details['usr_par_code'])) {
            // restrict partners
            $stmt .= '
                 LEFT JOIN
                    {{%issue_partner}}
                 ON
                    ipa_iss_id=iss_id';
        }
        if ((!empty($options['show_authorized_issues'])) || (($role_id == User::ROLE_REPORTER) && (Project::getSegregateReporters($prj_id)))) {
            $stmt .= '
                 LEFT JOIN
                    {{%issue_user_replier}}
                 ON
                    iur_iss_id=iss_id';
        }
        if (!empty($options['show_notification_list_issues'])) {
            $stmt .= '
                 LEFT JOIN
                    {{%subscription}}
                 ON
                    sub_iss_id=iss_id';
        }
        if (!empty($options['product'])) {
            $stmt .= '
                 LEFT JOIN
                    {{%issue_product_version}}
                 ON
                    ipv_iss_id=iss_id';
        }
        $stmt .= "
                 LEFT JOIN
                    {{%group}}
                 ON
                    iss_grp_id=grp_id
                 LEFT JOIN
                    {{%project_category}}
                 ON
                    iss_prc_id=prc_id
                 LEFT JOIN
                    {{%project_release}}
                 ON
                    iss_pre_id = pre_id
                 LEFT JOIN
                    {{%status}}
                 ON
                    iss_sta_id=sta_id
                 LEFT JOIN
                    {{%project_priority}}
                 ON
                    iss_pri_id=pri_id
                 LEFT JOIN
                    {{%project_severity}}
                 ON
                    iss_sev_id=sev_id
                 LEFT JOIN
                    {{%issue_quarantine}}
                 ON
                    iss_id=iqu_iss_id AND
                    (iqu_expiration > '" . Date_Helper::getCurrentDateGMT() . "' OR iqu_expiration IS NULL)
                 LEFT JOIN
                    {{%issue_access_list}}
                 ON
                    iss_id = ial_iss_id AND
                    ial_usr_id = " . $usr_id . '
                 LEFT JOIN
                    {{%user_group}}
                 ON
                    ugr_usr_id = ' . $usr_id . '
                 WHERE
                    iss_prj_id= ' . Misc::escapeInteger($prj_id);
        $stmt .= self::buildWhereClause($options);

        if (strstr($options['sort_by'], 'custom_field') !== false) {
            $fld_details = Custom_Field::getDetails($fld_id);
            $sort_by = 'cf_sort.' . Custom_Field::getDBValueFieldNameByType($fld_details['fld_type']);
        } else {
            $sort_by = Misc::escapeString($options['sort_by']);
        }

        $stmt .= '
                 GROUP BY
                    iss_id
                 ORDER BY
                    ' . $sort_by . ' ' . Misc::escapeString($options['sort_order']) . ',
                    iss_id DESC';
        $total_rows = Pager::getTotalRows($stmt);
        $stmt .= '
                 LIMIT
                    ' . Misc::escapeInteger($max) . ' OFFSET ' . Misc::escapeInteger($start);

        try {
            $res = DB_Helper::getInstance()->getAll($stmt);
        } catch (DatabaseException $e) {
            return [
                'list' => null,
                'info' => null,
                'csv' => null,
            ];
        }

        if (count($res) > 0) {
            Issue::getAssignedUsersByIssues($res);
            Time_Tracking::fillTimeSpentByIssues($res);
            // need to get the customer titles for all of these issues...
            if (CRM::hasCustomerIntegration($prj_id)) {
                $crm = CRM::getInstance($prj_id);
                $crm->processListIssuesResult($res);
            }
            Issue::formatLastActionDates($res);
            Issue::getLastStatusChangeDates($prj_id, $res);
        } elseif ($current_row > 0) {
            // if there are no results, and the page is not the first page reset page to one and reload results
            Auth::redirect("list.php?pagerRow=0&rows=$max");
        }

        $column_headings = [];
        $columns_to_display = Display_Column::getColumnsToDisplay($prj_id, 'list_issues');
        foreach ($columns_to_display as $col_key => $column) {
            if ($col_key == 'custom_fields' && count($custom_fields) > 0) {
                foreach ($custom_fields as $fld_id => $fld_title) {
                    $column_headings['cstm_' . $fld_id] = $fld_title;
                }
            } else {
                $column_headings[$col_key] = $column['title'];
            }
        }
        $csv[] = @implode("\t", $column_headings);

        if (@$options['hide_excerpts'] != 1 && self::doesBackendSupportExcerpts() == true) {
            $excerpts = self::getFullTextExcerpts($options);
        }

        foreach ($res as &$row) {
            $issue_id = $row['iss_id'];
            $row['time_spent'] = Misc::getFormattedTime($row['time_spent']);
            $row['expected_resolution_date'] = Date_Helper::getSimpleDate($row['iss_expected_resolution_date'], false);
            $row['excerpts'] = isset($excerpts[$issue_id]) ? $excerpts[$issue_id] : '';

            $row['access_level_name'] = Access::getAccessLevelName($row['iss_access_level']);

            $fields = [];
            foreach (array_keys($columns_to_display) as $col_key) {
                switch ($col_key) {
                    case 'pri_rank':
                        $col_key = 'pri_title';break;
                    case 'assigned':
                        $col_key = 'assigned_users';break;
                    case 'sta_rank':
                        $col_key = 'sta_title';break;
                    case 'sta_change_date':
                        $col_key = 'status_change_date';break;
                    case 'sev_rank':
                        $col_key = 'sev_title';break;
                    case 'iss_customer_id':
                        $col_key = 'customer_title';break;
                }
                if ($col_key == 'custom_fields' && count($custom_fields) > 0) {
                    $custom_field_values = Custom_Field::getListByIssue($prj_id, $row['iss_id']);
                    foreach ($custom_field_values as $this_field) {
                        if (!empty($custom_fields[$this_field['fld_id']])) {
                            $row['custom_field'][$this_field['fld_id']] = $this_field['value'];
                            $fields[] = $this_field['value'];
                        }
                    }
                } else {
                    $fields[] = isset($row[$col_key]) ? $row[$col_key] : '';
                }
            }
            if (CRM::hasCustomerIntegration($prj_id)) {
                // check if current user is a customer and has a per incident contract.
                // if so, check if issue is redeemed.
                if (User::getRoleByUser($usr_id, $prj_id) == User::ROLE_CUSTOMER) {
                    // TODOCRM: Fix per incident usage
//                    if ((Customer::hasPerIncidentContract($prj_id, Issue::getCustomerID($res[$i]['iss_id'])) &&
//                            (Customer::isRedeemedIncident($prj_id, $res[$i]['iss_id'])))) {
//                        $res[$i]['redeemed'] = true;
//                    }
                }
            }

            $csv[] = @implode("\t", $fields);
        }

        $total_pages = ceil($total_rows / $max);
        $last_page = $total_pages - 1;

        return [
            'list' => $res,
            'info' => [
                'current_page' => $current_row,
                'start_offset' => $start,
                'end_offset' => $start + count($res),
                'total_rows' => $total_rows,
                'total_pages' => $total_pages,
                'previous_page' => ($current_row == 0) ? '-1' : ($current_row - 1),
                'next_page' => ($current_row == $last_page) ? '-1' : ($current_row + 1),
                'last_page' => $last_page,
                'custom_fields' => $custom_fields,
            ],
            'csv' => @implode("\n", $csv),
        ];
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
        $usr_details = User::getDetails($usr_id);

        $stmt = ' AND iss_usr_id = usr_id';
        if ($role_id == User::ROLE_CUSTOMER) {
            $crm = CRM::getInstance($prj_id);
            $contact = $crm->getContact($usr_details['usr_customer_contact_id']);
            $stmt .= " AND iss_customer_contract_id IN('" . implode("','", $contact->getContractIDs()) . "')";
            $stmt .= " AND iss_customer_id ='" . Auth::getCurrentCustomerID() . "'";
        } elseif (($role_id == User::ROLE_REPORTER) && (Project::getSegregateReporters($prj_id))) {
            $stmt .= " AND (
                        iss_usr_id = $usr_id OR
                        iur_usr_id = $usr_id
                        )";
        }

        if (!empty($usr_details['usr_par_code'])) {
            // restrict partners
            $stmt .= " AND ipa_par_code = '" . Misc::escapeString($usr_details['usr_par_code']) . "'";
        }

        if (!empty($options['users'])) {
            $stmt .= " AND (\n";
            if (stristr($options['users'], 'grp') !== false) {
                $chunks = explode(':', $options['users']);
                $stmt .= 'iss_grp_id = ' . Misc::escapeInteger($chunks[1]);
            } else {
                if ($options['users'] == '-1') {
                    $stmt .= 'isu_usr_id IS NULL';
                } elseif ($options['users'] == '-2') {
                    $stmt .= 'isu_usr_id IS NULL OR isu_usr_id=' . $usr_id;
                } elseif ($options['users'] == '-3') {
                    $stmt .= 'isu_usr_id = ' . $usr_id;
                    $user_groups = User::getGroupIDs($usr_id);
                    if (count($user_groups) > 0) {
                        $stmt .= ' OR iss_grp_id IN(' . implode(',', $user_groups) . ')';
                    }
                } elseif ($options['users'] == '-4') {
                    $stmt .= 'isu_usr_id IS NULL OR isu_usr_id = ' . $usr_id;
                    $user_groups = User::getGroupIDs($usr_id);
                    if (count($user_groups) > 0) {
                        $stmt .= ' OR iss_grp_id IN(' . implode(',', $user_groups) . ')';
                    }
                } else {
                    $stmt .= 'isu_usr_id =' . Misc::escapeInteger($options['users']);
                }
            }
            $stmt .= ')';
        }
        if (!empty($options['reporter'])) {
            $stmt .= ' AND iss_usr_id = ' . Misc::escapeInteger($options['reporter']);
        }
        if (!empty($options['show_authorized_issues'])) {
            $stmt .= " AND (iur_usr_id=$usr_id)";
        }
        if (!empty($options['show_notification_list_issues'])) {
            $stmt .= " AND (sub_usr_id=$usr_id)";
        }
        if (!empty($options['keywords'])) {
            $stmt .= " AND (\n";
            if (($options['search_type'] == 'all_text') && (APP_ENABLE_FULLTEXT)) {
                $stmt .= 'iss_id IN(' . implode(', ', self::getFullTextIssues($options)) . ')';
            } elseif (($options['search_type'] == 'customer') && (CRM::hasCustomerIntegration($prj_id))) {
                // check if the user is trying to search by customer name / email
                $crm = CRM::getInstance($prj_id);
                $customer_ids = $crm->getCustomerIDsByString($options['keywords'], true);
                if (count($customer_ids) > 0) {
                    $stmt .= ' iss_customer_id IN (' . implode(', ', $customer_ids) . ')';
                } else {
                    // no results, kill query
                    $stmt .= ' iss_customer_id = -1';
                }
            } else {
                $stmt .= '(' . Misc::prepareBooleanSearch('iss_summary', $options['keywords']);
                $stmt .= ' OR ' . Misc::prepareBooleanSearch('iss_description', $options['keywords']) . ')';
            }
            $stmt .= "\n) ";
        }
        if (!empty($options['customer_id'])) {
            $stmt .= " AND iss_customer_id='" . Misc::escapeString($options['customer_id']) . "'";
        }
        if (!empty($options['priority'])) {
            $stmt .= ' AND iss_pri_id=' . Misc::escapeInteger($options['priority']);
        }
        if (!empty($options['severity'])) {
            $stmt .= ' AND iss_sev_id=' . Misc::escapeInteger($options['severity']);
        }
        if (!empty($options['status'])) {
            $stmt .= ' AND iss_sta_id=' . Misc::escapeInteger($options['status']);
        }
        if (!empty($options['category'])) {
            if (!is_array($options['category'])) {
                $options['category'] = [$options['category']];
            }
            $stmt .= ' AND iss_prc_id IN(' . implode(', ', Misc::escapeInteger($options['category'])) . ')';
        }
        if (!empty($options['hide_closed'])) {
            $stmt .= ' AND sta_is_closed=0';
        }
        if (!empty($options['release'])) {
            $stmt .= ' AND iss_pre_id = ' . Misc::escapeInteger($options['release']);
        }
        if (!empty($options['product'])) {
            $stmt .= ' AND ipv_pro_id = ' . Misc::escapeInteger($options['product']);
        }
        // now for the date fields
        $date_fields = [
            'created_date',
            'updated_date',
            'last_response_date',
            'first_response_date',
            'closed_date',
        ];
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
                            Misc::escapeInteger($options[$field_name]['time_period']) . '*3600)';
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
                        $stmt .= " AND\n `cf" . $fld_id . '_' . $cfo_id . '`.icf_iss_id = iss_id';
                        $stmt .= " AND\n `cf" . $fld_id . '_' . $cfo_id . "`.icf_fld_id = $fld_id";
                        $stmt .= " AND\n `cf" . $fld_id . '_' . $cfo_id . '`.' . $fld_db_name . " = '$cfo_id'";
                    }
                } elseif ($field['fld_type'] == 'date') {
                    if ((empty($search_value['Year'])) || (empty($search_value['Month'])) || (empty($search_value['Day']))) {
                        continue;
                    }
                    $search_value = $search_value['Year'] . '-' . $search_value['Month'] . '-' . $search_value['Day'];
                    $stmt .= " AND\n (iss_id = `cf" . $fld_id . '``.icf_iss_id AND
                        `cf' . $fld_id . '`.' . $fld_db_name . " = '" . Misc::escapeString($search_value) . "')";
                } elseif ($field['fld_type'] == 'integer') {
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
                    $stmt .= " AND\n (iss_id = cf" . $fld_id . '.icf_iss_id';
                    $stmt .= " AND\n cf" . $fld_id . ".icf_fld_id = $fld_id";
                    $stmt .= ' AND cf' . $fld_id . '.' . $fld_db_name . $cmp . Misc::escapeString($value) . ')';
                } else {
                    $stmt .= " AND\n (iss_id = cf" . $fld_id . '.icf_iss_id';
                    $stmt .= " AND\n cf" . $fld_id . ".icf_fld_id = $fld_id";
                    if ($field['fld_type'] == 'combo') {
                        $stmt .= ' AND cf' . $fld_id . '.' . $fld_db_name . " IN('" . implode("', '", Misc::escapeString($search_value)) . "')";
                    } else {
                        $stmt .= ' AND cf' . $fld_id . '.' . $fld_db_name . " LIKE '%" . Misc::escapeString($search_value) . "%'";
                    }
                    $stmt .= ')';
                }
            }
        }

        // access restriction
        $stmt .= Access::getListingSQL($prj_id);

        // clear cached full-text values if we are not searching fulltext anymore
        if ((APP_ENABLE_FULLTEXT) && (@$options['search_type'] != 'all_text')) {
            Session::set('fulltext_string', '');
            Session::set('fulltext_issues', '');
            Session::set('fulltext_excerpts', '');
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

        $fulltext = self::getFullTextSearchInstance();
        $issues = $fulltext->getIssueIDs($options);

        if (count($issues) < 1) {
            $issues = [-1]; // no results, kill the query
        }

        Session::set('fulltext_string', $options['keywords']);
        Session::set('fulltext_issues', $issues);
        Session::set('fulltext_excerpts', '');

        return $issues;
    }

    /**
     * This needs to be called after getFullTextIssues
     */
    public static function getFullTextExcerpts($options)
    {
        if (!APP_ENABLE_FULLTEXT || empty($options['keywords'])) {
            return [];
        }

        // check if excerpts for this full text search is already cached
        $excerpts = Session::get('fulltext_excerpts');
        if (empty($excerpts)) {
            $excerpts = self::getFullTextSearchInstance()->getExcerpts();
            Session::set('fulltext_excerpts', $excerpts);
        }

        return $excerpts;
    }

    /**
     * @static
     * @return Abstract_Fulltext_Search
     */
    private static function getFullTextSearchInstance()
    {
        static $instance = false;

        if ($instance == false) {
            $class = APP_FULLTEXT_SEARCH_CLASS;

            // XXX legacy: handle lowercased classname
            if ($class == 'mysql_fulltext_search') {
                $class = 'MySQL_Fulltext_Search';
            } elseif ($class == 'sphinx_fulltext_search') {
                $class = 'Sphinx_Fulltext_Search';
            }

            $instance = new $class();
        }

        return $instance;
    }

    public static function getMatchModes()
    {
        return self::getFullTextSearchInstance()->getMatchModes();
    }

    public static function doesBackendSupportExcerpts()
    {
        return self::getFullTextSearchInstance()->supportsExcerpts();
    }
}
