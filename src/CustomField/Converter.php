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

use Doctrine\Common\Collections\ArrayCollection;
use Eventum\CustomField\Fields\DefaultValueInterface;
use Eventum\CustomField\Fields\JavascriptValidationInterface;
use Eventum\CustomField\Fields\RequiredValueInterface;
use Eventum\Model\Entity\CustomField;
use Eventum\Model\Entity\IssueCustomField;
use Generator;

class Converter
{
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
    public function convertIssueCustomFields(array $customFields, int $issueId, ?string $formType): array
    {
        $fields = [];
        foreach ($this->expandIssueCustomFields($customFields, $issueId) as $row) {
            $fld_id = $row['fld_id'];
            /** @var CustomField $cf */
            $cf = $row['_cf'];
            /** @var IssueCustomField $icf */
            $icf = $row['_icf'];

            if ($row['fld_type'] === 'combo') {
                $row['selected_cfo_id'] = $row['value'];
                $row['original_value'] = $row['value'];
                $row['value'] = $icf->getOptionValue();
                $row['field_options'] = $this->getOptions($row, $formType, $issueId);

                // add the select option to the list of values if it isn't on the list (useful for fields with active and non-active items)
                if (!empty($row['original_value']) && !isset($row['field_options'][$row['original_value']])) {
                    $row['field_options'][$row['original_value']] = $icf->getOptionValue();
                }

                $fields[] = $row;
            } elseif ($cf->isOptionType()) {
                // check whether this field is already in the array
                $found_index = null;
                foreach ($fields as $y => $field) {
                    if ($field['fld_id'] === $fld_id) {
                        $found_index = $y;
                    }
                }
                $original_value = $row['value'];
                if ($found_index === null) {
                    $row['selected_cfo_id'] = [$row['value']];
                    $row['value'] = $icf->getOptionValue();
                    $row['field_options'] = $this->getOptions($row, $formType, $issueId);

                    $fields[] = $row;
                    $found_index = count($fields) - 1;
                } else {
                    $fields[$found_index]['value'] .= ', ' . $icf->getOptionValue();
                    $fields[$found_index]['selected_cfo_id'][] = $row['value'];
                }

                // add the select option to the list of values if it isn't on the list (useful for fields with active and non-active items)
                if ($original_value !== null && !in_array($original_value, $fields[$found_index]['field_options'], true)) {
                    $fields[$found_index]['field_options'][$original_value] = $icf->getOptionValue();
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
            /** @var CustomField $cf */
            $cf = $field['_cf'];
            $backend = $cf->getProxy();

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
                $field['field_options'] = $this->getOptions($field, $formType);

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

    private function getOptions(array $field, ?string $formType = null, ?int $issueId = null): array
    {
        /** @var CustomField $cf */
        $cf = $field['_cf'];

        return $cf->getOptionValues($formType, $issueId);
    }

    private function expandIssueCustomFields(array $customFields, int $issueId): Generator
    {
        $result = new ArrayCollection();
        /** @var CustomField $cf */
        foreach ($customFields as $cf) {
            $issueFields = $cf->getIssueCustomFields($issueId);
            if ($issueFields->count()) {
                foreach ($issueFields as $isc) {
                    $row = $this->convertIssueCustomField($isc);
                    $result->add($row);
                }
            } else {
                // create empty IssueCustomField for left-joined issues
                $icf = new IssueCustomField();
                $icf->customField = $cf;
                $row = $this->convertIssueCustomField($icf);
                $result->add($row);
            }
        }

        yield from $result;
    }

    private function convertIssueCustomField(IssueCustomField $icf): array
    {
        $result = $this->convertCustomField($icf->customField);

        $value = $icf->getValue();
        $result += [
            '_icf' => $icf,
            'value' => $value,
            'icf_value' => $icf->getStringValue(),
            'icf_value_date' => $icf->getDate(),
            'icf_value_integer' => $icf->getIntegerValue(),
        ];

        return $result;
    }

    private function convertCustomField(CustomField $field): array
    {
        $result = $field->toArray();
        $result['_cf'] = $field;

        return $result;
    }
}
