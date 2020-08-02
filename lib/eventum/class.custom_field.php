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

use Eventum\Config\Paths;
use Eventum\CustomField\Converter;
use Eventum\CustomField\Fields\FormatValueInterface;
use Eventum\Db\Doctrine;
use Eventum\Diff;
use Eventum\Extension\ExtensionLoader;
use Eventum\Model\Entity\CustomField;
use Eventum\ServiceContainer;

/**
 * Class to handle the business logic related to the administration
 * of custom fields in the system.
 */
class Custom_Field
{
    /**
     * Updates custom field values from the $_POST array.
     */
    public static function updateFromPost($send_notification = false)
    {
        $issue_id = $_POST['issue_id'];
        $custom_fields = $_POST['custom_fields'] ?? null;
        if ($custom_fields) {
            $updated_fields = self::updateValues($issue_id, $custom_fields);
            if ($send_notification) {
                Notification::notifyIssueUpdated($issue_id, [], [], $updated_fields);
            }

            return $updated_fields;
        }

        return null;
    }

    public static function updateCustomFieldValues(int $issue_id, int $role_id, array $custom_fields): array
    {
        if (!$custom_fields) {
            return [];
        }

        $repo = Doctrine::getCustomFieldRepository();

        return $repo->updateCustomFieldValues($issue_id, $role_id, $custom_fields);
    }

    /**
     * Method used to update the values stored in the database.
     */
    public static function updateValues(int $issue_id, array $custom_fields): array
    {
        if (!$custom_fields) {
            return [];
        }

        $prj_id = Auth::getCurrentProject();
        $role_id = Auth::getCurrentRole();
        $usr_id = Auth::getUserID();
        $usr_full_name = User::getFullName($usr_id);

        $old_values = self::getValuesByIssue($prj_id, $issue_id);
        $updated_fields = self::updateCustomFieldValues($issue_id, $role_id, $custom_fields);
        $new_values = self::getValuesByIssue($prj_id, $issue_id);

        Workflow::handleCustomFieldsUpdated($prj_id, $issue_id, $old_values, $new_values, $updated_fields);
        Issue::markAsUpdated($issue_id);

        // log the changes
        $changes = [];
        foreach ($updated_fields as $fld_id => $updated_field) {
            if (!isset($changes[$updated_field['min_role']])) {
                $changes[$updated_field['min_role']] = [];
            }
            $title = $updated_field['title'];
            $value = $updated_field['changes'];

            if ($value) {
                $changes[$updated_field['min_role']][] = "$title: $value";
            } else {
                $changes[$updated_field['min_role']][] = $title;
            }
        }

        foreach ($changes as $min_role => $role_changes) {
            History::add($issue_id, $usr_id, 'custom_field_updated', 'Custom field updated ({changes}) by {user}', [
                'changes' => implode('; ', $role_changes),
                'user' => $usr_full_name,
            ], $min_role);
        }

        return $updated_fields;
    }

