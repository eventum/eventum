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
use Eventum\Extension\ExtensionLoader;
use Eventum\Monolog\Logger;

/**
 * Class to handle the business logic related to the administration
 * of custom fields in the system.
 */
class Custom_Field
{
    public static $option_types = ['combo', 'multiple', 'checkbox'];

    public static $order_by_choices = [
        'cfo_id ASC' => 'Insert',
        'cfo_id DESC' => 'Reverse insert',
        'cfo_value ASC' => 'Alphabetical',
        'cfo_value DESC' => 'Reverse alphabetical',
        'cfo_rank ASC' => 'Manual',
    ];

    /**
     * @param int $fld_id
     */
    public static function updateOptions($fld_id, $options, $new_options)
    {
        $old_options = self::getOptions($fld_id);

        $rank = 1;
        foreach ($options as $cfo_id => $cfo_value) {
            self::updateOption($cfo_id, $cfo_value, $rank++);
            unset($old_options[$cfo_id]);
        }

        // delete any options leftover
        if (count($old_options) > 0) {
            self::removeOptions($fld_id, array_keys($old_options));
        }

        if (count($new_options) > 0) {
            self::addOptions($fld_id, $new_options, $rank);
        }

        return 1;
    }

    /**
     * Method used to remove a group of custom field options.
     *
     * @param   array $fld_id The list of custom field IDs
     * @param   array $fld_id The list of custom field option IDs
     * @return  bool
     */
    public static function removeOptions($fld_id, $cfo_id)
    {
        if (!is_array($fld_id)) {
            $fld_id = [$fld_id];
        }
        if (!is_array($cfo_id)) {
            $cfo_id = [$cfo_id];
        }
        $stmt = 'DELETE FROM
                    `custom_field_option`
                 WHERE
                    cfo_id IN (' . DB_Helper::buildList($cfo_id) . ')';
        try {
            DB_Helper::getInstance()->query($stmt, $cfo_id);
        } catch (DatabaseException $e) {
            return false;
        }

        // also remove any custom field option that is currently assigned to an issue
        // FIXME: review this
        $stmt = 'DELETE FROM
                    `issue_custom_field`
                 WHERE
                    icf_fld_id IN (' . DB_Helper::buildList($fld_id) . ') AND
                    icf_value IN (' . DB_Helper::buildList($cfo_id) . ')';
        $params = array_merge($fld_id, $cfo_id);
        DB_Helper::getInstance()->query($stmt, $params);

        return true;
    }

    /**
     * Method used to add possible options into a given custom field.
     *
     * @param   int $fld_id The custom field ID
     * @param   array $options The list of options that need to be added
     * @param   int $start_rank The rank for the first new option to be inserted with
     * @return  int 1 if the insert worked, -1 otherwise
     */
    public static function addOptions($fld_id, $options, $start_rank)
    {
        if (!is_array($options)) {
            $options = [$options];
        }

        foreach ($options as $option) {
            if (empty($option)) {
                continue;
            }
            $stmt = 'INSERT INTO
                        `custom_field_option`
                     (
                        cfo_fld_id,
                        cfo_value,
                        cfo_rank
                     ) VALUES (
                        ?,
                        ?,
                        ?
                     )';
            $params = [$fld_id, $option, $start_rank++];
            try {
                DB_Helper::getInstance()->query($stmt, $params);
            } catch (DatabaseException $e) {
                return -1;
            }
        }

        return 1;
    }

