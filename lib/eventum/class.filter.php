<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2012 Eventum Team.                              |
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


/**
 * Class to handle the business logic related to the custom filters.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

class Filter
{
    /**
     * Method used to check whether the given custom filter is a
     * global one or not.
     *
     * @access  public
     * @param   integer $cst_id The custom filter ID
     * @return  boolean
     */
    function isGlobal($cst_id)
    {
        $stmt = "SELECT
                    COUNT(*)
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_filter
                 WHERE
                    cst_id=" . Misc::escapeInteger($cst_id) . " AND
                    cst_is_global=1";
        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            if ($res == 1) {
                return true;
            } else {
                return false;
            }
        }
    }


    /**
     * Method used to check whether the given user is the owner of the custom
     * filter ID.
     *
     * @access  public
     * @param   integer $cst_id The custom filter ID
     * @param   integer $usr_id The user ID
     * @return  boolean
     */
    function isOwner($cst_id, $usr_id)
    {
        $stmt = "SELECT
                    COUNT(*)
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_filter
                 WHERE
                    cst_id=" . Misc::escapeInteger($cst_id) . " AND
                    cst_usr_id=" . Misc::escapeInteger($usr_id);
        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            if ($res == 1) {
                return true;
            } else {
                return false;
            }
        }
    }


    /**
     * Method used to save the changes made to an existing custom
     * filter, or to create a new custom filter.
     *
     * @access  public
     * @return  integer 1 if the update worked properly, any other value otherwise
     */
    function save()
    {
        $cst_id = self::getFilterID($_POST["title"]);
        // loop through all available date fields and prepare the values for the sql query
        $date_fields = array(
            'created_date',
            'updated_date',
            'last_response_date',
            'first_response_date',
            'closed_date'
        );
        foreach ($date_fields as $field_name) {
            $date_var = $field_name;
            $filter_type_var = $field_name . '_filter_type';
            $date_end_var = $field_name . '_end';
            if (@$_POST['filter'][$field_name] == 'yes') {
                $$date_var = "'" . Misc::escapeString($_POST[$field_name]["Year"] . "-" . $_POST[$field_name]["Month"] . "-" . $_POST[$field_name]["Day"]) . "'";
                $$filter_type_var = "'" . $_POST[$field_name]['filter_type'] . "'";
                if ($$filter_type_var == "'between'") {
                    $$date_end_var = "'" . Misc::escapeString($_POST[$date_end_var]["Year"] . "-" . $_POST[$date_end_var]["Month"] . "-" . $_POST[$date_end_var]["Day"]) . "'";
                } elseif (($$filter_type_var == "'null'") || ($$filter_type_var == "'in_past'")) {
                    $$date_var = "NULL";
                    $$date_end_var = "NULL";
                } else {
                    $$date_end_var = "NULL";
                }
            } else {
                $$date_var = 'NULL';
                $$filter_type_var = "NULL";
                $$date_end_var = 'NULL';
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
            $stmt = "UPDATE
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_filter
                     SET
                        cst_iss_pri_id='" . Misc::escapeInteger(@$_POST["priority"]) . "',
                        cst_iss_sev_id='" . Misc::escapeInteger(@$_POST["severity"]) . "',
                        cst_keywords='" . Misc::escapeString($_POST["keywords"]) . "',
                        cst_users='" . Misc::escapeString($_POST["users"]) . "',
                        cst_reporter=" . Misc::escapeInteger($_POST["reporter"]) . ",
                        cst_iss_sta_id='" . Misc::escapeInteger($_POST["status"]) . "',
                        cst_iss_pre_id='" . Misc::escapeInteger(@$_POST["release"]) . "',
                        cst_iss_prc_id='" . Misc::escapeInteger(@$_POST["category"]) . "',
                        cst_pro_id='" . Misc::escapeInteger(@$_POST["product"]) . "',
                        cst_rows='" . Misc::escapeString($_POST["rows"]) . "',
                        cst_sort_by='" . Misc::escapeString($_POST["sort_by"]) . "',
                        cst_sort_order='" . Misc::escapeString($_POST["sort_order"]) . "',
                        cst_hide_closed='" . Misc::escapeInteger(@$_POST["hide_closed"]) . "',
                        cst_show_authorized='" . Misc::escapeString(@$_POST["show_authorized_issues"]) . "',
                        cst_show_notification_list='" . Misc::escapeString(@$_POST["show_notification_list_issues"]) . "',
                        cst_created_date=$created_date,
                        cst_created_date_filter_type=$created_date_filter_type,
                        cst_created_date_time_period='" . @Misc::escapeInteger(@$_REQUEST['created_date']['time_period']) . "',
                        cst_created_date_end=$created_date_end,
                        cst_updated_date=$updated_date,
                        cst_updated_date_filter_type=$updated_date_filter_type,
                        cst_updated_date_time_period='" . @Misc::escapeInteger(@$_REQUEST['updated_date']['time_period']) . "',
                        cst_updated_date_end=$updated_date_end,
                        cst_last_response_date=$last_response_date,
                        cst_last_response_date_filter_type=$last_response_date_filter_type,
                        cst_last_response_date_time_period='" .@ Misc::escapeInteger(@$_REQUEST['last_response_date']['time_period']) . "',
                        cst_last_response_date_end=$last_response_date_end,
                        cst_first_response_date=$first_response_date,
                        cst_first_response_date_filter_type=$first_response_date_filter_type,
                        cst_first_response_date_time_period='" . @Misc::escapeInteger(@$_REQUEST['first_response_date']['time_period']) . "',
                        cst_first_response_date_end=$first_response_date_end,
                        cst_closed_date=$closed_date,
                        cst_closed_date_filter_type=$closed_date_filter_type,
                        cst_closed_date_time_period='" . @Misc::escapeInteger(@$_REQUEST['closed_date']['time_period']) . "',
                        cst_closed_date_end=" . Misc::escapeString($closed_date_end) . ",
                        cst_is_global=" . Misc::escapeInteger($is_global_filter) . ",
                        cst_search_type='" . Misc::escapeString($_POST['search_type']) . "',
                        cst_custom_field='" . Misc::escapeString($custom_field_string) . "'
                     WHERE
                        cst_id=$cst_id";
        } else {
            $stmt = "INSERT INTO
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_filter
                     (
                        cst_usr_id,
                        cst_prj_id,
                        cst_title,
                        cst_iss_pri_id,
                        cst_iss_sev_id,
                        cst_keywords,
                        cst_users,
                        cst_reporter,
                        cst_iss_sta_id,
                        cst_iss_pre_id,
                        cst_iss_prc_id,
                        cst_pro_id,
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
                        " . Auth::getUserID() . ",
                        " . Auth::getCurrentProject() . ",
                        '" . Misc::escapeString($_POST["title"]) . "',
                        '" . Misc::escapeInteger(@$_POST["priority"]) . "',
                        '" . Misc::escapeInteger(@$_POST["severity"]) . "',
                        '" . Misc::escapeString($_POST["keywords"]) . "',
                        '" . Misc::escapeString($_POST["users"]) . "',
                        '" . Misc::escapeInteger($_POST["reporter"]) . "',
                        '" . Misc::escapeInteger($_POST["status"]) . "',
                        '" . Misc::escapeInteger(@$_POST["release"]) . "',
                        '" . Misc::escapeInteger(@$_POST["category"]) . "',
                        '" . Misc::escapeInteger(@$_POST["product"]) . "',
                        '" . Misc::escapeString($_POST["rows"]) . "',
                        '" . Misc::escapeString($_POST["sort_by"]) . "',
                        '" . Misc::escapeString($_POST["sort_order"]) . "',
                        '" . Misc::escapeInteger(@$_POST["hide_closed"]) . "',
                        '" . Misc::escapeString(@$_POST["show_authorized_issues"]) . "',
                        '" . Misc::escapeString(@$_POST["show_notification_list_issues"]) . "',
                        $created_date,
                        $created_date_filter_type,
                        '" . @Misc::escapeInteger(@$_REQUEST['created_date']['time_period']) . "',
                        $created_date_end,
                        $updated_date,
                        $updated_date_filter_type,
                        '" . @Misc::escapeInteger(@$_REQUEST['updated_date']['time_period']) . "',
                        $updated_date_end,
                        $last_response_date,
                        $last_response_date_filter_type,
                        '" . @Misc::escapeInteger(@$_REQUEST['response_date']['time_period']) . "',
                        $last_response_date_end,
                        $first_response_date,
                        $first_response_date_filter_type,
                        '" . @Misc::escapeInteger(@$_REQUEST['first_response_date']['time_period']) . "',
                        $first_response_date_end,
                        $closed_date,
                        $closed_date_filter_type,
                        '" . @Misc::escapeInteger(@$_REQUEST['closed_date']['time_period']) . "',
                        $closed_date_end,
                        " . Misc::escapeInteger($is_global_filter) . ",
                        '" . Misc::escapeString($_POST['search_type']) . "',
                        '" . Misc::escapeString($custom_field_string) . "'
                     )";
        }
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            return 1;
        }
    }


    /**
     * Method used to get the filter ID associated with a specific
     * filter title.
     *
     * @access  public
     * @param   string $cst_title The custom filter title
     * @return  integer The custom filter ID
     */
    function getFilterID($cst_title)
    {
        $stmt = "SELECT
                    cst_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_filter
                 WHERE
                    cst_usr_id=" . Auth::getUserID() . " AND
                    cst_prj_id=" . Auth::getCurrentProject() . " AND
                    cst_title='" . Misc::escapeString($cst_title) . "'";
        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return 0;
        } else {
            return $res;
        }
    }


    /**
     * Method used to get an associative array of the full list of
     * custom filters (filter id => filter title) associated with the
     * current user and the current 'active' project.
     *
     * @access  public
     * @return  array The full list of custom filters
     */
    function getAssocList()
    {
        $stmt = "SELECT
                    cst_id,
                    cst_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_filter
                 WHERE
                    cst_prj_id=" . Auth::getCurrentProject() . " AND
                    (
                        cst_usr_id=" . Auth::getUserID() . " OR
                        cst_is_global=1
                    )
                 ORDER BY
                    cst_title";
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get an array of the full list of the custom
     * filters associated with the current user and the current
     * 'active' project.
     *
     * @access  public
     * @param   boolean $build_url If a URL for this filter should be constructed.
     * @return  array The full list of custom filters
     */
    function getListing($build_url = false)
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_filter
                 WHERE
                    cst_prj_id=" . Auth::getCurrentProject() . " AND
                    (
                        cst_usr_id=" . Auth::getUserID() . " OR
                        cst_is_global=1
                    )
                 ORDER BY
                    cst_title";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            if ((count($res) > 0) && ($build_url == true)) {
                $filter_info = self::getFiltersInfo();
                for ($i = 0; $i < count($res); $i++) {
                    $res[$i]['url'] = Filter::buildUrl($filter_info, self::removeCSTprefix($res[$i]));
                }
            }

            return $res;
        }
    }

    private static function removeCSTprefix($res)
    {
        $return = array();
        foreach ($res as $key => $val) {
            $return[str_replace('cst_', '', $key)] = $val;
        }
        return $return;
    }


    public static function buildUrl($filter_info, $options, $exclude_filter=false, $use_params=false)
    {
        $url = '';
        foreach ($filter_info as $field => $filter) {
            if ($use_params && isset($filter['param']) && isset($options[$filter['param']])) {
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
                        $url .= $filter['param']  . '[Year]=' . $value['Year'] . '&';
                        $url .= $filter['param']  . '[Month]=' . $value['Month'] . '&';
                        $url .= $filter['param']  . '[Day]=' . $value['Day'] . '&';

                        $end_date = $options[$field . '_end'];
                        if (!empty($end_date)) {
                            $url .= $filter['param']  . '_end[Year]=' . $end_date['Year'] . '&';
                            $url .= $filter['param']  . '_end[Month]=' . $end_date['Month'] . '&';
                            $url .= $filter['param']  . '_end[Day]=' . $end_date['Day'] . '&';
                        }
                    }
                }
            } else {
                if ((@$filter['is_custom'] != 1) && ($value !== null)) {
                    $url .= $filter['param'] . '=' . urlencode($value) . '&';
                }
            }
        }
        if (isset($options['custom_field'])) {
            $url .= 'custom_field=' . urlencode($options['custom_field']);
        }

        return $url;
    }


    /**
     * Takes the saved search details and information about filters and returns an array of
     * of the saved search information.
     *
     * @access  private
     * @param   array $details An array of information about the saved search, usually the direct row from the database.
     * @param   array $info An array of information about filters
     * @return  array An array of information about the saved search.
     */
    function buildOptions($details, $info)
    {
        $options = array();
        foreach ($info as $field => $filter) {
            if (@$filter['is_date'] == true) {
                $options[$filter['param']]['filter_type'] =  $details['cst_' . $field . '_filter_type'];
                if ($details['cst_' . $field . '_filter_type'] == 'in_past') {
                    $options[$filter['param']]['time_period'] = $details['cst_' . $field . '_time_period'] . '&';
                } else {
                    $start_date = $details['cst_' . $field];
                    if (!empty($start_date)) {
                        $start_date_parts = explode("-", $start_date);
                        $options[$filter['param']]['Year'] = $start_date_parts[0];
                        $options[$filter['param']]['Month'] = $start_date_parts[1];
                        $options[$filter['param']]['Day'] = $start_date_parts[2];
                    }
                    $end_date = $details['cst_' . $field . '_end'];
                    if (!empty($end_date)) {
                        $end_date_parts = explode("-", $end_date);
                        $options[$filter['param'] . '_end']['Year'] = $end_date_parts[0];
                        $options[$filter['param'] . '_end']['Month'] = $end_date_parts[1];
                        $options[$filter['param'] . '_end']['Day'] = $end_date_parts[2];
                    }
                }
            } else {
                if (@$filter['is_custom'] != 1) {
                    $options[$filter['param']] = $details['cst_' . $field];
                }
            }
        }
        $options['custom_field'] = $details['cst_custom_field'];
        return $options;
    }


    /**
     * Method used to get an associative array of the full details of
     * a specific custom filter.
     *
     * @access  public
     * @param   integer $cst_id The custom filter ID
     * @param   boolean $check_perm Whether to check for the permissions or not
     * @return  array The custom filter details
     */
    function getDetails($cst_id, $check_perm = TRUE)
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_filter
                 WHERE";
        if ($check_perm) {
            $stmt .= "
                    cst_usr_id=" . Auth::getUserID() . " AND
                    cst_prj_id=" . Auth::getCurrentProject() . " AND ";
        }
        $stmt .= "
                    cst_id=$cst_id";
        $res = DB_Helper::getInstance()->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            if (is_string($res['cst_custom_field'])) {
                $res['cst_custom_field'] = unserialize($res['cst_custom_field']);
            }
            return $res;
        }
    }


    /**
     * Method used to remove specific custom filters.
     *
     * @access  public
     * @return  integer 1 if the removals worked properly, any other value otherwise
     */
    function remove()
    {
        $items = implode(", ", Misc::escapeInteger($_POST["item"]));
        foreach ($_POST["item"] as $cst_id) {
            $stmt = "DELETE FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_filter
                     WHERE";
            if (self::isGlobal($cst_id)) {
                if (Auth::getCurrentRole() >= User::getRoleID('Manager')) {
                    $stmt .= " cst_is_global=1 AND ";
                } else {
                    $stmt .= "
                        cst_is_global=1 AND
                        cst_usr_id=" . Auth::getUserID() . " AND ";
                }
            } else {
                $stmt .= " cst_usr_id=" . Auth::getUserID() . " AND ";
            }
            $stmt .= "
                        cst_prj_id=" . Auth::getCurrentProject() . " AND
                        cst_id=$cst_id";
            $res = DB_Helper::getInstance()->query($stmt);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return -1;
            }
        }
        return 1;
    }


    /**
     * Method used to remove all custom filters associated with some
     * specific projects.
     *
     * @access  public
     * @param   array $ids List of projects to remove from
     * @return  boolean Whether the removal worked properly or not
     */
    function removeByProjects($ids)
    {
        $items = implode(", ", Misc::escapeInteger($ids));
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_filter
                 WHERE
                    cst_prj_id IN ($items)";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }

    /**
     * Returns an array of active filters
     *
     * @param   array $options The options array
     * @return array
     */
    function getActiveFilters($options)
    {
        $prj_id = Auth::getCurrentProject();
        $filter_info = self::getFiltersInfo();

        $return = array();

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
                    } else {
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
                    }
                } elseif (in_array($filter['fld_type'], array('multiple', 'combo'))) {
                    $display = join(', ', Custom_Field::getOptions($fld_id, $options['custom_field'][$fld_id]));
                } else {
                    $display = $options['custom_field'][$fld_id];
                }
            } elseif ((!isset($options[$filter['param']])) || (empty($options[$filter['param']])) ||
                    (in_array($filter_key, array('sort_order', 'sort_by', 'rows', 'search_type')))) {
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
                $display = $statuses[$filter_details];
            } elseif ($filter['param'] == 'category') {
                $categories = Category::getAssocList($prj_id);
                if (is_array($filter_details)) {
                    $active_categories = array();
                    foreach ($filter_details as $category) {
                        $active_categories[] = $categories[$category];
                    }
                    $display = join(', ', $active_categories);
                } else {
                    $display = $categories[$filter_details];
                }
            } elseif ($filter['param'] == 'priority') {
                $priorities = Priority::getAssocList($prj_id);
                $display = $priorities[$filter_details];
            } elseif ($filter['param'] == 'severity') {
                $severities = Severity::getAssocList($prj_id);
                $display = $severities[$filter_details];
            } elseif ($filter['param'] == 'users') {
                if ($filter_details == -1) {
                    $display = ev_gettext('un-assigned');
                } elseif ($filter_details == -2) {
                    $display = ev_gettext('myself and un-assigned');
                } elseif ($filter_details == -3) {
                    $display = ev_gettext('myself and my group');
                } elseif ($filter_details == -4) {
                    $display = ev_gettext('myself, un-assigned and my group');
                } elseif (substr($filter_details, 0, 3) == 'grp') {
                    $display = ev_gettext('%1$s Group', Group::getName(substr($filter_details, 4)));
                } else {
                    $display = User::getFullName($filter_details);
                }
            } elseif ($filter['param'] == 'hide_closed') {
                if ($filter_details == true) {
                    $display = ev_gettext('Yes');
                }
            } elseif ($filter['param'] == 'reporter') {
                $display = User::getFullName($filter_details);
            } elseif ($filter['param'] == 'release') {
                $display = Release::getTitle($filter_details);
            } elseif ($filter['param'] == 'customer_id') {
                $display = Customer::getTitle($prj_id, $filter_details);
            } elseif ($filter['param'] == 'product') {
                $display = Product::getTitle($filter_details);
            } else {
                $display = $filter_details;
            }

            if ($display != false) {
                $return[$filter['title']] = array(
                    'value' =>  $display,
                    'remove_link'   =>  'list.php?view=clearandfilter&' . Filter::buildUrl($filter_info, $options, $filter_key, true)
                );
            }
        }
        return $return;
    }


    /**
     * Returns an array of information about all the different filter fields.
     *
     * @access  public
     * @return  Array an array of information.
     */
    function getFiltersInfo()
    {
        // format is "name_of_db_field" => array(
        //      "title" => human readable title,
        //      "param" => name that appears in get, post or cookie
        $fields = array(
            'iss_pri_id'    =>  array(
                'title' =>  ev_gettext("Priority"),
                'param' =>  'priority',
                'quickfilter'   =>  true
            ),
            'iss_sev_id'    =>  array(
                'title' =>  ev_gettext("Severity"),
                'param' =>  'severity',
                'quickfilter'   =>  true
            ),
            'keywords'  =>  array(
                'title' =>  ev_gettext("Keyword(s)"),
                'param' =>  'keywords',
                'quickfilter'   =>  true
            ),
            'users' =>  array(
                'title' =>  ev_gettext("Assigned"),
                'param' =>  'users',
                'quickfilter'   =>  true
            ),
            'iss_prc_id'    =>  array(
                'title' =>  ev_gettext("Category"),
                'param' =>  'category',
                'quickfilter'   =>  true
            ),
            'iss_sta_id'    =>  array(
                'title' =>  ev_gettext("Status"),
                'param' =>  'status',
                'quickfilter'   =>  true
            ),
            'iss_pre_id'    =>  array(
                'title' =>  ev_gettext("Release"),
                'param' =>  'release'
            ),
            'created_date'  =>  array(
                'title' =>  ev_gettext("Created Date"),
                'param' =>  'created_date',
                'is_date'   =>  true
            ),
            'updated_date'  =>  array(
                'title' =>  ev_gettext("Updated Date"),
                'param' =>  'updated_date',
                'is_date'   =>  true
            ),
            'last_response_date'  =>  array(
                'title' =>  ev_gettext("Last Response Date"),
                'param' =>  'last_response_date',
                'is_date'   =>  true
            ),
            'first_response_date'  =>  array(
                'title' =>  ev_gettext("First Response Date"),
                'param' =>  'first_response_date',
                'is_date'   =>  true
            ),
            'closed_date'  =>  array(
                'title' =>  ev_gettext("Closed Date"),
                'param' =>  'closed_date',
                'is_date'   =>  true
            ),
            'rows'  =>  array(
                'title' =>  ev_gettext("Rows Per Page"),
                'param' =>  'rows'
            ),
            'sort_by'   =>  array(
                'title' =>  ev_gettext("Sort By"),
                'param' =>  'sort_by'
            ),
            'sort_order'    =>  array(
                'title' =>  ev_gettext("Sort Order"),
                'param' =>  'sort_order',
            ),
            'hide_closed'   =>  array(
                'title' =>  ev_gettext("Hide Closed Issues"),
                'param' =>  'hide_closed'
            ),
            'show_authorized'   =>  array(
                'title' =>  ev_gettext("Authorized to Send Emails"),
                'param' =>  'show_authorized_issues'
            ),
            'show_notification_list'    =>  array(
                'title' =>  ev_gettext("In Notification List"),
                'param' =>  'show_notification_list_issues'
            ),
            'search_type'   =>  array(
                'title' =>  ev_gettext("Search Type"),
                'param' =>  'search_type'
            ),
            'reporter'  =>  array(
                'title' =>  ev_gettext("Reporter"),
                'param' =>  'reporter'
            ),
            'customer_id'=>  array(
                'title' =>  ev_gettext("Customer"),
                'param' =>  'customer_id'
            ),
            'pro_id'   =>  array(
                'title' =>  ev_gettext("Product"),
                'param' =>  'product'
            )
        );

        // add custom fields
        $custom_fields = Custom_Field::getFieldsByProject(Auth::getCurrentProject());
        if (count($custom_fields) > 0) {
            foreach ($custom_fields as $fld_id) {
                $field = Custom_Field::getDetails($fld_id);
                $fields['custom_field_' . $fld_id] = array(
                    'title' =>  $field['fld_title'],
                    'is_custom' =>  1,
                    'fld_id'    =>  $fld_id,
                    'fld_type'  =>  $field['fld_type']
                );
            }
        }

        return $fields;
    }
}
