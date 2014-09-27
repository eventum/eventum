<?php

class Date_HelperTest extends PHPUnit_Framework_TestCase
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
        return array(
            array(0, true),
            array(10, true),
            array('20', false),
            array(42, false),
        );
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
        return array(
            array(0, false),
            array(10, false),
            array('20', true),
            array(42, false),
        );
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
        return array(
            array(0, 10, '0d 0h'),
            array(0, 3600, '0d -1h'),
            array(7200, 3600, '0d 1h'),
        );
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
        return array(
            // FIXME: this function is stupid, it should return the input or result of time()
            // unix timestamps are timezoneless
            array(1411842757, false, 1411842757),
        );
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
        return array(
            // FIXME: this value is off, correct value is Sat, 27 Sep 2014 18:32:37 GMT
            array(1411842757, false, 'Sat, 27 Sep 2014 21:32:37 GMT'),
        );
    }

    /**
     * @covers Date_Helper::getTimezoneShortNameByUser
     */
    public function testGetTimezoneShortNameByUser()
    {
        $this->markTestSkipped('Requires database');
        $res = Date_Helper::getTimezoneShortNameByUser(APP_SYSTEM_USER_ID);
        $this->assertEquals('UTC', $res);
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
        return array(
            array(0, 'Thu, 01 Jan 1970, 00:00:00 UTC'),
            array(1411840837, 'Sat, 27 Sep 2014, 18:00:37 UTC'),
        );
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
        return array(
            array(1391205600, false, '01 Feb 2014'),
            array(1391291999, false, '01 Feb 2014'),
        );
    }

    /**
     * @covers Date_Helper::getPreferredTimezone
     */
    public function testGetPreferredTimezone()
    {
        $this->markTestSkipped('Requires database');
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
        return array(
            // FIXME: this is probably wrong
            array('2014-09-27 00:00:00', '2014-09-27 00:00:00'),
        );
    }

    /**
     * @covers Date_Helper::getWeekOptions
     * @todo   Implement testGetWeekOptions().
     */
    public function testGetWeekOptions()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers Date_Helper::getCurrentWeek
     * @todo   Implement testGetCurrentWeek().
     */
    public function testGetCurrentWeek()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers       Date_Helper::getSecondsDiff
     * @dataProvider testGetSecondsDiff_data
     */
    public function testGetSecondsDiff($ts1, $ts2, $exp)
    {
        // NOTE: this is supoer pointless function
        $res = Date_Helper::getSecondsDiff($ts1, $ts2);
        $this->assertEquals($exp, $res);
    }

    public function testGetSecondsDiff_data()
    {
        return array(
            array(0, 10, 10),
            array(0, 3600, 3600),
            array(7200, 3600, -3600),
            array(3600, 7200, 3600),
        );
    }
}
