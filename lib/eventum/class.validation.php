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
 * Class to handle form validation in the server-side, duplicating the
 * javascript based validation available in most forms, to make sure
 * the data integrity is the best possible.
 */
class Validation
{
    /**
     * Method used to check whether a string is totally compromised of
     * whitespace characters, such as spaces, tabs or newlines.
     *
     * @param   string $str The string to check against
     * @return  bool
     */
    public static function isWhitespace($str): bool
    {
        return trim($str) === '';
    }
}