    /**
     * Method used to update an existing custom field option value.
     *
     * @param   int $cfo_id The custom field option ID
     * @param   string $cfo_value The custom field option value
     * @param   int $rank The rank of the custom field option
     * @return  bool
     */
    public static function updateOption($cfo_id, $cfo_value, $rank = null)
    {
        $stmt = 'UPDATE
                    `custom_field_option`
                 SET
                    cfo_value=?,
                    cfo_rank=?
                 WHERE
                    cfo_id=?';
        try {
            DB_Helper::getInstance()->query($stmt, [$cfo_value, $rank, $cfo_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Updates custom field values from the $_POST array.
     */
    public static function updateFromPost($send_notification = false)
    {
        if (isset($_POST['custom_fields'])) {
            $updated_fields = self::updateValues($_POST['issue_id'], $_POST['custom_fields']);
            if ($send_notification) {
                Notification::notifyIssueUpdated($_POST['issue_id'], [], [], $updated_fields);
            }

            return $updated_fields;
        }
    }

    /**
     * Method used to update the values stored in the database.
     *
     * @param $issue_id
     * @param $custom_fields
     * @return array|int -1 if there is an error, otherwise an array of the updated fields
     */
    public static function updateValues($issue_id, $custom_fields)
    {
        $prj_id = Auth::getCurrentProject();

        $old_values = self::getValuesByIssue($prj_id, $issue_id);

        if (count($custom_fields) > 0) {
            // get the types for all of the custom fields being submitted
            $cf = array_keys($custom_fields);
            $cf_list = DB_Helper::buildList($cf);
            $stmt = "SELECT
                        fld_id,
                        fld_type
                     FROM
                        `custom_field`
                     WHERE
                        fld_id IN ($cf_list)";
            $field_types = DB_Helper::getInstance()->getPair($stmt, $cf);

            // get the titles for all of the custom fields being submitted
            $stmt = "SELECT
                        fld_id,
                        fld_title
                     FROM
                        `custom_field`
                     WHERE
                        fld_id IN ($cf_list)";
            $field_titles = DB_Helper::getInstance()->getPair($stmt, $cf);

            $updated_fields = [];
            foreach ($custom_fields as $fld_id => $value) {
                // security check
                $sql = 'SELECT
                            fld_min_role
                        FROM
                            `custom_field`
                        WHERE
                            fld_id = ?';

                $min_role = DB_Helper::getInstance()->getOne($sql, [$fld_id]);
                if ($min_role > Auth::getCurrentRole()) {
                    continue;
                }

                $updated_fields[$fld_id] = [
                    'title' => $field_titles[$fld_id],
                    'type' => $field_types[$fld_id],
                    'min_role' => $min_role,
                    'changes' => '',
                    'old_display' => '',
                    'new_display' => '',
                ];
                if (!in_array($field_types[$fld_id], self::$option_types)) {
                    // check if this is a date field
                    $fld_db_name = self::getDBValueFieldNameByType($field_types[$fld_id]);

                    // first check if there is actually a record for this field for the issue
                    $stmt = "SELECT
                                icf_id,
                                $fld_db_name as value
                             FROM
                                `issue_custom_field`
                             WHERE
                                icf_iss_id=? AND
                                icf_fld_id=?";

                    try {
                        $res = DB_Helper::getInstance()->getRow($stmt, [$issue_id, $fld_id]);
                    } catch (DatabaseException $e) {
                        return -1;
                    }
                    $icf_id = $res['icf_id'];
                    $old_value = $res['value'];

                    if ($old_value == $value) {
                        unset($updated_fields[$fld_id]);
                        continue;
                    }

                    if (empty($icf_id)) {
                        // record doesn't exist, insert new record
                        $stmt = "INSERT INTO
                                    `issue_custom_field`
                                 (
                                    icf_iss_id,
                                    icf_fld_id,
                                    $fld_db_name
                                 ) VALUES (
                                    ?, ?, ?
                                 )";
                        $params = [
                            $issue_id, $fld_id, $value,
                        ];
                        try {
                            DB_Helper::getInstance()->query($stmt, $params);
                        } catch (DatabaseException $e) {
                            return -1;
                        }
                    } else {
                        // record exists, update it
                        $stmt = "UPDATE
                                    `issue_custom_field`
                                 SET
                                    $fld_db_name=?
                                 WHERE
                                    icf_id=?";
                        $params = [$value, $icf_id];
                        try {
                            DB_Helper::getInstance()->query($stmt, $params);
                        } catch (DatabaseException $e) {
                            return -1;
                        }
                    }
                    $updated_fields[$fld_id]['old_display'] = $old_value;
                    $updated_fields[$fld_id]['new_display'] = $value;
                    if ($field_types[$fld_id] == 'textarea') {
                        $updated_fields[$fld_id]['changes'] = '';
                    } else {
                        $updated_fields[$fld_id]['changes'] = History::formatChanges($old_value, $value);
                    }
                } else {
                    $old_value = self::getDisplayValue($issue_id, $fld_id, true);

                    // remove dummy value from checkboxes. This dummy value is required so all real values can be unchecked.
                    if ($field_types[$fld_id] == 'checkbox') {
                        $value = array_filter($value);
                    }

                    if (!is_array($old_value)) {
                        $old_value = [$old_value];
                    }
                    if (!is_array($value)) {
                        $value = [$value];
                    }
                    if ((count(array_diff($old_value, $value)) > 0) || (count(array_diff($value, $old_value)) > 0)) {
                        $old_display_value = self::getDisplayValue($issue_id, $fld_id);
                        // need to remove all associated options from issue_custom_field and then
                        // add the selected options coming from the form
                        self::removeIssueAssociation($fld_id, $issue_id);
                        if (@count($value) > 0) {
                            self::associateIssue($issue_id, $fld_id, $value);
                        }
                        $new_display_value = self::getDisplayValue($issue_id, $fld_id);
                        $updated_fields[$fld_id]['changes'] = History::formatChanges($old_display_value, $new_display_value);
                        $updated_fields[$fld_id]['old_display'] = $old_display_value;
                        $updated_fields[$fld_id]['new_display'] = $new_display_value;
                    } else {
                        unset($updated_fields[$fld_id]);
                    }
                }
            }

            Workflow::handleCustomFieldsUpdated($prj_id, $issue_id, $old_values, self::getValuesByIssue($prj_id, $issue_id), $updated_fields);
            Issue::markAsUpdated($issue_id);

            if (count($updated_fields) > 0) {
                // log the changes
                $changes = [];
                foreach ($updated_fields as $fld_id => $updated_field) {
                    if (!isset($changes[$updated_field['min_role']])) {
                        $changes[$updated_field['min_role']] = [];
                    }
                    $title = $updated_field['title'];
                    $value = $updated_field['changes'];

                    if (!empty($value)) {
                        $changes[$updated_field['min_role']][] = "$title: $value";
                    } else {
                        $changes[$updated_field['min_role']][] = $title;
                    }
                }

                $usr_id = Auth::getUserID();
                $usr_full_name = User::getFullName($usr_id);
                foreach ($changes as $min_role => $role_changes) {
                    History::add($issue_id, $usr_id, 'custom_field_updated', 'Custom field updated ({changes}) by {user}', [
                        'changes' => implode('; ', $role_changes),
                        'user' => $usr_full_name,
                    ], $min_role);
                }
            }

            return $updated_fields;
        }

        return [];
    }

    /**
     * Returns custom field updates that are visible to the specified role
     *
     * @param   array $updated_fields
     * @param   int $role
     * @return  array
     */
    public static function getUpdatedFieldsForRole($updated_fields, $role)
    {
        $role_updates = [];
        foreach ($updated_fields as $fld_id => $field) {
            if ($role >= $field['min_role']) {
                $role_updates[$fld_id] = $field;
            }
        }

        return $role_updates;
    }

    /**
     * Returns custom field updates in a diff format
     *
     * @param   array $updated_fields
     * @param   bool $role If specified only fields that $role can see will be returned
     * @return  array
     */
    public static function formatUpdatesToDiffs($updated_fields, $role = false)
    {
        if ($role) {
            $updated_fields = self::getUpdatedFieldsForRole($updated_fields, $role);
        }
        $diffs = [];
        foreach ($updated_fields as $fld_id => $field) {
            if ($field['old_display'] != $field['new_display']) {
                if ($field['type'] == 'textarea') {
                    $old = explode("\n", $field['old_display']);
                    $new = explode("\n", $field['new_display']);
                    $diff = new Text_Diff($old, $new);
                    $renderer = new Text_Diff_Renderer_unified();
                    $desc_diff = explode("\n", trim($renderer->render($diff)));
                    $diffs[] = $field['title'] . ':';
                    foreach ($desc_diff as $diff) {
                        $diffs[] = $diff;
                    }
                    $diffs[] = '';
                } else {
                    $diffs[] = '-' . $field['title'] . ': ' . $field['old_display'];
                    $diffs[] = '+' . $field['title'] . ': ' . $field['new_display'];
                }
            }
        }

        return $diffs;
    }

    /**
     * Method used to associate a custom field value to a given
     * issue ID.
     *
     * @param   int $iss_id The issue ID
     * @param   int $fld_id The custom field ID
     * @param   string  $value The custom field value
     * @return  bool Whether the association worked or not
     */
    public static function associateIssue($iss_id, $fld_id, $value)
    {
        // check if this is a date field
        $fld_details = self::getDetails($fld_id);
        if (!is_array($value)) {
            $value = [$value];
        }
        foreach ($value as $item) {
            $params = [$iss_id, $fld_id];
            if ($fld_details['fld_type'] == 'integer') {
                $params[] = $item;
            } elseif ((in_array($fld_details['fld_type'], self::$option_types) && ($item == -1))) {
                continue;
            } else {
                $params[] = $item;
            }

            $fld_name = self::getDBValueFieldNameByType($fld_details['fld_type']);
            $stmt = "INSERT INTO
                        `issue_custom_field`
                     (
                        icf_iss_id,
                        icf_fld_id,
                        $fld_name
                     ) VALUES (
                        ?, ?, ?
                     )";
            try {
                DB_Helper::getInstance()->query($stmt, $params);
            } catch (DatabaseException $e) {
                return false;
            }
        }

        return true;
    }

    /**
     * Method used to get the list of custom fields associated with
     * a given project.
     *
     * @param   int $prj_id The project ID
     * @param   string $form_type The type of the form
     * @param   string $fld_type The type of field (optional)
     * @param   bool    $for_edit True if the fld_min_role_edit permission should be checked
     * @return  array The list of custom fields
     */
    public static function getListByProject($prj_id, $form_type, $fld_type = false, $for_edit = false)
    {
        $stmt = 'SELECT
                    fld_id,
                    fld_title,
                    fld_description,
                    fld_type,
                    fld_report_form_required,
                    fld_anonymous_form_required,
                    fld_min_role
                 FROM
                    `custom_field`,
                    `project_custom_field`
                 WHERE
                    pcf_fld_id=fld_id AND
                    pcf_prj_id=?';

        $params = [
            $prj_id,
        ];

        if ($form_type != 'anonymous_form') {
            $stmt .= ' AND
                    fld_min_role <= ?';
            $params[] = Auth::getCurrentRole();
        }
        if ($for_edit) {
            $stmt .= ' AND
                    fld_min_role_edit <= ?';
            $params[] = Auth::getCurrentRole();
        }
        if ($form_type != '') {
            $fld_name = 'fld_' . Misc::escapeString($form_type);
            $stmt .= " AND\n" . $fld_name . '=1';
        }
        if ($fld_type != '') {
            $stmt .= " AND\nfld_type=?";
            $params[] = $fld_type;
        }
        $stmt .= '
                 ORDER BY
                    fld_rank ASC';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, $params);
        } catch (DatabaseException $e) {
            return [];
        }

        if (count($res) == 0) {
            return [];
        }

        foreach ($res as &$row) {
            // check if this has a dynamic field custom backend
            $backend = self::getBackend($row['fld_id']);
            if ((is_object($backend)) && (is_subclass_of($backend, 'Dynamic_Custom_Field_Backend'))) {
                $row['dynamic_options'] = $backend->getStructuredData();
                $row['controlling_field_id'] = $backend->getDOMid();
                $row['controlling_field_name'] = $backend->getControllingCustomFieldName();
                $row['hide_when_no_options'] = $backend->hideWhenNoOptions();
                $row['lookup_method'] = $backend->lookupMethod();
            }
            // check if the backend implements "isRequired"
            if ((is_object($backend)) && (method_exists($backend, 'isRequired'))) {
                $row['fld_report_form_required'] = $backend->isRequired($row['fld_id'], 'report');
                $row['fld_anonymous_form_required'] = $backend->isRequired($row['fld_id'], 'anonymous');
                $row['fld_close_form_required'] = $backend->isRequired($row['fld_id'], 'close');
                $row['edit_form_required'] = $backend->isRequired($row['fld_id'], 'edit');
            }
            if ((is_object($backend)) && (method_exists($backend, 'getValidationJS'))) {
                $row['validation_js'] = $backend->getValidationJS($row['fld_id'], $form_type);
            } else {
                $row['validation_js'] = '';
            }

            $row['field_options'] = self::getOptions($row['fld_id'], false, false, $form_type);

            // get the default value (if one exists)
            $backend = self::getBackend($row['fld_id']);
            if ((is_object($backend)) && (method_exists($backend, 'getDefaultValue'))) {
                $row['default_value'] = $backend->getDefaultValue($row['fld_id']);
            } else {
                $row['default_value'] = '';
            }
        }

        return $res;
    }

    /**
     * Method used to get the custom field option value.
     *
     * @param   int $fld_id The custom field ID
     * @param   int $value The custom field option ID
     * @return  string The custom field option value
     */
    public static function getOptionValue($fld_id, $value)
    {
        static $returns;

        if (empty($value)) {
            return '';
        }

        if (isset($returns[$fld_id . $value])) {
            return $returns[$fld_id . $value];
        }

        $backend = self::getBackend($fld_id);
        if ((is_object($backend)) && ((method_exists($backend, 'getList')) || (method_exists($backend, 'getOptionValue')))) {
            if (method_exists($backend, 'getOptionValue')) {
                return $backend->getOptionValue($fld_id, $value);
            }

            $values = $backend->getList($fld_id, false);
            $returns[$fld_id . $value] = @$values[$value];

            return @$values[$value];
        }

        $stmt = 'SELECT
                    cfo_value
                 FROM
                    `custom_field_option`
                 WHERE
                    cfo_fld_id=? AND
                    cfo_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$fld_id, $value]);
        } catch (DatabaseException $e) {
            return '';
        }

        if ($res == null) {
            $returns[$fld_id . $value] = '';

            return '';
        }

        $returns[$fld_id . $value] = $res;

        return $res;
    }

