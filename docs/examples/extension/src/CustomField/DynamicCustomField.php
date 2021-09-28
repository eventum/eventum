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

use Custom_Field;
use Dynamic_Custom_Field_Backend;

/**
 * Example dynamic custom field. This requires you create a custom field with the name "Dynamic Controller" and the options "red",
 * "blue" and "yellow". You also must create another custom field named whatever you like, with this file as the "Custom Field Backend".
 */
class DynamicCustomField extends Dynamic_Custom_Field_Backend
{
    public function getStructuredData(): array
    {
        $fld_id = $this->getControllingCustomFieldId();
        // should pull from a dynamic data source but will hard code for now
        $data = [
            [
                'keys' => [Custom_Field::getOptionKey($fld_id, 'red')],
                'options' => [
                    '1' => 'Apple',
                    '2' => 'Fire Engine',
                    '3' => 'Fire',
                ],
            ],
            [
                'keys' => [Custom_Field::getOptionKey($fld_id, 'blue')],
                'options' => [
                    '4' => 'water',
                    '5' => 'sky',
                ],
            ],
            [
                'keys' => [Custom_Field::getOptionKey($fld_id, 'yellow')],
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

    public function getControllingCustomFieldId(): int
    {
        return Custom_Field::getIdByTitle($this->getControllingCustomFieldName());
    }

    public function getControllingCustomFieldName(): string
    {
        return 'Dynamic Controller';
    }
}
