<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2014 Eventum Team.                              |
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
// | 51 Franklin Street, Suite 330                                          |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+


/**
 * Class to handle the business logic related to the administration
 * of custom fields in the system.
 */

class Custom_Field
{
    /**
     * Method used to remove a group of custom field options.
     *
     * @param   array $fld_id The list of custom field IDs
     * @param   array $fld_id The list of custom field option IDs
     * @return  boolean
     */
    public function removeOptions($fld_id, $cfo_id)
    {
        if (!is_array($fld_id)) {
            $fld_id = array($fld_id);
        }
        if (!is_array($cfo_id)) {
            $cfo_id = array($cfo_id);
        }
        $stmt = "DELETE FROM
                    {{%custom_field_option}}
                 WHERE
                    cfo_id IN (" . DB_Helper::buildList($cfo_id) . ")";
        try {
            DB_Helper::getInstance()->query($stmt, $cfo_id);
        } catch (DbException $e) {
            return false;
        }

        // also remove any custom field option that is currently assigned to an issue
        // FIXME: review this
        $stmt = "DELETE FROM
                    {{%issue_custom_field}}
                 WHERE
                    icf_fld_id IN (" . DB_Helper::buildList($fld_id) . ") AND
                    icf_value IN (" . DB_Helper::buildList($cfo_id) . ")";
        $params = array_merge($fld_id, $cfo_id);
        DB_Helper::getInstance()->query($stmt, $params);

        return true;
    }

    /**
     * Method used to add possible options into a given custom field.
     *
     * @param   integer $fld_id The custom field ID
     * @param   array $options The list of options that need to be added
     * @return  integer 1 if the insert worked, -1 otherwise
     */
    public function addOptions($fld_id, $options)
    {
        if (!is_array($options)) {
            $options = array($options);
        }

        foreach ($options as $option) {
            $stmt = "INSERT INTO
                        {{%custom_field_option}}
                     (
                        cfo_fld_id,
                        cfo_value
                     ) VALUES (
                        ?,
                        " . DB_Helper::buildList($option) . "
                     )";
            $params = array_merge(array($fld_id, $option));
            try {
                DB_Helper::getInstance()->query($stmt, $params);
            } catch (DbException $e) {
                return -1;
            }
        }

        return 1;
    }

