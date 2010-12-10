<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2010 Bryan Alsdorf                                     |
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
// | Authors: Bryan Alsdorf <balsdorf@gmail.com                           |
// +----------------------------------------------------------------------+


/**
 * Class to handle issue severity
 *
 * @author Bryan Alsdorf <balsdorf@gmail.com>
 */

class Severity
{
    /**
     * Method used to quickly change the ranking of a severity entry
     * from the administration screen.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $sev_id The severityID
     * @param   string $rank_type Whether we should change the reminder ID down or up (options are 'asc' or 'desc')
     * @return  boolean
     */
    public static function changeRank($prj_id, $sev_id, $rank_type)
    {
        // check if the current rank is not already the first or last one
        $ranking = self::_getRanking($prj_id);
        $ranks = array_values($ranking);
        $ids = array_keys($ranking);
        $last = end($ids);
        $first = reset($ids);
        if ((($rank_type == 'asc') && ($sev_id == $first)) ||
                (($rank_type == 'desc') && ($sev_id == $last))) {
            return false;
        }

        if ($rank_type == 'asc') {
            $diff = -1;
        } else {
            $diff = 1;
        }
        $new_rank = $ranking[$sev_id] + $diff;
        if (in_array($new_rank, $ranks)) {
            // switch the rankings here...
            $index = array_search($new_rank, $ranks);
            $replaced_sev_id = $ids[$index];
            $sql = "UPDATE
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_severity
                     SET
                        sev_rank=" . Misc::escapeInteger($ranking[$sev_id]) . "
                     WHERE
                        sev_prj_id=" . Misc::escapeInteger($prj_id) . " AND
                        sev_id=" . Misc::escapeInteger($replaced_sev_id);
            $res = DB_Helper::getInstance()->query($sql);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return array();
            }
        }
        $sql = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_severity
                 SET
                    sev_rank=" . Misc::escapeInteger($new_rank) . "
                 WHERE
                    sev_prj_id=" . Misc::escapeInteger($prj_id) . " AND
                    sev_id=" . Misc::escapeInteger($sev_id);
        $res = DB_Helper::getInstance()->query($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        }
        return true;
    }


    /**
     * Returns an associative array with the list of severity IDs and
     * their respective ranking.
     *
     * @param   integer $prj_id The ID of the project
     * @return  array The list of severities
     */
    private static function _getRanking($prj_id)
    {
        $sql = "SELECT
                    sev_id,
                    sev_rank
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_severity
                 WHERE
                    sev_prj_id=" . Misc::escapeInteger($prj_id) . "
                 ORDER BY
                    sev_rank ASC";
        $res = DB_Helper::getInstance()->getAssoc($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the full details of a severity.
     *
     * @param   integer $sev_id The severity ID
     * @return  array The information about the severity provided
     */
    public static function getDetails($sev_id)
    {
        $sql = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_severity
                 WHERE
                    sev_id=" . Misc::escapeInteger($sev_id);
        $res = DB_Helper::getInstance()->getRow($sql, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to remove all severities related to a set of
     * specific projects.
     *
     * @param   array $prj_ids The project IDs to be removed
     * @return  boolean Whether the removal worked or not
     */
    public static function removeByProjects($prj_ids)
    {
        $items = @implode(", ", Misc::escapeInteger($prj_ids));
        $sql = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_severity
                 WHERE
                    sev_prj_id IN ($items)";
        $res = DB_Helper::getInstance()->query($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method used to remove user-selected priorities from the
     * database.
     *
     * @param   array   $sev_ids Severity ids to remove
     * @return  boolean Whether the removal worked or not
     */
    public static function remove($sev_ids)
    {
        if (count($sev_ids) < 1) {
            return true;
        }
        $items = @implode(", ", Misc::escapeInteger($sev_ids));
        $sql = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_severity
                 WHERE
                    sev_id IN ($items)";
        $res = DB_Helper::getInstance()->query($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method used to update a single severity
     *
     * @return  integer 1 if the update worked properly, any other value otherwise
     */
    public static function update($sev_id, $title, $description, $rank)
    {
        if (Validation::isWhitespace($title)) {
            return -2;
        }
        $sql = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_severity
                 SET
                    sev_title='" . Misc::escapeString($title) . "',
                    sev_description='" . Misc::escapeString($description) . "',
                    sev_rank=" . Misc::escapeInteger($rank) . "
                 WHERE
                    sev_id=" . Misc::escapeInteger($sev_id);
        $res = DB_Helper::getInstance()->query($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            return 1;
        }
    }


    /**
     * Method used to add a new severity to the application.
     *
     * @return  integer 1 if the update worked properly, any other value otherwise
     */
    public static function insert($prj_id, $title, $description, $rank)
    {
        if (Validation::isWhitespace($title)) {
            return -2;
        }
        $sql = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_severity
                 SET
                    sev_prj_id = " . Misc::escapeInteger($prj_id) . ",
                    sev_title='" . Misc::escapeString($title) . "',
                    sev_description='" . Misc::escapeString($description) . "',
                    sev_rank=" . Misc::escapeInteger($rank);
        $res = DB_Helper::getInstance()->query($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            return 1;
        }
    }


    /**
     * Method used to get the full list of severities associated with
     * a specific project.
     *
     * @param   integer $prj_id The project ID
     * @return  array The full list of severities
     */
    public static function getList($prj_id)
    {
        $sql = "SELECT
                    sev_id,
                    sev_title,
                    sev_rank,
                    sev_description
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_severity
                 WHERE
                    sev_prj_id=" . Misc::escapeInteger($prj_id) . "
                 ORDER BY
                    sev_rank ASC";
        $res = DB_Helper::getInstance()->getAll($sql, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the title for a severity ID.
     *
     * @param   integer $sev_id The severity ID
     * @return  string The severity title
     */
    public function getTitle($sev_id)
    {
        $sql = "SELECT
                    sev_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_severity
                 WHERE
                    sev_id=" . Misc::escapeInteger($sev_id);
        $res = DB_Helper::getInstance()->getOne($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the list of severities as an associative array in the
     * style of (id => title)
     *
     * @param   integer $prj_id The project ID
     * @return  array The list of severities
     */
    public static function getAssocList($prj_id)
    {
        static $list;

        if (isset($list[$prj_id])) {
            return $list[$prj_id];
        }

        $sql = "SELECT
                    sev_id,
                    sev_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_severity
                 WHERE
                    sev_prj_id=" . Misc::escapeInteger($prj_id) . "
                 ORDER BY
                    sev_rank ASC";
        $res = DB_Helper::getInstance()->getAssoc($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            $list[$prj_id] = $res;
            return $res;
        }
    }

    /**
     * Method used to get the sev_id of a project by severity title.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $sev_id The severity ID
     * @param   string $sev_title The severity title
     * @return  integer $sev_id The severity ID
     */
    public static function getID($prj_id, $sev_title)
    {
        $sql = "SELECT
                    sev_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_severity
                 WHERE
                    sev_prj_id=" . Misc::escapeInteger($prj_id) . "
					AND sev_title = '" . Misc::escapeString($sev_title) . "'";
        $res = DB_Helper::getInstance()->getOne($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return $res;
        }
    }
}
