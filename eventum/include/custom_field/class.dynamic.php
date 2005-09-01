<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005 MySQL AB                              |
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

include_once(APP_INC_PATH . "class.user.php");
include_once(APP_INC_PATH . "db_access.php");


/**
 * Custom field backend to assist other backends in dynamically changing the
 * contents of one field or hiding/showing based on another field.
 * 
 * @author Bryan Alsdorf <bryan@mysql.com>
 */
class Dynamic_Custom_Field_Backend
{
    function getList($fld_id)
    {
        $list = array();
        $data = $this->getStructuredData();
        foreach ($data as $row) {
            $list += $row['options'];
        }
        return $list;
    }
    
    
    /**
     * Returns a multi dimension array of data to display. The values listed
     * in the "keys" array are possible values for the controlling field to display
     * options from the "options" array.
     * For example, if you have a field 'name' that you want to display different
     * options in, depending on the contents of the 'color' field the array should
     * have the following structure:
     * array(
     *      array(
     *          "keys" =>   array("male", "dude"),
     *          "options"   =>  array(
     *              "bryan" =>  "Bryan",
     *              "joao"  =>  "Joao",
     *              "bob"   =>  "Bob"
     *          )
     *      ),
     *      array(
     *          "keys"  =>  array("female", "chick"),
     *          "options"   =>  array(
     *              "freya" =>  "Freya",
     *              "becky" =>  "Becky",
     *              "sharon"    =>  "Sharon",
     *              "layla"     =>  "Layla"
     *          )
     *      )
     * );
     * 
     * @return  array An array of data to display
     */
   function getStructuredData()
   {
       return array();
   }
   
   
    /**
     * Returns the ID of the "controlling" custom field.
     * 
     * @return   integer The ID of the controlling custom field
     */
    function getControllingCustomFieldID()
    {
        return 0;
    }
   
   
    /**
     * Returns the name of the "controlling" custom field.
     * 
     * @return   string The name of the controlling custom field
     */
    function getControllingCustomFieldName()
    {
        return '';
    }
    
    
    /**
     * Returns true if this row should be hidden if it has no value
     * 
     * @return  boolean True if this field should be hidden before options are set
     */
    function hideWhenNoOptions()
    {
        return false;
    }
}


?>