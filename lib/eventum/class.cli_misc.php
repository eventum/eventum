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
 * Class to hold methods and algorithms that wouldn't fit in other classes, such
 * as functions to work around PHP bugs or incompatibilities between separate
 * PHP configurations.
 */
class CLI_Misc
{
    /**
     * Method used to print a prompt asking the user for information.
     *
     * @param   string $message The message to print
     * @param   string $default_value The default value to be used if the user just press <enter>
     * @return  string The user response
     */
    public static function prompt($message, $default_value)
    {
        echo $message;
        if ($default_value !== false) {
            echo " [default: $default_value] -> ";
        } else {
            echo ' [required] -> ';
        }
        flush();
        $input = trim(self::getInputLine());
        if (empty($input)) {
            if ($default_value === false) {
                die("ERROR: Required parameter was not provided!\n");
            }

            return $default_value;
        }

        return $input;
    }

    /**
     * Method used to get the standard input.
     *
     * @return  string The standard input value
     */
    public static function getInputLine()
    {
        return fgets(STDIN);
    }

    public static function base64_decode($data)
    {
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $data[$k] = self::base64_decode($v);
            }
        } else {
            $data = base64_decode($data);
        }

        return $data;
    }
}
