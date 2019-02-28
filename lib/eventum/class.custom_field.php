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

use Eventum\CustomField\Converter;
use Eventum\CustomField\Factory;
use Eventum\CustomField\Fields\FormatValueInterface;
use Eventum\CustomField\Fields\ListInterface;
use Eventum\CustomField\Fields\OptionValueInterface;
use Eventum\CustomField\Proxy;
use Eventum\Db\DatabaseException;
use Eventum\Db\Doctrine;
use Eventum\Differ;
use Eventum\Extension\ExtensionLoader;
use Eventum\Model\Entity\CustomField;
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
        $differ = new Differ();
        foreach ($updated_fields as $fld_id => $field) {
            if ($field['old_display'] != $field['new_display']) {
                if ($field['type'] === 'textarea') {
                    $desc_diff = $differ->diff($field['old_display'], $field['new_display']);
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
     * @param   string $value The custom field value
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
     * @param   bool $for_edit True if the fld_min_role_edit permission should be checked
     * @return  array The list of custom fields
     */
    public static function getListByProject($prj_id, $form_type, $fld_type = false, $for_edit = false): array
    {
        $repo = Doctrine::getCustomFieldRepository();
        $usr_role = Auth::getCurrentRole();

        $customFields = $repo->getListByProject($prj_id, $usr_role, $form_type ?: null, $fld_type, $for_edit);
        $converter = new Converter();

        return $converter->convertCustomFields($customFields, null, $form_type ?: null);
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

        $cacheKey = $fld_id . $value;

        if (isset($returns[$cacheKey])) {
            return $returns[$cacheKey];
        }

        $backend = self::getBackend($fld_id);

        if ($backend && $backend->hasInterface(OptionValueInterface::class)) {
            return $backend->getOptionValue($fld_id, $value);
        }

        if ($backend && $backend->hasInterface(ListInterface::class)) {
            $values = $backend->getList($fld_id, false);
            $returns[$cacheKey] = @$values[$value];

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
            $returns[$cacheKey] = '';

            return '';
        }

        $returns[$cacheKey] = $res;

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

        $cacheKey = $fld_id . $value;
        if (isset($returns[$cacheKey])) {
            return $returns[$cacheKey];
        }

        $backend = self::getBackend($fld_id);
        // TODO: add OptionKey interface, instead of using possibly heavy getList()
        if ($backend && $backend->hasInterface(ListInterface::class)) {
            $values = $backend->getList($fld_id, false);
            $key = array_search($value, $values);
            $returns[$cacheKey] = $key;

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
            $returns[$cacheKey] = '';

            return '';
        }

        $returns[$cacheKey] = $res;

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
     * @param   mixed $form_type The name of the form this is for or if this is an array the ids of the fields to return
     * @param   bool $for_edit True if the fld_min_role_edit permission should be checked
     * @return  array The list of custom fields
     */
    public static function getListByIssue($prj_id, $iss_id, $usr_id = null, $form_type = false, $for_edit = false): array
    {
        if (is_array($form_type)) {
            // form type used as custom_field id value. find this usage!
            throw new LogicException('Not supported');
        }

        $usr_role = User::getRoleByUser($usr_id ?: Auth::getUserID(), $prj_id) ?: 0;

        $repo = Doctrine::getCustomFieldRepository();
        $customFields = $repo->getListByIssue($prj_id, $iss_id, $usr_role, $form_type ?: null, $for_edit);
        if (!$customFields) {
            return [];
        }

        $converter = new Converter();

        return $converter->convertIssueCustomFields($customFields, $iss_id, $form_type ?: null);
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
        if ($backend && $backend->hasInterface(ListInterface::class)) {
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

        if ($order_by === null) {
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
     * Method used to remove the issue associations related to a given
     * custom field ID.
     *
     * @param   int|int[] $fld_id The custom field ID
     * @param   int $issue_id The issue ID (not required)
     * @param   int $prj_id The project ID (not required)
     */
    public static function removeIssueAssociation($fld_id, $issue_id = null, $prj_id = null): void
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
            $res = DB_Helper::getInstance()->getColumn($sql, [$prj_id]);

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
        DB_Helper::getInstance()->query($stmt, $params);
    }

    /**
     * Method to return the names of the fields which should be displayed on the list issues page.
     *
     * @param   int $prj_id the ID of the project
     * @return  array an array of custom field names
     */
    public static function getFieldsToBeListed(int $prj_id): array
    {
        $role_id = Auth::getCurrentRole();
        $repo = Doctrine::getCustomFieldRepository();
        $fields = $repo->getListByProject($prj_id, $role_id, CustomField::LIST_DISPLAY);

        $res = [];
        foreach ($fields as $field) {
            $res[$field->getId()] = $field->getTitle();
        }

        return $res;
    }

    /**
     * Returns the fld_id of the field with the specified title
     *
     * @param   string $title The title of the field
     * @return  int The fld_id
     */
    public static function getIdByTitle($title): ?int
    {
        $repo = Doctrine::getCustomFieldRepository();
        $cf = $repo->findOneBy(['title' => $title]);

        return $cf ? $cf->getId() : null;
    }

    /**
     * Returns the value for the specified field
     *
     * @param   int $iss_id The ID of the issue
     * @param   int $fld_id The ID of the field
     * @param   bool $original If the raw value should be displayed
     * @return mixed an array or string containing the value
     */
    public static function getDisplayValue(int $iss_id, int $fld_id, bool $original = false)
    {
        $repo = Doctrine::getCustomFieldRepository();
        $cf = $repo->findById($fld_id);

        $convertValue = !$original && $cf->isOptionType();
        $values = [];
        foreach ($cf->getMatchingIssues($iss_id) as $icf) {
            $value = $icf->getValue();
            if ($convertValue) {
                $value = self::getOptionValue($cf->getId(), $value);
            }
            $values[] = $value;
        }

        if ($original) {
            return $values;
        }

        return implode(', ', $values);
    }

    /**
     * Returns an instance of custom field backend class if it exists for the
     * specified field.
     *
     * @param   int $fld_id The ID of the field
     * @return  Proxy null if there is no backend or an instance of the backend class
     */
    public static function getBackend($fld_id): ?Proxy
    {
        static $returns;

        // poor mans caching
        if (isset($returns[$fld_id])) {
            return $returns[$fld_id] ?: null;
        }

        $sql = 'SELECT
                    fld_backend
                FROM
                    `custom_field`
                WHERE
                    fld_id = ?';
        $res = DB_Helper::getInstance()->getOne($sql, [$fld_id]);

        if ($res) {
            try {
                $instance = Factory::create($res);
            } catch (InvalidArgumentException $e) {
                Logger::app()->error("Could not load backend $res", ['exception' => $e]);
                $instance = false;
            }

            $returns[$fld_id] = $instance;
        } else {
            $returns[$fld_id] = false;
        }

        return $returns[$fld_id] ?: null;
    }

    /**
     * Formats the return value
     *
     * @param   mixed $value The value to format
     * @param   int $fld_id The ID of the field
     * @param   int $issue_id The ID of the issue
     * @return  mixed   the formatted value
     */
    public static function formatValue($value, $fld_id, $issue_id)
    {
        $backend = self::getBackend($fld_id);
        if ($backend && $backend->hasInterface(FormatValueInterface::class)) {
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
