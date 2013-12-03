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
 * Class to handle the business logic related to the administration
 * of time tracking categories in the system.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

class Time_Tracking
{
    /**
     * These categories are required by Eventum and cannot be deleted.
     * @var array
     */
    private static $default_categories = array('Note Discussion', 'Email Discussion', 'Telephone Discussion');

    /**
     * Method used to get the ID of a given category.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   string $ttc_title The time tracking category title
     * @return  integer The time tracking category ID
     */
    function getCategoryID($prj_id, $ttc_title = '')
    {
        // LEGACY: handle swapped params, i.e one parameter call where
        // $ttc_title was only arg. This is not needed by Eventum Core, but
        // kept for the sake of Workflow and Customer integration.
        if (func_num_args() == 1) {
            $ttc_title = $prj_id;
            $prj_id = Auth::getCurrentProject();
        }

        $stmt = "SELECT
                    ttc_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "time_tracking_category
                 WHERE
                    ttc_prj_id=" . Misc::escapeInteger($prj_id) . " AND
                    ttc_title='" . Misc::escapeString($ttc_title) . "'";
        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return 0;
        }
        return $res;
    }


    /**
     * Method used to get the details of a time tracking category.
     *
     * @access  public
     * @param   integer $ttc_id The time tracking category ID
     * @return  array The details of the category
     */
    function getDetails($ttc_id)
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "time_tracking_category
                 WHERE
                    ttc_id=" . Misc::escapeInteger($ttc_id);
        $res = DB_Helper::getInstance()->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        }
        return $res;
    }

    /**
     * Get statistic of time categories usage
     *
     * @return  array $ttc_id => issue_count
     */
    private static function getCategoryStats($ttc_ids)
    {
        $list = implode(", ", Misc::escapeInteger($ttc_ids));
        $stmt = "SELECT
                    ttr_ttc_id,
                    COUNT(*)
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "time_tracking
                 WHERE
                    ttr_ttc_id IN ($list)
                 GROUP BY 1";
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return null;
        }

        return $res;
    }

    /**
     * Method used to remove a specific set of time tracking categories
     *
     * @access  public
     * @return  int, 1 on success, -1 on error, -2 if can't remove because time category is being used
     */
    function remove()
    {
        $items = $_POST["items"];

        // check that none of the categories are in use
        $usage = self::getCategoryStats($items);
        foreach ($usage as $ttc_id => $count) {
            if ($count > 0) {
                return -2;
            }
        }

        $items = implode(", ", Misc::escapeInteger($items));
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "time_tracking_category
                 WHERE
                    ttc_id IN ($items)";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        }

        return 1;
    }


    /**
     * Method used to update a specific time tracking category
     *
     * @access  public
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function update()
    {
        if (Validation::isWhitespace($_POST["title"])) {
            return -2;
        }
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "time_tracking_category
                 SET
                    ttc_title='" . Misc::escapeString($_POST["title"]) . "'
                 WHERE
                    ttc_prj_id=" . Misc::escapeInteger($_POST["prj_id"]) . " AND
                    ttc_id=" . Misc::escapeInteger($_POST["id"]);
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        }
        return 1;
    }


    /**
     * Method used to add a new time tracking category
     *
     * @param   integer $prj_id The project ID
     * @param   string $title The title of the time tracking category
     * @return  integer 1 if the update worked, -1 otherwise
     */
    public static function insert($prj_id, $title)
    {
        if (Validation::isWhitespace($title)) {
            return -2;
        }

        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "time_tracking_category
                 (
                    ttc_prj_id,
                    ttc_title,
                    ttc_created_date
                 ) VALUES (
                    " . Misc::escapeInteger($prj_id) . ",
                    '" . Misc::escapeString($title) . "',
                    '" . Date_Helper::getCurrentDateGMT() . "'
                 )";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        }

        return 1;
    }

    /**
     * Method used to add a default timetracking categories for project.
     *
     * @param   integer $prj_id The project ID
     * @return  integer 1 if the inserts worked, -1 otherwise
     */
    public static function addProjectDefaults($prj_id)
    {
        $res = 1;
        foreach (self::$default_categories as $title) {
            $res = min($res, self::insert($prj_id, $title));
        }
        return $res;
    }

    /**
     * Method used to get the full list of time tracking categories associated
     * with a specific project.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  array The list of categories
     */
    function getList($prj_id)
    {
        $ttc_list = join(', ', Misc::escapeString(self::$default_categories, true));
        $stmt = "SELECT
                    ttc_id,
                    ttc_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "time_tracking_category
                 WHERE
                    ttc_prj_id=" . Misc::escapeInteger($prj_id) . " AND
                    ttc_title NOT IN ($ttc_list)
                 ORDER BY
                    ttc_title ASC";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        }

        $ttc_usage = self::getCategoryStats(Misc::collect('ttc_id', $res));
        foreach ($res as &$row) {
            $ttc_id = $row['ttc_id'];
            if (isset($ttc_usage[$ttc_id])) {
                $row['ttc_count'] = $ttc_usage[$ttc_id];
            }
        }

        return $res;
    }


    /**
     * Method used to get the full list of time tracking categories as an
     * associative array in the style of (id => title)
     *
     * @access  public
     * @return  array The list of categories
     */
    function getAssocCategories($prj_id)
    {
        $stmt = "SELECT
                    ttc_id,
                    ttc_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "time_tracking_category
                 WHERE
                    ttc_prj_id=" . Misc::escapeInteger($prj_id) . "
                 ORDER BY
                    ttc_title ASC";
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        }

        return $res;
    }


    /**
     * Method used to get the time spent on a given list of issues.
     *
     * @access  public
     * @param   array $result The result set
     * @return  void
     */
    function getTimeSpentByIssues(&$result)
    {
        $ids = array();
        for ($i = 0; $i < count($result); $i++) {
            $ids[] = $result[$i]["iss_id"];
        }
        if (count($ids) == 0) {
            return false;
        }
        $ids = implode(", ", Misc::escapeInteger($ids));
        $stmt = "SELECT
                    ttr_iss_id,
                    SUM(ttr_time_spent)
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "time_tracking
                 WHERE
                    ttr_iss_id IN ($ids)
                 GROUP BY
                    ttr_iss_id";
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return;
        }

        for ($i = 0; $i < count($result); $i++) {
            @$result[$i]['time_spent'] = $res[$result[$i]['iss_id']];
        }
    }


    /**
     * Method used to get the total time spent for a specific issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  integer The total time spent
     */
    function getTimeSpentByIssue($issue_id)
    {
        $stmt = "SELECT
                    SUM(ttr_time_spent)
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "time_tracking
                 WHERE
                    ttr_iss_id=" . Misc::escapeInteger($issue_id);
        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return 0;
        }

        return $res;
    }


    /**
     * Method used to get the full listing of time entries in the system for a
     * specific issue
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  array The full list of time entries
     */
    function getListing($issue_id)
    {
        $stmt = "SELECT
                    ttr_id,
                    ttr_created_date,
                    ttr_summary,
                    ttr_time_spent,
                    ttc_title,
                    ttr_usr_id,
                    usr_full_name
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "time_tracking,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "time_tracking_category,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    ttr_ttc_id=ttc_id AND
                    ttr_usr_id=usr_id AND
                    ttr_iss_id=" . Misc::escapeInteger($issue_id) . "
                 ORDER BY
                    ttr_created_date ASC";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return 0;
        }

        $total_time_spent = 0;
        $total_time_by_user = array();
        for ($i = 0; $i < count($res); $i++) {
            $res[$i]["ttr_summary"] = Link_Filter::processText(Issue::getProjectID($issue_id), nl2br(htmlspecialchars($res[$i]["ttr_summary"])));
            $res[$i]["formatted_time"] = Misc::getFormattedTime($res[$i]["ttr_time_spent"]);
            $res[$i]["ttr_created_date"] = Date_Helper::getFormattedDate($res[$i]["ttr_created_date"]);

            if (isset($total_time_by_user[$res[$i]['ttr_usr_id']])) {
               $total_time_by_user[$res[$i]['ttr_usr_id']]['time_spent'] += $res[$i]['ttr_time_spent'];
            } else {
                $total_time_by_user[$res[$i]['ttr_usr_id']] = array(
                    'usr_full_name' => $res[$i]['usr_full_name'],
                    'time_spent'    => $res[$i]['ttr_time_spent']
                );
            }
            $total_time_spent += $res[$i]["ttr_time_spent"];
        }
        usort($total_time_by_user, create_function('$a,$b', 'return $a["time_spent"]<$b["time_spent"];'));
        foreach ($total_time_by_user as &$item) {
            $item['time_spent'] = Misc::getFormattedTime($item['time_spent']);
        }
        return array(
            "total_time_spent"   => Misc::getFormattedTime($total_time_spent),
            "total_time_by_user" => $total_time_by_user,
            "list"               => $res
        );
    }


    /**
     * Method used to remove all time entries associated with the specified list
     * of issues.
     *
     * @access  public
     * @param   array $ids The list of issues
     * @return  boolean
     */
    function removeByIssues($ids)
    {
        $items = @implode(", ", Misc::escapeInteger($ids));
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "time_tracking
                 WHERE
                    ttr_iss_id IN ($items)";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        }
        return true;
    }


    /**
     * Method used to remove a specific time entry from the system.
     *
     * @access  public
     * @param   integer $time_id The time entry ID
     * @param   integer $usr_id The user ID of the person trying to remove this entry
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function removeEntry($time_id, $usr_id)
    {
        $time_id = Misc::escapeInteger($time_id);
        $stmt = "SELECT
                    ttr_iss_id issue_id,
                    ttr_usr_id owner_usr_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "time_tracking
                 WHERE
                    ttr_id=$time_id";
        $details = DB_Helper::getInstance()->getRow($stmt, DB_FETCHMODE_ASSOC);
        // check if the owner is the one trying to remove this entry
        if (($details['owner_usr_id'] != $usr_id) || (!Issue::canAccess($details['issue_id'], $usr_id))) {
            return -1;
        }

        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "time_tracking
                 WHERE
                    ttr_id=$time_id";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            Issue::markAsUpdated($details['issue_id']);
            // need to save a history entry for this
            History::add($details['issue_id'], $usr_id, History::getTypeID('time_removed'), ev_gettext('Time tracking entry removed by %1$s', User::getFullName($usr_id)));
            return 1;
        }
    }


    /**
     * Method used to add a new time entry in the system.
     *
     * @access  public
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function insertEntry()
    {
        if (!empty($_POST["date"])) {
            // format the date from the form
            $created_date = sprintf('%04d-%02d-%02d %02d:%02d:%02d',
                $_POST["date"]["Year"], $_POST["date"]["Month"],
                $_POST["date"]["Day"], $_POST["date"]["Hour"],
                $_POST["date"]["Minute"], 0);
            // convert the date to GMT timezone
            $created_date = Date_Helper::convertDateGMT($created_date);
        } else {
            $created_date = Date_Helper::getCurrentDateGMT();
        }
        $usr_id = Auth::getUserID();
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "time_tracking
                 (
                    ttr_ttc_id,
                    ttr_iss_id,
                    ttr_usr_id,
                    ttr_created_date,
                    ttr_time_spent,
                    ttr_summary
                 ) VALUES (
                    " . $_POST["category"] . ",
                    " . $_POST["issue_id"] . ",
                    $usr_id,
                    '$created_date',
                    " . Misc::escapeInteger($_POST["time_spent"]) . ",
                    '" . Misc::escapeString($_POST["summary"]) . "'
                 )";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        }

        Issue::markAsUpdated($_POST["issue_id"], 'time added');
        // need to save a history entry for this
        History::add($_POST["issue_id"], $usr_id, History::getTypeID('time_added'), ev_gettext('Time tracking entry submitted by %1$s', User::getFullName($usr_id)));
        return 1;
    }


    /**
     * Method used to remotely record a time tracking entry.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   integer $usr_id The user ID
     * @param   integer $cat_id The time tracking category ID
     * @param   string $summary The summary of the work entry
     * @param   integer $time_spent The time spent in minutes
     * @return  integer 1 if the insert worked, -1 otherwise
     */
    function recordRemoteEntry($issue_id, $usr_id, $cat_id, $summary, $time_spent)
    {
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "time_tracking
                 (
                    ttr_ttc_id,
                    ttr_iss_id,
                    ttr_usr_id,
                    ttr_created_date,
                    ttr_time_spent,
                    ttr_summary
                 ) VALUES (
                    " . Misc::escapeInteger($cat_id) . ",
                    " . Misc::escapeInteger($issue_id) . ",
                    " . Misc::escapeInteger($usr_id) . ",
                    '" . Date_Helper::getCurrentDateGMT() . "',
                    " . Misc::escapeInteger($time_spent) . ",
                    '" . Misc::escapeString($summary) . "'
                 )";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        }

        Issue::markAsUpdated($issue_id);
        // need to save a history entry for this
        History::add($issue_id, $usr_id, History::getTypeID('remote_time_added'), ev_gettext('Time tracking entry submitted remotely by %1$s', User::getFullName($usr_id)));
        return 1;
    }


    /**
     * Returns summary information about all time spent by a user in a specified time frame.
     *
     * @access  public
     * @param   string $usr_id The ID of the user this report is for.
     * @param   integer The timestamp of the beginning of the report.
     * @param   integer The timestamp of the end of this report.
     * @return  array An array of data containing information about time trackinge
     */
    function getSummaryByUser($usr_id, $start, $end)
    {
        $stmt = "SELECT
                    ttc_title,
                    COUNT(ttr_id) as total,
                    SUM(ttr_time_spent) as total_time
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "time_tracking,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "time_tracking_category
                 WHERE
                    iss_id = ttr_iss_id AND
                    ttr_ttc_id = ttc_id AND
                    iss_prj_id = " . Auth::getCurrentProject() . " AND
                    ttr_usr_id = " . Misc::escapeInteger($usr_id) . " AND
                    ttr_created_date BETWEEN '" . Misc::escapeString($start) . "' AND '" . Misc::escapeString($end) . "'
                 GROUP BY
                    ttc_title";
        $res = DB_Helper::getInstance()->getAssoc($stmt, false, array(), DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        }

        if (count($res) > 0) {
            foreach ($res as $index => $row) {
                $res[$index]["formatted_time"] = Misc::getFormattedTime($res[$index]["total_time"], true);
            }
        }
        return $res;
    }

    /**
     * Method used to get the time spent for a specific issue
     * at a specific time.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   string $usr_id The ID of the user this report is for.
     * @param   integer The timestamp of the beginning of the report.
     * @param   integer The timestamp of the end of this report.
     * @return  integer The time spent
     */
    function getTimeSpentByIssueAndTime($issue_id, $usr_id, $start, $end)
    {
        $stmt = "SELECT
                    SUM(ttr_time_spent)
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "time_tracking
                 WHERE
                    ttr_usr_id = " . Misc::escapeInteger($usr_id) . " AND
                    ttr_created_date BETWEEN '" . Misc::escapeString($start) . "' AND '" . Misc::escapeString($end) . "' AND
                    ttr_iss_id=" . Misc::escapeInteger($issue_id);
        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return 0;
        }

        return $res;
    }
    /**
     * Method used to add time spent on issue to a list of user issues.
     *
     * @access  private
     * @param   array $res User issues
     * @param   string $usr_id The ID of the user this report is for.
     * @param   integer $start The timestamp of the beginning of the report.
     * @param   integer $end The timestamp of the end of this report.
     * @return  void
     */
    function fillTimeSpentByIssueAndTime(&$res, $usr_id, $start, $end)
    {

        $issue_ids = array();
        for ($i = 0; $i < count($res); $i++) {
            $issue_ids[] = Misc::escapeInteger($res[$i]["iss_id"]);
        }
        $ids = implode(", ", $issue_ids);

        $stmt = "SELECT
                    ttr_iss_id, sum(ttr_time_spent)
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "time_tracking
                 WHERE
                    ttr_usr_id = " . Misc::escapeInteger($usr_id) . " AND
                    ttr_created_date BETWEEN '" . Misc::escapeString($start) . "' AND '" . Misc::escapeString($end) . "' AND
                    ttr_iss_id in ($ids)
                 GROUP BY ttr_iss_id";
        $result = DB_Helper::getInstance()->getAssoc($stmt);

        if (PEAR::isError($result)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return 0;
        }

        foreach($res as $key => $item) {
            @$res[$key]['it_spent'] = $result[$item['iss_id']];
            @$res[$key]['time_spent'] = Misc::getFormattedTime($result[$item['iss_id']], false);
        }
    }
}
