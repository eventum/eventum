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
 * Smarty plugin
 */

/**
 * Smarty relative date / time plugin
 *
 * Type:     modifier<br>
 * Name:     relative_datetime<br>
 * Date:     March 18, 2009
 * Purpose:  converts a date to a relative time
 * Input:    date to format
 * Example:  {$datetime|relative_datetime}
 * @author   Eric Lamb <eric@ericlamb.net>
 * @version 1.0
 * @param string
 * @return string
 */
function smarty_modifier_relative_datetime($timestamp)
{
    if (!$timestamp) {
        return 'N/A';
    }

    $timestamp = (int)strtotime($timestamp);
    $difference = time() - $timestamp;
    $periods = ['sec', 'min', 'hour', 'day', 'week', 'month', 'year', 'decade'];
    $lengths = ['60', '60', '24', '7', '4.35', '12', '10'];
    $total_lengths = count($lengths);

    if ($difference > 0) { // this was in the past
        $ending = 'ago';
    } else { // this was in the future
        $difference = -$difference;
        $ending = ' from now';
    }
    //return;

    for ($j = 0; $difference > $lengths[$j] && $total_lengths > $j; $j++) {
        $difference /= $lengths[$j];
    }

    $difference = round($difference);
    if ($difference != 1) {
        $periods[$j] .= 's';
    }

    $text = "$difference $periods[$j] $ending";

    return $text;
}
