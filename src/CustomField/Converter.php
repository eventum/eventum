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

namespace Eventum\CustomField;

use Custom_Field;
use DateTime;
use Eventum\CustomField\Fields\DynamicCustomFieldInterface;
use Eventum\CustomField\Fields\JavascriptValidationInterface;
use Eventum\CustomField\Fields\RequiredValueInterface;
use Eventum\Model\Entity\IssueCustomField;

class Converter
{
    private const DATE_FORMAT = 'Y-m-d';

    /**
     * Convert values to legacy array structures
     */
    public function convert(array $customFields, int $projectId, int $issueId, ?string $formType): array
    {
        $fields = [];
        foreach ($customFields as $customField) {
            $row = $this->convertRow($customField);

            if ($row['fld_type'] === 'combo') {
                $row['selected_cfo_id'] = $row['value'];
                $row['original_value'] = $row['value'];
                $row['value'] = Custom_Field::getOptionValue($row['fld_id'], $row['value']);
                $row['field_options'] = Custom_Field::getOptions($row['fld_id'], false, $issueId);

                // add the select option to the list of values if it isn't on the list (useful for fields with active and non-active items)
                if (!empty($row['original_value']) && !isset($row['field_options'][$row['original_value']])) {
                    $row['field_options'][$row['original_value']] = Custom_Field::getOptionValue($row['fld_id'], $row['original_value']);
                }

                $fields[] = $row;
            } elseif (in_array($row['fld_type'], Custom_Field::$option_types, true)) {
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
                    $row['value'] = Custom_Field::getOptionValue($row['fld_id'], $row['value']);
                    $row['field_options'] = Custom_Field::getOptions($row['fld_id']);
                    $fields[] = $row;
                    $found_index = count($fields) - 1;
                } else {
                    $fields[$found_index]['value'] .= ', ' . Custom_Field::getOptionValue($row['fld_id'], $row['value']);
                    $fields[$found_index]['selected_cfo_id'][] = $row['value'];
                }

                // add the select option to the list of values if it isn't on the list (useful for fields with active and non-active items)
                if ($original_value !== null && !in_array($original_value, $fields[$found_index]['field_options'])) {
                    $fields[$found_index]['field_options'][$original_value] = Custom_Field::getOptionValue($row['fld_id'], $original_value);
                }
            } else {
                $row['value'] = $row[Custom_Field::getDBValueFieldNameByType($row['fld_type'])];
                $fields[] = $row;
            }
        }

        foreach ($fields as $key => $field) {
            $backend = Custom_Field::getBackend($field['fld_id']);
            if ($backend instanceof DynamicCustomFieldInterface) {
                $fields[$key]['dynamic_options'] = $backend->getStructuredData();
                $fields[$key]['controlling_field_id'] = $backend->getControllingCustomFieldID();
                $fields[$key]['controlling_field_name'] = $backend->getControllingCustomFieldName();
                $fields[$key]['hide_when_no_options'] = $backend->hideWhenNoOptions();
                $fields[$key]['lookup_method'] = $backend->lookupMethod();
            }

            // check if the backend implements "isRequired"
            if ($backend && $backend->hasInterface(RequiredValueInterface::class)) {
                $fields[$key]['fld_report_form_required'] = $backend->isRequired($fields[$key]['fld_id'], 'report', $issueId);
                $fields[$key]['fld_anonymous_form_required'] = $backend->isRequired($fields[$key]['fld_id'], 'anonymous', $issueId);
                $fields[$key]['fld_close_form_required'] = $backend->isRequired($fields[$key]['fld_id'], 'close', $issueId);
                $fields[$key]['fld_edit_form_required'] = $backend->isRequired($fields[$key]['fld_id'], 'edit', $issueId);
            }
            if ($backend && $backend->hasInterface(JavascriptValidationInterface::class)) {
                $fields[$key]['validation_js'] = $backend->getValidationJs($fields[$key]['fld_id'], $formType, $issueId);
            } else {
                $fields[$key]['validation_js'] = '';
            }
        }

        return $fields;
    }

    private function convertRow(IssueCustomField $icf): array
    {
        $field = $icf->customField;
        $value = $icf->getValue();

        return [
            'fld_id' => $field->getId(),
            'fld_title' => $field->getTitle(),
            'fld_type' => $field->getType(),
            'fld_report_form_required' => (string)(int)$field->isReportFormRequired(),
            'fld_anonymous_form_required' => (string)(int)$field->isAnonymousFormRequired(),
            'fld_close_form_required' => (string)(int)$field->isCloseFormRequired(),
            'fld_edit_form_required' => (string)(int)$field->isEditFormRequired(),
            'value' => $value,
            'icf_value' => $icf->getStringValue(),
            'icf_value_date' => $value instanceof DateTime ? $value->format(self::DATE_FORMAT) : null,
            'icf_value_integer' => $icf->getIntegerValue(),
            'fld_min_role' => $field->getMinRole(),
            'fld_description' => $field->getDescription(),
        ];
    }
}
