<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004 MySQL AB                                    |
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
// @(#) $Id: s.class.phone_support.php 1.6 03/12/31 17:29:01-00:00 jpradomaia $
//


/**
 * Class to handle the business logic related to the phone support
 * feature of the application.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

include_once(APP_INC_PATH . "class.error_handler.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.user.php");
include_once(APP_INC_PATH . "class.history.php");
include_once(APP_INC_PATH . "class.issue.php");
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.date.php");

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
        global $HTTP_POST_VARS;

        if (Validation::isWhitespace($HTTP_POST_VARS["title"])) {
            return -2;
        }
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_phone_category
                 (
                    phc_prj_id,
                    phc_title
                 ) VALUES (
                    " . $HTTP_POST_VARS["prj_id"] . ",
                    '" . Misc::escapeString($HTTP_POST_VARS["title"]) . "'
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
     * Method used to update the values stored in the database. 
     * Typically the user would modify the title of the category in 
     * the application and this method would be called.
     *
     * @access  public
     * @return  integer 1 if the update worked properly, any other value otherwise
     */
    function updateCategory()
    {
        global $HTTP_POST_VARS;

        if (Validation::isWhitespace($HTTP_POST_VARS["title"])) {
            return -2;
        }
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_phone_category
                 SET
                    phc_title='" . Misc::escapeString($HTTP_POST_VARS["title"]) . "'
                 WHERE
                    phc_prj_id=" . $HTTP_POST_VARS["prj_id"] . " AND
                    phc_id=" . $HTTP_POST_VARS["id"];
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
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
        global $HTTP_POST_VARS;

        $items = @implode(", ", $HTTP_POST_VARS["items"]);
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_phone_category
                 WHERE
                    phc_id IN ($items)";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
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
                    phc_id=$phc_id";
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
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
                    phc_prj_id=$prj_id
                 ORDER BY
                    phc_title ASC";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
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
                    phc_prj_id=$prj_id
                 ORDER BY
                    phc_id ASC";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
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
                    phs_id=$phs_id";
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
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
                    phc_title
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
                    phs_iss_id=$issue_id
                 ORDER BY
                    phs_created_date ASC";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]["phs_description"] = Misc::activateLinks(nl2br(htmlspecialchars($res[$i]["phs_description"])));
                $res[$i]["phs_description"] = Misc::activateIssueLinks($res[$i]["phs_description"]);
                $res[$i]["phs_created_date"] = Date_API::getFormattedDate($res[$i]["phs_created_date"]);
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
        global $HTTP_POST_VARS;

        $usr_id = Auth::getUserID();
        // format the date from the form
        $created_date = sprintf('%04d-%02d-%02d %02d:%02d:%02d', 
            $HTTP_POST_VARS["date"]["Year"], $HTTP_POST_VARS["date"]["Month"],
            $HTTP_POST_VARS["date"]["Day"], $HTTP_POST_VARS["date"]["Hour"],
            $HTTP_POST_VARS["date"]["Minute"], 0);
        // convert the date to GMT timezone
        $created_date = Date_API::getDateGMT($created_date);
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
                    " . $HTTP_POST_VARS["issue_id"] . ",
                    $usr_id,
                    " . $HTTP_POST_VARS["phone_category"] . ",
                    '" . Misc::escapeString($created_date) . "',
                    '" . Misc::escapeString($HTTP_POST_VARS["type"]) . "',
                    '" . Misc::escapeString($HTTP_POST_VARS["phone_number"]) . "',
                    '" . Misc::escapeString($HTTP_POST_VARS["description"]) . "',
                    '" . Misc::escapeString($HTTP_POST_VARS["phone_type"]) . "',
                    '" . Misc::escapeString($HTTP_POST_VARS["from_lname"]) . "',
                    '" . Misc::escapeString($HTTP_POST_VARS["from_fname"]) . "',
                    '" . Misc::escapeString($HTTP_POST_VARS["to_lname"]) . "',
                    '" . Misc::escapeString($HTTP_POST_VARS["to_fname"]) . "'
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            // enter the time tracking entry about this phone support entry
            $phs_id = $GLOBALS["db_api"]->get_last_insert_id();
            $HTTP_POST_VARS['category'] = Time_Tracking::getCategoryID('Telephone Discussion');
            $HTTP_POST_VARS['time_spent'] = $HTTP_POST_VARS['call_length'];
            $HTTP_POST_VARS['summary'] = 'Time entry inserted from phone call.';
            Time_Tracking::insertEntry();
            $stmt = "SELECT
                        max(ttr_id)
                     FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "time_tracking
                     WHERE
                        ttr_iss_id = " . $HTTP_POST_VARS["issue_id"] . " AND
                        ttr_usr_id = $usr_id";
            $ttr_id = $GLOBALS["db_api"]->dbh->getOne($stmt);
            
            Issue::markAsUpdated($HTTP_POST_VARS['issue_id'], 'phone call');
            // need to save a history entry for this
            History::add($HTTP_POST_VARS['issue_id'], $usr_id, History::getTypeID('phone_entry_added'), 
                            'Phone Support entry submitted by ' . User::getFullName($usr_id));
            // XXX: send notifications for the issue being updated (new notification type phone_support?)
            
            // update phone record with time tracking ID.
            if ((!empty($phs_id)) && (!empty($ttr_id))) {
                $stmt = "UPDATE
                            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "phone_support
                         SET
                            phs_ttr_id = $ttr_id
                         WHERE
                            phs_id = $phs_id";
                $res = $GLOBALS["db_api"]->dbh->query($stmt);
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
        $stmt = "SELECT
                    phs_iss_id,
                    phs_ttr_id,
                    phs_usr_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "phone_support
                 WHERE
                    phs_id=$phone_id";
        $details = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);

        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "phone_support
                 WHERE
                    phs_id=$phone_id";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            Issue::markAsUpdated($details["phs_iss_id"]);
            // need to save a history entry for this
            History::add($details["phs_iss_id"], Auth::getUserID(), History::getTypeID('phone_entry_removed'), 
                            'Phone Support entry removed by ' . User::getFullName(Auth::getUserID()));
            
            if (!empty($details["phs_ttr_id"])) {
                $time_result = Time_Tracking::removeEntry($details["phs_ttr_id"]);
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
        $items = implode(", ", $ids);
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "phone_support
                 WHERE
                    phs_iss_id IN ($items)";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
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
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "phone_support
                 WHERE
                    phs_created_date BETWEEN '$start' AND '$end' AND
                    phs_usr_id = $usr_id";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        }
        return $res;
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Phone_Support Class');
}
?>