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
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+

/**
 * Class to handle the business logic related to the reminder emails
 * that the system sends out.
 */

class Reminder_Condition
{
    /**
     * Method used to get the details for a specific reminder condition.
     *
     * @param   integer $rlc_id The reminder condition ID
     * @return  array The details for the specified reminder condition
     */
    public static function getDetails($rlc_id)
    {
        $stmt = 'SELECT
                    *
                 FROM
                    {{%reminder_level_condition}}
                 WHERE
                    rlc_id=?';
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, array($rlc_id));
        } catch (DbException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to create a new reminder condition.
     *
     * @return  integer 1 if the insert worked, -1 or -2 otherwise
     */
    public static function insert()
    {
        $stmt = 'INSERT INTO
                    {{%reminder_level_condition}}
                 (
                    rlc_created_date,
                    rlc_rma_id,
                    rlc_rmf_id,
                    rlc_rmo_id,
                    rlc_value,
                    rlc_comparison_rmf_id
                 ) VALUES (
                    ?, ?, ?, ?, ?, ?
                 )';
        $params = array(
            Date_Helper::getCurrentDateGMT(),
            $_POST['rma_id'],
            $_POST['field'],
            $_POST['operator'],
            @$_POST['value'],
            @$_POST['comparison_field'],
        );
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DbException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Method used to update the details of a specific reminder condition.
     *
     * @return  integer 1 if the update worked, -1 or -2 otherwise
     */
    public static function update()
    {
        $stmt = 'UPDATE
                    {{%reminder_level_condition}}
                 SET
                    rlc_last_updated_date=?,
                    rlc_rmf_id=?,
                    rlc_rmo_id=?,
                    rlc_value=?,
                    rlc_comparison_rmf_id = ?
                 WHERE
                    rlc_id=?';
        $params = array(
            Date_Helper::getCurrentDateGMT(),
            $_POST['field'],
            $_POST['operator'],
            @$_POST['value'],
            @$_POST['comparison_field'],
            $_POST['id'],
        );

        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DbException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Method used to remove reminder conditions by using the administrative
     * interface of the system.
     *
     * @return  boolean
     */
    public static function remove()
    {
        $items = $_POST['items'];
        $stmt = 'DELETE FROM
                    {{%reminder_level_condition}}
                 WHERE
                    rlc_id IN (' . DB_Helper::buildList($items) . ')';
        DB_Helper::getInstance()->query($stmt, $items);
    }

    /**
     * Method used to get the list of reminder conditions associated with a given
     * reminder action ID.
     *
     * @param   integer $action_id The reminder action ID
     * @return  array The list of reminder conditions
     */
    public static function getList($action_id)
    {
        $stmt = 'SELECT
                    *
                 FROM
                    {{%reminder_level_condition}},
                    {{%reminder_field}},
                    {{%reminder_operator}}
                 WHERE
                    rlc_rma_id=? AND
                    rlc_rmf_id=rmf_id AND
                    rlc_rmo_id=rmo_id';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, array($action_id));
        } catch (DbException $e) {
            return array();
        }

        if (empty($res)) {
            return array();
        }

        return $res;
    }

    /**
     * Method used to get the list of reminder conditions to be displayed in the
     * administration section.
     *
     * @param   integer $rma_id The reminder action ID
     * @return  array The list of reminder conditions
     */
    public static function getAdminList($rma_id)
    {
        $stmt = 'SELECT
                    rlc_id,
                    rlc_value,
                    rlc_comparison_rmf_id,
                    rmf_title,
                    rmo_title
                 FROM
                    {{%reminder_level_condition}},
                    {{%reminder_field}},
                    {{%reminder_operator}}
                 WHERE
                    rlc_rmf_id=rmf_id AND
                    rlc_rmo_id=rmo_id AND
                    rlc_rma_id=?
                 ORDER BY
                    rlc_id ASC';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, array($rma_id));
        } catch (DbException $e) {
            return array();
        }

        foreach ($res as &$row) {
            if (!empty($row['rlc_comparison_rmf_id'])) {
                $row['rlc_value'] = ev_gettext('Field') . ': ' . self::getFieldTitle($row['rlc_comparison_rmf_id']);
            } elseif (strtolower($row['rmf_title']) == 'status') {
                $row['rlc_value'] = Status::getStatusTitle($row['rlc_value']);
            } elseif (strtolower($row['rmf_title']) == 'category') {
                $row['rlc_value'] = Category::getTitle($row['rlc_value']);
            } elseif ((strtolower($row['rmf_title']) == 'group') || (strtolower($row['rmf_title']) == 'active group')) {
                $row['rlc_value'] = Group::getName($row['rlc_value']);
            } elseif (strtoupper($row['rlc_value']) != 'NULL') {
                $row['rlc_value'] .= ' ' . ev_gettext('hours');
            }
        }

        return $res;
    }

    /**
     * Method used to get the title of a specific reminder field.
     *
     * @param   integer $field_id The reminder field ID
     * @return  string The title of the reminder field
     */
    public static function getFieldTitle($field_id)
    {
        $stmt = 'SELECT
                    rmf_title
                 FROM
                    {{%reminder_field}}
                 WHERE
                    rmf_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, array($field_id));
        } catch (DbException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the sql_field of a specific reminder field.
     *
     * @param   integer $field_id The reminder field ID
     * @return  string The sql_field of the reminder field
     */
    public static function getSQLField($field_id)
    {
        $stmt = 'SELECT
                    rmf_sql_field
                 FROM
                    {{%reminder_field}}
                 WHERE
                    rmf_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, array($field_id));
        } catch (DbException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the list of reminder fields to be displayed in the
     * administration section.
     *
     * @param   boolean $comparable_only If true, only fields that can be compared to other fields will be returned
     * @return  array The list of reminder fields
     */
    public static function getFieldAdminList($comparable_only = false)
    {
        $stmt = "SELECT
                    rmf_id,
                    rmf_title
                 FROM
                    {{%reminder_field}}\n";
        if ($comparable_only == true) {
            $stmt .= "WHERE rmf_allow_column_compare = 1\n";
        }
        $stmt .= 'ORDER BY
                    rmf_title ASC';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt);
        } catch (DbException $e) {
            return array();
        }

        return $res;
    }

    /**
     * Method used to get the list of reminder operators to be displayed in the
     * administration section.
     *
     * @return  array The list of reminder operators
     */
    public static function getOperatorAdminList()
    {
        $stmt = 'SELECT
                    rmo_id,
                    rmo_title
                 FROM
                    {{%reminder_operator}}
                 ORDER BY
                    rmo_title ASC';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt);
        } catch (DbException $e) {
            return array();
        }

        return $res;
    }

    /**
     * Method used to see if a specific reminder field can be compared to other fields.
     *
     * @param   integer $field_id The reminder field ID
     * @return  boolean If this field can be compared to other fields.
     */
    public static function canFieldBeCompared($field_id)
    {
        $stmt = 'SELECT
                    rmf_allow_column_compare
                 FROM
                    {{%reminder_field}}
                 WHERE
                    rmf_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, array($field_id));
        } catch (DbException $e) {
            return '';
        }

        return $res;
    }
}
