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

namespace Eventum\Test;

use Date_Helper;
use DateTime;
use Exception;

/**
 * DateHelper tests that do not use database.
 * Put user specific tests (that requires database) to DateHelperUserTests class
 *
 * @group date
 */
class DateHelperTest extends TestCase
{
    /**
     * @covers       Date_Helper::isAM
     * @dataProvider testIsAM_data
     */
    public function testIsAM($input, $exp)
    {
        $res = Date_Helper::isAM($input);
        $this->assertEquals($exp, $res);
    }

    public function testIsAM_data()
    {
        return [
            [0, true],
            [10, true],
            ['20', false],
            [42, false],
        ];
    }

    /**
     * @covers       Date_Helper::isPM
     * @dataProvider testIsPM_data
     */
    public function testIsPM($input, $exp)
    {
        $res = Date_Helper::isPM($input);
        $this->assertEquals($exp, $res);
    }

    public function testIsPM_data()
    {
        return [
            [0, false],
            [10, false],
            ['20', true],
            [42, false],
        ];
    }

    /**
     * @covers       Date_Helper::getFormattedDateDiff
     * @dataProvider testGetFormattedDateDiff_data
     */
    public function testGetFormattedDateDiff($ts1, $ts2, $exp)
    {
        $res = Date_Helper::getFormattedDateDiff($ts1, $ts2);
        $this->assertEquals($exp, $res);
    }

    public function testGetFormattedDateDiff_data()
    {
        return [
            [0, 10, '0d 0h'],
            [0, 3600, '0d -1h'],
            [7200, 3600, '0d 1h'],
            [new DateTime('tomorrow 04:00'), new DateTime('today 00:00'), '1d 4h'],
        ];
    }

    /**
     * @covers       Date_Helper::getUnixTimestamp
     * @dataProvider testGetUnixTimestamp_data
     */
    public function testGetUnixTimestamp($ts, $tz, $exp)
    {
        $res = Date_Helper::getUnixTimestamp($ts, $tz);
        $this->assertEquals($exp, $res);
    }

    public function testGetUnixTimestamp_data()
    {
        return [
            // unix timestamps are timezoneless
            [1411842757, false, 1411842757],
            ['2014-09-27 17:03:23', false, 1411837403],
        ];
    }

    /**
     * @covers       Date_Helper::getRFC822Date
     * @dataProvider testGetRFC822Date_data
     */
    public function testGetRFC822Date($ts, $tz, $exp)
    {
        $res = Date_Helper::getRFC822Date($ts, $tz);
        $this->assertEquals($exp, $res);
    }

    public function testGetRFC822Date_data()
    {
        return [
            [1411842757, false, 'Sat, 27 Sep 2014 18:32:37 GMT'],
        ];
    }

    /**
     * @covers       Date_Helper::getFormattedDate
     * @dataProvider testGetFormattedDate_data
     */
    public function testGetFormattedDate($input, $exp)
    {
        $res = Date_Helper::getFormattedDate($input);
        $this->assertEquals($exp, $res);
    }

    public function testGetFormattedDate_data()
    {
        return [
            [0, 'Thu, 01 Jan 1970, 00:00:00 GMT'],
            [1411840837, 'Sat, 27 Sep 2014, 18:00:37 GMT'],
        ];
    }

    /**
     * @covers       Date_Helper::getSimpleDate
     * @dataProvider testGetSimpleDate_data
     */
    public function testGetSimpleDate($ts, $convert, $exp)
    {
        $res = Date_Helper::getSimpleDate($ts, $convert);
        $this->assertEquals($exp, $res);
    }

    public function testGetSimpleDate_data()
    {
        return [
            [1391212800, false, '01 Feb 2014'],
            [1391299199, false, '01 Feb 2014'],
            [1391212800, true, '01 Feb 2014'],
            [1391299199, true, '01 Feb 2014'],
        ];
    }

    /**
     * @covers       Date_Helper::convertDateGMT
     * @dataProvider testConvertDateGMT_data
     */
    public function testConvertDateGMT($date, $exp)
    {
        $res = Date_Helper::convertDateGMT($date);
        $this->assertEquals($exp, $res);
    }

    public function testConvertDateGMT_data()
    {
        return [
            ['2014-09-27 00:00:00', '2014-09-27 00:00:00'],
            ['Sun Sep 28 09:46:50 EEST 2014', '2014-09-28 06:46:50'],
            ['Sun Sep 28 06:47:25 GMT 2014', '2014-09-28 06:47:25'],
        ];
    }

    /**
     * @dataProvider testInvalidTimezone_data
     */
    public function testInvalidTimezone($ts, $tz, $exp)
    {
        $date = Date_Helper::getFormattedDate($ts, $tz);
        $this->assertEquals($exp, $date);
    }

    public function testInvalidTimezone_data()
    {
        return [
            ['Sat Oct 11 11:51:12 EEST 2014', 'Europe/Tallinn', 'Sat, 11 Oct 2014, 11:51:12 EEST'],
            ['Sat Oct 11 11:51:12 EEST 2014', 'America/New_York', 'Sat, 11 Oct 2014, 04:51:12 EDT'],
//            ["Sat Oct 11 11:51:12 EEST 2014", "Eastern Standard Time", "Sat, 11 Oct 2014, 08:51:12 UTC"],
            ['2014-10-14 11:32:57', 'Eastern Standard Time', 'Tue, 14 Oct 2014, 11:32:57 GMT'],
            ['2014-10-14 11:32:57', 'America/New_York', 'Tue, 14 Oct 2014, 07:32:57 EDT'],
        ];
    }

    public function testGetTimezoneList()
    {
        $pear_timezones = require __DIR__ . '/data/timezones.php';
        $timezones = Date_Helper::getTimezoneList();

        $diff = array_diff($pear_timezones, $timezones);
        printf("%d PEAR timezones\n", count($pear_timezones));
        printf("%d PHP timezones\n", count($timezones));
        printf("%d Differences\n", count($diff));
//        print_r($diff);
    }

    public function testTzNamingDifferences()
    {
        $created_date = Date_Helper::convertDateGMT('2015-05-19 12:22:24 EET');
        $this->assertEquals('2015-05-19 10:22:24', $created_date);

        $created_date = Date_Helper::convertDateGMT('2015-05-19 12:22:24 EEST');
        $this->assertEquals('2015-05-19 09:22:24', $created_date);

        $created_date = Date_Helper::convertDateGMT('2015-05-19 12:22:24 Europe/Tallinn');
        $this->assertEquals('2015-05-19 09:22:24', $created_date);
    }

    /**
     * @see https://github.com/eventum/eventum/issues/204
     */
    public function testBug_204()
    {
        try {
            Date_Helper::convertDateGMT('2016-10-03 10:20:00 US/Central');
            $this->fail();
        } catch (Exception $e) {
            $this->assertEquals(
                'DateTime::__construct(): Failed to parse time string (2016-10-03 10:20:00 US/Central) at position 20 (U): The timezone could not be found in the database',
                $e->getMessage()
            );
        }
        $d = Date_Helper::convertDateGMT('2016-10-03 10:20:00 America/Chicago');
        $this->assertNotEmpty($d);
    }
}