    /**
     * Returns custom field updates in a diff format
     *
     * @param   array $updated_fields
     * @param   int|null $role_id If specified only fields that $role can see will be returned
     * @return  array
     */
    public static function formatUpdatesToDiffs(array $updated_fields, ?int $role_id = null)
    {
        $differ = new Diff\CustomField();

        return $differ->diff($updated_fields, $role_id);
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
     * Method used to get the custom field key based on the value.
     *
     * @param   int $fld_id The custom field ID
     * @param   int $value The custom field option ID
     * @return  string The custom field option value
     */
    public static function getOptionKey(int $fld_id, $value): ?int
    {
        // to compensate broken custom field backends
        if (!$fld_id) {
            return 0;
        }

        $repo = Doctrine::getCustomFieldRepository();
        $cf = $repo->findById($fld_id);
        $values = $cf->getOptionValues();
        $cfo_id = array_search($value, $values, true);

        return $cfo_id ?: null;
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
            if ($field['fld_type'] === 'combo') {
                $values[$field['fld_id']] = [
                    $field['selected_cfo_id'] => $field['value'],
                ];
            } elseif ($field['fld_type'] === 'multiple' || $field['fld_type'] === 'checkbox') {
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
     * Method used to get the details of a specific custom field.
     *
     * @param   int $fld_id The custom field ID
     * @return  array The custom field details
     */
    public static function getDetails(int $fld_id): array
    {
        $repo = Doctrine::getCustomFieldRepository();
        $cf = $repo->findById($fld_id);

        $res = $cf->toArray();
        $res['projects'] = $cf->getProjectIds();
        $res['field_options'] = $cf->getOptionValues();

        return $res;
    }

    /**
     * Method used to get the list of custom field options associated
     * with a given custom field ID.
     *
     * @param   int $fld_id The custom field ID
     * @param   array $cfo_ids an array of ids to return values for
     * @return array The list of custom field options
     */
    public static function getOptions(int $fld_id, array $cfo_ids = []): array
    {
        $repo = Doctrine::getCustomFieldRepository();
        $cf = $repo->findById($fld_id);
        $list = $cf->getOptionValues();

        if ($cfo_ids) {
            foreach ($list as $id => $value) {
                if (!in_array($id, $cfo_ids, true)) {
                    unset($list[$id]);
                }
            }
        }

        return $list;
    }

    /**
     * Method to return the names of the fields which should be displayed on the list issues page.
     *
     * @param   int $prj_id the ID of the project
     * @return  array an array of custom field names
     * @see Custom_Field::formatValue()
     */
    public static function getFieldsToBeListed(int $prj_id): array
    {
        static $cache;
        $role_id = Auth::getCurrentRole();

        $initialize = static function (int $prj_id) use ($role_id) {
            $repo = Doctrine::getCustomFieldRepository();
            $fields = $repo->getListByProject($prj_id, $role_id, CustomField::LIST_DISPLAY);

            $res = [];
            foreach ($fields as $field) {
                $res[$field->getId()] = $field->getTitle();
            }

            return $res;
        };

        return $cache[$prj_id][$role_id] ?? $cache[$prj_id][$role_id] = $initialize($prj_id);
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
     * @return mixed an array or string containing the value
     */
    public static function getDisplayValue(int $iss_id, int $fld_id)
    {
        $repo = Doctrine::getCustomFieldRepository();

        return $repo->findById($fld_id)->getDisplayValue($iss_id);
    }

    /**
     * Formats the return value
     *
     * @param   mixed $value The value to format
     * @param   int $fld_id The ID of the field
     * @param   int $issue_id The ID of the issue
     * @return  mixed   the formatted value
     * @see Custom_Field::getFieldsToBeListed
     */
    public static function formatValue($value, $fld_id, $issue_id)
    {
        $repo = Doctrine::getCustomFieldRepository();
        $cf = $repo->findById($fld_id);
        $backend = $cf->getProxy();

        if ($backend && $backend->hasInterface(FormatValueInterface::class)) {
            return $backend->formatValue($value, $fld_id, $issue_id);
        }

        $prj_id = Auth::getCurrentProject();

        return Link_Filter::processText($prj_id, Misc::htmlentities($value), true);
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

    /**
     * @internal
     */
    public static function getExtensionLoader(): ExtensionLoader
    {
        $localPath = ServiceContainer::getConfig()['local_path'];

        $dirs = [
            Paths::APP_INC_PATH . '/custom_field',
            $localPath . '/custom_field',
        ];

        return new ExtensionLoader($dirs, '%s_Custom_Field_Backend');
    }

    /**
     * @deprecated preserved for workflow methods
     */
    public static function getFieldsByProject(int $prj_id): array
    {
        $repo = Doctrine::getCustomFieldRepository();
        // get all fields, regardless user level
        $fields = $repo->getListByProject($prj_id, User::ROLE_NEVER_DISPLAY, null);

        $result = [];
        foreach ($fields as $field) {
            $result[] = $field->getId();
        }

        return $result;
    }

    /**
     * @deprecated preserved for workflow methods
     */
    public static function associateIssue($issue_id, $fld_id, $value): bool
    {
        $role_id = Auth::getCurrentRole();
        $custom_fields = [
            $fld_id => $value,
        ];
        try {
            self::updateCustomFieldValues($issue_id, $role_id, $custom_fields);
        } catch (Throwable $e) {
            ServiceContainer::getLogger()->error($e->getMessage(), ['exception' => $e]);

            return false;
        }

        return true;
    }
}
