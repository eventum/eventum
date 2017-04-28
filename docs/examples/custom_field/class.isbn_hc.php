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

class Isbn_Hc_Custom_Field_Backend extends Dynamic_Custom_Field_Backend
{
    public function getStructuredData()
    {
        $fld_id = self::getControllingCustomFieldID();
        // should pull from a dynamic data source but will hard code for now
        $data = [
            [
                'keys' => [Custom_Field::getOptionKey($fld_id, 'present')],
                'options' => [],
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
        return '';
    }

    public function hideWhenNoOptions()
    {
        return true;
    }
}
