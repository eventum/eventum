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

/**
 * Class to handle the business logic related to the reminder emails
 * that the system sends out.
 */
class Reminder
{
    public static $debug = false;

    /**
     * Returns whether we are in "debug mode" or not. Returning true
     * here will enable all sorts of helpful messages in the reminder
     * check script.
     *
     * @return  bool
     */
    public static function isDebug()
    {
        return self::$debug;
    }

    /**
     * Method used to quickly change the ranking of a reminder entry
     * from the administration screen.
     *
     * @param   int $rem_id The reminder entry ID
     * @param   string $rank_type Whether we should change the reminder ID down or up (options are 'asc' or 'desc')
     * @return  bool
     */
    public static function changeRank($rem_id, $rank_type)
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
            $stmt = 'UPDATE
                        `reminder_level`
                     SET
                        rem_rank=?
                     WHERE
                        rem_id=?';
            DB_Helper::getInstance()->query($stmt, [$ranking[$rem_id], $replaced_rem_id]);
        }
        $stmt = 'UPDATE
                    `reminder_level`
                 SET
                    rem_rank=?
                 WHERE
                    rem_id=?';
        DB_Helper::getInstance()->query($stmt, [$new_rank, $rem_id]);

        return true;
    }

    /**
     * Returns an associative array with the list of reminder IDs and
     * their respective ranking.
     *
     * @return  array The list of reminders
     */
    private static function _getRanking()
    {
        $stmt = 'SELECT
                    rem_id,
                    rem_rank
                 FROM
                    `reminder_level`
                 ORDER BY
                    rem_rank ASC';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt);
        } catch (DatabaseException $e) {
            return [];
        }

        return $res;
    }

    /**
     * Method used by the administration screen to list the available
     * issues in a project.
     *
     * @param   int $prj_id The project ID
     * @return  array The list of issues
     */
    public static function getIssueAssocListByProject($prj_id)
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
     * @param   int $rem_id The reminder ID
     * @return  string The title of the reminder
     */
    public static function getTitle($rem_id)
    {
        $stmt = 'SELECT
                    rem_title
                 FROM
                    `reminder_level`
                 WHERE
                    rem_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$rem_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the project associated to a given reminder.
     *
     * @param   int $rem_id The reminder ID
     * @return  int The project ID
     */
    public static function getProjectID($rem_id)
    {
        $stmt = 'SELECT
                    rem_prj_id
                 FROM
                    `reminder_level`
                 WHERE
                    rem_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$rem_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the details for a specific reminder.
     *
     * @param   int $rem_id The reminder ID
     * @return  array The details for the specified reminder
     */
    public static function getDetails($rem_id)
    {
        $stmt = 'SELECT
                    *
                 FROM
                    `reminder_level`
                 WHERE
                    rem_id=?';
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, [$rem_id]);
        } catch (DatabaseException $e) {
            return '';
        }

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
        $products = self::getAssociatedProducts($rem_id);
        if (count($products) > 0) {
            $res['check_product'] = 'yes';
            $res['rer_pro_id'] = $products;
        }
        $severities = self::getAssociatedSeverities($rem_id);
        if (count($severities) > 0) {
            $res['check_severity'] = 'yes';
            $res['rer_sev_id'] = $severities;
        }

        return $res;
    }

    /**
     * Method used to get a list of all priority IDs associated with the given
     * reminder.
     *
     * @param   int $rem_id The reminder ID
     * @return  array The list of associated priority IDs
     */
    public static function getAssociatedPriorities($rem_id)
    {
        $stmt = 'SELECT
                    rep_pri_id
                 FROM
                    `reminder_priority`
                 WHERE
                    rep_rem_id=?';
        try {
            $res = DB_Helper::getInstance()->getColumn($stmt, [$rem_id]);
        } catch (DatabaseException $e) {
            return [];
        }

        return $res;
    }

    public static function getAssociatedProducts($rem_id)
    {
        $stmt = 'SELECT
                    rpr_pro_id
                 FROM
                    `reminder_product`
                 WHERE
                    rpr_rem_id=?';
        try {
            $res = DB_Helper::getInstance()->getColumn($stmt, [$rem_id]);
        } catch (DatabaseException $e) {
            return [];
        }

        return $res;
    }

    /**
     * Method used to get a list of all severity IDs associated with the given
     * reminder.
     *
     * @param   int $rem_id The reminder ID
     * @return  array The list of associated severity IDs
     */
    public static function getAssociatedSeverities($rem_id)
    {
        $stmt = 'SELECT
                    rms_sev_id
                 FROM
                    `reminder_severity`
                 WHERE
                    rms_rem_id=?';
        try {
            $res = DB_Helper::getInstance()->getColumn($stmt, [$rem_id]);
        } catch (DatabaseException $e) {
            return [];
        }

        return $res;
    }

    /**
     * Method used to associate a support level ID with a given
     * reminder entry ID.
     *
     * @param   int $rem_id The reminder ID
     * @param   int $support_level_id The support level ID
     * @return  bool
     */
    public static function addSupportLevelAssociation($rem_id, $support_level_id)
    {
        $stmt = 'INSERT INTO
                    `reminder_requirement`
                 (
                    rer_rem_id,
                    rer_support_level_id
                 ) VALUES (
                    ?, ?
                 )';
        try {
            DB_Helper::getInstance()->query($stmt, [$rem_id, $support_level_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to associate an issue with a given reminder.
     *
     * @param   int $rem_id The reminder ID
     * @param   int $issue_id The issue ID
     * @return  bool
     */
    public static function addIssueAssociation($rem_id, $issue_id)
    {
        $stmt = 'INSERT INTO
                    `reminder_requirement`
                 (
                    rer_rem_id,
                    rer_iss_id
                 ) VALUES (
                    ?, ?
                 )';
        try {
            DB_Helper::getInstance()->query($stmt, [$rem_id, $issue_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to associate a customer ID with a given reminder
     * entry ID.
     *
     * @param   int $rem_id The reminder ID
     * @param   int $customer_id The customer ID
     * @return  bool
     */
    public static function addCustomerAssociation($rem_id, $customer_id)
    {
        $stmt = 'INSERT INTO
                    `reminder_requirement`
                 (
                    rer_rem_id,
                    rer_customer_id
                 ) VALUES (
                    ?, ?
                 )';
        try {
            DB_Helper::getInstance()->query($stmt, [$rem_id, $customer_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to associate a reminder with any issue.
     *
     * @param   int $rem_id The reminder ID
     * @return  bool
     */
    public static function associateAllIssues($rem_id)
    {
        $stmt = 'INSERT INTO
                    `reminder_requirement`
                 (
                    rer_rem_id,
                    rer_trigger_all_issues
                 ) VALUES (
                    ?, ?
                 )';
        try {
            DB_Helper::getInstance()->query($stmt, [$rem_id, 1]);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to associate a priority with a given reminder.
     *
     * @param   int $rem_id The reminder ID
     * @param   int $priority_id The priority ID
     * @return  bool
     */
    public static function addPriorityAssociation($rem_id, $priority_id)
    {
        $stmt = 'INSERT INTO
                    `reminder_priority`
                 (
                    rep_rem_id,
                    rep_pri_id
                 ) VALUES (
                    ?, ?
                 )';
        try {
            DB_Helper::getInstance()->query($stmt, [$rem_id, $priority_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    public static function addProductAssociation($rem_id, $pro_id)
    {
        $stmt = 'INSERT INTO
                    `reminder_product`
                 (
                    rpr_rem_id,
                    rpr_pro_id
                 ) VALUES (
                    ?, ?
                 )';
        try {
            DB_Helper::getInstance()->query($stmt, [$rem_id, $pro_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to associate a severity with a given reminder.
     *
     * @param   int $rem_id The severity ID
     * @param $severity_id
     * @return  bool
     */
    public static function addSeverityAssociation($rem_id, $severity_id)
    {
        $stmt = 'INSERT INTO
                    `reminder_severity`
                 (
                    rms_rem_id,
                    rms_sev_id
                 ) VALUES (
                    ?, ?
                 )';
        try {
            DB_Helper::getInstance()->query($stmt, [$rem_id, $severity_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to remove all requirements and priority associations for a
     * given reminder.
     *
     * @param   int $rem_id The reminder ID
     */
    public static function removeAllAssociations($rem_id)
    {
        if (!is_array($rem_id)) {
            $rem_id = [$rem_id];
        }
        $itemlist = DB_Helper::buildList($rem_id);

        $stmt = "DELETE FROM
                    `reminder_requirement`
                 WHERE
                    rer_rem_id IN ($itemlist)";
        DB_Helper::getInstance()->query($stmt, $rem_id);

        $stmt = "DELETE FROM
                    `reminder_priority`
                 WHERE
                    rep_rem_id IN ($itemlist)";
        DB_Helper::getInstance()->query($stmt, $rem_id);

        $stmt = "DELETE FROM
                    `reminder_product`
                 WHERE
                    rpr_rem_id IN ($itemlist)";
        DB_Helper::getInstance()->query($stmt, $rem_id);

        $stmt = "DELETE FROM
                    `reminder_severity`
                 WHERE
                    rms_rem_id IN ($itemlist)";
        DB_Helper::getInstance()->query($stmt, $rem_id);
    }

    /**
     * Method used to create a new reminder.
     *
     * @return  int 1 if the insert worked, -1 or -2 otherwise
     */
    public static function insert()
    {
        $stmt = 'INSERT INTO
                    `reminder_level`
                 (
                    rem_created_date,
                    rem_rank,
                    rem_title,
                    rem_prj_id,
                    rem_skip_weekend
                 ) VALUES (
                    ?, ?, ?, ?, ?
                 )';
        $params = [
            Date_Helper::getCurrentDateGMT(),
            $_POST['rank'],
            $_POST['title'],
            $_POST['project'],
            $_POST['skip_weekend'],
        ];
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return -1;
        }

        $new_rem_id = DB_Helper::get_last_insert_id();
        // map the reminder requirements now
        if ((@$_POST['reminder_type'] == 'support_level') && (count($_POST['support_levels']) > 0)) {
            foreach ($_POST['support_levels'] as $level) {
                self::addSupportLevelAssociation($new_rem_id, $level);
            }
        } elseif ((@$_POST['reminder_type'] == 'issue') && (count($_POST['issues']) > 0)) {
            foreach ($_POST['issues'] as $issue_id) {
                self::addIssueAssociation($new_rem_id, $issue_id);
            }
        } elseif ((@$_POST['reminder_type'] == 'customer') && (count($_POST['customers']) > 0)) {
            foreach ($_POST['customers'] as $customer_id) {
                self::addCustomerAssociation($new_rem_id, $customer_id);
            }
        } elseif (@$_POST['reminder_type'] == 'all_issues') {
            self::associateAllIssues($new_rem_id);
        }
        if ((@$_POST['check_priority'] == 'yes') && (count($_POST['priorities']) > 0)) {
            foreach ($_POST['priorities'] as $priority_id) {
                self::addPriorityAssociation($new_rem_id, $priority_id);
            }
        }
        if ((@$_POST['check_product'] == 'yes') && (count($_POST['products']) > 0)) {
            foreach ($_POST['products'] as $pro_id) {
                self::addProductAssociation($new_rem_id, $pro_id);
            }
        }
        if ((@$_POST['check_severity'] == 'yes') && (count($_POST['severities']) > 0)) {
            foreach ($_POST['severities'] as $severity_id) {
                self::addSeverityAssociation($new_rem_id, $severity_id);
            }
        }

        return 1;
    }

    /**
     * Method used to update the details of a specific reminder.
     *
     * @return  int 1 if the update worked, -1 or -2 otherwise
     */
    public static function update()
    {
        $stmt = 'UPDATE
                    `reminder_level`
                 SET
                    rem_last_updated_date=?,
                    rem_rank=?,
                    rem_title=?,
                    rem_prj_id=?,
                    rem_skip_weekend=?
                 WHERE
                    rem_id=?';
        $params = [
            Date_Helper::getCurrentDateGMT(),
            $_POST['rank'],
            $_POST['title'],
            $_POST['project'],
            $_POST['skip_weekend'],
            $_POST['id'],
        ];
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return -1;
        }

        self::removeAllAssociations($_POST['id']);
        // map the reminder requirements now
        if ((@$_POST['reminder_type'] == 'support_level') && (count($_POST['support_levels']) > 0)) {
            foreach ($_POST['support_levels'] as $level) {
                self::addSupportLevelAssociation($_POST['id'], $level);
            }
        } elseif ((@$_POST['reminder_type'] == 'issue') && (count($_POST['issues']) > 0)) {
            foreach ($_POST['issues'] as $issue_id) {
                self::addIssueAssociation($_POST['id'], $issue_id);
            }
        } elseif ((@$_POST['reminder_type'] == 'customer') && (count($_POST['customers']) > 0)) {
            foreach ($_POST['customers'] as $customer_id) {
                self::addCustomerAssociation($_POST['id'], $customer_id);
            }
        } elseif (@$_POST['reminder_type'] == 'all_issues') {
            self::associateAllIssues($_POST['id']);
        }
        if ((@$_POST['check_priority'] == 'yes') && (count($_POST['priorities']) > 0)) {
            foreach ($_POST['priorities'] as $priority_id) {
                self::addPriorityAssociation($_POST['id'], $priority_id);
            }
        }
        if ((@$_POST['check_product'] == 'yes') && (count($_POST['products']) > 0)) {
            foreach ($_POST['products'] as $pro_id) {
                self::addProductAssociation($_POST['id'], $pro_id);
            }
        }
        if ((@$_POST['check_severity'] == 'yes') && (count($_POST['severities']) > 0)) {
            foreach ($_POST['severities'] as $severity_id) {
                self::addSeverityAssociation($_POST['id'], $severity_id);
            }
        }

        return 1;
    }

    /**
     * Method used to remove reminders by using the administrative
     * interface of the system.
     *
     * @return  bool
     */
    public static function remove()
    {
        $items = $_POST['items'];
        $itemlist = DB_Helper::buildList($items);

        $stmt = "DELETE FROM
                    `reminder_level`
                 WHERE
                    rem_id IN ($itemlist)";
        try {
            DB_Helper::getInstance()->query($stmt, $items);
        } catch (DatabaseException $e) {
            return false;
        }

        self::removeAllAssociations($items);
        $stmt = "SELECT
                    rma_id
                 FROM
                    `reminder_action`
                 WHERE
                    rma_rem_id IN ($itemlist)";
        $actions = DB_Helper::getInstance()->getColumn($stmt, $items);
        if (count($actions) > 0) {
            Reminder_Action::remove($actions);
        }

        return true;
    }

    /**
     * Method used to get the list of requirements associated with a given
     * reminder.
     *
     * @param   int $rem_id The reminder ID
     * @return  array The list of requirements
     */
    public static function getRequirements($rem_id)
    {
        $stmt = 'SELECT
                    rer_customer_id,
                    rer_iss_id,
                    rer_support_level_id,
                    rer_trigger_all_issues
                 FROM
                    `reminder_requirement`
                 WHERE
                    rer_rem_id=?';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, [$rem_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        $type = '';
        $values = [];
        foreach ($res as $row) {
            if ($row['rer_trigger_all_issues'] == '1') {
                return ['type' => 'ALL'];
            }

            if (!empty($row['rer_support_level_id'])) {
                $type = 'support_level';
                $values[] = $row['rer_support_level_id'];
            } elseif (!empty($row['rer_customer_id'])) {
                $type = 'customer';
                $values[] = $row['rer_customer_id'];
            } elseif (!empty($row['rer_iss_id'])) {
                $type = 'issue';
                $values[] = $row['rer_iss_id'];
            }
        }

        return [
            'type' => $type,
            'values' => $values,
        ];
    }

    /**
     * Method used to get the list of reminders to be displayed in the
     * administration section.
     *
     * @return  array The list of reminders
     */
    public static function getAdminList()
    {
        $stmt = 'SELECT
                    `reminder_level`.*,
                    prj_title
                 FROM
                    `reminder_level`,
                    `project`
                 WHERE
                    rem_prj_id=prj_id
                 ORDER BY
                    rem_rank ASC';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt);
        } catch (DatabaseException $e) {
            return [];
        }

        foreach ($res as &$row) {
            $actions = Reminder_Action::getList($row['rem_id']);
            $row['total_actions'] = count($actions);
            $priorities = self::getAssociatedPriorities($row['rem_id']);
            $priority_titles = Priority::getAssocList($row['rem_prj_id']);
            $row['priorities'] = [];
            if (count($priorities) > 0) {
                foreach ($priorities as $pri_id) {
                    $row['priorities'][] = $priority_titles[$pri_id];
                }
            } else {
                $row['priorities'][] = 'Any';
            }
            $requirements = self::getRequirements($row['rem_id']);
            $row['type'] = $requirements['type'];
        }

        return $res;
    }

    /**
     * Method used to get the full list of reminders.
     *
     * @return  array The list of reminders
     */
    public static function getList()
    {
        $stmt = 'SELECT
                    *
                 FROM
                    `reminder_level`
                 ORDER BY
                    rem_rank ASC';
        $res = DB_Helper::getInstance()->getAll($stmt);

        if (empty($res)) {
            return [];
        }

        $t = [];
        foreach ($res as &$row) {
            // ignore reminders that have no actions set yet...
            $actions = Reminder_Action::getList($row['rem_id']);
            if (count($actions) == 0) {
                continue;
            }
            $row['actions'] = $actions;
            $t[] = $row;
            unset($row);
        }

        return $t;
    }

    /**
     * Method used to get the list of issue IDs that match the given conditions.
     *
     * @param   array $reminder The reminder data
     * @param   array $conditions The list of conditions
     * @return  array The list of issue IDs
     */
    public static function getTriggeredIssues($reminder, $conditions)
    {
        // - build the SQL query to check if we have an issue that matches these conditions...
        $stmt = 'SELECT
                    iss_id,
                    iss_prj_id
                 FROM
                    `issue`';

        $products = self::getAssociatedProducts($reminder['rem_id']);
        if (count($products) > 0) {
            $stmt .= ',
                    `issue_product_version`';
        }

        $stmt .= self::getWhereClause($reminder, $conditions);
        $stmt .= ' AND iss_trigger_reminders=1 ';
        // can't rely on the mysql server's timezone setting, so let's use gmt dates throughout
        $stmt = str_replace('UNIX_TIMESTAMP()', "UNIX_TIMESTAMP('" . Date_Helper::getCurrentDateGMT() . "')", $stmt);
        try {
            $res = DB_Helper::getInstance()->getPair($stmt);
        } catch (DatabaseException $e) {
            return [];
        }

        // - if query returns >= 1, then run the appropriate action
        if (empty($res)) {
            return [];
        }

        // check for conditions that can't be run in the DB
        foreach ($res as $iss_id => $iss_prj_id) {
            foreach ($conditions as $condition) {
                if (!empty($condition['rmf_sql_representation'])) {
                    continue;
                }
                if ($condition['rmf_title'] == 'Active Group') {
                    $equal = (Workflow::getActiveGroup($iss_prj_id) == $condition['rlc_value']);
                    if ((($condition['rmo_sql_representation'] == '=') && ($equal != true)) ||
                        (($condition['rmo_sql_representation'] == '<>') && ($equal != false))) {
                        unset($res[$iss_id]);
                    }
                }
            }
        }

        return array_keys($res);
    }

    /**
     * Method used to generate a where clause from the given list of conditions.
     *
     * @param   array $reminder an array of reminder info
     * @param   array $conditions The list of conditions
     * @return  string The where clause
     */
    public static function getWhereClause($reminder, $conditions)
    {
        $stmt = '
                  WHERE
                    iss_prj_id=' . $reminder['rem_prj_id'] . "\n";
        $requirement = self::getRequirements($reminder['rem_id']);
        if ($requirement['type'] == 'issue') {
            $stmt .= ' AND iss_id IN (' . implode(', ', $requirement['values']) . ")\n";
        } else {
            if (CRM::hasCustomerIntegration($reminder['rem_prj_id'])) {
                $crm = CRM::getInstance($reminder['rem_prj_id']);
                if ($requirement['type'] == 'customer') {
                    $stmt .= ' AND iss_customer_id IN (' . implode(', ', $requirement['values']) . ")\n";
                } elseif ($requirement['type'] == 'support_level') {
                    $customer_ids = $crm->getCustomerIDsBySupportLevel($requirement['values'], [CRM_EXCLUDE_EXPIRED]);
                    // break the query on purpose if no customers could be found
                    if (count($customer_ids) == 0) {
                        $customer_ids = [-1];
                    }
                    $stmt .= ' AND iss_customer_id IN (' . implode(', ', $customer_ids) . ")\n";
                }
            }
        }
        $priorities = self::getAssociatedPriorities($reminder['rem_id']);
        if (count($priorities) > 0) {
            $stmt .= ' AND iss_pri_id IN (' . implode(', ', $priorities) . ")\n";
        }
        $products = self::getAssociatedProducts($reminder['rem_id']);
        if (count($products) > 0) {
            $stmt .= ' AND ipv_iss_id = iss_id AND ipv_pro_id IN (' . implode(', ', $products) . ")\n";
        }
        $severities = self::getAssociatedSeverities($reminder['rem_id']);
        if (count($severities) > 0) {
            $stmt .= ' AND iss_sev_id IN (' . implode(', ', $severities) . ")\n";
        }

        // now for the interesting stuff
        foreach ($conditions as &$cond) {
            if (empty($cond['rmf_sql_representation'])) {
                continue;
            }

            // check for fields that compare to other fields
            if (!empty($cond['rlc_comparison_rmf_id'])) {
                $sql_field = Reminder_Condition::getSQLField($cond['rlc_comparison_rmf_id']);
                $stmt .= sprintf(" AND %s %s %s\n", $cond['rmf_sql_field'],
                    $cond['rmo_sql_representation'], $sql_field);
            } else {
                // date field values are always saved as number of hours, so let's calculate them now as seconds
                if (stripos($cond['rmf_title'], 'date') !== false) {
                    // support NULL as values for a date field
                    if (strtoupper($cond['rlc_value']) === 'NULL') {
                        $cond['rmf_sql_representation'] = $cond['rmf_sql_field'];
                    } elseif (strtoupper($cond['rlc_value']) === 'NOW') {
                        $cond['rmf_sql_representation'] = 'UNIX_TIMESTAMP(' .
                            $cond['rmf_sql_field'] . ')';
                        $cond['rlc_value'] = 'UNIX_TIMESTAMP()';
                    } else {
                        $cond['rlc_value'] = $cond['rlc_value'] * 60 * 60;
                        if (@$reminder['rem_skip_weekend'] == 1) {
                            $sql_field = Reminder_Condition::getSQLField($cond['rlc_rmf_id']);
                            $cond['rmf_sql_representation'] = DB_Helper::getNoWeekendDateDiffSQL($sql_field);
                        }
                    }
                }

                $stmt .= sprintf(" AND %s %s %s\n",
                    $cond['rmf_sql_representation'], $cond['rmo_sql_representation'], $cond['rlc_value']);
            }
        }

        return $stmt;
    }

    /**
     * Method used to generate an SQL query to be used in debugging the reminder
     * conditions.
     *
     * @param   int $rem_id The reminder ID
     * @param   int $rma_id The reminder action ID
     * @return  string The SQL query
     */
    public static function getSQLQuery($rem_id, $rma_id)
    {
        $reminder = self::getDetails($rem_id);
        $conditions = Reminder_Condition::getList($rma_id);
        $stmt = 'SELECT
                    iss_id
                 FROM
                    `issue`';
        $products = self::getAssociatedProducts($reminder['rem_id']);
        if (count($products) > 0) {
            $stmt .= ',
                    issue_product_version';
        }
        $stmt .= self::getWhereClause($reminder, $conditions);
        // can't rely on the mysql server's timezone setting, so let's use gmt dates throughout
        $stmt = str_replace('UNIX_TIMESTAMP()', "UNIX_TIMESTAMP('" . Date_Helper::getCurrentDateGMT() . "')", $stmt);

        return $stmt;
    }

    /**
     * Method used to list the history of triggered reminder actions
     * for a given issue.
     *
     * @param   int $iss_id The issue ID
     * @return  array The list of triggered reminder actions
     */
    public static function getHistoryList($iss_id)
    {
        $stmt = 'SELECT
                    rmh_created_date,
                    rma_title
                 FROM
                    `reminder_history`,
                    `reminder_action`
                 WHERE
                    rmh_iss_id=? AND
                    rmh_rma_id=rma_id
                 ORDER BY
                    rmh_created_date DESC';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, [$iss_id]);
        } catch (DatabaseException $e) {
            return [];
        }

        return $res;
    }

    /**
     * Method used to get the list of email addresses to use
     * to send diagnostic information about the reminder system.
     *
     * @return  array The list of alert email addresses
     */
    public static function _getReminderAlertAddresses()
    {
        $emails = [];
        $setup = Setup::get();
        if ($setup['email_reminder']['status'] == 'enabled' && $setup['email_reminder']['addresses']) {
            $emails = explode(',', $setup['email_reminder']['addresses']);
            $emails = Misc::trim($emails);
        }

        return $emails;
    }
}
