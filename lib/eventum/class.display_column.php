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

use Eventum\Db\Adapter\AdapterInterface;
use Eventum\Db\DatabaseException;

/**
 * Class to handle determining which columns should be displayed and in what order
 * on a page (e.g. Issue Listing page).
 */
class Display_Column
{
    /**
     * Returns the columns that should be displayed for the specified page.
     * This method will remove columns that should not be displayed, due to
     * lack of customer integration or insufficient role.
     *
     * @param   int $prj_id the ID of the project
     * @param   string $page the page to return columns for
     * @return  array an array of columns that should be displayed
     */
    public static function getColumnsToDisplay($prj_id, $page)
    {
        static $returns;

        // poor man's caching system
        if (!empty($returns[$prj_id][$page])) {
            return $returns[$prj_id][$page];
        }

        $current_role = Auth::getCurrentRole();
        $data = self::getSelectedColumns($prj_id, $page);
        $has_customer_integration = CRM::hasCustomerIntegration($prj_id);
        $only_with_customers = ['iss_customer_id', 'support_level'];

        // remove groups if there are no groups in the system.
        if (count(Group::getAssocList($prj_id)) < 1) {
            unset($data['iss_grp_id']);
        }
        // remove category column if there are no categories in the system
        if (count(Category::getAssocList($prj_id)) < 1) {
            unset($data['prc_title']);
        }
        // remove custom fields column if there are no custom fields
        if (count(Custom_Field::getFieldsToBeListed($prj_id)) < 1) {
            unset($data['custom_fields']);
        }
        // remove customer field if user has a role of customer
        if ($current_role == User::ROLE_CUSTOMER) {
            unset($data['iss_customer_id']);
        }
        // remove status change date column if no customizations setup
        if (count(Status::getProjectStatusCustomization($prj_id, array_keys(Status::getAssocStatusList($prj_id)))) < 1) {
            unset($data['sta_change_date']);
        }

        foreach ($data as $field => $info) {
            // remove fields based on role
            if ($info['min_role'] > $current_role) {
                unset($data[$field]);
                continue;
            }
            // remove fields based on customer integration
            if (!$has_customer_integration && (in_array($field, $only_with_customers))) {
                unset($data[$field]);
                continue;
            }
            // get title
            $data[$field] = self::getColumnInfo($page, $field);
            if (!isset($data[$field]['width'])) {
                $data[$field]['width'] = '';
            }
        }
        $returns[$prj_id][$page] = $data;

        return $data;
    }

    /**
     * Returns the columns that have been selected to be displayed on the specified page. This list
     * contains all selected columns, even if they won't actually be displayed.
     *
     * @param   int $prj_id the ID of the project
     * @param   string $page the page to return columns for
     * @return  array an array of columns that should be displayed
     */
    public static function getSelectedColumns($prj_id, $page)
    {
        static $returns;

        // poor man's caching system
        if (!empty($returns[$prj_id][$page])) {
            return $returns[$prj_id][$page];
        }

        $stmt = 'SELECT
                    ctd_field,
                    ctd_min_role,
                    ctd_rank
                FROM
                    {{%columns_to_display}}
                WHERE
                    ctd_prj_id = ? AND
                    ctd_page = ?
                ORDER BY
                    ctd_rank';
        try {
            $res = DB_Helper::getInstance()->fetchAssoc($stmt, [$prj_id, $page], AdapterInterface::DB_FETCHMODE_ASSOC);
        } catch (DatabaseException $e) {
            return [];
        }

        $returns[$prj_id][$page] = [];
        foreach ($res as $field_name => $row) {
            $returns[$prj_id][$page][$field_name] = self::getColumnInfo($page, $field_name);
            $returns[$prj_id][$page][$field_name]['min_role'] = $row['ctd_min_role'];
            $returns[$prj_id][$page][$field_name]['rank'] = $row['ctd_rank'];
        }

        return $returns[$prj_id][$page];
    }

    /**
     * Returns the info of the column
     *
     * @param   string $page the name of the page
     * @param   string $column The name of the column
     * @return  string Info on the column
     */
    public static function getColumnInfo($page, $column)
    {
        $columns = self::getAllColumns($page);

        return isset($columns[$column]) ? $columns[$column] : null;
    }

