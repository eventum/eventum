### DynamicCustomFieldExample

Put the contents below into the file "include/custom_field/class.dynamic_example.php".

Create a custom field named "Dynamic Controller", set the type to "combo box" and add the options to "red", "yellow" and "blue".

Then create another custom field, named whatever you like, set the type to "combo box" and the custom field backend to "Dynamic Example".

Also, please make sure that **either fields got a ranking \> 0** (the default), otherwise the setup will not work (press up arrow in one will probably do).

```php
    <?php
    /* vim: set expandtab tabstop=4 shiftwidth=4: */
    // +----------------------------------------------------------------------+
    // | Eventum - Issue Tracking System                                      |
    // +----------------------------------------------------------------------+
    // | Copyright (c) 2008 MySQL AB                                          |
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
    // | Authors: Bryan Alsdorf <bryan@mysql.com>                             |
    // +----------------------------------------------------------------------+
    //

    /**
     * Example dynamic custom field. This requires you create a custom field with the name "Dynamic Controller" and the options "red",
     * "blue" and "yellow". You also must create another custom field named whatever you like, with this file as the "Custom Field Backend".
     */
    class Dynamic_Example_Custom_Field_Backend extends Dynamic_Custom_Field_Backend
    {
        function getStructuredData()
        {
            $fld_id = self::getControllingCustomFieldID();
            // should pull from a dynamic data source but will hard code for now
            $data = array(
                array(
                    "keys"  => array(Custom_Field::getOptionKey($fld_id, 'red')),
                    "options"   =>  array(
                        "1" =>  "Apple",
                        "2" =>  "Fire Engine",
                        "3" =>  "Fire",
                    )
                ),
                array(
                    "keys"  => array(Custom_Field::getOptionKey($fld_id, 'blue')),
                    "options"   =>  array(
                        "4" =>  "water",
                        "5" =>  "sky",
                    )
                ),
                array(
                    "keys"  =>  array(Custom_Field::getOptionKey($fld_id, 'yellow')),
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


        function getControllingCustomFieldID()
        {
            return Custom_Field::getIdByTitle(self::getControllingCustomFieldName());
        }


        function getControllingCustomFieldName()
        {
            return 'Dynamic Controller';
        }
    }
```
