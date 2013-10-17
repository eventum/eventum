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
 * Class to handle the business logic related to the phone support
 * feature of the application.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

class Phone_Support
{
    /**
     * Method used to add a new category to the application.
     *
     * @access  public
     * @return  integer 1 if the update worked properly, any other value otherwise
     */
    function insertCategory()
    {
        if (Validation::isWhitespace($_POST["title"])) {
            return -2;
        }
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_phone_category
                 (
                    phc_prj_id,
                    phc_title
                 ) VALUES (
                    " . Misc::escapeInteger($_POST["prj_id"]) . ",
                    '" . Misc::escapeString($_POST["title"]) . "'
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
     * Method used to update the values stored in the database.
     * Typically the user would modify the title of the category in
     * the application and this method would be called.
     *
     * @access  public
     * @return  integer 1 if the update worked properly, any other value otherwise
     */
    function updateCategory()
    {
        if (Validation::isWhitespace($_POST["title"])) {
            return -2;
        }
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_phone_category
                 SET
                    phc_title='" . Misc::escapeString($_POST["title"]) . "'
                 WHERE
                    phc_prj_id=" . Misc::escapeInteger($_POST["prj_id"]) . " AND
                    phc_id=" . Misc::escapeInteger($_POST["id"]);
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            return 1;
        }
    }


    /**
     * Method used to remove user-selected categories from the
     * database.
     *
     * @access  public
     * @return  boolean Whether the removal worked or not
     */
    function removeCategory()
    {
        $items = @implode(", ", Misc::escapeInteger($_POST["items"]));
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_phone_category
                 WHERE
                    phc_id IN ($items)";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method used to get the full details of a category.
     *
     * @access  public
     * @param   integer $phc_id The category ID
     * @return  array The information about the category provided
     */
    function getCategoryDetails($phc_id)
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_phone_category
                 WHERE
                    phc_id=" . Misc::escapeInteger($phc_id);
        $res = DB_Helper::getInstance()->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the full list of categories associated with
     * a specific project.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  array The full list of categories
     */
    function getCategoryList($prj_id)
    {
        $stmt = "SELECT
                    phc_id,
                    phc_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_phone_category
                 WHERE
                    phc_prj_id=" . Misc::escapeInteger($prj_id) . "
                 ORDER BY
                    phc_title ASC";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get an associative array of the list of
     * categories associated with a specific project.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  array The associative array of categories
     */
    function getCategoryAssocList($prj_id)
    {
        $stmt = "SELECT
                    phc_id,
                    phc_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_phone_category
                 WHERE
                    phc_prj_id=" . Misc::escapeInteger($prj_id) . "
                 ORDER BY
                    phc_id ASC";
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the details of a given phone support entry.
     *
     * @access  public
     * @param   integer $phs_id The phone support entry ID
     * @return  array The phone support entry details
     */
    function getDetails($phs_id)
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "phone_support
                 WHERE
                    phs_id=" . Misc::escapeInteger($phs_id);
        $res = DB_Helper::getInstance()->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the full listing of phone support entries
     * associated with a specific issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  array The list of notes
     */
    function getListing($issue_id)
    {
        $stmt = "SELECT
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "phone_support.*,
                    usr_full_name,
                    phc_title,
                    iss_prj_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "phone_support,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_phone_category,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 WHERE
                    phs_iss_id=iss_id AND
                    iss_prj_id=phc_prj_id AND
                    phs_phc_id=phc_id AND
                    phs_usr_id=usr_id AND
                    phs_iss_id=" .  Misc::escapeInteger($issue_id) . "
                 ORDER BY
                    phs_created_date ASC";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]["phs_description"] = Misc::activateLinks(nl2br(htmlspecialchars($res[$i]["phs_description"])));
                $res[$i]["phs_description"] = Link_Filter::processText($res[$i]['iss_prj_id'], $res[$i]["phs_description"]);
                $res[$i]["phs_created_date"] = Date_Helper::getFormattedDate($res[$i]["phs_created_date"]);
            }
            return $res;
        }
    }


    /**
     * Method used to add a phone support entry using the user
     * interface form available in the application.
     *
     * @access  public
     * @return  integer 1 if the insert worked, -1 or -2 otherwise
     */
    function insert()
    {
        $usr_id = Auth::getUserID();
        // format the date from the form
        $created_date = sprintf('%04d-%02d-%02d %02d:%02d:%02d',
            $_POST["date"]["Year"], $_POST["date"]["Month"],
            $_POST["date"]["Day"], $_POST["date"]["Hour"],
            $_POST["date"]["Minute"], 0);
        // convert the date to GMT timezone
        $created_date = Date_Helper::convertDateGMT($created_date);
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "phone_support
                 (
                    phs_iss_id,
                    phs_usr_id,
                    phs_phc_id,
                    phs_created_date,
                    phs_type,
                    phs_phone_number,
                    phs_description,
                    phs_phone_type,
                    phs_call_from_lname,
                    phs_call_from_fname,
                    phs_call_to_lname,
                    phs_call_to_fname
                 ) VALUES (
                    " . Misc::escapeInteger($_POST["issue_id"]) . ",
                    $usr_id,
                    " . Misc::escapeInteger($_POST["phone_category"]) . ",
                    '" . Misc::escapeString($created_date) . "',
                    '" . Misc::escapeString($_POST["type"]) . "',
                    '" . Misc::escapeString($_POST["phone_number"]) . "',
                    '" . Misc::escapeString($_POST["description"]) . "',
                    '" . Misc::escapeString($_POST["phone_type"]) . "',
                    '" . Misc::escapeString($_POST["from_lname"]) . "',
                    '" . Misc::escapeString($_POST["from_fname"]) . "',
                    '" . Misc::escapeString($_POST["to_lname"]) . "',
                    '" . Misc::escapeString($_POST["to_fname"]) . "'
                 )";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            // enter the time tracking entry about this phone support entry
            $phs_id = DB_Helper::get_last_insert_id();
            $prj_id = Auth::getCurrentProject();
            $_POST['category'] = Time_Tracking::getCategoryID($prj_id, 'Telephone Discussion');
            $_POST['time_spent'] = $_POST['call_length'];
            $_POST['summary'] = ev_gettext("Time entry inserted from phone call.");
            Time_Tracking::insertEntry();
            $stmt = "SELECT
                        max(ttr_id)
                     FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "time_tracking
                     WHERE
                        ttr_iss_id = " . Misc::escapeInteger($_POST["issue_id"]) . " AND
                        ttr_usr_id = $usr_id";
            $ttr_id = DB_Helper::getInstance()->getOne($stmt);

            Issue::markAsUpdated($_POST['issue_id'], 'phone call');
            // need to save a history entry for this
            History::add($_POST['issue_id'], $usr_id, History::getTypeID('phone_entry_added'),
                            ev_gettext('Phone Support entry submitted by %1$s', User::getFullName($usr_id)));
            // XXX: send notifications for the issue being updated (new notification type phone_support?)

            // update phone record with time tracking ID.
            if ((!empty($phs_id)) && (!empty($ttr_id))) {
                $stmt = "UPDATE
                            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "phone_support
                         SET
                            phs_ttr_id = $ttr_id
                         WHERE
                            phs_id = " . Misc::escapeInteger($phs_id);
                $res = DB_Helper::getInstance()->query($stmt);
                if (PEAR::isError($res)) {
                    Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                    return -1;
                }
            }
            return 1;
        }
    }


    /**
     * Method used to remove a specific phone support entry from the
     * application.
     *
     * @access  public
     * @param   integer $phone_id The phone support entry ID
     * @return  integer 1 if the removal worked, -1 or -2 otherwise
     */
    function remove($phone_id)
    {
        $phone_id = Misc::escapeInteger($phone_id);

        $stmt = "SELECT
                    phs_iss_id,
                    phs_ttr_id,
                    phs_usr_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "phone_support
                 WHERE
                    phs_id=$phone_id";
        $details = DB_Helper::getInstance()->getRow($stmt, DB_FETCHMODE_ASSOC);
        if ($details['phs_usr_id'] != Auth::getUserID()) {
            return -2;
        }

        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "phone_support
                 WHERE
                    phs_id=$phone_id";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            Issue::markAsUpdated($details["phs_iss_id"]);
            // need to save a history entry for this
            History::add($details["phs_iss_id"], Auth::getUserID(), History::getTypeID('phone_entry_removed'),
                            ev_gettext('Phone Support entry removed by %1$s', User::getFullName(Auth::getUserID())));

            if (!empty($details["phs_ttr_id"])) {
                $time_result = Time_Tracking::removeEntry($details["phs_ttr_id"], $details['phs_usr_id']);
                if ($time_result == 1) {
                    return 2;
                } else {
                    return $time_result;
                }
            } else {
                return 1;
            }
        }
    }


    /**
     * Method used to remove all phone support entries associated with
     * a given set of issues.
     *
     * @access  public
     * @param   array $ids The array of issue IDs
     * @return  boolean
     */
    function removeByIssues($ids)
    {
        $items = implode(", ", Misc::escapeInteger($ids));
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "phone_support
                 WHERE
                    phs_iss_id IN ($items)";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Returns the number of calls by a user in a time range.
     *
     * @access  public
     * @param   string $usr_id The ID of the user
     * @param   integer $start The timestamp of the start date
     * @param   integer $end The timestamp of the end date
     * @return  integer The number of phone calls by the user.
     */
    function getCountByUser($usr_id, $start, $end)
    {
        $stmt = "SELECT
                    COUNT(phs_id)
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "phone_support,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 WHERE
                    phs_iss_id = iss_id AND
                    iss_prj_id = " . Auth::getCurrentProject() . " AND
                    phs_created_date BETWEEN '" . Misc::escapeString($start) . "' AND '" . Misc::escapeString($end) . "' AND
                    phs_usr_id = " . Misc::escapeInteger($usr_id);
        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        }
        return $res;
    }
}