    /**
     * Returns all columns available for a page
     *
     * @param   string $page The name of the page
     * @return  array An array of columns
     */
    public static function getAllColumns($page)
    {
        $columns = [
            'list_issues' => [
                'pri_rank' => [
                    'title' => ev_gettext('Priority'),
                ],
                'sev_rank' => [
                    'title' => ev_gettext('Severity'),
                ],
                'iss_id' => [
                    'title' => ev_gettext('Issue ID'),
                ],
                'usr_full_name' => [
                    'title' => ev_gettext('Reporter'),
                ],
                'iss_created_date' => [
                    'title' => ev_gettext('Created Date'),
                ],
                'grp_name' => [
                    'title' => ev_gettext('Group'),
                ],
                'assigned' => [
                    'title' => ev_gettext('Assigned'),
                ],
                'time_spent' => [
                    'title' => ev_gettext('Time Spent'),
                ],
                'iss_percent_complete' => [
                    'title' => ev_gettext('% Complete'),
                    'default_role' => 9,
                ],
                'iss_dev_time' => [
                    'title' => ev_gettext('Est Dev Time'),
                    'default_role' => 9,
                ],
                'prc_title' => [
                    'title' => ev_gettext('Category'),
                ],
                'pre_title' => [
                    'title' => ev_gettext('Release'),
                ],
                'iss_customer_id' => [
                    'title' => ev_gettext('Customer'),
                ],
                'support_level' => [
                    'title' => ev_gettext('Support Level'),
                ],
                'sta_rank' => [
                    'title' => ev_gettext('Status'),
                ],
                'sta_change_date' => [
                    'title' => ev_gettext('Status Change Date'),
                ],
                'last_action_date' => [
                    'title' => ev_gettext('Last Action Date'),
                ],
                'custom_fields' => [
                    'title' => ev_gettext('Custom Fields'),
                ],
                'iss_summary' => [
                    'title' => ev_gettext('Summary'),
                    'align' => 'left',
                    'width' => '30%',
                ],
                'iss_expected_resolution_date' => [
                    'title' => ev_gettext('Expected Resolution Date'),
                ],
                'iss_status_change_date' => [
                    'title' => ev_gettext('Last Status Change Date'),
                ],
            ],
        ];

        return $columns[$page];
    }

    /**
     * Saves settings on which columns should be displayed.
     *
     * @return  int 1 if settings were saved successfully, -1 if there was an error
     */
    public static function save()
    {
        $page = $_REQUEST['page'];
        $prj_id = $_REQUEST['prj_id'];
        $ranks = $_REQUEST['rank'];

        asort($ranks);

        // delete current entries
        $stmt = 'DELETE FROM
                    {{%columns_to_display}}
                WHERE
                    ctd_prj_id = ? AND
                    ctd_page = ?';
        try {
            DB_Helper::getInstance()->query($stmt, [$prj_id, $page]);
        } catch (DatabaseException $e) {
            return -1;
        }

        $rank = 1;
        foreach ($ranks as $field_name => $requested_rank) {
            $sql = 'INSERT INTO
                        {{%columns_to_display}}
                    SET
                        ctd_prj_id = ?,
                        ctd_page = ?,
                        ctd_field = ?,
                        ctd_min_role = ?,
                        ctd_rank = ?';
            $params = [$prj_id, $page, $field_name, $_REQUEST['min_role'][$field_name], $rank];
            try {
                DB_Helper::getInstance()->query($sql, $params);
            } catch (DatabaseException $e) {
                return -1;
            }
            $rank++;
        }

        return 1;
    }

    /**
     * Adds records in database for new project.
     *
     * @param   int $prj_id the ID of the project
     * @return int
     */
    public static function setupNewProject($prj_id)
    {
        $page = 'list_issues';
        $columns = self::getAllColumns($page);
        $rank = 1;
        foreach ($columns as $field_name => $column) {
            if (!empty($column['default_role'])) {
                $min_role = $column['default_role'];
            } else {
                $min_role = 1;
            }
            $stmt = 'INSERT INTO
                        {{%columns_to_display}}
                     SET
                        ctd_prj_id = ?,
                        ctd_page = ?,
                        ctd_field = ?,
                        ctd_min_role = ?,
                        ctd_rank = ?';
            $params = [$prj_id, $page, $field_name, $min_role, $rank];
            try {
                DB_Helper::getInstance()->query($stmt, $params);
            } catch (DatabaseException $e) {
                return -1;
            }
            $rank++;
        }

        return 1;
    }
}
