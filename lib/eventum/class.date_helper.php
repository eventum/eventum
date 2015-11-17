<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2015 Eventum Team.                              |
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
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+

/**
 * Class to handle date conversion issues, which enable the
 * application of storing all dates in GMT dates and allowing each
 * user to specify a timezone that is supposed to be used across the
 * pages.
 */
class Date_Helper
{
    const SECOND = 1;
    const MINUTE = 60;
    const HOUR = 3600;
    const DAY = 86400;
    const WEEK = 604800;
    // MONTH and YEAR are rather approximate (4 weeks in month), do not use them
    const MONTH = 2419200; // WEEK * 4
    const YEAR = 29030400; // MONTH * 12

    /**
     * Creates a new DateTime Object initialized to the current date/time in
     * the GMT timezone by default.
     * A date optionally passed in may be in the ISO 8601, UNIXTIME format, or
     * another DateTime object. If no date is passed, the current date/time is
     * used.
     *
     * @param int|DateTime|string $ts
     * @param string $timezone
     * @return DateTime
     */
    public static function getDateTime($ts = 'now', $timezone = null)
    {
        if ($ts instanceof DateTime) {
            $dateTime = clone $ts;
        } else {
            if ($ts === false || $ts === null) {
                $ts = 'now';
            } elseif (is_int($ts)) {
                $ts = "@$ts";
            }

            $dateTime = new DateTime($ts, new DateTimeZone('GMT'));
        }

        if (!$timezone) {
            $timezone = self::getPreferredTimezone();
        }
        try {
            $dateTime->setTimeZone(new DateTimeZone($timezone));
        } catch (Exception $e) {
            // Yes, the exception name is just "Exception":
            // "Exception : DateTimeZone::__construct(): Unknown or bad timezone (Eastern Standard Time)"
            // invalid timezone, ignore and use UTC
        }

        return $dateTime;
    }

    /**
     * Returns whether the given hour is AM or not.
     *
     * @param   integer $hour The hour number
     * @return  boolean
     */
    public static function isAM($hour)
    {
        return $hour >= 0 && $hour <= 11;
    }

    /**
     * Returns whether the given hour is PM or not.
     *
     * @param   integer $hour The hour number
     * @return  boolean
     */
    public static function isPM($hour)
    {
        return $hour >= 12 && $hour <= 23;
    }

    /**
     * Returns the current UNIX timestamp in the GMT timezone.
     *
     * @return  integer The current UNIX timestamp in GMT
     * @deprecated just use time() function
     */
    public static function getCurrentUnixTimestampGMT()
    {
        return time();
    }

    /**
     * Method used to get a pretty-like formatted time output for the
     * difference in time between two dates.
     *
     * NOTE: "h" and "d" (hours and days) are not localized
     *
     * @param integer|string|DateTime $now The current timestamp
     * @param integer|string|DateTime $date The old timestamp
     * @return string The formatted difference in time
     */
    public static function getFormattedDateDiff($now, $date)
    {
        $now_ts = self::getDateTime($now)->getTimestamp();
        $old_ts = self::getDateTime($date)->getTimestamp();
        $value = (integer) (($now_ts - $old_ts) / self::DAY);
        $ret = sprintf('%d', round($value, 1)) . 'd';
        $mod = (integer) (($now_ts - $old_ts) % self::DAY);
        $mod = (integer) ($mod / self::HOUR);

        return $ret . ' ' . $mod . 'h';
    }

    /**
     * Method used to get the user's current time (timezone included) as
     * a UNIX timestamp.
     *
     * @param   integer|string $timestamp The current UNIX timestamp
     * @param   string $timezone The needed timezone
     * @return  integer The UNIX timestamp representing the user's current time
     * @deprecated do not use when input is timestamp, the same input will be returned and calling this function is pointless then
     */
    public static function getUnixTimestamp($timestamp, $timezone = null)
    {
        $date = self::getDateTime($timestamp, $timezone);

        return $date->getTimestamp();
    }

