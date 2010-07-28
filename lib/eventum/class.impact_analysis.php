<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
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
 * Class to handle the business logic related to the impact analysis section
 * of the view issue page. This section allows the developer to give feedback
 * on the impacts required to implement a needed feature, or to change an
 * existing application.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

class Impact_Analysis
{
    /**
     * Method used to insert a new requirement for an existing issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  integer -1 if an error occurred or 1 otherwise
     */
    function insert($issue_id)
    {
        $usr_id = Auth::getUserID();
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_requirement
                 (
                    isr_iss_id,
                    isr_usr_id,
                    isr_created_date,
                    isr_requirement
                 ) VALUES (
                    " . Misc::escapeInteger($issue_id) . ",
                    $usr_id,
                    '" . Date_Helper::getCurrentDateGMT() . "',
                    '" . Misc::escapeString($_POST["new_requirement"]) . "'
                 )";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            Issue::markAsUpdated($issue_id);
            // need to save a history entry for this
            History::add($issue_id, $usr_id, History::getTypeID('impact_analysis_added'), ev_gettext('New requirement submitted by %1$s', User::getFullName($usr_id)));
            return 1;
        }
    }


    /**
     * Method used to get the full list of requirements and impact analysis for
     * a specific issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  array The full list of requirements
     */
    function getListing($issue_id)
    {
        $stmt = "SELECT
                    isr_id,
                    isr_requirement,
                    isr_dev_time,
                    isr_impact_analysis,
                    A.usr_full_name AS submitter_name,
                    B.usr_full_name AS handler_name
                 FROM
                    (
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_requirement,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user A
                    )
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user B
                 ON
                    isr_updated_usr_id=B.usr_id
                 WHERE
                    isr_iss_id=" . Misc::escapeInteger($issue_id) . " AND
                    isr_usr_id=A.usr_id";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            if (count($res) == 0) {
                return "";
            } else {
                for ($i = 0; $i < count($res); $i++) {
                    $res[$i]["isr_requirement"] = Link_Filter::processText(Issue::getProjectID($issue_id), nl2br(htmlspecialchars($res[$i]["isr_requirement"])));
                    $res[$i]["isr_impact_analysis"] = Link_Filter::processText(Issue::getProjectID($issue_id), nl2br(htmlspecialchars($res[$i]["isr_impact_analysis"])));
                    $res[$i]["formatted_dev_time"] = Misc::getFormattedTime($res[$i]["isr_dev_time"]);
                }
                return $res;
            }
        }
    }


    /**
     * Method used to update an existing requirement with the appropriate
     * impact analysis.
     *
     * @access  public
     * @param   integer $isr_id The requirement ID
     * @return  integer -1 if an error occurred or 1 otherwise
     */
    function update($isr_id)
    {
        $stmt = "SELECT
                    isr_iss_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_requirement
                 WHERE
                    isr_id=" . Misc::escapeInteger($isr_id);
        $issue_id = DB_Helper::getInstance()->getOne($stmt);

        // we are storing minutes, not hours
        $dev_time = $_POST["dev_time"] * 60;
        $usr_id = Auth::getUserID();
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_requirement
                 SET
                    isr_updated_usr_id=$usr_id,
                    isr_updated_date='" . Date_Helper::getCurrentDateGMT() . "',
                    isr_dev_time=$dev_time,
                    isr_impact_analysis='" . Misc::escapeString($_POST["impact_analysis"]) . "'
                 WHERE
                    isr_id=" . Misc::escapeInteger($isr_id);
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            Issue::markAsUpdated($issue_id);
            // need to save a history entry for this
            History::add($issue_id, $usr_id, History::getTypeID('impact_analysis_updated'), ev_gettext('Impact analysis submitted by %1$s', User::getFullName($usr_id)));
            return 1;
        }
    }


    /**
     * Method used to remove an existing set of requirements.
     *
     * @access  public
     * @return  integer -1 if an error occurred or 1 otherwise
     */
    function remove()
    {
        $items = implode(", ", Misc::escapeInteger($_POST["item"]));
        $stmt = "SELECT
                    isr_iss_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_requirement
                 WHERE
                    isr_id IN ($items)";
        $issue_id = DB_Helper::getInstance()->getOne($stmt);

        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_requirement
                 WHERE
                    isr_id IN ($items)";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            Issue::markAsUpdated($issue_id);
            // need to save a history entry for this
            History::add($issue_id, Auth::getUserID(), History::getTypeID('impact_analysis_removed'), ev_gettext('Impact analysis removed by %1$s', User::getFullName(Auth::getUserID())));
            return 1;
        }
    }


    /**
     * Method used to remove all of the requirements associated with a set of
     * issue IDs.
     *
     * @access  public
     * @param   array $ids The list of issue IDs
     * @return  boolean
     */
    function removeByIssues($ids)
    {
        $items = implode(", ", Misc::escapeInteger($ids));
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_requirement
                 WHERE
                    isr_iss_id IN ($items)";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }
}
