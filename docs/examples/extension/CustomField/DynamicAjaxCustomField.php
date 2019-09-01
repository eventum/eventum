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

namespace Example\CustomField;

use Dynamic_Custom_Field_Backend;
use Eventum\CustomField\Fields\OptionValueInterface;

class DynamicAjaxCustomField extends Dynamic_Custom_Field_Backend implements OptionValueInterface
{
    public function getStructuredData(): array
    {
        $fld_id = $this->getControllingCustomFieldId();
        // should pull from a dynamic data source but will hard code for now
        $data = [
            [
                'keys' => [1],
                'options' => [
                    '1' => 'Apple',
                    '2' => 'Fire Engine',
                    '3' => 'Fire',
                ],
            ],
            [
                'keys' => [2],
                'options' => [
                    '4' => 'water',
                    '5' => 'sky',
                ],
            ],
            [
                'keys' => [3],
                'options' => [
                    '6' => 'bannana',
                    '7' => 'gold',
                    '8' => 'yellow things',
                    '9' => 'more yellow things',
                ],
            ],
        ];

        return $data;
    }

    public function getOptionValue(int $fld_id, string $value): string
    {
        return $value;
    }

    public function getControllingCustomFieldName(): string
    {
        return 'Priority';
    }

    public function hideWhenNoOptions(): bool
    {
        return false;
    }

    public function getDomId(): string
    {
        return 'priority';
    }

    /**
     * Should return 'local' or 'ajax'.
     */
    public function lookupMethod(): string
    {
        return 'ajax';
    }

    public function getDynamicOptions(array $data): array
    {
        $value = $data['priority'];
        foreach ($this->getStructuredData() as $row) {
            if (in_array($value, $row['keys'])) {
                return array_merge(['' => 'Please choose an option'], $row['options']);
            }
        }

        return [];
    }
}
