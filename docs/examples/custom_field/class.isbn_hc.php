<?php

class Isbn_Hc_Custom_Field_Backend extends Dynamic_Custom_Field_Backend
{
    public function getStructuredData()
    {
        $fld_id = self::getControllingCustomFieldID();
        // should pull from a dynamic data source but will hard code for now
        $data = array(
            array(
                "keys" => array(Custom_Field::getOptionKey($fld_id, 'present')),
                "options" => array()
            )
        );
        return $data;
    }

    public function getControllingCustomFieldID()
    {
        return Custom_Field::getIDByTitle(self::getControllingCustomFieldName());
    }

    public function getControllingCustomFieldName()
    {
        return '';
    }

    public function hideWhenNoOptions()
    {
        return true;
    }
}