    /**
     * Method used to get the current date in the GMT timezone in an
     * RFC822 compliant format.
     *
     * NOTE: $timezone param is deprecated as input is always GMT and result is also always GMT
     *
     * @return  string $ts The current GMT date
     * @param   string $timezone The needed timezone
     */
    public static function getRFC822Date($ts, $timezone = null)
    {
        $date = self::getDateTime($ts, 'GMT');

        return $date->format('D, d M Y H:i:s') . ' GMT';
    }

    /**
     * Method used to get the current date and time in an
     * iso8601 compliant format. If no timezone is specified it will
     * use the users preferred timezone.
     *
     * @return  string $ts The current GMT date
     * @param   int|DateTime|string $ts
     * @param   string $timezone
     * @param   bool $omit_offset
     */
    public static function getISO8601date($ts, $timezone = null, $omit_offset = false)
    {
        $date = self::getDateTime($ts, $timezone);

        if ($omit_offset) {
            $fmt = 'Y-m-d\TH:i:s';
        } else {
            $fmt = DateTime::ISO8601;
        }

        return $date->format($fmt);
    }

    /**
     * Method used to get the current date in the GMT timezone.
     *
     * @return  string The current GMT date in DATE_FORMAT_ISO (YYYY-MM-DD HH:MM:SS) format.
     */
    public static function getCurrentDateGMT()
    {
        return gmdate('Y-m-d H:i:s');
    }

    /**
     * Method used to get the full list of available timezones to be
     * presented to the user.
     *
     * @return  array The list of timezones
     */
    public static function getTimezoneList()
    {
        $time_zones = timezone_identifiers_list();
        asort($time_zones);

        return $time_zones;
    }

    /**
     * Method used to get the proper timezone short name for the current date
     * and time on the given user's timezone. This respects the DST setting.
     *
     * @param   integer $usr_id The user ID
     * @return  string The timezone short name
     */
    public static function getTimezoneShortNameByUser($usr_id)
    {
        $timezone = self::getPreferredTimezone($usr_id);
        $date = self::getDateTime(null, $timezone);

        return $date->format('T');
    }

    /**
     * Method used to get the formatted date for a specific timestamp
     * and a specific timezone, provided by the user preference.
     *
     * This method is locale sensitive, returns localized timestamp
     *
     * @param   string $ts The date timestamp to be formatted
     * @param   string $timezone The timezone name
     * @return  string
     */
    public static function getFormattedDate($ts, $timezone = null)
    {
        $date = self::getDateTime($ts, $timezone);

        return self::formatLocalized($date, '%a, %d %b %Y, %H:%M:%S %Z');
    }

    /**
     * Method used to get the formatted date for a specific timestamp
     * and a specific timezone, provided by the user' preference.
     *
     * This method is locale sensitive, returns localized timestamp
     *
     * @param   string $ts The date timestamp to be formatted
     * @param   boolean $convert If the timestamp should be converted to the preferred timezone
     * @return  string
     */
    public static function getSimpleDate($ts, $convert = true)
    {
        if (!$ts) {
            // FIXME: why
            return '';
        }

        // FIXME: the current logic below ends up the same result
        // now convert to another timezone and return the date
        if ($convert) {
            $timezone = self::getPreferredTimezone();
        } else {
            $timezone = false;
        }

        $date = self::getDateTime($ts, $timezone);

        return self::formatLocalized($date, '%d %b %Y');
    }

    /**
     * Method used to get the timezone preferred by the user.
     *
     * @param integer $usr_id The user ID
     * @return string The timezone preferred by the user
     */
    public static function getPreferredTimezone($usr_id = null)
    {
        if (!$usr_id) {
            $usr_id = Auth::getUserID();
        }
        if (!$usr_id) {
            return self::getDefaultTimezone();
        }
        $prefs = Prefs::get($usr_id);
        if (!empty($prefs['timezone'])) {
            return $prefs['timezone'];
        }

        return self::getDefaultTimezone();
    }

    /**
     * Method used to get the application default timezone.
     *
     * @return  string The default timezone
     */
    public static function getDefaultTimezone()
    {
        return APP_DEFAULT_TIMEZONE;
    }

