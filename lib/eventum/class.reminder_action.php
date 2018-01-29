<?php

/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

use Eventum\Db\DatabaseException;
use MariaDB\Eventum\SMSAlert;

/**
 * Class to handle the business logic related to the reminder emails
 * that the system sends out.
 */
class Reminder_Action
{
    /**
     * Method used to quickly change the ranking of a reminder action
     * from the administration screen.
     *
     * @param   int $rem_id The reminder ID
     * @param   int $rma_id The reminder action ID
     * @param   string $rank_type Whether we should change the entry down or up (options are 'asc' or 'desc')
     * @return  bool
     */
    public static function changeRank($rem_id, $rma_id, $rank_type)
    {
        // check if the current rank is not already the first or last one
        $ranking = self::_getRanking($rem_id);
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
            $stmt = 'UPDATE
                        `reminder_action`
                     SET
                        rma_rank=?
                     WHERE
                        rma_id=?';
            DB_Helper::getInstance()->query($stmt, [$ranking[$rma_id], $replaced_rma_id]);
        }
        $stmt = 'UPDATE
                    `reminder_action`
                 SET
                    rma_rank=?
                 WHERE
                    rma_id=?';
        DB_Helper::getInstance()->query($stmt, [$new_rank, $rma_id]);

        return true;
    }

    /**
     * Returns an associative array with the list of reminder action
     * IDs and their respective ranking.
     *
     * @param   int $rem_id The reminder ID
     * @return  array The list of reminder actions
     */
    private function _getRanking($rem_id)
    {
        $stmt = 'SELECT
                    rma_id,
                    rma_rank
                 FROM
                    `reminder_action`
                 WHERE
                    rma_rem_id = ?
                 ORDER BY
                    rma_rank ASC';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, [$rem_id]);
        } catch (DatabaseException $e) {
            return [];
        }

        return $res;
    }

    /**
     * Method used to get the title of a specific reminder action.
     *
     * @param   int $rma_id The reminder action ID
     * @return  string The title of the reminder action
     */
    public static function getTitle($rma_id)
    {
        $stmt = 'SELECT
                    rma_title
                 FROM
                    `reminder_action`
                 WHERE
                    rma_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$rma_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the details for a specific reminder action.
     *
     * @param   int $rma_id The reminder action ID
     * @return  array The details for the specified reminder action
     */
    public static function getDetails($rma_id)
    {
        $stmt = 'SELECT
                    *
                 FROM
                    `reminder_action`
                 WHERE
                    rma_id=?';
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, [$rma_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        // get the user list, if appropriate
        if (self::isUserList($res['rma_rmt_id'])) {
            $res['user_list'] = self::getUserList($res['rma_id']);
        }

        return $res;
    }

    /**
     * Method used to create a new reminder action.
     *
     * @return  int 1 if the insert worked, -1 or -2 otherwise
     */
    public static function insert()
    {
        $stmt = 'INSERT INTO
                    `reminder_action`
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
                    ?, ?, ?, ?, ?,
                    ?, ?, ?
                 )';
        $params = [
            $_POST['rem_id'],
            $_POST['type'],
            Date_Helper::getCurrentDateGMT(),
            $_POST['title'],
            $_POST['rank'],
            $_POST['alert_irc'],
            $_POST['alert_group_leader'],
            $_POST['boilerplate'],
        ];
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return -1;
        }

        $new_rma_id = DB_Helper::get_last_insert_id();
        // add the user list, if appropriate
        if (self::isUserList($_POST['type'])) {
            self::associateUserList($new_rma_id, $_POST['user_list']);
        }

        return 1;
    }

    /**
     * Returns the list of users associated with a given reminder
     * action ID
     *
     * @param   int $rma_id The reminder action ID
     * @return  array The list of associated users
     */
    public static function getUserList($rma_id)
    {
        $stmt = 'SELECT
                    ral_usr_id,
                    ral_email
                 FROM
                    `reminder_action_list`
                 WHERE
                    ral_rma_id=?';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, [$rma_id]);
        } catch (DatabaseException $e) {
            return [];
        }

        $t = [];
        foreach ($res as $row) {
            if (Validation::isEmail($row['ral_email'])) {
                $t[$row['ral_email']] = $row['ral_email'];
            } else {
                $t[$row['ral_usr_id']] = User::getFullName($row['ral_usr_id']);
            }
        }

        return $t;
    }

    /**
     * Method used to associate a list of users with a given reminder
     * action ID
     *
     * @param   int $rma_id The reminder action ID
     * @param   array $user_list The list of users
     */
    public static function associateUserList($rma_id, $user_list)
    {
        foreach ($user_list as $user) {
            if (!Validation::isEmail($user)) {
                $usr_id = $user;
                $email = '';
            } else {
                $usr_id = 0;
                $email = $user;
            }
            $stmt = 'INSERT INTO
                        `reminder_action_list`
                     (
                        ral_rma_id,
                        ral_usr_id,
                        ral_email
                     ) VALUES (
                        ?, ?, ?
                     )';
            DB_Helper::getInstance()->query($stmt, [$rma_id, $usr_id, $email]);
        }
    }

    /**
     * Method used to update the details of a specific reminder action.
     *
     * @return  int 1 if the update worked, -1 or -2 otherwise
     */
    public static function update()
    {
        $stmt = 'UPDATE
                    `reminder_action`
                 SET
                    rma_last_updated_date=?,
                    rma_rank=?,
                    rma_title=?,
                    rma_rmt_id=?,
                    rma_alert_irc=?,
                    rma_alert_group_leader=?,
                    rma_boilerplate=?
                 WHERE
                    rma_id=?';
        $params = [
            Date_Helper::getCurrentDateGMT(),
            $_POST['rank'],
            $_POST['title'],
            $_POST['type'],
            $_POST['alert_irc'],
            $_POST['alert_group_leader'],
            $_POST['boilerplate'],
            $_POST['id'],
        ];
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return -1;
        }

        // remove any user list associated with this reminder action
        self::clearActionUserList($_POST['id']);
        // add the user list back in, if appropriate
        if (self::isUserList($_POST['type'])) {
            self::associateUserList($_POST['id'], $_POST['user_list']);
        }

        return 1;
    }

    /**
     * Checks whether the given reminder action type is one where a
     * list of users is used or not.
     *
     * @param   int $rmt_id The reminder action type ID
     * @return  bool
     */
    public static function isUserList($rmt_id)
    {
        $stmt = 'SELECT
                    rmt_type
                 FROM
                    `reminder_action_type`
                 WHERE
                    rmt_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$rmt_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        $user_list_types = [
            'sms_list',
            'email_list',
        ];

        if (!in_array($res, $user_list_types)) {
            return false;
        }

        return true;
    }

    /**
     * Removes the full user list for a given reminder action ID.
     *
     * @param   int $rma_id The reminder action ID
     */
    public static function clearActionUserList($rma_id)
    {
        if (!is_array($rma_id)) {
            $rma_id = [$rma_id];
        }

        $items = DB_Helper::buildList($rma_id);
        $stmt = "DELETE FROM
                    `reminder_action_list`
                 WHERE
                    ral_rma_id IN ($items)";
        DB_Helper::getInstance()->query($stmt, $rma_id);
    }

    /**
     * Method used to remove reminder actions by using the administrative
     * interface of the system.
     */
    public static function remove($action_ids)
    {
        $items = DB_Helper::buildList($action_ids);

        $stmt = "DELETE FROM
                    `reminder_action`
                 WHERE
                    rma_id IN ($items)";
        DB_Helper::getInstance()->query($stmt, $action_ids);

        $stmt = "DELETE FROM
                    `reminder_history`
                 WHERE
                    rmh_rma_id IN ($items)";
        DB_Helper::getInstance()->query($stmt, $action_ids);

        $stmt = "DELETE FROM
                    `reminder_level_condition`
                 WHERE
                    rlc_rma_id IN ($items)";
        DB_Helper::getInstance()->query($stmt, $action_ids);

        self::clearActionUserList($action_ids);
    }

    /**
     * Method used to get an associative array of action types.
     *
     * @return  array The list of action types
     */
    public static function getActionTypeList()
    {
        $stmt = 'SELECT
                    rmt_id,
                    rmt_title
                 FROM
                    `reminder_action_type`
                 ORDER BY
                    rmt_title ASC';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt);
        } catch (DatabaseException $e) {
            return [];
        }

        return $res;
    }

    /**
     * Method used to get the list of reminder actions to be displayed in the
     * administration section.
     *
     * @param   int $rem_id The reminder ID
     * @return  array The list of reminder actions
     */
    public static function getAdminList($rem_id)
    {
        $stmt = 'SELECT
                    rma_rem_id,
                    rma_id,
                    rma_title,
                    rmt_title,
                    rma_rank,
                    rma_alert_irc,
                    rma_alert_group_leader
                 FROM
                    `reminder_action`,
                    `reminder_action_type`
                 WHERE
                    rma_rmt_id=rmt_id AND
                    rma_rem_id=?
                 ORDER BY
                    rma_rank ASC';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, [$rem_id]);
        } catch (DatabaseException $e) {
            return [];
        }

        foreach ($res as &$row) {
            $conditions = Reminder_Condition::getList($row['rma_id']);
            $row['total_conditions'] = count($conditions);
            foreach ($conditions as $condition) {
                if ($condition['rmf_sql_field'] == 'iss_sta_id') {
                    $row['status'] = Status::getStatusTitle($condition['rlc_value']);
                }
            }
        }

        return $res;
    }

    /**
     * Method used to get the list of reminder actions associated with a given
     * reminder ID.
     *
     * @param   int $reminder_id The reminder ID
     * @return  array The list of reminder actions
     */
    public static function getList($reminder_id)
    {
        $stmt = 'SELECT
                    *
                 FROM
                    `reminder_action`
                 WHERE
                    rma_rem_id=?
                 ORDER BY
                    rma_rank ASC';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, [$reminder_id]);
        } catch (DatabaseException $e) {
            return [];
        }

        if (empty($res)) {
            return [];
        }

        return $res;
    }

    /**
     * Method used to get the title of a reminder action type.
     *
     * @param   int $rmt_id The reminder action type
     * @return  string The action type title
     */
    public static function getActionType($rmt_id)
    {
        $stmt = 'SELECT
                    rmt_type
                 FROM
                    `reminder_action_type`
                 WHERE
                    rmt_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$rmt_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to save a history entry about the execution of the current
     * reminder.
     *
     * @param   int $issue_id The issue ID
     * @param   int $rma_id The reminder action ID
     * @return  bool
     */
    public static function saveHistory($issue_id, $rma_id)
    {
        $stmt = 'INSERT INTO
                    `reminder_history`
                 (
                    rmh_iss_id,
                    rmh_rma_id,
                    rmh_created_date
                 ) VALUES (
                    ?, ?, ?
                 )';
        try {
            DB_Helper::getInstance()->query($stmt, [$issue_id, $rma_id, Date_Helper::getCurrentDateGMT()]);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to perform a specific action to an issue.
     *
     * @param   int $issue_id The issue ID
     * @param   array $reminder The reminder details
     * @param   array $action The action details
     * @return  bool
     */
    public static function perform($issue_id, $reminder, $action)
    {
        $type = '';
        // - see which action type we're talking about here...
        $action_type = self::getActionType($action['rma_rmt_id']);
        // - do we also need to alert the group leader about this?
        $group_leader_usr_id = 0;
        if ($action['rma_alert_group_leader']) {
            if (Reminder::isDebug()) {
                echo '  - ' . ev_gettext('Processing Group Leader notification') . "\n";
            }
            $group_id = Issue::getGroupID($issue_id);
            // check if there's even a group associated with this issue
            if (empty($group_id)) {
                if (Reminder::isDebug()) {
                    echo '  - ' . ev_gettext('No group associated with issue %1$s', $issue_id) . "\n";
                }
            } else {
                $group_details = Group::getDetails($group_id);
                if (!empty($group_details['grp_manager_usr_id'])) {
                    $group_leader_usr_id = $group_details['grp_manager_usr_id'];
                }
            }
        }
        if (Reminder::isDebug()) {
            echo '  - ' . ev_gettext('Performing action %1$s for issue # %2$s', $action_type, $issue_id) . "\n";
        }
        $sms_users = [];
        switch ($action_type) {
            case 'email_assignee':
                $type = 'email';
                $assignees = Issue::getAssignedUserIDs($issue_id);
                $to = [];
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
                $list = self::getUserList($action['rma_id']);
                $to = [];
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
                $to = [];
                foreach ($assignees as $assignee) {
                    if (User::isClockedIn($assignee)) {
                        $sms_email = User::getSMS($assignee);
                        if (!empty($sms_email)) {
                            if (strpos($sms_email, '@') === false) {
                                $sms_users[] = $assignee;
                            } else {
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
            case 'sms_list':
                $type = 'sms';
                $list = self::getUserList($action['rma_id']);
                $to = [];
                foreach ($list as $key => $value) {
                    // add the recipient to the list if it's a simple email address
                    if (Validation::isEmail($key)) {
                        $to[] = $key;
                    } else {
                        // otherwise, check for the clocked-in status
                        if (User::isClockedIn($key)) {
                            $sms_email = User::getSMS($key);
                            if (!empty($sms_email)) {
                                if (strpos($sms_email, '@') === false) {
                                    $sms_users[] = $key;
                                } else {
                                    $to[] = $sms_email;
                                }
                            }
                        }
                    }
                }
                // add the group leader to the recipient list, if needed
                if ((!empty($group_leader_usr_id)) && (User::isClockedIn($group_leader_usr_id))) {
                    $leader_sms_email = User::getSMS($group_leader_usr_id);
                    if ((!empty($leader_sms_email)) && (!in_array($leader_sms_email, $to))) {
                        if (strpos($leader_sms_email, '@') === false) {
                            $sms_users[] = $group_leader_usr_id;
                        } else {
                            $to[] = $leader_sms_email;
                        }
                    }
                }
                break;
        }
        $data = Issue::getDetails($issue_id);
        $conditions = Reminder_Condition::getAdminList($action['rma_id']);
        // alert IRC if needed
        if ($action['rma_alert_irc']) {
            if (Reminder::isDebug()) {
                echo "  - Processing IRC notification\n";
            }
            $irc_notice = "Issue #$issue_id (";
            if (!empty($data['pri_title'])) {
                $irc_notice .= 'Priority: ' . $data['pri_title'];
            }
            if (!empty($data['sev_title'])) {
                $irc_notice .= 'Severity: ' . $data['sev_title'];
            }
            // also add information about the assignee, if any
            $assignment = Issue::getAssignedUsers($issue_id);
            if (count($assignment) > 0) {
                $irc_notice .= '; Assignment: ' . implode(', ', $assignment);
            }
            if (!empty($data['iss_grp_id'])) {
                $irc_notice .= '; Group: ' . Group::getName($data['iss_grp_id']);
            }
            $irc_notice .= "), Reminder action '" . $action['rma_title'] . "' was just triggered; " . $action['rma_boilerplate'];
            Notification::notifyIRC(Issue::getProjectID($issue_id), $irc_notice, $issue_id, false,
                APP_EVENTUM_IRC_CATEGORY_REMINDER);
        }
        $setup = Setup::get();
        // if there are no recipients, then just skip to the next action
        if (count($to) == 0 && count($sms_users) == 0) {
            if (Reminder::isDebug()) {
                echo "  - No recipients could be found\n";
            }
            // if not even an irc alert was sent, then save
            // a notice about this on reminder_sent@, if needed
            if (!$action['rma_alert_irc']) {
                if ($setup['email_reminder']['status'] == 'enabled') {
                    self::_recordNoRecipientError($issue_id, $type, $reminder, $action, $data, $conditions);
                }

                return false;
            }
        }

        // - save a history entry about this action
        self::saveHistory($issue_id, $action['rma_id']);
        // - save this action as the latest triggered one for the given issue ID
        self::recordLastTriggered($issue_id, $action['rma_id']);

        // - perform the action
        if (count($to) > 0) {
            // send a copy of this reminder to reminder_sent@, if needed
            if ($setup['email_reminder']['status'] == 'enabled' && $setup['email_reminder']['addresses']) {
                $addresses = Reminder::_getReminderAlertAddresses();
                if (count($addresses) > 0) {
                    $to = array_merge($to, $addresses);
                }
            }
            $tpl = new Template_Helper();
            $tpl->setTemplate('reminders/' . $type . '_alert.tpl.text');
            $tpl->assign([
                'data' => $data,
                'reminder' => $reminder,
                'action' => $action,
                'conditions' => $conditions,
                'has_customer_integration' => CRM::hasCustomerIntegration(Issue::getProjectID($issue_id)),
            ]);
            $text_message = $tpl->getTemplateContents();
            foreach ($to as $address) {
                // TRANSLATORS: %1 - issue_id, %2 - rma_title
                $subject = ev_gettext('[#%1$s] Reminder: %2$s', $issue_id, $action['rma_title']);

                $options = [
                    'type' => 'reminder',
                ];
                Notification::notifyByMail($text_message, null, $address, $subject, $issue_id, $options);
            }
        }

        // send sms alerts
        if (count($sms_users) > 0) {
            $message = $action['rma_title'] . ' #' . $issue_id . ':' . $data['sev_title'] . '[' .
                $data['customer']['name'] . "] " . $data['iss_summary'];
            $message = substr($message, 0, 159);
            foreach ($sms_users as $sms_user) {
                SMSAlert::notify($sms_user, $message);
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
     * @param   int $issue_id The issue ID
     * @param   string $type Which reminder are we trying to send, email or sms
     * @param   array $reminder The reminder details
     * @param   array $action The action details
     */
    private function _recordNoRecipientError($issue_id, $type, $reminder, $action, $data, $conditions)
    {
        $to = Reminder::_getReminderAlertAddresses();
        if (count($to) > 0) {
            $tpl = new Template_Helper();
            $tpl->setTemplate('reminders/alert_no_recipients.tpl.text');
            $tpl->assign([
                'type' => $type,
                'data' => $data,
                'reminder' => $reminder,
                'action' => $action,
                'conditions' => $conditions,
                'has_customer_integration' => CRM::hasCustomerIntegration(Issue::getProjectID($issue_id)),
            ]);
            $text_message = $tpl->getTemplateContents();
            foreach ($to as $address) {
                // TRANSLATORS: %1 = issue_id, %2 - rma_title
                $subject = ev_gettext('[#%1$s] Reminder Not Triggered: [#%2$s]', $issue_id, $action['rma_title']);
                Notification::notifyByMail($text_message, null, $address, $subject, $issue_id);
            }
        }
    }

    /**
     * Returns the given list of issues with only the issues that
     * were last triggered for the given reminder action ID.
     *
     * @param   array $issues The list of issue IDs
     * @param   int $rma_id The reminder action ID
     * @return  array The list of issue IDs
     */
    public static function getRepeatActions($issues, $rma_id)
    {
        if (count($issues) == 0) {
            return $issues;
        }

        $idlist = DB_Helper::buildList($issues);

        $stmt = "SELECT
                    rta_iss_id,
                    rta_rma_id
                 FROM
                    `reminder_triggered_action`
                 WHERE
                    rta_iss_id IN ($idlist)";
        try {
            $triggered_actions = DB_Helper::getInstance()->getPair($stmt, $issues);
        } catch (DatabaseException $e) {
            return $issues;
        }

        $repeat_issues = [];
        foreach ($issues as $issue_id) {
            // if the issue was already triggered and the last triggered
            // action was the given one, then add it to the list of repeat issues
            if (in_array($issue_id, array_keys($triggered_actions)) && $triggered_actions[$issue_id] == $rma_id) {
                $repeat_issues[] = $issue_id;
            }
        }

        return $repeat_issues;
    }

    /**
     * Records the last triggered reminder action for a given
     * issue ID.
     *
     * @param   int $issue_id The issue ID
     * @param   int $rma_id The reminder action ID
     * @return  bool
     */
    public static function recordLastTriggered($issue_id, $rma_id)
    {
        $stmt = 'SELECT
                    COUNT(*)
                 FROM
                    `reminder_triggered_action`
                 WHERE
                    rta_iss_id=?';

        $total = DB_Helper::getInstance()->getOne($stmt, [$issue_id]);
        if ($total == 1) {
            $stmt = 'UPDATE
                        `reminder_triggered_action`
                     SET
                        rta_rma_id=?
                     WHERE
                        rta_iss_id=?';
            $params = [$rma_id, $issue_id];
        } else {
            $stmt = 'INSERT INTO
                        `reminder_triggered_action`
                     (
                        rta_iss_id,
                        rta_rma_id
                     ) VALUES (
                        ?,
                        ?
                     )';
            $params = [$issue_id, $rma_id];
        }
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Clears the last triggered reminder for a given issue ID.
     *
     * @param   int $issue_id The issue ID
     * @return  bool
     */
    public static function clearLastTriggered($issue_id)
    {
        $stmt = 'DELETE FROM
                    `reminder_triggered_action`
                 WHERE
                    rta_iss_id=?';
        try {
            DB_Helper::getInstance()->query($stmt, [$issue_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }
}
