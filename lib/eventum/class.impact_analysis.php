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
 * Class to handle the business logic related to the impact analysis section
 * of the view issue page. This section allows the developer to give feedback
 * on the impacts required to implement a needed feature, or to change an
 * existing application.
 */

class Impact_Analysis
{
    /**
     * Method used to insert a new requirement for an existing issue.
     *
     * @param   integer $issue_id The issue ID
     * @return  integer -1 if an error occurred or 1 otherwise
     */
    public static function insert($issue_id)
    {
        $usr_id = Auth::getUserID();
        $stmt = 'INSERT INTO
                    {{%issue_requirement}}
                 (
                    isr_iss_id,
                    isr_usr_id,
                    isr_created_date,
                    isr_requirement
                 ) VALUES (
                    ?, ?, ?, ?
                 )';
        $params = array($issue_id, $usr_id, Date_Helper::getCurrentDateGMT(), $_POST['new_requirement']);
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DbException $e) {
            return -1;
        }

        Issue::markAsUpdated($issue_id);
        // need to save a history entry for this
        History::add($issue_id, $usr_id, 'impact_analysis_added', 'New requirement submitted by {user}', array(
            'user' => User::getFullName($usr_id)
        ));

        return 1;
    }

    /**
     * Method used to get the full list of requirements and impact analysis for
     * a specific issue.
     *
     * @param   integer $issue_id The issue ID
     * @return  array The full list of requirements
     */
    public static function getListing($issue_id)
    {
        $stmt = 'SELECT
                    isr_id,
                    isr_requirement,
                    isr_dev_time,
                    isr_impact_analysis,
                    A.usr_full_name AS submitter_name,
                    B.usr_full_name AS handler_name
                 FROM
                    (
                    {{%issue_requirement}},
                    {{%user}} A
                    )
                 LEFT JOIN
                    {{%user}} B
                 ON
                    isr_updated_usr_id=B.usr_id
                 WHERE
                    isr_iss_id=? AND
                    isr_usr_id=A.usr_id';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, array($issue_id));
        } catch (DbException $e) {
            return '';
        }

        if (count($res) == 0) {
            return '';
        }

        $prj_id = Issue::getProjectID($issue_id);
        foreach ($res as &$row) {
            $row['isr_requirement'] = Link_Filter::processText($prj_id, nl2br(htmlspecialchars($row['isr_requirement'])));
            $row['isr_impact_analysis'] = Link_Filter::processText($prj_id, nl2br(htmlspecialchars($row['isr_impact_analysis'])));
            $row['formatted_dev_time'] = Misc::getFormattedTime($row['isr_dev_time']);
        }

        return $res;
    }

    /**
     * Method used to update an existing requirement with the appropriate
     * impact analysis.
     *
     * @param   integer $isr_id The requirement ID
     * @return  integer -1 if an error occurred or 1 otherwise
     */
    public static function update($isr_id)
    {
        $stmt = 'SELECT
                    isr_iss_id
                 FROM
                    {{%issue_requirement}}
                 WHERE
                    isr_id=?';
        $issue_id = DB_Helper::getInstance()->getOne($stmt, array($isr_id));

        // we are storing minutes, not hours
        $dev_time = $_POST['dev_time'] * 60;
        $usr_id = Auth::getUserID();
        $stmt = 'UPDATE
                    {{%issue_requirement}}
                 SET
                    isr_updated_usr_id=?,
                    isr_updated_date=?,
                    isr_dev_time=?,
                    isr_impact_analysis=?
                 WHERE
                    isr_id=?';
        $params = array($usr_id, Date_Helper::getCurrentDateGMT(), $dev_time, $_POST['impact_analysis'], $isr_id);
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DbException $e) {
            return -1;
        }

        Issue::markAsUpdated($issue_id);
        // need to save a history entry for this
        History::add($issue_id, $usr_id, 'impact_analysis_updated', 'Impact analysis submitted by {user}', array(
            'user' => User::getFullName($usr_id)
        ));

        return 1;
    }

    /**
     * Method used to remove an existing set of requirements.
     *
     * @return  integer -1 if an error occurred or 1 otherwise
     */
    public static function remove()
    {
        $items = $_POST['item'];
        $itemlist = DB_Helper::buildList($items);

        $stmt = "SELECT
                    isr_iss_id
                 FROM
                    {{%issue_requirement}}
                 WHERE
                    isr_id IN ($itemlist)";
        $issue_id = DB_Helper::getInstance()->getOne($stmt, $items);

        $stmt = "DELETE FROM
                    {{%issue_requirement}}
                 WHERE
                    isr_id IN ($itemlist)";
        try {
            DB_Helper::getInstance()->query($stmt, $items);
        } catch (DbException $e) {
            return -1;
        }

        Issue::markAsUpdated($issue_id);
        // need to save a history entry for this
        $usr_id = Auth::getUserID();
        History::add($issue_id, $usr_id, 'impact_analysis_removed', 'Impact analysis removed by {user}', array(
            'user' => User::getFullName($usr_id)
        ));

        return 1;
    }

    /**
     * Method used to remove all of the requirements associated with a set of
     * issue IDs.
     *
     * @param   array $ids The list of issue IDs
     * @return  boolean
     */
    public static function removeByIssues($ids)
    {
        $items = DB_Helper::buildList($ids);
        $stmt = "DELETE FROM
                    {{%issue_requirement}}
                 WHERE
                    isr_iss_id IN ($items)";
        try {
            DB_Helper::getInstance()->query($stmt, $ids);
        } catch (DbException $e) {
            return false;
        }

        return true;
    }
}
