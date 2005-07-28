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
// @(#) $Id: s.class.filter.php 1.18 04/01/08 15:36:38-00:00 jpradomaia $
//

include_once(APP_INC_PATH . "class.error_handler.php");
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.date.php");

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
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
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
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
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
        global $HTTP_POST_VARS;

        $cst_id = Filter::getFilterID($HTTP_POST_VARS["title"]);
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
            if (@$HTTP_POST_VARS['filter'][$field_name] == 'yes') {
                $$date_var = "'" . Misc::escapeString($HTTP_POST_VARS[$field_name]["Year"] . "-" . $HTTP_POST_VARS[$field_name]["Month"] . "-" . $HTTP_POST_VARS[$field_name]["Day"]) . "'";
                $$filter_type_var = "'" . $HTTP_POST_VARS[$field_name]['filter_type'] . "'";
                if ($$filter_type_var == "'between'") {
                    $$date_end_var = "'" . Misc::escapeString($HTTP_POST_VARS[$date_end_var]["Year"] . "-" . $HTTP_POST_VARS[$date_end_var]["Month"] . "-" . $HTTP_POST_VARS[$date_end_var]["Day"]) . "'";
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
        if ((is_array($HTTP_POST_VARS['custom_field'])) && (count($HTTP_POST_VARS['custom_field']) > 0)) {
            foreach ($HTTP_POST_VARS['custom_field'] as $fld_id => $search_value) {
                if (empty($search_value)) {
                    unset($HTTP_POST_VARS[$fld_id]);
                }
            }
            $custom_field_string = serialize($HTTP_POST_VARS['custom_field']);
        } else {
            $custom_field_string = '';
        }
        
        if (empty($HTTP_POST_VARS['is_global'])) {
            $is_global_filter = 0;
        } else {
            $is_global_filter = $HTTP_POST_VARS['is_global'];
        }
        if ($cst_id != 0) {
            $stmt = "UPDATE
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_filter
                     SET
                        cst_iss_pri_id='" . Misc::escapeInteger($HTTP_POST_VARS["priority"]) . "',
                        cst_keywords='" . Misc::escapeString($HTTP_POST_VARS["keywords"]) . "',
                        cst_users='" . Misc::escapeString($HTTP_POST_VARS["users"]) . "',
                        cst_iss_sta_id='" . Misc::escapeInteger($HTTP_POST_VARS["status"]) . "',
                        cst_iss_pre_id='" . Misc::escapeInteger(@$HTTP_POST_VARS["release"]) . "',
                        cst_iss_prc_id='" . Misc::escapeInteger(@$HTTP_POST_VARS["category"]) . "',
                        cst_rows='" . Misc::escapeString($HTTP_POST_VARS["rows"]) . "',
                        cst_sort_by='" . Misc::escapeString($HTTP_POST_VARS["sort_by"]) . "',
                        cst_sort_order='" . Misc::escapeString($HTTP_POST_VARS["sort_order"]) . "',
                        cst_hide_closed='" . Misc::escapeInteger(@$HTTP_POST_VARS["hide_closed"]) . "',
                        cst_show_authorized='" . Misc::escapeString(@$HTTP_POST_VARS["show_authorized_issues"]) . "',
                        cst_show_notification_list='" . Misc::escapeString(@$HTTP_POST_VARS["show_notification_list_issues"]) . "',
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
                        cst_search_type='" . Misc::escapeString($HTTP_POST_VARS['search_type']) . "',
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
                        cst_keywords,
                        cst_users,
                        cst_iss_sta_id,
                        cst_iss_pre_id,
                        cst_iss_prc_id,
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
                        '" . Misc::escapeString($HTTP_POST_VARS["title"]) . "',
                        '" . Misc::escapeInteger($HTTP_POST_VARS["priority"]) . "',
                        '" . Misc::escapeString($HTTP_POST_VARS["keywords"]) . "',
                        '" . Misc::escapeString($HTTP_POST_VARS["users"]) . "',
                        '" . Misc::escapeInteger($HTTP_POST_VARS["status"]) . "',
                        '" . Misc::escapeInteger(@$HTTP_POST_VARS["release"]) . "',
                        '" . Misc::escapeInteger(@$HTTP_POST_VARS["category"]) . "',
                        '" . Misc::escapeString($HTTP_POST_VARS["rows"]) . "',
                        '" . Misc::escapeString($HTTP_POST_VARS["sort_by"]) . "',
                        '" . Misc::escapeString($HTTP_POST_VARS["sort_order"]) . "',
                        '" . Misc::escapeInteger(@$HTTP_POST_VARS["hide_closed"]) . "',
                        '" . Misc::escapeString(@$HTTP_POST_VARS["show_authorized_issues"]) . "',
                        '" . Misc::escapeString(@$HTTP_POST_VARS["show_notification_list_issues"]) . "',
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
                        '" . Misc::escapeString($HTTP_POST_VARS['search_type']) . "',
                        '" . Misc::escapeString($custom_field_string) . "'
                     )";
        }
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
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
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
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
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
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
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            if ((count($res) > 0) && ($build_url == true)) {
                $filter_info = Filter::getFiltersInfo();
                for ($i = 0; $i < count($res); $i++) {
                    $res[$i]['url'] = '';
                    foreach ($filter_info as $field => $filter) {
                        if (@$filter['is_date'] == true) {
                            $res[$i]['url'] .= $filter['param'] . '[filter_type]=' . $res[$i]['cst_' . $field . '_filter_type'] . '&';
                            if ($res[$i]['cst_' . $field . '_filter_type'] == 'in_past') {
                                $res[$i]['url'] .= $filter['param'] . '[time_period]=' . $res[$i]['cst_' . $field . '_time_period'] . '&';
                            } else {
                                $start_date = $res[$i]['cst_' . $field];
                                if (!empty($start_date)) {
                                    $start_date_parts = explode("-", $start_date);
                                    $res[$i]['url'] .= $filter['param']  . '[Year]=' . $start_date_parts[0] . '&';
                                    $res[$i]['url'] .= $filter['param']  . '[Month]=' . $start_date_parts[1] . '&';
                                    $res[$i]['url'] .= $filter['param']  . '[Day]=' . $start_date_parts[2] . '&';
                                }
                                $end_date = $res[$i]['cst_' . $field . '_end'];
                                if (!empty($end_date)) {
                                    $end_date_parts = explode("-", $end_date);
                                    $res[$i]['url'] .= $filter['param']  . '_end[Year]=' . $end_date_parts[0] . '&';
                                    $res[$i]['url'] .= $filter['param']  . '_end[Month]=' . $end_date_parts[1] . '&';
                                    $res[$i]['url'] .= $filter['param']  . '_end[Day]=' . $end_date_parts[2] . '&';
                                }
                            }
                        } else {
                            if (@$filter['is_custom'] != 1) {
                                $res[$i]['url'] .= $filter['param'] . '=' . urlencode($res[$i]['cst_' . $field]) . '&';
                            }
                        }
                    }
                    $res[$i]['url'] .= 'custom_field=' . urlencode($res[$i]['cst_custom_field']);
                }
            }
            
            return $res;
        }
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
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
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
        global $HTTP_POST_VARS;

        $items = implode(", ", Misc::escapeInteger($HTTP_POST_VARS["item"]));
        foreach ($HTTP_POST_VARS["item"] as $cst_id) {
            $stmt = "DELETE FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_filter
                     WHERE";
            if (Filter::isGlobal($cst_id)) {
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
            $res = $GLOBALS["db_api"]->dbh->query($stmt);
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
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
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
                'title' =>  'Priority',
                'param' =>  'priority',
                'quickfilter'   =>  true
            ),
            'keywords'  =>  array(
                'title' =>  'Keyword(s)',
                'param' =>  'keywords',
                'quickfilter'   =>  true
            ),
            'users' =>  array(
                'title' =>  'Assigned',
                'param' =>  'users',
                'quickfilter'   =>  true
            ),
            'iss_prc_id'    =>  array(
                'title' =>  'Category',
                'param' =>  'category',
                'quickfilter'   =>  true
            ),
            'iss_sta_id'    =>  array(
                'title' =>  'Status',
                'param' =>  'status',
                'quickfilter'   =>  true
            ),
            'iss_pre_id'    =>  array(
                'title' =>  'Release',
                'param' =>  'release'
            ),
            'created_date'  =>  array(
                'title' =>  'Created Date',
                'param' =>  'created_date',
                'is_date'   =>  true
            ),
            'updated_date'  =>  array(
                'title' =>  'Updated Date',
                'param' =>  'updated_date',
                'is_date'   =>  true
            ),
            'last_response_date'  =>  array(
                'title' =>  'Last Response Date',
                'param' =>  'last_response_date',
                'is_date'   =>  true
            ),
            'first_response_date'  =>  array(
                'title' =>  'First Response Date',
                'param' =>  'first_response_date',
                'is_date'   =>  true
            ),
            'closed_date'  =>  array(
                'title' =>  'Closed Date',
                'param' =>  'closed_date',
                'is_date'   =>  true
            ),
            'rows'  =>  array(
                'title' =>  'Rows Per Page',
                'param' =>  'rows'
            ),
            'sort_by'   =>  array(
                'title' =>  'Sort By',
                'param' =>  'sort_by'
            ),
            'sort_order'    =>  array(
                'title' =>  'Sort Order',
                'param' =>  'sort_order',
            ),
            'hide_closed'   =>  array(
                'title' =>  'Hide Closed Issues',
                'param' =>  'hide_closed'
            ),
            'show_authorized'   =>  array(
                'title' =>  'Authorized to Send Emails',
                'param' =>  'show_authorized_issues'
            ),
            'show_notification_list'    =>  array(
                'title' =>  'In Notification List',
                'param' =>  'show_notification_list_issues'
            ),
            'search_type'   =>  array(
                'title' =>  'Search Type',
                'param' =>  'search_type'
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

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Filter Class');
}
?>