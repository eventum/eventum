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

require_once 'class.dynamic.php';

/**
 * Example dynamic custom field. This requires you create a custom field with the name "Dynamic Controller" and the options "red",
 * "blue" and "yellow". You also must create another custom field named whatever you like, with this file as the "Custom Field Backend".
 */
class Dynamic_Example_Custom_Field_Backend extends Dynamic_Custom_Field_Backend
{
    public function getStructuredData()
    {
        $fld_id = self::getControllingCustomFieldID();
        // should pull from a dynamic data source but will hard code for now
        $data = [
            [
                'keys'  => [Custom_Field::getOptionKey($fld_id, 'red')],
                'options'   =>  [
                    '1' =>  'Apple',
                    '2' =>  'Fire Engine',
                    '3' =>  'Fire',
                ]
            ],
            [
                'keys'  => [Custom_Field::getOptionKey($fld_id, 'blue')],
                'options'   =>  [
                    '4' =>  'water',
                    '5' =>  'sky',
                ]
            ],
            [
                'keys'  =>  [Custom_Field::getOptionKey($fld_id, 'yellow')],
                'options'   =>  [
                    '6' =>  'bannana',
                    '7' =>  'gold',
                    '8' =>  'yellow things',
                    '9' =>  'more yellow things',
                ]
            ],
        ];

        return $data;
    }

    public function getControllingCustomFieldID()
    {
        return Custom_Field::getIDByTitle(self::getControllingCustomFieldName());
    }

    public function getControllingCustomFieldName()
    {
        return 'Dynamic Controller';
    }
}
