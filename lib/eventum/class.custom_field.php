<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2012 Eventum Team.                              |
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
 * Class to handle the business logic related to the administration
 * of custom fields in the system.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

class Custom_Field
{
    /**
     * Method used to remove a group of custom field options.
     *
     * @access  public
     * @param   array $fld_id The list of custom field IDs
     * @param   array $fld_id The list of custom field option IDs
     * @return  boolean
     */
    function removeOptions($fld_id, $cfo_id)
    {
        $fld_id = Misc::escapeInteger($fld_id);
        $cfo_id = Misc::escapeInteger($cfo_id);
        if (!is_array($fld_id)) {
            $fld_id = array($fld_id);
        }
        if (!is_array($cfo_id)) {
            $cfo_id = array($cfo_id);
        }
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field_option
                 WHERE
                    cfo_id IN (" . implode(",", $cfo_id) . ")";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            // also remove any custom field option that is currently assigned to an issue
            // XXX: review this
            $stmt = "DELETE FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_custom_field
                     WHERE
                        icf_fld_id IN (" . implode(", ", $fld_id) . ") AND
                        icf_value IN (" . implode(", ", $cfo_id) . ")";
            DB_Helper::getInstance()->query($stmt);
            return true;
        }
    }


    /**
     * Method used to add possible options into a given custom field.
     *
     * @access  public
     * @param   integer $fld_id The custom field ID
     * @param   array $options The list of options that need to be added
     * @return  integer 1 if the insert worked, -1 otherwise
     */
    function addOptions($fld_id, $options)
    {
        $fld_id = Misc::escapeInteger($fld_id);
        if (!is_array($options)) {
            $options = array($options);
        }
        foreach ($options as $option) {
            $stmt = "INSERT INTO
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field_option
                     (
                        cfo_fld_id,
                        cfo_value
                     ) VALUES (
                        $fld_id,
                        '" . Misc::escapeString($option) . "'
                     )";
            $res = DB_Helper::getInstance()->query($stmt);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return -1;
            }
        }
        return 1;
    }


    /**
     * Method used to update an existing custom field option value.
     *
     * @access  public
     * @param   integer $cfo_id The custom field option ID
     * @param   string $cfo_value The custom field option value
     * @return  boolean
     */
    function updateOption($cfo_id, $cfo_value)
    {
        $cfo_id = Misc::escapeInteger($cfo_id);
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field_option
                 SET
                    cfo_value='" . Misc::escapeString($cfo_value) . "'
                 WHERE
                    cfo_id=" . $cfo_id;
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method used to update the values stored in the database.
     *
     * @access  public
     * @return  integer 1 if the update worked properly, any other value otherwise
     */
    function updateValues()
    {
        $prj_id = Auth::getCurrentProject();
        $issue_id = Misc::escapeInteger($_POST["issue_id"]);

        $old_values = self::getValuesByIssue($prj_id, $issue_id);


        if ((isset($_POST['custom_fields'])) && (count($_POST['custom_fields']) > 0)) {
            // get the types for all of the custom fields being submitted
            $stmt = "SELECT
                        fld_id,
                        fld_type
                     FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field
                     WHERE
                        fld_id IN (" . implode(", ", Misc::escapeInteger(@array_keys($_POST['custom_fields']))) . ")";
            $field_types = DB_Helper::getInstance()->getAssoc($stmt);

            // get the titles for all of the custom fields being submitted
            $stmt = "SELECT
                        fld_id,
                        fld_title
                     FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field
                     WHERE
                        fld_id IN (" . implode(", ", Misc::escapeInteger(@array_keys($_POST['custom_fields']))) . ")";
            $field_titles = DB_Helper::getInstance()->getAssoc($stmt);

            $updated_fields = array();
            foreach ($_POST["custom_fields"] as $fld_id => $value) {

                $fld_id = Misc::escapeInteger($fld_id);

                // security check
                $sql = "SELECT
                            fld_min_role
                        FROM
                            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field
                        WHERE
                            fld_id = $fld_id";
                $min_role = DB_Helper::getInstance()->getOne($sql);
                if ($min_role > Auth::getCurrentRole()) {
                    continue;
                }

                $option_types = array(
                    'multiple',
                    'combo'
                );
                if (!in_array($field_types[$fld_id], $option_types)) {
                    // check if this is a date field
                    if ($field_types[$fld_id] == 'integer') {
                        $value = Misc::escapeInteger($value);
                    }
                    $fld_db_name = self::getDBValueFieldNameByType($field_types[$fld_id]);

                    // first check if there is actually a record for this field for the issue
                    $stmt = "SELECT
                                icf_id,
                                $fld_db_name as value
                             FROM
                                " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_custom_field
                             WHERE
                                icf_iss_id=" . $issue_id . " AND
                                icf_fld_id=$fld_id";
                    $res = DB_Helper::getInstance()->getRow($stmt, DB_FETCHMODE_ASSOC);
                    if (PEAR::isError($res)) {
                        Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                        return -1;
                    }
                    $icf_id = $res['icf_id'];
                    $old_value = $res['value'];

                    if ($old_value == $value) {
                        continue;
                    }

                    if (empty($value)) {
                        $value = 'NULL';
                    } else {
                        $value = "'" . Misc::escapeString($value) . "'";
                    }

                    if (empty($icf_id)) {
                        // record doesn't exist, insert new record
                        $stmt = "INSERT INTO
                                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_custom_field
                                 (
                                    icf_iss_id,
                                    icf_fld_id,
                                    $fld_db_name
                                 ) VALUES (
                                    " . $issue_id . ",
                                    $fld_id,
                                    $value
                                 )";
                        $res = DB_Helper::getInstance()->query($stmt);
                        if (PEAR::isError($res)) {
                            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                            return -1;
                        }
                    } else {
                        // record exists, update it
                        $stmt = "UPDATE
                                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_custom_field
                                 SET
                                    $fld_db_name=$value
                                 WHERE
                                    icf_id=$icf_id";
                        $res = DB_Helper::getInstance()->query($stmt);
                        if (PEAR::isError($res)) {
                            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                            return -1;
                        }
                    }
                    if ($field_types[$fld_id] == 'textarea') {
                        $updated_fields[$field_titles[$fld_id]] = '';
                    } else {
                        $updated_fields[$field_titles[$fld_id]] = History::formatChanges($old_value, $value);
                    }
                } else {
                    $old_value = self::getDisplayValue($_POST['issue_id'], $fld_id, true);

                    if (!is_array($old_value)) {
                        $old_value = array($old_value);
                    }
                    if (!is_array($value)) {
                        $value = array($value);
                    }
                    if ((count(array_diff($old_value, $value)) > 0) || (count(array_diff($value, $old_value)) > 0)) {

                        $old_display_value = self::getDisplayValue($_POST['issue_id'], $fld_id);
                        // need to remove all associated options from issue_custom_field and then
                        // add the selected options coming from the form
                        self::removeIssueAssociation($fld_id, $_POST["issue_id"]);
                        if (@count($value) > 0) {
                            self::associateIssue($_POST["issue_id"], $fld_id, $value);
                        }
                        $new_display_value = self::getDisplayValue($_POST['issue_id'], $fld_id);
                        $updated_fields[$field_titles[$fld_id]] = History::formatChanges($old_display_value, $new_display_value);
                    }
                }
            }

            Workflow::handleCustomFieldsUpdated($prj_id, $issue_id, $old_values, self::getValuesByIssue($prj_id, $issue_id));
            Issue::markAsUpdated($_POST["issue_id"]);
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
                History::add($_POST["issue_id"], Auth::getUserID(), History::getTypeID('custom_field_updated'), ev_gettext('Custom field updated (%1$s) by %2$s', $changes, User::getFullName(Auth::getUserID())));
            }
        }
        return 1;
    }


    /**
     * Method used to associate a custom field value to a given
     * issue ID.
     *
     * @access  public
     * @param   integer $iss_id The issue ID
     * @param   integer $fld_id The custom field ID
     * @param   string  $value The custom field value
     * @return  boolean Whether the association worked or not
     */
    function associateIssue($iss_id, $fld_id, $value)
    {
        // check if this is a date field
        $fld_details = self::getDetails($fld_id);
        if (!is_array($value)) {
            $value = array($value);
        }
        foreach ($value as $item) {
            if ($fld_details['fld_type'] == 'integer') {
                $item = Misc::escapeInteger($item);
            } elseif ((in_array($fld_details['fld_type'], array('combo', 'multiple')) && ($item == -1))) {
                continue;
            } else {
                $item = "'" . Misc::escapeString($item) . "'";
            }
            $stmt = "INSERT INTO
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_custom_field
                     (
                        icf_iss_id,
                        icf_fld_id,
                        " . self::getDBValueFieldNameByType($fld_details['fld_type']) . "
                     ) VALUES (
                        " . Misc::escapeInteger($iss_id) . ",
                        " . Misc::escapeInteger($fld_id) . ",
                        $item
                     )";
            $res = DB_Helper::getInstance()->query($stmt);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return false;
            }
        }
        return true;
    }


    /**
     * Method used to get the list of custom fields associated with
     * a given project.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   string $form_type The type of the form
     * @param   string $fld_type The type of field (optional)
     * @return  array The list of custom fields
     */
    function getListByProject($prj_id, $form_type, $fld_type = false)
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
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_custom_field
                 WHERE
                    pcf_fld_id=fld_id AND
                    pcf_prj_id=" . Misc::escapeInteger($prj_id);
        if ($form_type != 'anonymous_form') {
            $stmt .= " AND
                    fld_min_role <= " . Auth::getCurrentRole();
        }
        if ($form_type != '') {
            $stmt .= " AND\nfld_" .  Misc::escapeString($form_type) . "=1";
        }
        if ($fld_type != '') {
            $stmt .= " AND\nfld_type='" .  Misc::escapeString($fld_type) . "'";
        }
        $stmt .= "
                 ORDER BY
                    fld_rank ASC";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            if (count($res) == 0) {
                return array();
            } else {
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
        }
    }


    /**
     * Method used to get the custom field option value.
     *
     * @access  public
     * @param   integer $fld_id The custom field ID
     * @param   integer $value The custom field option ID
     * @return  string The custom field option value
     */
    function getOptionValue($fld_id, $value)
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
            } else {
                $values = $backend->getList($fld_id, false);
                $returns[$fld_id . $value] = @$values[$value];
                return @$values[$value];
            }
        } else {
            $stmt = "SELECT
                        cfo_value
                     FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field_option
                     WHERE
                        cfo_fld_id=" .  Misc::escapeInteger($fld_id) . " AND
                        cfo_id=" .  Misc::escapeInteger($value);
            $res = DB_Helper::getInstance()->getOne($stmt);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return "";
            } else {
                if ($res == NULL) {
                    $returns[$fld_id . $value] = '';
                    return "";
                } else {
                    $returns[$fld_id . $value] = $res;
                    return $res;
                }
            }
        }
    }


    /**
     * Method used to get the custom field key based on the value.
     *
     * @access  public
     * @param   integer $fld_id The custom field ID
     * @param   integer $value The custom field option ID
     * @return  string The custom field option value
     */
    function getOptionKey($fld_id, $value)
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
        } else {
            $stmt = "SELECT
                        cfo_id
                     FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field_option
                     WHERE
                        cfo_fld_id=" .  Misc::escapeInteger($fld_id) . " AND
                        cfo_value='" .  Misc::escapeString($value) . "'";
            $res = DB_Helper::getInstance()->getOne($stmt);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return "";
            } else {
                if ($res == NULL) {
                    $returns[$fld_id . $value] = '';
                    return "";
                } else {
                    $returns[$fld_id . $value] = $res;
                    return $res;
                }
            }
        }
    }


    /**
     * Method used to get the list of custom fields and custom field
     * values associated with a given issue ID. If usr_id is false method
     * defaults to current user.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $iss_id The issue ID
     * @param   integer $usr_id The ID of the user who is going to be viewing this list.
     * @param   mixed   $form_type The name of the form this is for or if this is an array the ids of the fields to return
     * @return  array The list of custom fields
     */
    function getListByIssue($prj_id, $iss_id, $usr_id = false, $form_type = false)
    {
        if ($usr_id == false) {
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
                    fld_min_role
                 FROM
                    (
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_custom_field
                    )
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_custom_field
                 ON
                    pcf_fld_id=icf_fld_id AND
                    icf_iss_id=" .  Misc::escapeInteger($iss_id) . "
                 WHERE
                    pcf_fld_id=fld_id AND
                    pcf_prj_id=" .  Misc::escapeInteger($prj_id) . " AND
                    fld_min_role <= " . $usr_role;
        if ($form_type != false) {
            if (is_array($form_type)) {
                $stmt .= " AND
                    fld_id IN(" . join($form_type) . ")";
            } else {
                $stmt .= " AND
                    fld_" .  Misc::escapeString($form_type) . "=1";
            }
        }
        $stmt .= "
                 ORDER BY
                    fld_rank ASC";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            if (count($res) == 0) {
                return array();
            } else {
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

                    } elseif ($row['fld_type'] == 'multiple') {
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
                        if (!in_array($original_value, $fields[$found_index]['field_options'])) {
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
        }
    }


    /**
     * Returns an array of fields and values for a specific issue
     *
     * @access  public
     * @param   integer $prj_id The ID of the project
     * @param   integer $iss_id The ID of the issue to return values for
     * @return  array An array containging fld_id => value
     */
    function getValuesByIssue($prj_id, $iss_id)
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
     * @access  public
     * @return  boolean
     */
    function remove()
    {
        $items = @implode(", ", Misc::escapeInteger($_POST["items"]));
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field
                 WHERE
                    fld_id IN ($items)";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            $stmt = "DELETE FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_custom_field
                     WHERE
                        pcf_fld_id IN ($items)";
            $res = DB_Helper::getInstance()->query($stmt);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return false;
            } else {
                $stmt = "DELETE FROM
                            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_custom_field
                         WHERE
                            icf_fld_id IN ($items)";
                $res = DB_Helper::getInstance()->query($stmt);
                if (PEAR::isError($res)) {
                    Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                    return false;
                } else {
                    $stmt = "DELETE FROM
                                " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field_option
                             WHERE
                                cfo_fld_id IN ($items)";
                    $res = DB_Helper::getInstance()->query($stmt);
                    if (PEAR::isError($res)) {
                        Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                        return false;
                    } else {
                        return true;
                    }
                }
            }
        }
    }


    /**
     * Method used to add a new custom field to the system.
     *
     * @access  public
     * @return  integer 1 if the insert worked, -1 otherwise
     */
    function insert()
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
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field
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
                    '" . Misc::escapeString($_POST["title"]) . "',
                    '" . Misc::escapeString($_POST["description"]) . "',
                    '" . Misc::escapeString($_POST["field_type"]) . "',
                    " . Misc::escapeInteger($_POST["report_form"]) . ",
                    " . Misc::escapeInteger($_POST["report_form_required"]) . ",
                    " . Misc::escapeInteger($_POST["anon_form"]) . ",
                    " . Misc::escapeInteger($_POST["anon_form_required"]) . ",
                    " . Misc::escapeInteger($_POST["close_form"]) . ",
                    " . Misc::escapeInteger($_POST["close_form_required"]) . ",
                    " . Misc::escapeInteger($_POST["list_display"]) . ",
                    " . Misc::escapeInteger($_POST["min_role"]) . ",
                    " . Misc::escapeInteger($_POST['rank']) . ",
                    '" . Misc::escapeString(@$_POST['custom_field_backend']) . "'
                 )";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $new_id = DB_Helper::get_last_insert_id();
            if (($_POST["field_type"] == 'combo') || ($_POST["field_type"] == 'multiple')) {
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
    }


    /**
     * Method used to associate a custom field to a project.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $fld_id The custom field ID
     * @return  boolean
     */
    function associateProject($prj_id, $fld_id)
    {
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_custom_field
                 (
                    pcf_prj_id,
                    pcf_fld_id
                 ) VALUES (
                    " . Misc::escapeInteger($prj_id) . ",
                    " . Misc::escapeInteger($fld_id) . "
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
     * Method used to get the list of custom fields available in the
     * system.
     *
     * @access  public
     * @return  array The list of custom fields
     */
    function getList()
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field
                 ORDER BY
                    fld_rank ASC";
        $res = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
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
    }


    /**
     * Method used to get the list of associated projects with a given
     * custom field ID.
     *
     * @access  public
     * @param   integer $fld_id The project ID
     * @return  array The list of associated projects
     */
    function getAssociatedProjects($fld_id)
    {
        $stmt = "SELECT
                    prj_id,
                    prj_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_custom_field
                 WHERE
                    pcf_prj_id=prj_id AND
                    pcf_fld_id=" . Misc::escapeInteger($fld_id) . "
                 ORDER BY
                    prj_title ASC";
        $res = DB_Helper::getInstance()->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the details of a specific custom field.
     *
     * @access  public
     * @param   integer $fld_id The custom field ID
     * @param   boolean $force_refresh If the details must be loaded again from the database
     * @return  array The custom field details
     */
    function getDetails($fld_id, $force_refresh = false)
    {
        static $returns;

        if ((isset($returns[$fld_id])) && ($force_refresh == false)) {
            return $returns[$fld_id];
        }

        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field
                 WHERE
                    fld_id=" . Misc::escapeInteger($fld_id);
        $res = DB_Helper::getInstance()->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            $res["projects"] = @array_keys(self::getAssociatedProjects($fld_id));
            $t = array();
            $options = self::getOptions($fld_id);
            foreach ($options as $cfo_id => $cfo_value) {
                $res["field_options"]["existing:" . $cfo_id . ":" . $cfo_value] = $cfo_value;
            }
            $returns[$fld_id] = $res;
            return $res;
        }
    }


    /**
     * Method used to get the list of custom field options associated
     * with a given custom field ID.
     *
     * @access  public
     * @param   integer $fld_id The custom field ID
     * @param   array $ids An array of ids to return values for.
     * @param   integer $issue_id The ID of the issue
     * @return  array The list of custom field options
     */
    function getOptions($fld_id, $ids = false, $issue_id = false, $form_type = false)
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
        } else {
            $stmt = "SELECT
                        cfo_id,
                        cfo_value
                     FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field_option
                     WHERE
                        cfo_fld_id=" . Misc::escapeInteger($fld_id);
            if ($ids != false) {
                $stmt .= " AND
                        cfo_id IN(" . join(', ', Misc::escapeInteger($ids)) . ")";
            }
            $stmt .= "
                     ORDER BY
                        cfo_id ASC";
            $res = DB_Helper::getInstance()->getAssoc($stmt);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return "";
            } else {
                asort($res);
                $returns[$return_key] = $res;
                return $res;
            }
        }
    }


    /**
     * Method used to parse the special format used in the combo boxes
     * in the administration section of the system, in order to be
     * used as a way to flag the system for whether the custom field
     * option is a new one or one that should be updated.
     *
     * @access  private
     * @param   string $value The custom field option format string
     * @return  array Parameters used by the update/insert methods
     */
    function parseParameters($value)
    {
        if (substr($value, 0, 4) == 'new:') {
            return array(
                "type"  => "new",
                "value" => substr($value, 4)
            );
        } else {
            $value = substr($value, strlen("existing:"));
            return array(
                "type"  => "existing",
                "id"    => substr($value, 0, strpos($value, ":")),
                "value" => substr($value, strpos($value, ":")+1)
            );
        }
    }


    /**
     * Method used to update the details for a specific custom field.
     *
     * @access  public
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function update()
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
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field
                 SET
                    fld_title='" . Misc::escapeString($_POST["title"]) . "',
                    fld_description='" . Misc::escapeString($_POST["description"]) . "',
                    fld_type='" . Misc::escapeString($_POST["field_type"]) . "',
                    fld_report_form=" . Misc::escapeInteger($_POST["report_form"]) . ",
                    fld_report_form_required=" . Misc::escapeInteger($_POST["report_form_required"]) . ",
                    fld_anonymous_form=" . Misc::escapeInteger($_POST["anon_form"]) . ",
                    fld_anonymous_form_required=" . Misc::escapeInteger($_POST["anon_form_required"]) . ",
                    fld_close_form=" . Misc::escapeInteger($_POST["close_form"]) . ",
                    fld_close_form_required=" . Misc::escapeInteger($_POST["close_form_required"]) . ",
                    fld_list_display=" . Misc::escapeInteger($_POST["list_display"]) . ",
                    fld_min_role=" . Misc::escapeInteger($_POST['min_role']) . ",
                    fld_rank = " . Misc::escapeInteger($_POST['rank']) . ",
                    fld_backend = '" . Misc::escapeString(@$_POST['custom_field_backend']) . "'
                 WHERE
                    fld_id=" . Misc::escapeInteger($_POST["id"]);
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            // if the current custom field is a combo box, get all of the current options
            if (in_array($_POST["field_type"], array('combo', 'multiple'))) {
                $stmt = "SELECT
                            cfo_id
                         FROM
                            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field_option
                         WHERE
                            cfo_fld_id=" . Misc::escapeInteger($_POST["id"]);
                $current_options = DB_Helper::getInstance()->getCol($stmt);
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
            if (($_POST["field_type"] == "combo") || ($_POST["field_type"] == "multiple")) {
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
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_custom_field
                     WHERE
                        pcf_fld_id=" . Misc::escapeInteger($_POST["id"]);
            $res = DB_Helper::getInstance()->query($stmt);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return -1;
            } else {
                foreach ($_POST["projects"] as $prj_id) {
                    self::associateProject($prj_id, $_POST["id"]);
                }
            }
            return 1;
        }
    }


    /**
     * Method used to get the list of custom fields associated with a
     * given project.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @return  array The list of custom fields
     */
    function getFieldsByProject($prj_id)
    {
        $stmt = "SELECT
                    pcf_fld_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_custom_field
                 WHERE
                    pcf_prj_id=" . Misc::escapeInteger($prj_id);
        $res = DB_Helper::getInstance()->getCol($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            return $res;
        }
    }


    /**
     * Method used to remove the issue associations related to a given
     * custom field ID.
     *
     * @access  public
     * @param   integer $fld_id The custom field ID
     * @param   integer $issue_id The issue ID (not required)
     * @param   integer $prj_id The project ID (not required)
     * @return  boolean
     */
    function removeIssueAssociation($fld_id, $issue_id = FALSE, $prj_id = false)
    {
        if (is_array($fld_id)) {
            $fld_id = implode(", ",  Misc::escapeInteger($fld_id));
        }
        $issues = array();
        if ($issue_id != false) {
            $issues = array($issue_id);
        } elseif ($prj_id != false) {
            $sql = "SELECT
                        iss_id
                    FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                    WHERE
                        iss_prj_id = " . Misc::escapeInteger($prj_id);
            $res = DB_Helper::getInstance()->getCol($sql);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return false;
            } else {
                $issues = $res;
            }
        }
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_custom_field
                 WHERE
                    icf_fld_id IN (" . $fld_id . ")";
        if (count($issues) > 0) {
            $stmt .= " AND icf_iss_id IN(" . join(', ', Misc::escapeInteger($issues)) . ")";
        }
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method used to remove the custom field options associated with
     * a given list of custom field IDs.
     *
     * @access  public
     * @param   array $ids The list of custom field IDs
     * @return  boolean
     */
    function removeOptionsByFields($ids)
    {
        if (!is_array($ids)) {
            $ids = array($ids);
        }
        $items = implode(", ", Misc::escapeInteger($ids));
        $stmt = "SELECT
                    cfo_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field_option
                 WHERE
                    cfo_fld_id IN ($items)";
        $res = DB_Helper::getInstance()->getCol($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            self::removeOptions($ids, $res);
            return true;
        }
    }


    /**
     * Method used to remove all custom field entries associated with
     * a given set of issues.
     *
     * @access  public
     * @param   array $ids The array of issue IDs
     * @return  boolean
     */
    function removeByIssues($ids)
    {
        $items = implode(", ", Misc::escapeInteger($ids));
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_custom_field
                 WHERE
                    icf_iss_id IN ($items)";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
    }


    /**
     * Method used to remove all custom fields associated with
     * a given set of projects.
     *
     * @access  public
     * @param   array $ids The array of project IDs
     * @return  boolean
     */
    function removeByProjects($ids)
    {
        $items = implode(", ", Misc::escapeInteger($ids));
        $stmt = "DELETE FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_custom_field
                 WHERE
                    pcf_prj_id IN ($items)";
        $res = DB_Helper::getInstance()->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            return true;
        }
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
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_custom_field
                WHERE
                    fld_id = pcf_fld_id AND
                    pcf_prj_id = " . Misc::escapeInteger($prj_id) . " AND
                    fld_list_display = 1  AND
                    fld_min_role <= " . Auth::getCurrentRole() . "
                ORDER BY
                    fld_rank ASC";
        $res = DB_Helper::getInstance()->getAssoc($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        }

        return $res;
    }


    /**
     * Returns the fld_id of the field with the specified title
     *
     * @access  public
     * @param   string $title The title of the field
     * @return  integer The fld_id
     */
    function getIDByTitle($title)
    {
        $sql = "SELECT
                    fld_id
                FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field
                WHERE
                    fld_title = '" . Misc::escapeString($title) . "'";
        $res = DB_Helper::getInstance()->getOne($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return 0;
        } else {
            if (empty($res)) {
                return 0;
            } else {
                return $res;
            }
        }
    }


    /**
     * Returns the value for the specified field
     *
     * @access  public
     * @param   integer $iss_id The ID of the issue
     * @param   integer $fld_id The ID of the field
     * @param   boolean $raw If the raw value should be displayed
     * @param   mixed an array or string containing the value
     */
    function getDisplayValue($iss_id, $fld_id, $raw = false)
    {
        $sql = "SELECT
                    fld_id,
                    fld_type,
                    " . self::getDBValueFieldSQL() . " as value
                FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_custom_field
                WHERE
                    fld_id=icf_fld_id AND
                    icf_iss_id=" .  Misc::escapeInteger($iss_id) . " AND
                    fld_id = " . Misc::escapeInteger($fld_id);
        $res = DB_Helper::getInstance()->getAll($sql, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return '';
        } else {
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
            } else {
                return join(', ', $values);
            }
        }
    }


    /**
     * Returns the current maximum rank of any custom fields.
     *
     * @access  public
     * @return  integer The highest rank
     */
    function getMaxRank()
    {
        $sql = "SELECT
                    max(fld_rank)
                FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field";
        return DB_Helper::getInstance()->getOne($sql);
    }


    /**
     * Changes the rank of a custom field
     *
     * @access  public
     */
    function changeRank()
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
     * @access  public
     * @param   integer $fld_id The ID of the field
     * @param   integer $rank The new rank for this field
     * @return  integer 1 if successful, -1 otherwise
     */
    function setRank($fld_id, $rank)
    {
        $sql = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field
                SET
                    fld_rank = $rank
                WHERE
                    fld_id = $fld_id";
        $res = DB_Helper::getInstance()->query($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        }
        return 1;
    }


    /**
     * Returns the list of available custom field backends by listing the class
     * files in the backend directory.
     *
     * @access  public
     * @return  array Associative array of filename => name
     */
    function getBackendList()
    {
        $list = array();
        $files = Misc::getFileList(APP_INC_PATH . '/custom_field');
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
     * @access  public
     * @param   string $backend The full backend file name
     * @return  string The pretty name of the backend.
     */
    function getBackendName($backend)
    {
        preg_match('/^class\.(.*)\.php$/', $backend, $matches);
        return ucwords(str_replace('_', ' ', $matches[1]));
    }


    /**
     * Returns an instance of custom field backend class if it exists for the
     * specified field.
     *
     * @access  public
     * @param   integer $fld_id The ID of the field
     * @return  mixed false if there is no backend or an instance of the backend class
     */
    function &getBackend($fld_id)
    {
        static $returns;

        // poor mans caching
        if (isset($returns[$fld_id])) {
            return $returns[$fld_id];
        }

        $sql = "SELECT
                    fld_backend
                FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field
                WHERE
                    fld_id = " . Misc::escapeInteger($fld_id);
        $res = DB_Helper::getInstance()->getOne($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } elseif (!empty($res)) {
            $file_name = APP_INC_PATH . "/custom_field/$res";
            if (!file_exists($file_name)) {
                $returns[$fld_id] = false;
                return $returns[$fld_id];
            }
            require_once $file_name;

            $file_name_chunks = explode(".", $res);
            $class_name = $file_name_chunks[1] . "_Custom_Field_Backend";

            if (!class_exists($class_name)) {
                $returns[$fld_id] = false;
                return $returns[$fld_id];
            }

            $returns[$fld_id] = new $class_name;
        } else {
            $returns[$fld_id] = false;
        }
        return $returns[$fld_id];
    }


    /**
     * Searches a specified custom field for a string and returns any issues that match
     *
     * @access  public
     * @param   integer $fld_id The ID of the custom field
     * @param   string  $search The string to search for
     * @return  array An array of issue IDs
     */
    function getIssuesByString($fld_id, $search)
    {
        $sql = "SELECT
                    icf_iss_id
                FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_custom_field
                WHERE
                    icf_fld_id = " . Misc::escapeInteger($fld_id) . " AND
                    (
                        icf_value LIKE '%" . Misc::escapeString($search) . "%' OR
                        icf_value_integer LIKE '%" . Misc::escapeInteger($search) . "%' OR
                        icf_value_date LIKE '%" . Misc::escapeString($search) . "%'
                    )";
        $res = DB_Helper::getInstance()->getCol($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        }
        return $res;
    }


    /**
     * Formats the return value
     *
     * @access  public
     * @param   mixed   $value The value to format
     * @param   integer $fld_id The ID of the field
     * @param   integer $issue_id The ID of the issue
     * @return  mixed   the formatted value.
     */
    function formatValue($value, $fld_id, $issue_id)
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
     * @access  public
     * @param   integer $issue_id The Issue ID
     */
    function populateAllFields($issue_id)
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
    function getDBValueFieldNameByType($type)
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


    function getDBValueFieldSQL()
    {
        return "(IF(fld_type = 'date', icf_value_date, IF(fld_type = 'integer', icf_value_integer, icf_value)))";
    }


    /**
     * Analyzes the contents of the issue_custom_field and updates
     * contents based on the fld_type.
     *
     * @param   integer $fld_id
     */
    function updateValuesForNewType($fld_id)
    {
        $details = self::getDetails($fld_id, true);
        $db_field_name = self::getDBValueFieldNameByType($details['fld_type']);


        $sql = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_custom_field
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
                    icf_fld_id = " . Misc::escapeInteger($fld_id);
        $res = DB_Helper::getInstance()->query($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        }
        return true;
    }
}
