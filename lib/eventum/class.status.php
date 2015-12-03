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
 * Class to handle all business logic related to the way statuses
 * are represented in the system.
 */

class Status
{
    /**
     * Returns the label and date field associated with the customization of
     * the given project and status IDs.
     *
     * @param   integer $prj_id The project ID
     * @param   array $sta_ids The list of status IDs
     * @return  array The label and date field
     */
    public static function getProjectStatusCustomization($prj_id, $sta_ids)
    {
        $sta_ids = array_unique($sta_ids);

        $stmt = 'SELECT
                    psd_sta_id,
                    psd_label,
                    psd_date_field
                 FROM
                    {{%project_status_date}}
                 WHERE
                    psd_prj_id=? AND
                    psd_sta_id IN (' . DB_Helper::buildList($sta_ids). ')';
        $params = array_merge(array($prj_id), $sta_ids);

        try {
            $res = DB_Helper::getInstance()->fetchAssoc($stmt, $params);
        } catch (DbException $e) {
            return array();
        }

        return $res;
    }

    /**
     * Returns the details of a given project status customization entry.
     *
     * @param   integer $psd_id The customization entry ID
     * @return  array The details
     */
    public static function getCustomizationDetails($psd_id)
    {
        $stmt = 'SELECT
                    *
                 FROM
                    {{%project_status_date}}
                 WHERE
                    psd_id=?';
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, array($psd_id));
        } catch (DbException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Removes a given set of customizations.
     *
     * @param   array $items The customization entry IDs
     * @return  boolean
     */
    public static function removeCustomization($items)
    {
        $stmt = 'DELETE FROM
                    {{%project_status_date}}
                 WHERE
                    psd_id IN (' . DB_Helper::buildList($items) . ')';
        try {
            DB_Helper::getInstance()->query($stmt, $items);
        } catch (DbException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to update the details of a customization entry in the system.
     *
     * @param   integer $psd_id The customization entry ID
     * @param   integer $prj_id The project ID
     * @param   integer $sta_id The status ID
     * @param   string $date_field The date field name
     * @param   string $label The label that should appear in the issue listing screen
     * @return  integer 1 if the insert worked properly, any other value otherwise
     */
    public static function updateCustomization($psd_id, $prj_id, $sta_id, $date_field, $label)
    {
        $stmt = 'UPDATE
                    {{%project_status_date}}
                 SET
                    psd_prj_id=?,
                    psd_sta_id=?,
                    psd_date_field=?,
                    psd_label=?
                 WHERE
                    psd_id=?';
        try {
            DB_Helper::getInstance()->query($stmt, array($prj_id, $sta_id, $date_field, $label, $psd_id));
        } catch (DbException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Method used to add a new customization entry to the system.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $sta_id The status ID
     * @param   string $date_field The date field name
     * @param   string $label The label that should appear in the issue listing screen
     * @return  integer 1 if the insert worked properly, any other value otherwise
     */
    public static function insertCustomization($prj_id, $sta_id, $date_field, $label)
    {
        $stmt = 'INSERT INTO
                    {{%project_status_date}}
                 (
                    psd_prj_id,
                    psd_sta_id,
                    psd_date_field,
                    psd_label
                 ) VALUES (
                    ?, ?, ?, ?
                 )';
        try {
            DB_Helper::getInstance()->query($stmt, array($prj_id, $sta_id, $date_field, $label));
        } catch (DbException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Method used to get a list of all existing customizations.
     *
     * @return  array The list of available customizations
     */
    public static function getCustomizationList()
    {
        $stmt = 'SELECT
                    psd_id,
                    psd_prj_id,
                    psd_sta_id,
                    psd_label,
                    psd_date_field,
                    prj_title,
                    sta_title
                 FROM
                    {{%project_status_date}},
                    {{%project}},
                    {{%status}}
                 WHERE
                    prj_id=psd_prj_id AND
                    sta_id=psd_sta_id
                 ORDER BY
                    prj_title ASC';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt);
        } catch (DbException $e) {
            return '';
        }

        $date_fields = Issue::getDateFieldsAssocList(true);
        foreach ($res as &$row) {
            $row['date_field'] = $date_fields[$row['psd_date_field']];
        }

        return $res;
    }

    /**
     * Method used to check whether the given status has a closed context or
     * not.
     *
     * @return  boolean
     */
    public function hasClosedContext($sta_id)
    {
        $stmt = 'SELECT
                    sta_is_closed
                 FROM
                    {{%status}}
                 WHERE
                    sta_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, array($sta_id));
        } catch (DbException $e) {
            return false;
        }

        if (empty($res)) {
            return false;
        }

        return true;
    }

    /**
     * Method used to add a new custom status to the system.
     *
     * @return  integer 1 if the insert worked properly, any other value otherwise
     */
    public static function insert()
    {
        if (Validation::isWhitespace($_POST['title'])) {
            return -2;
        }
        $stmt = 'INSERT INTO
                    {{%status}}
                 (
                    sta_title,
                    sta_abbreviation,
                    sta_rank,
                    sta_color,
                    sta_is_closed
                 ) VALUES (
                    ?, ?, ?, ?, ?
                 )';
        $params = array($_POST['title'], $_POST['abbreviation'], $_POST['rank'], $_POST['color'], $_POST['is_closed']);
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DbException $e) {
            return -1;
        }

        $new_status_id = DB_Helper::get_last_insert_id();
        // now populate the project-status mapping table
        foreach ($_POST['projects'] as $prj_id) {
            self::addProjectAssociation($new_status_id, $prj_id);
        }

        return 1;
    }

    /**
     * Method used to update the details of a given custom status.
     *
     * @return  integer 1 if the update worked properly, any other value otherwise
     */
    public static function updateFromPost()
    {
        if (Validation::isWhitespace($_POST['title'])) {
            return -2;
        }
        $color = $_POST['color'];

        // validate that it is valid RGB hex color
        if (!preg_match('/^#[a-f\d]{6}$/i', $color)) {
            return -3;
        }

        $stmt = 'UPDATE
                    {{%status}}
                 SET
                    sta_title=?,
                    sta_abbreviation=?,
                    sta_rank=?,
                    sta_color=?,
                    sta_is_closed=?
                 WHERE
                    sta_id=?';
        $params = array($_POST['title'], $_POST['abbreviation'], $_POST['rank'], $color, $_POST['is_closed'], $_POST['id']);
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DbException $e) {
            return -1;
        }

        $projects = self::getAssociatedProjects($_POST['id']);
        $current_projects = array_keys($projects);
        // remove all of the associations with projects, then add them all again
        self::removeProjectAssociations($_POST['id']);
        foreach ($_POST['projects'] as $prj_id) {
            self::addProjectAssociation($_POST['id'], $prj_id);
        }
        // need to update all issues that are not supposed to have the changed sta_id to '0'
        $removed_projects = array();
        foreach ($current_projects as $project_id) {
            if (!in_array($project_id, $_POST['projects'])) {
                $removed_projects[] = $project_id;
            }
        }

        if (count($removed_projects) > 0) {
            $stmt = 'UPDATE
                        {{%issue}}
                     SET
                        iss_sta_id=0
                     WHERE
                        iss_sta_id=? AND
                        iss_prj_id IN (' . implode(', ', $removed_projects) . ')';
            try {
                DB_Helper::getInstance()->query($stmt, array($_POST['id']));
            } catch (DbException $e) {
                // FIXME: why no error handling?
            }
        }

        return 1;
    }

    /**
     * Method used to remove a set of custom statuses.
     *
     * @return  boolean
     */
    public static function remove()
    {
        $items = $_POST['items'];
        $item_list = DB_Helper::buildList($items);

        $stmt = "DELETE FROM
                    {{%status}}
                 WHERE
                    sta_id IN ($item_list)";
        try {
            DB_Helper::getInstance()->query($stmt, $items);
        } catch (DbException $e) {
            return false;
        }

        self::removeProjectAssociations($items);
        // also set all issues currently set to these statuses to status '0'
        $stmt = "UPDATE
                    {{%issue}}
                 SET
                    iss_sta_id=0
                 WHERE
                    iss_sta_id IN ($item_list)";
        DB_Helper::getInstance()->query($stmt, $items);

        return true;
    }

    /**
     * Method used to add a project association to a status.
     *
     * @param   integer $sta_id The status ID
     * @param   integer $prj_id The project ID
     * @return  void
     */
    public static function addProjectAssociation($sta_id, $prj_id)
    {
        $stmt = 'INSERT INTO
                    {{%project_status}}
                 (
                    prs_sta_id,
                    prs_prj_id
                 ) VALUES (
                    ?, ?
                 )';
        DB_Helper::getInstance()->query($stmt, array($sta_id, $prj_id));
    }

    /**
     * Method used to remove the project associations for a given
     * custom status.
     *
     * @param   int|array $sta_id The custom status ID
     * @param   integer $prj_id The project ID
     * @return  boolean
     */
    public static function removeProjectAssociations($sta_id, $prj_id = null)
    {
        if (!is_array($sta_id)) {
            $sta_id = array($sta_id);
        }

        $stmt = 'DELETE FROM
                    {{%project_status}}
                 WHERE
                    prs_sta_id IN (' . DB_Helper::buildList($sta_id) . ')';

        $params = $sta_id;
        if ($prj_id) {
            $stmt .= ' AND prs_prj_id=?';
            $params[] = $prj_id;
        }
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DbException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to get the details of a given status ID.
     *
     * @param   integer $sta_id The custom status ID
     * @return  array The status details
     */
    public static function getDetails($sta_id)
    {
        $stmt = 'SELECT
                    *
                 FROM
                    {{%status}}
                 WHERE
                    sta_id=?';
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, array($sta_id));
        } catch (DbException $e) {
            return '';
        }

        // get all of the project associations here as well
        $res['projects'] = array_keys(self::getAssociatedProjects($res['sta_id']));

        return $res;
    }

    /**
     * Method used to get the list of statuses ordered by title.
     *
     * @return  array The list of statuses
     */
    public static function getList()
    {
        $stmt = 'SELECT
                    *
                 FROM
                    {{%status}}
                 ORDER BY
                    sta_rank ASC,
                    sta_title';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt);
        } catch (DbException $e) {
            return '';
        }

        // get the list of associated projects
        foreach ($res as &$row) {
            $row['projects'] = implode(', ', array_values(self::getAssociatedProjects($row['sta_id'])));
        }

        return $res;
    }

    /**
     * Method used to get the list of associated projects for a given
     * custom status.
     *
     * @param   integer $sta_id The custom status ID
     * @return  array The list of projects
     */
    public function getAssociatedProjects($sta_id)
    {
        $stmt = 'SELECT
                    prj_id,
                    prj_title
                 FROM
                    {{%project}},
                    {{%project_status}}
                 WHERE
                    prj_id=prs_prj_id AND
                    prs_sta_id=?';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, array($sta_id));
        } catch (DbException $e) {
            return array();
        }

        return $res;
    }

    /**
     * Method used to get the status ID for a given status title.
     *
     * @param   string $sta_title The status title
     * @return  integer The status ID
     */
    public static function getStatusID($sta_title)
    {
        static $returns;

        if (!empty($returns[$sta_title])) {
            return $returns[$sta_title];
        }

        $stmt = 'SELECT
                    sta_id
                 FROM
                    {{%status}}
                 WHERE
                    sta_title=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, array($sta_title));
        } catch (DbException $e) {
            return '';
        }

        $returns[$sta_title] = $res;

        return $res;
    }

    /**
     * Method used to get the status title for a given status ID.
     *
     * @param   integer $sta_id The status ID
     * @return  string The status title
     */
    public static function getStatusTitle($sta_id)
    {
        $stmt = 'SELECT
                    sta_title
                 FROM
                    {{%status}}
                 WHERE
                    sta_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, array($sta_id));
        } catch (DbException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the list of available closed-context statuses as an
     * associative array in the style of (abbreviation => title)
     *
     * @param   array|int $prj_id List of project IDs
     * @return  array The list of closed-context statuses
     */
    public static function getClosedAbbreviationAssocList($prj_id)
    {
        if (!is_array($prj_id)) {
            $prj_id = array($prj_id);
        }

        $stmt = 'SELECT
                    UPPER(sta_abbreviation),
                    sta_title
                 FROM
                    {{%status}},
                    {{%project_status}}
                 WHERE
                    prs_prj_id IN (' . DB_Helper::buildList($prj_id) . ') AND
                    prs_sta_id=sta_id AND
                    sta_is_closed=1
                 ORDER BY
                    sta_rank ASC';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, $prj_id);
        } catch (DbException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the list of available statuses as an associative array
     * in the style of (abbreviation => title)
     *
     * @param   array|int $prj_id List of project IDs
     * @param   boolean $show_closed Whether to also return closed-context statuses or not
     * @return  array The list of statuses
     */
    public static function getAbbreviationAssocList($prj_id, $show_closed)
    {
        if (!is_array($prj_id)) {
            $prj_id = array($prj_id);
        }

        $stmt = 'SELECT
                    UPPER(sta_abbreviation),
                    sta_title
                 FROM
                    {{%status}},
                    {{%project_status}}
                 WHERE
                    prs_prj_id IN (' . DB_Helper::buildList($prj_id) . ') AND
                    prs_sta_id=sta_id';
        if (!$show_closed) {
            $stmt .= ' AND sta_is_closed=0 ';
        }
        $stmt .= '
                 ORDER BY
                    sta_rank ASC';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, $prj_id);
        } catch (DbException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the list of available statuses as an associative array
     * in the style of (id => title)
     *
     * @param   array|int $prj_id List of project IDs
     * @param   boolean $show_closed Whether to show closed context statuses or not
     * @return  array The list of statuses
     */
    public static function getAssocStatusList($prj_id, $show_closed = true)
    {
        if (!is_array($prj_id)) {
            $prj_id = array($prj_id);
        }

        $stmt = 'SELECT
                    sta_id,
                    sta_title
                 FROM
                    {{%status}},
                    {{%project_status}}
                 WHERE
                    prs_prj_id IN (' . DB_Helper::buildList($prj_id) . ') AND
                    prs_sta_id=sta_id';
        if (!$show_closed) {
            $stmt .= ' AND sta_is_closed=0 ';
        }
        $stmt .= '
                 ORDER BY
                    sta_rank ASC';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, $prj_id);
        } catch (DbException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the list of available statuses as an associative array
     * in the style of (id => title)
     *
     * @return  array The list of statuses
     */
    public static function getAssocList()
    {
        $stmt = 'SELECT
                    sta_id,
                    sta_title
                 FROM
                    {{%status}}
                 ORDER BY
                    sta_rank ASC';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt);
        } catch (DbException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the list of available statuses as an associative array
     * in the style of (id => title). Only return the list of statuses that have
     * a 'closed' context.
     *
     * @param   integer $prj_id The project ID
     * @return  array The list of statuses
     */
    public static function getClosedAssocList($prj_id)
    {
        $stmt = 'SELECT
                    sta_id,
                    sta_title
                 FROM
                    {{%status}},
                    {{%project_status}}
                 WHERE
                    prs_prj_id=? AND
                    prs_sta_id=sta_id AND
                    sta_is_closed=1
                 ORDER BY
                    sta_rank ASC';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, array($prj_id));
        } catch (DbException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the list of statuses and their respective colors
     *
     * @return  array List of statuses
     */
    public function getStatusColors()
    {
        $stmt = 'SELECT
                    sta_color,
                    sta_title
                 FROM
                    {{%status}}
                 ORDER BY
                    sta_rank ASC';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt);
        } catch (DbException $e) {
            return '';
        }

        return $res;
    }
}
