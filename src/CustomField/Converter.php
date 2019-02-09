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
use Eventum\CustomField\Fields\DefaultValueInterface;
use Eventum\CustomField\Fields\DynamicCustomFieldInterface;
use Eventum\CustomField\Fields\JavascriptValidationInterface;
use Eventum\CustomField\Fields\ListInterface;
use Eventum\CustomField\Fields\RequiredValueInterface;
use Eventum\Model\Entity\CustomField;
use Eventum\Model\Entity\IssueCustomField;

class Converter
{
    private const DATE_FORMAT = 'Y-m-d';

    public function convertCustomFields(array $customFields, ?int $issueId, ?string $formType): array
    {
        $fields = [];
        foreach ($customFields as $customField) {
            $fields[] = $this->convertCustomField($customField);
        }

        $this->applyBackendValues($fields, $issueId, $formType, false);

        return $fields;
    }

    /**
     * Convert values to legacy array structures
     */
    public function convertIssueCustomFields(array $customFields, ?int $issueId, ?string $formType): array
    {
        $fields = [];
        foreach ($customFields as $customField) {
            $row = $this->convertIssueCustomField($customField);
            $fld_id = $row['fld_id'];

            if ($row['fld_type'] === 'combo') {
                $row['selected_cfo_id'] = $row['value'];
                $row['original_value'] = $row['value'];
                $row['value'] = Custom_Field::getOptionValue($fld_id, $row['value']);
                $row['field_options'] = Custom_Field::getOptions($fld_id, false, $issueId);

                // add the select option to the list of values if it isn't on the list (useful for fields with active and non-active items)
                if (!empty($row['original_value']) && !isset($row['field_options'][$row['original_value']])) {
                    $row['field_options'][$row['original_value']] = Custom_Field::getOptionValue($fld_id, $row['original_value']);
                }

                $fields[] = $row;
            } elseif (in_array($row['fld_type'], Custom_Field::$option_types, true)) {
                // check whether this field is already in the array
                $found_index = null;
                foreach ($fields as $y => $field) {
                    if ($field['fld_id'] == $fld_id) {
                        $found_index = $y;
                    }
                }
                $original_value = $row['value'];
                if ($found_index === null) {
                    $row['selected_cfo_id'] = [$row['value']];
                    $row['value'] = Custom_Field::getOptionValue($fld_id, $row['value']);
                    $row['field_options'] = Custom_Field::getOptions($fld_id);
                    $fields[] = $row;
                    $found_index = count($fields) - 1;
                } else {
                    $fields[$found_index]['value'] .= ', ' . Custom_Field::getOptionValue($fld_id, $row['value']);
                    $fields[$found_index]['selected_cfo_id'][] = $row['value'];
                }

                // add the select option to the list of values if it isn't on the list (useful for fields with active and non-active items)
                if ($original_value !== null && !in_array($original_value, $fields[$found_index]['field_options'], true)) {
                    $fields[$found_index]['field_options'][$original_value] = Custom_Field::getOptionValue($fld_id, $original_value);
                }
            } else {
                $fields[] = $row;
            }
        }

        $this->applyBackendValues($fields, $issueId, $formType);

        return $fields;
    }

    private function applyBackendValues(array &$fields, ?int $issueId, ?string $formType, bool $skipValueOptions = true): void
    {
        foreach ($fields as &$field) {
            $fld_id = $field['fld_id'];
            $backend = Custom_Field::getBackend($fld_id);

            if ($backend instanceof DynamicCustomFieldInterface) {
                $field['dynamic_options'] = $backend->getStructuredData();
                $field['controlling_field_id'] = $backend->getControllingCustomFieldId();
                $field['controlling_field_name'] = $backend->getControllingCustomFieldName();
                $field['hide_when_no_options'] = $backend->hideWhenNoOptions();
                $field['lookup_method'] = $backend->lookupMethod();
            }

            if ($backend && $backend->hasInterface(RequiredValueInterface::class)) {
                $field['fld_report_form_required'] = $backend->isRequired($fld_id, 'report', $issueId);
                $field['fld_anonymous_form_required'] = $backend->isRequired($fld_id, 'anonymous', $issueId);
                $field['fld_close_form_required'] = $backend->isRequired($fld_id, 'close', $issueId);
                $field['fld_edit_form_required'] = $backend->isRequired($fld_id, 'edit', $issueId);
            }

            if ($backend && $backend->hasInterface(JavascriptValidationInterface::class)) {
                $field['validation_js'] = $backend->getValidationJs($fld_id, $formType, $issueId);
            } else {
                $field['validation_js'] = '';
            }

            if (!$skipValueOptions) {
                $field['field_options'] = $this->getOptions($field, $backend, $formType);

                if ($backend && $backend->hasInterface(DefaultValueInterface::class)) {
                    $field['default_value'] = $backend->getDefaultValue($fld_id);
                } else {
                    $field['default_value'] = '';
                }
            }

            // do not expose these outside. yet
            unset($field['_cf'], $field['_icf']);
        }
    }

    private function getOptions(array $field, ?Proxy $backend, ?string $formType): array
    {
        if ($backend && $backend->hasInterface(ListInterface::class)) {
            return $backend->getList($field['fld_id'], null, $formType);
        }

        /** @var CustomField $cf */
        $cf = $field['_cf'];

        return $cf->getOptions();
    }

    private function convertIssueCustomField(IssueCustomField $icf): array
    {
        $result = $this->convertCustomField($icf->customField);

        $value = $icf->getValue();
        $result += [
            '_icf' => $icf,
            'value' => $value,
            'icf_value' => $icf->getStringValue(),
            'icf_value_date' => $value instanceof DateTime ? $value->format(self::DATE_FORMAT) : null,
            'icf_value_integer' => $icf->getIntegerValue(),
        ];

        return $result;
    }

    private function convertCustomField(CustomField $field): array
    {
        return [
            '_cf' => $field,
            'fld_id' => $field->getId(),
            'fld_title' => $field->getTitle(),
            'fld_type' => $field->getType(),
            'fld_report_form_required' => (string)(int)$field->isReportFormRequired(),
            'fld_anonymous_form_required' => (string)(int)$field->isAnonymousFormRequired(),
            'fld_close_form_required' => (string)(int)$field->isCloseFormRequired(),
            'fld_edit_form_required' => (string)(int)$field->isEditFormRequired(),
            'fld_min_role' => $field->getMinRole(),
            'fld_description' => $field->getDescription(),
        ];
    }
}
