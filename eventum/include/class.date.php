<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004 MySQL AB                                    |
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
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// +----------------------------------------------------------------------+
//
// @(#) $Id: s.class.date.php 1.18 03/12/31 17:29:00-00:00 jpradomaia $
//


/**
 * Class to handle date convertion issues, which enable the 
 * application of storing all dates in GMT dates and allowing each
 * user to specify a timezone that is supposed to be used across the
 * pages.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.prefs.php");
include_once(APP_PEAR_PATH . "Date.php");

define('APP_DEFAULT_TIMEZONE', 'UTC');
define("SECOND", 1);
define("MINUTE", SECOND * 60);
define("HOUR", MINUTE * 60);
define("DAY", HOUR * 24);
define("WEEK", DAY * 7);
define("MONTH", WEEK * 4);

class Date_API
{
    function getCurrentUnixTimestampGMT()
    {
        return gmmktime();
    }


    /**
     * Method used to get a pretty-like formatted time output for the
     * difference in time between two unix timestamps.
     *
     * @access  public
     * @param   integer $now_ts The current UNIX timestamp
     * @param   integer $old_ts The old UNIX timestamp
     * @return  string The formatted difference in time
     */
    function getFormattedDateDiff($now_ts, $old_ts)
    {
        $value = (integer) (($now_ts - $old_ts) / DAY);
        $ret = sprintf("%d", round($value, 1)) . "d";
        $mod = (integer) (($now_ts - $old_ts) % DAY);
        $mod = (integer) ($mod / HOUR);
        return $ret . " " . $mod . "h";
    }


    /**
     * Method used to get the user's current time (timezone included) as
     * a UNIX timestamp.
     *
     * @access  public
     * @param   integer $timestamp The current UNIX timestamp
     * @param   string $timezone The needed timezone
     * @return  integer The UNIX timestamp representing the user's current time
     */
    function getUnixTimestamp($timestamp, $timezone = FALSE)
    {
        if (!$timezone) {
            $timezone = Date_API::getPreferredTimezone();
        }
        $date = new Date($timestamp);
        // now convert to another timezone and return the timestamp
        $date->convertTZById($timezone);
        return $date->getDate(DATE_FORMAT_UNIXTIME);
    }


    /**
     * Method used to get the current date in the GMT timezone in an 
     * RFC822 compliant format.
     *
     * @access  public
     * @return  string The current GMT date
     */
    function getRFC822Date($timestamp)
    {
        $timezone = Date_API::getPreferredTimezone();
        $date = new Date($timestamp);
        // now convert to another timezone and return the date
        $date->convertTZById($timezone);
        return $date->format('%a, %d %b %Y %H:%M:%S') . " GMT";
    }


    /**
     * Method used to get the current date in the GMT timezone.
     *
     * @access  public
     * @return  string The current GMT date
     */
    function getCurrentDateGMT()
    {
        return gmdate('Y-m-d H:i:s');
    }


    /**
     * Method used to get the full list of available timezones to be
     * presented to the user.
     *
     * @access  public
     * @return  array The list of timezones
     */
    function getTimezoneList()
    {
        return Date_TimeZone::getAvailableIDs();
    }


    /**
     * Method used to get the formatted date for a specific timestamp
     * and a specific timezone, provided by the user' preference.
     *
     * @access  public
     * @param   string $timestamp The date timestamp to be formatted
     * @return  string 
     */
    function getFormattedDate($timestamp)
    {
        $timezone = Date_API::getPreferredTimezone();
        $date = new Date($timestamp);
        // now convert to another timezone and return the date
        $date->convertTZById($timezone);
        return $date->format('%a, %d %b %Y, %H:%M:%S ') . $date->tz->getShortName();
    }


    /**
     * Method used to get the timezone preferred by the user.
     *
     * @access  public
     * @return  string The timezone preferred by the user
     */
    function getPreferredTimezone()
    {
        $usr_id = Auth::getUserID();
        if (empty($usr_id)) {
            return Date_API::getDefaultTimezone();
        }
        $prefs = Prefs::get($usr_id);
        if (empty($prefs["timezone"])) {
            return Date_API::getDefaultTimezone();
        } else {
            return $prefs["timezone"];
        }
    }


    /**
     * Method used to get the application default timezone.
     *
     * @access  public
     * @return  string The default timezone
     */
    function getDefaultTimezone()
    {
        return APP_DEFAULT_TIMEZONE;
    }


    /**
     * Method used to convert the user date (that might be in a 
     * specific timezone) to a GMT date.
     *
     * @access  public
     * @param   string $date The user based date
     * @return  string The date in the GMT timezone
     */
    function getDateGMT($date)
    {
        $dt = new Date($date);
        $dt->setTZbyID(Date_API::getPreferredTimezone());
        $dt->toUTC();
        return $dt->format('%Y-%m-%d %H:%M:%S');
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Date_API Class');
}
?>