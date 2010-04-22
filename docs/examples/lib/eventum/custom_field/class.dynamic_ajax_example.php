<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
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
// | Authors: Bryan Alsdorf <bryan@askmonty.org>                          |
// +----------------------------------------------------------------------+
//

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
