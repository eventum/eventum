<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005 MySQL AB                              |
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

// this line needed to make sure PEAR knows all eventum dates are stored as UTC (GMT).
$_DATE_TIMEZONE_DEFAULT = 'UTC';

include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.prefs.php");
include_once(APP_PEAR_PATH . "Date.php");

if (!defined('APP_DEFAULT_TIMEZONE')) {
    define('APP_DEFAULT_TIMEZONE', 'UTC');
}
define("SECOND", 1);
define("MINUTE", SECOND * 60);
define("HOUR", MINUTE * 60);
define("DAY", HOUR * 24);
define("WEEK", DAY * 7);
define("MONTH", WEEK * 4);
define("YEAR", MONTH * 12);

/**
 * Class to handle date convertion issues, which enable the
 * application of storing all dates in GMT dates and allowing each
 * user to specify a timezone that is supposed to be used across the
 * pages.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

class Date_API
{
    /**
     * Returns whether the given hour is AM or not.
     *
     * @access  public
     * @param   integer $hour The hour number
     * @return  boolean
     */
    function isAM($hour)
    {
        if (($hour >= 0) && ($hour <= 11)) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Returns whether the given hour is PM or not.
     *
     * @access  public
     * @param   integer $hour The hour number
     * @return  boolean
     */
    function isPM($hour)
    {
        if (($hour >= 12) && ($hour <= 23)) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Returns the current UNIX timestamp in the GMT timezone.
     *
     * @access  public
     * @return  integer The current UNIX timestamp in GMT
     */
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
     * @param   string $timezone The needed timezone
     */
    function getRFC822Date($timestamp, $timezone = FALSE)
    {
        if (!$timezone) {
            $timezone = Date_API::getPreferredTimezone();
        }
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
     * Method used to get the proper short name for a given date.
     *
     * @access  public
     * @param   object $date The Date object
     * @return  string The timezone short name
     */
    function getTimezoneShortName($date)
    {
        if ($date->inDaylightTime()) {
            return $date->tz->getDSTShortName();
        } else {
            return $date->tz->getShortName();
        }
    }


    /**
     * Method used to get the proper timezone short name for the current date
     * and time on the given user's timezone.
     *
     * @access  public
     * @param   object $date The Date object
     * @return  string The timezone short name
     */
    function getTimezoneShortNameByUser($usr_id)
    {
        $date = new Date();
        $date->convertTZById(Date_API::getPreferredTimezone($usr_id));
        return Date_API::getTimezoneShortName($date);
    }


    /**
     * Method used to get the formatted date for a specific timestamp
     * and a specific timezone, provided by the user' preference.
     *
     * @access  public
     * @param   string $timestamp The date timestamp to be formatted
     * @param   string $timezone The timezone name
     * @return  string
     */
    function getFormattedDate($timestamp, $timezone = FALSE)
    {
        if ($timezone === FALSE) {
            $timezone = Date_API::getPreferredTimezone();
        }
        $date = new Date($timestamp);
        // now convert to another timezone and return the date
        $date->convertTZById($timezone);
        return $date->format('%a, %d %b %Y, %H:%M:%S ') . Date_API::getTimezoneShortName($date);
    }


    /**
     * Method used to get the formatted date for a specific timestamp
     * and a specific timezone, provided by the user' preference.
     *
     * @access  public
     * @param   string $timestamp The date timestamp to be formatted
     * @param   boolean $convert If the timestamp should be converted to the preferred timezone
     * @return  string
     */
    function getSimpleDate($timestamp, $convert = true)
    {
        if (empty($timestamp)) {
            return '';
        }
        $date = new Date($timestamp);
        // now convert to another timezone and return the date
        if ($convert) {
            $timezone = Date_API::getPreferredTimezone();
            $date->convertTZById($timezone);
        }
        return $date->format('%d %b %Y');
    }


    /**
     * Method used to get the timezone preferred by the user.
     *
     * @access  public
     * @param   integer $usr_id The user ID
     * @return  string The timezone preferred by the user
     */
    function getPreferredTimezone($usr_id = FALSE)
    {
        if ($usr_id === FALSE) {
            $usr_id = Auth::getUserID();
        }
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


    /**
     * Method used to convert a unix timestamp date to a GMT date.
     *
     * @access  public
     * @param   integer $timestamp The user based date
     * @return  string The date in the GMT timezone
     */
    function getDateGMTByTS($timestamp)
    {
        return gmdate('Y-m-d H:i:s', $timestamp);
    }


    /**
     * Returns a list of weeks (May 2 - May 8, May 9 - May 15).
     *
     * @access public
     * @param   integer $weeks_past The number of weeks in the past to include.
     * @param   integer $weeks_future The number of weeks in the future to include.
     * @return  array An array of weeks.
     */
    function getWeekOptions($weeks_past, $weeks_future)
    {
        $options = array();

        // get current week details
        $current_start = date("U") - (DAY * (date("w") - 1));

        // previous weeks
        for ($week = $weeks_past; $week > 0; $week--) {
            $option = Date_API::formatWeekOption($current_start - ($week * WEEK));
            $options[$option[0]] = $option[1];
        }

        $option = Date_API::formatWeekOption($current_start);
        $options[$option[0]] = $option[1];

        // future weeks
        for ($week = 1; $week <= $weeks_future; $week++) {
            $option = Date_API::formatWeekOption($current_start + ($week * WEEK));
            $options[$option[0]] = $option[1];
        }

        return $options;
    }


    /**
     * Returns the current week in the same format formatWeekOption users.
     *
     * @access  public
     * @return  string A string containg the current week.
     */
    function getCurrentWeek()
    {
        $value_format = "Y-m-d";
        $start = date("U") - (DAY * (date("w") - 1));
        return date($value_format, $start) . "_" . date($value_format, ($start + (DAY * 6)));
    }


    /**
     * Formats a given week start and week end to a format useable by getWeekOptions().
     *
     * @access  private
     * @param   integer $start The start date of the week.
     * @return  array An array usable as an option in getWeekOptions.
     */
    function formatWeekOption($start)
    {
        $value_format = "Y-m-d";
        $display_format = "M jS";
        $end = ($start + (DAY * 6));
        $value = date($value_format, $start) . "_" . date($value_format, $end);
        $display = date($display_format, $start) . " - " . date($display_format, $end);
        return array($value,$display);
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Date_API Class');
}
?>