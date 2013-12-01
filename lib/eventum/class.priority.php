<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2013 Eventum Team.                              |
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
 * Class to handle project priority related issues.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

class Priority
{
    /**
     * Method used to quickly change the ranking of a reminder entry
     * from the administration screen.
     *
     * @access  public
     * @param   integer $pri_id The reminder entry ID
     * @param   string $rank_type Whether we should change the reminder ID down or up (options are 'asc' or 'desc')
     * @return  boolean
     */
    function changeRank($prj_id, $pri_id, $rank_type)
    {
        // check if the current rank is not already the first or last one
        $ranking = self::_getRanking($prj_id);
        $ranks = array_values($ranking);
        $ids = array_keys($ranking);
        $last = end($ids);
        $first = reset($ids);
        if ((($rank_type == 'asc') && ($pri_id == $first)) ||
                (($rank_type == 'desc') && ($pri_id == $last))) {
            return false;
        }

        if ($rank_type == 'asc') {
            $diff = -1;
        } else {
            $diff = 1;
        }
        $new_rank = $ranking[$pri_id] + $diff;
        if (in_array($new_rank, $ranks)) {
            // switch the rankings here...
            $index = array_search($new_rank, $ranks);
            $replaced_pri_id = $ids[$index];
            $stmt = "UPDATE
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_priority
                     SET
                        pri_rank=" . Misc::escapeInteger($ranking[$pri_id]) . "
                     WHERE
                        pri_prj_id=" . Misc::escapeInteger($prj_id) . " AND
                        pri_id=" . Misc::escapeInteger($replaced_pri_id);
            DB_Helper::getInstance()->query($stmt);
        }
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_priority
                 SET
                    pri_rank=" . Misc::escapeInteger($new_rank) . "
                 WHERE
                    pri_prj_id=" . Misc::escapeInteger($prj_id) . " AND
                    pri_id=" . Misc::escapeInteger($pri_id);
        DB_Helper::getInstance()->query($stmt);
        return true;
    }


    /**
     * Returns an associative array with the list of reminder IDs and
     * their respective ranking.
     *
     * @access  private
     * @param   integer $prj_id The ID of the project
     * @return  array The list of reminders
     */
    function _getRanking($prj_id)
    {
        $stmt = "SELECT
                    pri_id,
                    pri_rank
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_priority
                 WHERE
                    pri_prj_id=" . Misc::escapeInteger($prj_id) . "
                 ORDER BY
                    pri_rank ASC";
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the full details of a priority.
     *
     * @access  public
     * @param   integer $pri_id The priority ID
     * @return  array The information about the priority provided
     */
    function getDetails($pri_id)
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_priority
                 WHERE
                    pri_id=" . Misc::escapeInteger($pri_id);
        $res = DB_Helper::getInstance()->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to remove all priorities related to a set of
     * specific projects.
     *
     * @access  public
     * @param   array $ids The project IDs to be removed
     * @return  boolean Whether the removal worked or not
     */
    function removeByProjects($ids)
    {
        $items = @implode(", ", Misc::escapeInteger($ids));
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_priority
                 WHERE
                    pri_prj_id IN ($items)";
        $res = DB_Helper::getInstance()->query($stmt);
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
     * @access  public
     * @return  boolean Whether the removal worked or not
     */
    function remove()
    {
        $items = @implode(", ", Misc::escapeInteger($_POST["items"]));
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_priority
                 WHERE
                    pri_id IN ($items)";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method used to update the values stored in the database.
     * Typically the user would modify the title of the priority in
     * the application and this method would be called.
     *
     * @access  public
     * @return  integer 1 if the update worked properly, any other value otherwise
     */
    function update()
    {
        if (Validation::isWhitespace($_POST["title"])) {
            return -2;
        }
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_priority
                 SET
                    pri_title='" . Misc::escapeString($_POST["title"]) . "',
                    pri_rank=" . Misc::escapeInteger($_POST['rank']) . "
                 WHERE
                    pri_prj_id=" . Misc::escapeInteger($_POST["prj_id"]) . " AND
                    pri_id=" . Misc::escapeInteger($_POST["id"]);
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            return 1;
        }
    }


    /**
     * Method used to add a new priority to the application.
     *
     * @access  public
     * @return  integer 1 if the update worked properly, any other value otherwise
     */
    function insert()
    {
        if (Validation::isWhitespace($_POST["title"])) {
            return -2;
        }
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_priority
                 (
                    pri_prj_id,
                    pri_title,
                    pri_rank
                 ) VALUES (
                    " . Misc::escapeInteger($_POST["prj_id"]) . ",
                    '" . Misc::escapeString($_POST["title"]) . "',
                    " . Misc::escapeInteger($_POST['rank']) . "
                 )";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            return 1;
        }
    }


    /**
     * Method used to get the full list of priorities associated with
     * a specific project.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  array The full list of priorities
     */
    function getList($prj_id)
    {
        $stmt = "SELECT
                    pri_id,
                    pri_title,
                    pri_rank
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_priority
                 WHERE
                    pri_prj_id=" . Misc::escapeInteger($prj_id) . "
                 ORDER BY
                    pri_rank ASC";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the title for a priority ID.
     *
     * @access  public
     * @param   integer $pri_id The priority ID
     * @return  string The priority title
     */
    function getTitle($pri_id)
    {
        $stmt = "SELECT
                    pri_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_priority
                 WHERE
                    pri_id=" . Misc::escapeInteger($pri_id);
        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the list of priorities as an associative array in the
     * style of (id => title)
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  array The list of priorities
     */
    function getAssocList($prj_id)
    {
        static $list;

        if (count(@$list[$prj_id]) > 0) {
            return $list[$prj_id];
        }

        $stmt = "SELECT
                    pri_id,
                    pri_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_priority
                 WHERE
                    pri_prj_id=" . Misc::escapeInteger($prj_id) . "
                 ORDER BY
                    pri_rank ASC";
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            $list[$prj_id] = $res;
            return $res;
        }
    }

    /**
     * Method used to get the pri_id of a project by priority title.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $pri_id The priority ID
     * @param   string $pri_title The priority title
     * @return  integer $pri_id The priority ID
     */
    function getPriorityID($prj_id, $pri_title)
    {
        $stmt = "SELECT
                    pri_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_priority
                 WHERE
                    pri_prj_id=" . Misc::escapeInteger($prj_id) . "
                    AND pri_title = '" . Misc::escapeString($pri_title) . "'";

        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return null;
        } else {
            return $res;
        }
    }
}
