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
// @(#) $Id: s.class.reminder.php 1.3 04/01/19 15:15:25-00:00 jpradomaia $
//


/**
 * Class to handle the business logic related to the reminder emails
 * that the system sends out.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

include_once(APP_INC_PATH . "class.error_handler.php");
include_once(APP_INC_PATH . "class.reminder_action.php");

class Reminder
{
    /**
     * Method used to get the title of a specific reminder.
     *
     * @access  public
     * @param   integer $rem_id The reminder ID
     * @return  string The title of the reminder
     */
    function getTitle($rem_id)
    {
        $stmt = "SELECT
                    rem_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_level
                 WHERE
                    rem_id=$rem_id";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return '';
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the project associated to a given reminder.
     *
     * @access  public
     * @param   integer $rem_id The reminder ID
     * @return  integer The project ID
     */
    function getProjectID($rem_id)
    {
        $stmt = "SELECT
                    rem_prj_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_level
                 WHERE
                    rem_id=$rem_id";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return '';
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the details for a specific reminder.
     *
     * @access  public
     * @param   integer $rem_id The reminder ID
     * @return  array The details for the specified reminder
     */
    function getDetails($rem_id)
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_level
                 WHERE
                    rem_id=$rem_id";
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return '';
        } else {
            $requirements = Reminder::getRequirements($rem_id);
            if (!empty($requirements)) {
                $res['type'] = $requirements['type'];
                if ($res['type'] == 'issue') {
                    $res['rer_iss_id'] = $requirements['values'];
                }
            }
            $priorities = Reminder::getAssociatedPriorities($rem_id);
            if (count($priorities) > 0) {
                $res['check_priority'] = 'yes';
                $res['rer_pri_id'] = $priorities;
            }
            return $res;
        }
    }


    /**
     * Method used to get a list of all priority IDs associated with the given
     * reminder.
     *
     * @access  public
     * @param   integer $rem_id The reminder ID
     * @return  array The list of associated priority IDs
     */
    function getAssociatedPriorities($rem_id)
    {
        $stmt = "SELECT
                    rep_pri_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_priority
                 WHERE
                    rep_rem_id=$rem_id";
        $res = $GLOBALS["db_api"]->dbh->getCol($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            return $res;
        }
    }


    /**
     * Method used to get a list of all issue IDs associated with the given
     * reminder.
     *
     * @access  public
     * @param   integer $rem_id The reminder ID
     * @return  array The list of associated issue IDs
     */
    function getAssociatedIssues($rem_id)
    {
        $stmt = "SELECT
                    rer_iss_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_requirement
                 WHERE
                    rer_rem_id=$rem_id";
        $res = $GLOBALS["db_api"]->dbh->getCol($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            return $res;
        }
    }


    /**
     * Method used to associate an issue with a given reminder.
     *
     * @access  public
     * @param   integer $rem_id The reminder ID
     * @param   integer $issue_id The issue ID
     * @return  boolean
     */
    function addIssueAssociation($rem_id, $issue_id)
    {
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_requirement
                 (
                    rer_rem_id,
                    rer_iss_id
                 ) VALUES (
                    $rem_id,
                    $issue_id
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method used to associate a reminder with any issue.
     *
     * @access  public
     * @param   integer $rem_id The reminder ID
     * @return  boolean
     */
    function associateAllIssues($rem_id)
    {
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_requirement
                 (
                    rer_rem_id,
                    rer_trigger_all_issues
                 ) VALUES (
                    $rem_id,
                    1
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method used to associate a priority with a given reminder.
     *
     * @access  public
     * @param   integer $rem_id The reminder ID
     * @param   integer $priority_id The priority ID
     * @return  boolean
     */
    function addPriorityAssociation($rem_id, $priority_id)
    {
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_priority
                 (
                    rep_rem_id,
                    rep_pri_id
                 ) VALUES (
                    $rem_id,
                    $priority_id
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method used to remove all requirements and priority associations for a
     * given reminder.
     *
     * @access  public
     * @param   integer $rem_id The reminder ID
     */
    function removeAllAssociations($rem_id)
    {
        if (!is_array($rem_id)) {
            $rem_id = array($rem_id);
        }
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_requirement
                 WHERE
                    rer_rem_id IN (" . implode(',', $rem_id) . ")";
        $GLOBALS["db_api"]->dbh->query($stmt);
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_priority
                 WHERE
                    rep_rem_id IN (" . implode(',', $rem_id) . ")";
        $GLOBALS["db_api"]->dbh->query($stmt);
    }


    /**
     * Method used to create a new reminder.
     *
     * @access  public
     * @return  integer 1 if the insert worked, -1 or -2 otherwise
     */
    function insert()
    {
        global $HTTP_POST_VARS;

        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_level
                 (
                    rem_created_date,
                    rem_title,
                    rem_prj_id
                 ) VALUES (
                    '" . Date_API::getCurrentDateGMT() . "',
                    '" . Misc::runSlashes($HTTP_POST_VARS['title']) . "',
                    " . $HTTP_POST_VARS['project'] . "
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $new_rem_id = $GLOBALS["db_api"]->get_last_insert_id();
            // map the reminder requirements now
            if ((@$HTTP_POST_VARS['reminder_type'] == 'issue') && (count($HTTP_POST_VARS['issues']) > 0)) {
                for ($i = 0; $i < count($HTTP_POST_VARS['issues']); $i++) {
                    Reminder::addIssueAssociation($new_rem_id, $HTTP_POST_VARS['issues'][$i]);
                }
            } elseif (@$HTTP_POST_VARS['reminder_type'] == 'all_issues') {
                 Reminder::associateAllIssues($new_rem_id);
            }
            if ((@$HTTP_POST_VARS['check_priority'] == 'yes') && (count($HTTP_POST_VARS['priorities']) > 0)) {
                for ($i = 0; $i < count($HTTP_POST_VARS['priorities']); $i++) {
                    Reminder::addPriorityAssociation($new_rem_id, $HTTP_POST_VARS['priorities'][$i]);
                }
            }
            return 1;
        }
    }


    /**
     * Method used to update the details of a specific reminder.
     *
     * @access  public
     * @return  integer 1 if the update worked, -1 or -2 otherwise
     */
    function update()
    {
        global $HTTP_POST_VARS;

        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_level
                 SET
                    rem_last_updated_date='" . Date_API::getCurrentDateGMT() . "',
                    rem_title='" . Misc::runSlashes($HTTP_POST_VARS['title']) . "',
                    rem_prj_id=" . $HTTP_POST_VARS['project'] . "
                 WHERE
                    rem_id=" . $HTTP_POST_VARS['id'];
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            Reminder::removeAllAssociations($HTTP_POST_VARS['id']);
            // map the reminder requirements now
            if ((@$HTTP_POST_VARS['reminder_type'] == 'issue') && (count($HTTP_POST_VARS['issues']) > 0)) {
                for ($i = 0; $i < count($HTTP_POST_VARS['issues']); $i++) {
                    Reminder::addIssueAssociation($HTTP_POST_VARS['id'], $HTTP_POST_VARS['issues'][$i]);
                }
            } elseif (@$HTTP_POST_VARS['reminder_type'] == 'all_issues') {
                 Reminder::associateAllIssues($HTTP_POST_VARS['id']);
            }
            if ((@$HTTP_POST_VARS['check_priority'] == 'yes') && (count($HTTP_POST_VARS['priorities']) > 0)) {
                for ($i = 0; $i < count($HTTP_POST_VARS['priorities']); $i++) {
                    Reminder::addPriorityAssociation($HTTP_POST_VARS['id'], $HTTP_POST_VARS['priorities'][$i]);
                }
            }
            return 1;
        }
    }


    /**
     * Method used to remove reminders by using the administrative
     * interface of the system.
     *
     * @access  public
     * @return  boolean
     */
    function remove()
    {
        global $HTTP_POST_VARS;

        $items = @implode(", ", $HTTP_POST_VARS["items"]);
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_level
                 WHERE
                    rem_id IN ($items)";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            Reminder::removeAllAssociations($HTTP_POST_VARS["items"]);
            $stmt = "SELECT
                        rma_id
                     FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_action
                     WHERE
                        rma_rem_id IN ($items)";
            $actions = $GLOBALS["db_api"]->dbh->getCol($stmt);
            if (count($actions) > 0) {
                $stmt = "DELETE FROM
                            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_action
                         WHERE
                            rma_id IN (" . implode(', ', $actions) . ")";
                $GLOBALS["db_api"]->dbh->query($stmt);
                $stmt = "DELETE FROM
                            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_history
                         WHERE
                            rmh_rma_id IN (" . implode(', ', $actions) . ")";
                $GLOBALS["db_api"]->dbh->query($stmt);
                $stmt = "DELETE FROM
                            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_level_condition
                         WHERE
                            rlc_rma_id IN (" . implode(', ', $actions) . ")";
                $GLOBALS["db_api"]->dbh->query($stmt);
            }
            return true;
        }
    }


    /**
     * Method used to get the list of requirements associated with a given
     * reminder.
     *
     * @access  public
     * @param   integer $rem_id The reminder ID
     * @return  array The list of requirements
     */
    function getRequirements($rem_id)
    {
        $stmt = "SELECT
                    rer_iss_id,
                    rer_trigger_all_issues
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_requirement
                 WHERE
                    rer_rem_id=$rem_id";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return '';
        } else {
            $type = '';
            $values = array();
            for ($i = 0; $i < count($res); $i++) {
                if ($res[$i]['rer_trigger_all_issues'] == '1') {
                    return array('type' => 'ALL');
                } elseif (!empty($res[$i]['rer_iss_id'])) {
                    $type = 'issue';
                    $values[] = $res[$i]['rer_iss_id'];
                }
            }
            return array(
                'type'   => $type,
                'values' => $values
            );
        }
    }


    /**
     * Method used to get the list of reminders to be displayed in the 
     * administration section.
     *
     * @access  public
     * @return  array The list of reminders
     */
    function getAdminList()
    {
        $stmt = "SELECT
                    " . APP_TABLE_PREFIX . "reminder_level.*,
                    prj_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_level,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project
                 WHERE
                    rem_prj_id=prj_id
                 ORDER BY
                    rem_id ASC";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            $priority_titles = Misc::getAssocPriorities();
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]['rem_created_date'] = Date_API::getFormattedDate($res[$i]["rem_created_date"]);
                $actions = Reminder_Action::getList($res[$i]['rem_id']);
                $res[$i]['total_actions'] = count($actions);
                $priorities = Reminder::getAssociatedPriorities($res[$i]['rem_id']);
                $res[$i]['priorities'] = array();
                if (count($priorities) > 0) {
                    foreach ($priorities as $pri_id) {
                        $res[$i]['priorities'][] = $priority_titles[$pri_id];
                    }
                } else {
                    $res[$i]['priorities'][] = 'Any';
                }
                $requirements = Reminder::getRequirements($res[$i]['rem_id']);
                $res[$i]['type'] = $requirements['type'];
            }
            return $res;
        }
    }


    /**
     * Method used to get the full list of reminders.
     *
     * @access  public
     * @return  array The list of reminders
     */
    function getList()
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_level
                 ORDER BY
                    rem_id ASC";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            if (empty($res)) {
                return array();
            } else {
                $t = array();
                for ($i = 0; $i < count($res); $i++) {
                    // ignore reminders that have no actions set yet...
                    $actions = Reminder_Action::getList($res[$i]['rem_id']);
                    if (count($actions) == 0) {
                        continue;
                    }
                    $res[$i]['actions'] = $actions;
                    $t[] = $res[$i];
                }
                return $t;
            }
        }
    }


    /**
     * Method used to get the list of issue IDs that match the given conditions.
     *
     * @access  public
     * @param   integer $rem_id The reminder ID
     * @param   array $conditions The list of conditions
     * @return  array The list of issue IDs
     */
    function getTriggeredIssues($reminder, $conditions)
    {
        // - build the SQL query to check if we have an issue that matches these conditions...
        $stmt = "SELECT
                    iss_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue";
        $stmt .= Reminder::getWhereClause($reminder, $conditions);
        $res = $GLOBALS["db_api"]->dbh->getCol($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            // - if query returns >= 1, then run the appropriate action
            if (empty($res)) {
                return array();
            } else {
                return $res;
            }
        }
    }


    /**
     * Method used to generate a where clause from the given list of conditions.
     *
     * @access  public
     * @param   integer $rem_id The reminder ID
     * @param   array $conditions The list of conditions
     * @return  string The where clause
     */
    function getWhereClause($reminder, $conditions)
    {
        $stmt = '
                  WHERE
                    iss_prj_id=' . $reminder['rem_prj_id'] . "\n";
        $requirement = Reminder::getRequirements($reminder['rem_id']);
        if ($requirement['type'] == 'issue') {
            $stmt .= ' AND iss_id IN (' . implode(', ', $requirement['values']) . ")\n";
        }
        $priorities = Reminder::getAssociatedPriorities($reminder['rem_id']);
        if (count($priorities) > 0) {
            $stmt .= ' AND iss_pri_id IN (' . implode(', ', $priorities) . ")\n";
        }
        // now for the interesting stuff
        for ($i = 0; $i < count($conditions); $i++) {
            // date field values are always saved as number of hours, so let's calculate them now as seconds
            if (stristr($conditions[$i]['rmf_title'], 'date')) {
                // support NULL as values for a date field
                if (strtoupper($conditions[$i]['rlc_value']) == 'NULL') {
                    $conditions[$i]['rmf_sql_representation'] = $conditions[$i]['rmf_sql_field'];
                } else {
                    $conditions[$i]['rlc_value'] = $conditions[$i]['rlc_value'] * 60 * 60;
                }
            }
            $stmt .= sprintf(" AND %s %s %s\n", $conditions[$i]['rmf_sql_representation'],
                                              $conditions[$i]['rmo_sql_representation'],
                                              $conditions[$i]['rlc_value']);
        }
        return $stmt;
    }


    /**
     * Method used to generate an SQL query to be used in debugging the reminder
     * conditions.
     *
     * @access  public
     * @param   integer $rem_id The reminder ID
     * @param   integer $rma_id The reminder action ID
     * @return  string The SQL query
     */
    function getSQLQuery($rem_id, $rma_id)
    {
        $reminder = Reminder::getDetails($rem_id);
        $conditions = Reminder_Condition::getList($rma_id);
        $stmt = "SELECT
                    iss_id
                 FROM
                    " . APP_TABLE_PREFIX . "issue";
        $stmt .= Reminder::getWhereClause($reminder, $conditions);
        return $stmt;
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Reminder Class');
}
?>