    /**
     * Method used to get the custom field key based on the value.
     *
     * @param   int $fld_id The custom field ID
     * @param   int $value The custom field option ID
     * @return  string The custom field option value
     */
    public static function getOptionKey($fld_id, $value)
    {
        static $returns;

        if (empty($value)) {
            return '';
        }

        if (isset($returns[$fld_id . $value])) {
            return $returns[$fld_id . $value];
        }

        $backend = self::getBackend($fld_id);
        if ((is_object($backend)) && (method_exists($backend, 'getList'))) {
            $values = $backend->getList($fld_id, false);
            $key = array_search($value, $values);
            $returns[$fld_id . $value] = $key;

            return $key;
        }

        $stmt = 'SELECT
                    cfo_id
                 FROM
                    `custom_field_option`
                 WHERE
                    cfo_fld_id=? AND
                    cfo_value=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$fld_id, $value]);
        } catch (DatabaseException $e) {
            return '';
        }

        if ($res == null) {
            $returns[$fld_id . $value] = '';

            return '';
        }

        $returns[$fld_id . $value] = $res;

        return $res;
    }

    /**
     * Method used to get the list of custom fields and custom field
     * values associated with a given issue ID. If usr_id is false method
     * defaults to current user.
     *
     * @param   int $prj_id The project ID
     * @param   int $iss_id The issue ID
     * @param   int $usr_id the ID of the user who is going to be viewing this list
     * @param   mixed   $form_type The name of the form this is for or if this is an array the ids of the fields to return
     * @param   bool    $for_edit True if the fld_min_role_edit permission should be checked
     * @return  array The list of custom fields
     */
    public static function getListByIssue($prj_id, $iss_id, $usr_id = null, $form_type = false, $for_edit = false)
    {
        if (!$usr_id) {
            $usr_id = Auth::getUserID();
        }

        $usr_role = User::getRoleByUser($usr_id, $prj_id);
        if (empty($usr_role)) {
            $usr_role = 0;
        }

        $stmt = 'SELECT
                    fld_id,
                    fld_title,
                    fld_type,
                    fld_report_form_required,
                    fld_anonymous_form_required,
                    fld_close_form_required,
                    fld_edit_form_required,
                    ' . self::getDBValueFieldSQL() . ' as value,
                    icf_value,
                    icf_value_date,
                    icf_value_integer,
                    fld_min_role,
                    fld_description
                 FROM
                    (
                    `custom_field`,
                    `project_custom_field`
                    )
                 LEFT JOIN
                    `issue_custom_field`
                 ON
                    pcf_fld_id=icf_fld_id AND
                    icf_iss_id=?
                 WHERE
                    pcf_fld_id=fld_id AND
                    pcf_prj_id=? AND
                    fld_min_role <= ?';
        $params = [
            $iss_id, $prj_id, $usr_role,
        ];

        if ($for_edit) {
            $stmt .= ' AND
                    fld_min_role_edit <= ?';
            $params[] = $usr_role;
        }

        if ($form_type != false) {
            if (is_array($form_type)) {
                $stmt .= ' AND fld_id IN(' . DB_Helper::buildList($form_type) . ')';
                $params = array_merge($params, $form_type);
            } else {
                $fld_name = 'fld_' . Misc::escapeString($form_type);
                $stmt .= " AND $fld_name=1";
            }
        }
        $stmt .= '
                 ORDER BY
                    fld_rank ASC';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, $params);
        } catch (DatabaseException $e) {
            return [];
        }

        if (count($res) == 0) {
            return [];
        }

        $fields = [];
        foreach ($res as &$row) {
            if ($row['fld_type'] == 'combo') {
                $row['selected_cfo_id'] = $row['value'];
                $row['original_value'] = $row['value'];
                $row['value'] = self::getOptionValue($row['fld_id'], $row['value']);
                $row['field_options'] = self::getOptions($row['fld_id'], false, $iss_id);

                // add the select option to the list of values if it isn't on the list (useful for fields with active and non-active items)
                if ((!empty($row['original_value'])) && (!isset($row['field_options'][$row['original_value']]))) {
                    $row['field_options'][$row['original_value']] = self::getOptionValue($row['fld_id'], $row['original_value']);
                }

                $fields[] = $row;
            } elseif (in_array($row['fld_type'], self::$option_types)) {
                // check whether this field is already in the array
                $found = 0;
                foreach ($fields as $y => $field) {
                    if ($field['fld_id'] == $row['fld_id']) {
                        $found = 1;
                        $found_index = $y;
                    }
                }
                $original_value = $row['value'];
                if (!$found) {
                    $row['selected_cfo_id'] = [$row['value']];
                    $row['value'] = self::getOptionValue($row['fld_id'], $row['value']);
                    $row['field_options'] = self::getOptions($row['fld_id']);
                    $fields[] = $row;
                    $found_index = count($fields) - 1;
                } else {
                    $fields[$found_index]['value'] .= ', ' . self::getOptionValue($row['fld_id'], $row['value']);
                    $fields[$found_index]['selected_cfo_id'][] = $row['value'];
                }

                // add the select option to the list of values if it isn't on the list (useful for fields with active and non-active items)
                if ($original_value !== null && !in_array($original_value, $fields[$found_index]['field_options'])) {
                    $fields[$found_index]['field_options'][$original_value] = self::getOptionValue($row['fld_id'], $original_value);
                }
            } else {
                $row['value'] = $row[self::getDBValueFieldNameByType($row['fld_type'])];
                $fields[] = $row;
            }
        }

        foreach ($fields as $key => $field) {
            $backend = self::getBackend($field['fld_id']);
            if ((is_object($backend)) && (is_subclass_of($backend, 'Dynamic_Custom_Field_Backend'))) {
                $fields[$key]['dynamic_options'] = $backend->getStructuredData();
                $fields[$key]['controlling_field_id'] = $backend->getControllingCustomFieldID();
                $fields[$key]['controlling_field_name'] = $backend->getControllingCustomFieldName();
                $fields[$key]['hide_when_no_options'] = $backend->hideWhenNoOptions();
                $fields[$key]['lookup_method'] = $backend->lookupMethod();
            }

            // check if the backend implements "isRequired"
            if ((is_object($backend)) && (method_exists($backend, 'isRequired'))) {
                $fields[$key]['fld_report_form_required'] = $backend->isRequired($fields[$key]['fld_id'], 'report', $iss_id);
                $fields[$key]['fld_anonymous_form_required'] = $backend->isRequired($fields[$key]['fld_id'], 'anonymous', $iss_id);
                $fields[$key]['fld_close_form_required'] = $backend->isRequired($fields[$key]['fld_id'], 'close', $iss_id);
                $fields[$key]['fld_edit_form_required'] = $backend->isRequired($fields[$key]['fld_id'], 'edit', $iss_id);
            }
            if ((is_object($backend)) && (method_exists($backend, 'getValidationJS'))) {
                $fields[$key]['validation_js'] = $backend->getValidationJS($fields[$key]['fld_id'], $form_type, $iss_id);
            } else {
                $fields[$key]['validation_js'] = '';
            }
        }

        return $fields;
    }

    /**
     * Returns an array of fields and values for a specific issue
     *
     * @param   int $prj_id The ID of the project
     * @param   int $iss_id The ID of the issue to return values for
     * @return  array An array containging fld_id => value
     */
    public static function getValuesByIssue($prj_id, $iss_id)
    {
        $values = [];
        $list = self::getListByIssue($prj_id, $iss_id);
        foreach ($list as $field) {
            if ($field['fld_type'] == 'combo') {
                $values[$field['fld_id']] = [
                    $field['selected_cfo_id'] => $field['value'],
                ];
            } elseif ($field['fld_type'] == 'multiple' || $field['fld_type'] == 'checkbox') {
                $selected = $field['selected_cfo_id'];
                foreach ($selected as $cfo_id) {
                    $values[$field['fld_id']][$cfo_id] = @$field['field_options'][$cfo_id];
                }
            } else {
                $values[$field['fld_id']] = $field['value'];
            }
        }

        return $values;
    }

    /**
     * Method used to remove a given list of custom fields.
     *
     * @return  bool
     */
    public static function remove()
    {
        $items = $_POST['items'];
        $list = DB_Helper::buildList($items);
        $stmt = "DELETE FROM
                    `custom_field`
                 WHERE
                    fld_id IN ($list)";
        try {
            DB_Helper::getInstance()->query($stmt, $items);
        } catch (DatabaseException $e) {
            return false;
        }

        $stmt = "DELETE FROM
                    `project_custom_field`
                 WHERE
                    pcf_fld_id IN ($list)";
        try {
            DB_Helper::getInstance()->query($stmt, $items);
        } catch (DatabaseException $e) {
            return false;
        }

        $stmt = "DELETE FROM
                    `issue_custom_field`
                 WHERE
                    icf_fld_id IN ($list)";
        try {
            DB_Helper::getInstance()->query($stmt, $items);
        } catch (DatabaseException $e) {
            return false;
        }

        $stmt = "DELETE FROM
                    `custom_field_option`
                 WHERE
                    cfo_fld_id IN ($list)";
        try {
            DB_Helper::getInstance()->query($stmt, $items);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to add a new custom field to the system.
     *
     * @return  int 1 if the insert worked, -1 otherwise
     */
    public static function insert()
    {
        if (empty($_POST['report_form'])) {
            $_POST['report_form'] = 0;
        }
        if (empty($_POST['report_form_required'])) {
            $_POST['report_form_required'] = 0;
        }
        if (empty($_POST['anon_form'])) {
            $_POST['anon_form'] = 0;
        }
        if (empty($_POST['anon_form_required'])) {
            $_POST['anon_form_required'] = 0;
        }
        if (empty($_POST['close_form'])) {
            $_POST['close_form'] = 0;
        }
        if (empty($_POST['close_form_required'])) {
            $_POST['close_form_required'] = 0;
        }
        if (empty($_POST['edit_form_required'])) {
            $_POST['edit_form_required'] = 0;
        }
        if (empty($_POST['list_display'])) {
            $_POST['list_display'] = 0;
        }
        if (empty($_POST['min_role'])) {
            $_POST['min_role'] = 1;
        }
        if (empty($_POST['min_role_edit'])) {
            $_POST['min_role_edit'] = 1;
        }
        if (!isset($_POST['rank'])) {
            $_POST['rank'] = (self::getMaxRank() + 1);
        }
        $stmt = 'INSERT INTO
                    `custom_field`
                 (
                    fld_title,
                    fld_description,
                    fld_type,
                    fld_report_form,
                    fld_report_form_required,
                    fld_anonymous_form,
                    fld_anonymous_form_required,
                    fld_close_form,
                    fld_close_form_required,
                    fld_edit_form_required,
                    fld_list_display,
                    fld_min_role,
                    fld_min_role_edit,
                    fld_rank,
                    fld_backend
                 ) VALUES (
                     ?, ?, ?, ?, ?,
                     ?, ?, ?, ?, ?,
                     ?, ?, ?, ?, ?
                 )';
        try {
            DB_Helper::getInstance()->query($stmt, [
                $_POST['title'],
                $_POST['description'],
                $_POST['field_type'],
                $_POST['report_form'],
                $_POST['report_form_required'],
                $_POST['anon_form'],
                $_POST['anon_form_required'],
                $_POST['close_form'],
                $_POST['close_form_required'],
                $_POST['edit_form_required'],
                $_POST['list_display'],
                $_POST['min_role'],
                $_POST['min_role_edit'],
                $_POST['rank'],
                @$_POST['custom_field_backend'],
            ]);
        } catch (DatabaseException $e) {
            return -1;
        }

        $new_id = DB_Helper::get_last_insert_id();
        // add the project associations!
        foreach ($_POST['projects'] as $prj_id) {
            self::associateProject($prj_id, $new_id);
        }

        return 1;
    }

    /**
     * Method used to associate a custom field to a project.
     *
     * @param   int $prj_id The project ID
     * @param   int $fld_id The custom field ID
     * @return  bool
     */
    public static function associateProject($prj_id, $fld_id)
    {
        $stmt = 'INSERT INTO
                    `project_custom_field`
                 (
                    pcf_prj_id,
                    pcf_fld_id
                 ) VALUES (
                    ?, ?
                 )';
        try {
            DB_Helper::getInstance()->query($stmt, [$prj_id, $fld_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to get the list of custom fields available in the
     * system.
     *
     * @return  array The list of custom fields
     */
    public static function getList()
    {
        $stmt = 'SELECT
                    *
                 FROM
                    `custom_field`
                 ORDER BY
                    fld_rank ASC';

        $res = DB_Helper::getInstance()->getAll($stmt);

        foreach ($res as &$row) {
            $row['projects'] = @implode(', ', array_values(self::getAssociatedProjects($row['fld_id'])));
            $row['min_role_name'] = User::getRole($row['fld_min_role']);
            $row['min_role_edit_name'] = User::getRole($row['fld_min_role_edit']);
            $row['has_options'] = in_array($row['fld_type'], self::$option_types);
            $row['field_options'] = self::getOptions($row['fld_id']);
        }

        return $res;
    }

    /**
     * Method used to get the list of associated projects with a given
     * custom field ID.
     *
     * @param   int $fld_id The project ID
     * @return  array The list of associated projects
     */
    public static function getAssociatedProjects($fld_id)
    {
        $stmt = 'SELECT
                    prj_id,
                    prj_title
                 FROM
                    `project`,
                    `project_custom_field`
                 WHERE
                    pcf_prj_id=prj_id AND
                    pcf_fld_id=?
                 ORDER BY
                    prj_title ASC';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, [$fld_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the details of a specific custom field.
     *
     * @param   int $fld_id The custom field ID
     * @param   bool $force_refresh If the details must be loaded again from the database
     * @return  array The custom field details
     */
    public static function getDetails($fld_id, $force_refresh = false)
    {
        static $returns;

        if ((isset($returns[$fld_id])) && ($force_refresh == false)) {
            return $returns[$fld_id];
        }

        $stmt = 'SELECT
                    *
                 FROM
                    `custom_field`
                 WHERE
                    fld_id=?';
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, [$fld_id]);
        } catch (DatabaseException $e) {
            return '';
        }
        if (empty($res)) {
            return null;
        }

        $res['projects'] = @array_keys(self::getAssociatedProjects($fld_id));
        $res['field_options'] = self::getOptions($fld_id, null, null, null, $res['fld_order_by']);

        $returns[$fld_id] = $res;

        return $res;
    }

    /**
     * Method used to get the list of custom field options associated
     * with a given custom field ID.
     *
     * @param   int $fld_id The custom field ID
     * @param   array $ids an array of ids to return values for
     * @param   int $issue_id The ID of the issue
     * @param   string $form_type
     * @param   string $order_by The field and order to sort by. If null it will use the field setting
     * @return array The list of custom field options
     */
    public static function getOptions($fld_id, $ids = null, $issue_id = null, $form_type = null, $order_by = null)
    {
        static $returns;

        $return_key = $fld_id . serialize($ids);

        if (isset($returns[$return_key])) {
            return $returns[$return_key];
        }

        $backend = self::getBackend($fld_id);
        if ((is_object($backend)) && (method_exists($backend, 'getList'))) {
            $list = $backend->getList($fld_id, $issue_id, $form_type);
            if ($ids) {
                foreach ($list as $id => $value) {
                    if (!in_array($id, $ids)) {
                        unset($list[$id]);
                    }
                }
            }
            // don't cache the return value for fields with backends
            return $list;
        }

        if (is_null($order_by)) {
            $fld_details = self::getDetails($fld_id);
            $order_by = $fld_details['fld_order_by'];
        }

        $stmt = 'SELECT
                    cfo_id,
                    cfo_value
                 FROM
                    `custom_field_option`
                 WHERE
                    cfo_fld_id=?';
        $params = [$fld_id];
        if ($ids) {
            $stmt .= ' AND
                    cfo_id IN(' . DB_Helper::buildList($ids) . ')';
            $params = array_merge($params, $ids);
        }
        $stmt .= '
                 ORDER BY
                    ' . $order_by;
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, $params);
        } catch (DatabaseException $e) {
            return '';
        }

        $returns[$return_key] = $res;

        return $res;
    }

    /**
     * Method used to update the details for a specific custom field.
     *
     * @return  int 1 if the update worked, -1 otherwise
     */
    public static function update()
    {
        if (empty($_POST['report_form'])) {
            $_POST['report_form'] = 0;
        }
        if (empty($_POST['report_form_required'])) {
            $_POST['report_form_required'] = 0;
        }
        if (empty($_POST['anon_form'])) {
            $_POST['anon_form'] = 0;
        }
        if (empty($_POST['anon_form_required'])) {
            $_POST['anon_form_required'] = 0;
        }
        if (empty($_POST['list_display'])) {
            $_POST['list_display'] = 0;
        }
        if (empty($_POST['close_form'])) {
            $_POST['close_form'] = 0;
        }
        if (empty($_POST['close_form_required'])) {
            $_POST['close_form_required'] = 0;
        }
        if (empty($_POST['edit_form_required'])) {
            $_POST['edit_form_required'] = 0;
        }
        if (empty($_POST['min_role'])) {
            $_POST['min_role'] = 1;
        }
        if (empty($_POST['min_role_edit'])) {
            $_POST['min_role_edit'] = 1;
        }
        if (!isset($_POST['rank'])) {
            $_POST['rank'] = (self::getMaxRank() + 1);
        }
        if (!isset($_POST['order_by'])) {
            $_POST['order_by'] = 'cfo_id ASC';
        }
        $old_details = self::getDetails($_POST['id']);
        $stmt = 'UPDATE
                    `custom_field`
                 SET
                    fld_title=?,
                    fld_description=?,
                    fld_type=?,
                    fld_report_form=?,
                    fld_report_form_required=?,
                    fld_anonymous_form=?,
                    fld_anonymous_form_required=?,
                    fld_close_form=?,
                    fld_close_form_required=?,
                    fld_edit_form_required=?,
                    fld_list_display=?,
                    fld_min_role=?,
                    fld_min_role_edit=?,
                    fld_rank = ?,
                    fld_backend = ?,
                    fld_order_by = ?
                 WHERE
                    fld_id=?';
        try {
            DB_Helper::getInstance()->query($stmt, [
                $_POST['title'],
                $_POST['description'],
                $_POST['field_type'],
                $_POST['report_form'],
                $_POST['report_form_required'],
                $_POST['anon_form'],
                $_POST['anon_form_required'],
                $_POST['close_form'],
                $_POST['close_form_required'],
                $_POST['edit_form_required'],
                $_POST['list_display'],
                $_POST['min_role'],
                $_POST['min_role_edit'],
                $_POST['rank'],
                @$_POST['custom_field_backend'],
                $_POST['order_by'],
                $_POST['id'],
            ]);
        } catch (DatabaseException $e) {
            return -1;
        }

        if ($old_details['fld_type'] != $_POST['field_type']) {
            // gotta remove all custom field options if the field is being changed from a combo box to a text field
            if ((!in_array($old_details['fld_type'], ['text', 'textarea'])) &&
                  (!in_array($_POST['field_type'], self::$option_types))) {
                self::removeOptionsByFields($_POST['id']);
            }
            if (in_array($_POST['field_type'], ['text', 'textarea', 'date', 'integer'])) {
                // update values for all other option types
                self::updateValuesForNewType($_POST['id']);
            }
        }

        // now we need to check for any changes in the project association of this custom field
        // and update the mapping table accordingly
        $old_proj_ids = @array_keys(self::getAssociatedProjects($_POST['id']));
        $diff_ids = array_diff($old_proj_ids, $_POST['projects']);
        if (count($diff_ids) > 0) {
            foreach ($diff_ids as $removed_prj_id) {
                self::removeIssueAssociation($_POST['id'], false, $removed_prj_id);
            }
        }

        // update the project associations now
        $stmt = 'DELETE FROM
                    `project_custom_field`
                 WHERE
                    pcf_fld_id=?';
        try {
            DB_Helper::getInstance()->query($stmt, [$_POST['id']]);
        } catch (DatabaseException $e) {
            return -1;
        }

        foreach ($_POST['projects'] as $prj_id) {
            self::associateProject($prj_id, $_POST['id']);
        }

        return 1;
    }

    /**
     * Method used to get the list of custom fields associated with a
     * given project.
     *
     * @param   int $prj_id The project ID
     * @return  array The list of custom fields
     */
    public static function getFieldsByProject($prj_id)
    {
        $stmt = 'SELECT
                    pcf_fld_id
                 FROM
                    `project_custom_field`
                 WHERE
                    pcf_prj_id=?';
        try {
            $res = DB_Helper::getInstance()->getColumn($stmt, [$prj_id]);
        } catch (DatabaseException $e) {
            return [];
        }

        return $res;
    }

    /**
     * Method used to remove the issue associations related to a given
     * custom field ID.
     *
     * @param   int[] $fld_id The custom field ID
     * @param   int $issue_id The issue ID (not required)
     * @param   int $prj_id The project ID (not required)
     * @return  bool
     */
    public static function removeIssueAssociation($fld_id, $issue_id = null, $prj_id = null)
    {
        if (!is_array($fld_id)) {
            $fld_id = [$fld_id];
        }

        $issues = [];
        if ($issue_id) {
            $issues = [$issue_id];
        } elseif ($prj_id) {
            $sql = 'SELECT
                        iss_id
                    FROM
                        `issue`
                    WHERE
                        iss_prj_id = ?';
            try {
                $res = DB_Helper::getInstance()->getColumn($sql, [$prj_id]);
            } catch (DatabaseException $e) {
                return false;
            }

            $issues = $res;
        }

        $stmt = 'DELETE FROM
                    `issue_custom_field`
                 WHERE
                    icf_fld_id IN (' . DB_Helper::buildList($fld_id) . ')';
        $params = $fld_id;
        if (count($issues) > 0) {
            $stmt .= ' AND icf_iss_id IN(' . DB_Helper::buildList($issues) . ')';
            $params = array_merge($params, $issues);
        }
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to remove the custom field options associated with
     * a given list of custom field IDs.
     *
     * @param   array $ids The list of custom field IDs
     * @return  bool
     */
    public static function removeOptionsByFields($ids)
    {
        $items = DB_Helper::buildList($ids);
        $stmt = "SELECT
                    cfo_id
                 FROM
                    `custom_field_option`
                 WHERE
                    cfo_fld_id IN ($items)";
        try {
            $res = DB_Helper::getInstance()->getColumn($stmt, $ids);
        } catch (DatabaseException $e) {
            return false;
        }

        self::removeOptions($ids, $res);

        return true;
    }

    /**
     * Method to return the names of the fields which should be displayed on the list issues page.
     *
     * @param   int $prj_id the ID of the project
     * @return  array an array of custom field names
     */
    public static function getFieldsToBeListed($prj_id)
    {
        $sql = 'SELECT
                    fld_id,
                    fld_title
                FROM
                    `custom_field`,
                    `project_custom_field`
                WHERE
                    fld_id = pcf_fld_id AND
                    pcf_prj_id = ? AND
                    fld_list_display = 1  AND
                    fld_min_role <= ?
                ORDER BY
                    fld_rank ASC';
        try {
            $res = DB_Helper::getInstance()->getPair($sql, [$prj_id, Auth::getCurrentRole()]);
        } catch (DatabaseException $e) {
            return [];
        }

        return $res;
    }

    /**
     * Returns the fld_id of the field with the specified title
     *
     * @param   string $title The title of the field
     * @return  int The fld_id
     */
    public static function getIDByTitle($title)
    {
        $sql = 'SELECT
                    fld_id
                FROM
                    `custom_field`
                WHERE
                    fld_title = ?';
        try {
            $res = DB_Helper::getInstance()->getOne($sql, [$title]);
        } catch (DatabaseException $e) {
            return 0;
        }

        if (empty($res)) {
            return 0;
        }

        return $res;
    }

    /**
     * Returns the value for the specified field
     *
     * @param   int $iss_id The ID of the issue
     * @param   int $fld_id The ID of the field
     * @param   bool $raw If the raw value should be displayed
     * @return mixed an array or string containing the value
     */
    public static function getDisplayValue($iss_id, $fld_id, $raw = false)
    {
        $sql = 'SELECT
                    fld_id,
                    fld_type,
                    ' . self::getDBValueFieldSQL() . ' as value
                FROM
                    `custom_field`,
                    `issue_custom_field`
                WHERE
                    fld_id=icf_fld_id AND
                    icf_iss_id=? AND
                    fld_id = ?';
        try {
            $res = DB_Helper::getInstance()->getAll($sql, [$iss_id, $fld_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        $values = [];
        foreach ($res as $row) {
            if (in_array($row['fld_type'], self::$option_types)) {
                if ($raw) {
                    $values[] = $row['value'];
                } else {
                    $values[] = self::getOptionValue($row['fld_id'], $row['value']);
                }
            } else {
                $values[] = $row['value'];
            }
        }

        if ($raw) {
            return $values;
        }

        return implode(', ', $values);
    }

    /**
     * Returns the current maximum rank of any custom fields.
     *
     * @return  int The highest rank
     */
    public static function getMaxRank()
    {
        $sql = 'SELECT
                    max(fld_rank)
                FROM
                    `custom_field`';

        return DB_Helper::getInstance()->getOne($sql);
    }

    /**
     * Changes the rank of a custom field
     */
    public static function changeRank()
    {
        $fld_id = $_REQUEST['id'];
        $direction = $_REQUEST['direction'];
        // get array of all fields and current ranks
        $fields = self::getList();
        foreach ($fields as $i => $field) {
            if ($field['fld_id'] != $fld_id) {
                continue;
            }

            // this is the field we want to mess with
            if ((($i == 0) && ($direction == -1)) ||
                ((($i + 1) == count($fields)) && ($direction == +1))) {
                // trying to move first entry lower or last entry higher will not work
                break;
            }

            $target_index = ($i + $direction);
            $target_row = $fields[$target_index];
            if (empty($target_row)) {
                break;
            }
            // update this entry
            self::setRank($fld_id, $target_row['fld_rank']);

            // update field we stole this rank from
            self::setRank($target_row['fld_id'], $field['fld_rank']);
        }

        // re-order everything starting from 1
        $fields = self::getList();
        $rank = 1;
        foreach ($fields as $field) {
            self::setRank($field['fld_id'], $rank++);
        }

        return 1;
    }

    /**
     * Sets the rank of a custom field
     *
     * @param   int $fld_id The ID of the field
     * @param   int $rank The new rank for this field
     * @return  int 1 if successful, -1 otherwise
     */
    public static function setRank($fld_id, $rank)
    {
        $sql = 'UPDATE
                    `custom_field`
                SET
                    fld_rank = ?
                WHERE
                    fld_id = ?';
        try {
            DB_Helper::getInstance()->query($sql, [$rank, $fld_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Returns an instance of custom field backend class if it exists for the
     * specified field.
     *
     * @param   int $fld_id The ID of the field
     * @return  mixed false if there is no backend or an instance of the backend class
     */
    public static function getBackend($fld_id)
    {
        static $returns;

        // poor mans caching
        if (isset($returns[$fld_id])) {
            return $returns[$fld_id];
        }

        $sql = 'SELECT
                    fld_backend
                FROM
                    `custom_field`
                WHERE
                    fld_id = ?';
        try {
            $res = DB_Helper::getInstance()->getOne($sql, [$fld_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        if ($res) {
            try {
                $instance = static::getExtensionLoader()->createInstance($res);
            } catch (InvalidArgumentException $e) {
                Logger::app()->error("Could not load backend $res", ['exception' => $e]);
                $instance = false;
            }

            $returns[$fld_id] = $instance;
        } else {
            $returns[$fld_id] = false;
        }

        return $returns[$fld_id];
    }

    /**
     * Formats the return value
     *
     * @param   mixed   $value The value to format
     * @param   int $fld_id The ID of the field
     * @param   int $issue_id The ID of the issue
     * @return  mixed   the formatted value
     */
    public static function formatValue($value, $fld_id, $issue_id)
    {
        $backend = self::getBackend($fld_id);
        if (is_object($backend) && method_exists($backend, 'formatValue')) {
            return $backend->formatValue($value, $fld_id, $issue_id);
        }

        return Link_Filter::processText(Auth::getCurrentProject(), Misc::htmlentities($value));
    }

    /**
     * Returns the name of the db field this custom field uses based on the type.
     *
     * @param   string $type
     * @return  string
     */
    public static function getDBValueFieldNameByType($type)
    {
        switch ($type) {
            case 'date':
                return 'icf_value_date';
            case 'integer':
                return 'icf_value_integer';
            default:
                return 'icf_value';
        }
    }

    public static function getDBValueFieldSQL()
    {
        return "(CASE
        WHEN fld_type = 'date' THEN icf_value_date
        WHEN fld_type = 'integer' THEN icf_value_integer
        ELSE icf_value END)";
    }

    /**
     * Analyzes the contents of the issue_custom_field and updates
     * contents based on the fld_type.
     *
     * @param   int $fld_id
     * @return bool
     */
    public static function updateValuesForNewType($fld_id)
    {
        $details = self::getDetails($fld_id, true);
        $db_field_name = self::getDBValueFieldNameByType($details['fld_type']);

        $sql = 'UPDATE
                    `issue_custom_field`
                SET
                    ';
        if ($details['fld_type'] == 'integer') {
            $sql .= "$db_field_name = IFNULL(icf_value, IFNULL(icf_value_date, NULL)),
                    icf_value = NULL,
                    icf_value_date = NULL";
        } elseif ($details['fld_type'] == 'date') {
            $sql .= "$db_field_name = IFNULL(icf_value, IFNULL(icf_value_date, NULL)),
                    icf_value = NULL,
                    icf_value_integer = NULL";
        } else {
            $sql .= "$db_field_name = IFNULL(icf_value_integer, IFNULL(icf_value_date, NULL)),
                    icf_value_integer = NULL,
                    icf_value_date = NULL";
        }
        $sql .= "
                WHERE
                    $db_field_name IS NULL AND
                    icf_fld_id = ?";
        $params = [$fld_id];
        try {
            DB_Helper::getInstance()->query($sql, $params);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * @return ExtensionLoader
     * @internal
     */
    public static function getExtensionLoader()
    {
        $dirs = [
            APP_INC_PATH . '/custom_field',
            APP_LOCAL_PATH . '/custom_field',
        ];

        return new ExtensionLoader($dirs, '%s_Custom_Field_Backend');
    }
}
