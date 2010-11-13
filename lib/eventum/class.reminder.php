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
 * Class to handle the business logic related to the reminder emails
 * that the system sends out.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

class Reminder
{
    public static $debug = false;


    /**
     * Returns whether we are in "debug mode" or not. Returning true
     * here will enable all sorts of helpful messages in the reminder
     * check script.
     *
     * @access  public
     * @return  boolean
     */
    function isDebug()
    {
        return self::$debug;
    }


    /**
     * Method used to quickly change the ranking of a reminder entry
     * from the administration screen.
     *
     * @access  public
     * @param   integer $rem_id The reminder entry ID
     * @param   string $rank_type Whether we should change the reminder ID down or up (options are 'asc' or 'desc')
     * @return  boolean
     */
    function changeRank($rem_id, $rank_type)
    {
        // check if the current rank is not already the first or last one
        $ranking = self::_getRanking();
        $ranks = array_values($ranking);
        $ids = array_keys($ranking);
        $last = end($ids);
        $first = reset($ids);
        if ((($rank_type == 'asc') && ($rem_id == $first)) ||
                (($rank_type == 'desc') && ($rem_id == $last))) {
            return false;
        }

        if ($rank_type == 'asc') {
            $diff = -1;
        } else {
            $diff = 1;
        }
        $new_rank = $ranking[$rem_id] + $diff;
        if (in_array($new_rank, $ranks)) {
            // switch the rankings here...
            $index = array_search($new_rank, $ranks);
            $replaced_rem_id = $ids[$index];
            $stmt = "UPDATE
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_level
                     SET
                        rem_rank=" . Misc::escapeInteger($ranking[$rem_id]) . "
                     WHERE
                        rem_id=" . Misc::escapeInteger($replaced_rem_id);
            DB_Helper::getInstance()->query($stmt);
        }
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_level
                 SET
                    rem_rank=" . Misc::escapeInteger($new_rank) . "
                 WHERE
                    rem_id=" . Misc::escapeInteger($rem_id);
        DB_Helper::getInstance()->query($stmt);
        return true;
    }


