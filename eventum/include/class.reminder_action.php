<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005 MySQL AB                              |
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
// @(#) $Id: s.class.reminder_action.php 1.2 04/01/19 15:15:25-00:00 jpradomaia $
//

include_once(APP_INC_PATH . "class.error_handler.php");
include_once(APP_INC_PATH . "class.reminder_condition.php");
include_once(APP_INC_PATH . "class.notification.php");
include_once(APP_INC_PATH . "class.user.php");
include_once(APP_INC_PATH . "class.group.php");
include_once(APP_INC_PATH . "class.mail.php");
include_once(APP_INC_PATH . "class.issue.php");
include_once(APP_INC_PATH . "class.validation.php");

/**
 * Class to handle the business logic related to the reminder emails
 * that the system sends out.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

class Reminder_Action
{
    /**
     * Method used to quickly change the ranking of a reminder action
     * from the administration screen.
     *
     * @access  public
     * @param   integer $rem_id The reminder ID
     * @param   integer $rma_id The reminder action ID
     * @param   string $rank_type Whether we should change the entry down or up (options are 'asc' or 'desc')
     * @return  boolean
     */
    function changeRank($rem_id, $rma_id, $rank_type)
    {
        // check if the current rank is not already the first or last one
        $ranking = Reminder_Action::_getRanking($rem_id);
        $ranks = array_values($ranking);
        $ids = array_keys($ranking);
        $last = end($ids);
        $first = reset($ids);
        if ((($rank_type == 'asc') && ($rma_id == $first)) ||
                (($rank_type == 'desc') && ($rma_id == $last))) {
            return false;
        }

        if ($rank_type == 'asc') {
            $diff = -1;
        } else {
            $diff = 1;
        }
        $new_rank = $ranking[$rma_id] + $diff;
        if (in_array($new_rank, $ranks)) {
            // switch the rankings here...
            $index = array_search($new_rank, $ranks);
            $replaced_rma_id = $ids[$index];
            $stmt = "UPDATE
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_action
                     SET
                        rma_rank=" . Misc::escapeInteger($ranking[$rma_id]) . "
                     WHERE
                        rma_id=" . Misc::escapeInteger($replaced_rma_id);
            $GLOBALS["db_api"]->dbh->query($stmt);
        }
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_action
                 SET
                    rma_rank=" . Misc::escapeInteger($new_rank) . "
                 WHERE
                    rma_id=" . Misc::escapeInteger($rma_id);
        $GLOBALS["db_api"]->dbh->query($stmt);
        return true;
    }


    /**
     * Returns an associative array with the list of reminder action 
     * IDs and their respective ranking.
     *
     * @access  private
     * @param   integer $rem_id The reminder ID
     * @return  array The list of reminder actions
     */
    function _getRanking($rem_id)
    {
        $stmt = "SELECT
                    rma_id,
                    rma_rank
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_action
                 WHERE
                    rma_rem_id = " . Misc::escapeInteger($rem_id) . "
                 ORDER BY
                    rma_rank ASC";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the title of a specific reminder action.
     *
     * @access  public
     * @param   integer $rma_id The reminder action ID
     * @return  string The title of the reminder action
     */
    function getTitle($rma_id)
    {
        $stmt = "SELECT
                    rma_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_action
                 WHERE
                    rma_id=" . Misc::escapeInteger($rma_id);
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return '';
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the details for a specific reminder action.
     *
     * @access  public
     * @param   integer $rma_id The reminder action ID
     * @return  array The details for the specified reminder action
     */
    function getDetails($rma_id)
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_action
                 WHERE
                    rma_id=" . Misc::escapeInteger($rma_id);
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return '';
        } else {
            // get the user list, if appropriate
            if (Reminder_Action::isUserList($res['rma_rmt_id'])) {
                $res['user_list'] = Reminder_Action::getUserList($res['rma_id']);
            }
            return $res;
        }
    }


    /**
     * Method used to create a new reminder action.
     *
     * @access  public
     * @return  integer 1 if the insert worked, -1 or -2 otherwise
     */
    function insert()
    {
        global $HTTP_POST_VARS;

        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_action
                 (
                    rma_rem_id,
                    rma_rmt_id,
                    rma_created_date,
                    rma_title,
                    rma_rank,
                    rma_alert_irc,
                    rma_alert_group_leader,
                    rma_boilerplate
                 ) VALUES (
                    " . Misc::escapeInteger($HTTP_POST_VARS['rem_id']) . ",
                    " . Misc::escapeInteger($HTTP_POST_VARS['type']) . ",
                    '" . Date_API::getCurrentDateGMT() . "',
                    '" . Misc::escapeString($HTTP_POST_VARS['title']) . "',
                    '" . Misc::escapeInteger($HTTP_POST_VARS['rank']) . "',
                    " . Misc::escapeInteger($HTTP_POST_VARS['alert_irc']) . ",
                    " . Misc::escapeInteger($HTTP_POST_VARS['alert_group_leader']) . ",
                    '" . Misc::escapeString($HTTP_POST_VARS['boilerplate']) . "'
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $new_rma_id = $GLOBALS["db_api"]->get_last_insert_id();
            // add the user list, if appropriate
            if (Reminder_Action::isUserList($HTTP_POST_VARS['type'])) {
                Reminder_Action::associateUserList($new_rma_id, $HTTP_POST_VARS['user_list']);
            }
            return 1;
        }
    }


    /**
     * Returns the list of users associated with a given reminder
     * action ID
     *
     * @access  public
     * @param   integer $rma_id The reminder action ID
     * @return  array The list of associated users
     */
    function getUserList($rma_id)
    {
        $stmt = "SELECT
                    ral_usr_id,
                    ral_email
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_action_list
                 WHERE
                    ral_rma_id=" . Misc::escapeInteger($rma_id);
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            $t = array();
            for ($i = 0; $i < count($res); $i++) {
                if (Validation::isEmail($res[$i]['ral_email'])) {
                    $t[$res[$i]['ral_email']] = $res[$i]['ral_email'];
                } else {
                    $t[$res[$i]['ral_usr_id']] = User::getFullName($res[$i]['ral_usr_id']);
                }
            }
            return $t;
        }
    }


    /**
     * Method used to associate a list of users with a given reminder
     * action ID
     *
     * @access  public
     * @param   integer $rma_id The reminder action ID
     * @param   array $user_list The list of users
     * @return  void
     */
    function associateUserList($rma_id, $user_list)
    {
        for ($i = 0; $i < count($user_list); $i++) {
            $usr_id = 0;
            $email = '';
            if (!Validation::isEmail($user_list[$i])) {
                $usr_id = $user_list[$i];
            } else {
                $email = $user_list[$i];
            }
            $stmt = "INSERT INTO
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_action_list
                     (
                        ral_rma_id,
                        ral_usr_id,
                        ral_email
                     ) VALUES (
                        " . Misc::escapeInteger($rma_id) . ",
                        " . Misc::escapeInteger($usr_id) . ",
                        '" . Misc::escapeString($email) . "'
                     )";
            $GLOBALS["db_api"]->dbh->query($stmt);
        }
    }


    /**
     * Method used to update the details of a specific reminder action.
     *
     * @access  public
     * @return  integer 1 if the update worked, -1 or -2 otherwise
     */
    function update()
    {
        global $HTTP_POST_VARS;

        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_action
                 SET
                    rma_last_updated_date='" . Date_API::getCurrentDateGMT() . "',
                    rma_rank='" . Misc::escapeInteger($HTTP_POST_VARS['rank']) . "',
                    rma_title='" . Misc::escapeString($HTTP_POST_VARS['title']) . "',
                    rma_rmt_id=" . Misc::escapeInteger($HTTP_POST_VARS['type']) . ",
                    rma_alert_irc=" . Misc::escapeInteger($HTTP_POST_VARS['alert_irc']) . ",
                    rma_alert_group_leader=" . Misc::escapeInteger($HTTP_POST_VARS['alert_group_leader']) . ",
                    rma_boilerplate='" . Misc::escapeString($HTTP_POST_VARS['boilerplate']) . "'
                 WHERE
                    rma_id=" . Misc::escapeInteger($HTTP_POST_VARS['id']);
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            // remove any user list associated with this reminder action
            Reminder_Action::clearActionUserList($HTTP_POST_VARS['id']);
            // add the user list back in, if appropriate
            if (Reminder_Action::isUserList($HTTP_POST_VARS['type'])) {
                Reminder_Action::associateUserList($HTTP_POST_VARS['id'], $HTTP_POST_VARS['user_list']);
            }
            return 1;
        }
    }


    /**
     * Checks whether the given reminder action type is one where a
     * list of users is used or not.
     *
     * @access  public
     * @param   integer $rmt_id The reminder action type ID
     * @return  boolean
     */
    function isUserList($rmt_id)
    {
        $stmt = "SELECT
                    rmt_type
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_action_type
                 WHERE
                    rmt_id=" . Misc::escapeInteger($rmt_id);
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            $user_list_types = array(
                'sms_list',
                'email_list'
            );
            if (!in_array($res, $user_list_types)) {
                return false;
            } else {
                return true;
            }
        }
    }


    /**
     * Removes the full user list for a given reminder action ID.
     *
     * @access  public
     * @param   integer $rma_id The reminder action ID
     * @return  void
     */
    function clearActionUserList($rma_id)
    {
        if (!is_array($rma_id)) {
            $rma_id = array($rma_id);
        }
        $items = @implode(", ", Misc::escapeInteger($rma_id));
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_action_list
                 WHERE
                    ral_rma_id IN ($items)";
        $GLOBALS["db_api"]->dbh->query($stmt);
    }


    /**
     * Method used to remove reminder actions by using the administrative
     * interface of the system.
     *
     * @access  public
     * @return  boolean
     */
    function remove($action_ids)
    {
        $items = @implode(", ", Misc::escapeInteger($action_ids));
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_action
                 WHERE
                    rma_id IN ($items)";
        $GLOBALS["db_api"]->dbh->query($stmt);
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_history
                 WHERE
                    rmh_rma_id IN ($items)";
        $GLOBALS["db_api"]->dbh->query($stmt);
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_level_condition
                 WHERE
                    rlc_rma_id IN ($items)";
        $GLOBALS["db_api"]->dbh->query($stmt);
        Reminder_Action::clearActionUserList($action_ids);
    }


    /**
     * Method used to get an associative array of action types.
     *
     * @access  public
     * @return  array The list of action types
     */
    function getActionTypeList()
    {
        $stmt = "SELECT
                    rmt_id,
                    rmt_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_action_type
                 ORDER BY
                    rmt_title ASC";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the list of reminder actions to be displayed in the 
     * administration section.
     *
     * @access  public
     * @param   integer $rem_id The reminder ID
     * @return  array The list of reminder actions
     */
    function getAdminList($rem_id)
    {
        $stmt = "SELECT
                    rma_rem_id,
                    rma_id,
                    rma_title,
                    rmt_title,
                    rma_rank,
                    rma_alert_irc,
                    rma_alert_group_leader
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_action,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_action_type
                 WHERE
                    rma_rmt_id=rmt_id AND
                    rma_rem_id=" . Misc::escapeInteger($rem_id) . "
                 ORDER BY
                    rma_rank ASC";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            for ($i = 0; $i < count($res); $i++) {
                $conditions = Reminder_Condition::getList($res[$i]['rma_id']);
                $res[$i]['total_conditions'] = count($conditions);
                foreach ($conditions as $condition) {
                    if ($condition['rmf_sql_field'] == 'iss_sta_id') {
                        $res[$i]['status'] = Status::getStatusTitle($condition['rlc_value']);
                    }
                }
            }
            return $res;
        }
    }


    /**
     * Method used to get the list of reminder actions associated with a given
     * reminder ID.
     *
     * @access  public
     * @param   integer $reminder_id The reminder ID
     * @return  array The list of reminder actions
     */
    function getList($reminder_id)
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_action
                 WHERE
                    rma_rem_id=" . Misc::escapeInteger($reminder_id) . "
                 ORDER BY
                    rma_rank ASC";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            if (empty($res)) {
                return array();
            } else {
                return $res;
            }
        }
    }


    /**
     * Method used to get the title of a reminder action type.
     *
     * @access  public
     * @param   integer $rmt_id The reminder action type
     * @return  string The action type title
     */
    function getActionType($rmt_id)
    {
        $stmt = "SELECT
                    rmt_type
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_action_type
                 WHERE
                    rmt_id=" . Misc::escapeInteger($rmt_id);
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return '';
        } else {
            return $res;
        }
    }


    /**
     * Method used to save a history entry about the execution of the current
     * reminder.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   integer $rma_id The reminder action ID
     * @return  boolean
     */
    function saveHistory($issue_id, $rma_id)
    {
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_history
                 (
                    rmh_iss_id,
                    rmh_rma_id,
                    rmh_created_date
                 ) VALUES (
                    " . Misc::escapeInteger($issue_id) . ",
                    " . Misc::escapeInteger($rma_id) . ",
                    '" . Date_API::getCurrentDateGMT() . "'
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
     * Method used to perform a specific action to an issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   array $reminder The reminder details
     * @param   array $action The action details
     * @return  boolean
     */
    function perform($issue_id, $reminder, $action)
    {
        $type = '';
        // - see which action type we're talking about here...
        $action_type = Reminder_Action::getActionType($action['rma_rmt_id']);
        // - do we also need to alert the group leader about this?
        $group_leader_usr_id = 0;
        if ($action['rma_alert_group_leader']) {
            if (Reminder::isDebug()) {
                echo "  - Processing Group Leader notification\n";
            }
            $group_id = Issue::getGroupID($issue_id);
            // check if there's even a group associated with this issue
            if (empty($group_id)) {
                if (Reminder::isDebug()) {
                    echo "  - No group associated with issue $issue_id\n";
                }
            } else {
                $group_details = Group::getDetails($group_id);
                if (!empty($group_details['grp_manager_usr_id'])) {
                    $group_leader_usr_id = $group_details['grp_manager_usr_id'];
                }
            }
        }
        if (Reminder::isDebug()) {
           echo "  - Performing action '$action_type' for issue #$issue_id\n";
        }
        switch ($action_type) {
            case 'email_assignee':
                $type = 'email';
                $assignees = Issue::getAssignedUserIDs($issue_id);
                $to = array();
                foreach ($assignees as $assignee) {
                    $to[] = User::getFromHeader($assignee);
                }
                // add the group leader to the recipient list, if needed
                if (!empty($group_leader_usr_id)) {
                    $leader_email = User::getFromHeader($group_leader_usr_id);
                    if ((!empty($leader_email)) && (!in_array($leader_email, $to))) {
                        $to[] = $leader_email;
                    }
                }
                break;
            case 'email_list':
                $type = 'email';
                $list = Reminder_Action::getUserList($action['rma_id']);
                $to = array();
                foreach ($list as $key => $value) {
                    // add the recipient to the list if it's a simple email address
                    if (Validation::isEmail($key)) {
                        $to[] = $key;
                    } else {
                        $to[] = User::getFromHeader($key);
                    }
                }
                // add the group leader to the recipient list, if needed
                if (!empty($group_leader_usr_id)) {
                    $leader_email = User::getFromHeader($group_leader_usr_id);
                    if ((!empty($leader_email)) && (!in_array($leader_email, $to))) {
                        $to[] = $leader_email;
                    }
                }
                break;
            case 'sms_assignee':
                $type = 'sms';
                $assignees = Issue::getAssignedUserIDs($issue_id);
                $to = array();
                foreach ($assignees as $assignee) {
                    if (User::isClockedIn($assignee)) {
                        $sms_email = User::getSMS($assignee);
                        if (!empty($sms_email)) {
                            $to[] = $sms_email;
                        }
                    }
                }
                // add the group leader to the recipient list, if needed
                if ((!empty($group_leader_usr_id)) && (User::isClockedIn($group_leader_usr_id))) {
                    $leader_sms_email = User::getSMS($group_leader_usr_id);
                    if ((!empty($leader_sms_email)) && (!in_array($leader_sms_email, $to))) {
                        $to[] = $leader_sms_email;
                    }
                }
                break;
            case 'sms_list':
                $type = 'sms';
                $list = Reminder_Action::getUserList($action['rma_id']);
                $to = array();
                foreach ($list as $key => $value) {
                    // add the recipient to the list if it's a simple email address
                    if (Validation::isEmail($key)) {
                        $to[] = $key;
                    } else {
                        // otherwise, check for the clocked-in status
                        if (User::isClockedIn($key)) {
                            $sms_email = User::getSMS($key);
                            if (!empty($sms_email)) {
                                $to[] = $sms_email;
                            }
                        }
                    }
                }
                // add the group leader to the recipient list, if needed
                if ((!empty($group_leader_usr_id)) && (User::isClockedIn($group_leader_usr_id))) {
                    $leader_sms_email = User::getSMS($group_leader_usr_id);
                    if ((!empty($leader_sms_email)) && (!in_array($leader_sms_email, $to))) {
                        $to[] = $leader_sms_email;
                    }
                }
                break;
        }
        $data = Notification::getIssueDetails($issue_id);
        $conditions = Reminder_Condition::getAdminList($action['rma_id']);
        // alert IRC if needed
        if ($action['rma_alert_irc']) {
            if (Reminder::isDebug()) {
                echo "  - Processing IRC notification\n";
            }
            $irc_notice = "Issue #$issue_id (Priority: " . $data['pri_title'];
            // also add information about the assignee, if any
            $assignment = Issue::getAssignedUsers($issue_id);
            if (count($assignment) > 0) {
                $irc_notice .= "; Assignment: " . implode(', ', $assignment);
            }
            if (!empty($data['iss_grp_id'])) {
                $irc_notice .= "; Group: " . Group::getName($data['iss_grp_id']);
            }
            $irc_notice .= "), Reminder action '" . $action['rma_title'] . "' was just triggered";
            Notification::notifyIRC(Issue::getProjectID($issue_id), $irc_notice, $issue_id);
        }
        $setup = Setup::load();
        // if there are no recipients, then just skip to the next action
        if (count($to) == 0) {
            if (Reminder::isDebug()) {
                echo "  - No recipients could be found\n";
            }
            // if not even an irc alert was sent, then save 
            // a notice about this on reminder_sent@, if needed
            if (!$action['rma_alert_irc']) {
                if (@$setup['email_reminder']['status'] == 'enabled') {
                    Reminder_Action::_recordNoRecipientError($issue_id, $type, $reminder, $action);
                }
                return false;
            }
        }
        // - save a history entry about this action
        Reminder_Action::saveHistory($issue_id, $action['rma_id']);
        // - save this action as the latest triggered one for the given issue ID
        Reminder_Action::recordLastTriggered($issue_id, $action['rma_id']);

        // - perform the action
        if (count($to) > 0) {
            // send a copy of this reminder to reminder_sent@, if needed
            if ((@$setup['email_reminder']['status'] == 'enabled') &&
                    (!empty($setup['email_reminder']['addresses']))) {
                $addresses = Reminder::_getReminderAlertAddresses();
                if (count($addresses) > 0) {
                    $to = array_merge($to, $addresses);
                }
            }
            $tpl = new Template_API;
            $tpl->setTemplate('reminders/' . $type . '_alert.tpl.text');
            $tpl->bulkAssign(array(
                "data"                     => $data,
                "reminder"                 => $reminder,
                "action"                   => $action,
                "conditions"               => $conditions,
                "has_customer_integration" => Customer::hasCustomerIntegration(Issue::getProjectID($issue_id))
            ));
            $text_message = $tpl->getTemplateContents();
            foreach ($to as $address) {
                // send email (use PEAR's classes)
                $mail = new Mail_API;
                $mail->setTextBody($text_message);
                $setup = $mail->getSMTPSettings();
                $mail->send($setup["from"], $address, "[#$issue_id] Reminder: " . $action['rma_title'], 0, $issue_id, 'reminder');
            }
        }
        // - eventum saves the day once again
        return true;
    }


    /**
     * Method used to send an alert to a set of email addresses when
     * a reminder action was triggered, but no action was really
     * taken because no recipients could be found.
     *
     * @access  private
     * @param   integer $issue_id The issue ID
     * @param   string $type Which reminder are we trying to send, email or sms
     * @param   array $reminder The reminder details
     * @param   array $action The action details
     * @return  void
     */
    function _recordNoRecipientError($issue_id, $type, $reminder, $action)
    {
        $to = Reminder::_getReminderAlertAddresses();
        if (count($to) > 0) {
            $tpl = new Template_API;
            $tpl->setTemplate('reminders/alert_no_recipients.tpl.text');
            $tpl->bulkAssign(array(
                "type"                     => $type,
                "data"                     => $data,
                "reminder"                 => $reminder,
                "action"                   => $action,
                "conditions"               => $conditions,
                "has_customer_integration" => Customer::hasCustomerIntegration(Issue::getProjectID($issue_id))
            ));
            $text_message = $tpl->getTemplateContents();
            foreach ($to as $address) {
                // send email (use PEAR's classes)
                $mail = new Mail_API;
                $mail->setTextBody($text_message);
                $setup = $mail->getSMTPSettings();
                $mail->send($setup["from"], $address, "[#$issue_id] Reminder Not Triggered: " . $action['rma_title'], 0, $issue_id);
            }
        }
    }


    /**
     * Returns the given list of issues with only the issues that 
     * were last triggered for the given reminder action ID.
     *
     * @access  public
     * @param   array $issues The list of issue IDs
     * @param   integer $rma_id The reminder action ID
     * @return  array The list of issue IDs
     */
    function getRepeatActions($issues, $rma_id)
    {
        if (count($issues) == 0) {
            return $issues;
        }

        $stmt = "SELECT
                    rta_iss_id,
                    rta_rma_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_triggered_action
                 WHERE
                    rta_iss_id IN (" . implode(', ', Misc::escapeInteger($issues)) . ")";
        $triggered_actions = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($triggered_actions)) {
            Error_Handler::logError(array($triggered_actions->getMessage(), $triggered_actions->getDebugInfo()), __FILE__, __LINE__);
            return $issues;
        } else {
            $repeat_issues = array();
            foreach ($issues as $issue_id) {
                // if the issue was already triggered and the last triggered 
                // action was the given one, then add it to the list of repeat issues
                if ((in_array($issue_id, array_keys($triggered_actions))) && ($triggered_actions[$issue_id] == $rma_id)) {
                    $repeat_issues[] = $issue_id;
                }
            }
            return $repeat_issues;
        }
    }


    /**
     * Records the last triggered reminder action for a given 
     * issue ID.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   integer $rma_id The reminder action ID
     * @return  boolean
     */
    function recordLastTriggered($issue_id, $rma_id)
    {
        $issue_id = Misc::escapeInteger($issue_id);
        $rma_id = Misc::escapeInteger($rma_id);
        $stmt = "SELECT
                    COUNT(*)
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_triggered_action
                 WHERE
                    rta_iss_id=$issue_id";
        $total = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if ($total == 1) {
            $stmt = "UPDATE
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_triggered_action
                     SET
                        rta_rma_id=$rma_id
                     WHERE
                        rta_iss_id=$issue_id";
        } else {
            $stmt = "INSERT INTO
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_triggered_action
                     (
                        rta_iss_id,
                        rta_rma_id
                     ) VALUES (
                        $issue_id,
                        $rma_id
                     )";
        }
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Clears the last triggered reminder for a given issue ID.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  boolean
     */
    function clearLastTriggered($issue_id)
    {
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_triggered_action
                 WHERE
                    rta_iss_id=" . Misc::escapeInteger($issue_id);
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Reminder_Action Class');
}
?>