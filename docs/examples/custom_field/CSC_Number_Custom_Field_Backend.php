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

class CSC_Number_Custom_Field_Backend
{
    public function formatValue($value, $fld_id, $iss_id)
    {
        if (!empty($value)) {
            $numbers = explode(',', $value);
            $links = [];
            foreach ($numbers as $number) {
                $links[] = "<a href=\"https://example.com/view.php?id=$number\" target=\"csc_$number\" class=\"link\">$number</a>";
            }

            return implode(', ', $links);
        } else {
            return $value;
        }
    }
}
