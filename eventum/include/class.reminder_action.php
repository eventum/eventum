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
// @(#) $Id: s.class.reminder_action.php 1.2 04/01/19 15:15:25-00:00 jpradomaia $
//


/**
 * Class to handle the business logic related to the reminder emails
 * that the system sends out.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

include_once(APP_INC_PATH . "class.error_handler.php");
include_once(APP_INC_PATH . "class.reminder_condition.php");
include_once(APP_INC_PATH . "class.notification.php");
include_once(APP_INC_PATH . "class.user.php");
include_once(APP_INC_PATH . "class.mail.php");
include_once(APP_INC_PATH . "class.issue.php");

class Reminder_Action
{
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
                    rma_id=$rma_id";
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
                    rma_id=$rma_id";
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return '';
        } else {
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
                    rma_rank
                 ) VALUES (
                    " . $HTTP_POST_VARS['rem_id'] . ",
                    " . $HTTP_POST_VARS['type'] . ",
                    '" . Date_API::getCurrentDateGMT() . "',
                    '" . Misc::runSlashes($HTTP_POST_VARS['title']) . "',
                    '" . $HTTP_POST_VARS['rank'] . "'
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
                    rma_rank='" . $HTTP_POST_VARS['rank'] . "',
                    rma_title='" . Misc::runSlashes($HTTP_POST_VARS['title']) . "',
                    rma_rmt_id=" . $HTTP_POST_VARS['type'] . "
                 WHERE
                    rma_id=" . $HTTP_POST_VARS['id'];
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            return 1;
        }
    }


    /**
     * Method used to remove reminder actions by using the administrative
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
                    rma_rank
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_action,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_action_type
                 WHERE
                    rma_rmt_id=rmt_id AND
                    rma_rem_id=$rem_id
                 ORDER BY
                    rma_rank ASC";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]['total_conditions'] = count(Reminder_Condition::getList($res[$i]['rma_id']));
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
                    rma_rem_id=$reminder_id
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
                    rmt_id=$rmt_id";
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
                    $issue_id,
                    $rma_id,
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
        echo "- Performing action '$action_type' for issue #$issue_id\n";
        switch ($action_type) {
            case 'email_assignee':
                $type = 'email';
                $assignees = Issue::getAssignedUserIDs($issue_id);
                $to = array();
                foreach ($assignees as $assignee) {
                    $to[] = User::getFromHeader($assignee);
                }
                // if there are no recipients, then just skip to the next action
                if (count($to) == 0) {
                    echo "- No assigned users could be found\n";
                    return false;
                }
                break;
            case 'sms_assignee':
                $type = 'sms';
                $assignees = Issue::getAssignedUserIDs($issue_id);
                $to = array();
                foreach ($assignees as $assignee) {
                    $sms_email = User::getSMS($assignee);
                    if (!empty($sms_email)) {
                        $to[] = $sms_email;
                    }
                }
                // if there are no recipients, then just skip to the next action
                if (count($to) == 0) {
                    echo "- No assigned users with SMS email address could be found\n";
                    return false;
                }
                break;
        }
        // - save a history entry about this action
        Reminder_Action::saveHistory($issue_id, $action['rma_id']);

        $conditions = Reminder_Condition::getAdminList($action['rma_id']);
        // - perform the action
        if ($type == 'email') {
            $tpl = new Template_API;
            $tpl->setTemplate('reminders/email_alert.tpl.text');
            $tpl->bulkAssign(array(
                "app_base_url" => APP_BASE_URL,
                "data"         => Notification::getIssueDetails($issue_id),
                "conditions"   => $conditions
            ));
            $text_message = $tpl->getTemplateContents();

            foreach ($to as $address) {
                // send email (use PEAR's classes)
                $mail = new Mail_API;
                $mail->setTextBody($text_message);
                $setup = $mail->getSMTPSettings();
                $mail->send($setup["from"], $address, APP_SHORT_NAME . ": Reminder Alert for Issue #$issue_id", TRUE);
            }
        } elseif ($type == 'sms') {
            $tpl = new Template_API;
            $tpl->setTemplate('reminders/sms_alert.tpl.text');
            $tpl->bulkAssign(array(
                "app_base_url" => APP_BASE_URL,
                "data"         => Notification::getIssueDetails($issue_id),
                "conditions"   => $conditions
            ));
            $text_message = $tpl->getTemplateContents();

            foreach ($to as $address) {
                // send email (use PEAR's classes)
                $mail = new Mail_API;
                $mail->setTextBody($text_message);
                $setup = $mail->getSMTPSettings();
                $mail->send($setup["from"], $address, "Reminder Alert for Issue #$issue_id", TRUE);
            }
        }
        // - eventum saves the day once again
        return true;
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Reminder_Action Class');
}
?>