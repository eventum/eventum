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
// @(#) $Id: s.class.custom_field.php 1.28 03/12/31 17:29:00-00:00 jpradomaia $
//

require_once(APP_INC_PATH . "class.error_handler.php");
require_once(APP_INC_PATH . "class.misc.php");
require_once(APP_INC_PATH . "class.issue.php");
require_once(APP_INC_PATH . "class.user.php");
require_once(APP_INC_PATH . "class.auth.php");
require_once(APP_INC_PATH . "class.history.php");

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
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
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
            $GLOBALS["db_api"]->dbh->query($stmt);
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
            $res = $GLOBALS["db_api"]->dbh->query($stmt);
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
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
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

        $old_values = Custom_Field::getValuesByIssue($prj_id, $issue_id);

        // get the types for all of the custom fields being submitted
        $stmt = "SELECT
                    fld_id,
                    fld_type
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field
                 WHERE
                    fld_id IN (" . implode(", ", Misc::escapeInteger(@array_keys($_POST['custom_fields']))) . ")";
        $field_types = $GLOBALS["db_api"]->dbh->getAssoc($stmt);

        // get the titles for all of the custom fields being submitted
        $stmt = "SELECT
                    fld_id,
                    fld_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field
                 WHERE
                    fld_id IN (" . implode(", ", Misc::escapeInteger(@array_keys($_POST['custom_fields']))) . ")";
        $field_titles = $GLOBALS["db_api"]->dbh->getAssoc($stmt);

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
            $min_role = $GLOBALS["db_api"]->dbh->getOne($sql);
            if ($min_role > Auth::getCurrentRole()) {
                continue;
            }

            $option_types = array(
                'multiple',
                'combo'
            );
            if (!in_array($field_types[$fld_id], $option_types)) {
                // check if this is a date field
                if ($field_types[$fld_id] == 'date') {
                    $value = $value['Year'] . "-" . $value['Month'] . "-" . $value['Day'];
                    if ($value == '--') {
                        $value = '';
                    }
                }

                // first check if there is actually a record for this field for the issue
                $stmt = "SELECT
                            icf_id,
                            icf_value
                         FROM
                            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_custom_field
                         WHERE
                            icf_iss_id=" . $issue_id . " AND
                            icf_fld_id=$fld_id";
                $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
                if (PEAR::isError($res)) {
                    Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                    return -1;
                }
                $icf_id = $res['icf_id'];
                $icf_value = $res['icf_value'];

                if ($icf_value == $value) {
                    continue;
                }

                if (empty($icf_id)) {
                    // record doesn't exist, insert new record
                    $stmt = "INSERT INTO
                                " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_custom_field
                             (
                                icf_iss_id,
                                icf_fld_id,
                                icf_value
                             ) VALUES (
                                " . $issue_id . ",
                                $fld_id,
                                '" . Misc::escapeString($value) . "'
                             )";
                    $res = $GLOBALS["db_api"]->dbh->query($stmt);
                    if (PEAR::isError($res)) {
                        Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                        return -1;
                    }
                } else {
                    // record exists, update it
                    $stmt = "UPDATE
                                " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_custom_field
                             SET
                                icf_value='" . Misc::escapeString($value) . "'
                             WHERE
                                icf_id=$icf_id";
                    $res = $GLOBALS["db_api"]->dbh->query($stmt);
                    if (PEAR::isError($res)) {
                        Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                        return -1;
                    }
                }
                if ($field_types[$fld_id] == 'textarea') {
                    $updated_fields[$field_titles[$fld_id]] = '';
                } else {
                    $updated_fields[$field_titles[$fld_id]] = History::formatChanges($icf_value, $value);
                }
            } else {
                $old_value = Custom_Field::getDisplayValue($_POST['issue_id'], $fld_id, true);

                if (!is_array($old_value)) {
                    $old_value = array($old_value);
                }
                if (!is_array($value)) {
                    $value = array($value);
                }
                if ((count(array_diff($old_value, $value)) > 0) || (count(array_diff($value, $old_value)) > 0)) {

                    $old_display_value = Custom_Field::getDisplayValue($_POST['issue_id'], $fld_id);
                    // need to remove all associated options from issue_custom_field and then
                    // add the selected options coming from the form
                    Custom_Field::removeIssueAssociation($fld_id, $_POST["issue_id"]);
                    if (@count($value) > 0) {
                        Custom_Field::associateIssue($_POST["issue_id"], $fld_id, $value);
                    }
                    $new_display_value = Custom_Field::getDisplayValue($_POST['issue_id'], $fld_id);
                    $updated_fields[$field_titles[$fld_id]] = History::formatChanges($old_display_value, $new_display_value);
                }
            }
        }

        Workflow::handleCustomFieldsUpdated($prj_id, $issue_id, $old_values, Custom_Field::getValuesByIssue($prj_id, $issue_id));
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
        $fld_details = Custom_Field::getDetails($fld_id);
        if ($fld_details['fld_type'] == 'date') {
            $value= $value['Year'] . "-" . $value['Month'] . "-" . $value['Day'];
        }
        if (!is_array($value)) {
            $value = array($value);
        }
        foreach ($value as $item) {
            $stmt = "INSERT INTO
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_custom_field
                     (
                        icf_iss_id,
                        icf_fld_id,
                        icf_value
                     ) VALUES (
                        " . Misc::escapeInteger($iss_id) . ",
                        " . Misc::escapeInteger($fld_id) . ",
                        '" . Misc::escapeString($item) . "'
                     )";
            $GLOBALS["db_api"]->dbh->query($stmt);
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
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            if (count($res) == 0) {
                return "";
            } else {
                for ($i = 0; $i < count($res); $i++) {
                    // check if this has a dynamic field custom backend
                    $backend = Custom_Field::getBackend($res[$i]['fld_id']);
                    if ((is_object($backend)) && (is_subclass_of($backend, "Dynamic_Custom_Field_Backend"))) {
                        $res[$i]['dynamic_options'] = $backend->getStructuredData();
                        $res[$i]['controlling_field_id'] = $backend->getControllingCustomFieldID();
                        $res[$i]['controlling_field_name'] = $backend->getControllingCustomFieldName();
                        $res[$i]['hide_when_no_options'] = $backend->hideWhenNoOptions();
                    }
                    $res[$i]["field_options"] = Custom_Field::getOptions($res[$i]["fld_id"]);
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

        $backend = Custom_Field::getBackend($fld_id);
        if ((is_object($backend)) && (method_exists($backend, 'getList'))) {
            $values = $backend->getList($fld_id);
            $returns[$fld_id . $value] = @$values[$value];
            return @$values[$value];
        } else {
            $stmt = "SELECT
                        cfo_value
                     FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field_option
                     WHERE
                        cfo_fld_id=" .  Misc::escapeInteger($fld_id) . " AND
                        cfo_id=" .  Misc::escapeInteger($value);
            $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
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

        $backend = Custom_Field::getBackend($fld_id);
        if ((is_object($backend)) && (method_exists($backend, 'getList'))) {
            $values = $backend->getList($fld_id);
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
            $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
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
     * @return  array The list of custom fields
     */
    function getListByIssue($prj_id, $iss_id, $usr_id = false)
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
                    icf_value,
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
                    fld_min_role <= " . $usr_role . "
                 ORDER BY
                    fld_rank ASC";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            if (count($res) == 0) {
                return array();
            } else {
                $fields = array();
                for ($i = 0; $i < count($res); $i++) {
                    if (($res[$i]['fld_type'] == 'text') || ($res[$i]['fld_type'] == 'textarea') || ($res[$i]['fld_type'] == 'date')) {
                        $fields[] = $res[$i];
                    } elseif ($res[$i]["fld_type"] == "combo") {
                        $res[$i]["selected_cfo_id"] = $res[$i]["icf_value"];
                        $res[$i]["icf_value"] = Custom_Field::getOptionValue($res[$i]["fld_id"], $res[$i]["icf_value"]);
                        $res[$i]["field_options"] = Custom_Field::getOptions($res[$i]["fld_id"]);
                        $fields[] = $res[$i];
                    } elseif ($res[$i]['fld_type'] == 'multiple') {
                        // check whether this field is already in the array
                        $found = 0;
                        for ($y = 0; $y < count($fields); $y++) {
                            if ($fields[$y]['fld_id'] == $res[$i]['fld_id']) {
                                $found = 1;
                                $found_index = $y;
                            }
                        }
                        if (!$found) {
                            $res[$i]["selected_cfo_id"] = array($res[$i]["icf_value"]);
                            $res[$i]["icf_value"] = Custom_Field::getOptionValue($res[$i]["fld_id"], $res[$i]["icf_value"]);
                            $res[$i]["field_options"] = Custom_Field::getOptions($res[$i]["fld_id"]);
                            $fields[] = $res[$i];
                        } else {
                            $fields[$found_index]['icf_value'] .= ', ' . Custom_Field::getOptionValue($res[$i]["fld_id"], $res[$i]["icf_value"]);
                            $fields[$found_index]['selected_cfo_id'][] = $res[$i]["icf_value"];
                        }
                    }
                }
                foreach ($fields as $key => $field) {
                    $backend = Custom_Field::getBackend($field['fld_id']);
                    if ((is_object($backend)) && (is_subclass_of($backend, "Dynamic_Custom_Field_Backend"))) {
                        $fields[$key]['dynamic_options'] = $backend->getStructuredData();
                        $fields[$key]['controlling_field_id'] = $backend->getControllingCustomFieldID();
                        $fields[$key]['controlling_field_name'] = $backend->getControllingCustomFieldName();
                        $fields[$key]['hide_when_no_options'] = $backend->hideWhenNoOptions();
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
        $list = Custom_Field::getListByIssue($prj_id, $iss_id);
        foreach ($list as $field) {
            if (in_array($field['fld_type'], array('text', 'textarea'))) {
                $values[$field['fld_id']] = $field['icf_value'];
            } elseif ($field['fld_type'] == 'combo') {
                $values[$field['fld_id']] = array(
                    $field['selected_cfo_id'] => $field['icf_value']
                );
            } elseif ($field['fld_type'] == 'multiple') {
                $selected = $field['selected_cfo_id'];
                foreach ($selected as $cfo_id) {
                    $values[$field['fld_id']][$cfo_id] = @$field['field_options'][$cfo_id];
                }
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
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            $stmt = "DELETE FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_custom_field
                     WHERE
                        pcf_fld_id IN ($items)";
            $res = $GLOBALS["db_api"]->dbh->query($stmt);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return false;
            } else {
                $stmt = "DELETE FROM
                            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_custom_field
                         WHERE
                            icf_fld_id IN ($items)";
                $res = $GLOBALS["db_api"]->dbh->query($stmt);
                if (PEAR::isError($res)) {
                    Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                    return false;
                } else {
                    $stmt = "DELETE FROM
                                " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field_option
                             WHERE
                                cfo_fld_id IN ($items)";
                    $res = $GLOBALS["db_api"]->dbh->query($stmt);
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
        if (empty($_POST["list_display"])) {
            $_POST["list_display"] = 0;
        }
        if (empty($_POST["min_role"])) {
            $_POST["min_role"] = 1;
        }
        if (!isset($_POST["rank"])) {
            $_POST["rank"] = (Custom_Field::getMaxRank() + 1);
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
                    " . Misc::escapeInteger($_POST["list_display"]) . ",
                    " . Misc::escapeInteger($_POST["min_role"]) . ",
                    " . Misc::escapeInteger($_POST['rank']) . ",
                    '" . Misc::escapeString(@$_POST['custom_field_backend']) . "'
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $new_id = $GLOBALS["db_api"]->get_last_insert_id();
            if (($_POST["field_type"] == 'combo') || ($_POST["field_type"] == 'multiple')) {
                foreach ($_POST["field_options"] as $option_value) {
                    $params = Custom_Field::parseParameters($option_value);
                    Custom_Field::addOptions($new_id, $params["value"]);
                }
            }
            // add the project associations!
            for ($i = 0; $i < count($_POST["projects"]); $i++) {
                Custom_Field::associateProject($_POST["projects"][$i], $new_id);
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
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
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
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            for ($i = 0; $i < count($res); $i++) {
                $res[$i]["projects"] = @implode(", ", array_values(Custom_Field::getAssociatedProjects($res[$i]["fld_id"])));
                if (($res[$i]["fld_type"] == "combo") || ($res[$i]["fld_type"] == "multiple")) {
                    if (!empty($res[$i]['fld_backend'])) {
                        $res[$i]["field_options"] = @implode(", ", array_values(Custom_Field::getOptions($res[$i]["fld_id"])));
                    }
                }
                if (!empty($res[$i]['fld_backend'])) {
                    $res[$i]['field_options'] = 'Backend: ' . Custom_Field::getBackendName($res[$i]['fld_backend']);
                }
                $res[$i]['min_role_name'] = @User::getRole($res[$i]['fld_min_role']);
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
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
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
     * @return  array The custom field details
     */
    function getDetails($fld_id)
    {
        static $returns;

        if (isset($returns[$fld_id])) {
            return $returns[$fld_id];
        }

        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field
                 WHERE
                    fld_id=" . Misc::escapeInteger($fld_id);
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            $res["projects"] = @array_keys(Custom_Field::getAssociatedProjects($fld_id));
            $t = array();
            $options = Custom_Field::getOptions($fld_id);
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
     * @return  array The list of custom field options
     */
    function getOptions($fld_id, $ids = false)
    {
        static $returns;

        $return_key = $fld_id . serialize($ids);

        if (isset($returns[$return_key])) {
            return $returns[$return_key];
        }
        $backend = Custom_Field::getBackend($fld_id);
        if ((is_object($backend)) && (method_exists($backend, 'getList'))) {
            $list = $backend->getList($fld_id);
            if ($ids != false) {
                foreach ($list as $id => $value) {
                    if (!in_array($id, $ids)) {
                        unset($list[$id]);
                    }
                }
            }
            $returns[$return_key] = $list;
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
            $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
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
        if (empty($_POST["min_role"])) {
            $_POST["min_role"] = 1;
        }
        if (!isset($_POST["rank"])) {
            $_POST["rank"] = (Custom_Field::getMaxRank() + 1);
        }
        $old_details = Custom_Field::getDetails($_POST["id"]);
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
                    fld_list_display=" . Misc::escapeInteger($_POST["list_display"]) . ",
                    fld_min_role=" . Misc::escapeInteger($_POST['min_role']) . ",
                    fld_rank = " . Misc::escapeInteger($_POST['rank']) . ",
                    fld_backend = '" . Misc::escapeString(@$_POST['custom_field_backend']) . "'
                 WHERE
                    fld_id=" . Misc::escapeInteger($_POST["id"]);
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
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
                $current_options = $GLOBALS["db_api"]->dbh->getCol($stmt);
            }
            // gotta remove all custom field options if the field is being changed from a combo box to a text field
            if (($old_details["fld_type"] != $_POST["field_type"]) &&
                      (!in_array($old_details['fld_type'], array('text', 'textarea'))) &&
                      (!in_array($_POST["field_type"], array('combo', 'multiple')))) {
                Custom_Field::removeOptionsByFields($_POST["id"]);
            }
            // update the custom field options, if any
            if (($_POST["field_type"] == "combo") || ($_POST["field_type"] == "multiple")) {
                $updated_options = array();
                if (empty($_POST['custom_field_backend'])) {
                    foreach ($_POST["field_options"] as $option_value) {
                        $params = Custom_Field::parseParameters($option_value);
                        if ($params["type"] == 'new') {
                            Custom_Field::addOptions($_POST["id"], $params["value"]);
                        } else {
                            $updated_options[] = $params["id"];
                            // check if the user is trying to update the value of this option
                            if ($params["value"] != Custom_Field::getOptionValue($_POST["id"], $params["id"])) {
                                Custom_Field::updateOption($params["id"], $params["value"]);
                            }
                        }
                    }
                }
            }
            // get the diff between the current options and the ones posted by the form
            // and then remove the options not found in the form submissions
            if (in_array($_POST["field_type"], array('combo', 'multiple'))) {
                $diff_ids = @array_diff($current_options, $updated_options);
                if (@count($diff_ids) > 0) {
                    Custom_Field::removeOptions($_POST['id'], array_values($diff_ids));
                }
            }
            // now we need to check for any changes in the project association of this custom field
            // and update the mapping table accordingly
            $old_proj_ids = @array_keys(Custom_Field::getAssociatedProjects($_POST["id"]));
            // COMPAT: this next line requires PHP > 4.0.4
            $diff_ids = array_diff($old_proj_ids, $_POST["projects"]);
            if (count($diff_ids) > 0) {
                foreach ($diff_ids as $removed_prj_id) {
                    Custom_Field::removeIssueAssociation($_POST["id"], false, $removed_prj_id );
                }
            }
            // update the project associations now
            $stmt = "DELETE FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_custom_field
                     WHERE
                        pcf_fld_id=" . Misc::escapeInteger($_POST["id"]);
            $res = $GLOBALS["db_api"]->dbh->query($stmt);
            if (PEAR::isError($res)) {
                Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
                return -1;
            } else {
                for ($i = 0; $i < count($_POST["projects"]); $i++) {
                    Custom_Field::associateProject($_POST["projects"][$i], $_POST["id"]);
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
        $res = $GLOBALS["db_api"]->dbh->getCol($stmt);
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
            $res = $GLOBALS['db_api']->dbh->getCol($sql);
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
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
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
        $res = $GLOBALS["db_api"]->dbh->getCol($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            Custom_Field::removeOptions($ids, $res);
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
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
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
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
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
     * @access  public
     * @param   integer $prj_id The ID of the project.
     * @return  array An array of custom field names.
     */
    function getFieldsToBeListed($prj_id)
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
        $res = $GLOBALS["db_api"]->dbh->getAssoc($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            return $res;
        }
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
        $res = $GLOBALS["db_api"]->dbh->getOne($sql);
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
                    icf_value
                FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_custom_field
                WHERE
                    fld_id=icf_fld_id AND
                    icf_iss_id=" .  Misc::escapeInteger($iss_id) . " AND
                    fld_id = " . Misc::escapeInteger($fld_id);
        $res = $GLOBALS["db_api"]->dbh->getAll($sql, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return '';
        } else {
            $values = array();
            for ($i = 0; $i < count($res); $i++) {
                if (($res[$i]['fld_type'] == 'text') || ($res[$i]['fld_type'] == 'textarea')) {
                    $values[] = $res[$i]['icf_value'];
                } elseif (($res[$i]["fld_type"] == "combo") || ($res[$i]['fld_type'] == 'multiple')) {
                    if ($raw) {
                        $values[] = $res[$i]['icf_value'];
                    } else {
                        $values[] = Custom_Field::getOptionValue($res[$i]["fld_id"], $res[$i]["icf_value"]);
                    }
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
        return $GLOBALS["db_api"]->dbh->getOne($sql);
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
        $fields = Custom_Field::getList();
        for ($i = 0;$i < count($fields); $i++) {
            if ($fields[$i]['fld_id'] == $fld_id) {
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
                Custom_Field::setRank($fld_id, $target_row['fld_rank']);

                // update field we stole this rank from
                Custom_Field::setRank($target_row['fld_id'], $fields[$i]['fld_rank']);
            }
        }

        // re-order everything starting from 1
        $fields = Custom_Field::getList();
        $rank = 1;
        foreach ($fields as $field) {
            Custom_Field::setRank($field['fld_id'], $rank++);
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
        $res = $GLOBALS["db_api"]->dbh->query($sql);
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
        $files = Misc::getFileList(APP_INC_PATH . "custom_field");
        $list = array();
        for ($i = 0; $i < count($files); $i++) {
            // make sure we only list the backends
            if (preg_match('/^class\.(.*)\.php$/', $files[$i])) {
                // display a prettyfied backend name in the admin section
                $list[$files[$i]] = Custom_Field::getBackendName($files[$i]);
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
        $res = $GLOBALS["db_api"]->dbh->getOne($sql);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } elseif (!empty($res)) {
            require_once(APP_INC_PATH . "custom_field/$res");

            $file_name_chunks = explode(".", $res);
            $class_name = $file_name_chunks[1] . "_Custom_Field_Backend";

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
                    icf_value LIKE '%" . Misc::escapeString($search) . "%'";
        $res = $GLOBALS["db_api"]->dbh->getCol($sql);
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
        $backend = Custom_Field::getBackend($fld_id);
        if ((is_object($backend)) && (method_exists($backend, 'formatValue'))) {
            return $backend->formatValue($value, $fld_id, $issue_id);
        } else {
            return Link_Filter::processText(Auth::getCurrentProject(), htmlspecialchars($value));
        }
    }

}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Custom_Field Class');
}
?>