    /**
     * Method used to update an existing custom field option value.
     *
     * @param   integer $cfo_id The custom field option ID
     * @param   string $cfo_value The custom field option value
     * @return  boolean
     */
    public function updateOption($cfo_id, $cfo_value)
    {
        $stmt = "UPDATE
                    {{%custom_field_option}}
                 SET
                    cfo_value=?
                 WHERE
                    cfo_id=?" ;
        try {
            DB_Helper::getInstance()->query($stmt, array($cfo_value, $cfo_id));
        } catch (DbException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to update the values stored in the database.
     *
     * @return  integer 1 if the update worked properly, any other value otherwise
     */
    public static function updateValues()
    {
        $prj_id = Auth::getCurrentProject();
        $issue_id = $_POST["issue_id"];

        $old_values = self::getValuesByIssue($prj_id, $issue_id);

        if ((isset($_POST['custom_fields'])) && (count($_POST['custom_fields']) > 0)) {
            $custom_fields = $_POST["custom_fields"];

            // get the types for all of the custom fields being submitted
            $cf = array_keys($custom_fields);
            $cf_list = DB_Helper::buildList($cf);
            $stmt = "SELECT
                        fld_id,
                        fld_type
                     FROM
                        {{%custom_field}}
                     WHERE
                        fld_id IN ($cf_list)";
            $field_types = DB_Helper::getInstance()->getPair($stmt, $cf);

            // get the titles for all of the custom fields being submitted
            $stmt = "SELECT
                        fld_id,
                        fld_title
                     FROM
                        {{%custom_field}}
                     WHERE
                        fld_id IN ($cf_list)";
            $field_titles = DB_Helper::getInstance()->getPair($stmt, $cf);

            $updated_fields = array();
            foreach ($custom_fields as $fld_id => $value) {
                // security check
                $sql = "SELECT
                            fld_min_role
                        FROM
                            {{%custom_field}}
                        WHERE
                            fld_id = ?";

                $min_role = DB_Helper::getInstance()->getOne($sql, array($fld_id));
                if ($min_role > Auth::getCurrentRole()) {
                    continue;
                }

                $option_types = array(
                    'multiple',
                    'combo',
                    'checkbox',
                );
                if (!in_array($field_types[$fld_id], $option_types)) {
                    // check if this is a date field
                    $fld_db_name = self::getDBValueFieldNameByType($field_types[$fld_id]);

                    // first check if there is actually a record for this field for the issue
                    $stmt = "SELECT
                                icf_id,
                                $fld_db_name as value
                             FROM
                                {{%issue_custom_field}}
                             WHERE
                                icf_iss_id=? AND
                                icf_fld_id=?";

                    try {
                        $res = DB_Helper::getInstance()->getRow($stmt, array($issue_id, $fld_id));
                    } catch (DbException $e) {
                        return -1;
                    }
                    $icf_id = $res['icf_id'];
                    $old_value = $res['value'];

                    if ($old_value == $value) {
                        continue;
                    }

                    if (empty($icf_id)) {
                        // record doesn't exist, insert new record
                        $stmt = "INSERT INTO
                                    {{%issue_custom_field}}
                                 (
                                    icf_iss_id,
                                    icf_fld_id,
                                    $fld_db_name
                                 ) VALUES (
                                    ?, ?, ?
                                 )";
                        $params = array(
                            $issue_id, $fld_id, $value
                        );
                        try {
                            DB_Helper::getInstance()->query($stmt, $params);
                        } catch (DbException $e) {
                            return -1;
                        }
                    } else {
                        // record exists, update it
                        $stmt = "UPDATE
                                    {{%issue_custom_field}}
                                 SET
                                    $fld_db_name=?
                                 WHERE
                                    icf_id=?";
                        $params = array($value, $icf_id);
                        try {
                            DB_Helper::getInstance()->query($stmt, $params);
                        } catch (DbException $e) {
                            return -1;
                        }
                    }
                    if ($field_types[$fld_id] == 'textarea') {
                        $updated_fields[$field_titles[$fld_id]] = '';
                    } else {
                        $updated_fields[$field_titles[$fld_id]] = History::formatChanges($old_value, $value);
                    }
                } else {
                    $old_value = self::getDisplayValue($issue_id, $fld_id, true);

                    if (!is_array($old_value)) {
                        $old_value = array($old_value);
                    }
                    if (!is_array($value)) {
                        $value = array($value);
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
                        $updated_fields[$field_titles[$fld_id]] = History::formatChanges($old_display_value, $new_display_value);
                    }
                }
            }

            Workflow::handleCustomFieldsUpdated($prj_id, $issue_id, $old_values, self::getValuesByIssue($prj_id, $issue_id));
            Issue::markAsUpdated($issue_id);
            // need to save a history entry for this

            if (count($updated_fields) > 0) {
                // log the changes
                $changes = '';
                $i = 0;
                foreach ($updated_fields as $key => $value) {
                    if ($i > 0) {
                        $changes .= "; ";
                    }
                    if (!empty($value)) {
                        $changes .= "$key: $value";
                    } else {
                        $changes .= "$key";
                    }
                    $i++;
                }

                $summary = ev_gettext('Custom field updated (%1$s) by %2$s', $changes, User::getFullName(Auth::getUserID()));
                History::add($issue_id, Auth::getUserID(), History::getTypeID('custom_field_updated'), $summary);
            }
        }

        return 1;
    }

    /**
     * Method used to associate a custom field value to a given
     * issue ID.
     *
     * @param   integer $iss_id The issue ID
     * @param   integer $fld_id The custom field ID
     * @param   string  $value The custom field value
     * @return  boolean Whether the association worked or not
     */
    public static function associateIssue($iss_id, $fld_id, $value)
    {
        // check if this is a date field
        $fld_details = self::getDetails($fld_id);
        if (!is_array($value)) {
            $value = array($value);
        }
        foreach ($value as $item) {
            $params = array($iss_id, $fld_id);
            if ($fld_details['fld_type'] == 'integer') {
                $params[] = $item;
            } elseif ((in_array($fld_details['fld_type'], array('combo', 'multiple')) && ($item == -1))) {
                continue;
            } else {
                $params[] = $item;
            }

            $fld_name = self::getDBValueFieldNameByType($fld_details['fld_type']);
            $stmt = "INSERT INTO
                        {{%issue_custom_field}}
                     (
                        icf_iss_id,
                        icf_fld_id,
                        $fld_name
                     ) VALUES (
                        ?, ?, ?
                     )";
            try {
                DB_Helper::getInstance()->query($stmt, $params);
            } catch (DbException $e) {
                return false;
            }
        }

        return true;
    }

    /**
     * Method used to get the list of custom fields associated with
     * a given project.
     *
     * @param   integer $prj_id The project ID
     * @param   string $form_type The type of the form
     * @param   string $fld_type The type of field (optional)
     * @return  array The list of custom fields
     */
    public static function getListByProject($prj_id, $form_type, $fld_type = false)
    {
        $stmt = "SELECT
                    fld_id,
                    fld_title,
                    fld_description,
                    fld_type,
                    fld_report_form_required,
                    fld_anonymous_form_required,
                    fld_min_role
                 FROM
                    {{%custom_field}},
                    {{%project_custom_field}}
                 WHERE
                    pcf_fld_id=fld_id AND
                    pcf_prj_id=?";

        $params = array(
            $prj_id,
        );

        if ($form_type != 'anonymous_form') {
            $stmt .= " AND
                    fld_min_role <= ?";
            $params[] = Auth::getCurrentRole();
        }
        if ($form_type != '') {
            $fld_name = "fld_" . Misc::escapeString($form_type);
            $stmt .= " AND\n" . $fld_name . "=1";
        }
        if ($fld_type != '') {
            $stmt .= " AND\nfld_type=?";
            $params[] = $fld_type;
        }
        $stmt .= "
                 ORDER BY
                    fld_rank ASC";
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, $params);
        } catch (DbException $e) {
            return array();
        }

        if (count($res) == 0) {
            return array();
        }

        foreach ($res as &$row) {
            // check if this has a dynamic field custom backend
            $backend = self::getBackend($row['fld_id']);
            if ((is_object($backend)) && (is_subclass_of($backend, "Dynamic_Custom_Field_Backend"))) {
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
            }
            if ((is_object($backend)) && (method_exists($backend, 'getValidationJS'))) {
                $row['validation_js'] = $backend->getValidationJS($row['fld_id'], $form_type);
            } else {
                $row['validation_js'] = '';
            }

            $row["field_options"] = self::getOptions($row["fld_id"], false, false, $form_type);

            // get the default value (if one exists)
            $backend = self::getBackend($row["fld_id"]);
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
     * @param   integer $fld_id The custom field ID
     * @param   integer $value The custom field option ID
     * @return  string The custom field option value
     */
    public function getOptionValue($fld_id, $value)
    {
        static $returns;

        if (empty($value)) {
            return "";
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

        $stmt = "SELECT
                    cfo_value
                 FROM
                    {{%custom_field_option}}
                 WHERE
                    cfo_fld_id=? AND
                    cfo_id=?";
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, array($fld_id, $value));
        } catch (DbException $e) {
            return "";
        }

        if ($res == NULL) {
            $returns[$fld_id . $value] = '';

            return "";
        }

        $returns[$fld_id . $value] = $res;

        return $res;

    }

    /**
     * Method used to get the custom field key based on the value.
     *
     * @param   integer $fld_id The custom field ID
     * @param   integer $value The custom field option ID
     * @return  string The custom field option value
     */
    public static function getOptionKey($fld_id, $value)
    {
        static $returns;

        if (empty($value)) {
            return "";
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

        $stmt = "SELECT
                    cfo_id
                 FROM
                    {{%custom_field_option}}
                 WHERE
                    cfo_fld_id=? AND
                    cfo_value=?";
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, array($fld_id, $value));
        } catch (DbException $e) {
            return "";
        }

        if ($res == NULL) {
            $returns[$fld_id . $value] = '';

            return "";
        }

        $returns[$fld_id . $value] = $res;

        return $res;
    }

