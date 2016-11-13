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
 * Custom field backend showing example default value
 */
class Default_Value_Custom_Field_Backend
{
    public function getDefaultValue($fld_id)
    {
        // your logic here
        return '123';
    }
}
