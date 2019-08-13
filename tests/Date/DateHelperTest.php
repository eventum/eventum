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

namespace Eventum\Test\Date;

use Date_Helper;
use DateTime;
use Eventum\Test\TestCase;
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
     * @dataProvider dataTestParse
     */
    public function testParse($input, array $exp): void
    {
        $date = Date_Helper::getDateTime($input);
        $this->assertEquals($exp, [
            $date->getTimestamp(),
            $date->getOffset(),
            $date->getTimezone()->getName(),
            $date->format('Y-m-d H:i:s.u'),
        ]);
    }

    public function dataTestParse(): array
    {
        return [
            [
                1565687097,
                [
                    0 => 1565687097,
                    1 => 10800,
                    2 => 'Europe/Tallinn',
                    3 => '2019-08-13 12:04:57.000000',
                ],
            ],
            [
                '1565687097',
                [
                    0 => 1565687097,
                    1 => 10800,
                    2 => 'Europe/Tallinn',
                    3 => '2019-08-13 12:04:57.000000',
                ],
            ],
            [
                1565687097.1584,
                [
                    0 => 1565687097,
                    1 => 10800,
                    2 => 'Europe/Tallinn',
                    3 => '2019-08-13 12:04:57.158400',
                ],
            ],
            [
                '1565687097.1584',
                [
                    0 => 1565687097,
                    1 => 10800,
                    2 => 'Europe/Tallinn',
                    3 => '2019-08-13 12:04:57.158400',
                ],
            ],
        ];
    }

    /**
     * @covers       Date_Helper::isAM
     * @dataProvider dataTestIsAM
     */
    public function testIsAM($input, $exp): void
    {
        $res = Date_Helper::isAM($input);
        $this->assertEquals($exp, $res);
    }

    public function dataTestIsAM(): array
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
     * @dataProvider dataTestIsPM
     */
    public function testIsPM($input, $exp): void
    {
        $res = Date_Helper::isPM($input);
        $this->assertEquals($exp, $res);
    }

    public function dataTestIsPM(): array
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
     * @dataProvider dataTestGetFormattedDateDiff
     */
    public function testGetFormattedDateDiff($ts1, $ts2, $exp): void
    {
        $res = Date_Helper::getFormattedDateDiff($ts1, $ts2);
        $this->assertEquals($exp, $res);
    }

    public function dataTestGetFormattedDateDiff(): array
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
     * @dataProvider dataTestGetUnixTimestamp
     */
    public function testGetUnixTimestamp($ts, $tz, $exp): void
    {
        $res = Date_Helper::getUnixTimestamp($ts, $tz);
        $this->assertEquals($exp, $res);
    }

    public function dataTestGetUnixTimestamp(): array
    {
        return [
            // unix timestamps are timezoneless
            [1411842757, false, 1411842757],
            ['2014-09-27 17:03:23', false, 1411837403],
        ];
    }

    /**
     * @covers       Date_Helper::getRFC822Date
     * @dataProvider dataTestGetRFC822Date
     */
    public function testGetRFC822Date($ts, $exp): void
    {
        $res = Date_Helper::getRFC822Date($ts);
        $this->assertEquals($exp, $res);
    }

    public function dataTestGetRFC822Date(): array
    {
        return [
            [1411842757, 'Sat, 27 Sep 2014 18:32:37 GMT'],
        ];
    }

    /**
     * @covers       Date_Helper::getFormattedDate
     * @dataProvider dataTestGetFormattedDate
     */
    public function testGetFormattedDate($input, $exp): void
    {
        $res = Date_Helper::getFormattedDate($input);
        $this->assertEquals($exp, $res);
    }

    public function dataTestGetFormattedDate(): array
    {
        return [
            [0, 'Thu, 01 Jan 1970, 00:00:00 GMT'],
            [1411840837, 'Sat, 27 Sep 2014, 18:00:37 GMT'],
        ];
    }

    /**
     * @covers       Date_Helper::getSimpleDate
     * @dataProvider dataTestGetSimpleDate
     */
    public function testGetSimpleDate($ts, $convert, $exp): void
    {
        $res = Date_Helper::getSimpleDate($ts, $convert);
        $this->assertEquals($exp, $res);
    }

    public function dataTestGetSimpleDate(): array
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
     * @dataProvider dataTestConvertDateGMT
     */
    public function testConvertDateGMT($date, $exp): void
    {
        $res = Date_Helper::convertDateGMT($date);
        $this->assertEquals($exp, $res);
    }

    public function dataTestConvertDateGMT(): array
    {
        return [
            ['2014-09-27 00:00:00', '2014-09-27 00:00:00'],
            ['Sun Sep 28 09:46:50 EEST 2014', '2014-09-28 06:46:50'],
            ['Sun Sep 28 06:47:25 GMT 2014', '2014-09-28 06:47:25'],
        ];
    }

    /**
     * @dataProvider dataTestInvalidTimezone
     */
    public function testInvalidTimezone($ts, $tz, $exp): void
    {
        $date = Date_Helper::getFormattedDate($ts, $tz);
        $this->assertEquals($exp, $date);
    }

    public function dataTestInvalidTimezone(): array
    {
        return [
            ['Sat Oct 11 11:51:12 EEST 2014', 'Europe/Tallinn', 'Sat, 11 Oct 2014, 11:51:12 EEST'],
            ['Sat Oct 11 11:51:12 EEST 2014', 'America/New_York', 'Sat, 11 Oct 2014, 04:51:12 EDT'],
//            ["Sat Oct 11 11:51:12 EEST 2014", "Eastern Standard Time", "Sat, 11 Oct 2014, 08:51:12 UTC"],
            ['2014-10-14 11:32:57', 'Eastern Standard Time', 'Tue, 14 Oct 2014, 11:32:57 GMT'],
            ['2014-10-14 11:32:57', 'America/New_York', 'Tue, 14 Oct 2014, 07:32:57 EDT'],
        ];
    }

    public function testTzNamingDifferences(): void
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
    public function testBug_204(): void
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
