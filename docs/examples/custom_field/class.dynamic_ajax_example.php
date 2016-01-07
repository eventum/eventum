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

class Dynamic_Ajax_Example_Custom_Field_Backend extends Dynamic_Custom_Field_Backend
{
    function getStructuredData()
    {
        $fld_id = self::getControllingCustomFieldID();
        // should pull from a dynamic data source but will hard code for now
        $data = array(
            array(
                "keys"  => array(1),
                "options"   =>  array(
                    "1" =>  "Apple",
                    "2" =>  "Fire Engine",
                    "3" =>  "Fire",
                )
            ),
            array(
                "keys"  => array(2),
                "options"   =>  array(
                    "4" =>  "water",
                    "5" =>  "sky",
                )
            ),
            array(
                "keys"  =>  array(3),
                "options"   =>  array(
                    '6' =>  'bannana',
                    '7' =>  'gold',
                    '8' =>  'yellow things',
                    '9' =>  'more yellow things',
                )
            ),
        );
        return $data;
    }

    function getOptionValue($fld_id, $value)
    {
        return $value;
    }

    function getControllingCustomFieldName()
    {
        return 'Priority';
    }

    function hideWhenNoOptions()
    {
        return false;
    }

    function getDomID()
    {
        return 'priority';
    }

    /**
     * Should return 'local' or 'ajax'.
     *
     * @return string
     */
    function lookupMethod()
    {
        return 'ajax';
    }

    function getDynamicOptions($data)
    {
        $value = $data['priority'];
        foreach( $this->getStructuredData() as $row) {
            if (in_array($value, $row['keys'])) {
                return array_merge(array("" => "Please choose an option"), $row['options']);
            }
        }
    }
}
