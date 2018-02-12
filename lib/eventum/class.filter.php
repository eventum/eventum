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
 * Class to handle the business logic related to the custom filters.
 */
class Filter
{
    /**
     * Method used to check whether the given custom filter is a
     * global one or not.
     *
     * @param   int $cst_id The custom filter ID
     * @return  bool
     */
    public static function isGlobal($cst_id)
    {
        $stmt = 'SELECT
                    COUNT(*)
                 FROM
                    `custom_filter`
                 WHERE
                    cst_id=? AND
                    cst_is_global=1';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$cst_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        if ($res == 1) {
            return true;
        }

        return false;
    }

    /**
     * Method used to check whether the given user is the owner of the custom
     * filter ID.
     *
     * @param   int $cst_id The custom filter ID
     * @param   int $usr_id The user ID
     * @return  bool
     */
    public static function isOwner($cst_id, $usr_id)
    {
        $stmt = 'SELECT
                    COUNT(*)
                 FROM
                    `custom_filter`
                 WHERE
                    cst_id=? AND
                    cst_usr_id=?';

        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$cst_id, $usr_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        if ($res == 1) {
            return true;
        }

        return false;
    }

    /**
     * Method used to save the changes made to an existing custom
     * filter, or to create a new custom filter.
     *
     * @return  int 1 if the update worked properly, any other value otherwise
     */
    public static function save()
    {
        $cst_id = self::getFilterID($_POST['title']);

        // loop through multi-select fields and prepare the values
        /**
         * @var $priority
         * @var $severity
         * @var $users
         * @var $reporter
         * @var $category
         * @var $status
         * @var $release
         * @var $product
         */

        foreach (self::getFiltersInfo() as $field => $filter) {
            if (@$filter['is_array'] == true) {
                $field_name = $filter['param'];
                $value = @$_POST[$field_name];
                if (is_array($value)) {
                    $value = implode(',', $value);
                }
                $$field_name = $value;
            }
        }

        // loop through all available date fields and prepare the values for the sql query
        $date_fields = [
            'created_date',
            'updated_date',
            'last_response_date',
            'first_response_date',
            'closed_date',
        ];

        /**
         * @var $created_date
         * @var $created_date_filter_type
         * @var $created_date_end
         * @var $updated_date
         * @var $updated_date_filter_type
         * @var $updated_date_end
         * @var $last_response_date
         * @var $last_response_date_filter_type
         * @var $last_response_date_end
         * @var $first_response_date
         * @var $first_response_date_filter_type
         * @var $first_response_date_end
         * @var $closed_date
         * @var $closed_date_filter_type
         * @var $closed_date_end
         */

        foreach ($date_fields as $field_name) {
            $date_var = $field_name;
            $filter_type_var = $field_name . '_filter_type';
            $date_end_var = $field_name . '_end';
            if (@$_POST['filter'][$field_name] == 'yes') {
                $$date_var = "'" . Misc::escapeString($_POST[$field_name]['Year'] . '-' . $_POST[$field_name]['Month'] . '-' . $_POST[$field_name]['Day']) . "'";
                $$filter_type_var = "'" . $_POST[$field_name]['filter_type'] . "'";
                if ($$filter_type_var == "'between'") {
                    $$date_end_var = "'" . Misc::escapeString($_POST[$date_end_var]['Year'] . '-' . $_POST[$date_end_var]['Month'] . '-' . $_POST[$date_end_var]['Day']) . "'";
                } elseif (($$filter_type_var == "'null'") || ($$filter_type_var == "'in_past'")) {
                    $$date_var = null;
                    $$date_end_var = null;
                } else {
                    $$date_end_var = null;
                }
            } else {
                $$date_var = null;
                $$filter_type_var = null;
                $$date_end_var = null;
            }
        }

        // save custom fields to search
        if ((is_array($_POST['custom_field'])) && (count($_POST['custom_field']) > 0)) {
            foreach ($_POST['custom_field'] as $fld_id => $search_value) {
                if (empty($search_value)) {
                    unset($_POST[$fld_id]);
                }
            }
            $custom_field_string = serialize($_POST['custom_field']);
        } else {
            $custom_field_string = '';
        }

        if (empty($_POST['is_global'])) {
            $is_global_filter = 0;
        } else {
            $is_global_filter = $_POST['is_global'];
        }

        if ($cst_id != 0) {
            $stmt = 'UPDATE
                        `custom_filter`
                     SET
                        cst_priorities=?,
                        cst_severities=?,
                        cst_keywords=?,
                        cst_users=?,
                        cst_reporters=?,
                        cst_statuses=?,
                        cst_releases=?,
                        cst_categories=?,
                        cst_products=?,
                        cst_rows=?,
                        cst_sort_by=?,
                        cst_sort_order=?,
                        cst_hide_closed=?,
                        cst_show_authorized=?,
                        cst_show_notification_list=?,
                        cst_created_date=?,
                        cst_created_date_filter_type=?,
                        cst_created_date_time_period=?,
                        cst_created_date_end=?,
                        cst_updated_date=?,
                        cst_updated_date_filter_type=?,
                        cst_updated_date_time_period=?,
                        cst_updated_date_end=?,
                        cst_last_response_date=?,
                        cst_last_response_date_filter_type=?,
                        cst_last_response_date_time_period=?,
                        cst_last_response_date_end=?,
                        cst_first_response_date=?,
                        cst_first_response_date_filter_type=?,
                        cst_first_response_date_time_period=?,
                        cst_first_response_date_end=?,
                        cst_closed_date=?,
                        cst_closed_date_filter_type=?,
                        cst_closed_date_time_period=?,
                        cst_closed_date_end=?,
                        cst_is_global=?,
                        cst_search_type=?,
                        cst_custom_field=?
                     WHERE
                        cst_id=?';
            $params = [
                $priority,
                $severity,
                $_POST['keywords'],
                $users,
                $reporter,
                $status,
                $release,
                $category,
                $product,
                $_POST['rows'],
                $_POST['sort_by'],
                $_POST['sort_order'],
                @$_POST['hide_closed'],
                @$_POST['show_authorized_issues'],
                @$_POST['show_notification_list_issues'],
                $created_date,
                $created_date_filter_type,
                @$_REQUEST['created_date']['time_period'],
                $created_date_end,
                $updated_date,
                $updated_date_filter_type,
                @$_REQUEST['updated_date']['time_period'],
                $updated_date_end,
                $last_response_date,
                $last_response_date_filter_type,
                @$_REQUEST['last_response_date']['time_period'],
                $last_response_date_end,
                $first_response_date,
                $first_response_date_filter_type,
                @$_REQUEST['first_response_date']['time_period'],
                $first_response_date_end,
                $closed_date,
                $closed_date_filter_type,
                @$_REQUEST['closed_date']['time_period'],
                $closed_date_end,
                $is_global_filter,
                $_POST['search_type'],
                $custom_field_string,
                $cst_id,
            ];
        } else {
            $stmt = 'INSERT INTO
                        `custom_filter`
                     (
                        cst_usr_id,
                        cst_prj_id,
                        cst_title,
                        cst_priorities,
                        cst_severities,
                        cst_keywords,
                        cst_users,
                        cst_reporters,
                        cst_statuses,
                        cst_releases,
                        cst_categories,
                        cst_products,
                        cst_rows,
                        cst_sort_by,
                        cst_sort_order,
                        cst_hide_closed,
                        cst_show_authorized,
                        cst_show_notification_list,
                        cst_created_date,
                        cst_created_date_filter_type,
                        cst_created_date_time_period,
                        cst_created_date_end,
                        cst_updated_date,
                        cst_updated_date_filter_type,
                        cst_updated_date_time_period,
                        cst_updated_date_end,
                        cst_last_response_date,
                        cst_last_response_date_filter_type,
                        cst_last_response_date_time_period,
                        cst_last_response_date_end,
                        cst_first_response_date,
                        cst_first_response_date_filter_type,
                        cst_first_response_date_time_period,
                        cst_first_response_date_end,
                        cst_closed_date,
                        cst_closed_date_filter_type,
                        cst_closed_date_time_period,
                        cst_closed_date_end,
                        cst_is_global,
                        cst_search_type,
                        cst_custom_field
                     ) VALUES (
                         ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                         ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                         ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                         ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                         ?
                     )';
            $params = [
                Auth::getUserID(),
                Auth::getCurrentProject(),
                $_POST['title'],
                $priority,
                $severity,
                $_POST['keywords'],
                $users,
                $reporter,
                $status,
                $release,
                $category,
                $product,
                $_POST['rows'],
                $_POST['sort_by'],
                $_POST['sort_order'],
                @$_POST['hide_closed'],
                @$_POST['show_authorized_issues'],
                @$_POST['show_notification_list_issues'],
                $created_date,
                $created_date_filter_type,
                @$_REQUEST['created_date']['time_period'],
                $created_date_end,
                $updated_date,
                $updated_date_filter_type,
                @$_REQUEST['updated_date']['time_period'],
                $updated_date_end,
                $last_response_date,
                $last_response_date_filter_type,
                @$_REQUEST['response_date']['time_period'],
                $last_response_date_end,
                $first_response_date,
                $first_response_date_filter_type,
                @$_REQUEST['first_response_date']['time_period'],
                $first_response_date_end,
                $closed_date,
                $closed_date_filter_type,
                @$_REQUEST['closed_date']['time_period'],
                $closed_date_end,
                $is_global_filter,
                $_POST['search_type'],
                $custom_field_string,
            ];
        }

        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Method used to get the filter ID associated with a specific
     * filter title.
     *
     * @param   string $cst_title The custom filter title
     * @return  int The custom filter ID
     */
    public static function getFilterID($cst_title)
    {
        $stmt = 'SELECT
                    cst_id
                 FROM
                    `custom_filter`
                 WHERE
                    cst_usr_id=? AND
                    cst_prj_id=? AND
                    cst_title=?';
        $params = [Auth::getUserID(), Auth::getCurrentProject(), $cst_title];
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, $params);
        } catch (DatabaseException $e) {
            return 0;
        }

        return $res;
    }

    /**
     * Method used to get an associative array of the full list of
     * custom filters (filter id => filter title) associated with the
     * current user and the current 'active' project.
     *
     * @return  array The full list of custom filters
     */
    public static function getAssocList()
    {
        $stmt = 'SELECT
                    cst_id,
                    cst_title
                 FROM
                    `custom_filter`
                 WHERE
                    cst_prj_id=? AND
                    (
                        cst_usr_id=? OR
                        cst_is_global=1
                    )
                 ORDER BY
                    cst_title';
        $params = [Auth::getCurrentProject(), Auth::getUserID()];
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, $params);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get an array of the full list of the custom
     * filters associated with the current user and the current
     * 'active' project.
     *
     * @param   bool $build_url if a URL for this filter should be constructed
     * @return  array The full list of custom filters
     */
    public static function getListing($build_url = false)
    {
        $stmt = 'SELECT
                    *
                 FROM
                    `custom_filter`
                 WHERE
                    cst_prj_id=? AND
                    (
                        cst_usr_id=? OR
                        cst_is_global=1
                    )
                 ORDER BY
                    cst_title';
        $params = [Auth::getCurrentProject(), Auth::getUserID()];
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, $params);
        } catch (DatabaseException $e) {
            return '';
        }

        if (count($res) > 0 && $build_url == true) {
            $filter_info = self::getFiltersInfo();
            foreach ($res as &$row) {
                $temp = self::removeCSTprefix($row);
                foreach ($filter_info as $filter_key => $filter) {
                    if (@$filter['is_array'] == true) {
                        $temp[$filter_key] = explode(',', $temp[$filter_key]);
                    }
                }
                $row['url'] = self::buildUrl($filter_info, $temp);
            }
        }

        return $res;
    }

    private static function removeCSTprefix($res)
    {
        $return = [];
        foreach ($res as $key => $val) {
            $return[str_replace('cst_', '', $key)] = $val;
        }

        return $return;
    }

    public static function buildUrl($filter_info, $options, $exclude_filter = false, $use_params = false)
    {
        $url = '';
        foreach ($filter_info as $field => $filter) {
            if ($use_params && isset($filter['param'], $options[$filter['param']])) {
                $value = $options[$filter['param']];
            } elseif (isset($options[$field])) {
                $value = $options[$field];
            } else {
                $value = null;
            }

            if ($field == $exclude_filter) {
                if ($field == 'hide_closed') {
                    $value = 0;
                } else {
                    continue;
                }
            }

            if (@$filter['is_date'] == true) {
                if (isset($value['filter_type'])) {
                    $url .= $filter['param'] . '[filter_type]=' . $value['filter_type'] . '&';
                    if ($value['filter_type'] == 'in_past') {
                        $url .= $filter['param'] . '[time_period]=' . $value['time_period'] . '&';
                    } else {
                        $url .= $filter['param'] . '[Year]=' . $value['Year'] . '&';
                        $url .= $filter['param'] . '[Month]=' . $value['Month'] . '&';
                        $url .= $filter['param'] . '[Day]=' . $value['Day'] . '&';

                        $end_date = $options[$field . '_end'];
                        if (!empty($end_date)) {
                            $url .= $filter['param'] . '_end[Year]=' . $end_date['Year'] . '&';
                            $url .= $filter['param'] . '_end[Month]=' . $end_date['Month'] . '&';
                            $url .= $filter['param'] . '_end[Day]=' . $end_date['Day'] . '&';
                        }
                    }
                }
            } elseif (@$filter['is_array'] == true) {
                foreach ($value as $id) {
                    $url .= $filter['param'] . '[]=' . $id . '&';
                }
            } else {
                if ((@$filter['is_custom'] != 1) && ($value !== null)) {
                    $url .= $filter['param'] . '=' . urlencode($value) . '&';
                }
            }
        }
        if (isset($options['custom_field'])) {
            if (is_array($options['custom_field'])) {
                $options['custom_field'] = serialize($options['custom_field']);
            }
            $url .= 'custom_field=' . urlencode($options['custom_field']) . '&';
        }
        if (isset($options['nosave'])) {
            $url .= 'nosave=' . $options['nosave'] . '&';
        }

        return $url;
    }

    /**
     * Method used to get an associative array of the full details of
     * a specific custom filter.
     *
     * @param   int $cst_id The custom filter ID
     * @param   bool $check_perm Whether to check for the permissions or not
     * @return  array The custom filter details
     */
    public static function getDetails($cst_id, $check_perm = true)
    {
        $stmt = 'SELECT
                    *
                 FROM
                    `custom_filter`
                 WHERE';
        $params = [];
        if ($check_perm) {
            $stmt .= '
                    cst_usr_id=? AND
                    cst_prj_id=? AND ';
            $params[] = Auth::getUserID();
            $params[] = Auth::getCurrentProject();
        }
        $stmt .= '
                    cst_id=?';
        $params[] = $cst_id;

        try {
            $res = DB_Helper::getInstance()->getRow($stmt, $params);
        } catch (DatabaseException $e) {
            return '';
        }

        foreach (self::getFiltersInfo() as $field => $filter) {
            if (@$filter['is_array'] == true) {
                $res['cst_' . $field] = explode(',', $res['cst_' . $field]);
            }
        }

        if (is_string($res['cst_custom_field'])) {
            $res['cst_custom_field'] = unserialize($res['cst_custom_field']);
        }

        return $res;
    }

    /**
     * Method used to remove specific custom filters.
     *
     * @return  int 1 if the removals worked properly, any other value otherwise
     */
    public static function remove()
    {
        foreach ($_POST['item'] as $cst_id) {
            $stmt = 'DELETE FROM
                        `custom_filter`
                     WHERE';
            $params = [];

            if (self::isGlobal($cst_id)) {
                if (Auth::getCurrentRole() >= User::ROLE_MANAGER) {
                    $stmt .= ' cst_is_global=1 AND ';
                } else {
                    $stmt .= '
                        cst_is_global=1 AND
                        cst_usr_id=? AND ';
                    $params[] = Auth::getUserID();
                }
            } else {
                $stmt .= ' cst_usr_id=? AND ';
                $params[] = Auth::getUserID();
            }
            $stmt .= '
                        cst_prj_id=? AND
                        cst_id=?';
            $params[] = Auth::getCurrentProject();
            $params[] = $cst_id;

            try {
                DB_Helper::getInstance()->query($stmt, $params);
            } catch (DatabaseException $e) {
                return -1;
            }
        }

        return 1;
    }

    /**
     * Returns an array of active filters
     *
     * @param   array $options The options array
     * @return array
     */
    public static function getActiveFilters($options)
    {
        $prj_id = Auth::getCurrentProject();
        $filter_info = self::getFiltersInfo();

        $return = [];

        foreach ($filter_info as $filter_key => $filter) {
            $display = false;

            if ((isset($filter['param'])) && (isset($options[$filter['param']]))) {
                $filter_details = $options[$filter['param']];
            }

            if (isset($filter['is_custom'])) {
                // custom fields
                $fld_id = $filter['fld_id'];
                if ((!isset($options['custom_field'][$fld_id])) || (empty($options['custom_field'][$fld_id]))) {
                    continue;
                } elseif (($filter['fld_type'] == 'date') && (empty($options['custom_field'][$fld_id]['Year']))) {
                    continue;
                } elseif ($filter['fld_type'] == 'integer') {
                    if ((!isset($options['custom_field'][$fld_id]['value'])) || (empty($options['custom_field'][$fld_id]['value']))) {
                        continue;
                    }
                    $filter_details = $options['custom_field'][$fld_id];
                    switch ($filter_details['filter_type']) {
                        case 'ge':
                            $display = ev_gettext('%1$s or greater', $filter_details['value']);
                            break;
                        case 'le':
                            $display = ev_gettext('%1$s or less', $filter_details['value']);
                            break;
                        case 'gt':
                            $display = ev_gettext('Greater than %1$s', $filter_details['value']);
                            break;
                        case 'lt':
                            $display = ev_gettext('Less than %1$s', $filter_details['value']);
                            break;
                        default:
                            $display = $filter_details['value'];
                    }
                } elseif (in_array($filter['fld_type'], ['multiple', 'combo', 'checkbox'])) {
                    $display = implode(', ', Custom_Field::getOptions($fld_id, $options['custom_field'][$fld_id]));
                } else {
                    $display = $options['custom_field'][$fld_id];
                }
            } elseif ((!isset($options[$filter['param']])) || (empty($options[$filter['param']])) ||
                    (in_array($filter_key, ['sort_order', 'sort_by', 'rows', 'search_type']))) {
                continue;
            } elseif ((isset($filter['is_date'])) && ($filter['is_date'] == true)) {
                if ((!empty($filter_details['Year'])) || (isset($filter_details['time_period']))) {
                    switch ($filter_details['filter_type']) {
                        case 'in_past':
                            $display = ev_gettext('In Past %1$s hours', $filter_details['time_period']);
                            break;
                        case 'null':
                            $display = ev_gettext('Is NULL');
                            break;
                        case 'between':
                            $end = $options[$filter['param'] . '_end'];
                            $display = ev_gettext('Is between %1$s-%2$s-%3$s AND %4$s-%5$s-%6$s', $filter_details['Year'], $filter_details['Month'],
                                            $filter_details['Day'], $end['Year'], $end['Month'], $end['Day']);
                            break;
                        case 'greater':
                            $display = ev_gettext('Is greater than %1$s-%2$s-%3$s', $filter_details['Year'], $filter_details['Month'], $filter_details['Day']);
                            break;
                        case 'less':
                            $display = ev_gettext('Is less than %1$s-%2$s-%3$s', $filter_details['Year'], $filter_details['Month'], $filter_details['Day']);
                    }
                }
            } elseif ($filter['param'] == 'status') {
                $statuses = Status::getAssocStatusList($prj_id);
                $active_statuses = [];
                foreach ($filter_details as $status) {
                    if ($status != '') {
                        $active_statuses[] = $statuses[$status];
                    }
                }
                $display = implode(', ', $active_statuses);
            } elseif ($filter['param'] == 'category') {
                $categories = Category::getAssocList($prj_id);
                $active_categories = [];
                foreach ($filter_details as $category) {
                    if ($category != '') {
                        $active_categories[] = $categories[$category];
                    }
                }
                $display = implode(', ', $active_categories);
            } elseif ($filter['param'] == 'priority') {
                $priorities = Priority::getAssocList($prj_id);
                $active_priorities = [];
                foreach ($filter_details as $priority) {
                    if ($priority != '') {
                        $active_priorities[] = $priorities[$priority];
                    }
                }
                $display = implode(', ', $active_priorities);
            } elseif ($filter['param'] == 'severity') {
                $severities = Severity::getAssocList($prj_id);
                $active_severities = [];
                foreach ($filter_details as $severity) {
                    if ($severity != '') {
                        $active_severities[] = $severities[$severity];
                    }
                }
                $display = implode(', ', $active_severities);
            } elseif ($filter['param'] == 'users') {
                $active_users = [];
                foreach ($filter_details as $user) {
                    if ($user == -1) {
                        $active_users[] = ev_gettext('un-assigned');
                    } elseif ($user == -2) {
                        $active_users[] = ev_gettext('myself and un-assigned');
                    } elseif ($user == -3) {
                        $active_users[] = ev_gettext('myself and my group');
                    } elseif ($user == -4) {
                        $active_users[] = ev_gettext('myself, un-assigned and my group');
                    } elseif (substr($user, 0, 3) == 'grp') {
                        $active_users[] = ev_gettext('%1$s Group', Group::getName(substr($user, 4)));
                    } else {
                        $active_users[] = User::getFullName($user);
                    }
                }
                $display = implode(', ', $active_users);
            } elseif ($filter['param'] == 'hide_closed') {
                if ($filter_details == true) {
                    $display = ev_gettext('Yes');
                }
            } elseif ($filter['param'] == 'reporter') {
                $active_reporters = [];
                foreach ($filter_details as $reporter) {
                    if ($reporter != '') {
                        $active_reporters[] = User::getFullName($reporter);
                    }
                }
                $display = implode(', ', $active_reporters);
            } elseif ($filter['param'] == 'release') {
                $active_releases = [];
                foreach ($filter_details as $release) {
                    if ($release != '') {
                        $active_releases[] = Release::getTitle($release);
                    }
                }
                $display = implode(', ', $active_releases);
            } elseif ($filter['param'] == 'customer_id') {
                try {
                    $crm = CRM::getInstance($prj_id);
                    $customer = $crm->getCustomer($filter_details);
                    $display = $customer->getName();
                } catch (CRMException $e) {
                    $display = $filter_details;
                }
            } elseif ($filter['param'] == 'product') {
                $active_products = [];
                foreach ($filter_details as $product) {
                    if ($product != '') {
                        $active_products[] = Product::getTitle($product);
                    }
                }
                $display = implode(', ', $active_products);
            } else {
                $display = $filter_details;
            }

            if ($display != false) {
                $return[$filter['title']] = [
                    'value' => $display,
                    'remove_link' => 'list.php?view=clearandfilter&' . self::buildUrl($filter_info, $options, $filter_key, true),
                ];
            }
        }

        return $return;
    }

    /**
     * Returns an array of information about all the different filter fields.
     *
     * @return  array an array of information
     */
    public static function getFiltersInfo()
    {
        // format is "name_of_db_field" => array(
        //      "title" => human readable title,
        //      "param" => name that appears in get, post or cookie
        $fields = [
            'priorities' => [
                'title' => ev_gettext('Priority'),
                'param' => 'priority',
                'quickfilter' => true,
                'is_array' => true,
            ],
            'severities' => [
                'title' => ev_gettext('Severity'),
                'param' => 'severity',
                'quickfilter' => true,
                'is_array' => true,
            ],
            'keywords' => [
                'title' => ev_gettext('Keyword(s)'),
                'param' => 'keywords',
                'quickfilter' => true,
            ],
            'users' => [
                'title' => ev_gettext('Assigned'),
                'param' => 'users',
                'quickfilter' => true,
                'is_array' => true,
            ],
            'categories' => [
                'title' => ev_gettext('Category'),
                'param' => 'category',
                'quickfilter' => true,
                'is_array' => true,
            ],
            'statuses' => [
                'title' => ev_gettext('Status'),
                'param' => 'status',
                'quickfilter' => true,
                'is_array' => true,
            ],
            'releases' => [
                'title' => ev_gettext('Release'),
                'param' => 'release',
                'is_array' => true,
            ],
            'created_date' => [
                'title' => ev_gettext('Created Date'),
                'param' => 'created_date',
                'is_date' => true,
            ],
            'updated_date' => [
                'title' => ev_gettext('Updated Date'),
                'param' => 'updated_date',
                'is_date' => true,
            ],
            'last_response_date' => [
                'title' => ev_gettext('Last Response Date'),
                'param' => 'last_response_date',
                'is_date' => true,
            ],
            'first_response_date' => [
                'title' => ev_gettext('First Response Date'),
                'param' => 'first_response_date',
                'is_date' => true,
            ],
            'closed_date' => [
                'title' => ev_gettext('Closed Date'),
                'param' => 'closed_date',
                'is_date' => true,
            ],
            'rows' => [
                'title' => ev_gettext('Rows Per Page'),
                'param' => 'rows',
            ],
            'sort_by' => [
                'title' => ev_gettext('Sort By'),
                'param' => 'sort_by',
            ],
            'sort_order' => [
                'title' => ev_gettext('Sort Order'),
                'param' => 'sort_order',
            ],
            'hide_closed' => [
                'title' => ev_gettext('Hide Closed Issues'),
                'param' => 'hide_closed',
            ],
            'show_authorized' => [
                'title' => ev_gettext('Authorized to Send Emails'),
                'param' => 'show_authorized_issues',
            ],
            'show_notification_list' => [
                'title' => ev_gettext('In Notification List'),
                'param' => 'show_notification_list_issues',
            ],
            'search_type' => [
                'title' => ev_gettext('Search Type'),
                'param' => 'search_type',
            ],
            'reporters' => [
                'title' => ev_gettext('Reporter'),
                'param' => 'reporter',
                'is_array' => true,
            ],
            'customer_id' => [
                'title' => ev_gettext('Customer'),
                'param' => 'customer_id',
            ],
            'products' => [
                'title' => ev_gettext('Product'),
                'param' => 'product',
                'is_array' => true,
            ],
        ];

        // add custom fields
        $custom_fields = Custom_Field::getFieldsByProject(Auth::getCurrentProject());
        if (count($custom_fields) > 0) {
            foreach ($custom_fields as $fld_id) {
                $field = Custom_Field::getDetails($fld_id);
                $fields['custom_field_' . $fld_id] = [
                    'title' => $field['fld_title'],
                    'is_custom' => 1,
                    'fld_id' => $fld_id,
                    'fld_type' => $field['fld_type'],
                ];
            }
        }

        return $fields;
    }
}