    /**
     * Returns an associative array with the list of reminder IDs and
     * their respective ranking.
     *
     * @access  private
     * @return  array The list of reminders
     */
    function _getRanking()
    {
        $stmt = "SELECT
                    rem_id,
                    rem_rank
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_level
                 ORDER BY
                    rem_rank ASC";
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            return $res;
        }
    }


    /**
     * Method used by the administration screen to list the available
     * issues in a project.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  array The list of issues
     */
    function getIssueAssocListByProject($prj_id)
    {
        $issues = Issue::getAssocListByProject($prj_id);
        foreach ($issues as $iss_id => $iss_summary) {
            $issues[$iss_id] = $iss_id . ': ' . $iss_summary;
        }
        return $issues;
    }


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
                    rem_id=" . Misc::escapeInteger($rem_id);
        $res = DB_Helper::getInstance()->getOne($stmt);
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
                    rem_id=" . Misc::escapeInteger($rem_id);
        $res = DB_Helper::getInstance()->getOne($stmt);
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
                    rem_id=" . Misc::escapeInteger($rem_id);
        $res = DB_Helper::getInstance()->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return '';
        } else {
            $requirements = self::getRequirements($rem_id);
            if (!empty($requirements)) {
                $res['type'] = $requirements['type'];
                if ($res['type'] == 'support_level') {
                    $res['rer_support_level_id'] = $requirements['values'];
                } elseif ($res['type'] == 'customer') {
                    $res['rer_customer_id'] = $requirements['values'];
                } elseif ($res['type'] == 'issue') {
                    $res['rer_iss_id'] = array_values($requirements['values']);
                }
            }
            $priorities = self::getAssociatedPriorities($rem_id);
            if (count($priorities) > 0) {
                $res['check_priority'] = 'yes';
                $res['rer_pri_id'] = $priorities;
            }
            $severities = self::getAssociatedSeverities($rem_id);
            if (count($severities) > 0) {
                $res['check_severity'] = 'yes';
                $res['rer_sev_id'] = $severities;
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
                    rep_rem_id=" . Misc::escapeInteger($rem_id);
        $res = DB_Helper::getInstance()->getCol($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            return $res;
        }
    }


    /**
     * Method used to get a list of all severity IDs associated with the given
     * reminder.
     *
     * @access  public
     * @param   integer $rem_id The reminder ID
     * @return  array The list of associated severity IDs
     */
    function getAssociatedSeverities($rem_id)
    {
        $stmt = "SELECT
                    rms_sev_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_severity
                 WHERE
                    rms_rem_id=" . Misc::escapeInteger($rem_id);
        $res = DB_Helper::getInstance()->getCol($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            return $res;
        }
    }


    /**
     * Method used to associate a support level ID with a given
     * reminder entry ID.
     *
     * @access  public
     * @param   integer $rem_id The reminder ID
     * @param   integer $support_level_id The support level ID
     * @return  boolean
     */
    function addSupportLevelAssociation($rem_id, $support_level_id)
    {
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_requirement
                 (
                    rer_rem_id,
                    rer_support_level_id
                 ) VALUES (
                    " . Misc::escapeInteger($rem_id) . ",
                    " . Misc::escapeInteger($support_level_id) . "
                 )";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
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
                    " . Misc::escapeInteger($rem_id) . ",
                    " . Misc::escapeInteger($issue_id) . "
                 )";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method used to associate a customer ID with a given reminder
     * entry ID.
     *
     * @access  public
     * @param   integer $rem_id The reminder ID
     * @param   integer $customer_id The customer ID
     * @return  boolean
     */
    function addCustomerAssociation($rem_id, $customer_id)
    {
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_requirement
                 (
                    rer_rem_id,
                    rer_customer_id
                 ) VALUES (
                    " . Misc::escapeInteger($rem_id) . ",
                    " . Misc::escapeInteger($customer_id) . "
                 )";
        $res = DB_Helper::getInstance()->query($stmt);
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
                    " . Misc::escapeInteger($rem_id) . ",
                    1
                 )";
        $res = DB_Helper::getInstance()->query($stmt);
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
                    " . Misc::escapeInteger($rem_id) . ",
                    " . Misc::escapeInteger($priority_id) . "
                 )";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method used to associate a severity with a given reminder.
     *
     * @access  public
     * @param   integer $rem_id The reminder ID
     * @param   integer $priority_id The severity ID
     * @return  boolean
     */
    function addSeverityAssociation($rem_id, $severity_id)
    {
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_severity
                 (
                    rms_rem_id,
                    rms_sev_id
                 ) VALUES (
                    " . Misc::escapeInteger($rem_id) . ",
                    " . Misc::escapeInteger($severity_id) . "
                 )";
        $res = DB_Helper::getInstance()->query($stmt);
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
        $rem_id = Misc::escapeInteger($rem_id);
        if (!is_array($rem_id)) {
            $rem_id = array($rem_id);
        }
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_requirement
                 WHERE
                    rer_rem_id IN (" . implode(',', $rem_id) . ")";
        DB_Helper::getInstance()->query($stmt);
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_priority
                 WHERE
                    rep_rem_id IN (" . implode(',', $rem_id) . ")";
        DB_Helper::getInstance()->query($stmt);
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_severity
                 WHERE
                    rms_rem_id IN (" . implode(',', $rem_id) . ")";
        DB_Helper::getInstance()->query($stmt);
    }


    /**
     * Method used to create a new reminder.
     *
     * @access  public
     * @return  integer 1 if the insert worked, -1 or -2 otherwise
     */
    function insert()
    {
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_level
                 (
                    rem_created_date,
                    rem_rank,
                    rem_title,
                    rem_prj_id,
                    rem_skip_weekend
                 ) VALUES (
                    '" . Date_Helper::getCurrentDateGMT() . "',
                    " . Misc::escapeInteger($_POST['rank']) . ",
                    '" . Misc::escapeString($_POST['title']) . "',
                    " . Misc::escapeInteger($_POST['project']) . ",
                    " . Misc::escapeInteger($_POST['skip_weekend']) . "
                 )";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $new_rem_id = DB_Helper::get_last_insert_id();
            // map the reminder requirements now
            if ((@$_POST['reminder_type'] == 'support_level') && (count($_POST['support_levels']) > 0)) {
                for ($i = 0; $i < count($_POST['support_levels']); $i++) {
                    self::addSupportLevelAssociation($new_rem_id, $_POST['support_levels'][$i]);
                }
            } elseif ((@$_POST['reminder_type'] == 'issue') && (count($_POST['issues']) > 0)) {
                for ($i = 0; $i < count($_POST['issues']); $i++) {
                    self::addIssueAssociation($new_rem_id, $_POST['issues'][$i]);
                }
            } elseif ((@$_POST['reminder_type'] == 'customer') && (count($_POST['customers']) > 0)) {
                for ($i = 0; $i < count($_POST['customers']); $i++) {
                    self::addCustomerAssociation($new_rem_id, $_POST['customers'][$i]);
                }
            } elseif (@$_POST['reminder_type'] == 'all_issues') {
                 self::associateAllIssues($new_rem_id);
            }
            if ((@$_POST['check_priority'] == 'yes') && (count($_POST['priorities']) > 0)) {
                for ($i = 0; $i < count($_POST['priorities']); $i++) {
                    self::addPriorityAssociation($new_rem_id, $_POST['priorities'][$i]);
                }
            }
            if ((@$_POST['check_severity'] == 'yes') && (count($_POST['severities']) > 0)) {
                for ($i = 0; $i < count($_POST['severities']); $i++) {
                    self::addSeverityAssociation($new_rem_id, $_POST['severities'][$i]);
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
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_level
                 SET
                    rem_last_updated_date='" . Date_Helper::getCurrentDateGMT() . "',
                    rem_rank=" . Misc::escapeInteger($_POST['rank']) . ",
                    rem_title='" . Misc::escapeString($_POST['title']) . "',
                    rem_prj_id=" . Misc::escapeInteger($_POST['project']) . ",
                    rem_skip_weekend=" . Misc::escapeInteger($_POST['skip_weekend']) . "
                 WHERE
                    rem_id=" . Misc::escapeInteger($_POST['id']);
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            self::removeAllAssociations($_POST['id']);
            // map the reminder requirements now
            if ((@$_POST['reminder_type'] == 'support_level') && (count($_POST['support_levels']) > 0)) {
                for ($i = 0; $i < count($_POST['support_levels']); $i++) {
                    self::addSupportLevelAssociation($_POST['id'], $_POST['support_levels'][$i]);
                }
            } elseif ((@$_POST['reminder_type'] == 'issue') && (count($_POST['issues']) > 0)) {
                for ($i = 0; $i < count($_POST['issues']); $i++) {
                    self::addIssueAssociation($_POST['id'], $_POST['issues'][$i]);
                }
            } elseif ((@$_POST['reminder_type'] == 'customer') && (count($_POST['customers']) > 0)) {
                for ($i = 0; $i < count($_POST['customers']); $i++) {
                    self::addCustomerAssociation($_POST['id'], $_POST['customers'][$i]);
                }
            } elseif (@$_POST['reminder_type'] == 'all_issues') {
                 self::associateAllIssues($_POST['id']);
            }
            if ((@$_POST['check_priority'] == 'yes') && (count($_POST['priorities']) > 0)) {
                for ($i = 0; $i < count($_POST['priorities']); $i++) {
                    self::addPriorityAssociation($_POST['id'], $_POST['priorities'][$i]);
                }
            }
            if ((@$_POST['check_severity'] == 'yes') && (count($_POST['severities']) > 0)) {
                for ($i = 0; $i < count($_POST['severities']); $i++) {
                    self::addSeverityAssociation($_POST['id'], $_POST['severities'][$i]);
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
        $items = @implode(", ", Misc::escapeInteger($_POST["items"]));
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_level
                 WHERE
                    rem_id IN ($items)";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            self::removeAllAssociations($_POST["items"]);
            $stmt = "SELECT
                        rma_id
                     FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_action
                     WHERE
                        rma_rem_id IN ($items)";
            $actions = DB_Helper::getInstance()->getCol($stmt);
            if (count($actions) > 0) {
                Reminder_Action::remove($actions);
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
                    rer_customer_id,
                    rer_iss_id,
                    rer_support_level_id,
                    rer_trigger_all_issues
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_requirement
                 WHERE
                    rer_rem_id=" . Misc::escapeInteger($rem_id);
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return '';
        } else {
            $type = '';
            $values = array();
            for ($i = 0; $i < count($res); $i++) {
                if ($res[$i]['rer_trigger_all_issues'] == '1') {
                    return array('type' => 'ALL');
                } elseif (!empty($res[$i]['rer_support_level_id'])) {
                    $type = 'support_level';
                    $values[] = $res[$i]['rer_support_level_id'];
                } elseif (!empty($res[$i]['rer_customer_id'])) {
                    $type = 'customer';
                    $values[] = $res[$i]['rer_customer_id'];
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
                    rem_rank ASC";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]['rem_created_date'] = Date_Helper::getFormattedDate($res[$i]["rem_created_date"]);
                $actions = Reminder_Action::getList($res[$i]['rem_id']);
                $res[$i]['total_actions'] = count($actions);
                $priorities = self::getAssociatedPriorities($res[$i]['rem_id']);
                $priority_titles = Priority::getAssocList($res[$i]['rem_prj_id']);
                $res[$i]['priorities'] = array();
                if (count($priorities) > 0) {
                    foreach ($priorities as $pri_id) {
                        $res[$i]['priorities'][] = $priority_titles[$pri_id];
                    }
                } else {
                    $res[$i]['priorities'][] = 'Any';
                }
                $requirements = self::getRequirements($res[$i]['rem_id']);
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
                    rem_rank ASC";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
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
        $stmt .= self::getWhereClause($reminder, $conditions);
        $stmt .= ' AND iss_trigger_reminders=1 ';
        // can't rely on the mysql server's timezone setting, so let's use gmt dates throughout
        $stmt = str_replace('UNIX_TIMESTAMP()', "UNIX_TIMESTAMP('" . Date_Helper::getCurrentDateGMT() . "')", $stmt);
        $res = DB_Helper::getInstance()->getCol($stmt);
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
     * @param   array $reminder An array of reminder info.
     * @param   array $conditions The list of conditions
     * @return  string The where clause
     */
    function getWhereClause($reminder, $conditions)
    {
        $stmt = '
                  WHERE
                    iss_prj_id=' . $reminder['rem_prj_id'] . "\n";
        $requirement = self::getRequirements($reminder['rem_id']);
        if ($requirement['type'] == 'issue') {
            $stmt .= ' AND iss_id IN (' . implode(', ', $requirement['values']) . ")\n";
        } else {
            if (Customer::hasCustomerIntegration($reminder['rem_prj_id'])) {
                if ($requirement['type'] == 'customer') {
                    $stmt .= ' AND iss_customer_id IN (' . implode(', ', $requirement['values']) . ")\n";
                } elseif ($requirement['type'] == 'support_level') {
                    if (Customer::doesBackendUseSupportLevels($reminder['rem_prj_id'])) {
                        $customer_ids = Customer::getListBySupportLevel($reminder['rem_prj_id'], $requirement['values'], CUSTOMER_EXCLUDE_EXPIRED);
                        // break the query on purpose if no customers could be found
                        if (count($customer_ids) == 0) {
                            $customer_ids = array(-1);
                        }
                        $stmt .= ' AND iss_customer_id IN (' . implode(', ', $customer_ids) . ")\n";
                    }
                }
            }
        }
        $priorities = self::getAssociatedPriorities($reminder['rem_id']);
        if (count($priorities) > 0) {
            $stmt .= ' AND iss_pri_id IN (' . implode(', ', $priorities) . ")\n";
        }
        $severities = self::getAssociatedSeverities($reminder['rem_id']);
        if (count($severities) > 0) {
            $stmt .= ' AND iss_sev_id IN (' . implode(', ', $severities) . ")\n";
        }
        // now for the interesting stuff
        for ($i = 0; $i < count($conditions); $i++) {
            // check for fields that compare to other fields
            if (!empty($conditions[$i]['rlc_comparison_rmf_id'])) {
                $sql_field = Reminder_Condition::getSQLField($conditions[$i]['rlc_comparison_rmf_id']);
                $stmt .= sprintf(" AND %s %s %s\n", $conditions[$i]['rmf_sql_field'],
                                              $conditions[$i]['rmo_sql_representation'],
                                              $sql_field);
            } else {
                // date field values are always saved as number of hours, so let's calculate them now as seconds
                if (stristr($conditions[$i]['rmf_title'], 'date')) {
                    // support NULL as values for a date field
                    if (strtoupper($conditions[$i]['rlc_value']) == 'NULL') {
                        $conditions[$i]['rmf_sql_representation'] = $conditions[$i]['rmf_sql_field'];
                    } else {
                        $conditions[$i]['rlc_value'] = $conditions[$i]['rlc_value'] * 60 * 60;
                        if (@$reminder["rem_skip_weekend"] == 1) {
                            $sql_field = Reminder_Condition::getSQLField($conditions[$i]['rlc_rmf_id']);
                            $conditions[$i]['rmf_sql_representation'] = DB_Helper::getNoWeekendDateDiffSQL($sql_field);
                        }
                    }
                }

                $stmt .= sprintf(" AND %s %s %s\n", $conditions[$i]['rmf_sql_representation'],
                                                  $conditions[$i]['rmo_sql_representation'],
                                                  $conditions[$i]['rlc_value']);
            }
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
        $reminder = self::getDetails($rem_id);
        $conditions = Reminder_Condition::getList($rma_id);
        $stmt = "SELECT
                    iss_id
                 FROM
                    " . APP_TABLE_PREFIX . "issue";
        $stmt .= self::getWhereClause($reminder, $conditions);
        // can't rely on the mysql server's timezone setting, so let's use gmt dates throughout
        $stmt = str_replace('UNIX_TIMESTAMP()', "UNIX_TIMESTAMP('" . Date_Helper::getCurrentDateGMT() . "')", $stmt);
        return $stmt;
    }


    /**
     * Method used to list the history of triggered reminder actions
     * for a given issue.
     *
     * @access  public
     * @param   integer $iss_id The issue ID
     * @return  array The list of triggered reminder actions
     */
    function getHistoryList($iss_id)
    {
        $stmt = "SELECT
                    rmh_created_date,
                    rma_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_history,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "reminder_action
                 WHERE
                    rmh_iss_id=" . Misc::escapeInteger($iss_id) . " AND
                    rmh_rma_id=rma_id
                 ORDER BY
                    rmh_created_date DESC";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]["rmh_created_date"] = Date_Helper::getFormattedDate($res[$i]["rmh_created_date"]);
            }
            return $res;
        }
    }


    /**
     * Method used to get the list of email addresses to use
     * to send diagnostic information about the reminder system.
     *
     * @access  private
     * @return  array The list of alert email addresses
     */
    function _getReminderAlertAddresses()
    {
        $emails = array();
        $setup = Setup::load();
        if ((@$setup['email_reminder']['status'] == 'enabled') &&
                (!empty($setup['email_reminder']['addresses']))) {
            $addresses = $setup['email_reminder']['addresses'];
            $emails = explode(',', $addresses);
        }
        $emails = array_map('trim', $emails);
        return $emails;
    }
}