    /**
     * Method used to get the default start of week day.
     *
     * @return  integer 0 - Sunday, 1 - Monday
     */
    public static function getDefaultWeekday()
    {
        return APP_DEFAULT_WEEKDAY;
    }

    /**
     * Method used to convert the user date (that is in a specific timezone) to
     * a GMT date.
     *
     * @param string $ts The date in users timezone
     * @return string The date in the GMT timezone
     */
    public static function convertDateGMT($ts)
    {
        $date = self::getDateTime($ts);
        $date->setTimezone(new DateTimeZone('GMT'));

        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Get Date in MySQL DATETIME format
     *
     * @param DateTime|string $ts
     * @param string $tz
     * @return string
     */
    public static function getSqlDateTime($ts, $tz = null)
    {
        $date = self::getDateTime($ts, $tz);

        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Method used to convert a unix timestamp date to a GMT date.
     *
     * @param   integer $timestamp The user based date
     * @return  string The date in the GMT timezone
     * @deprecated convertDateGMT can do exactly the same
     */
    public static function convertDateGMTByTS($timestamp)
    {
        return gmdate('Y-m-d H:i:s', $timestamp);
    }

    /**
     * Returns a list of weeks (May 2 - May 8, May 9 - May 15).
     *
     * @param   integer $weeks_past The number of weeks in the past to include.
     * @param   integer $weeks_future The number of weeks in the future to include.
     * @return  array An array of weeks.
     */
    public static function getWeekOptions($weeks_past, $weeks_future)
    {
        $options = array();

        // get current week details
        $current_start = date('U') - (self::DAY * (date('w') - 1));

        // previous weeks
        for ($week = $weeks_past; $week > 0; $week--) {
            $option = self::formatWeekOption($current_start - ($week * self::WEEK));
            $options[$option[0]] = $option[1];
        }

        $option = self::formatWeekOption($current_start);
        $options[$option[0]] = $option[1];

        // future weeks
        for ($week = 1; $week <= $weeks_future; $week++) {
            $option = self::formatWeekOption($current_start + ($week * self::WEEK));
            $options[$option[0]] = $option[1];
        }

        return $options;
    }

    /**
     * Returns the current week in the same format formatWeekOption users.
     *
     * @return  string A string containing the current week.
     */
    public static function getCurrentWeek()
    {
        $value_format = 'Y-m-d';
        $start = date('U') - (self::DAY * (date('w') - 1));

        return date($value_format, $start) . '_' . date($value_format, ($start + (self::DAY * 6)));
    }

    /**
     * Formats a given week start and week end to a format useable by getWeekOptions().
     *
     * @param   integer $start The start date of the week.
     * @return  array An array usable as an option in getWeekOptions.
     */
    private static function formatWeekOption($start)
    {
        $value_format = 'Y-m-d';
        $display_format = 'M jS';
        $end = ($start + (self::DAY * 6));
        $value = date($value_format, $start) . '_' . date($value_format, $end);
        $display = date($display_format, $start) . ' - ' . date($display_format, $end);

        return array($value, $display);
    }

    /**
     * Format $date under current locale. Uses strftime format.
     *
     * @param DateTime $date
     * @param string $fmt strftime date format
     * @return string
     */
    private static function formatLocalized($date, $fmt)
    {
        // use gmstrftime for GMT timezone, this matches expectations
        $offset = $date->getTimezone()->getOffset($date);
        if ($offset == 0) {
            return gmstrftime($fmt, $date->getTimestamp());
        }

        // as can't tell strftime timezone, set default timezone temporarily
        // set tz only if it differs
        $current_tz = date_default_timezone_get();
        $tz = $date->getTimezone()->getName();
        if ($current_tz != $tz) {
            date_default_timezone_set($tz);
            unset($tz);
        }
        $res = strftime($fmt, $date->getTimestamp());
        if (!isset($tz)) {
            date_default_timezone_set($current_tz);
        }

        return $res;
    }
}