    /**
     * Method used to get the list of custom fields and custom field
     * values associated with a given issue ID. If usr_id is false method
     * defaults to current user.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $iss_id The issue ID
     * @param   integer $usr_id The ID of the user who is going to be viewing this list.
     * @param   mixed   $form_type The name of the form this is for or if this is an array the ids of the fields to return
     * @return  array The list of custom fields
     */
    public static function getListByIssue($prj_id, $iss_id, $usr_id = null, $form_type = false)
    {
        if (!$usr_id) {
            $usr_id = Auth::getUserID();
        }

        $usr_role = User::getRoleByUser($usr_id, $prj_id);
        if (empty($usr_role)) {
            $usr_role = 0;
        }

        $stmt = "SELECT
                    fld_id,
                    fld_title,
                    fld_type,
                    fld_report_form_required,
                    fld_anonymous_form_required,
                    fld_close_form_required,
                    " . self::getDBValueFieldSQL() . " as value,
                    icf_value,
                    icf_value_date,
                    icf_value_integer,
                    fld_min_role,
                    fld_description
                 FROM
                    (
                    {{%custom_field}},
                    {{%project_custom_field}}
                    )
                 LEFT JOIN
                    {{%issue_custom_field}}
                 ON
                    pcf_fld_id=icf_fld_id AND
                    icf_iss_id=?
                 WHERE
                    pcf_fld_id=fld_id AND
                    pcf_prj_id=? AND
                    fld_min_role <= ?";
        $params = array(
            $iss_id, $prj_id, $usr_role
        );

        if ($form_type != false) {
            if (is_array($form_type)) {
                $stmt .= " AND fld_id IN(" . DB_Helper::buildList($form_type). ")";
                $params = array_merge($params, $form_type);
            } else {
                $fld_name = "fld_" . Misc::escapeString($form_type);
                $stmt .= " AND $fld_name=1";
            }
        }
        $stmt .= "
                 ORDER BY
                    fld_rank ASC";
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, $params);
        } catch (DbException $e) {
            return array();
        }

        if (count($res) == 0) {
            return array();
        }

        $fields = array();
        foreach ($res as &$row) {
            if ($row["fld_type"] == "combo") {
                $row["selected_cfo_id"] = $row["value"];
                $row["original_value"] = $row["value"];
                $row["value"] = self::getOptionValue($row["fld_id"], $row["value"]);
                $row["field_options"] = self::getOptions($row["fld_id"], false, $iss_id);

                // add the select option to the list of values if it isn't on the list (useful for fields with active and non-active items)
                if ((!empty($row['original_value'])) && (!isset($row['field_options'][$row['original_value']]))) {
                    $row['field_options'][$row['original_value']] = self::getOptionValue($row['fld_id'], $row['original_value']);
                }

                $fields[] = $row;

            } elseif ($row['fld_type'] == 'multiple' || $row['fld_type'] == 'checkbox') {
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
                    $row["selected_cfo_id"] = array($row["value"]);
                    $row["value"] = self::getOptionValue($row["fld_id"], $row["value"]);
                    $row["field_options"] = self::getOptions($row["fld_id"]);
                    $fields[] = $row;
                    $found_index = count($fields) - 1;
                } else {
                    $fields[$found_index]['value'] .= ', ' . self::getOptionValue($row["fld_id"], $row["value"]);
                    $fields[$found_index]['selected_cfo_id'][] = $row["value"];
                }

                // add the select option to the list of values if it isn't on the list (useful for fields with active and non-active items)
                if (!is_null($original_value) && !in_array($original_value, $fields[$found_index]['field_options'])) {
                    $fields[$found_index]['field_options'][$original_value] = self::getOptionValue($row['fld_id'], $original_value);
                }
            } else {
                $row['value'] = $row[self::getDBValueFieldNameByType($row['fld_type'])];
                $fields[] = $row;
            }
        }

        foreach ($fields as $key => $field) {
            $backend = self::getBackend($field['fld_id']);
            if ((is_object($backend)) && (is_subclass_of($backend, "Dynamic_Custom_Field_Backend"))) {
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
     * @param   integer $prj_id The ID of the project
     * @param   integer $iss_id The ID of the issue to return values for
     * @return  array An array containging fld_id => value
     */
    public static function getValuesByIssue($prj_id, $iss_id)
    {
        $values = array();
        $list = self::getListByIssue($prj_id, $iss_id);
        foreach ($list as $field) {
            if ($field['fld_type'] == 'combo') {
                $values[$field['fld_id']] = array(
                    $field['selected_cfo_id'] => $field['value']
                );
            } elseif ($field['fld_type'] == 'multiple') {
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
     * @return  boolean
     */
    public static function remove()
    {
        $items = $_POST["items"];
        $list = DB_Helper::buildList($items);
        $stmt = "DELETE FROM
                    {{%custom_field}}
                 WHERE
                    fld_id IN ($list)";
        try {
            DB_Helper::getInstance()->query($stmt, $items);
        } catch (DbException $e) {
            return false;
        }

        $stmt = "DELETE FROM
                    {{%project_custom_field}}
                 WHERE
                    pcf_fld_id IN ($list)";
        try {
            DB_Helper::getInstance()->query($stmt, $items);
        } catch (DbException $e) {
            return false;
        }

        $stmt = "DELETE FROM
                    {{%issue_custom_field}}
                 WHERE
                    icf_fld_id IN ($list)";
        try {
            DB_Helper::getInstance()->query($stmt, $items);
        } catch (DbException $e) {
            return false;
        }

        $stmt = "DELETE FROM
                    {{%custom_field_option}}
                 WHERE
                    cfo_fld_id IN ($list)";
        try {
            DB_Helper::getInstance()->query($stmt, $items);
        } catch (DbException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to add a new custom field to the system.
     *
     * @return  integer 1 if the insert worked, -1 otherwise
     */
    public static function insert()
    {
        if (empty($_POST["report_form"])) {
            $_POST["report_form"] = 0;
        }
        if (empty($_POST["report_form_required"])) {
            $_POST["report_form_required"] = 0;
        }
        if (empty($_POST["anon_form"])) {
            $_POST["anon_form"] = 0;
        }
        if (empty($_POST["anon_form_required"])) {
            $_POST["anon_form_required"] = 0;
        }
        if (empty($_POST["close_form"])) {
            $_POST["close_form"] = 0;
        }
        if (empty($_POST["close_form_required"])) {
            $_POST["close_form_required"] = 0;
        }
        if (empty($_POST["list_display"])) {
            $_POST["list_display"] = 0;
        }
        if (empty($_POST["min_role"])) {
            $_POST["min_role"] = 1;
        }
        if (!isset($_POST["rank"])) {
            $_POST["rank"] = (self::getMaxRank() + 1);
        }
        $stmt = "INSERT INTO
                    {{%custom_field}}
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
                    fld_list_display,
                    fld_min_role,
                    fld_rank,
                    fld_backend
                 ) VALUES (
                     ?, ?, ?, ?, ?,
                     ?, ?, ?, ?, ?,
                     ?, ?, ?
                 )";
        try {
            DB_Helper::getInstance()->query($stmt, array(
                $_POST["title"],
                $_POST["description"],
                $_POST["field_type"],
                $_POST["report_form"],
                $_POST["report_form_required"],
                $_POST["anon_form"],
                $_POST["anon_form_required"],
                $_POST["close_form"],
                $_POST["close_form_required"],
                $_POST["list_display"],
                $_POST["min_role"],
                $_POST['rank'],
                @$_POST['custom_field_backend'],
            ));
        } catch (DbException $e) {
            return -1;
        }

        $new_id = DB_Helper::get_last_insert_id();
        if (($_POST["field_type"] == 'combo') || ($_POST["field_type"] == 'multiple')
             || ($_POST["field_type"] == 'checkbox')) {
            foreach ($_POST["field_options"] as $option_value) {
                $params = self::parseParameters($option_value);
                self::addOptions($new_id, $params["value"]);
            }
        }
        // add the project associations!
        foreach ($_POST["projects"] as $prj_id) {
            self::associateProject($prj_id, $new_id);
        }

        return 1;
    }

    /**
     * Method used to associate a custom field to a project.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $fld_id The custom field ID
     * @return  boolean
     */
    public function associateProject($prj_id, $fld_id)
    {
        $stmt = "INSERT INTO
                    {{%project_custom_field}}
                 (
                    pcf_prj_id,
                    pcf_fld_id
                 ) VALUES (
                    ?, ?
                 )";
        try {
            $res = DB_Helper::getInstance()->query($stmt, array($prj_id, $fld_id));
        } catch (DbException $e) {
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
        $stmt = "SELECT
                    *
                 FROM
                    {{%custom_field}}
                 ORDER BY
                    fld_rank ASC";
        try {
            $res = DB_Helper::getInstance()->getAll($stmt);
        } catch (DbException $e) {
            return "";
        }

        foreach ($res as &$row) {
            $row["projects"] = @implode(", ", array_values(self::getAssociatedProjects($row["fld_id"])));
            if (($row["fld_type"] == "combo") || ($row["fld_type"] == "multiple")) {
                if (!empty($row['fld_backend'])) {
                    $row["field_options"] = implode(", ", array_values(self::getOptions($row["fld_id"])));
                }
            }
            if (!empty($row['fld_backend'])) {
                $row['field_options'] = 'Backend: ' . self::getBackendName($row['fld_backend']);
            }
            $row['min_role_name'] = User::getRole($row['fld_min_role']);
        }

        return $res;
    }

    /**
     * Method used to get the list of associated projects with a given
     * custom field ID.
     *
     * @param   integer $fld_id The project ID
     * @return  array The list of associated projects
     */
    public function getAssociatedProjects($fld_id)
    {
        $stmt = "SELECT
                    prj_id,
                    prj_title
                 FROM
                    {{%project}},
                    {{%project_custom_field}}
                 WHERE
                    pcf_prj_id=prj_id AND
                    pcf_fld_id=?
                 ORDER BY
                    prj_title ASC";
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, array($fld_id));
        } catch (DbException $e) {
            return "";
        }

        return $res;
    }

    /**
     * Method used to get the details of a specific custom field.
     *
     * @param   integer $fld_id The custom field ID
     * @param   boolean $force_refresh If the details must be loaded again from the database
     * @return  array The custom field details
     */
    public static function getDetails($fld_id, $force_refresh = false)
    {
        static $returns;

        if ((isset($returns[$fld_id])) && ($force_refresh == false)) {
            return $returns[$fld_id];
        }

        $stmt = "SELECT
                    *
                 FROM
                    {{%custom_field}}
                 WHERE
                    fld_id=?";
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, array($fld_id));
        } catch (DbException $e) {
            return "";
        }

        $res["projects"] = @array_keys(self::getAssociatedProjects($fld_id));

        $options = self::getOptions($fld_id);
        foreach ($options as $cfo_id => $cfo_value) {
            $res["field_options"]["existing:" . $cfo_id . ":" . $cfo_value] = $cfo_value;
        }
        $returns[$fld_id] = $res;

        return $res;
    }

    /**
     * Method used to get the list of custom field options associated
     * with a given custom field ID.
     *
     * @param   integer $fld_id The custom field ID
     * @param   array $ids An array of ids to return values for.
     * @param   integer $issue_id The ID of the issue
     * @return  array The list of custom field options
     */
    public static function getOptions($fld_id, $ids = false, $issue_id = false, $form_type = false)
    {
        static $returns;

        $return_key = $fld_id . serialize($ids);

        if (isset($returns[$return_key])) {
            return $returns[$return_key];
        }

        $backend = self::getBackend($fld_id);
        if ((is_object($backend)) && (method_exists($backend, 'getList'))) {
            $list = $backend->getList($fld_id, $issue_id, $form_type);
            if ($ids != false) {
                foreach ($list as $id => $value) {
                    if (!in_array($id, $ids)) {
                        unset($list[$id]);
                    }
                }
            }
            // don't cache the return value for fields with backends
            return $list;
        }

        $stmt = "SELECT
                    cfo_id,
                    cfo_value
                 FROM
                    {{%custom_field_option}}
                 WHERE
                    cfo_fld_id=?";
        $params = array($fld_id);
        if ($ids != false) {
            $stmt .= " AND
                    cfo_id IN(" . DB_Helper::buildList($ids) . ")";
            $params = array_merge($params, $ids);
        }
        $stmt .= "
                 ORDER BY
                    cfo_id ASC";
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, $params);
        } catch (DbException $e) {
            return "";
        }

        asort($res);
        $returns[$return_key] = $res;

        return $res;
    }

    /**
     * Method used to parse the special format used in the combo boxes
     * in the administration section of the system, in order to be
     * used as a way to flag the system for whether the custom field
     * option is a new one or one that should be updated.
     *
     * @param   string $value The custom field option format string
     * @return  array Parameters used by the update/insert methods
     */
    private function parseParameters($value)
    {
        if (substr($value, 0, 4) == 'new:') {
            return array(
                "type"  => "new",
                "value" => substr($value, 4)
            );
        }

        $value = substr($value, strlen("existing:"));

        return array(
            "type"  => "existing",
            "id"    => substr($value, 0, strpos($value, ":")),
            "value" => substr($value, strpos($value, ":")+1)
        );
    }

    /**
     * Method used to update the details for a specific custom field.
     *
     * @return  integer 1 if the update worked, -1 otherwise
     */
    public static function update()
    {
        if (empty($_POST["report_form"])) {
            $_POST["report_form"] = 0;
        }
        if (empty($_POST["report_form_required"])) {
            $_POST["report_form_required"] = 0;
        }
        if (empty($_POST["anon_form"])) {
            $_POST["anon_form"] = 0;
        }
        if (empty($_POST["anon_form_required"])) {
            $_POST["anon_form_required"] = 0;
        }
        if (empty($_POST["list_display"])) {
            $_POST["list_display"] = 0;
        }
        if (empty($_POST["close_form"])) {
            $_POST["close_form"] = 0;
        }
        if (empty($_POST["close_form_required"])) {
            $_POST["close_form_required"] = 0;
        }
        if (empty($_POST["min_role"])) {
            $_POST["min_role"] = 1;
        }
        if (!isset($_POST["rank"])) {
            $_POST["rank"] = (self::getMaxRank() + 1);
        }
        $old_details = self::getDetails($_POST["id"]);
        $stmt = "UPDATE
                    {{%custom_field}}
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
                    fld_list_display=?,
                    fld_min_role=?,
                    fld_rank = ?,
                    fld_backend = ?
                 WHERE
                    fld_id=?";
        try {
            DB_Helper::getInstance()->query($stmt, array(
                $_POST["title"],
                $_POST["description"],
                $_POST["field_type"],
                $_POST["report_form"],
                $_POST["report_form_required"],
                $_POST["anon_form"],
                $_POST["anon_form_required"],
                $_POST["close_form"],
                $_POST["close_form_required"],
                $_POST["list_display"],
                $_POST['min_role'],
                $_POST['rank'],
                @$_POST['custom_field_backend'],
                $_POST["id"],
            ));
        } catch (DbException $e) {
            return -1;
        }

        // if the current custom field is a combo box, get all of the current options
        if (in_array($_POST["field_type"], array('combo', 'multiple'))) {
            $stmt = "SELECT
                        cfo_id
                     FROM
                        {{%custom_field_option}}
                     WHERE
                        cfo_fld_id=?";
            $current_options = DB_Helper::getInstance()->getColumn($stmt, array($_POST["id"]));
        }

        if ($old_details["fld_type"] != $_POST["field_type"]) {
            // gotta remove all custom field options if the field is being changed from a combo box to a text field
            if ((!in_array($old_details['fld_type'], array('text', 'textarea'))) &&
                  (!in_array($_POST["field_type"], array('combo', 'multiple')))) {
               self::removeOptionsByFields($_POST["id"]);
            }
            if (in_array($_POST['field_type'], array('text', 'textarea', 'date', 'integer'))) {
                // update values for all other option types
                self::updateValuesForNewType($_POST['id']);
            }
        }
        // update the custom field options, if any
        if (($_POST["field_type"] == "combo") || ($_POST["field_type"] == "multiple") ||
            ($_POST["field_type"] == "checkbox")) {
            $updated_options = array();
            foreach ($_POST["field_options"] as $option_value) {
                $params = self::parseParameters($option_value);
                if ($params["type"] == 'new') {
                    self::addOptions($_POST["id"], $params["value"]);
                } else {
                    $updated_options[] = $params["id"];
                    // check if the user is trying to update the value of this option
                    if ($params["value"] != self::getOptionValue($_POST["id"], $params["id"])) {
                        self::updateOption($params["id"], $params["value"]);
                    }
                }
            }
        }
        // get the diff between the current options and the ones posted by the form
        // and then remove the options not found in the form submissions
        if (in_array($_POST["field_type"], array('combo', 'multiple'))) {
            $diff_ids = @array_diff($current_options, $updated_options);
            if (@count($diff_ids) > 0) {
                self::removeOptions($_POST['id'], array_values($diff_ids));
            }
        }
        // now we need to check for any changes in the project association of this custom field
        // and update the mapping table accordingly
        $old_proj_ids = @array_keys(self::getAssociatedProjects($_POST["id"]));
        // COMPAT: this next line requires PHP > 4.0.4
        $diff_ids = array_diff($old_proj_ids, $_POST["projects"]);
        if (count($diff_ids) > 0) {
            foreach ($diff_ids as $removed_prj_id) {
                self::removeIssueAssociation($_POST["id"], false, $removed_prj_id );
            }
        }

        // update the project associations now
        $stmt = "DELETE FROM
                    {{%project_custom_field}}
                 WHERE
                    pcf_fld_id=?";
        try {
            DB_Helper::getInstance()->query($stmt, array($_POST["id"]));
        } catch (DbException $e) {
            return -1;
        }

        foreach ($_POST["projects"] as $prj_id) {
            self::associateProject($prj_id, $_POST["id"]);
        }

        return 1;
    }

    /**
     * Method used to get the list of custom fields associated with a
     * given project.
     *
     * @param   integer $prj_id The project ID
     * @return  array The list of custom fields
     */
    public static function getFieldsByProject($prj_id)
    {
        $stmt = "SELECT
                    pcf_fld_id
                 FROM
                    {{%project_custom_field}}
                 WHERE
                    pcf_prj_id=?";
        try {
            $res = DB_Helper::getInstance()->getColumn($stmt, array($prj_id));
        } catch (DbException $e) {
            return array();
        }

        return $res;
    }

    /**
     * Method used to remove the issue associations related to a given
     * custom field ID.
     *
     * @param   integer $fld_id The custom field ID
     * @param   integer $issue_id The issue ID (not required)
     * @param   integer $prj_id The project ID (not required)
     * @return  boolean
     */
    public function removeIssueAssociation($fld_id, $issue_id = false, $prj_id = false)
    {
        $issues = array();
        if ($issue_id != false) {
            $issues = array($issue_id);
        } elseif ($prj_id != false) {
            $sql = "SELECT
                        iss_id
                    FROM
                        {{%issue}}
                    WHERE
                        iss_prj_id = ?";
            try {
                $res = DB_Helper::getInstance()->getColumn($sql, array($prj_id));
            } catch (DbException $e) {
                return false;
            }

            $issues = $res;
        }

        $stmt = "DELETE FROM
                    {{%issue_custom_field}}
                 WHERE
                    icf_fld_id IN (" . DB_Helper::buildList($fld_id) . ")";
        $params = array($fld_id);
        if (count($issues) > 0) {
            $stmt .= " AND icf_iss_id IN(" . DB_Helper::buildList($issues) . ")";
            $params = array_merge($params, $issues);
        }
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DbException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to remove the custom field options associated with
     * a given list of custom field IDs.
     *
     * @param   array $ids The list of custom field IDs
     * @return  boolean
     */
    public function removeOptionsByFields($ids)
    {
        $items = DB_Helper::buildList($ids);
        $stmt = "SELECT
                    cfo_id
                 FROM
                    {{%custom_field_option}}
                 WHERE
                    cfo_fld_id IN ($items)";
        try {
            $res = DB_Helper::getInstance()->getColumn($stmt, $ids);
        } catch (DbException $e) {
            return false;
        }

        self::removeOptions($ids, $res);

        return true;
    }

    /**
     * Method used to remove all custom field entries associated with
     * a given set of issues.
     *
     * @param   array $ids The array of issue IDs
     * @return  boolean
     */
    public static function removeByIssues($ids)
    {
        $items = DB_Helper::buildList($ids);
        $stmt = "DELETE FROM
                    {{%issue_custom_field}}
                 WHERE
                    icf_iss_id IN ($items)";
        try {
            DB_Helper::getInstance()->query($stmt, $ids);
        } catch (DbException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to remove all custom fields associated with
     * a given set of projects.
     *
     * @param   array $ids The array of project IDs
     * @return  boolean
     */
    public static function removeByProjects($ids)
    {
        $stmt = "DELETE FROM
                    {{%project_custom_field}}
                 WHERE
                    pcf_prj_id IN (" . DB_Helper::buildList($ids) . ")";
        try {
            DB_Helper::getInstance()->query($stmt, $ids);
        } catch (DbException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method to return the names of the fields which should be displayed on the list issues page.
     *
     * @param   integer $prj_id The ID of the project.
     * @return  array An array of custom field names.
     */
    public static function getFieldsToBeListed($prj_id)
    {
        $sql = "SELECT
                    fld_id,
                    fld_title
                FROM
                    {{%custom_field}},
                    {{%project_custom_field}}
                WHERE
                    fld_id = pcf_fld_id AND
                    pcf_prj_id = ? AND
                    fld_list_display = 1  AND
                    fld_min_role <= ?
                ORDER BY
                    fld_rank ASC";
        try {
            $res = DB_Helper::getInstance()->getPair($sql, array($prj_id, Auth::getCurrentRole()));
        } catch (DbException $e) {
           return array();
        }

        return $res;
    }

    /**
     * Returns the fld_id of the field with the specified title
     *
     * @param   string $title The title of the field
     * @return  integer The fld_id
     */
    public static function getIDByTitle($title)
    {
        $sql = "SELECT
                    fld_id
                FROM
                    {{%custom_field}}
                WHERE
                    fld_title = ?";
        try {
            $res = DB_Helper::getInstance()->getOne($sql, array($title));
        } catch (DbException $e) {
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
     * @param   integer $iss_id The ID of the issue
     * @param   integer $fld_id The ID of the field
     * @param   boolean $raw If the raw value should be displayed
     * @return mixed an array or string containing the value
     */
    public static function getDisplayValue($iss_id, $fld_id, $raw = false)
    {
        $sql = "SELECT
                    fld_id,
                    fld_type,
                    " . self::getDBValueFieldSQL() . " as value
                FROM
                    {{%custom_field}},
                    {{%issue_custom_field}}
                WHERE
                    fld_id=icf_fld_id AND
                    icf_iss_id=? AND
                    fld_id = ?";
        try {
            $res = DB_Helper::getInstance()->getAll($sql, array($iss_id, $fld_id));
        } catch (DbException $e) {
            return '';
        }

        $values = array();
        foreach ($res as $row) {
            if ($row["fld_type"] == "combo" || $row['fld_type'] == 'multiple') {
                if ($raw) {
                    $values[] = $row['value'];
                } else {
                    $values[] = self::getOptionValue($row["fld_id"], $row["value"]);
                }
            } else {
                $values[] = $row['value'];
            }
        }

        if ($raw) {
            return $values;
        }

        return join(', ', $values);
    }

    /**
     * Returns the current maximum rank of any custom fields.
     *
     * @return  integer The highest rank
     */
    public function getMaxRank()
    {
        $sql = "SELECT
                    max(fld_rank)
                FROM
                    {{%custom_field}}";

        return DB_Helper::getInstance()->getOne($sql);
    }

    /**
     * Changes the rank of a custom field
     *
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
                ((($i+1) == count($fields)) && ($direction == +1))) {
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
     * @param   integer $fld_id The ID of the field
     * @param   integer $rank The new rank for this field
     * @return  integer 1 if successful, -1 otherwise
     */
    public function setRank($fld_id, $rank)
    {
        $sql = "UPDATE
                    {{%custom_field}}
                SET
                    fld_rank = ?
                WHERE
                    fld_id = ?";
        try {
            DB_Helper::getInstance()->query($sql, array($rank, $fld_id));
        } catch (DbException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Returns the list of available custom field backends by listing the class
     * files in the backend directory.
     *
     * @return  array Associative array of filename => name
     */
    public static function getBackendList()
    {
        $list = array();
        $files = Misc::getFileList(APP_INC_PATH . '/custom_field');
        $files = array_merge($files, Misc::getFileList(APP_LOCAL_PATH. '/custom_field'));
        foreach ($files as $file) {
            // make sure we only list the backends
            if (preg_match('/^class\.(.*)\.php$/', $file)) {
                // display a prettyfied backend name in the admin section
                $list[$file] = self::getBackendName($file);
            }
        }

        return $list;
    }

    /**
     * Returns the 'pretty' name of the backend
     *
     * @param   string $backend The full backend file name
     * @return  string The pretty name of the backend.
     */
    public function getBackendName($backend)
    {
        preg_match('/^class\.(.*)\.php$/', $backend, $matches);

        return ucwords(str_replace('_', ' ', $matches[1]));
    }

    /**
     * Returns an instance of custom field backend class if it exists for the
     * specified field.
     *
     * @param   integer $fld_id The ID of the field
     * @return  mixed false if there is no backend or an instance of the backend class
     */
    public static function &getBackend($fld_id)
    {
        static $returns;

        // poor mans caching
        if (isset($returns[$fld_id])) {
            return $returns[$fld_id];
        }

        $sql = "SELECT
                    fld_backend
                FROM
                    {{%custom_field}}
                WHERE
                    fld_id = ?";
        try {
            $res = DB_Helper::getInstance()->getOne($sql, array($fld_id));
        } catch (DbException $e) {
            return false;
        }

        if (!empty($res)) {
            if (file_exists(APP_LOCAL_PATH . "/custom_field/$res")) {
                require_once APP_LOCAL_PATH . "/custom_field/$res";

            } elseif (file_exists(APP_INC_PATH . "/custom_field/$res")) {
                require_once APP_INC_PATH . "/custom_field/$res";

            } else {
                $returns[$fld_id] = false;

                return $returns[$fld_id];
            }

            $file_name_chunks = explode(".", $res);
            $class_name = $file_name_chunks[1] . "_Custom_Field_Backend";

            if (!class_exists($class_name)) {
                $returns[$fld_id] = false;

                return $returns[$fld_id];
            }

            $returns[$fld_id] = new $class_name();
        } else {
            $returns[$fld_id] = false;
        }

        return $returns[$fld_id];
    }

    /**
     * Searches a specified custom field for a string and returns any issues that match
     *
     * @param   integer $fld_id The ID of the custom field
     * @param   string  $search The string to search for
     * @return  array An array of issue IDs
     */
    public function getIssuesByString($fld_id, $search)
    {
        $sql = "SELECT
                    icf_iss_id
                FROM
                    {{%issue_custom_field}}
                WHERE
                    icf_fld_id = ? AND
                    (
                        icf_value LIKE ? OR
                        icf_value_integer LIKE ? OR
                        icf_value_date LIKE ?
                    )";
        try {
            $params = array(
                $fld_id,
                "%$search%",
                "%$search%",
                "%$search%",
            );
            $res = DB_Helper::getInstance()->getColumn($sql, $params);
        } catch (DbException $e) {
            return array();
        }

        return $res;
    }

    /**
     * Formats the return value
     *
     * @param   mixed   $value The value to format
     * @param   integer $fld_id The ID of the field
     * @param   integer $issue_id The ID of the issue
     * @return  mixed   the formatted value.
     */
    public function formatValue($value, $fld_id, $issue_id)
    {
        $backend = self::getBackend($fld_id);
        if ((is_object($backend)) && (method_exists($backend, 'formatValue'))) {
            return $backend->formatValue($value, $fld_id, $issue_id);
        } else {
            return Link_Filter::processText(Auth::getCurrentProject(), Misc::htmlentities($value));
        }
    }

    /**
     * This method inserts a blank value for all custom fields that do not already have a record.
     * It currently is not called by the main code, but is included to be called from workflow classes.
     *
     * @param   integer $issue_id The Issue ID
     */
    public function populateAllFields($issue_id)
    {
        $prj_id = Issue::getProjectID($issue_id);
        $fields = self::getListByIssue($prj_id, $issue_id, APP_SYSTEM_USER_ID);
        foreach ($fields as $field) {
            if (empty($field['value'])) {
                self::removeIssueAssociation($field['fld_id'], $issue_id);
                self::associateIssue($issue_id, $field['fld_id'], '');
            }
        }
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

    public function getDBValueFieldSQL()
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
     * @param   integer $fld_id
     * @return bool
     */
    public static function updateValuesForNewType($fld_id)
    {
        $details = self::getDetails($fld_id, true);
        $db_field_name = self::getDBValueFieldNameByType($details['fld_type']);

        $sql = "UPDATE
                    {{%issue_custom_field}}
                SET
                    ";
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
        $params = array($fld_id);
        try {
            DB_Helper::getInstance()->query($sql, $params);
        } catch (DbException $e) {
            return false;
        }

        return true;
    }
}
