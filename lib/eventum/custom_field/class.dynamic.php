<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2012 Eventum Team.                              |
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
 * Custom field backend to assist other backends in dynamically changing the
 * contents of one field or hiding/showing based on another field.
 *
 * @author Bryan Alsdorf <bryan@mysql.com>
 */
class Dynamic_Custom_Field_Backend
{
    function getList($fld_id, $issue_id = false)
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

    /**
     * Returns the DOM ID of the controlling field, by default this will return
     * 'custom_field_XX' where XX is the ID returned by getControllingCustomFieldID()
     * but this should be overridden if a field other then a custom field
     * is used.
     *
     * @return  string
     */
    function getDomID()
    {
        return 'custom_field_' . $this->getControllingCustomFieldID();
    }

    /**
     * Should return 'local' or 'ajax'. If ajax is specified then getDynamicOptions()
     * should be implemented as well
     *
     * @return string
     */
    function lookupMethod()
    {
        return 'local';
    }

    /**
     * This method should return the correct options to display for the given
     * data. This array of data will contain all the information from the
     * new issue form or the edit custom field form (as appropriate)
     * @param   $data   array
     * @return  array
     */
    function getDynamicOptions($data)
    {
        return null;
    }
}
