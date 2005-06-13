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
// @(#) $Id: s.class.reminder_condition.php 1.2 04/01/19 15:15:25-00:00 jpradomaia $
//

include_once(APP_INC_PATH . "class.error_handler.php");
include_once(APP_INC_PATH . "class.status.php");

/**
 * Class to handle the business logic related to the reminder emails
 * that the system sends out.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

class Reminder_Condition
{
    /**
     * Method used to get the details for a specific reminder condition.
     *
     * @access  public
     * @param   integer $rlc_id The reminder condition ID
     * @return  array The details for the specified reminder condition
     */
    function getDetails($rlc_id)
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_level_condition
                 WHERE
                    rlc_id=" . Misc::escapeInteger($rlc_id);
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return '';
        } else {
            return $res;
        }
    }


    /**
     * Method used to create a new reminder condition.
     *
     * @access  public
     * @return  integer 1 if the insert worked, -1 or -2 otherwise
     */
    function insert()
    {
        global $HTTP_POST_VARS;

        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_level_condition
                 (
                    rlc_created_date,
                    rlc_rma_id,
                    rlc_rmf_id,
                    rlc_rmo_id,
                    rlc_value,
                    rlc_comparison_rmf_id
                 ) VALUES (
                    '" . Date_API::getCurrentDateGMT() . "',
                    " . Misc::escapeInteger($HTTP_POST_VARS['rma_id']) . ",
                    " . Misc::escapeInteger($HTTP_POST_VARS['field']) . ",
                    " . Misc::escapeInteger($HTTP_POST_VARS['operator']) . ",
                    '" . Misc::escapeString(@$HTTP_POST_VARS['value']) . "',
                    '" . Misc::escapeInteger(@$HTTP_POST_VARS['comparison_field']) . "'
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            return 1;
        }
    }


    /**
     * Method used to update the details of a specific reminder condition.
     *
     * @access  public
     * @return  integer 1 if the update worked, -1 or -2 otherwise
     */
    function update()
    {
        global $HTTP_POST_VARS;

        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_level_condition
                 SET
                    rlc_last_updated_date='" . Date_API::getCurrentDateGMT() . "',
                    rlc_rmf_id=" . Misc::escapeInteger($HTTP_POST_VARS['field']) . ",
                    rlc_rmo_id=" . Misc::escapeInteger($HTTP_POST_VARS['operator']) . ",
                    rlc_value='" . Misc::escapeString(@$HTTP_POST_VARS['value']) . "',
                    rlc_comparison_rmf_id = '" . Misc::escapeInteger(@$HTTP_POST_VARS['comparison_field']) . "'
                 WHERE
                    rlc_id=" . Misc::escapeInteger($HTTP_POST_VARS['id']);
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            return 1;
        }
    }


    /**
     * Method used to remove reminder conditions by using the administrative
     * interface of the system.
     *
     * @access  public
     * @return  boolean
     */
    function remove()
    {
        global $HTTP_POST_VARS;

        $items = @implode(", ", Misc::escapeInteger($HTTP_POST_VARS["items"]));
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_level_condition
                 WHERE
                    rlc_id IN ($items)";
        $GLOBALS["db_api"]->dbh->query($stmt);
    }


    /**
     * Method used to get the list of reminder conditions associated with a given
     * reminder action ID.
     *
     * @access  public
     * @param   integer $action_id The reminder action ID
     * @return  array The list of reminder conditions
     */
    function getList($action_id)
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_level_condition,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_field,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_operator
                 WHERE
                    rlc_rma_id=" . Misc::escapeInteger($action_id) . " AND
                    rlc_rmf_id=rmf_id AND
                    rlc_rmo_id=rmo_id";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            if (empty($res)) {
                return array();
            } else {
                return $res;
            }
        }
    }


    /**
     * Method used to get the list of reminder conditions to be displayed in the
     * administration section.
     *
     * @access  public
     * @param   integer $rma_id The reminder action ID
     * @return  array The list of reminder conditions
     */
    function getAdminList($rma_id)
    {
        $stmt = "SELECT
                    rlc_id,
                    rlc_value,
                    rlc_comparison_rmf_id,
                    rmf_title,
                    rmo_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_level_condition,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_field,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_operator
                 WHERE
                    rlc_rmf_id=rmf_id AND
                    rlc_rmo_id=rmo_id AND
                    rlc_rma_id=" . Misc::escapeInteger($rma_id) . "
                 ORDER BY
                    rlc_id ASC";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            for ($i = 0; $i < count($res); $i++) {
                if (!empty($res[$i]['rlc_comparison_rmf_id'])) {
                    $res[$i]['rlc_value'] = 'Field: ' . Reminder_Condition::getFieldTitle($res[$i]['rlc_comparison_rmf_id']);
                }elseif (strtolower($res[$i]['rmf_title']) == 'status') {
                    $res[$i]['rlc_value'] = Status::getStatusTitle($res[$i]['rlc_value']);
                } elseif (strtolower($res[$i]['rmf_title']) == 'category') {
                    $res[$i]['rlc_value'] = Category::getTitle($res[$i]['rlc_value']);
                } elseif (strtoupper($res[$i]['rlc_value']) != 'NULL') {
                    $res[$i]['rlc_value'] .= ' hours';
                }
            }
            return $res;
        }
    }


    /**
     * Method used to get the title of a specific reminder field.
     *
     * @access  public
     * @param   integer $field_id The reminder field ID
     * @return  string The title of the reminder field
     */
    function getFieldTitle($field_id)
    {
        $stmt = "SELECT
                    rmf_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_field
                 WHERE
                    rmf_id=" . Misc::escapeInteger($field_id);
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return '';
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the sql_field of a specific reminder field.
     *
     * @access  public
     * @param   integer $field_id The reminder field ID
     * @return  string The sql_field of the reminder field
     */
    function getSQLField($field_id)
    {
        $stmt = "SELECT
                    rmf_sql_field
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_field
                 WHERE
                    rmf_id=" . Misc::escapeInteger($field_id);
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return '';
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the list of reminder fields to be displayed in the
     * administration section.
     *
     * @access  public
     * @param   boolean $comparable_only If true, only fields that can be compared to other fields will be returned
     * @return  array The list of reminder fields
     */
    function getFieldAdminList($comparable_only = false)
    {
        $stmt = "SELECT
                    rmf_id,
                    rmf_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_field\n";
        if ($comparable_only == true) {
            $stmt .= "WHERE rmf_allow_column_compare = 1\n";   
        }
        $stmt .= "ORDER BY
                    rmf_title ASC";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the list of reminder operators to be displayed in the
     * administration section.
     *
     * @access  public
     * @return  array The list of reminder operators
     */
    function getOperatorAdminList()
    {
        $stmt = "SELECT
                    rmo_id,
                    rmo_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_operator
                 ORDER BY
                    rmo_title ASC";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            return $res;
        }
    }
    

    /**
     * Method used to see if a specific reminder field can be compared to other fields.
     *
     * @access  public
     * @param   integer $field_id The reminder field ID
     * @return  boolean If this field can be compared to other fields.
     */
    function canFieldBeCompared($field_id)
    {
        $stmt = "SELECT
                    rmf_allow_column_compare
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_field
                 WHERE
                    rmf_id=" . Misc::escapeInteger($field_id);
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return '';
        } else {
            return $res;
        }
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Reminder_Condition Class');
}
?>