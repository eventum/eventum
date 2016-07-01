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

/**
 * Custom field backend to assist other backends in dynamically changing the
 * contents of one field or hiding/showing based on another field.
 */
class Dynamic_Custom_Field_Backend
{
    public function getList($fld_id, $issue_id = false)
    {
        $list = [];
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
   public function getStructuredData()
   {
       return [];
   }

    /**
     * Returns the ID of the "controlling" custom field.
     *
     * @return   integer The ID of the controlling custom field
     */
    public function getControllingCustomFieldID()
    {
        return 0;
    }

    /**
     * Returns the name of the "controlling" custom field.
     *
     * @return   string The name of the controlling custom field
     */
    public function getControllingCustomFieldName()
    {
        return '';
    }

    /**
     * Returns true if this row should be hidden if it has no value
     *
     * @return  boolean True if this field should be hidden before options are set
     */
    public function hideWhenNoOptions()
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
    public function getDomID()
    {
        return 'custom_field_' . $this->getControllingCustomFieldID();
    }

    /**
     * Should return 'local' or 'ajax'. If ajax is specified then getDynamicOptions()
     * should be implemented as well
     *
     * @return string
     */
    public function lookupMethod()
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
    public function getDynamicOptions($data)
    {
        return null;
    }
}
